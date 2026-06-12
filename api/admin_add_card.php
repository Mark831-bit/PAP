<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/lib/logger.php';
require_once __DIR__ . '/lib/validators.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode([
        'ok' => false,
        'error' => 'Acesso negado'
    ]);
    exit;
}

csrf_check();

try {
    $role = trim($_POST['role'] ?? '');
    $nome = trim($_POST['nome'] ?? '');
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $dataNascimento = trim($_POST['data_nascimento'] ?? '');
    $turmaNum = trim($_POST['turma_num'] ?? '');
    $turmaLetra = trim($_POST['turma_letra'] ?? '');
    $uid = trim($_POST['uid'] ?? '');
    $cargo    = trim($_POST['cargo']    ?? '');
    $gabinete = trim($_POST['gabinete'] ?? '');
    $horario  = trim($_POST['horario']  ?? '');
    $materia  = trim($_POST['materia']  ?? '');

    $turma = $turmaNum . $turmaLetra;

    if ($role === '' || $nome === '' || $login === '' || $password === '' || $turmaNum === '' || $turmaLetra === '') {
        echo json_encode([
            'ok' => false,
            'error' => 'Preencha os campos obrigatórios'
        ]);
        exit;
    }

    if ($role !== 'Aluno' && $role !== 'Professor') {
        echo json_encode([
            'ok' => false,
            'error' => 'Role inválida'
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

    if ($role === 'Aluno') {
        if ($dataNascimento === '') {
            echo json_encode([
                'ok' => false,
                'error' => 'Para Aluno, Data de nascimento é obrigatória'
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
    }

    $stmtCheck = $pdo->prepare("SELECT Login FROM login WHERE Login = ? LIMIT 1");
    $stmtCheck->execute([$login]);
    if ($stmtCheck->fetch()) {
        echo json_encode([
            'ok' => false,
            'error' => 'Esse login já existe'
        ]);
        exit;
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $pdo->beginTransaction();

    $stmtLogin = $pdo->prepare("
        INSERT INTO login (Login, Password, UID, Role)
        VALUES (?, ?, ?, ?)
    ");
    $stmtLogin->execute([$login, $passwordHash, $uid, $role]);

    if ($role === 'Aluno') {
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
    } else {
        $stmtProfessor = $pdo->prepare("
            INSERT INTO professores (`Nome`, `Cargo (posição)`, `Gabinete`, `Presença`, `Horario`, `Matéria ensinada`, `turma`, `turma_num`, `turma_letra`, `login`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtProfessor->execute([
            $nome,
            $cargo,
            $gabinete,
            0,
            $horario,
            $materia,
            $turma,
            (int)$turmaNum,
            strtoupper($turmaLetra),
            $login
        ]);
    }

    $pdo->commit();

    log_event("ADMIN_ACTION", "user_created", [
        "admin"        => $_SESSION['login'] ?? null,
        "target_login" => $login,
        "target_role"  => $role,
        "has_uid"      => ($uid !== ''),
    ]);

    echo json_encode([
        'ok' => true,
        'message' => $role . ' criado com sucesso'
    ]);
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    log_event("ERROR", "admin_add_card exception", [
        "admin" => $_SESSION['login'] ?? null,
        "msg"   => $e->getMessage(),
    ]);

    echo json_encode([
        'ok' => false,
        'error' => 'Erro interno do servidor.'
    ]);
    exit;
}
