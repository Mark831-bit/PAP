<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    $users = [];

    $sqlAlunos = "
        SELECT 
            a.`ID` AS id,
            a.`Nome` AS nome,
            a.`Idade` AS idade,
            a.`Turma` AS turma,
            a.`Número em turma` AS numero_turma,
            a.`login` AS login,
            l.`UID` AS uid,
            'aluno' AS tipo
        FROM alunos a
        LEFT JOIN login l ON l.`Login` = a.`login`
    ";

    $resultAlunos = $conn->query($sqlAlunos);
    if ($resultAlunos) {
        while ($row = $resultAlunos->fetch_assoc()) {
            $users[] = $row;
        }
    }

    $sqlProfessores = "
        SELECT 
            p.`ID` AS id,
            p.`Nome` AS nome,
            '' AS idade,
            p.`turma` AS turma,
            '' AS numero_turma,
            p.`login` AS login,
            l.`UID` AS uid,
            'professor' AS tipo
        FROM professores p
        LEFT JOIN login l ON l.`Login` = p.`login`
    ";

    $resultProfessores = $conn->query($sqlProfessores);
    if ($resultProfessores) {
        while ($row = $resultProfessores->fetch_assoc()) {
            $users[] = $row;
        }
    }

    echo json_encode([
        'success' => true,
        'users' => $users
    ]);
    exit;
}

if ($action === 'update') {
    $input = json_decode(file_get_contents('php://input'), true);

    $id = $input['id'] ?? null;
    $tipo = strtolower(trim($input['tipo'] ?? ''));
    $nome = trim($input['nome'] ?? '');
    $login = trim($input['login'] ?? '');
    $idade = trim($input['idade'] ?? '');
    $turma = trim($input['turma'] ?? '');
    $numero_turma = trim($input['numero_turma'] ?? '');
    $uid = trim($input['uid'] ?? '');
    $password = trim($input['password'] ?? '');

    if (!$id || !$tipo || !$login) {
        echo json_encode([
            'success' => false,
            'message' => 'ID, tipo ou login em falta.'
        ]);
        exit;
    }

    if ($tipo === 'aluno') {
        $stmt = $conn->prepare("
            UPDATE alunos
            SET `Nome` = ?, `Idade` = ?, `Turma` = ?, `Número em turma` = ?, `login` = ?
            WHERE `ID` = ?
        ");

        if (!$stmt) {
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao preparar UPDATE de aluno.'
            ]);
            exit;
        }

        $stmt->bind_param("sisssi", $nome, $idade, $turma, $numero_turma, $login, $id);

    } elseif ($tipo === 'professor') {
        $stmt = $conn->prepare("
            UPDATE professores
            SET `Nome` = ?, `turma` = ?, `login` = ?
            WHERE `ID` = ?
        ");

        if (!$stmt) {
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao preparar UPDATE de professor.'
            ]);
            exit;
        }

        $stmt->bind_param("sssi", $nome, $turma, $login, $id);

    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Tipo inválido.'
        ]);
        exit;
    }

    if (!$stmt->execute()) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao atualizar tabela principal.'
        ]);
        $stmt->close();
        exit;
    }
    $stmt->close();

    // обновляем UID в login
    $stmtLogin = $conn->prepare("
        UPDATE login
        SET `UID` = ?
        WHERE `Login` = ?
    ");

    if (!$stmtLogin) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao preparar UPDATE de login/UID.'
        ]);
        exit;
    }

    $stmtLogin->bind_param("ss", $uid, $login);

    if (!$stmtLogin->execute()) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao atualizar UID.'
        ]);
        $stmtLogin->close();
        exit;
    }
    $stmtLogin->close();

    // обновляем пароль только если введён новый
    if ($password !== '') {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmtPass = $conn->prepare("
            UPDATE login
            SET `Password` = ?
            WHERE `Login` = ?
        ");

        if (!$stmtPass) {
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao preparar UPDATE de password.'
            ]);
            exit;
        }

        $stmtPass->bind_param("ss", $passwordHash, $login);

        if (!$stmtPass->execute()) {
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao atualizar password.'
            ]);
            $stmtPass->close();
            exit;
        }

        $stmtPass->close();
    }

    echo json_encode([
        'success' => true
    ]);
    exit;
}

echo json_encode([
    'success' => false,
    'message' => 'Ação inválida.'
]);