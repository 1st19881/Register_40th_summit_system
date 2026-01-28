<?php
// ไฟล์นี้จะทำหน้าที่ดึงรูปจาก API มาไว้ที่ Server เราเองชั่วคราวเพื่อให้เซฟรูปได้
$url = $_GET['url'] ?? '';
if ($url) {
    header('Content-Type: image/png');
    echo file_get_contents($url);
}
