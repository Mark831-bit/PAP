<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/lib/logger.php';

header('Content-Type: application/json; charset=utf-8');

$action  = $_GET['action'] ?? $_POST['action'] ?? '';
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

/* Public: list active — usado pelo carousel do index */
if ($action === 'public_list') {
    $res = $conn->query("
        SELECT id, titulo, corpo, imagem
        FROM noticias
        WHERE ativo = 1
        ORDER BY criado_em DESC, id DESC
    ");
    $out = [];
    while ($row = $res->fetch_assoc()) {
        $out[] = [
            'id'     => (int)$row['id'],
            'titulo' => $row['titulo'],
            'corpo'  => $row['corpo'],
            'imagem' => $row['imagem'],
        ];
    }
    echo json_encode(['ok' => true, 'noticias' => $out]);
    exit;
}

/* Admin-only a partir daqui */
if (!$isAdmin) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'forbidden']);
    exit;
}

if ($action === 'list') {
    $res = $conn->query("
        SELECT id, titulo, corpo, imagem, ativo, criado_em
        FROM noticias
        ORDER BY criado_em DESC, id DESC
    ");
    $out = [];
    while ($row = $res->fetch_assoc()) {
        $out[] = [
            'id'        => (int)$row['id'],
            'titulo'    => $row['titulo'],
            'corpo'     => $row['corpo'],
            'imagem'    => $row['imagem'],
            'ativo'     => (int)$row['ativo'],
            'criado_em' => $row['criado_em'],
        ];
    }
    echo json_encode(['ok' => true, 'noticias' => $out]);
    exit;
}

if ($action === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'id required']);
        exit;
    }
    $stmt = $conn->prepare("SELECT id, titulo, corpo, imagem, ativo FROM noticias WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'not found']);
        exit;
    }
    echo json_encode(['ok' => true, 'noticia' => [
        'id'     => (int)$row['id'],
        'titulo' => $row['titulo'],
        'corpo'  => $row['corpo'],
        'imagem' => $row['imagem'],
        'ativo'  => (int)$row['ativo'],
    ]]);
    exit;
}

if ($action === 'create') {
    csrf_check();

    $titulo = trim($_POST['titulo'] ?? '');
    $corpo  = trim($_POST['corpo']  ?? '');
    $imagem = trim($_POST['imagem'] ?? '');
    $ativo  = isset($_POST['ativo']) ? (int)$_POST['ativo'] : 1;

    if ($titulo === '' || $corpo === '' || mb_strlen($titulo) > 200) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'campos obrigatórios']);
        exit;
    }
    if ($imagem !== '' && mb_strlen($imagem) > 300) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'imagem demasiado longa']);
        exit;
    }
    $imagemSql = $imagem !== '' ? $imagem : null;
    $ativo     = $ativo ? 1 : 0;

    $stmt = $conn->prepare("INSERT INTO noticias (titulo, corpo, imagem, ativo) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $titulo, $corpo, $imagemSql, $ativo);

    if (!$stmt->execute()) {
        log_event("ERROR", "noticia insert failed", ["err" => $stmt->error]);
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'db error']);
        exit;
    }
    $id = $stmt->insert_id;
    $stmt->close();
    log_event("INFO", "noticia created", ["id" => $id, "titulo" => $titulo]);
    echo json_encode(['ok' => true, 'id' => $id]);
    exit;
}

if ($action === 'update') {
    csrf_check();

    $id     = (int)($_POST['id'] ?? 0);
    $titulo = trim($_POST['titulo'] ?? '');
    $corpo  = trim($_POST['corpo']  ?? '');
    $imagem = trim($_POST['imagem'] ?? '');
    $ativo  = isset($_POST['ativo']) ? (int)$_POST['ativo'] : 1;

    if ($id <= 0 || $titulo === '' || $corpo === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'campos obrigatórios']);
        exit;
    }
    $imagemSql = $imagem !== '' ? $imagem : null;
    $ativo     = $ativo ? 1 : 0;

    $stmt = $conn->prepare("UPDATE noticias SET titulo = ?, corpo = ?, imagem = ?, ativo = ? WHERE id = ?");
    $stmt->bind_param("sssii", $titulo, $corpo, $imagemSql, $ativo, $id);

    if (!$stmt->execute()) {
        log_event("ERROR", "noticia update failed", ["err" => $stmt->error]);
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'db error']);
        exit;
    }
    $stmt->close();
    log_event("INFO", "noticia updated", ["id" => $id]);
    echo json_encode(['ok' => true]);
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

    $stmt = $conn->prepare("UPDATE noticias SET ativo = 1 - ativo WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    $q = $conn->prepare("SELECT ativo FROM noticias WHERE id = ?");
    $q->bind_param("i", $id);
    $q->execute();
    $r = $q->get_result()->fetch_assoc();
    $q->close();

    if (!$r) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'not found']);
        exit;
    }
    log_event("INFO", "noticia toggled", ["id" => $id, "ativo" => (int)$r['ativo']]);
    echo json_encode(['ok' => true, 'ativo' => (int)$r['ativo']]);
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
    $stmt = $conn->prepare("DELETE FROM noticias WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    if ($affected === 0) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'not found']);
        exit;
    }
    log_event("INFO", "noticia deleted", ["id" => $id]);
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'invalid action']);
