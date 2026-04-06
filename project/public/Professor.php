<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Professor') {
    header("Location: /PAP/project/public/index.php");
    exit;
}

if (!isset($_SESSION['login'])) {
    die("Login do professor não encontrado na sessão.");
}

$login = $_SESSION['login'];

/* 1. Ищем учителя по login */
$stmtProf = $conn->prepare("
    SELECT ID, Nome, turma, login
    FROM professores
    WHERE login = ?
    LIMIT 1
");

if (!$stmtProf) {
    die("Erro na query do professor: " . $conn->error);
}

$stmtProf->bind_param("s", $login);
$stmtProf->execute();
$resultProf = $stmtProf->get_result();
$professor = $resultProf->fetch_assoc();

if (!$professor) {
    die("Professor não encontrado.");
}

if (empty($professor['turma'])) {
    die("A turma do professor ainda não está definida.");
}

$turma = $professor['turma'];
$professorNome = $professor['Nome'];

/* 2. Получаем учеников этой turma */
$stmtAlunos = $conn->prepare("
    SELECT 
        ID,
        Nome AS nome,
        Idade AS idade,
        Turma AS turma,
        `Número em turma` AS numero_turma,
        `Presença` AS presenca
    FROM alunos
    WHERE Turma = ?
    ORDER BY `Número em turma` ASC, Nome ASC
");

if (!$stmtAlunos) {
    die("Erro na query dos alunos: " . $conn->error);
}

$stmtAlunos->bind_param("s", $turma);
$stmtAlunos->execute();
$resultAlunos = $stmtAlunos->get_result();

$alunos = [];
while ($row = $resultAlunos->fetch_assoc()) {
    $alunos[] = $row;
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professor - Os meus alunos</title>
    <link rel="stylesheet" href="/PAP/project/assets/style.css?v=7">
</head>
<body class="page-professor">

<header class="topbar">
    <div class="topbar-inner">
        <div class="topbar-left">
            <a href="/PAP/project/public/index.php">
                <img class="logo" src="../assets/aemtg.jpg" alt="Logo">
            </a>
        </div>

        <div class="topbar-center">
            <a href="/PAP/project/public/index.php">Principal</a>
            <a href="/PAP/api/profile.php">Página pessoal</a>
            <a href="/PAP/project/public/dashboard">Horário</a>
        </div>

        <div class="topbar-right">
            <h1>Professor</h1>
        </div>
    </div>
</header>

<main class="page-content">
    <section class="students-panel">
        <div class="students-panel-header">
            <div>
                <h2>Os meus alunos</h2>
                <p>
                    Professor: <?= htmlspecialchars($professorNome) ?> |
                    Turma: <?= htmlspecialchars($turma) ?>
                </p>
            </div>
        </div>

        <div class="students-list">
            <?php if (count($alunos) > 0): ?>
                <?php foreach ($alunos as $aluno): ?>
                    <div class="student-card">
                        <div class="student-left">
                            <span class="status-dot <?= ((int)$aluno['presenca'] === 1) ? 'present' : 'absent' ?>"></span>

                            <div class="student-info">
                                <div class="student-name">
                                    <?= htmlspecialchars($aluno['nome']) ?>
                                </div>

                                <div class="student-meta">
                                    Nº <?= htmlspecialchars($aluno['numero_turma']) ?> •
                                    Idade <?= htmlspecialchars($aluno['idade']) ?> •
                                    Turma <?= htmlspecialchars($aluno['turma']) ?>
                                </div>
                            </div>
                        </div>

                        <div class="student-right <?= ((int)$aluno['presenca'] === 1) ? 'present-text' : 'absent-text' ?>">
                          <?= ((int)$aluno['presenca'] === 1) ? 'Presente' : 'Ausente' ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    Não há alunos associados a esta turma.
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

</body>
</html>