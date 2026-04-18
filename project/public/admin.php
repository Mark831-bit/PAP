<?php


require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /PAP/project/public/index.php");
    exit;
}

/* Stat-cards */
$statAlunosTotal   = 0;
$statAlunosPresent = 0;
$statProfTotal     = 0;
$statProfPresent   = 0;

if ($res = $conn->query("SELECT COUNT(*) AS n FROM alunos")) {
    $statAlunosTotal = (int)($res->fetch_assoc()['n'] ?? 0);
}
if ($res = $conn->query("SELECT COUNT(*) AS n FROM alunos WHERE `Presença` = 1")) {
    $statAlunosPresent = (int)($res->fetch_assoc()['n'] ?? 0);
}
if ($res = $conn->query("SELECT COUNT(*) AS n FROM professores")) {
    $statProfTotal = (int)($res->fetch_assoc()['n'] ?? 0);
}
if ($res = $conn->query("SELECT COUNT(*) AS n FROM professores WHERE `Presença` = 1")) {
    $statProfPresent = (int)($res->fetch_assoc()['n'] ?? 0);
}

/* График: последние 7 дней, уникальные вошедшие */
$chartLabels = [];
$chartValues = [];

for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = date('d/m', strtotime($d));

    $count = 0;
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT login) AS n
        FROM presencas
        WHERE data = ? AND presenca = 1
    ");
    if ($stmt) {
        $stmt->bind_param("s", $d);
        $stmt->execute();
        $count = (int)($stmt->get_result()->fetch_assoc()['n'] ?? 0);
        $stmt->close();
    }
    $chartValues[] = $count;
}

