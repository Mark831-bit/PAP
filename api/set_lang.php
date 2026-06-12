<?php
require_once __DIR__ . '/../config/session.php';

$lang = $_GET['lang'] ?? 'pt';
if (!in_array($lang, ['pt', 'en'], true)) $lang = 'pt';
$_SESSION['lang'] = $lang;

$allowed = [
    '/PAP/project/public/index.php',
    '/PAP/project/public/index_EN.php',
    '/PAP/project/public/Aluno.php',
    '/PAP/project/public/Aluno_EN.php',
    '/PAP/project/public/Professor.php',
    '/PAP/project/public/Professore_EN.php',
    '/PAP/project/public/admin.php',
    '/PAP/project/public/admin_en.php',
];

$to = $_GET['to'] ?? '/PAP/project/public/index.php';
if (!in_array($to, $allowed, true)) {
    $to = '/PAP/project/public/index.php';
}

header("Location: $to");
exit;
