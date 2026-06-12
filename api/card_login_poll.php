<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/lib/logger.php';

header('Content-Type: application/json; charset=utf-8');

// Уже залогинен — сразу ок
if (isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => true, 'already_logged' => true, 'role' => $_SESSION['role'] ?? '']);
    exit;
}

$pendingFile = __DIR__ . '/../logs/pending_login.json';

if (!file_exists($pendingFile)) {
    echo json_encode(['ok' => false, 'waiting' => true]);
    exit;
}

$raw  = file_get_contents($pendingFile);
$data = json_decode($raw, true);

$ttl = 15; // секунд
if (!is_array($data) || (time() - ((int)($data['created_at'] ?? 0))) > $ttl) {
    echo json_encode(['ok' => false, 'waiting' => true]);
    exit;
}

$login = $data['login'] ?? '';
$role  = $data['role']  ?? '';

if ($login === '' || $role === '') {
    echo json_encode(['ok' => false, 'waiting' => true]);
    exit;
}

// Одноразовый: удаляем файл после использования
@unlink($pendingFile);

// Создаём сессию
session_regenerate_id(true);

$stmt = $pdo->prepare("SELECT UID FROM login WHERE Login = ? LIMIT 1");
$stmt->execute([$login]);
$row = $stmt->fetch();

$_SESSION['user_id'] = $login;
$_SESSION['login']   = $login;
$_SESSION['role']    = $role;

// Подтягиваем Nome по роли
$nome = $login;
if ($role === 'Aluno') {
    $q = $pdo->prepare("SELECT Nome FROM alunos WHERE login = ? LIMIT 1");
    $q->execute([$login]);
    $r = $q->fetch();
    if ($r && !empty($r['Nome'])) $nome = $r['Nome'];
} elseif ($role === 'Professor') {
    $q = $pdo->prepare("SELECT Nome FROM professores WHERE login = ? LIMIT 1");
    $q->execute([$login]);
    $r = $q->fetch();
    if ($r && !empty($r['Nome'])) $nome = $r['Nome'];
}
$_SESSION['nome'] = $nome;

log_event("INFO", "card login", ["login" => $login, "role" => $role]);

echo json_encode([
    'ok'    => true,
    'login' => $login,
    'role'  => $role,
    'nome'  => $nome,
]);
