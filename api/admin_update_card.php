<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/lib/logger.php';
require_once __DIR__ . '/lib/validators.php';

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
            a.`data_nascimento` AS data_nascimento,
            CONCAT(a.`turma_num`, a.`turma_letra`) AS turma,
            a.`turma_num` AS turma_num,
            a.`turma_letra` AS turma_letra,
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
            '' AS data_nascimento,
            CONCAT(p.turma_num, p.turma_letra) AS turma,
            p.`turma_num` AS turma_num,
            p.`turma_letra` AS turma_letra,
            p.`login` AS login,
            l.`UID` AS uid,
            'professor' AS tipo,
            IFNULL((SELECT GROUP_CONCAT(CONCAT(pt.turma_num, pt.turma_letra) ORDER BY pt.turma_num, pt.turma_letra SEPARATOR ',')
                    FROM professor_turmas pt WHERE pt.professor_login = p.login), '') AS turmas_str
        FROM professores p
        LEFT JOIN login l ON l.`Login` = p.`login`
    ";

    $resultProfessores = $conn->query($sqlProfessores);
    if ($resultProfessores) {
        while ($row = $resultProfessores->fetch_assoc()) {
            $row['turmas'] = $row['turmas_str'] ? explode(',', $row['turmas_str']) : [];
            unset($row['turmas_str']);
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
    $dataNascimento = trim((string)($input['data_nascimento'] ?? ''));
    $turma = trim($input['turma'] ?? '');
    $turmaNum = trim((string)($input['turma_num'] ?? ''));
    $turmaLetra = strtoupper(trim($input['turma_letra'] ?? ''));
    $uid = trim($input['uid'] ?? '');
    $password = trim($input['password'] ?? '');

    if ($id === null || $id === '' || $tipo === '' || $login === '') {
        echo json_encode([
            'success' => false,
            'message' => 'ID, tipo ou login em falta.'
        ]);
        exit;
    }

    if ($turmaNum !== '' && !is_valid_turma_num($turmaNum)) {
        echo json_encode([
            'success' => false,
            'message' => 'turma_num inválido.'
        ]);
        exit;
    }
    if ($turmaLetra !== '' && !is_valid_turma_letra($turmaLetra)) {
        echo json_encode([
            'success' => false,
            'message' => 'turma_letra inválida.'
        ]);
        exit;
    }

    if ($tipo === 'aluno' && $dataNascimento !== '') {
        $dt = DateTime::createFromFormat('Y-m-d', $dataNascimento);
        if (!$dt || $dt->format('Y-m-d') !== $dataNascimento) {
            echo json_encode([
                'success' => false,
                'message' => 'Data de nascimento inválida.'
            ]);
            exit;
        }
    }

    $mainUpdated = false;
    $uidUpdated = false;
    $passwordUpdated = false;

    if ($tipo === 'aluno') {
        $stmt = $conn->prepare("
            UPDATE alunos
            SET `Nome` = ?, `data_nascimento` = ?, `turma_num` = ?, `turma_letra` = ?
            WHERE `ID` = ?
        ");

        if (!$stmt) {
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao preparar update de aluno.'
            ]);
            exit;
        }

        $turmaNumInt = ($turmaNum === '') ? 0 : (int)$turmaNum;
        $idInt       = (int)$id;

        $stmt->bind_param("ssisi", $nome, $dataNascimento, $turmaNumInt, $turmaLetra, $idInt);

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

        // Sync professor_turmas junction table
        $turmasInput = $input['turmas'] ?? null;
        if (is_array($turmasInput)) {
            $validTurmas = [];
            foreach ($turmasInput as $t) {
                $t = trim($t);
                if (preg_match('/^(10|11|12)[ABC]$/', $t)) {
                    $validTurmas[] = $t;
                }
            }
            $stmtDelPT = $conn->prepare("DELETE FROM professor_turmas WHERE professor_login = ?");
            $stmtDelPT->bind_param("s", $login);
            $stmtDelPT->execute();
            $stmtDelPT->close();

            if (count($validTurmas) > 0) {
                $stmtInsPT = $conn->prepare("INSERT IGNORE INTO professor_turmas (professor_login, turma_num, turma_letra) VALUES (?, ?, ?)");
                foreach ($validTurmas as $t) {
                    $tNum   = (int)substr($t, 0, 2);
                    $tLetra = substr($t, 2);
                    $stmtInsPT->bind_param("sis", $login, $tNum, $tLetra);
                    $stmtInsPT->execute();
                }
                $stmtInsPT->close();

                // Update legacy single-turma fields with first turma
                $first = $validTurmas[0];
                $fNum   = (int)substr($first, 0, 2);
                $fLetra = substr($first, 2);
                $stmtLeg = $conn->prepare("UPDATE professores SET `turma` = ?, `turma_num` = ?, `turma_letra` = ? WHERE `login` = ?");
                $stmtLeg->bind_param("siss", $first, $fNum, $fLetra, $login);
                $stmtLeg->execute();
                $stmtLeg->close();
            }
        }

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
            "admin"            => $_SESSION['login'] ?? null,
            "target_login"     => $login,
            "target_tipo"      => $tipo,
            "main_updated"     => $mainUpdated,
            "uid_updated"      => $uidUpdated,
            "password_changed" => $passwordUpdated,
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
