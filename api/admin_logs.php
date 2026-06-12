<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/session.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit;
}

$logsDir = __DIR__ . '/../logs/';
$entries = [];

if (is_dir($logsDir)) {
    $files = glob($logsDir . '*.log');
    rsort($files); // файлы от новых к старым

    foreach ($files as $file) {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$lines) continue;

        foreach (array_reverse($lines) as $line) {
            $data = json_decode($line, true);
            if ($data) {
                $entries[] = $data;
            }
        }
    }
}

echo json_encode(['success' => true, 'entries' => $entries]);
