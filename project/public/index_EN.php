<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';

$hora = (int)date('H');
if ($hora >= 6 && $hora < 12)      $saudacao = "Good morning";
elseif ($hora >= 12 && $hora < 19) $saudacao = "Good afternoon";
else                                $saudacao = "Good evening";

if (($_SESSION['lang'] ?? 'pt') === 'pt') {
    header("Location: /PAP/project/public/index.php");
    exit;
}

$noticias = [];
$res = $conn->query("SELECT titulo, corpo, titulo_en, corpo_en, imagem FROM noticias WHERE ativo = 1 ORDER BY criado_em DESC, id DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $noticias[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
  <title>PAP - Main</title>
  <link rel="stylesheet" href="/PAP/project/assets/style.css?v=335">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>

<body class="page-index">

  <header class="topbar">
    <div class="topbar-inner">

      <div class="topbar-left">
        <a href="/PAP/project/public/index.php" class="logo-hover">
          <img src="../assets/aemtg.jpg" alt="Logo" class="school-logo">
          <div class="hover-text">School website</div>
        </a>

        <a href="https://classroom.google.com/" class="logo-hover service-link">
          <img src="../assets/classroom.jpg" alt="Classroom" class="service-logo">
          <div class="hover-text">Classroom</div>
        </a>

        <a href="https://aepombal.unicard.pt" class="logo-hover service-link">
          <img src="../assets/sige.png" alt="SIGE" class="service-logo">
          <div class="hover-text">SIGE</div>
        </a>

        <a href="https://inovar.aemtg.pt/inovaralunos/Inicial.wgx" class="logo-hover service-link">
          <img src="../assets/inovar.png" alt="Inovar" class="service-logo">
          <div class="hover-text">Inovar</div>
        </a>
      </div>

      <div class="topbar-center">
        <?php if (isset($_SESSION['user_id'])): ?>
          <a href="/PAP/project/public/index.php">Home</a>
          <a href="/PAP/api/profile.php">Personal page</a>
        <?php endif; ?>
      </div>

      <div class="topbar-right">
        <?php if (isset($_SESSION['user_id'])): ?>

          <div class="user-status" style="color: black;">
            Logged in as: <?= htmlspecialchars($_SESSION['nome'] ?? '') ?> (<?= htmlspecialchars($_SESSION['role']) ?>)
          </div>

          <div id="logoutBox">
            <a href="/PAP/api/logout.php">Log out</a>
          </div>

        <?php else: ?>

          <div class="auth-buttons">
            <button type="button" class="button" id="openLogin">Log in</button>
            <button type="button" class="button" id="openRegister">Register</button>
          </div>

        <?php endif; ?>
      </div>

    </div>
  </header>

<div class="lang-switcher">
    <a href="/PAP/api/set_lang.php?lang=pt&to=/PAP/project/public/index.php" class="lang-btn" title="Português">
        <img src="/PAP/project/assets/pt.png" alt="PT" class="lang-flag">
    </a>
    <a href="/PAP/api/set_lang.php?lang=en&to=/PAP/project/public/index_EN.php" class="lang-btn lang-active" title="English">
        <img src="/PAP/project/assets/britan.png" alt="EN" class="lang-flag">
    </a>
</div>

  <?php if (!isset($_SESSION['user_id'])): ?>
    <div id="loginModal" class="auth-modal hidden">
      <div class="auth-box">
        <button class="close-modal" type="button" data-close="loginModal">×</button>
        <h2>Log in</h2>

        <form id="loginForm">
          <input type="text" name="login" placeholder="Log in" required>
          <input type="password" name="password" placeholder="Password" required>
          <button type="submit">Log in</button>
          <div id="loginFormStatus">Please enter your login details</div>
          <a href="#" id="openProblem">Need help signing in? Contact the administrator</a>
        </form>
      </div>
    </div>

    <div id="problemModal" class="auth-modal hidden">
      <div class="auth-box">
        <button class="close-modal" type="button" data-close="problemModal">×</button>
        <h2>Contact administrator</h2>
        <form id="problemForm">
          <input type="email" class = "email" name="email" placeholder="Your email">
          <textarea name="mensagem" class = "email" placeholder="Describe the problem..." rows="5" maxlength="2000" required></textarea>
          <button type="submit">Send</button>
          <div id="problemStatus"></div>
        </form>
      </div>
    </div>

    <div id="registerModal" class="auth-modal hidden">
      <div class="auth-box">
        <button class="close-modal" type="button" data-close="registerModal">×</button>
        <h2>Create a student account</h2>

        <form id="registerForm">
          <input type="email" name="login" placeholder="Email" required>
          <input type="password" name="password" id="regPassword" placeholder="Password" required>
          <input type="password" name="password_confirm" id="regPasswordConfirm" placeholder="Confirm password" required>
          <input type="text" name="nome" placeholder="Full name" required>
          <input type="date" name="data_nascimento" required>
        <div style="display: flex; gap: 10px;">
          
          <select name="turma_num" required>
            <option value="">Year</option>
            <option value="10">10</option>
            <option value="11">11</option>
            <option value="12">12</option>
          </select>

          <select name="turma_letra" required>
            <option value="">Class</option>
            <option value="A">A</option>
            <option value="B">B</option>
            <option value="C">C</option>
          </select>

        </div>
        <p>For full functionality, please contact the administrator.</p>
          
          <button type="submit">Create account</button>
          <div id="registerStatus"></div>
      </form>
      </div>
    </div>
  <?php endif; ?>
    
  <main class="page-content">
    <h1 class="main_hello"><?= htmlspecialchars($saudacao) ?>!</h1>
    <h2 class="main_subtitle">Welcome to the school RFID platform</h2>
    <br>
    <p class="main_text">This platform was developed to simplify access to school information and automate attendance registration using RFID technology.</p>
    <p>The system identifies students and teachers through their school card, automatically records entries and exits, and makes this information available in real time.</p>
    <p>From one place, users can check attendance, grades, schedules and other essential information quickly, clearly and securely.</p>
    <p>How does it work?</p>
    <p>The student taps the RFID card on the reader.</p>
    <p>The Arduino sends the UID to the server.</p>
    <p>Attendance is recorded automatically.</p>
    
    <br>
    <?php if (count($noticias) > 0): ?>
    <section class="carousel-container" aria-label="News carousel">
      <div class="carousel" id="carousel">
        <?php foreach ($noticias as $i => $n): ?>
          <?php
            $titulo = $n['titulo_en'] !== null && $n['titulo_en'] !== '' ? $n['titulo_en'] : $n['titulo'];
            $corpo  = $n['corpo_en']  !== null && $n['corpo_en']  !== '' ? $n['corpo_en']  : $n['corpo'];
          ?>
          <article class="slide">
            <?php if (!empty($n['imagem'])): ?>
              <img src="<?= htmlspecialchars($n['imagem']) ?>" alt="<?= htmlspecialchars($titulo) ?>">
            <?php endif; ?>
            <div class="slide-caption">
              <h3><?= htmlspecialchars($titulo) ?></h3>
              <p><?= nl2br(htmlspecialchars($corpo)) ?></p>
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
    <p>Main features:</p>
    <p>• Automatic attendance registration with RFID  </p>
    <p>• Access to grades and academic progress  </p>
    <p>• Access to the school schedule</p>
    <p>• User and card management (admin)</p>
    <p>A simple, efficient, and integrated solution for everyday school life.</p>
  </main>
  <footer class="site-footer">
    <div class="footer-top-line"></div>

    <div class="footer-content">
      <p>Sistema Escolar RFID — PAP 2026</p>
      <p class="footer-projects">
         Marko Nikolaienko
      </p>
      
    </div>
  </footer>
  <script src="/PAP/project/assets/app.js?v=23"></script>
  <?php if (!isset($_SESSION['user_id'])): ?>
  <script>
  (function () {
    async function poll() {
      try {
        const res = await fetch('/PAP/api/card_login_poll.php', { credentials: 'same-origin' });
        const data = await res.json();
        if (data.ok && !data.already_logged) {
          location.reload();
        }
      } catch (e) { /* тихо */ }
    }
    setInterval(poll, 1500);
  })();
  </script>
  <?php endif; ?>
</body>
</html>
