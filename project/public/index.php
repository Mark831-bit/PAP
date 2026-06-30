<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';

$hora = (int)date('H');
if ($hora >= 6 && $hora < 12)      $saudacao = "Bom dia";
elseif ($hora >= 12 && $hora < 19) $saudacao = "Boa tarde";
else                                $saudacao = "Boa noite";

if (($_SESSION['lang'] ?? 'pt') === 'en') {
    header("Location: /PAP/project/public/index_EN.php");
    exit;
}

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
  <link rel="stylesheet" href="/PAP/project/assets/style.css?v=335">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>

<body class="page-index">

  <header class="topbar">
    <div class="topbar-inner">

      <div class="topbar-left">
        <a href="/PAP/project/public/index.php" class="logo-hover">
          <img src="../assets/aemtg.jpg" alt="Logo" class="school-logo">
          <div class="hover-text">Website da escola</div>
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
          <a href="/PAP/project/public/index.php">Principal</a>
          <a href="/PAP/api/profile.php">Página pessoal</a>
        <?php endif; ?>
      </div>

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
            <button type="button" class="button" id="openLogin">Entrar</button>
            <button type="button" class="button" id="openRegister">Registar</button>
          </div>

        <?php endif; ?>
      </div>

    </div>
  </header>

<div class="lang-switcher">
    <a href="/PAP/api/set_lang.php?lang=pt&to=/PAP/project/public/index.php" class="lang-btn lang-active" title="Português">
        <img src="/PAP/project/assets/pt.png" alt="PT" class="lang-flag">
    </a>
    <a href="/PAP/api/set_lang.php?lang=en&to=/PAP/project/public/index_EN.php" class="lang-btn" title="English">
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
          <input type="password" name="password" placeholder="Palavra-passe" required>
          <button type="submit">Entrar</button>
          <div id="loginFormStatus">Sessão não iniciada</div>
          <a href="#" id="openProblem">Problemas com acesso? Contacte o administrador</a>
        </form>
      </div>
    </div>

    <div id="problemModal" class="auth-modal hidden">
      <div class="auth-box">
        <button class="close-modal" type="button" data-close="problemModal">×</button>
        <h2>Contactar administrador</h2>
        <form id="problemForm">
          <input type="email" class = "email" name="email" placeholder="O seu email">
          <textarea name="mensagem" class = "email" placeholder="Descreva o problema..." rows="5" maxlength="2000" required></textarea>
          <button type="submit">Enviar</button>
          <div id="problemStatus"></div>
        </form>
      </div>
    </div>

    <div id="registerModal" class="auth-modal hidden">
      <div class="auth-box">
        <button class="close-modal" type="button" data-close="registerModal">×</button>
        <h2>Criar conta de aluno</h2>

        <form id="registerForm">
          <input type="email" name="login" placeholder="Email" required>
          <input type="password" name="password" id="regPassword" placeholder="Palavra-passe" required>
          <input type="password" name="password_confirm" id="regPasswordConfirm" placeholder="Confirmar palavra-passe" required>
          <input type="text" name="nome" placeholder="Nome completo" required>
          <input type="date" id="dataNascimento" name="data_nascimento" required>
        <div style="display: flex; gap: 10px;">
          
         <select name="turma_num" required>
            <option value="" selected disabled>Ano</option>
            <option value="10">10</option>
            <option value="11">11</option>
            <option value="12">12</option>
          </select>

          <select name="turma_letra" required>
            <option value="" selected disabled>Turma</option>
            <option value="A">A</option>
            <option value="B">B</option>
            <option value="C">C</option>
          </select>
          

        </div>

        <p>Para funcionalidade completa, por favor contacte o administrador.</p>
          
          <button type="submit">Criar conta</button>
          <div id="registerStatus"></div>
      </form>
      </div>
    </div>
  <?php endif; ?>
    
  <main class="page-content">
    <h1 class="main_hello"><?= htmlspecialchars($saudacao) ?>!</h1>
    <h2 class="main_subtitle">Bem-vindo à plataforma RFID escolar</h2>
    <br>
    <p class="main_text">Esta plataforma foi desenvolvida com o objetivo de simplificar o acesso à informação escolar e automatizar o registo de presenças através da tecnologia RFID.</p>
    <p>O sistema permite identificar alunos e professores por meio do cartão escolar, registando automaticamente entradas e saídas, e disponibilizando essa informação em tempo real.</p>
    <p>Num único ambiente, os utilizadores podem consultar presenças, avaliações, horários e outras funcionalidades essenciais de forma rápida, organizada e segura.</p>
    <p>Como funciona?</p>
    <p>O aluno aproxima o cartão RFID.</p>
    <p>O Arduino envia o UID para o servidor.</p>
    <p>A presença é registada automaticamente.</p>
    
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
    <p>Funcionalidades principais:</p>
    <p>• Registo automático de presenças com RFID  </p>
    <p>• Consulta de avaliações e progresso académico  </p>
    <p>• Acesso ao horário escolar</p>
    <p>• Gestão de utilizadores e cartões (admin)</p>
    <p>Uma solução simples, eficiente e integrada para o dia a dia escolar.</p>
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
  <script src="/PAP/project/assets/app.js?v=25"></script>
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

