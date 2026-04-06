<?php
require_once __DIR__ . '/../config/db.php';
session_start();

header('Content-Type: application/json');

$login = trim($_POST['login'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($login === '' || $password === '') {
    echo json_encode(['ok' => false, 'error' => 'Заполните логин и пароль']);
    exit;
}

$stmt = $conn->prepare("SELECT Login FROM login WHERE Login = ?");
$stmt->bind_param("s", $login);
$stmt->execute();
$res = $stmt->get_result();

if ($res->fetch_assoc()) {
    echo json_encode(['ok' => false, 'error' => 'Такой логин уже существует']);
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$role = 'Aluno';
$uid = '';

$stmt = $conn->prepare("INSERT INTO login (Login, Password, UID, Role) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $login, $hash, $uid, $role);

if ($stmt->execute()) {
    echo json_encode(['ok' => true, 'message' => 'Пользователь создан']);
} else {
    echo json_encode(['ok' => false, 'error' => 'Ошибка регистрации']);
}