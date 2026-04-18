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
    $turmaNum   = $_GET['turma_num']   ?? '';
    $turmaLetra = strtoupper($_GET['turma_letra'] ?? '');

    $sql = "
        SELECT t.id, t.titulo, t.descricao, t.data_teste,
               t.turma_num, t.turma_letra, t.professor_login,
               t.materia, t.criado_em,
               p.Nome AS professor_nome
        FROM testes t
        LEFT JOIN professores p ON p.login = t.professor_login
        WHERE 1=1
    ";
    $params = [];
    $types  = "";

    if ($turmaNum !== '') {
        $sql .= " AND t.turma_num = ?";
        $params[] = (int)$turmaNum;
        $types   .= "i";
    }
    if ($turmaLetra !== '') {
        $sql .= " AND t.turma_letra = ?";
        $params[] = $turmaLetra;
        $types   .= "s";
    }

    $sql .= " ORDER BY t.data_teste DESC, t.id DESC";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();

    $testes = [];
    while ($row = $res->fetch_assoc()) $testes[] = $row;
    $stmt->close();

    echo json_encode(['ok' => true, 'testes' => $testes]);
    exit;
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'id required']);
        exit;
    }
    $stmt = $conn->prepare("DELETE FROM testes WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    log_event("INFO", "teste deleted", ["id" => $id]);
    echo json_encode(['ok' => true, 'deleted' => $affected]);
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'invalid action']);
