<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/lib/logger.php';

header('Content-Type: application/json; charset=utf-8');

csrf_check();

try {
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $nome = trim($_POST['nome'] ?? '');
    $idade = trim($_POST['idade'] ?? '');
    $turmaNum = trim($_POST['turma_num'] ?? '');
    $turmaLetra = trim($_POST['turma_letra'] ?? '');
    $turma = $turmaNum . $turmaLetra;
    $numeroTurma = trim($_POST['numero_turma'] ?? '');

    if ($login === '' || $password === '' || $nome === '' || $idade === '' || $turma === '' || $numeroTurma === '') {
        echo json_encode([
            'ok' => false,
            'error' => 'Preencha todos os campos'
        ]);
        exit;
    }

    if (!is_numeric($idade) || (int)$idade <= 0) {
        echo json_encode([
            'ok' => false,
            'error' => 'Idade inválida'
        ]);
        exit;
    }

    if (!is_numeric($numeroTurma) || (int)$numeroTurma <= 0) {
        echo json_encode([
            'ok' => false,
            'error' => 'Número em turma inválido'
        ]);
        exit;
    }

    /* Проверка: логин уже существует? */
    $stmt = $pdo->prepare("SELECT Login FROM login WHERE Login = ? LIMIT 1");
    $stmt->execute([$login]);
    $existingUser = $stmt->fetch();

    if ($existingUser) {
        echo json_encode([
            'ok' => false,
            'error' => 'Esse login já existe'
        ]);
        exit;
    }

    /* Хешируем пароль */
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    /* По умолчанию регистрируем как Aluno */
    $role = 'Aluno';
    $uid = '';

    /* 1. Сохраняем в login */
    $stmtLogin = $pdo->prepare("
        INSERT INTO login (Login, Password, UID, Role)
        VALUES (?, ?, ?, ?)
    ");
    $stmtLogin->execute([$login, $passwordHash, $uid, $role]);

    /* 2. Сохраняем в alunos */
    $stmtAluno = $pdo->prepare("
        INSERT INTO alunos (`Nome`, `Idade`, `Turma`, `Número em turma`, `Presença`, `login`)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmtAluno->execute([
        $nome,
        (int)$idade,
        $turma,
        (int)$numeroTurma,
        0,
        $login
    ]);

    session_regenerate_id(true);
    $_SESSION['user_id'] = $login;
    $_SESSION['login'] = $login;
    $_SESSION['role'] = 'Aluno';

    echo json_encode([
        'ok' => true,
        'message' => 'Conta criada com sucesso'
    ]);
    exit;

} catch (Throwable $e) {
    log_event("ERROR", "register exception", [
        "msg" => $e->getMessage()
    ]);
    echo json_encode([
        'ok' => false,
        'error' => 'Erro interno do servidor.'
    ]);
    exit;
}
?>