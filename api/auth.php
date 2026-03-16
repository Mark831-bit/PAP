<?php
session_start();

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/lib/logger.php';

$login = $_POST['login'] ?? '';
$password = $_POST['password'] ?? '';

log_event("INFO","login attempt",["login"=>$login]);

$stmt = $pdo->prepare("SELECT * FROM login WHERE Login = ? LIMIT 1");
$stmt->execute([$login]);

$user = $stmt->fetch();

if(!$user){
    log_event("WARN","login not found",["login"=>$login]);
    header("Location: /PAP/project/public/login.php");
    exit;
}

if($user['Password'] !== $password){
    log_event("WARN","wrong password",["login"=>$login]);
    header("Location: /PAP/project/public/login.php");
    exit;
}

$_SESSION['user_id'] = $user['UID'];
$_SESSION['role'] = $user['Role'];
$_SESSION['login'] = $user['Login'];

log_event("INFO","login success",[
 "login"=>$user['Login'],
 "role"=>$user['Role']
]);

header("Location: /PAP/api/profile.php");
exit;