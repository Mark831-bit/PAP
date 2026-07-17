<?php
/**
 * config/session.php — Sessão segura + helpers CSRF
 */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => false,   // mudar para true em HTTPS
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

$timeout = 1800; // 30 minutos

if (isset($_SESSION['LAST_ACTIVITY']) &&
   (time() - $_SESSION['LAST_ACTIVITY'] > $timeout)) {

    session_unset();
    session_destroy();
    session_start();
}

$_SESSION['LAST_ACTIVITY'] = time();

// ---- CSRF helpers ----

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Insere campo oculto CSRF numa form HTML */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="'
         . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">';
}


function csrf_check(): void
{
    $submitted = $_POST['_csrf']
              ?? $_SERVER['HTTP_X_CSRF_TOKEN']
              ?? '';

    if (empty($submitted)) {
        $ct = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($ct, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            if ($raw) {
                $body = json_decode($raw, true);
                if (is_array($body)) {
                    $submitted = $body['_csrf'] ?? '';
                }
            }
        }
    }

    if (empty($submitted) || !hash_equals(csrf_token(), $submitted)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok'      => false,
            'success' => false,
            'error'   => 'csrf_invalid',
            'message' => 'Token CSRF inválido.'
        ]);
        exit;
    }
}
