<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/lib/logger.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Professor' || empty($_SESSION['login'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'forbidden']);
    exit;
}

csrf_check();

$login      = $_SESSION['login'];
$titulo     = trim($_POST['titulo'] ?? '');
$descricao  = trim($_POST['descricao'] ?? '');
$dataTeste  = trim($_POST['data_teste'] ?? '');
$turmaNum   = trim($_POST['turma_num'] ?? '');
$turmaLetra = strtoupper(trim($_POST['turma_letra'] ?? ''));

if ($titulo === '' || $dataTeste === '' || $turmaNum === '' || $turmaLetra === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'missing fields']);
    exit;
}

if (!in_array($turmaNum, ['10', '11', '12'], true) || !in_array($turmaLetra, ['A', 'B', 'C'], true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid turma']);
    exit;
}

/* Автоподстановка matéria из professores */
$materia = null;
$stmt = $conn->prepare("SELECT `Matéria ensinada` AS m FROM professores WHERE login = ? LIMIT 1");
$stmt->bind_param("s", $login);
$stmt->execute();
$r = $stmt->get_result()->fetch_assoc();
$materia = $r['m'] ?? null;
$stmt->close();

$ins = $conn->prepare("
    INSERT INTO testes (titulo, descricao, data_teste, turma_num, turma_letra, professor_login, materia)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
$turmaNumInt = (int)$turmaNum;
$ins->bind_param("sssisss", $titulo, $descricao, $dataTeste, $turmaNumInt, $turmaLetra, $login, $materia);

if ($ins->execute()) {
    log_event("INFO", "teste created", [
        "professor" => $login,
        "turma" => $turmaNum . $turmaLetra,
        "titulo" => $titulo
    ]);
    echo json_encode(['ok' => true, 'id' => $ins->insert_id]);
} else {
    log_event("ERROR", "teste insert failed", ["err" => $ins->error]);
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'db error']);
}

$ins->close();
