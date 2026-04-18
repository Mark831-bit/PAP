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
CREATE TABLE IF NOT EXISTS testes (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    titulo          VARCHAR(200) NOT NULL,
    descricao       TEXT,
    data_teste      DATE NOT NULL,
    turma_num       TINYINT NOT NULL,
    turma_letra     CHAR(1) NOT NULL,
    professor_login VARCHAR(50) NOT NULL,
    materia         VARCHAR(100),
    criado_em       DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_turma (turma_num, turma_letra),
    INDEX idx_data  (data_teste)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === true) {
    echo "OK: tabela 'testes' criada (ou já existia).\n";
} else {
    http_response_code(500);
    echo "ERRO: " . $conn->error;
}
