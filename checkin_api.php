<?php
header('Content-Type: application/json');

try {
    require 'config/config.php';

    $qr = $_POST['qr'] ?? '';

    if (empty($qr)) {
        echo json_encode(['status' => 'fail', 'message' => 'ไม่พบข้อมูล QR Code']);
        exit;
    }

    // 1. ตรวจสอบข้อมูลพนักงานจาก Oracle
    $sql_check = "SELECT EMP_NAME, STATUS, PLANT, TABLE_CODE, TABLE_NO, IS_ATTENDED FROM EMP_CHECKIN WHERE QR_CODE = :qr";
    $stid_check = oci_parse($conn, $sql_check);
    oci_bind_by_name($stid_check, ":qr", $qr);
    oci_execute($stid_check);
    $row = oci_fetch_array($stid_check, OCI_ASSOC);

    // กรณี 1: ไม่พบข้อมูลพนักงาน
    if (!$row) {
        oci_free_statement($stid_check);
        oci_close($conn);
        echo json_encode([
            'status' => 'fail',
            'message' => 'ไม่พบข้อมูล'
        ]);
        exit;
    }

    // ตรวจสอบแสดงเลขโต๊ะ: ถ้า IS_ATTENDED=Y แต่ TABLE_CODE และ TABLE_NO ว่าง → แสดง "ผู้บริหาร"
    $is_attended = ($row['IS_ATTENDED'] ?? 'N');
    $table_code_val = trim($row['TABLE_CODE'] ?? '');
    $table_no_val = trim($row['TABLE_NO'] ?? '');
    if ($is_attended === 'Y' && empty($table_code_val) && empty($table_no_val)) {
        $display_table = 'ผู้บริหาร';
    } else {
        $display_table = !empty($table_code_val) ? $table_code_val : '-';
    }

    // กรณี 2: เช็คอินแล้ว (STATUS = DONE)
    if ($row['STATUS'] === 'DONE') {
        oci_free_statement($stid_check);
        oci_close($conn);
        echo json_encode([
            'status' => 'already',
            'message' => 'เช็คอินแล้ว',
            'name' => $row['EMP_NAME'],
            'plant' => $row['PLANT'],
            'table_code' => $display_table
        ]);
        exit;
    }

    // กรณี 3: รอเช็คอิน (STATUS = PENDING) -> ทำการ Update
    // เพิ่ม IS_ATTENDED = 'Y' เพื่อ นับเข้างานในแดชบอร์ด
    $sql_update = "UPDATE EMP_CHECKIN 
                   SET STATUS = 'DONE', SCAN_TIME = CURRENT_TIMESTAMP, IS_ATTENDED = 'Y' 
                   WHERE QR_CODE = :qr AND STATUS = 'PENDING'";
    $stid_update = oci_parse($conn, $sql_update);
    oci_bind_by_name($stid_update, ":qr", $qr);
    $result = oci_execute($stid_update, OCI_DEFAULT);

    if ($result) {
        // บันทึกลงตาราง employees สำหรับจับรางวัล
        // ใช้ MERGE เพื่อไม่ให้เกิดข้อมูลซ้ำ (ถ้ามีอยู่แล้วก็ไม่ทำอะไร)
        $sql_employee = "MERGE INTO employees e
                         USING (SELECT :emp_id AS emp_id, :emp_name AS emp_name, :plant AS plant FROM DUAL) src
                         ON (e.emp_id = src.emp_id)
                         WHEN NOT MATCHED THEN
                           INSERT (emp_id, emp_name, plant, is_drawn)
                           VALUES (src.emp_id, src.emp_name, src.plant, 0)";
        $stid_employee = oci_parse($conn, $sql_employee);
        $emp_id_from_qr = $qr; // QR_CODE = EMP_ID
        oci_bind_by_name($stid_employee, ":emp_id", $emp_id_from_qr);
        oci_bind_by_name($stid_employee, ":emp_name", $row['EMP_NAME']);
        oci_bind_by_name($stid_employee, ":plant", $row['PLANT']);
        $emp_insert_result = oci_execute($stid_employee, OCI_DEFAULT);

        if ($emp_insert_result) {
            oci_commit($conn);
            oci_free_statement($stid_employee);
            oci_free_statement($stid_check);
            oci_free_statement($stid_update);
            oci_close($conn);

            echo json_encode([
                'status' => 'success',
                'name' => $row['EMP_NAME'],
                'plant' => $row['PLANT'],
                'table_code' => $display_table
            ]);
        } else {
            $e = oci_error($stid_employee);
            oci_rollback($conn);
            oci_free_statement($stid_employee);
            oci_free_statement($stid_check);
            oci_free_statement($stid_update);
            oci_close($conn);

            echo json_encode([
                'status' => 'fail',
                'message' => 'ไม่สามารถบันทึกข้อมูลได้: ' . ($e['message'] ?? 'Unknown error')
            ]);
        }
    } else {
        $e = oci_error($stid_update);
        oci_free_statement($stid_check);
        oci_free_statement($stid_update);
        oci_close($conn);

        echo json_encode([
            'status' => 'fail',
            'message' => 'เกิดข้อผิดพลาดในฐานข้อมูล Oracle: ' . ($e['message'] ?? 'Unknown error')
        ]);
    }
} catch (Exception $ex) {
    echo json_encode([
        'status' => 'fail',
        'message' => 'Server Error: ' . $ex->getMessage()
    ]);
}
