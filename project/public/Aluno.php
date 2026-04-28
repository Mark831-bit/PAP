<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['login']) || ($_SESSION['role'] ?? '') !== 'Aluno') {
    header("Location: /PAP/project/public/index.php");
    exit;
}

$login = $_SESSION['login'];

/* 1. Данные ученика */
$stmt = $conn->prepare("SELECT Nome, CONCAT(turma_num, turma_letra) AS Turma, turma_num, turma_letra FROM alunos WHERE login = ? LIMIT 1");
$stmt->bind_param("s", $login);
$stmt->execute();
$aluno = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$aluno) {
    die("Aluno não encontrado.");
}

$alunoNome       = $aluno['Nome'];
$alunoTurma      = $aluno['Turma'];
$alunoTurmaNum   = (int)($aluno['turma_num'] ?? 0);
$alunoTurmaLetra = $aluno['turma_letra'] ?? '';

/* 2. Тесты для его turma (будущие + сегодня) */
$testesFuturos = [];
$testesPassados = [];

$stmt = $conn->prepare("
    SELECT t.titulo, t.descricao, t.data_teste, t.materia,
           p.Nome AS professor_nome
    FROM testes t
    LEFT JOIN professores p ON p.login = t.professor_login
    WHERE t.turma_num = ? AND t.turma_letra = ?
    ORDER BY t.data_teste ASC
");
$stmt->bind_param("is", $alunoTurmaNum, $alunoTurmaLetra);
$stmt->execute();
$res = $stmt->get_result();

$today = date('Y-m-d');
while ($row = $res->fetch_assoc()) {
    if ($row['data_teste'] >= $today) {
        $testesFuturos[] = $row;
    } else {
        $testesPassados[] = $row;
    }
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
    <title>Aluno</title>
    <link rel="stylesheet" href="/PAP/project/assets/style.css?v=311">
</head>

<body class="page-aluno">
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
                    <h2>Os meus testes</h2>
                    <p>
                        <?= htmlspecialchars($alunoNome) ?> •
                        Turma <?= htmlspecialchars($alunoTurma) ?>
                    </p>
                </div>
            </div>

            <div class="admin-card">
                <h2>Próximos testes</h2>
                <?php if (count($testesFuturos) > 0): ?>
                    <div class="testes-list">
                        <?php foreach ($testesFuturos as $t): ?>
                            <div class="teste-card">
                                <div class="teste-date"><?= htmlspecialchars(date('d/m/Y', strtotime($t['data_teste']))) ?></div>
                                <div class="teste-body">
                                    <div class="teste-title"><?= htmlspecialchars($t['titulo']) ?></div>
                                    <div class="teste-meta">
                                        <?= htmlspecialchars($t['materia'] ?? '—') ?>
                                        <?php if (!empty($t['professor_nome'])): ?>
                                            • <?= htmlspecialchars($t['professor_nome']) ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($t['descricao'])): ?>
                                        <div class="teste-desc"><?= htmlspecialchars($t['descricao']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="empty-state">Não tens testes marcados.</p>
                <?php endif; ?>
            </div>
                    <br>    
            <?php if (count($testesPassados) > 0): ?>
                
                <div class="admin-card">
                    <h2>Testes passados</h2>
                    <div class="testes-list">
                        <?php foreach (array_reverse($testesPassados) as $t): ?>
                            <div class="teste-card teste-past">
                                <div class="teste-date"><?= htmlspecialchars(date('d/m/Y', strtotime($t['data_teste']))) ?></div>
                                <div class="teste-body">
                                    <div class="teste-title"><?= htmlspecialchars($t['titulo']) ?></div>
                                    <div class="teste-meta">
                                        <?= htmlspecialchars($t['materia'] ?? '—') ?>
                                        <?php if (!empty($t['professor_nome'])): ?>
                                            • <?= htmlspecialchars($t['professor_nome']) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <br>

            <div class="admin-card">
                <h2>As minhas notas</h2>
                <div id="notasChartWrap" style="display:none; margin-bottom: 18px;">
                    <canvas id="notasChart" height="100"></canvas>
                </div>
                <div id="notasAluno" class="notas-aluno">
                    <p class="empty-state">A carregar...</p>
                </div>
            </div>
                    <br>
            <div class="admin-card">
                <h2>Sumários da minha turma</h2>
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
        </section>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="/PAP/project/assets/app.js?v=12"></script>
</body>
</html>
