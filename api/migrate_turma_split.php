<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: text/plain; charset=utf-8');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo "Apenas admin.";
    exit;
}

$steps = [];

/* ── alunos ──────────────────────────────── */
$check = $conn->query("SHOW COLUMNS FROM alunos LIKE 'turma_num'");
if ($check && $check->num_rows === 0) {
    if ($conn->query("ALTER TABLE alunos ADD COLUMN turma_num TINYINT NULL AFTER Turma") === true) {
        $steps[] = "alunos: coluna turma_num adicionada";
    } else {
        $steps[] = "ERRO alunos.turma_num: " . $conn->error;
    }
} else {
    $steps[] = "alunos.turma_num já existia";
}

$check = $conn->query("SHOW COLUMNS FROM alunos LIKE 'turma_letra'");
if ($check && $check->num_rows === 0) {
    if ($conn->query("ALTER TABLE alunos ADD COLUMN turma_letra CHAR(1) NULL AFTER turma_num") === true) {
        $steps[] = "alunos: coluna turma_letra adicionada";
    } else {
        $steps[] = "ERRO alunos.turma_letra: " . $conn->error;
    }
} else {
    $steps[] = "alunos.turma_letra já existia";
}

/* Заполняем из Turma */
$updA = $conn->query("
    UPDATE alunos
    SET turma_num   = CAST(SUBSTRING(Turma, 1, LENGTH(Turma) - 1) AS UNSIGNED),
        turma_letra = UPPER(RIGHT(Turma, 1))
    WHERE Turma IS NOT NULL AND Turma <> ''
      AND (turma_num IS NULL OR turma_letra IS NULL)
");
if ($updA) {
    $steps[] = "alunos: dados preenchidos (linhas afetadas: " . $conn->affected_rows . ")";
} else {
    $steps[] = "ERRO preenchendo alunos: " . $conn->error;
}

/* ── professores ─────────────────────────── */
$check = $conn->query("SHOW COLUMNS FROM professores LIKE 'turma_num'");
if ($check && $check->num_rows === 0) {
    if ($conn->query("ALTER TABLE professores ADD COLUMN turma_num TINYINT NULL AFTER turma") === true) {
        $steps[] = "professores: coluna turma_num adicionada";
    } else {
        $steps[] = "ERRO professores.turma_num: " . $conn->error;
    }
} else {
    $steps[] = "professores.turma_num já existia";
}

$check = $conn->query("SHOW COLUMNS FROM professores LIKE 'turma_letra'");
if ($check && $check->num_rows === 0) {
    if ($conn->query("ALTER TABLE professores ADD COLUMN turma_letra CHAR(1) NULL AFTER turma_num") === true) {
        $steps[] = "professores: coluna turma_letra adicionada";
    } else {
        $steps[] = "ERRO professores.turma_letra: " . $conn->error;
    }
} else {
    $steps[] = "professores.turma_letra já existia";
}

$updP = $conn->query("
    UPDATE professores
    SET turma_num   = CAST(SUBSTRING(turma, 1, LENGTH(turma) - 1) AS UNSIGNED),
        turma_letra = UPPER(RIGHT(turma, 1))
    WHERE turma IS NOT NULL AND turma <> ''
      AND (turma_num IS NULL OR turma_letra IS NULL)
");
if ($updP) {
    $steps[] = "professores: dados preenchidos (linhas afetadas: " . $conn->affected_rows . ")";
} else {
    $steps[] = "ERRO preenchendo professores: " . $conn->error;
}

echo "OK:\n" . implode("\n", $steps) . "\n";