/* Последние 10 сканов */
$lastScans = [];
if ($res = $conn->query("
    SELECT nome, person_type, data, hora, presenca
    FROM presencas
    ORDER BY data DESC, hora DESC
    LIMIT 10
")) {
    while ($row = $res->fetch_assoc()) {
        $lastScans[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Panel</title>
  <link rel="stylesheet" href="/PAP/project/assets/style.css?v=310">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="page-admin">

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
        <h1>Admin</h1>
      </div>
    </div>
  </header>

  <main class="page-content">
    <section class="admin-panel">

      <div class="admin-tabs">
        <button class="admin-tab active" data-tab="charts">Charts</button>
        <button class="admin-tab" data-tab="cards">Cards</button>
        <button class="admin-tab" data-tab="alunos">Alunos</button>
        <button class="admin-tab" data-tab="professores">Professores</button>
        <button class="admin-tab" data-tab="logs">Logs</button>
      </div>

      <!-- CHARTS -->
      <div class="admin-tab-content active" id="tab-charts">

        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-label">Alunos (total)</div>
            <div class="stat-value"><?= $statAlunosTotal ?></div>
          </div>
          <div class="stat-card">
            <div class="stat-label">Alunos presentes</div>
            <div class="stat-value"><?= $statAlunosPresent ?></div>
          </div>
          <div class="stat-card">
            <div class="stat-label">Professores (total)</div>
            <div class="stat-value"><?= $statProfTotal ?></div>
          </div>
          <div class="stat-card">
            <div class="stat-label">Professores presentes</div>
            <div class="stat-value"><?= $statProfPresent ?></div>
          </div>
        </div>

        <div class="admin-card">
          <h2>Presenças — últimos 7 dias</h2>
          <div class="chart-wrap">
            <canvas id="chartPresencas"></canvas>
          </div>
        </div>

        <div class="admin-card">
          <h2>Últimos scans</h2>
          <?php if (count($lastScans) > 0): ?>
            <div class="scans-list">
              <?php foreach ($lastScans as $s): ?>
                <div class="scan-row">
                  <span class="scan-dot <?= ((int)$s['presenca'] === 1) ? 'present' : 'absent' ?>"></span>
                  <div class="scan-info">
                    <div class="scan-name"><?= htmlspecialchars($s['nome']) ?></div>
                    <div class="scan-meta">
                      <?= htmlspecialchars($s['person_type']) ?> •
                      <?= htmlspecialchars(date('d/m/Y', strtotime($s['data']))) ?>
                      <?= htmlspecialchars(substr($s['hora'], 0, 5)) ?>
                    </div>
                  </div>
                  <div class="scan-type <?= ((int)$s['presenca'] === 1) ? 'present-text' : 'absent-text' ?>">
                    <?= ((int)$s['presenca'] === 1) ? 'Entrada' : 'Saída' ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <p class="empty-state">Ainda não há scans registados.</p>
          <?php endif; ?>
        </div>

      </div>

      <script>
        window.__chartData = {
          labels: <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>,
          values: <?= json_encode($chartValues) ?>
        };
      </script>

      <!-- CARDS -->
      <div class="admin-tab-content" id="tab-cards">

        <!-- Adicionar -->
        <div class="admin-card">
          <h2>Adicionar</h2>

          <form class="admin-form" id="addCardForm">

            <div class="radio-group">
              <label class="radio-option">
                <input type="radio" name="role" value="Aluno" checked>
                <span>Aluno</span>
              </label>

              <label class="radio-option">
                <input type="radio" name="role" value="Professor">
                <span>Professor</span>
              </label>
            </div>

            <div class="form-row">
              <label for="addNome">Nome</label>
              <input type="text" id="addNome" name="nome" placeholder="Nome completo">
            </div>

            <div class="form-row">
              <label for="addLogin">Login</label>
              <input type="text" id="addLogin" name="login" placeholder="Login">
            </div>

            <div class="form-row">
              <label for="addPassword">Password</label>
              <input type="password" id="addPassword" name="password" placeholder="Password">
            </div>

            <div class="form-row">
              <label for="addIdade">Idade</label>
              <input type="number" id="addIdade" name="idade" placeholder="Idade">
            </div>

            <div class="form-row turma-row">
              <div>
                <label for="addTurmaNum">Turma</label>
                <select id="addTurmaNum" name="turma_num">
                  <option value="">Ano</option>
                  <option value="10">10</option>
                  <option value="11">11</option>
                  <option value="12">12</option>
                </select>
              </div>

              <div>
                <label for="addTurmaLetra">&nbsp;</label>
                <select id="addTurmaLetra" name="turma_letra">
                  <option value="">Letra</option>
                  <option value="A">A</option>
                  <option value="B">B</option>
                  <option value="C">C</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <label for="addNumeroTurma">Número em turma</label>
              <input type="number" id="addNumeroTurma" name="numero_turma" placeholder="Número">
            </div>

            <div class="form-row uid-row">
              <div class="uid-input-wrap">
                <label for="addUid">UID</label>
                <input type="text" id="addUid" name="uid" placeholder="UID do cartão">
              </div>

              <div class="uid-button-wrap">
                <label>&nbsp;</label>
                <button type="button" class="secondary-btn" id="addScanBtn">Ler cartão</button>
              </div>
            </div>

            <div class="scan-status" id="addScanStatus"></div>

            <div class="form-actions">
              <button type="submit">Guardar</button>
              <div id="addCardStatus"></div>
              
            </div>
          </form>
        </div>

        <!-- Atualizar -->
      <div class="admin-card">
        <h2>Atualizar</h2>

        <div class="search-bar">
          <input type="text" id="update-search" placeholder="Pesquisar nome">
        </div>

        <div id="update-results"></div>

        <form id="update-form" style="display:none;">
          <input type="hidden" id="update-id" name="person_id">
          <input type="hidden" id="update-type" name="person_type" value="Aluno">

          <div class="form-row">
            <label for="update-nome">Nome</label>
            <input type="text" id="update-nome" name="nome">
          </div>

          <div class="form-row">
            <label for="update-login">Login</label>
            <input type="text" id="update-login" name="login" readonly>
          </div>

          <div class="form-row">
            <label for="update-password">Password</label>
            <input type="password" id="update-password" name="password" placeholder="Novo password">
          </div>

          <div class="form-row">
            <label for="updateI-idade">Idade</label>
            <input type="number" id="update-idade" name="idade">
          </div>

          <div class="form-row turma-row">
            <div>
              <label for="updateTurmaNum">Turma</label>
              <select id="updateTurmaNum" name="turma_num">
                <option value="">Ano</option>
                <option value="10">10</option>
                <option value="11">11</option>
                <option value="12">12</option>
              </select>
            </div>

            <div>
              <label for="updateTurmaLetra">&nbsp;</label>
              <select id="updateTurmaLetra" name="turma_letra">
                <option value="">Letra</option>
                <option value="A">A</option>
                <option value="B">B</option>
                <option value="C">C</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <label for="updateNumeroTurma">Número em turma</label>
            <input type="number" id="updateNumeroTurma" name="numero_turma">
          </div>

          <div class="form-row uid-row">
            <div class="uid-input-wrap">
              <label for="updateUid">UID</label>
              <input type="text" id="updateUid" name="uid">
            </div>

            <div class="uid-button-wrap">
              <label>&nbsp;</label>
              <button type="button" class="secondary-btn" id="updateScanBtn">Ler cartão</button>
            </div>
          </div>

          <div class="scan-status" id="updateScanStatus"></div>

          <div class="form-actions">
            <button type="submit">Atualizar</button>
          </div>

          <div id="updateCardStatus"></div>
        </form>
      </div>

        <!-- Encontrar -->
        <div class="admin-card">
          <h2>Encontrar</h2>

          <div class="search-bar">
            <input type="text" id="find-search" placeholder="Procurar pessoa...">
          </div>

          <div id="find-results"></div>

          <form id="find-form" style="display:none;">
            <input type="hidden" id="find-id">
            <input type="hidden" id="find-type">

            <div class="form-row">
              <label for="find-nome">Nome</label>
              <input type="text" id="find-nome" readonly>
            </div>

            <div class="form-row">
              <label for="find-login">Login</label>
              <input type="text" id="find-login" readonly>
            </div>

            <div class="form-row">
              <label for="find-password">Password</label>
              <input type="text" id="find-password" value="********" readonly>
            </div>

            <div class="form-row">
              <label for="find-idade">Idade</label>
              <input type="text" id="find-idade" readonly>
            </div>

            <div class="form-row turma-row">
              <div>
                <label for="findTurmaNum">Turma</label>
                <select id="findTurmaNum" disabled>
                  <option value="">Ano</option>
                  <option value="10">10</option>
                  <option value="11">11</option>
                  <option value="12">12</option>
                </select>
              </div>

              <div>
                <label for="findTurmaLetra">&nbsp;</label>
                <select id="findTurmaLetra" disabled>
                  <option value="">Letra</option>
                  <option value="A">A</option>
                  <option value="B">B</option>
                  <option value="C">C</option>
                </select>
              </div>
        </div>

          <div class="form-row">
            <label for="findNumeroTurma">Número em turma</label>
            <input type="text" id="findNumeroTurma" readonly>
          </div>

          <div class="form-row">
            <label for="findUid">UID</label>
            <input type="text" id="findUid" readonly>
          </div>
        </form>
      </div>

      </div>

      <!-- ALUNOS -->
      <div class="admin-tab-content" id="tab-alunos">

        <div class="subtabs">
          <button class="subtab active" data-subtab="alunos-list">List</button>
          <button class="subtab" data-subtab="alunos-tests">Tests</button>
        </div>

        <!-- LIST -->
        <div class="subtab-content active" id="subtab-alunos-list">
          <div class="admin-card">
            <div class="logs-header">
              <h2>Alunos</h2>
              <input type="text" id="alunosSearch" placeholder="Pesquisar..." class="logs-filters-input">
            </div>
            <div id="alunos-list"><p class="logs-empty">A carregar...</p></div>
          </div>

          <div class="admin-card" id="alunoDossier" style="display:none;">
            <div class="dossier-header">
              <h2 id="dossierNome">—</h2>
              <button class="secondary-btn" id="dossierClose">Fechar</button>
            </div>

            <div class="dossier-grid">
              <div><span class="dossier-label">Login</span><span id="dossierLogin">—</span></div>
              <div><span class="dossier-label">Idade</span><span id="dossierIdade">—</span></div>
              <div><span class="dossier-label">Turma</span><span id="dossierTurma">—</span></div>
              <div><span class="dossier-label">Nº em turma</span><span id="dossierNumero">—</span></div>
              <div><span class="dossier-label">UID</span><span id="dossierUid">—</span></div>
              <div><span class="dossier-label">Presença</span><span id="dossierPresenca">—</span></div>
              <div><span class="dossier-label">Cartão</span><span id="dossierBlocked">—</span></div>
            </div>

            <div class="dossier-actions">
              <button class="secondary-btn" id="btnBlockCard">Bloquear cartão</button>
              <button class="danger-btn" id="btnDeleteAluno">Eliminar aluno</button>
            </div>
          </div>
        </div>

        <!-- TESTS -->
        <div class="subtab-content" id="subtab-alunos-tests">
          <div class="admin-card">
            <div class="logs-header">
              <h2>Testes</h2>
            </div>

            <div class="logs-filters">
              <select id="testesFilterNum">
                <option value="">Todas as turmas</option>
                <option value="10">10</option>
                <option value="11">11</option>
                <option value="12">12</option>
              </select>
              <select id="testesFilterLetra">
                <option value="">Todas as letras</option>
                <option value="A">A</option>
                <option value="B">B</option>
                <option value="C">C</option>
              </select>
            </div>

            <div id="testes-list"><p class="logs-empty">A carregar...</p></div>
          </div>
        </div>

      </div>

      <!-- MODAL CONFIRM -->
      <div class="modal-overlay" id="confirmModal" style="display:none;">
        <div class="modal-box">
          <h3 id="confirmTitle">Confirmação</h3>
          <p id="confirmText">—</p>
          <div class="modal-actions">
            <button class="secondary-btn" id="confirmCancel">Cancelar</button>
            <button class="danger-btn" id="confirmOk">Confirmar</button>
          </div>
        </div>
      </div>

      <div class="admin-tab-content" id="tab-professores">
        <div class="admin-card"><h2>Professores</h2><p>Em desenvolvimento...</p></div>
      </div>

      <div class="admin-tab-content" id="tab-logs">
        <div class="admin-card">
          <div class="logs-header">
            <h2>Logs</h2>
            <button class="secondary-btn" id="logsRefresh">Atualizar</button>
          </div>

          <div class="logs-filters">
            <input type="text" id="logsSearch" placeholder="Pesquisar...">
            <select id="logsLevel">
              <option value="">Todos os níveis</option>
              <option value="INFO">INFO</option>
              <option value="WARN">WARN</option>
              <option value="ERROR">ERROR</option>
            </select>
          </div>

          <div id="logs-list">
            <p class="logs-empty">A carregar logs...</p>
          </div>
        </div>
      </div>

    </section>
  </main>

<script src="/PAP/project/assets/app.js?v=310"></script>
</body>

</html>





