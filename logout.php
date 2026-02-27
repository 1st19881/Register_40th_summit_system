<?php
session_start();

// ลบ session ทั้งหมด
$_SESSION = [];

// ลบ cookie session
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

// ลบ remember me cookies
setcookie('remember_token', '', time() - 3600, '/');
setcookie('remember_user', '', time() - 3600, '/');

// ทำลาย session
session_destroy();

// Redirect ไปหน้า login
header('Location: login.php');
exit;
