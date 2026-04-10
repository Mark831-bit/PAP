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
  <link rel="stylesheet" href="/PAP/project/assets/style.css?v=300">
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
              <label for="addNumeroTurma">Número na turma</label>
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
            </div>
          </form>
        </div>

        <!-- Atualizar -->
        <div class="admin-card">
          <h2>Atualizar</h2>

          <div class="search-bar">
            <input type="text" placeholder="Procurar pessoa...">
            <button type="button">Find</button>
          </div>

          <div class="search-results">
            <button class="person-result" type="button">Marko Nikolaienko</button>
            <button class="person-result" type="button">Professor João</button>
          </div>

          <form class="admin-form" id="updateCardForm">

            <div class="radio-group">
              <label class="radio-option">
                <input type="radio" name="update_role" value="Aluno" checked>
                <span>Aluno</span>
              </label>

              <label class="radio-option">
                <input type="radio" name="update_role" value="Professor">
                <span>Professor</span>
              </label>
            </div>

            <div class="form-row">
              <label>Nome</label>
              <input type="text" placeholder="Nome completo">
            </div>

            <div class="form-row">
              <label>Login</label>
              <input type="text" placeholder="Login">
            </div>

            <div class="form-row">
              <label>Password</label>
              <input type="password" placeholder="Password">
            </div>

            <div class="form-row">
              <label>Idade</label>
              <input type="number" placeholder="Idade">
            </div>

            <div class="form-row turma-row">
              <div>
                <label>Turma</label>
                <select>
                  <option value="">Ano</option>
                  <option value="10">10</option>
                  <option value="11">11</option>
                  <option value="12">12</option>
                </select>
              </div>

              <div>
                <label>&nbsp;</label>
                <select>
                  <option value="">Letra</option>
                  <option value="A">A</option>
                  <option value="B">B</option>
                  <option value="C">C</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <label>Número na turma</label>
              <input type="number" placeholder="Número">
            </div>

            <div class="form-row uid-row">
              <div class="uid-input-wrap">
                <label>UID</label>
                <input type="text" placeholder="UID do cartão">
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
              <button type="submit">Atualizar</button>
            </div>
          </form>
        </div>

        <!-- Encontrar -->
        <div class="admin-card">
          <h2>Encontrar</h2>

          <div class="search-bar">
            <input type="text" placeholder="Procurar pessoa...">
            <button type="button">Find</button>
          </div>

          <div class="search-results">
            <button class="person-result" type="button">Marko Nikolaienko</button>
            <button class="person-result" type="button">Professor João</button>
          </div>

          <form class="admin-form">

            <div class="radio-group">
              <label class="radio-option">
                <input type="radio" checked disabled>
                <span>Aluno</span>
              </label>

              <label class="radio-option">
                <input type="radio" disabled>
                <span>Professor</span>
              </label>
            </div>

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

  <script>
    const tabs = document.querySelectorAll('.admin-tab');
    const contents = document.querySelectorAll('.admin-tab-content');

    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        tabs.forEach(t => t.classList.remove('active'));
        contents.forEach(c => c.classList.remove('active'));

        tab.classList.add('active');
        document.getElementById('tab-' + tab.dataset.tab).classList.add('active');
      });
    });
  </script>

</body>
</html>




<script>
document.getElementById('saveLoginForm').addEventListener('submit', async function(e) {
  e.preventDefault();

  const formData = new FormData(this);

  const response = await fetch('/PAP/api/save_login.php', {
    method: 'POST',
    body: formData
  });

  const result = await response.json();
  alert(JSON.stringify(result));
});
</script>