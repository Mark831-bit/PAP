<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/lib/logger.php';
require_once __DIR__ . '/lib/validators.php';

header('Content-Type: application/json; charset=utf-8');

csrf_check();

try {
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $nome = trim($_POST['nome'] ?? '');
    $dataNascimento = trim($_POST['data_nascimento'] ?? '');
    $turmaNum = trim($_POST['turma_num'] ?? '');
    $turmaLetra = trim($_POST['turma_letra'] ?? '');
    $turma = $turmaNum . $turmaLetra;

    if ($login === '' || $password === '' || $nome === '' || $dataNascimento === '' || $turma === '') {
        echo json_encode([
            'ok' => false,
            'error' => 'Preencha todos os campos'
        ]);
        exit;
    }

    if (!filter_var($login, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'ok' => false,
            'error' => 'Email inválido (deve conter "@")'
        ]);
        exit;
    }

    $dt = DateTime::createFromFormat('Y-m-d', $dataNascimento);
    if (!$dt || $dt->format('Y-m-d') !== $dataNascimento) {
        echo json_encode([
            'ok' => false,
            'error' => 'Data de nascimento inválida'
        ]);
        exit;
    }

    if (!is_valid_turma($turmaNum, $turmaLetra)) {
        echo json_encode([
            'ok' => false,
            'error' => 'Turma inválida'
        ]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT Login FROM login WHERE Login = ? LIMIT 1");
    $stmt->execute([$login]);
    $existingUser = $stmt->fetch();

    if ($existingUser) {
        echo json_encode([
            'ok' => false,
            'error' => 'Esse email já está registado'
        ]);
        exit;
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $role = 'Aluno';
    $uid = '';

    $stmtLogin = $pdo->prepare("
        INSERT INTO login (Login, Password, UID, Role)
        VALUES (?, ?, ?, ?)
    ");
    $stmtLogin->execute([$login, $passwordHash, $uid, $role]);

    $stmtAluno = $pdo->prepare("
        INSERT INTO alunos (`Nome`, `data_nascimento`, `turma_num`, `turma_letra`, `Presença`, `login`)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmtAluno->execute([
        $nome,
        $dataNascimento,
        (int)$turmaNum,
        strtoupper($turmaLetra),
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
