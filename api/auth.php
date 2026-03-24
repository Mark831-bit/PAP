<?php
/*
session_start();

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/lib/logger.php';

header('Content-Type: application/json; charset=utf-8');

$login = trim($_POST['login'] ?? '');
$password = trim($_POST['password'] ?? '');

log_event("INFO", "login attempt", ["login" => $login]);

$stmt = $pdo->prepare("SELECT * FROM login WHERE Login = ? LIMIT 1");
$stmt->execute([$login]);
$user = $stmt->fetch();

if (!$user) {
    log_event("WARN", "login not found", ["login" => $login]);
    echo json_encode([
        "ok" => false,
        "error" => "login not found"
    ]);
    exit;
}

if ($user['Password'] !== $password) {
    log_event("WARN", "wrong password", ["login" => $login]);
    echo json_encode([
        "ok" => false,
        "error" => "wrong password"
    ]);
    exit;
}

$_SESSION['user_id'] = $user['UID'];
$_SESSION['role'] = $user['Role'];
$_SESSION['login'] = $user['Login'];

log_event("INFO", "login success", [
    "login" => $user['Login'],
    "role" => $user['Role']
]);

echo json_encode([
    "ok" => true,
    "login" => $user['Login'],
    "role" => $user['Role']
]);
exit;*/


session_start();

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/lib/logger.php';

header('Content-Type: application/json; charset=utf-8');

$login = trim($_POST['login'] ?? '');
$password = trim($_POST['password'] ?? '');

log_event("INFO", "login attempt", ["login" => $login]);

$stmt = $pdo->prepare("SELECT * FROM login WHERE Login = ? LIMIT 1");
$stmt->execute([$login]);
$user = $stmt->fetch();

if (!$user) {
    log_event("WARN", "login not found", ["login" => $login]);
    echo json_encode([
        "ok" => false,
        "error" => "login not found"
    ]);
    exit;
}

if ($user['Password'] !== $password) {
    log_event("WARN", "wrong password", ["login" => $login]);
    echo json_encode([
        "ok" => false,
        "error" => "wrong password"
    ]);
    exit;
}

$_SESSION['user_id'] = $user['UID'];
$_SESSION['role'] = $user['Role'];
$_SESSION['login'] = $user['Login'];

log_event("INFO", "login success", [
    "login" => $user['Login'],
    "role" => $user['Role']
]);

echo json_encode([
    "ok" => true,
    "login" => $user['Login'],
    "role" => $user['Role']
]);
exit;