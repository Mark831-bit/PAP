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
CREATE TABLE IF NOT EXISTS presencas (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    login       VARCHAR(50) NOT NULL,
    nome        VARCHAR(100) NOT NULL,
    person_type ENUM('Aluno','Professor') NOT NULL,
    uid         VARCHAR(32) NOT NULL,
    data        DATE NOT NULL,
    hora        TIME NOT NULL,
    presenca    TINYINT(1) NOT NULL,
    INDEX idx_data (data),
    INDEX idx_login (login)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === true) {
    echo "OK: tabela 'presencas' criada (ou já existia).\n";
} else {
    http_response_code(500);
    echo "ERRO: " . $conn->error;
}
