<?php
require_once 'includes/auth.php';
$_SESSION = [];
if (ini_set("session.use_cookies")) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $p["path"], $p["domain"], $p["secure"], true);
}
session_regenerate_id(true);
session_destroy();
header('Location: index.php');
exit;