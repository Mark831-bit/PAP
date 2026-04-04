<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['role'] !== 'Professor') {
    header("Location: ../../api/profile.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/PAP/project/assets/style.css?v=2">
    <title>Document</title>
</head>
<body class = "page-professor">
     <header class="topbar">
    <div class="topbar-inner">

      <div class="topbar-left">
        
        <a href="/PAP/project/public/index"><img class="logo" src="../assets/aemtg.jpg" alt="Logo", ></a>
      </div>

      <div class="topbar-center">
        <a href="/PAP/project/public/">Principal</a>
        <a href="/PAP/api/profile.php">Pagina pessoal</a>
        <a href="/PAP/project/public/dashboard">Horario</a>
      </div>

      <div class="topbar-right">
       <?php if (isset($_SESSION['user_id'])): ?>
        <div id="loginStatus" style="color: green;">
            Вы вошли как: <?= htmlspecialchars($_SESSION['login']) ?> (<?= htmlspecialchars($_SESSION['role']) ?>)
            
        </div>

    <div id="logoutBox" style="display: block;">
        
        <a href="/PAP/api/logout.php">Logout</a>
    </div>

    <?php else: ?>
          <form id="loginForm">
              <input name="login" placeholder="Login" required>
              <input type="password" name="password" placeholder="Password" required>
              <button type="submit">Login</button>
              <div id="loginStatus">Вы не вошли в систему</div>
          </form>

          <div id="logoutBox" style="display: none;">
             
              <a href="/PAP/api/logout.php">Logout</a>
          </div>
          <?php endif; ?>

        </div>

       

        </form>
      </div>

    </div>
  </header>
</body>
</html>