<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/lib/logger.php';

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
        $stmt = $conn->prepare("
            SELECT n.id, n.login_aluno, n.materia, n.tipo, n.valor,
                   n.data, n.professor_login, n.observacao, n.criado_em,
                   p.Nome AS professor_nome
            FROM notas n
            LEFT JOIN professores p ON p.login = n.professor_login
            WHERE n.login_aluno = ?
            ORDER BY n.data ASC, n.id ASC
        ");
        $stmt->bind_param("s", $login);
    } else {
        $stmt = $conn->prepare("
            SELECT n.id, n.login_aluno, n.materia, n.tipo, n.valor,
                   n.data, n.professor_login, n.observacao, n.criado_em,
                   a.Nome AS aluno_nome,
                   '' AS aluno_numero,
                   CONCAT(a.turma_num, a.turma_letra) AS aluno_turma
            FROM notas n
            LEFT JOIN alunos a ON a.login = n.login_aluno
            WHERE n.professor_login = ?
            ORDER BY n.data DESC, n.id DESC
        ");
        $stmt->bind_param("s", $login);
    }
    $stmt->execute();
    $res = $stmt->get_result();

    $out = [];
    while ($row = $res->fetch_assoc()) {
        $row['id']    = (int)$row['id'];
        $row['valor'] = (float)$row['valor'];
        $out[] = $row;
    }
    $stmt->close();

    echo json_encode(['ok' => true, 'notas' => $out]);
    exit;
}

if ($action === 'alunos_da_turma') {
    if ($role !== 'Professor') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'forbidden']);
        exit;
    }

    $stmt = $conn->prepare("SELECT turma FROM professores WHERE login = ? LIMIT 1");
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $p = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$p || empty($p['turma'])) {
        echo json_encode(['ok' => true, 'alunos' => []]);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT login, Nome AS nome
        FROM alunos
        WHERE CONCAT(turma_num, turma_letra) = ?
        ORDER BY Nome ASC
    ");
    $stmt->bind_param("s", $p['turma']);
    $stmt->execute();
    $res = $stmt->get_result();

    $alunos = [];
    while ($row = $res->fetch_assoc()) {
        $alunos[] = $row;
    }
    $stmt->close();

    echo json_encode(['ok' => true, 'alunos' => $alunos, 'turma' => $p['turma']]);
    exit;
}

if ($action === 'create') {
    if ($role !== 'Professor') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'forbidden']);
        exit;
    }
    csrf_check();

    $loginAluno = trim($_POST['login_aluno'] ?? '');
    $tipo       = trim($_POST['tipo']        ?? '');
    $valorRaw   = trim($_POST['valor']       ?? '');
    $data       = trim($_POST['data']        ?? '');
    $observacao = trim($_POST['observacao']  ?? '');

    if ($loginAluno === '' || $tipo === '' || $valorRaw === '' || $data === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'missing fields']);
        exit;
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'data inválida']);
        exit;
    }

    $valor = (float)str_replace(',', '.', $valorRaw);
    if ($valor < 0 || $valor > 20) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'valor deve estar entre 0 e 20']);
        exit;
    }

    $stmt = $conn->prepare("SELECT turma FROM professores WHERE login = ? LIMIT 1");
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $p = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$p || empty($p['turma'])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'professor sem turma']);
        exit;
    }

    $stmt = $conn->prepare("SELECT 1 FROM alunos WHERE login = ? AND CONCAT(turma_num, turma_letra) = ? LIMIT 1");
    $stmt->bind_param("ss", $loginAluno, $p['turma']);
    $stmt->execute();
    $exists = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();

    if (!$exists) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'aluno fora da turma']);
        exit;
    }

    $materia = null;
    $stmt = $conn->prepare("SELECT `Matéria ensinada` AS m FROM professores WHERE login = ? LIMIT 1");
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $materia = $r['m'] ?? 'Geral';
    $stmt->close();

    $obsSql = $observacao !== '' ? $observacao : null;

    $ins = $conn->prepare("
        INSERT INTO notas (login_aluno, materia, tipo, valor, data, professor_login, observacao)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $ins->bind_param("sssdsss", $loginAluno, $materia, $tipo, $valor, $data, $login, $obsSql);

    if ($ins->execute()) {
        log_event("INFO", "nota created", [
            "prof"    => $login,
            "aluno"   => $loginAluno,
            "materia" => $materia,
            "valor"   => $valor,
        ]);
        echo json_encode(['ok' => true, 'id' => $ins->insert_id]);
    } else {
        log_event("ERROR", "nota insert failed", ["err" => $ins->error]);
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'db error']);
    }
    $ins->close();
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'invalid action']);
