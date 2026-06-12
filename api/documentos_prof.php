<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/lib/logger.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['login']) || ($_SESSION['role'] ?? '') !== 'Professor') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Acesso negado.']);
    exit;
}

$login  = $_SESSION['login'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

$uploadDir = __DIR__ . '/../uploads/docs_prof/' . preg_replace('/[^a-zA-Z0-9@._-]/', '_', $login) . '/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// ── LIST ──────────────────────────────────────────────────────────────
if ($action === 'list') {
    $files = [];
    foreach (glob($uploadDir . '*') as $path) {
        if (!is_file($path)) continue;
        $name = basename($path);
        $files[] = [
            'name'     => $name,
            'size'     => filesize($path),
            'modified' => filemtime($path),
            'url'      => '/PAP/uploads/docs_prof/' . rawurlencode(preg_replace('/[^a-zA-Z0-9@._-]/', '_', $login)) . '/' . rawurlencode($name),
        ];
    }
    usort($files, fn($a, $b) => $b['modified'] - $a['modified']);
    echo json_encode(['ok' => true, 'files' => $files]);
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
    $maxSize = 15 * 1024 * 1024;

    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['ok' => false, 'error' => 'Erro no upload.']);
        exit;
    }

    if ($file['size'] > $maxSize) {
        echo json_encode(['ok' => false, 'error' => 'Ficheiro demasiado grande (máx 15 MB).']);
        exit;
    }

    $allowedMime = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    $allowedExt = ['pdf','doc','docx','xls','xlsx','ppt','pptx','txt','jpg','jpeg','png','gif','webp'];

    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    $origName = basename($file['name']);
    $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

    if (!in_array($mimeType, $allowedMime, true) || !in_array($ext, $allowedExt, true)) {
        echo json_encode(['ok' => false, 'error' => 'Tipo de ficheiro não permitido.']);
        exit;
    }

    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($origName, PATHINFO_FILENAME));
    $safeName = substr($safeName, 0, 80);
    $filename = date('Ymd_His') . '_' . $safeName . '.' . $ext;
    $destPath = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        echo json_encode(['ok' => false, 'error' => 'Erro ao guardar ficheiro.']);
        exit;
    }

    log_event('INFO', 'documento_prof uploaded', ['login' => $login, 'file' => $filename]);
    echo json_encode(['ok' => true, 'filename' => $filename]);
    exit;
}

// ── DELETE ────────────────────────────────────────────────────────────
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $filename = basename($_POST['filename'] ?? '');
    if ($filename === '') {
        echo json_encode(['ok' => false, 'error' => 'Nome inválido.']);
        exit;
    }

    $path = $uploadDir . $filename;

    if (!file_exists($path) || !is_file($path)) {
        echo json_encode(['ok' => false, 'error' => 'Ficheiro não encontrado.']);
        exit;
    }

    unlink($path);
    log_event('INFO', 'documento_prof deleted', ['login' => $login, 'file' => $filename]);
    echo json_encode(['ok' => true]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Ação desconhecida.']);
