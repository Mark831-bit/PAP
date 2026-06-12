<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/lib/logger.php';
require_once __DIR__ . '/lib/validators.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['role'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'forbidden']);
    exit;
}

$role   = $_SESSION['role'];
$login  = $_SESSION['login'] ?? '';
$action = $_GET['action'] ?? $_POST['action'] ?? '';

function fetch_horario_turma(mysqli $conn, int $turmaNum, string $turmaLetra): array {
    $stmt = $conn->prepare("
        SELECT h.id, h.turma_num, h.turma_letra, h.dia_semana,
               h.hora_inicio, h.hora_fim, h.materia, h.sala, h.professor_login,
               p.Nome AS professor_nome
        FROM horario h
        LEFT JOIN professores p ON p.login = h.professor_login
        WHERE h.turma_num = ? AND h.turma_letra = ?
        ORDER BY h.dia_semana ASC, h.hora_inicio ASC
    ");
    $stmt->bind_param("is", $turmaNum, $turmaLetra);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    $stmt->close();
    return $rows;
}

if ($action === 'list') {
    if ($role === 'admin') {
        $turmaNum   = $_GET['turma_num'] ?? '';
        $turmaLetra = strtoupper(trim($_GET['turma_letra'] ?? ''));
        if (!is_valid_turma($turmaNum, $turmaLetra)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'invalid turma']);
            exit;
        }
        echo json_encode(['ok' => true, 'aulas' => fetch_horario_turma($conn, (int)$turmaNum, $turmaLetra)]);
        exit;
    }

    if ($role === 'Aluno') {
        $stmt = $conn->prepare("SELECT turma_num, turma_letra FROM alunos WHERE login = ? LIMIT 1");
        $stmt->bind_param("s", $login);
        $stmt->execute();
        $aluno = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$aluno || $aluno['turma_num'] === null || $aluno['turma_letra'] === null) {
            echo json_encode(['ok' => true, 'aulas' => []]);
            exit;
        }
        echo json_encode(['ok' => true, 'aulas' => fetch_horario_turma($conn, (int)$aluno['turma_num'], $aluno['turma_letra'])]);
        exit;
    }

    if ($role === 'Professor') {
        $stmt = $conn->prepare("
            SELECT id, turma_num, turma_letra, dia_semana, hora_inicio, hora_fim, materia, sala
            FROM horario
            WHERE professor_login = ?
            ORDER BY dia_semana ASC, hora_inicio ASC
        ");
        $stmt->bind_param("s", $login);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        $stmt->close();
        echo json_encode(['ok' => true, 'aulas' => $rows]);
        exit;
    }

    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'forbidden']);
    exit;
}

if ($action === 'professores') {
    if ($role !== 'admin') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'forbidden']);
        exit;
    }
    $res = $conn->query("SELECT login, Nome AS nome FROM professores ORDER BY Nome ASC");
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    echo json_encode(['ok' => true, 'professores' => $rows]);
    exit;
}

if (in_array($action, ['create', 'update', 'delete'], true)) {
    if ($role !== 'admin') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'forbidden']);
        exit;
    }
    csrf_check();

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'id required']);
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM horario WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        log_event("INFO", "horario deleted", ["id" => $id]);
        echo json_encode(['ok' => true]);
        exit;
    }

    $turmaNum   = $_POST['turma_num'] ?? '';
    $turmaLetra = strtoupper(trim($_POST['turma_letra'] ?? ''));
    $diaSemana  = $_POST['dia_semana'] ?? '';
    $horaInicio = trim($_POST['hora_inicio'] ?? '');
    $horaFim    = trim($_POST['hora_fim'] ?? '');
    $materia    = trim($_POST['materia'] ?? '');
    $sala       = trim($_POST['sala'] ?? '');
    $profLogin  = trim($_POST['professor_login'] ?? '');

    if (!is_valid_turma($turmaNum, $turmaLetra)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'invalid turma']);
        exit;
    }
    if (!is_valid_dia_semana($diaSemana)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'invalid dia_semana']);
        exit;
    }
    if ($materia === '' || !is_valid_hora($horaInicio) || !is_valid_hora($horaFim)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'missing or invalid fields']);
        exit;
    }
    if ($horaInicio >= $horaFim) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'hora_inicio must be before hora_fim']);
        exit;
    }

    if ($profLogin !== '') {
        $chk = $conn->prepare("SELECT 1 FROM professores WHERE login = ? LIMIT 1");
        $chk->bind_param("s", $profLogin);
        $chk->execute();
        $exists = $chk->get_result()->fetch_assoc();
        $chk->close();
        if (!$exists) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'invalid professor']);
            exit;
        }
    }

    $turmaNumInt   = (int)$turmaNum;
    $diaSemanaInt  = (int)$diaSemana;
    $profLoginParam = $profLogin !== '' ? $profLogin : null;
    $salaParam      = $sala !== '' ? $sala : null;

    if ($action === 'create') {
        $stmt = $conn->prepare("
            INSERT INTO horario (turma_num, turma_letra, dia_semana, hora_inicio, hora_fim, materia, sala, professor_login)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("isisssss", $turmaNumInt, $turmaLetra, $diaSemanaInt, $horaInicio, $horaFim, $materia, $salaParam, $profLoginParam);
        $stmt->execute();
        $newId = $stmt->insert_id;
        $stmt->close();

        log_event("INFO", "horario created", ["turma" => $turmaNumInt . $turmaLetra, "dia" => $diaSemanaInt, "materia" => $materia]);
        echo json_encode(['ok' => true, 'id' => $newId]);
        exit;
    }

    // update
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'id required']);
        exit;
    }
    $stmt = $conn->prepare("
        UPDATE horario
        SET turma_num = ?, turma_letra = ?, dia_semana = ?, hora_inicio = ?, hora_fim = ?, materia = ?, sala = ?, professor_login = ?
        WHERE id = ?
    ");
    $stmt->bind_param("isisssssi", $turmaNumInt, $turmaLetra, $diaSemanaInt, $horaInicio, $horaFim, $materia, $salaParam, $profLoginParam, $id);
    $stmt->execute();
    $stmt->close();

    log_event("INFO", "horario updated", ["id" => $id]);
    echo json_encode(['ok' => true]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'unknown action']);
