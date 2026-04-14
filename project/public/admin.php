<?php


require_once __DIR__ . '/../../config/session.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /PAP/project/public/index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Panel</title>
  <link rel="stylesheet" href="/PAP/project/assets/style.css?v=301">
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
        <button class="admin-tab active" data-tab="cards">Cards</button>
        <button class="admin-tab" data-tab="alunos">Alunos</button>
        <button class="admin-tab" data-tab="professores">Professores</button>
        <button class="admin-tab" data-tab="logs">Logs</button>
        <button class="admin-tab" data-tab="options">Options</button>
      </div>

      <!-- CARDS -->
      <div class="admin-tab-content active" id="tab-cards">

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
                <button type="button" class="secondary-btn">Ler cartão</button>
              </div>
            </div>

            <div class="scan-status">
              Aguardando cartão...
            </div>

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

          <div class="form-row">
            <label for="updateUid">UID</label>
            <input type="text" id="updateUid" name="uid">
          </div>

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
            <input type="text" placeholder="Procurar pessoa...">
            <button type="button">Find</button>
          </div>

       

          <form class="admin-form">

            <div class="form-row">
              <label>Nome</label>
              <input type="text" value="Marko Nikolaienko" readonly>
            </div>

            <div class="form-row">
              <label>Login</label>
              <input type="text" value="a12345" readonly>
            </div>

            <div class="form-row">
              <label>Password</label>
              <input type="text" value="********" readonly>
            </div>

            <div class="form-row">
              <label>Idade</label>
              <input type="text" value="18" readonly>
            </div>

            <div class="form-row">
              <label>Turma</label>
              <input type="text" value="12C" readonly>
            </div>

            <div class="form-row">
              <label>Número na turma</label>
              <input type="text" value="14" readonly>
            </div>

            <div class="form-row">
              <label>UID</label>
              <input type="text" value="C329471C" readonly>
            </div>
          </form>
        </div>

      </div>

      <!-- Другие вкладки пока заглушки -->
      <div class="admin-tab-content" id="tab-alunos">
        <div class="admin-card"><h2>Alunos</h2><p>Em desenvolvimento...</p></div>
      </div>

      <div class="admin-tab-content" id="tab-professores">
        <div class="admin-card"><h2>Professores</h2><p>Em desenvolvimento...</p></div>
      </div>

      <div class="admin-tab-content" id="tab-logs">
        <div class="admin-card"><h2>Logs</h2><p>Em desenvolvimento...</p></div>
      </div>

      <div class="admin-tab-content" id="tab-options">
        <div class="admin-card"><h2>Options</h2><p>Em desenvolvimento...</p></div>
      </div>

    </section>
  </main>

<script src="/PAP/project/assets/app.js?v=302"></script>
</body>

</html>





