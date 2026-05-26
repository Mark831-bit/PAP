<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'forbidden']);
    exit;
}

// week_offset: 0 = поточна тиждень (останні 7 днів до сьогодні)
//              -1 = попередня (7 днів до тижня тому)
//              +1 = майбутня — не дозволяємо
$weekOffset = (int)($_GET['week_offset'] ?? 0);
if ($weekOffset > 0) $weekOffset = 0;
if ($weekOffset < -52) $weekOffset = -52; // ограничение в год назад

$endDay   = $weekOffset * 7;    // 0 = сьогодні, -7 = тиждень тому, …
$startDay = $endDay - 6;         // 7 днів вікно

$labels = [];
$values = [];
$startDateStr = '';
$endDateStr   = '';

$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT login) AS n
    FROM presencas
    WHERE data = ? AND presenca = 1
");

for ($i = $startDay; $i <= $endDay; $i++) {
    $d = date('Y-m-d', strtotime("$i days"));
    if ($i === $startDay) $startDateStr = $d;
    if ($i === $endDay)   $endDateStr   = $d;

    $labels[] = date('d/m', strtotime($d));

    $count = 0;
    if ($stmt) {
        $stmt->bind_param("s", $d);
        $stmt->execute();
        $count = (int)($stmt->get_result()->fetch_assoc()['n'] ?? 0);
    }
    $values[] = $count;
}
if ($stmt) $stmt->close();

echo json_encode([
    'ok'          => true,
    'labels'      => $labels,
    'values'      => $values,
    'week_offset' => $weekOffset,
    'range'       => date('d/m', strtotime($startDateStr)) . ' – ' . date('d/m', strtotime($endDateStr)),
    'can_next'    => $weekOffset < 0,
    'can_prev'    => $weekOffset > -52,
]);
