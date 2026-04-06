<?php
require_once __DIR__ . '/../../config/session.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>PAP - Main</title>
  <link rel="stylesheet" href="/PAP/project/assets/style.css?v=3">
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
          <a href="/PAP/project/public/dashboard">Horario</a>
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
          <input type="text" name="login" placeholder="New login" required>
          <input type="password" name="password" placeholder="New password" required>
          <button type="submit">Criar conta</button>
          <div id="registerStatus"></div>
        </form>
      </div>
    </div>
  <?php endif; ?>

  <main class="page-content">
    <h1 class="main_hello">Bom dia</h1>
    <p class="main_text">Texto muito importante</p>

    <section class="carousel-container" aria-label="News carousel">
      <div class="carousel" id="carousel">

        <article class="slide">
          <img src="../assets/1_1.jpg" alt="Slide 1">
          <div class="slide-caption">
            <h3>Dia Internacional da Educação</h3>
            <p>
              Любой тестовый текст под картинкой. Потом сюда будем подставлять новости из БД.
              Можно 2–3 строки, дальше будет "…"
            </p>
          </div>
        </article>

        <article class="slide">
          <img src="../assets/2_2.jpg" alt="Slide 2">
          <div class="slide-caption">
            <h3>“SaborLeia” — активности в библиотеке</h3>
            <p>
              Пример описания: дата, место, учитель, что происходило. Текст обрезается по строкам.
            </p>
          </div>
        </article>

        <article class="slide">
          <img src="../assets/3_3.jpg" alt="Slide 3">
          <div class="slide-caption">
            <h3>Dia em Memória das Vítimas do Holocausto</h3>
            <p>
              Ещё один пример новости. Позже это можно сделать ссылкой на отдельную страницу.
            </p>
          </div>
        </article>

        <article class="slide">
          <img src="../assets/4_4.jpg" alt="Slide 4">
          <div class="slide-caption">
            <h3>“SaborLeia” — активности в библиотеке</h3>
            <p>
              Пример описания: дата, место, учитель, что происходило. Текст обрезается по строкам.
            </p>
          </div>
        </article>

      </div>

      <button class="nav-btn prev" type="button" aria-label="Previous slide">❮</button>
      <button class="nav-btn next" type="button" aria-label="Next slide">❯</button>
    </section>
  </main>

  <script src="/PAP/project/assets/app.js?v=3"></script>
</body>
</html>
