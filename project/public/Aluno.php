<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['login']) || ($_SESSION['role'] ?? '') !== 'Aluno') {
    header("Location: /PAP/project/public/index.php");
    exit;
}

if (($_SESSION['lang'] ?? 'pt') === 'en') {
    header("Location: /PAP/project/public/Aluno_EN.php");
    exit;
}

$login = $_SESSION['login'];

$stmt = $conn->prepare("SELECT Nome, CONCAT(turma_num, turma_letra) AS Turma, turma_num, turma_letra FROM alunos WHERE login = ? LIMIT 1");
$stmt->bind_param("s", $login);
$stmt->execute();
$aluno = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$aluno) die("Aluno não encontrado.");

$alunoNome       = $aluno['Nome'];
$alunoTurma      = $aluno['Turma'];
$alunoTurmaNum   = (int)($aluno['turma_num'] ?? 0);
$alunoTurmaLetra = $aluno['turma_letra'] ?? '';

$testesFuturos  = [];
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
$res   = $stmt->get_result();
$today = date('Y-m-d');
while ($row = $res->fetch_assoc()) {
    if ($row['data_teste'] >= $today) $testesFuturos[]  = $row;
    else                              $testesPassados[] = $row;
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
    <link rel="stylesheet" href="/PAP/project/assets/style.css?v=335">
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
            <div class="user-status" style="color:black;">
                Sessão iniciada como: <?= htmlspecialchars($_SESSION['nome'] ?? '') ?> (<?= htmlspecialchars($_SESSION['role']) ?>)
            </div>
            <div id="logoutBox">
                <a href="/PAP/api/logout.php">Terminar sessão</a>
            </div>
        </div>
    </div>
</header>

<div class="lang-switcher">
    <a href="/PAP/api/set_lang.php?lang=pt&to=/PAP/project/public/Aluno.php" class="lang-btn lang-active" title="Português">
        <img src="/PAP/project/assets/pt.png" alt="PT" class="lang-flag">
    </a>
    <a href="/PAP/api/set_lang.php?lang=en&to=/PAP/project/public/Aluno_EN.php" class="lang-btn" title="English">
        <img src="/PAP/project/assets/britan.png" alt="EN" class="lang-flag">
    </a>
</div>

<main class="page-content">
    <section class="admin-panel">

        <div class="students-panel-header">
            <div>
                <h2><?= htmlspecialchars($alunoNome) ?></h2>
                <p>Turma <?= htmlspecialchars($alunoTurma) ?></p>
            </div>
        </div>

        <div class="admin-tabs">
            <button class="admin-tab active" data-tab="aluno-testes">Testes</button>
            <button class="admin-tab" data-tab="aluno-notas">Notas</button>
            <button class="admin-tab" data-tab="aluno-sumarios">Sumários</button>
            <button class="admin-tab" data-tab="aluno-agenda">Agenda</button>
            <button class="admin-tab" data-tab="aluno-horario">Horário</button>
            <button class="admin-tab" data-tab="aluno-documentos">Documentos</button>
        </div>

        <!-- ─── TAB 1: TESTES ─── -->
        <div class="admin-tab-content active" id="tab-aluno-testes">
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
                                        <?php if (!empty($t['professor_nome'])): ?> • <?= htmlspecialchars($t['professor_nome']) ?><?php endif; ?>
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
                                    <?php if (!empty($t['professor_nome'])): ?> • <?= htmlspecialchars($t['professor_nome']) ?><?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- ─── TAB 2: NOTAS ─── -->
        <div class="admin-tab-content" id="tab-aluno-notas">
            <div class="admin-card">
                <h2>As minhas notas</h2>
                <div id="notasChartWrap" style="display:none; margin-bottom:18px;">
                    <canvas id="notasChart" height="100"></canvas>
                </div>
                <div id="notasAluno" class="notas-aluno">
                    <p class="empty-state">A carregar...</p>
                </div>
            </div>
        </div>

        <!-- ─── TAB 3: SUMÁRIOS ─── -->
        <div class="admin-tab-content" id="tab-aluno-sumarios">
            <div class="admin-card">
                <h2>Sumários da minha turma</h2>
                <div id="sumariosList" class="sumarios-list">
                    <p class="empty-state">A carregar...</p>
                </div>
            </div>
        </div>

        <!-- ─── TAB 4: AGENDA ─── -->
        <div class="admin-tab-content" id="tab-aluno-agenda">
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
        </div>

        <!-- ─── TAB: HORÁRIO ─── -->
        <div class="admin-tab-content" id="tab-aluno-horario">
            <div class="admin-card">
                <h2>O meu horário</h2>
                <div class="horario-grid" id="alunoHorarioGrid">
                    <p class="empty-state">A carregar...</p>
                </div>
            </div>
        </div>

        <!-- ─── TAB 5: DOCUMENTOS ─── -->
        <div class="admin-tab-content" id="tab-aluno-documentos">
            <div class="admin-card">
                <h2>Os meus documentos</h2>
                <p style="color:#6b7280;font-size:14px;margin:0 0 18px;">PDF, Word, Excel, PowerPoint, imagens — máx. 15 MB por ficheiro.</p>

                <div class="doc-upload-area" id="docDropZone">
                    <input type="file" id="docFileInput" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.jpg,.jpeg,.png,.gif,.webp" style="display:none">
                    <div class="doc-upload-icon">📎</div>
                    <p class="doc-upload-label">Arrasta ficheiros aqui ou <button type="button" class="doc-upload-btn" id="docPickBtn">escolhe ficheiro</button></p>
                    <div id="docUploadStatus"></div>
                </div>

                <div id="docList" class="doc-list">
                    <p class="empty-state">A carregar...</p>
                </div>
            </div>
        </div>

    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="/PAP/project/assets/app.js?v=20"></script>
<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

    // ── Tab switcher ─────────────────────────────────────────
    document.querySelectorAll('.admin-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.admin-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.admin-tab-content').forEach(c => c.classList.remove('active'));
            tab.classList.add('active');
            const content = document.getElementById('tab-' + tab.dataset.tab);
            if (content) content.classList.add('active');
            if (tab.dataset.tab === 'aluno-documentos') loadDocs();
        });
    });

    // ── DOCUMENTOS ───────────────────────────────────────────
    const dropZone    = document.getElementById('docDropZone');
    const fileInput   = document.getElementById('docFileInput');
    const pickBtn     = document.getElementById('docPickBtn');
    const uploadStatus = document.getElementById('docUploadStatus');
    const docList     = document.getElementById('docList');

    function escHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function formatSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function fileIcon(name) {
        const ext = name.split('.').pop().toLowerCase();
        if (['jpg','jpeg','png','gif','webp'].includes(ext)) return '🖼️';
        if (ext === 'pdf') return '📄';
        if (['doc','docx'].includes(ext)) return '📝';
        if (['xls','xlsx'].includes(ext)) return '📊';
        if (['ppt','pptx'].includes(ext)) return '📑';
        return '📎';
    }

    async function loadDocs() {
        if (!docList) return;
        docList.innerHTML = '<p class="empty-state">A carregar...</p>';
        try {
            const res  = await fetch('/PAP/api/documentos.php?action=list');
            const data = await res.json();
            if (!data.ok) throw new Error();
            renderDocs(data.files);
        } catch {
            docList.innerHTML = '<p class="empty-state">Erro ao carregar.</p>';
        }
    }

    function renderDocs(files) {
        if (!files.length) {
            docList.innerHTML = '<p class="empty-state">Sem documentos carregados.</p>';
            return;
        }
        docList.innerHTML = files.map(f => `
            <div class="doc-row" data-name="${escHtml(f.name)}">
                <span class="doc-icon">${fileIcon(f.name)}</span>
                <div class="doc-info">
                    <a class="doc-name" href="${escHtml(f.url)}" target="_blank" rel="noopener">${escHtml(f.name)}</a>
                    <span class="doc-meta">${formatSize(f.size)}</span>
                </div>
                <button class="doc-del-btn" type="button" title="Eliminar">🗑️</button>
            </div>
        `).join('');

        docList.querySelectorAll('.doc-del-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const row  = btn.closest('.doc-row');
                const name = row.dataset.name;
                if (!confirm('Eliminar "' + name + '"?')) return;
                const fd = new FormData();
                fd.append('filename', name);
                const res  = await fetch('/PAP/api/documentos.php?action=delete', {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': CSRF },
                    body: fd,
                });
                const data = await res.json();
                if (data.ok) loadDocs();
                else alert('Erro ao eliminar.');
            });
        });
    }

    async function uploadFile(file) {
        uploadStatus.textContent = 'A carregar "' + file.name + '"...';
        uploadStatus.style.color = '#6b7280';

        const fd = new FormData();
        fd.append('file', file);
        try {
            const res  = await fetch('/PAP/api/documentos.php?action=upload', {
                method: 'POST',
                headers: { 'X-CSRF-Token': CSRF },
                body: fd,
            });
            const data = await res.json();
            if (data.ok) {
                uploadStatus.textContent = '✓ Carregado com sucesso.';
                uploadStatus.style.color = '#10b981';
                loadDocs();
                setTimeout(() => { uploadStatus.textContent = ''; }, 3000);
            } else {
                uploadStatus.textContent = '✗ ' + (data.error || 'Erro.');
                uploadStatus.style.color = '#ef4444';
            }
        } catch {
            uploadStatus.textContent = '✗ Erro de rede.';
            uploadStatus.style.color = '#ef4444';
        }
    }

    if (pickBtn) pickBtn.addEventListener('click', () => fileInput.click());

    if (fileInput) {
        fileInput.addEventListener('change', () => {
            if (fileInput.files[0]) uploadFile(fileInput.files[0]);
            fileInput.value = '';
        });
    }

    if (dropZone) {
        dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('doc-drag-over'); });
        dropZone.addEventListener('dragleave', () => dropZone.classList.remove('doc-drag-over'));
        dropZone.addEventListener('drop', e => {
            e.preventDefault();
            dropZone.classList.remove('doc-drag-over');
            const file = e.dataTransfer.files[0];
            if (file) uploadFile(file);
        });
    }

    // Load docs on first visit if tab is active
    if (document.getElementById('tab-aluno-documentos')?.classList.contains('active')) loadDocs();
})();
</script>
</body>
</html>
