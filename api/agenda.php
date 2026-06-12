<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/lib/logger.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['login']) || !in_array($_SESSION['role'] ?? '', ['Aluno', 'Professor'], true)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'forbidden']);
    exit;
}

$login  = $_SESSION['login'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'list') {
    $stmt = $conn->prepare("
        SELECT id, titulo, data, concluido, criado_em
        FROM agenda
        WHERE login = ?
        ORDER BY concluido ASC,
                 (data IS NULL) ASC,
                 data ASC,
                 id DESC
    ");
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $res = $stmt->get_result();

    $tarefas = [];
    while ($row = $res->fetch_assoc()) {
        $tarefas[] = [
            'id'         => (int)$row['id'],
            'titulo'     => $row['titulo'],
            'data'       => $row['data'],
            'concluido'  => (int)$row['concluido'],
            'criado_em'  => $row['criado_em'],
        ];
    }
    $stmt->close();

    echo json_encode(['ok' => true, 'tarefas' => $tarefas]);
    exit;
}

if ($action === 'create') {
    csrf_check();

    $titulo = trim($_POST['titulo'] ?? '');
    $data   = trim($_POST['data'] ?? '');

    if ($titulo === '' || mb_strlen($titulo) > 200) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'titulo inválido']);
        exit;
    }

    $dataSql = null;
    if ($data !== '') {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'data inválida']);
            exit;
        }
        $dataSql = $data;
    }

    $stmt = $conn->prepare("INSERT INTO agenda (login, titulo, data) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $login, $titulo, $dataSql);

    if (!$stmt->execute()) {
        log_event("ERROR", "agenda insert failed", ["err" => $stmt->error]);
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'db error']);
        exit;
    }

    $id = $stmt->insert_id;
    $stmt->close();
    echo json_encode(['ok' => true, 'id' => $id]);
    exit;
}

if ($action === 'toggle') {
    csrf_check();

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'id required']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE agenda SET concluido = 1 - concluido WHERE id = ? AND login = ?");
    $stmt->bind_param("is", $id, $login);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    if ($affected === 0) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'not found']);
        exit;
    }

    $q = $conn->prepare("SELECT concluido FROM agenda WHERE id = ?");
    $q->bind_param("i", $id);
    $q->execute();
    $r = $q->get_result()->fetch_assoc();
    $q->close();

    echo json_encode(['ok' => true, 'concluido' => (int)$r['concluido']]);
    exit;
}

if ($action === 'delete') {
    csrf_check();

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'id required']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM agenda WHERE id = ? AND login = ?");
    $stmt->bind_param("is", $id, $login);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    if ($affected === 0) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'not found']);
        exit;
    }

    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'invalid action']);
