<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['role'] !== 'Aluno') {
    header("Location: ../../api/profile.php");
    exit;
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="/PAP/project/assets/style.css?v=2">
</head>

<body class="page-aluno">
    <header class="topbar">
        <div class="topbar-inner">

            <div class="topbar-left">
                
                <a href="/PAP/project/public/index"><img class="logo" src="../assets/aemtg.jpg" alt="Logo", ></a>
            </div>

            <div class="topbar-center">
                <a href="/PAP/project/public/index.php">Principal</a>
                <a href="/PAP/api/profile.php">Pagina pessoal</a>
                <a href="/PAP/project/public/dashboard">Horario</a>
            </div>

            <div class="topbar-right">
                <h1>Aluno</h1>
            </div>

        </div>
  </header>
  <main>
    
  </main>
</body>
</html>

