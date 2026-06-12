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
    $alunos = [];
    $sql = "
        SELECT
            a.ID                 AS id,
            a.Nome               AS nome,
            a.data_nascimento    AS data_nascimento,
            CONCAT(a.turma_num, a.turma_letra) AS turma,
            a.`Presença`         AS presenca,
            a.login              AS login,
            l.UID                AS uid,
            l.blocked            AS blocked
        FROM alunos a
        LEFT JOIN login l ON l.Login = a.login
        ORDER BY a.turma_num ASC, a.turma_letra ASC, a.Nome ASC
    ";
    if ($res = $conn->query($sql)) {
        while ($row = $res->fetch_assoc()) $alunos[] = $row;
    }
    echo json_encode(['ok' => true, 'alunos' => $alunos]);
    exit;
}

if ($action === 'get') {
    $login = $_GET['login'] ?? '';
    if ($login === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'login required']);
        exit;
    }
    $stmt = $conn->prepare("
        SELECT
            a.ID                 AS id,
            a.Nome               AS nome,
            a.data_nascimento    AS data_nascimento,
            CONCAT(a.turma_num, a.turma_letra) AS turma,
            a.`Presença`         AS presenca,
            a.login              AS login,
            l.UID                AS uid,
            l.blocked            AS blocked
        FROM alunos a
        LEFT JOIN login l ON l.Login = a.login
        WHERE a.login = ?
        LIMIT 1
    ");
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $aluno = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$aluno) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'not found']);
        exit;
    }
    echo json_encode(['ok' => true, 'aluno' => $aluno]);
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
    $stmt = $conn->prepare("UPDATE login SET blocked = 1 - blocked WHERE Login = ?");
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $stmt->close();

    $q = $conn->prepare("SELECT blocked FROM login WHERE Login = ?");
    $q->bind_param("s", $login);
    $q->execute();
    $r = $q->get_result()->fetch_assoc();
    $q->close();

    log_event("INFO", "card block toggled", ["login" => $login, "blocked" => (int)$r['blocked']]);
    echo json_encode(['ok' => true, 'blocked' => (int)$r['blocked']]);
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

    $conn->begin_transaction();
    try {
        $s1 = $conn->prepare("DELETE FROM presencas WHERE login = ?");
        $s1->bind_param("s", $login);
        $s1->execute();
        $s1->close();

        $s2 = $conn->prepare("DELETE FROM alunos WHERE login = ?");
        $s2->bind_param("s", $login);
        $s2->execute();
        $s2->close();

        $s3 = $conn->prepare("DELETE FROM login WHERE Login = ?");
        $s3->bind_param("s", $login);
        $s3->execute();
        $s3->close();

        $conn->commit();
        log_event("INFO", "aluno deleted", ["login" => $login]);
        echo json_encode(['ok' => true]);
    } catch (Throwable $e) {
        $conn->rollback();
        log_event("ERROR", "aluno delete failed", ["login" => $login, "err" => $e->getMessage()]);
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'db error']);
    }
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'invalid action']);
