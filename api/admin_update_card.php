<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/lib/logger.php';

if (!isset($_SESSION['login']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Acesso negado.'
    ]);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'update') {
    csrf_check();
}

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
    $idade = trim((string)($input['idade'] ?? ''));
    $turma = trim($input['turma'] ?? '');
    $turmaNum = trim((string)($input['turma_num'] ?? ''));
    $turmaLetra = strtoupper(trim($input['turma_letra'] ?? ''));
    $numero_turma = trim((string)($input['numero_turma'] ?? ''));
    $uid = trim($input['uid'] ?? '');
    $password = trim($input['password'] ?? '');

    if ($id === null || $id === '' || $tipo === '' || $login === '') {
        echo json_encode([
            'success' => false,
            'message' => 'ID, tipo ou login em falta.'
        ]);
        exit;
    }

    $mainUpdated = false;
    $uidUpdated = false;
    $passwordUpdated = false;

    if ($tipo === 'aluno') {
        $stmt = $conn->prepare("
            UPDATE alunos
            SET `Nome` = ?, `Idade` = ?, `Turma` = ?, `turma_num` = ?, `turma_letra` = ?, `Número em turma` = ?
            WHERE `ID` = ?
        ");

        if (!$stmt) {
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao preparar update de aluno.'
            ]);
            exit;
        }

        $idadeInt    = ($idade === '') ? 0 : (int)$idade;
        $turmaNumInt = ($turmaNum === '') ? 0 : (int)$turmaNum;
        $numeroInt   = ($numero_turma === '') ? 0 : (int)$numero_turma;
        $idInt       = (int)$id;

        $stmt->bind_param("sisisii", $nome, $idadeInt, $turma, $turmaNumInt, $turmaLetra, $numeroInt, $idInt);

        if (!$stmt->execute()) {
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao executar update de aluno.'
            ]);
            $stmt->close();
            exit;
        }

        $mainUpdated = ($stmt->affected_rows > 0);
        $stmt->close();

    } elseif ($tipo === 'professor') {
        $stmt = $conn->prepare("
            UPDATE professores
            SET `Nome` = ?, `turma` = ?, `turma_num` = ?, `turma_letra` = ?
            WHERE `ID` = ?
        ");

        if (!$stmt) {
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao preparar update de professor.'
            ]);
            exit;
        }

        $turmaNumInt = ($turmaNum === '') ? 0 : (int)$turmaNum;
        $idInt       = (int)$id;

        $stmt->bind_param("ssisi", $nome, $turma, $turmaNumInt, $turmaLetra, $idInt);

        if (!$stmt->execute()) {
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao executar update de professor.'
            ]);
            $stmt->close();
            exit;
        }

        $mainUpdated = ($stmt->affected_rows > 0);
        $stmt->close();

    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Tipo inválido.'
        ]);
        exit;
    }

    $stmtLogin = $conn->prepare("
        UPDATE login
        SET `UID` = ?
        WHERE `Login` = ?
    ");

    if (!$stmtLogin) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao preparar update de UID.'
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

    $uidUpdated = ($stmtLogin->affected_rows > 0);
    $stmtLogin->close();

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
                'message' => 'Erro ao preparar update de password.'
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

        $passwordUpdated = ($stmtPass->affected_rows > 0);
        $stmtPass->close();
    }

    if ($mainUpdated || $uidUpdated || $passwordUpdated) {
        log_event("ADMIN_ACTION", "card_updated", [
            "admin"           => $_SESSION['login'] ?? null,
            "target_login"    => $login,
            "target_tipo"     => $tipo,
            "main_updated"    => $mainUpdated,
            "uid_updated"     => $uidUpdated,
            "password_changed"=> $passwordUpdated,
        ]);
        echo json_encode([
            'success' => true,
            'message' => 'Dados atualizados com sucesso.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Nenhum dado foi alterado.'
        ]);
    }

    exit;
} 

echo json_encode([
    'success' => false,
    'message' => 'Ação inválida.'
]);