<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/lib/logger.php';

header('Content-Type: application/json; charset=utf-8');

$role  = $_SESSION['role']  ?? '';
$login = $_SESSION['login'] ?? '';

if (!$login || !in_array($role, ['Aluno', 'Professor'], true)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Acesso negado.']);
    exit;
}

$table     = $role === 'Aluno' ? 'alunos' : 'professores';
$loginSafe = preg_replace('/[^a-zA-Z0-9@._-]/', '_', $login);
$uploadDir = __DIR__ . '/../uploads/fotos/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ── GET ───────────────────────────────────────────────────────────────
if ($action === 'get') {
    $stmt = $conn->prepare("SELECT foto FROM $table WHERE login = ? LIMIT 1");
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row && $row['foto'] && file_exists($uploadDir . $row['foto'])) {
        $url = '/PAP/uploads/fotos/' . rawurlencode($row['foto']);
        echo json_encode(['ok' => true, 'url' => $url]);
    } else {
        echo json_encode(['ok' => true, 'url' => null]);
    }
    exit;
}

// ── UPLOAD ────────────────────────────────────────────────────────────
if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if (empty($_FILES['file'])) {
        echo json_encode(['ok' => false, 'error' => 'Nenhum ficheiro recebido.']);
        exit;
    }

    $file    = $_FILES['file'];
    $maxSize = 5 * 1024 * 1024; // 5 MB

    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['ok' => false, 'error' => 'Erro no upload.']);
        exit;
    }

    if ($file['size'] > $maxSize) {
        echo json_encode(['ok' => false, 'error' => 'Ficheiro demasiado grande (máx 5 MB).']);
        exit;
    }

    $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $allowedExt  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    $ext      = strtolower(pathinfo(basename($file['name']), PATHINFO_EXTENSION));

    if (!in_array($mimeType, $allowedMime, true) || !in_array($ext, $allowedExt, true)) {
        echo json_encode(['ok' => false, 'error' => 'Apenas imagens são permitidas (JPG, PNG, GIF, WEBP).']);
        exit;
    }

    // Remove old photo if exists
    $stmtOld = $conn->prepare("SELECT foto FROM $table WHERE login = ? LIMIT 1");
    $stmtOld->bind_param("s", $login);
    $stmtOld->execute();
    $oldRow = $stmtOld->get_result()->fetch_assoc();
    $stmtOld->close();
    if ($oldRow && $oldRow['foto']) {
        $oldPath = $uploadDir . $oldRow['foto'];
        if (file_exists($oldPath)) {
            unlink($oldPath);
        }
    }

    $filename = $loginSafe . '.' . $ext;
    $destPath = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        echo json_encode(['ok' => false, 'error' => 'Erro ao guardar ficheiro.']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE $table SET foto = ? WHERE login = ?");
    $stmt->bind_param("ss", $filename, $login);
    $stmt->execute();
    $stmt->close();

    log_event('INFO', 'foto uploaded', ['login' => $login, 'file' => $filename]);

    $url = '/PAP/uploads/fotos/' . rawurlencode($filename);
    echo json_encode(['ok' => true, 'url' => $url]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Ação desconhecida.']);
