<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/session.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Acesso negado.']);
    exit;
}

$sessionFile = __DIR__ . '/../logs/scan_session.json';
$action      = $_GET['action'] ?? '';
$TIMEOUT     = 60;

function readScanSession($file) {
    if (!file_exists($file)) return ['state' => 'idle', 'uid' => null, 'started_at' => 0];
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : ['state' => 'idle', 'uid' => null, 'started_at' => 0];
}

function writeScanSession($file, $data) {
    file_put_contents($file, json_encode($data), LOCK_EX);
}

if ($action === 'start') {
    writeScanSession($sessionFile, [
        'state'      => 'waiting',
        'uid'        => null,
        'started_at' => time(),
    ]);
    echo json_encode(['ok' => true]);
    exit;
}

if ($action === 'poll') {
    $session = readScanSession($sessionFile);

    if ($session['state'] === 'waiting' && (time() - ($session['started_at'] ?? 0)) > $TIMEOUT) {
        writeScanSession($sessionFile, ['state' => 'idle', 'uid' => null, 'started_at' => 0]);
        echo json_encode(['ok' => true, 'state' => 'timeout']);
        exit;
    }

    if ($session['state'] === 'ready') {
        $uid = $session['uid'];
        writeScanSession($sessionFile, ['state' => 'idle', 'uid' => null, 'started_at' => 0]);
        echo json_encode(['ok' => true, 'state' => 'ready', 'uid' => $uid]);
        exit;
    }

    echo json_encode(['ok' => true, 'state' => $session['state'] ?? 'idle']);
    exit;
}

if ($action === 'cancel') {
    writeScanSession($sessionFile, ['state' => 'idle', 'uid' => null, 'started_at' => 0]);
    echo json_encode(['ok' => true]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Ação inválida.']);
