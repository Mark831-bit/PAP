<?php
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/lib/logger.php';

$API_KEY = "pds_arduino_2026";

$key    = $_POST['key'] ?? '';
$uidRaw = $_POST['uid'] ?? '';

$uid = strtoupper(trim($uidRaw));
$uid = preg_replace('/\s+/', '', $uid);

log_event("INFO", "push request received", [
    "uid" => $uid
]);

if ($key !== $API_KEY) {
    log_event("WARN", "unauthorized push", [
        "uid" => $uid
    ]);

    http_response_code(401);
    echo json_encode([
        "ok" => false,
        "error" => "unauthorized"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($uid === '') {
    log_event("WARN", "uid missing");

    http_response_code(400);
    echo json_encode([
        "ok" => false,
        "error" => "uid required"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$mysqli = new mysqli("localhost", "root", "", "pap");

if ($mysqli->connect_error) {
    log_event("ERROR", "db connect failed", [
        "err" => $mysqli->connect_error
    ]);

    http_response_code(500);
    echo json_encode([
        "ok" => false,
        "error" => "db connect failed"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $mysqli->prepare("
        SELECT `Login`, `Role`, `UID`
        FROM `login`
        WHERE UPPER(REPLACE(`UID`, ' ', '')) = ?
        LIMIT 1
    ");

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $mysqli->error);
    }

    $stmt->bind_param("s", $uid);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        $stmt->close();
        $mysqli->close();

        log_event("INFO", "uid matched", [
            "uid" => $uid,
            "login" => $row['Login'],
            "role" => $row['Role']
        ]);

        echo json_encode([
            "ok" => true,
            "uid" => $uid,
            "login" => $row['Login'],
            "role" => $row['Role']
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt->close();
    $mysqli->close();

    log_event("WARN", "uid not found", [
        "uid" => $uid
    ]);

    http_response_code(404);
    echo json_encode([
        "ok" => false,
        "error" => "uid not found",
        "uid" => $uid
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    log_event("ERROR", "server exception", [
        "msg" => $e->getMessage()
    ]);

    http_response_code(500);
    echo json_encode([
        "ok" => false,
        "error" => "server error"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}