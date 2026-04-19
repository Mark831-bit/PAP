<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';

$noticias = [];
$res = $conn->query("SELECT titulo, corpo, imagem FROM noticias WHERE ativo = 1 ORDER BY criado_em DESC, id DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $noticias[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
  <title>PAP - Main</title>
  <link rel="stylesheet" href="/PAP/project/assets/style.css?v=5">
</head>

<body class="page-index">

  <header class="topbar">
    <div class="topbar-inner">

      <div class="topbar-left">
        <a href="/PAP/project/public/index.php">
          <img class="logo" src="../assets/aemtg.jpg" alt="Logo">
        </a>
      </div>

      <div class="topbar-center">
        <?php if (isset($_SESSION['user_id'])): ?>
          <a href="/PAP/project/public/index.php">Principal</a>
          <a href="/PAP/api/profile.php">Pagina pessoal</a>
          <a href="/PAP/project/public/admin.php">Horario</a>
        <?php endif; ?>
      </div>

      <div class="topbar-right">
        <?php if (isset($_SESSION['user_id'])): ?>

          <div class="user-status" style="color: green;">
            Вы вошли как: <?= htmlspecialchars($_SESSION['login']) ?> (<?= htmlspecialchars($_SESSION['role']) ?>)
          </div>

          <div id="logoutBox">
            <a href="/PAP/api/logout.php">Logout</a>
          </div>

        <?php else: ?>

          <div class="auth-buttons">
            <button type="button" id="openLogin">Login</button>
            <button type="button" id="openRegister">Register</button>
          </div>

        <?php endif; ?>
      </div>

    </div>
  </header>

  <?php if (!isset($_SESSION['user_id'])): ?>
    <div id="loginModal" class="auth-modal hidden">
      <div class="auth-box">
        <button class="close-modal" type="button" data-close="loginModal">×</button>
        <h2>Login</h2>

        <form id="loginForm">
          <input type="text" name="login" placeholder="Login" required>
          <input type="password" name="password" placeholder="Password" required>
          <button type="submit">Entrar</button>
          <div id="loginFormStatus">Вы не вошли в систему</div>
        </form>
      </div>
    </div>

    <div id="registerModal" class="auth-modal hidden">
      <div class="auth-box">
        <button class="close-modal" type="button" data-close="registerModal">×</button>
        <h2>Register</h2>

        <form id="registerForm">
          <input type="text" name="login" placeholder="Login" required>
          <input type="password" name="password" placeholder="Password" required>
          <input type="text" name="nome" placeholder="Nome" required>
          <input type="number" name="idade" placeholder="Idade" min="1" required>
        <div style="display: flex; gap: 10px;">
          
          <select name="turma_num" required>
            <option value="">Ano</option>
            <option value="10">10</option>
            <option value="11">11</option>
            <option value="12">12</option>
          </select>

          <select name="turma_letra" required>
            <option value="">Turma</option>
            <option value="A">A</option>
            <option value="B">B</option>
            <option value="C">C</option>
          </select>

        </div>
          <input type="number" name="numero_turma" placeholder="Número em turma" min="1" required>
          <button type="submit">Criar conta</button>
          <div id="registerStatus"></div>
      </form>
      </div>
    </div>
  <?php endif; ?>
    
  <main class="page-content">
    <h1 class="main_hello">Bem-vindo à plataforma RFID escolar</h1>
    <br>
    <p class="main_text">É um prazer tê-lo aqui. A nossa plataforma foi criada para tornar a vida escolar
  mais simples e organizada.</p>
  
    <p>Através deste sistema, pode acompanhar a sua presença, aceder ao seu perfil e
  utilizar diferentes funcionalidades de forma rápida e segura.
</p>
    <br>
    <?php if (count($noticias) > 0): ?>
    <section class="carousel-container" aria-label="News carousel">
      <div class="carousel" id="carousel">
        <?php foreach ($noticias as $i => $n): ?>
          <article class="slide">
            <?php if (!empty($n['imagem'])): ?>
              <img src="<?= htmlspecialchars($n['imagem']) ?>" alt="<?= htmlspecialchars($n['titulo']) ?>">
            <?php endif; ?>
            <div class="slide-caption">
              <h3><?= htmlspecialchars($n['titulo']) ?></h3>
              <p><?= nl2br(htmlspecialchars($n['corpo'])) ?></p>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

      <?php if (count($noticias) > 1): ?>
        <button class="nav-btn prev" type="button" aria-label="Previous slide">❮</button>
        <button class="nav-btn next" type="button" aria-label="Next slide">❯</button>
      <?php endif; ?>
    </section>
    <?php endif; ?>
    <p>Esperamos que tenha uma excelente experiência!</p>
  </main>
  <footer class="site-footer">
    <div class="footer-top-line"></div>

    <div class="footer-content">
      <p>
        <a href="https://classroom.google.com/?pli=1">Classroom</a> | <a href="https://inovar.aemtg.pt/inovaralunos/Inicial.wgx">Inovar</a> | <a href="https://aepombal.unicard.pt">SIGE</a>
      </p>
      <p class="footer-projects">
        Os nossos projetos: <strong>PAP RFID School System</strong>
      </p>
      
    </div>
  </footer>
  <script src="/PAP/project/assets/app.js?v=4"></script>
</body>
</html>



