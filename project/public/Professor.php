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
    SELECT DISTINCT CONCAT(`turma_num`, `turma_letra`) AS turma
    FROM alunos
    WHERE `turma_num` IS NOT NULL AND `turma_letra` IS NOT NULL
    ORDER BY `turma_num` ASC, `turma_letra` ASC
");

if ($stmtTurmas) {
    $stmtTurmas->execute();
    $resultTurmas = $stmtTurmas->get_result();

    while ($row = $resultTurmas->fetch_assoc()) {
        $turmas[] = $row['turma'];
    }
}

/* 3. Фильтры */
$filtroTurma = $_GET['turma'] ?? $turma;
$filtroPresenca = $_GET['presenca'] ?? '';

/* 4. Получаем учеников по выбранной turma + presença */
$sqlAlunos = "
    SELECT
        `ID`,
        `Nome` AS nome,
        TIMESTAMPDIFF(YEAR, `data_nascimento`, CURDATE()) AS idade,
        CONCAT(`turma_num`, `turma_letra`) AS turma,
        `Presença` AS presenca,
        `login`
    FROM alunos
    WHERE CONCAT(`turma_num`, `turma_letra`) = ?
";

$params = [$filtroTurma];
$types = "s";

if ($filtroPresenca !== '' && ($filtroPresenca === '0' || $filtroPresenca === '1')) {
    $sqlAlunos .= " AND `Presença` = ?";
    $params[] = (int)$filtroPresenca;
    $types .= "i";
}

