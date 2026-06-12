<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/lib/logger.php';

header('Content-Type: application/json; charset=utf-8');

csrf_check();

try {
    $email    = trim($_POST['email'] ?? '');
    $mensagem = trim($_POST['mensagem'] ?? '');

    if ($mensagem === '') {
        echo json_encode(['ok' => false, 'error' => 'Descreva o problema.']);
        exit;
    }

    if (strlen($mensagem) > 2000) {
        echo json_encode(['ok' => false, 'error' => 'Mensagem demasiado longa (máx 2000).']);
        exit;
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['ok' => false, 'error' => 'Email inválido.']);
        exit;
    }

    $emailForDb = $email !== '' ? $email : null;

    $stmt = $pdo->prepare("INSERT INTO access_problems (email, mensagem) VALUES (?, ?)");
    $stmt->execute([$emailForDb, $mensagem]);

    log_event("INFO", "access problem submitted", [
        "email" => $emailForDb,
        "len"   => strlen($mensagem)
    ]);

    echo json_encode(['ok' => true, 'message' => 'Mensagem enviada.']);
    exit;

} catch (Throwable $e) {
    log_event("ERROR", "access_problem exception", ["msg" => $e->getMessage()]);
    echo json_encode(['ok' => false, 'error' => 'Erro interno do servidor.']);
    exit;
}
