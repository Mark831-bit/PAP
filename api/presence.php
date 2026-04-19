<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Professor') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'forbidden']);
    exit;
}

$turma = $_GET['turma'] ?? '';
if ($turma === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'turma required']);
    exit;
}

$stmt = $conn->prepare("
    SELECT ID AS id, `Presença` AS presenca
    FROM alunos
    WHERE Turma = ?
");
$stmt->bind_param("s", $turma);
$stmt->execute();
$res = $stmt->get_result();

$alunos = [];
while ($row = $res->fetch_assoc()) {
    $alunos[] = [
        'id'       => (int)$row['id'],
        'presenca' => (int)$row['presenca'],
    ];
}
$stmt->close();

echo json_encode(['ok' => true, 'alunos' => $alunos]);
