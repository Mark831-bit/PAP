<?php
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/lib/logger.php';
require_once __DIR__ . '/../config/secrets.php';

$key    = $_POST['key'] ?? '';
$uidRaw = $_POST['uid'] ?? '';

$uid = strtoupper(trim($uidRaw));
$uid = preg_replace('/\s+/', '', $uid);

log_event("INFO", "push request received", ["uid" => $uid]);

// ── 1. Auth ──────────────────────────────────────────────
if (!is_string($key) || !hash_equals(ARDUINO_API_KEY, $key)) {
    log_event("WARN", "unauthorized push", ["uid" => $uid]);
    http_response_code(401);
    echo json_encode(["ok" => false, "error" => "unauthorized"], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($uid === '') {
    log_event("WARN", "uid missing");
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "uid required"], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── 2. Admin scan-session forwarding (для формы "Ler cartão") ──
$scanSessionFile = __DIR__ . '/../logs/scan_session.json';
if (file_exists($scanSessionFile)) {
    $scanData = json_decode(file_get_contents($scanSessionFile), true);
    if (
        is_array($scanData) &&
        ($scanData['state'] ?? '') === 'waiting' &&
        (time() - ($scanData['started_at'] ?? 0)) < 60
    ) {
        file_put_contents($scanSessionFile, json_encode([
            'state'      => 'ready',
            'uid'        => $uid,
            'started_at' => $scanData['started_at'],
        ]), LOCK_EX);
        log_event("INFO", "uid forwarded to admin scan session", ["uid" => $uid]);
    }
}

// ── 3. DB connect ────────────────────────────────────────
$mysqli = new mysqli("localhost", "root", "", "pap");
if ($mysqli->connect_error) {
    log_event("ERROR", "db connect failed", ["err" => $mysqli->connect_error]);
    http_response_code(500);
    echo json_encode(["ok" => false, "error" => "db connect failed"], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Сначала смотрим есть ли вообще такой UID (даже если заблокирован).
    $stmt = $mysqli->prepare("
        SELECT `Login`, `Role`, `UID`, `blocked`
        FROM `login`
        WHERE UPPER(REPLACE(`UID`, ' ', '')) = ?
        LIMIT 1
    ");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $mysqli->error);
    }
    $stmt->bind_param("s", $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    if (!$row) {
        $mysqli->close();
        log_event("WARN", "uid not found", ["uid" => $uid]);
        http_response_code(404);
        echo json_encode(["ok" => false, "error" => "uid not found", "uid" => $uid], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ── Blocked card → 403 ──────────────────────────────
    if ((int)$row['blocked'] === 1) {
        $mysqli->close();
        log_event("WARN", "blocked card", [
            "uid" => $uid, "login" => $row['Login'], "role" => $row['Role']
        ]);
        http_response_code(403);
        echo json_encode([
            "ok" => false, "error" => "blocked",
            "uid" => $uid, "login" => $row['Login'], "role" => $row['Role']
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $login = $row['Login'];
    $role  = $row['Role'];

    // ── Resolve Nome (только для Aluno/Professor) ──────
    $nome = null;
    if ($role === 'Aluno') {
        $qn = $mysqli->prepare("SELECT Nome FROM alunos WHERE login = ? LIMIT 1");
        $qn->bind_param("s", $login);
        $qn->execute();
        $rn = $qn->get_result()->fetch_assoc();
        $nome = $rn['Nome'] ?? null;
        $qn->close();
    } elseif ($role === 'Professor') {
        $qn = $mysqli->prepare("SELECT Nome FROM professores WHERE login = ? LIMIT 1");
        $qn->bind_param("s", $login);
        $qn->execute();
        $rn = $qn->get_result()->fetch_assoc();
        $nome = $rn['Nome'] ?? null;
        $qn->close();
    }

    // ── Register presença (toggle entrada/saída) ───────
    if ($nome !== null && ($role === 'Aluno' || $role === 'Professor')) {
        $today = date('Y-m-d');
        $qLast = $mysqli->prepare("
            SELECT presenca FROM presencas
            WHERE login = ? AND data = ?
            ORDER BY hora DESC
            LIMIT 1
        ");
        $qLast->bind_param("ss", $login, $today);
        $qLast->execute();
        $last = $qLast->get_result()->fetch_assoc();
        $qLast->close();

        // last=1 (entrada) → новая saída (0). Иначе entrada (1).
        $newPresenca = ($last && (int)$last['presenca'] === 1) ? 0 : 1;
        $now = date('H:i:s');

        $qIns = $mysqli->prepare("
            INSERT INTO presencas (login, nome, person_type, uid, data, hora, presenca)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $qIns->bind_param("ssssssi", $login, $nome, $role, $uid, $today, $now, $newPresenca);
        $qIns->execute();
        $qIns->close();

        $table = ($role === 'Aluno') ? 'alunos' : 'professores';
        $qUpd = $mysqli->prepare("UPDATE `$table` SET `Presença` = ? WHERE login = ?");
        $qUpd->bind_param("is", $newPresenca, $login);
        $qUpd->execute();
        $qUpd->close();

        log_event("INFO", "presenca registered", [
            "login" => $login, "role" => $role, "presenca" => $newPresenca
        ]);
    }

    // ── Kiosk auto-login: публикуем UID для polling из браузера ──
    $pendingLoginFile = __DIR__ . '/../logs/pending_login.json';
    file_put_contents($pendingLoginFile, json_encode([
        'uid'        => $uid,
        'login'      => $login,
        'role'       => $role,
        'created_at' => time(),
    ]), LOCK_EX);

    $mysqli->close();
    log_event("INFO", "uid matched", ["uid" => $uid, "login" => $login, "role" => $role]);

    echo json_encode([
        "ok"    => true,
        "uid"   => $uid,
        "login" => $login,
        "role"  => $role,
        "nome"  => $nome,
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    log_event("ERROR", "server exception", ["msg" => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(["ok" => false, "error" => "server error"], JSON_UNESCAPED_UNICODE);
    exit;
}
