<?php
session_start();

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $uid = trim($_POST['uid'] ?? '');
    $role = trim($_POST['role'] ?? '');

    if ($login === '') {
        echo json_encode([
            'ok' => false,
            'error' => 'Login is required'
        ]);
        exit;
    }

    if ($uid === '') {
        echo json_encode([
            'ok' => false,
            'error' => 'UID is required'
        ]);
        exit;
    }

    if ($role === '') {
        echo json_encode([
            'ok' => false,
            'error' => 'Role is required'
        ]);
        exit;
    }

    // Проверяем, есть ли уже такой логин
    $stmt = $pdo->prepare("SELECT * FROM login WHERE Login = ? LIMIT 1");
    $stmt->execute([$login]);
    $existingUser = $stmt->fetch();

    if ($existingUser) {
        // UPDATE существующего пользователя

        if ($password !== '') {
            // Если пароль ввели — обновляем и хешируем
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                UPDATE login
                SET Password = ?, UID = ?, Role = ?
                WHERE Login = ?
            ");
            $stmt->execute([$passwordHash, $uid, $role, $login]);
        } else {
            // Если пароль пустой — не трогаем его
            $stmt = $pdo->prepare("
                UPDATE login
                SET UID = ?, Role = ?
                WHERE Login = ?
            ");
            $stmt->execute([$uid, $role, $login]);
        }

        echo json_encode([
            'ok' => true,
            'action' => 'updated',
            'login' => $login
        ]);
        exit;
    } else {
        // INSERT нового пользователя

        if ($password === '') {
            echo json_encode([
                'ok' => false,
                'error' => 'Password is required for new user'
            ]);
            exit;
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO login (Login, Password, UID, Role)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$login, $passwordHash, $uid, $role]);

        echo json_encode([
            'ok' => true,
            'action' => 'inserted',
            'login' => $login
        ]);
        exit;
    }

} catch (Throwable $e) {
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}