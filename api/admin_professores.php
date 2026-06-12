<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/lib/logger.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'forbidden']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'list') {
    $stmt = $pdo->query("
        SELECT
            p.Nome AS nome,
            p.login,
            p.`Matéria ensinada` AS materia,
            l.blocked AS blocked
        FROM professores p
        LEFT JOIN login l ON l.Login = p.login
        ORDER BY p.Nome ASC
    ");

    echo json_encode([
        "ok" => true,
        "professores" => $stmt->fetchAll()
    ]);
    exit;
}

if ($action === 'get') {
    $login = $_GET['login'] ?? '';

    $stmt = $pdo->prepare("
        SELECT
            p.Nome AS nome,
            p.login,
            p.turma,
            p.`Gabinete` AS gabinete,
            p.`Cargo (posição)` AS cargo,
            p.`Matéria ensinada` AS materia,
            p.Horario AS horario,
            l.blocked AS blocked
        FROM professores p
        LEFT JOIN login l ON l.Login = p.login
        WHERE p.login = ?
        LIMIT 1
    ");
    $stmt->execute([$login]);
    $professor = $stmt->fetch();

    echo json_encode([
        "ok" => !!$professor,
        "professor" => $professor
    ]);
    exit;
}

if ($action === 'toggle_block') {
    csrf_check();
    $login = $_POST['login'] ?? '';
    if ($login === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'login required']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE login SET blocked = 1 - blocked WHERE Login = ?");
    $stmt->execute([$login]);

    $q = $pdo->prepare("SELECT blocked FROM login WHERE Login = ?");
    $q->execute([$login]);
    $r = $q->fetch();

    log_event("INFO", "professor block toggled", ["login" => $login, "blocked" => (int)($r['blocked'] ?? 0)]);
    echo json_encode(['ok' => true, 'blocked' => (int)($r['blocked'] ?? 0)]);
    exit;
}

if ($action === 'delete') {
    csrf_check();
    $login = $_POST['login'] ?? '';
    if ($login === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'login required']);
        exit;
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare("DELETE FROM professores WHERE login = ?")->execute([$login]);
        $pdo->prepare("DELETE FROM login WHERE Login = ?")->execute([$login]);
        $pdo->commit();
        log_event("INFO", "professor deleted", ["login" => $login]);
        echo json_encode(['ok' => true]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        log_event("ERROR", "professor delete failed", ["login" => $login, "err" => $e->getMessage()]);
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'db error']);
    }
    exit;
}

echo json_encode(["ok" => false, "error" => "unknown action"]);
