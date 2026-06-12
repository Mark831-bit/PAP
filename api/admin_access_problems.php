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

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    if ($action === 'list') {
        $stmt = $pdo->query("
            SELECT id, email, mensagem, criado_em, lido
            FROM access_problems
            ORDER BY criado_em DESC, id DESC
            LIMIT 200
        ");
        $rows = $stmt->fetchAll();
        echo json_encode(['ok' => true, 'items' => $rows]);
        exit;
    }

    csrf_check();

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['ok' => false, 'error' => 'invalid id']);
        exit;
    }

    if ($action === 'mark_read') {
        $stmt = $pdo->prepare("UPDATE access_problems SET lido = 1 WHERE id = ?");
        $stmt->execute([$id]);
        log_event("ADMIN_ACTION", "access problem marked read", [
            "admin" => $_SESSION['login'] ?? null, "id" => $id
        ]);
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'mark_unread') {
        $stmt = $pdo->prepare("UPDATE access_problems SET lido = 0 WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM access_problems WHERE id = ?");
        $stmt->execute([$id]);
        log_event("ADMIN_ACTION", "access problem deleted", [
            "admin" => $_SESSION['login'] ?? null, "id" => $id
        ]);
        echo json_encode(['ok' => true]);
        exit;
    }

    echo json_encode(['ok' => false, 'error' => 'unknown action']);
    exit;

} catch (Throwable $e) {
    log_event("ERROR", "admin_access_problems exception", ["msg" => $e->getMessage()]);
    echo json_encode(['ok' => false, 'error' => 'Erro interno do servidor.']);
    exit;
}
