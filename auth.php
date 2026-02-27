<?php

/**
 * Authentication Guard
 * Include ไฟล์นี้ที่หัวของหน้าที่ต้องการป้องกัน (ต้อง login ก่อนเข้า)
 * 
 * หน้าที่ไม่ต้อง login: scan.php, check_table.php
 * หน้าที่ต้อง login: index.php, dashboard.php, upload_employee.php,
 *                      manage_prizes.php, lucky_draw.php, register.php,
 *                      re_print.php, view_old_ticket.php, get_qr.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ตรวจสอบ session
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    // เก็บ URL ปัจจุบันเพื่อ redirect กลับหลัง login
    $_SESSION['redirect_after_login'] = basename($_SERVER['PHP_SELF']);

    // Redirect ไปหน้า login
    header('Location: login.php');
    exit;
}
