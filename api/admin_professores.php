<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    $stmt = $pdo->query("
        SELECT 
            Nome AS nome,
            login,
            `Matéria ensinada` AS materia
        FROM professores
        ORDER BY Nome ASC
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
            Nome AS nome,
            login,
            turma,
            `Gabinete` AS gabinete,
            `Cargo (posição)` AS cargo,
            `Matéria ensinada` AS materia,
            Horario AS horario
        FROM professores
        WHERE login = ?
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

echo json_encode(["ok" => false]);

if ($action === 'toggle_block') {
    $login = $_POST['login'] ?? '';

    $stmt = $pdo->prepare("UPDATE professores SET blocked = IFNULL(blocked,0) ^ 1 WHERE login = ?");
    $ok = $stmt->execute([$login]);

    echo json_encode(["ok" => $ok]);
    exit;
}

if ($action === 'delete') {
    $login = $_POST['login'] ?? '';

    $stmt1 = $pdo->prepare("DELETE FROM professores WHERE login = ?");
    $stmt2 = $pdo->prepare("DELETE FROM login WHERE Login = ?");

    $ok1 = $stmt1->execute([$login]);
    $ok2 = $stmt2->execute([$login]);

    echo json_encode(["ok" => ($ok1 && $ok2)]);
    exit;
}