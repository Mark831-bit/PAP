<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/lib/logger.php';
require_once __DIR__ . '/lib/validators.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['login']) || !in_array($_SESSION['role'] ?? '', ['Aluno', 'Professor'], true)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'forbidden']);
    exit;
}

$role   = $_SESSION['role'];
$login  = $_SESSION['login'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'list') {
    if ($role === 'Aluno') {
        $stmt = $conn->prepare("SELECT turma_num, turma_letra FROM alunos WHERE login = ? LIMIT 1");
        $stmt->bind_param("s", $login);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$r) { echo json_encode(['ok' => true, 'sumarios' => []]); exit; }

        $tNum   = (int)$r['turma_num'];
        $tLetra = $r['turma_letra'];

        $stmt = $conn->prepare("
            SELECT s.id, s.professor_login, s.turma_num, s.turma_letra,
                   s.data, s.materia, s.descricao, s.criado_em,
                   p.Nome AS professor_nome
            FROM sumarios s
            LEFT JOIN professores p ON p.login = s.professor_login
            WHERE s.turma_num = ? AND s.turma_letra = ?
            ORDER BY s.data DESC, s.id DESC
        ");
        $stmt->bind_param("is", $tNum, $tLetra);
    } else {
        $stmt = $conn->prepare("
            SELECT s.id, s.professor_login, s.turma_num, s.turma_letra,
                   s.data, s.materia, s.descricao, s.criado_em,
                   p.Nome AS professor_nome
            FROM sumarios s
            LEFT JOIN professores p ON p.login = s.professor_login
            WHERE s.professor_login = ?
            ORDER BY s.data DESC, s.id DESC
        ");
        $stmt->bind_param("s", $login);
    }

    $stmt->execute();
    $res = $stmt->get_result();

    $sumarios = [];
    while ($row = $res->fetch_assoc()) {
        $sumarios[] = [
            'id'              => (int)$row['id'],
            'professor_login' => $row['professor_login'],
            'professor_nome'  => $row['professor_nome'],
            'turma_num'       => (int)$row['turma_num'],
            'turma_letra'     => $row['turma_letra'],
            'data'            => $row['data'],
            'materia'         => $row['materia'],
            'descricao'       => $row['descricao'],
            'criado_em'       => $row['criado_em'],
        ];
    }
    $stmt->close();

    echo json_encode(['ok' => true, 'sumarios' => $sumarios]);
    exit;
}

if ($action === 'create') {
    if ($role !== 'Professor') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'forbidden']);
        exit;
    }
    csrf_check();

    $turmaNum   = trim($_POST['turma_num'] ?? '');
    $turmaLetra = strtoupper(trim($_POST['turma_letra'] ?? ''));
    $data       = trim($_POST['data'] ?? '');
    $descricao  = trim($_POST['descricao'] ?? '');

    if ($turmaNum === '' || $turmaLetra === '' || $data === '' || $descricao === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'missing fields']);
        exit;
    }
    if (!is_valid_turma($turmaNum, $turmaLetra)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'invalid turma']);
        exit;
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'invalid data']);
        exit;
    }

    $materia = null;
    $stmt = $conn->prepare("SELECT `Matéria ensinada` AS m FROM professores WHERE login = ? LIMIT 1");
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $materia = $r['m'] ?? null;
    $stmt->close();

    $turmaNumInt = (int)$turmaNum;

    $ins = $conn->prepare("
        INSERT INTO sumarios (professor_login, turma_num, turma_letra, data, materia, descricao)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $ins->bind_param("sissss", $login, $turmaNumInt, $turmaLetra, $data, $materia, $descricao);

    if ($ins->execute()) {
        log_event("INFO", "sumario created", [
            "professor" => $login,
            "turma"     => $turmaNum . $turmaLetra,
            "data"      => $data,
        ]);
        echo json_encode(['ok' => true, 'id' => $ins->insert_id]);
    } else {
        log_event("ERROR", "sumario insert failed", ["err" => $ins->error]);
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'db error']);
    }
    $ins->close();
    exit;
}

if ($action === 'delete') {
    if ($role !== 'Professor') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'forbidden']);
        exit;
    }
    csrf_check();

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'id required']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM sumarios WHERE id = ? AND professor_login = ?");
    $stmt->bind_param("is", $id, $login);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    if ($affected === 0) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'not found']);
        exit;
    }

    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'invalid action']);
