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

/* 2. Получаем все уникальные turma из alunos */
$turmas = [];

$stmtTurmas = $conn->prepare("
    SELECT DISTINCT Turma
    FROM alunos
    WHERE Turma IS NOT NULL AND Turma <> ''
    ORDER BY Turma ASC
");

if ($stmtTurmas) {
    $stmtTurmas->execute();
    $resultTurmas = $stmtTurmas->get_result();

    while ($row = $resultTurmas->fetch_assoc()) {
        $turmas[] = $row['Turma'];
    }
}

/* 3. Фильтры */
$filtroTurma = $_GET['turma'] ?? $turma;
$filtroPresenca = $_GET['presenca'] ?? '';

/* 4. Получаем учеников по выбранной turma + presença */
$sqlAlunos = "
    SELECT 
        ID,
        Nome AS nome,
        Idade AS idade,
        Turma AS turma,
        `Número em turma` AS numero_turma,
        `Presença` AS presenca,
        login
    FROM alunos
    WHERE Turma = ?
";

$params = [$filtroTurma];
$types = "s";

if ($filtroPresenca !== '' && ($filtroPresenca === '0' || $filtroPresenca === '1')) {
    $sqlAlunos .= " AND `Presença` = ?";
    $params[] = (int)$filtroPresenca;
    $types .= "i";
}

$sqlAlunos .= " ORDER BY `Número em turma` ASC, Nome ASC";

$stmtAlunos = $conn->prepare($sqlAlunos);

if (!$stmtAlunos) {
    die("Erro na query dos alunos: " . $conn->error);
}

$stmtAlunos->bind_param($types, ...$params);
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
    <link rel="stylesheet" href="/PAP/project/assets/style.css?v=308">
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
                    Turma: <?= htmlspecialchars($filtroTurma) ?>
                </p>
            </div>
        </div>

         <div class="test-box">
            <h2>Назначить тест</h2>

            <form id="testeForm">
                <div class="form-row turma-row">
                    <div>
                        <label for="testeTurmaNum">Turma</label>
                        <select id="testeTurmaNum" name="turma_num">
                            <option value="">Ano</option>
                            <option value="10">10</option>
                            <option value="11">11</option>
                            <option value="12">12</option>
                        </select>
                    </div>

                    <div>
                        <label for="testeTurmaLetra">&nbsp;</label>
                        <select id="testeTurmaLetra" name="turma_letra">
                            <option value="">Letra</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <label for="testTitle">Название</label>
                    <input
                        type="text"
                        id="testTitle"
                        name="titulo"
                        placeholder="Тест / контрольная работа"
                    >
                </div>

                <div class="form-row">
                    <label for="testDate">Дата</label>
                    <input
                        type="date"
                        id="testDate"
                        name="data_teste"
                    >
                </div>

                <div class="form-row">
                    <label for="testDescription">Описание</label>
                    <textarea
                        id="testDescription"
                        name="descricao"
                        rows="4"
                        placeholder="Опиши тему, материалы, инструкции..."
                    ></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit">Назначить</button>
                    <div id="testeStatus"></div>
                </div>
            </form>
        </div>

        <form class="students-filters" method="GET">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="turmaSelect">Turma</label>
                    <select id="turmaSelect" name="turma" onchange="this.form.submit()">
                        <?php foreach ($turmas as $turmaItem): ?>
                            <option value="<?= htmlspecialchars($turmaItem) ?>"
                                <?= ($turmaItem === $filtroTurma) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($turmaItem) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Presença</label>
                    <div class="radio-group">
                        <label class="radio-option">
                            <input type="radio" name="presenca" value="" <?= ($filtroPresenca === '') ? 'checked' : '' ?> onchange="this.form.submit()">
                            <span>Todos</span>
                        </label>

                        <label class="radio-option">
                            <input type="radio" name="presenca" value="1" <?= ($filtroPresenca === '1') ? 'checked' : '' ?> onchange="this.form.submit()">
                            <span>Presente</span>
                        </label>

                        <label class="radio-option">
                            <input type="radio" name="presenca" value="0" <?= ($filtroPresenca === '0') ? 'checked' : '' ?> onchange="this.form.submit()">
                            <span>Falta</span>
                        </label>
                    </div>
                </div>
            </div>
        </form>

       

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
                            <?= ((int)$aluno['presenca'] === 1) ? 'Presente' : 'Falta' ?>
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

<script>
document.getElementById("testeForm").addEventListener("submit", async (e) => {
    e.preventDefault();
    const form   = e.target;
    const status = document.getElementById("testeStatus");
    const fd     = new FormData(form);

    status.textContent = "A enviar...";
    status.style.color = "#6b7280";

    try {
        const res  = await fetch("/PAP/api/create_teste.php", { method: "POST", body: fd });
        const data = await res.json();

        if (data.ok) {
            status.textContent = "Teste criado.";
            status.style.color = "#10b981";
            form.reset();
        } else {
            status.textContent = "Erro: " + (data.error || "desconhecido");
            status.style.color = "#ef4444";
        }
    } catch (err) {
        status.textContent = "Erro de rede";
        status.style.color = "#ef4444";
    }
});
</script>

</body>
</html>