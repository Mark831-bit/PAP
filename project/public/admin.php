<?php


require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /PAP/project/public/index.php");
    exit;
}

if (($_SESSION['lang'] ?? 'pt') === 'en') {
    header("Location: /PAP/project/public/admin_en.php");
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
  <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
  <title>Admin Panel</title>
  <link rel="stylesheet" href="/PAP/project/assets/style.css?v=335">
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
      </div>

      <div class="topbar-right">
        
                <div class="topbar-right">
                    <?php if (isset($_SESSION['user_id'])): ?>

                    <div class="user-status" style="color: black;">
                        Sessão iniciada como: <?= htmlspecialchars($_SESSION['nome'] ?? '') ?> (<?= htmlspecialchars($_SESSION['role']) ?>)
                    </div>

                    <div id="logoutBox">
                        <a href="/PAP/api/logout.php">Terminar sessão</a>
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

<div class="lang-switcher">
    <a href="/PAP/api/set_lang.php?lang=pt&to=/PAP/project/public/admin.php" class="lang-btn lang-active" title="Português">
        <img src="/PAP/project/assets/pt.png" alt="PT" class="lang-flag">
    </a>
    <a href="/PAP/api/set_lang.php?lang=en&to=/PAP/project/public/admin_en.php" class="lang-btn" title="English">
        <img src="/PAP/project/assets/britan.png" alt="EN" class="lang-flag">
    </a>
</div>

  <main class="page-content">
    <section class="admin-panel">

      <div class="admin-tabs">
        <button class="admin-tab active" data-tab="charts">Charts</button>
        <button class="admin-tab" data-tab="cards">Utilizadores</button>
        <button class="admin-tab" data-tab="alunos">Alunos</button>
        <button class="admin-tab" data-tab="professores">Professores</button>
        <button class="admin-tab" data-tab="noticias">Notícias</button>
        <button class="admin-tab" data-tab="horario">Horário</button>
        <button class="admin-tab" data-tab="suporte">Suporte</button>
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
          <div class="chart-header">
            <h2>Presenças</h2>
            <div class="week-nav">
              <button type="button" class="week-nav-btn" id="weekPrev" aria-label="Semana anterior">‹</button>
              <span class="week-range" id="weekRange">—</span>
              <button type="button" class="week-nav-btn" id="weekNext" aria-label="Próxima semana" disabled>›</button>
            </div>
          </div>
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

      
      <div class="admin-tab-content" id="tab-cards">

        
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
              <label for="addPassword">Palavra-passe</label>
              <input type="password" id="addPassword" name="password" placeholder="Palavra-passe">
            </div>

            <div class="form-row" data-aluno-only>
              <label for="addDataNascimento">Data de nascimento</label>
              <input type="date" id="addDataNascimento" name="data_nascimento">
            </div>

            <div class="form-row" data-professor-only style="display:none;">
              <label for="addCargo">Cargo / Posição</label>
              <input type="text" id="addCargo" name="cargo" placeholder="Ex.: Diretor de turma, Professor">
            </div>

            <div class="form-row" data-professor-only style="display:none;">
              <label for="addGabinete">Gabinete</label>
              <input type="text" id="addGabinete" name="gabinete" placeholder="Ex.: Sala 12, Bloco A">
            </div>

            <div class="form-row" data-professor-only style="display:none;">
              <label for="addHorario">Horário</label>
              <input type="text" id="addHorario" name="horario" placeholder="Ex.: 2ª–6ª 08h–17h">
            </div>

            <div class="form-row" data-professor-only style="display:none;">
              <label for="addMateria">Matéria ensinada</label>
              <input type="text" id="addMateria" name="materia" placeholder="Ex.: Matemática, Português">
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
            <label for="update-password">Palavra-passe</label>
            <input type="password" id="update-password" name="password" placeholder="Novo password">
          </div>

          <div class="form-row">
            <label for="update-data-nascimento">Data de nascimento</label>
            <input type="date" id="update-data-nascimento" name="data_nascimento">
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
    </div>




     
      <div class="admin-tab-content" id="tab-alunos">
        <div class="subtabs">
          <button class="subtab active" data-subtab="alunos-list">List</button>
          <button class="subtab" data-subtab="alunos-tests">Tests</button>
        </div>

        <div class="subtab-content active" id="subtab-alunos-list">
          <div class="admin-card alunos-list-card">
            
            <div class="alunos-list-header">
              <h2>Lista de alunos</h2>
              <div class="presence-legend">
                <span><span class="legend-dot present"></span> Presente</span>
                <span><span class="legend-dot absent"></span> Falta</span>
              </div>

              <div class="alunos-filters">
                <input type="text" id="alunosSearch" placeholder="Pesquisar...">

                <select id="alunosFilterNum">
                  <option value="">Ano</option>
                  <option value="10">10</option>
                  <option value="11">11</option>
                  <option value="12">12</option>
                </select>

                <select id="alunosFilterLetra">
                  <option value="">Letra</option>
                  <option value="A">A</option>
                  <option value="B">B</option>
                  <option value="C">C</option>
                </select>
              </div>
            </div>
            <div id="alunos-list"></div>
          </div>

          <div class="admin-card aluno-dossier-card" id="alunoDossier" style="display:none;">
            <div class="aluno-dossier-top">
              <div>
                <h2>Conta do aluno</h2>
                <div class="aluno-dossier-name" id="dossierNome">—</div>
              </div>
            </div>

            <div class="aluno-dossier-grid">
              <div class="aluno-info-box">
                <span class="dossier-label">Login</span>
                <span id="dossierLogin">—</span>
              </div>

              <div class="aluno-info-box">
                <span class="dossier-label">Turma</span>
                <span id="dossierTurma">—</span>
              </div>

              <div class="aluno-info-box">
                <span class="dossier-label">Cartão</span>
                <span id="dossierBlocked">—</span>
              </div>

              <div class="aluno-info-box">
                <span class="dossier-label">Data de nascimento</span>
                <span id="dossierIdade">—</span>
              </div>

              <div class="aluno-info-box">
                <span class="dossier-label">UID</span>
                <span id="dossierUid">—</span>
              </div>

              <div class="aluno-info-box">
                <span class="dossier-label">Presença</span>
                <span id="dossierPresenca">—</span>
              </div>

              <div class="aluno-rfid-box">
                <div class="dossier-label">Cartão RFID</div>
                <div class="rfid-help-text">
                  Clique num botão para bloquear o cartão ou eliminar o aluno.
                </div>
              </div>
            </div>

            <div class="aluno-dossier-actions">
              <button class="secondary-btn" id="btnBlockCard">Bloquear cartão</button>
              <button class="secondary-btn" id="btnDeleteAluno">Eliminar aluno</button>
              <button class="secondary-btn" id="dossierClose">Fechar</button>
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
                <!-- professores -->
      <div class="admin-tab-content" id="tab-professores">
        <div class="admin-card professores-list-card">
          <div class="alunos-list-header">
            <h2>Lista de professores</h2>
            <div class="presence-legend">
                <span><span class="legend-dot present"></span> Presente</span>
                <span><span class="legend-dot absent"></span> Falta</span>
              </div>
            <div class="alunos-filters">
              <input type="text" id="professoresSearch" placeholder="Pesquisar...">
            </div>
          </div>

          <div id="professores-list"></div>
        </div>

        <div class="admin-card professor-dossier-card" id="professorDossier" style="display:none;">
          <div class="aluno-dossier-top">
            <div>
              <h2>Conta do professor</h2>
              <div class="aluno-dossier-name" id="professorNome">—</div>
            </div>
          </div>

          <div class="aluno-dossier-grid">
            <div class="aluno-info-box">
              <span class="dossier-label">Login</span>
              <span id="professorLogin">—</span>
            </div>

            <div class="aluno-info-box">
              <span class="dossier-label">Turma</span>
              <span id="professorTurma">—</span>
            </div>

            <div class="aluno-info-box">
              <span class="dossier-label">Gabinete</span>
              <span id="professorGabinete">—</span>
            </div>

            <div class="aluno-info-box">
              <span class="dossier-label">Cargo</span>
              <span id="professorCargo">—</span>
            </div>

            <div class="aluno-info-box">
              <span class="dossier-label">Matéria</span>
              <span id="professorMateria">—</span>
            </div>

            <div class="aluno-info-box">
              <span class="dossier-label">Horário</span>
              <span id="professorHorario">—</span>
            </div>

            <div class="aluno-rfid-box">
              <div class="dossier-label">Informação</div>
              <div class="rfid-help-text">
                Aqui poderá consultar os dados do professor e futuramente editar ou atualizar informações.
              </div>
            </div>
          </div>

          <div class="aluno-dossier-actions">
             <button class="secondary-btn" id="btnBlockProfessor">Bloquear conta</button>
              <button class="secondary-btn" id="btnDeleteProfessor">Eliminar professor</button>
              <button class="secondary-btn" id="professorClose">Fechar</button>
          </div>
        </div>
      </div>

      <div class="admin-tab-content" id="tab-noticias">
        <div class="admin-card">
          <h2>Nova notícia</h2>
          <form id="noticiaForm" class="noticia-form">
            <input type="hidden" id="noticiaId" name="id" value="">
            <div class="form-row">
              <label for="noticiaTitulo">Título</label>
              <input type="text" id="noticiaTitulo" name="titulo" maxlength="200" required>
            </div>
            <div class="form-row">
              <label for="noticiaCorpo">Corpo</label>
              <textarea id="noticiaCorpo" name="corpo" rows="4" required></textarea>
            </div>
            <div class="form-row">
              <label for="noticiaImagem">URL da imagem (opcional)</label>
              <input type="text" id="noticiaImagem" name="imagem" maxlength="300" placeholder="../assets/1_1.jpg ou https://...">
            </div>
            <div class="form-row">
              <label class="checkbox-label">
                <input type="checkbox" id="noticiaAtivo" name="ativo" value="1" checked>
                Ativa (mostrar no carrossel)
              </label>
            </div>
            <div class="form-actions">
              <button type="submit" id="noticiaSubmit">Criar notícia</button>
              <button type="button" id="noticiaCancel" class="secondary-btn" style="display:none;">Cancelar edição</button>
              <div id="noticiaStatus"></div>
            </div>
          </form>
        </div>

        <div class="admin-card">
          <h2>Notícias existentes</h2>
          <div id="noticiasList" class="noticias-admin-list">
            <p class="logs-empty">A carregar...</p>
          </div>
        </div>
      </div>

      <div class="admin-tab-content" id="tab-horario">
        <div class="admin-card">
          <h2>Horário semanal</h2>
          <div class="horario-controls">
            <label for="horarioTurmaSelect">Turma</label>
            <select id="horarioTurmaSelect">
              <?php foreach ([10, 11, 12] as $hNum): foreach (['A', 'B', 'C'] as $hLetra): ?>
                <option value="<?= $hNum . $hLetra ?>"><?= $hNum . $hLetra ?></option>
              <?php endforeach; endforeach; ?>
            </select>
          </div>
          <div class="horario-grid" id="horarioGrid">
            <p class="logs-empty">A carregar...</p>
          </div>
        </div>

        <div class="admin-card">
          <h2 id="horarioFormTitle">Adicionar aula</h2>
          <form id="horarioForm" class="horario-form">
            <input type="hidden" id="horarioId" name="id" value="">
            <div class="form-row">
              <label for="horarioDia">Dia da semana</label>
              <select id="horarioDia" name="dia_semana" required>
                <option value="1">Segunda</option>
                <option value="2">Terça</option>
                <option value="3">Quarta</option>
                <option value="4">Quinta</option>
                <option value="5">Sexta</option>
              </select>
            </div>
            <div class="form-row">
              <label for="horarioInicio">Hora de início</label>
              <input type="time" id="horarioInicio" name="hora_inicio" required>
            </div>
            <div class="form-row">
              <label for="horarioFim">Hora de fim</label>
              <input type="time" id="horarioFim" name="hora_fim" required>
            </div>
            <div class="form-row">
              <label for="horarioMateria">Disciplina</label>
              <input type="text" id="horarioMateria" name="materia" maxlength="100" required>
            </div>
            <div class="form-row">
              <label for="horarioSala">Sala</label>
              <input type="text" id="horarioSala" name="sala" maxlength="50">
            </div>
            <div class="form-row">
              <label for="horarioProf">Professor</label>
              <select id="horarioProf" name="professor_login">
                <option value="">—</option>
              </select>
            </div>
            <div class="form-actions">
              <button type="submit" id="horarioSubmit">Adicionar aula</button>
              <button type="button" id="horarioCancelEdit" class="secondary-btn" style="display:none;">Cancelar edição</button>
              <div id="horarioStatus"></div>
            </div>
          </form>
        </div>
      </div>

    <div class="admin-tab-content" id="tab-suporte">
      <div class="suporte-layout">
        <div class="admin-card suporte-list-card">
          <div class="logs-header">
            <h2>Problemas de acesso</h2>
            <button class="secondary-btn" id="suporteRefresh">Atualizar</button>
          </div>
          <div id="suporteList"><p class="logs-empty">A carregar...</p></div>
          </div>

        <div class="admin-card suporte-detail-card" id="suporteDossier" style="display:none;">
          <div class="suporte-detail-top">
            <h2 id="suporteDossierTitle">Mensagem</h2>
            
          </div>

          <div class="suporte-meta-grid">
            <div class="suporte-meta-box">
              <span class="dossier-label">Data</span>
              <span id="suporteDossierData">—</span>
            </div>

            <div class="suporte-meta-box">
              <span class="dossier-label">Email</span>
              <span id="suporteDossierEmail">—</span>
            </div>

            <div class="suporte-meta-box">
              <span class="dossier-label">Estado</span>
              <span id="suporteDossierEstado">—</span>
            </div>
          </div>

          <div class="suporte-message-section">
            <span class="dossier-label">Mensagem</span>
            <div id="suporteDossierMensagem" class="suporte-message-box"></div>
          </div>

          <div class="suporte-actions">
            <button class="secondary-btn" id="suporteDossierClose">Fechar</button>
            <button class="secondary-btn" id="suporteBtnRead">Marcar lido</button>
            <button class="secondary-btn" id="suporteBtnDelete">Eliminar</button>
          </div>
        </div>
      </div>
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

<script src="/PAP/project/assets/app.js?v=20"></script>
</body>

</html>





