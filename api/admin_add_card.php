<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode([
        'ok' => false,
        'error' => 'Acesso negado'
    ]);
    exit;
}

try {
    $role = trim($_POST['role'] ?? '');
    $nome = trim($_POST['nome'] ?? '');
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $idade = trim($_POST['idade'] ?? '');
    $turmaNum = trim($_POST['turma_num'] ?? '');
    $turmaLetra = trim($_POST['turma_letra'] ?? '');
    $numeroTurma = trim($_POST['numero_turma'] ?? '');
    $uid = trim($_POST['uid'] ?? '');

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
        if ($idade === '' || $numeroTurma === '') {
            $pdo->rollBack();
            echo json_encode([
                'ok' => false,
                'error' => 'Para Aluno, Idade e Número na turma são obrigatórios'
            ]);
            exit;
        }

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
    } else {
        $stmtProfessor = $pdo->prepare("
            INSERT INTO professores (`Nome`, `Cargo (posição)`, `Gabinete`, `Presença`, `Horario`, `Matéria ensinada`, `turma`, `login`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtProfessor->execute([
            $nome,
            '',
            '',
            1,
            '',
            '',
            $turma,
            $login
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'ok' => true,
        'message' => $role . ' criado com sucesso'
    ]);
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}