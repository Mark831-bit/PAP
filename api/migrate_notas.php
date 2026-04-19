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
CREATE TABLE IF NOT EXISTS notas (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    login_aluno      VARCHAR(50) NOT NULL,
    materia          VARCHAR(100) NOT NULL,
    tipo             VARCHAR(50) NOT NULL,
    valor            DECIMAL(4,1) NOT NULL,
    data             DATE NOT NULL,
    professor_login  VARCHAR(50) NOT NULL,
    observacao       TEXT,
    criado_em        DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_aluno   (login_aluno),
    INDEX idx_materia (materia),
    INDEX idx_data    (data),
    INDEX idx_prof    (professor_login)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === true) {
    echo "OK: tabela 'notas' criada (ou já existia).\n";
} else {
    http_response_code(500);
    echo "ERRO: " . $conn->error;
}
