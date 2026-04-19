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
CREATE TABLE IF NOT EXISTS noticias (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    titulo     VARCHAR(200) NOT NULL,
    corpo      TEXT NOT NULL,
    imagem     VARCHAR(300),
    ativo      TINYINT(1) NOT NULL DEFAULT 1,
    criado_em  DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ativo (ativo),
    INDEX idx_criado (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql) === true) {
    echo "OK: tabela 'noticias' criada (ou já existia).\n";

    $count = $conn->query("SELECT COUNT(*) AS c FROM noticias")->fetch_assoc()['c'];
    if ((int)$count === 0) {
        $seed = "
        INSERT INTO noticias (titulo, corpo, imagem, ativo) VALUES
        ('Gestão escolar simplificada',
         'Uma plataforma moderna que facilita o controlo de presenças e o acesso à informação escolar em tempo real.',
         '../assets/1_1.jpg', 1),
        ('Para alunos',
         'Consulta os teus dados, acompanha a tua presença e acede rapidamente às ferramentas escolares essenciais.',
         '../assets/2_2.jpg', 1),
        ('Para professores',
         'Acompanha os alunos, gere presenças e mantém toda a informação organizada num só lugar.',
         '../assets/3_3.jpg', 1),
        ('Controlo total para administradores',
         'Gere utilizadores, cartões RFID e dados do sistema de forma eficiente e segura.',
         '../assets/4_4.jpg', 1);
        ";
        $conn->query($seed);
        echo "Seed: 4 notícias iniciais inseridas.\n";
    } else {
        echo "Seed ignorado (já existem {$count} linhas).\n";
    }
} else {
    http_response_code(500);
    echo "ERRO: " . $conn->error;
}
