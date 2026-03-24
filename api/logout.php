// <?php
/* session_start();

$_SESSION = [];

session_destroy();


require_once __DIR__.'/lib/logger.php';

log_event("INFO","user logout",[
  "user"=>$_SESSION['login'] ?? null
]);

header("Location: /PAP/project/public/index.php");
exit;
 */

session_start();

require_once __DIR__.'/lib/logger.php';

log_event("INFO", "user logout", [
    "user" => $_SESSION['login'] ?? null
]);

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

header("Location: /PAP/project/public/index.php");
exit;