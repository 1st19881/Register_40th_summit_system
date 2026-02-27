<?php
header('Content-Type: application/json');

try {
    require 'config/config.php';

    $emp_id = $_POST['emp_id'] ?? '';

    if (empty($emp_id)) {
        echo json_encode(['status' => 'fail', 'message' => 'กรุณากรอกรหัสพนักงาน']);
        exit;
    }

    // ค้นหาข้อมูลพนักงานจาก Oracle
    $sql = "SELECT EMP_NAME, STATUS, PLANT, TABLE_CODE, TABLE_NO, IS_ATTENDED, QR_CODE 
            FROM EMP_CHECKIN 
            WHERE QR_CODE = :emp_id";
    $stid = oci_parse($conn, $sql);
    oci_bind_by_name($stid, ":emp_id", $emp_id);
    oci_execute($stid);
    $row = oci_fetch_array($stid, OCI_ASSOC);

    if (!$row) {
        oci_free_statement($stid);
        oci_close($conn);
        echo json_encode([
            'status' => 'fail',
            'message' => 'ไม่พบข้อมูลพนักงาน กรุณาตรวจสอบรหัสอีกครั้ง'
        ]);
        exit;
    }

    $is_checked_in = ($row['STATUS'] === 'DONE');

    // ตรวจสอบแสดงเลขโต๊ะ: ถ้า IS_ATTENDED=Y แต่ TABLE_CODE และ TABLE_NO ว่าง → แสดง "ผู้บริหาร"
    $is_attended = ($row['IS_ATTENDED'] ?? 'N');
    $table_code_val = trim($row['TABLE_CODE'] ?? '');
    $table_no_val = trim($row['TABLE_NO'] ?? '');
    if ($is_attended === 'Y' && empty($table_code_val) && empty($table_no_val)) {
        $display_table = 'ผู้บริหาร';
    } else {
        $display_table = !empty($table_code_val) ? $table_code_val : '-';
    }

    oci_free_statement($stid);
    oci_close($conn);

    echo json_encode([
        'status' => 'success',
        'name' => $row['EMP_NAME'],
        'plant' => $row['PLANT'],
        'table_code' => $display_table,
        'is_checked_in' => $is_checked_in
    ]);
} catch (Exception $ex) {
    echo json_encode([
        'status' => 'fail',
        'message' => 'Server Error: ' . $ex->getMessage()
    ]);
}
