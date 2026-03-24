<?php
require_once __DIR__.'/../../config/session.php';
session_start();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>PAP - Main</title>

  
  <!-- <link rel="stylesheet" href="/PAP/project/assets/style.css?v=dev"> -->
   <link rel="stylesheet" href="/PAP/project/assets/style.css? dev=2">
</head>

<body>

  <!-- HEADER -->
  <header class="topbar">
    <div class="topbar-inner">

      <div class="topbar-left">
        
        <a href="http://localhost/PAP/project/public/index"><img class="logo" src="../assets/aemtg.jpg" alt="Logo", ></a>
      </div>

      <div class="topbar-center">
        <a href="http://localhost/PAP/project/public/">Principal</a>
        <a href="http://localhost/PAP/api/profile.php">Pagina pessoal</a>
        <a href="http://localhost/PAP/project/public/dashboard">Horario</a>
      </div>

      <div class="topbar-right">
       <?php if (isset($_SESSION['user_id'])): ?>
    <div id="loginStatus" style="color: green;">
        Вы вошли как: <?= htmlspecialchars($_SESSION['login']) ?> (<?= htmlspecialchars($_SESSION['role']) ?>)
    </div>

    <div id="logoutBox" style="display: block;">
        <a href="/PAP/api/profile.php">Página pessoal</a>
        <a href="/PAP/api/logout.php">Logout</a>
    </div>

    <?php else: ?>
        <div id="loginStatus" style="color: gray;">Вы не вошли в систему</div>

          <form id="loginForm">
              <input name="login" placeholder="Login" required>
              <input type="password" name="password" placeholder="Password" required>
              <button type="submit">Login</button>
          </form>

          <div id="logoutBox" style="display: none;">
              <a href="/PAP/api/profile.php">Página pessoal</a>
              <a href="/PAP/api/logout.php">Logout</a>
          </div>
          <?php endif; ?>

        </div>

       

        </form>
      </div>

    </div>
  </header>

  <!-- MAIN -->
  <main class="page-content">
    <h1 class="main_hello">Bom dia</h1>
    <p class="main_text">Texto muito importante</p>

    <!-- CAROUSEL -->
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

      <!-- arrows -->
      <button class="nav-btn prev" type="button" aria-label="Previous slide">❮</button>
      <button class="nav-btn next" type="button" aria-label="Next slide">❯</button>
    </section>

  </main>

  <script src="/PAP/project/assets/app.js?v=dev"></script>
</body>
</html>


<!-- 

<script>
window.addEventListener("beforeunload", function () {
    navigator.sendBeacon("/PAP/api/logout.php");
});
</script>

 -->

