<?php
header('Content-Type: application/json; charset=utf-8');

// Важно: не выводим warning/notice в ответ (иначе Arduino получит HTML)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Логгер
require_once __DIR__ . '/lib/logger.php';

// Конфиг
$API_KEY = "pds_arduino_2026";

// Входные данные (UID-only; device_id опционально)
$key      = $_POST['key'] ?? '';
$uidRaw   = $_POST['uid'] ?? '';
$deviceId = $_POST['device_id'] ?? null;

// Нормализуем UID: убираем пробелы, делаем верхний регистр
$uid = strtoupper(preg_replace('/\s+/', '', trim($uidRaw)));

log_event("INFO", "push request", [
  "uid" => $uid,
  "device_id" => $deviceId
]);

// Проверки
if ($key !== $API_KEY) {
  log_event("WARN", "unauthorized", ["uid" => $uid, "device_id" => $deviceId]);
  http_response_code(401);
  echo json_encode(["ok" => false, "error" => "unauthorized"], JSON_UNESCAPED_UNICODE);
  exit;
}

if ($uid === '') {
  log_event("WARN", "uid missing", ["device_id" => $deviceId]);
  http_response_code(400);
  echo json_encode(["ok" => false, "error" => "uid required"], JSON_UNESCAPED_UNICODE);
  exit;
}

// DB (лучше через общий файл, но можно оставить так)
$mysqli = new mysqli("localhost", "root", "", "pap");
if ($mysqli->connect_error) {
  log_event("ERROR", "db connect failed", ["err" => $mysqli->connect_error]);
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "db connect failed"], JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  // 1) Ищем в alunos (у тебя поле UID)
  $stmt = $mysqli->prepare("SELECT ID, Nome, Turma FROM alunos WHERE UPPER(UID) = ? LIMIT 1");
  $stmt->bind_param("s", $uid);
  $stmt->execute();
  $res = $stmt->get_result();

  if ($row = $res->fetch_assoc()) {
    $stmt->close();

    // отмечаем присутствие
    $upd = $mysqli->prepare("UPDATE alunos SET `Presença` = IF(`Presença` = 1, 0, 1) WHERE ID = ?");
    $upd->bind_param("i", $row['ID']);
    $upd->execute();
    $upd->close();

    log_event("INFO", "match aluno", ["id" => (int)$row['ID'], "nome" => $row['Nome'], "uid" => $uid]);

    echo json_encode([
      "ok" => true,
      "type" => "aluno",
      "id" => (int)$row['ID'],
      "nome" => $row['Nome'],
      "turma" => $row['Turma'],
      "uid" => $uid
    ], JSON_UNESCAPED_UNICODE);
    $mysqli->close();
    exit;
  }
  $stmt->close();

  // 2) Ищем в professores (у тебя поле UID)
  $stmt = $mysqli->prepare("SELECT ID, Nome, `Cargo (posição)` AS Cargo, Gabinete FROM professores WHERE UPPER(UID) = ? LIMIT 1");
  $stmt->bind_param("s", $uid);
  $stmt->execute();
  $res = $stmt->get_result();

  if ($row = $res->fetch_assoc()) {
    $stmt->close();

    $upd = $mysqli->prepare("UPDATE professores SET `Presença` = IF(`Presença` = 1, 0, 1) WHERE ID = ?");
    $upd->bind_param("i", $row['ID']);
    $upd->execute();
    $upd->close();

    log_event("INFO", "match professor", ["id" => (int)$row['ID'], "nome" => $row['Nome'], "uid" => $uid]);

    echo json_encode([
      "ok" => true,
      "type" => "professor",
      "id" => (int)$row['ID'],
      "nome" => $row['Nome'],
      "cargo" => $row['Cargo'],
      "gabinete" => $row['Gabinete'],
      "uid" => $uid
    ], JSON_UNESCAPED_UNICODE);
    $mysqli->close();
    exit;
  }
  $stmt->close();

  // 3) Не нашли UID
  log_event("WARN", "uid not found", ["uid" => $uid, "device_id" => $deviceId]);
  http_response_code(404);
  echo json_encode(["ok" => false, "error" => "uid not found", "uid" => $uid], JSON_UNESCAPED_UNICODE);
  $mysqli->close();
  exit;

} catch (Throwable $e) {
  log_event("ERROR", "exception", ["msg" => $e->getMessage()]);
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "server error"], JSON_UNESCAPED_UNICODE);
  $mysqli->close();
  exit;
}