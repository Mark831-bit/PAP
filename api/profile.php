<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['role'] === 'student') {
    header("Location: student.php");
    exit;
}

if ($_SESSION['role'] === 'teacher') {
    header("Location: prof.php");
    exit;
}

if ($_SESSION['role'] === 'admin') {
    header("Location: admin.php");
    exit;
}

// если роль неизвестна
session_destroy();
header("Location: login.php");
exit;
?>