<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: text/plain; charset=utf-8');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo "Apenas admin pode executar a migração.";
    exit;
}

$sql = "
CREATE TABLE IF NOT EXISTS agenda (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    login       VARCHAR(50) NOT NULL,
    titulo      VARCHAR(200) NOT NULL,
    data        DATE NULL,
    concluido   TINYINT(1) NOT NULL DEFAULT 0,
    criado_em   DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_login (login),
    INDEX idx_data  (data)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === true) {
    echo "OK: tabela 'agenda' criada (ou já existia).\n";
} else {
    http_response_code(500);
    echo "ERRO: " . $conn->error;
}
