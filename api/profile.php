<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /PAP/project/public/index.php");
    exit;
}

if ($_SESSION['role'] === 'Aluno') {
    header("Location: /PAP/project/public/Aluno.php");
    exit;
}

if ($_SESSION['role'] === 'Professor') {
    header("Location: /PAP/project/public/Professor.php");
    exit;
}

if ($_SESSION['role'] === 'admin') {
    header("Location: /PAP/project/public/admin.php");
    exit;
}

// если роль неизвестна
session_destroy();
header("Location: /PAP/project/public/index.php");
exit;