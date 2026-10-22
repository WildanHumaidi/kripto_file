<?php
session_start();
require_once '../config.php';
require_once 'audit_helper.php';

if (!empty($_SESSION['username'])) {
    log_activity($connect, $_SESSION['username'], 'LOGOUT', 'Pengguna keluar dari sistem');
}

$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();
header('location: ../index.php');
exit();
?>