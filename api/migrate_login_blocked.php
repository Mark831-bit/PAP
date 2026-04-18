<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: text/plain; charset=utf-8');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo "Apenas admin.";
    exit;
}

$check = $conn->query("SHOW COLUMNS FROM login LIKE 'blocked'");
if ($check && $check->num_rows > 0) {
    echo "OK: coluna 'blocked' já existe.\n";
    exit;
}

$sql = "ALTER TABLE login ADD COLUMN blocked TINYINT(1) NOT NULL DEFAULT 0";

if ($conn->query($sql) === true) {
    echo "OK: coluna 'blocked' adicionada.\n";
} else {
    http_response_code(500);
    echo "ERRO: " . $conn->error;
}
