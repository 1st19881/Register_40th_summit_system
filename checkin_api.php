<?php
header('Content-Type: application/json');
require 'config/config.php'; // ไฟล์เชื่อมต่อ Oracle

$qr = $_POST['qr'] ?? '';

if (empty($qr)) {
    echo json_encode(['status' => 'fail', 'message' => 'ไม่พบข้อมูลจาก QR Code']);
    exit;
}

// 1. ตรวจสอบข้อมูลพนักงานและสถานะปัจจุบันจาก Oracle
$sql_check = "SELECT EMP_NAME, STATUS, PLANT FROM EMP_CHECKIN WHERE QR_CODE = :qr";
$stid_check = oci_parse($conn, $sql_check);
oci_bind_by_name($stid_check, ":qr", $qr);
oci_execute($stid_check);
$row = oci_fetch_array($stid_check, OCI_ASSOC);

// กรณีที่ 1: ไม่พบรหัสพนักงานในฐานข้อมูล
if (!$row) {
    echo json_encode([
        'status' => 'fail', 
        'message' => 'รหัสพนักงานผิด' 
    ]);
    exit;
}

// กรณีที่ 2: เคยเช็คอินเข้างานไปแล้ว (STATUS = DONE)
if ($row['STATUS'] === 'DONE') {
    echo json_encode([
        'status' => 'fail', 
        'message' => 'เช็คอินเข้างานแล้ว',
        'name' => $row['EMP_NAME'] 
    ]);
    exit;
}

// กรณีที่ 3: เช็คอินสำเร็จ (STATUS = PENDING) -> ทำการ Update
$sql_update = "UPDATE EMP_CHECKIN 
               SET STATUS = 'DONE', SCAN_TIME = CURRENT_TIMESTAMP 
               WHERE QR_CODE = :qr AND STATUS = 'PENDING'";
$stid_update = oci_parse($conn, $sql_update);
oci_bind_by_name($stid_update, ":qr", $qr);
$result = oci_execute($stid_update, OCI_COMMIT_ON_SUCCESS);

if ($result) {
    echo json_encode([
        'status' => 'success', 
        'name' => $row['EMP_NAME'],
        'plant' => $row['PLANT']
    ]);
} else {
    echo json_encode([
        'status' => 'fail', 
        'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูลลง Oracle'
    ]);
}

oci_free_statement($stid_check);
oci_free_statement($stid_update);
oci_close($conn);
?>