$sqlAlunos .= " ORDER BY `turma_num` ASC, `turma_letra` ASC, `Nome` ASC";

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
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
    <title>Professor - Os meus alunos</title>
    <link rel="stylesheet" href="/PAP/project/assets/style.css?v=311">
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
        </div>

        <div class="topbar-right">
                <div class="topbar-right">
                    <?php if (isset($_SESSION['user_id'])): ?>

                    <div class="user-status" style="color: black;">
                        Sessão iniciada como: <?= htmlspecialchars($_SESSION['nome'] ?? '') ?> (<?= htmlspecialchars($_SESSION['role']) ?>)
                    </div>

                    <div id="logoutBox">
                        <a href="/PAP/api/logout.php">Logout</a>
                    </div>

                    <?php else: ?>

                    <div class="auth-buttons">
                        <button type="button" class="button" id="openLogin">Login</button>
                        <button type="button" class="button" id="openRegister">Register</button>
                    </div>

                    <?php endif; ?>
                </div>
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
                        <select id="testeTurmaNum" name="turma_num" required>
                            <option value="">Ano</option>
                            <option value="10">10</option>
                            <option value="11">11</option>
                            <option value="12">12</option>
                        </select>
                    </div>

                    <div>
                        <label for="testeTurmaLetra">&nbsp;</label>
                        <select id="testeTurmaLetra" name="turma_letra" required>
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

       

        <div class="admin-card">
            <h2>Lançar nota</h2>
            <form id="notaForm" class="agenda-form">
                <div class="form-row">
                    <label for="notaAluno">Aluno</label>
                    <select id="notaAluno" name="login_aluno" required>
                        <option value="">A carregar alunos...</option>
                    </select>
                </div>
                <div class="form-row turma-row">
                    <div>
                        <label for="notaTipo">Tipo</label>
                        <select id="notaTipo" name="tipo" required>
                            <option value="">Tipo de avaliação</option>
                            <option value="Teste">Teste</option>
                            <option value="Trabalho">Trabalho</option>
                            <option value="Oral">Oral</option>
                            <option value="Participação">Participação</option>
                            <option value="Projeto">Projeto</option>
                        </select>
                    </div>
                    <div>
                        <label for="notaValor">Valor (0–20)</label>
                        <input type="number" id="notaValor" name="valor" min="0" max="20" step="0.1" required>
                    </div>
                </div>
                <div class="form-row">
                    <label for="notaData">Data</label>
                    <input type="date" id="notaData" name="data" required>
                </div>
                <div class="form-row">
                    <label for="notaObservacao">Observação (opcional)</label>
                    <textarea id="notaObservacao" name="observacao" rows="2"></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit">Lançar nota</button>
                    <div id="notaStatus"></div>
                </div>
            </form>

            <h3 class="subsection-title">Notas lançadas</h3>
            <div id="notasList" class="notas-list">
                <p class="empty-state">A carregar...</p>
            </div>
        </div>
        <br>
        <div class="admin-card">
            <h2>Sumários</h2>
            <form id="sumarioForm" class="agenda-form">
                <div class="form-row turma-row">
                    <div>
                        <label for="sumTurmaNum">Turma</label>
                        <select id="sumTurmaNum" name="turma_num" required>
                            <option value="">Ano</option>
                            <option value="10">10</option>
                            <option value="11">11</option>
                            <option value="12">12</option>
                        </select>
                    </div>
                    <div>
                        <label for="sumTurmaLetra">&nbsp;</label>
                        <select id="sumTurmaLetra" name="turma_letra" required>
                            <option value="">Letra</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <label for="sumData">Data</label>
                    <input type="date" id="sumData" name="data" required>
                </div>
                <div class="form-row">
                    <label for="sumDescricao">Descrição (inclui justificações de faltas, se houver)</label>
                    <textarea id="sumDescricao" name="descricao" rows="4" required placeholder="Tema da aula, matérias abordadas, justificações de faltas..."></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit">Criar sumário</button>
                    <div id="sumStatus"></div>
                </div>
            </form>

            <div id="sumariosList" class="sumarios-list">
                <p class="empty-state">A carregar...</p>
            </div>
        </div>
                            <br>
        <div class="admin-card">
            <h2>A minha agenda</h2>
            <form id="agendaForm" class="agenda-form">
                <div class="form-row">
                    <label for="agTitulo">Título</label>
                    <input type="text" id="agTitulo" name="titulo" maxlength="200" required placeholder="O que preciso de fazer">
                </div>
                <div class="form-row">
                    <label for="agData">Data (opcional)</label>
                    <input type="date" id="agData" name="data">
                </div>
                <div class="form-actions">
                    <button type="submit">Adicionar</button>
                    <div id="agStatus"></div>
                </div>
            </form>

            <div id="agendaList" class="agenda-list">
                <p class="empty-state">A carregar...</p>
            </div>
        </div>
                            <br>
        <div class="students-list">
            <?php if (count($alunos) > 0): ?>
                <?php foreach ($alunos as $aluno): ?>
                    <div class="student-card" data-aluno-id="<?= (int)$aluno['ID'] ?>">
                        <div class="student-left">
                            <span class="status-dot <?= ((int)$aluno['presenca'] === 1) ? 'present' : 'absent' ?>"></span>

                            <div class="student-info">
                                <div class="student-name">
                                    <?= htmlspecialchars($aluno['nome']) ?>
                                </div>

                                <div class="student-meta">
                                    <?php if (!empty($aluno['idade'])): ?>Idade <?= htmlspecialchars($aluno['idade']) ?> • <?php endif; ?>
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
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const res  = await fetch("/PAP/api/create_teste.php", {
            method: "POST",
            headers: { "X-CSRF-Token": csrf },
            body: fd
        });
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

(function () {
    const turma = <?= json_encode($filtroTurma) ?>;
    if (!turma) return;

    async function refreshPresence() {
        try {
            const res = await fetch("/PAP/api/presence.php?turma=" + encodeURIComponent(turma), {
                credentials: "same-origin"
            });
            if (!res.ok) return;
            const data = await res.json();
            if (!data.ok || !Array.isArray(data.alunos)) return;

            data.alunos.forEach(a => {
                const card = document.querySelector('.student-card[data-aluno-id="' + a.id + '"]');
                if (!card) return;

                const dot   = card.querySelector('.status-dot');
                const right = card.querySelector('.student-right');
                const presente = a.presenca === 1;

                if (dot) {
                    dot.classList.toggle('present', presente);
                    dot.classList.toggle('absent', !presente);
                }
                if (right) {
                    right.classList.toggle('present-text', presente);
                    right.classList.toggle('absent-text', !presente);
                    right.textContent = presente ? 'Presente' : 'Falta';
                }
            });
        } catch (e) { /* silencioso */ }
    }

    setInterval(refreshPresence, 5000);
})();
</script>

<script src="/PAP/project/assets/app.js?v=12"></script>
</body>
</html>