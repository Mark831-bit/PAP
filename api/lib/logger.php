<?php
function log_event(string $level, string $message, array $context = []): void
{
    $logDir = __DIR__ . "/../../logs";
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }

    $file = $logDir . "/app-" . date("Y-m-d") . ".log";

    if (isset($context["key"])) $context["key"] = "****";
    if (isset($context["password"])) $context["password"] = "****";

    $line = json_encode([
        "time" => date("Y-m-d H:i:s"),
        "level" => $level,
        "ip" => $_SERVER["REMOTE_ADDR"] ?? null,
        "uri" => $_SERVER["REQUEST_URI"] ?? null,
        "msg" => $message,
        "ctx" => $context
    ], JSON_UNESCAPED_UNICODE);

    $ok = file_put_contents($file, $line . PHP_EOL, FILE_APPEND);

    if ($ok === false) {
        // покажет ошибку в браузере (для теста)
        echo "LOGGER WRITE FAILED to: " . $file;
    }
}