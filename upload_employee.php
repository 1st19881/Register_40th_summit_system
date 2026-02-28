<?php

/**
 * Employee Upload Page - EMP_CHECKIN Table
 * Summit Auto Body Industry - 40th Anniversary
 */

// Auth check (includes session_start)
require 'auth.php';

// Include SimpleXLSX library for Excel reading
require_once 'lib/SimpleXLSX.php';

use Shuchkin\SimpleXLSX;

// --- Oracle Connection Configuration ---
$db_user = "hrmsit";
$db_pass = "ithrms";
$db_conn_str = "HRMS";

function get_db_connection()
{
    global $db_user, $db_pass, $db_conn_str;
    $conn = oci_connect($db_user, $db_pass, $db_conn_str, 'AL32UTF8');
    if (!$conn) {
        $e = oci_error();
        return null;
    }
    return $conn;
}

// Get message from session (after redirect)
$message = isset($_SESSION['flash_message']) ? $_SESSION['flash_message'] : '';
$messageType = isset($_SESSION['flash_type']) ? $_SESSION['flash_type'] : '';
// Clear flash message after reading
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

// Helper function to set flash message and redirect
function flashRedirect($msg, $type)
{
    $_SESSION['flash_message'] = $msg;
    $_SESSION['flash_type'] = $type;
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Function to insert or update employee data (upsert)
function upsertEmployee($conn, $qr_code, $emp_id, $emp_name, $plant, $status, $is_attended = 'N', $table_code = '', $table_no = '')
{
    // Check if already exists
    $check_sql = "SELECT COUNT(*) AS CNT FROM EMP_CHECKIN WHERE EMP_ID = :emp_id";
    $check_stid = oci_parse($conn, $check_sql);
    oci_bind_by_name($check_stid, ":emp_id", $emp_id);
    oci_execute($check_stid);
    $row = oci_fetch_array($check_stid, OCI_ASSOC);
    oci_free_statement($check_stid);

    if ($row['CNT'] == 0) {
        // INSERT new record
        $insert_sql = "INSERT INTO EMP_CHECKIN (QR_CODE, EMP_ID, EMP_NAME, PLANT, STATUS, IS_ATTENDED, TABLE_CODE, TABLE_NO) VALUES (:qr_code, :emp_id, :emp_name, :plant, :status, :is_attended, :table_code, :table_no)";
        $insert_stid = oci_parse($conn, $insert_sql);
        oci_bind_by_name($insert_stid, ":qr_code", $qr_code);
        oci_bind_by_name($insert_stid, ":emp_id", $emp_id);
        oci_bind_by_name($insert_stid, ":emp_name", $emp_name);
        oci_bind_by_name($insert_stid, ":plant", $plant);
        oci_bind_by_name($insert_stid, ":status", $status);
        oci_bind_by_name($insert_stid, ":is_attended", $is_attended);
        oci_bind_by_name($insert_stid, ":table_code", $table_code);
        oci_bind_by_name($insert_stid, ":table_no", $table_no);

        if (oci_execute($insert_stid, OCI_DEFAULT)) {
            oci_free_statement($insert_stid);
            return ['success' => true, 'action' => 'insert'];
        } else {
            oci_free_statement($insert_stid);
            return ['success' => false, 'error' => "ไม่สามารถเพิ่ม $emp_id"];
        }
    } else {
        // UPDATE existing record
        $update_sql = "UPDATE EMP_CHECKIN SET QR_CODE = :qr_code, EMP_NAME = :emp_name, PLANT = :plant, STATUS = :status, IS_ATTENDED = :is_attended, TABLE_CODE = :table_code, TABLE_NO = :table_no WHERE EMP_ID = :emp_id";
        $update_stid = oci_parse($conn, $update_sql);
        oci_bind_by_name($update_stid, ":qr_code", $qr_code);
        oci_bind_by_name($update_stid, ":emp_id", $emp_id);
        oci_bind_by_name($update_stid, ":emp_name", $emp_name);
        oci_bind_by_name($update_stid, ":plant", $plant);
        oci_bind_by_name($update_stid, ":status", $status);
        oci_bind_by_name($update_stid, ":is_attended", $is_attended);
        oci_bind_by_name($update_stid, ":table_code", $table_code);
        oci_bind_by_name($update_stid, ":table_no", $table_no);

        if (oci_execute($update_stid, OCI_DEFAULT)) {
            oci_free_statement($update_stid);
            return ['success' => true, 'action' => 'update'];
        } else {
            oci_free_statement($update_stid);
            return ['success' => false, 'error' => "ไม่สามารถอัปเดต $emp_id"];
        }
    }
}

// --- Handle File Upload (Employee Data) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file'];
    $allowedExtensions = ['csv', 'xlsx'];
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($fileExtension, $allowedExtensions)) {
        $message = 'กรุณาอัพโหลดไฟล์ Excel (.xlsx) หรือ CSV เท่านั้น';
        $messageType = 'danger';
    } else {
        $conn = get_db_connection();
        if ($conn) {
            try {
                $insertCount = 0;
                $updateCount = 0;
                $errorCount = 0;
                $errors = [];
                $rows = [];

                // Read file based on extension
                if ($fileExtension === 'xlsx') {
                    // Read Excel file using SimpleXLSX - ALL SHEETS
                    if ($xlsx = SimpleXLSX::parse($file['tmp_name'])) {
                        $sheetNames = $xlsx->sheetNames();

                        // Loop through all sheets
                        foreach ($sheetNames as $sheetIndex => $sheetName) {
                            $sheetRows = $xlsx->rows($sheetIndex);

                            // Skip empty sheets
                            if (empty($sheetRows)) {
                                continue;
                            }

                            // Remove header row (first row of each sheet)
                            array_shift($sheetRows);

                            // Merge rows from this sheet
                            $rows = array_merge($rows, $sheetRows);
                        }
                    } else {
                        throw new Exception('ไม่สามารถอ่านไฟล์ Excel: ' . SimpleXLSX::parseError());
                    }
                } else {
                    // Read CSV file
                    $handle = fopen($file['tmp_name'], 'r');
                    if ($handle) {
                        // Skip header row
                        fgetcsv($handle);
                        while (($data = fgetcsv($handle)) !== false) {
                            $rows[] = $data;
                        }
                        fclose($handle);
                    }
                }

                // Process rows
                foreach ($rows as $data) {
                    if (count($data) >= 4) {
                        $qr_code = trim($data[0]);
                        $emp_id = trim($data[1]);
                        $emp_name = trim($data[2]);
                        $plant = trim($data[3]);
                        $status = isset($data[4]) && !empty(trim($data[4])) ? trim($data[4]) : 'PENDING';
                        $is_attended = isset($data[5]) && !empty(trim($data[5])) ? strtoupper(trim($data[5])) : 'N';
                        $table_code = isset($data[6]) && !empty(trim($data[6])) ? trim($data[6]) : '';
                        $table_no = isset($data[7]) && !empty(trim($data[7])) ? trim($data[7]) : '';

                        if (!empty($qr_code) && !empty($emp_id)) {
                            $result = upsertEmployee($conn, $qr_code, $emp_id, $emp_name, $plant, $status, $is_attended, $table_code, $table_no);
                            if ($result['success']) {
                                if ($result['action'] === 'insert') {
                                    $insertCount++;
                                } else {
                                    $updateCount++;
                                }
                            } else {
                                $errorCount++;
                                $errors[] = $result['error'];
                            }
                        }
                    }
                }

                if ($insertCount > 0 || $updateCount > 0) {
                    oci_commit($conn);
                    $msg = "";
                    if ($insertCount > 0) {
                        $msg .= "เพิ่มใหม่ $insertCount รายการ";
                    }
                    if ($updateCount > 0) {
                        if ($msg) $msg .= ", ";
                        $msg .= "อัปเดต $updateCount รายการ";
                    }
                    if ($errorCount > 0) {
                        $msg .= " (ผิดพลาด $errorCount รายการ)";
                    }
                    oci_close($conn);
                    flashRedirect($msg, 'success');
                } else {
                    $msg = 'ไม่มีข้อมูลใหม่ที่จะนำเข้า';
                    if ($errorCount > 0) {
                        $msg .= " ($errorCount รายการซ้ำหรือผิดพลาด)";
                    }
                    oci_close($conn);
                    flashRedirect($msg, 'warning');
                }
            } catch (Exception $e) {
                oci_rollback($conn);
                oci_close($conn);
                flashRedirect('เกิดข้อผิดพลาด: ' . $e->getMessage(), 'danger');
            }
        } else {
            flashRedirect('ไม่สามารถเชื่อมต่อฐานข้อมูลได้', 'danger');
        }
    }
}

// --- Handle Delete ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_emp_id'])) {
    $conn = get_db_connection();
    if ($conn) {
        $emp_id = trim($_POST['delete_emp_id']);
        $delete_sql = "DELETE FROM EMP_CHECKIN WHERE EMP_ID = :emp_id";
        $delete_stid = oci_parse($conn, $delete_sql);
        oci_bind_by_name($delete_stid, ":emp_id", $emp_id);
        if (oci_execute($delete_stid)) {
            oci_commit($conn);
            oci_free_statement($delete_stid);
            oci_close($conn);
            flashRedirect("ลบข้อมูลพนักงาน $emp_id สำเร็จ", 'success');
        } else {
            oci_free_statement($delete_stid);
            oci_close($conn);
            flashRedirect("ไม่สามารถลบข้อมูลได้", 'danger');
        }
    }
}

// --- Handle Clear All ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_all'])) {
    $conn = get_db_connection();
    if ($conn) {
        $clear_sql = "DELETE FROM EMP_CHECKIN";
        $clear_stid = oci_parse($conn, $clear_sql);
        if (oci_execute($clear_stid)) {
            oci_commit($conn);
            oci_free_statement($clear_stid);
            oci_close($conn);
            flashRedirect("ลบข้อมูลทั้งหมดสำเร็จ", 'success');
        } else {
            oci_free_statement($clear_stid);
            oci_close($conn);
            flashRedirect("ไม่สามารถลบข้อมูลได้", 'danger');
        }
    }
}

// --- Handle Full Reset (For Testing) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['full_reset'])) {
    $conn = get_db_connection();
    if ($conn) {
        try {
            // Step 0: Restore prize quantities (add back won prizes before deleting winners)
            $restore_prizes_sql = "UPDATE prizes p SET prize_qty = prize_qty + (
                SELECT COUNT(*) FROM winners w WHERE w.prize_name = p.prize_name
            )";
            $restore_stid = oci_parse($conn, $restore_prizes_sql);
            oci_execute($restore_stid, OCI_DEFAULT);
            oci_free_statement($restore_stid);

            // Step 1: Delete all winners
            $sql1 = "DELETE FROM winners";
            $stid1 = oci_parse($conn, $sql1);
            oci_execute($stid1, OCI_DEFAULT);
            oci_free_statement($stid1);

            // Step 2: Delete all employees (lucky draw pool)
            $sql2 = "DELETE FROM employees";
            $stid2 = oci_parse($conn, $sql2);
            oci_execute($stid2, OCI_DEFAULT);
            oci_free_statement($stid2);

            // Step 3: Reset EMP_CHECKIN status to PENDING and clear SCAN_TIME
            $sql3 = "UPDATE EMP_CHECKIN SET STATUS = 'PENDING', SCAN_TIME = NULL";
            $stid3 = oci_parse($conn, $sql3);
            oci_execute($stid3, OCI_DEFAULT);
            oci_free_statement($stid3);

            // Step 4: Clear draw_commands (Remote Control commands)
            $sql4 = "DELETE FROM draw_commands";
            $stid4 = oci_parse($conn, $sql4);
            oci_execute($stid4, OCI_DEFAULT);
            oci_free_statement($stid4);

            oci_commit($conn);
            oci_close($conn);
            flashRedirect("Reset ทั้งระบบสำเร็จ! คืนจำนวนรางวัล, ลบผู้ชนะ, ล้าง Remote Commands และพร้อมเริ่มต้นใหม่", 'success');
        } catch (Exception $e) {
            oci_rollback($conn);
            oci_close($conn);
            flashRedirect("เกิดข้อผิดพลาด: " . $e->getMessage(), 'danger');
        }
    }
}

// --- Handle Transfer to Lucky Draw ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['transfer_to_lucky_draw'])) {
    $conn = get_db_connection();
    if ($conn) {
        try {
            $transferCount = 0;
            $skipCount = 0;
            $transferType = isset($_POST['transfer_type']) ? $_POST['transfer_type'] : 'checked';

            // Get employees from EMP_CHECKIN based on transfer type (exclude already DONE)
            if ($transferType === 'all') {
                $select_sql = "SELECT EMP_ID, EMP_NAME, PLANT FROM EMP_CHECKIN WHERE STATUS != 'DONE' AND IS_ATTENDED = 'Y'";
            } else {
                // Only transfer checked-in employees
                $select_sql = "SELECT EMP_ID, EMP_NAME, PLANT FROM EMP_CHECKIN WHERE STATUS = 'CHECKED' AND IS_ATTENDED = 'Y'";
            }

            $select_stid = oci_parse($conn, $select_sql);
            oci_execute($select_stid);

            while ($row = oci_fetch_array($select_stid, OCI_ASSOC)) {
                $emp_id = $row['EMP_ID'];
                $emp_name = $row['EMP_NAME'];
                $plant = $row['PLANT'];

                // Check if already exists in employees table
                $check_sql = "SELECT COUNT(*) AS CNT FROM employees WHERE emp_id = :emp_id";
                $check_stid = oci_parse($conn, $check_sql);
                oci_bind_by_name($check_stid, ":emp_id", $emp_id);
                oci_execute($check_stid);
                $check_row = oci_fetch_array($check_stid, OCI_ASSOC);
                oci_free_statement($check_stid);

                if ($check_row['CNT'] == 0) {
                    // Insert into employees table for lucky draw (is_drawn = 0 เพื่อให้สามารถจับรางวัลได้)
                    $insert_sql = "INSERT INTO employees (emp_id, emp_name, plant, is_drawn) VALUES (:emp_id, :emp_name, :plant, 0)";
                    $insert_stid = oci_parse($conn, $insert_sql);
                    oci_bind_by_name($insert_stid, ":emp_id", $emp_id);
                    oci_bind_by_name($insert_stid, ":emp_name", $emp_name);
                    oci_bind_by_name($insert_stid, ":plant", $plant);

                    if (oci_execute($insert_stid, OCI_DEFAULT)) {
                        $transferCount++;

                        // Update EMP_CHECKIN.STATUS to 'DONE' and SCAN_TIME after successful transfer
                        $update_sql = "UPDATE EMP_CHECKIN SET STATUS = 'DONE', SCAN_TIME = SYSDATE WHERE EMP_ID = :emp_id";
                        $update_stid = oci_parse($conn, $update_sql);
                        oci_bind_by_name($update_stid, ":emp_id", $emp_id);
                        oci_execute($update_stid, OCI_DEFAULT);
                        oci_free_statement($update_stid);
                    }
                    oci_free_statement($insert_stid);
                } else {
                    $skipCount++;

                    // Even if exists in employees, still mark as DONE and update SCAN_TIME in EMP_CHECKIN
                    $update_sql = "UPDATE EMP_CHECKIN SET STATUS = 'DONE', SCAN_TIME = SYSDATE WHERE EMP_ID = :emp_id";
                    $update_stid = oci_parse($conn, $update_sql);
                    oci_bind_by_name($update_stid, ":emp_id", $emp_id);
                    oci_execute($update_stid, OCI_DEFAULT);
                    oci_free_statement($update_stid);
                }
            }
            oci_free_statement($select_stid);

            if ($transferCount > 0 || $skipCount > 0) {
                oci_commit($conn);
                $msg = "Transfer สำเร็จ $transferCount รายการ ไปยังระบบจับรางวัล";
                if ($skipCount > 0) {
                    $msg .= " (ข้ามไป $skipCount รายการที่มีอยู่แล้ว)";
                }
                $msg .= " | STATUS อัปเดตเป็น DONE แล้ว";
                oci_close($conn);
                flashRedirect($msg, 'success');
            } else {
                oci_close($conn);
                flashRedirect("ไม่มีข้อมูลใหม่ที่จะ Transfer (อาจ Transfer ไปแล้วทั้งหมด)", 'warning');
            }
        } catch (Exception $e) {
            oci_rollback($conn);
            oci_close($conn);
            flashRedirect("เกิดข้อผิดพลาด: " . $e->getMessage(), 'danger');
        }
    }
}

// --- Fetch Current Data ---
$employees = [];
$attendedYCount = 0;
$attendedNCount = 0;
$conn = get_db_connection();
if ($conn) {
    $sql = "SELECT QR_CODE, EMP_ID, EMP_NAME, PLANT, STATUS, IS_ATTENDED, TABLE_CODE, TABLE_NO FROM EMP_CHECKIN ORDER BY EMP_ID ASC";
    $stid = oci_parse($conn, $sql);
    oci_execute($stid);
    while ($row = oci_fetch_array($stid, OCI_ASSOC)) {
        $employees[] = $row;
        // Count IS_ATTENDED status
        if (($row['IS_ATTENDED'] ?? 'N') === 'Y') {
            $attendedYCount++;
        } else {
            $attendedNCount++;
        }
    }
    oci_free_statement($stid);
    oci_close($conn);
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>อัพโหลดข้อมูลพนักงาน | EMP_CHECKIN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .hero-section {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
            border-bottom-left-radius: 50px;
            border-bottom-right-radius: 50px;
        }

        .upload-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .upload-zone {
            border: 3px dashed #dee2e6;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            background: #f8f9fa;
        }

        .upload-zone:hover,
        .upload-zone.dragover {
            border-color: #2a5298;
            background: #e8f4ff;
        }

        .upload-zone i {
            font-size: 4rem;
            color: #2a5298;
            margin-bottom: 15px;
        }

        .table-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-checked {
            background: #d4edda;
            color: #155724;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .btn-delete {
            padding: 4px 10px;
            font-size: 0.8rem;
        }

        .back-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
        }
    </style>
</head>

<body>

    <a href="index.php" class="btn btn-light back-btn shadow-sm">
        <i class="fas fa-arrow-left me-2"></i>กลับหน้าหลัก
    </a>

    <header class="hero-section text-center">
        <div class="container">
            <h1 class="display-5 fw-bold"><i class="fas fa-upload me-3"></i>อัพโหลดข้อมูลพนักงาน</h1>
            <p class="lead mb-0">นำเข้าข้อมูลพนักงานจากไฟล์ Excel หรือ CSV เข้าสู่ระบบ EMP_CHECKIN</p>
        </div>
    </header>

    <div class="container pb-5">
        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : ($messageType === 'warning' ? 'exclamation-triangle' : 'times-circle') ?> me-2"></i>
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Upload Section -->
            <div class="col-lg-4">
                <div class="card upload-card h-100">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4"><i class="fas fa-file-excel text-success me-2"></i>อัพโหลดไฟล์</h5>

                        <form method="POST" enctype="multipart/form-data" id="uploadForm">
                            <div class="upload-zone" id="uploadZone" onclick="document.getElementById('excel_file').click()">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <h5 class="fw-bold">ลากไฟล์มาวางที่นี่</h5>
                                <p class="text-muted mb-0">หรือคลิกเพื่อเลือกไฟล์</p>
                                <small class="text-muted">รองรับ: Excel (.xlsx), CSV</small>
                            </div>
                            <input type="file" name="excel_file" id="excel_file" accept=".csv,.xlsx" hidden>
                            <div id="fileName" class="text-center mt-3 text-success fw-bold" style="display:none;"></div>
                            <button type="submit" class="btn btn-primary w-100 mt-3" id="uploadBtn" disabled>
                                <i class="fas fa-upload me-2"></i>อัพโหลดและนำเข้าข้อมูล
                            </button>
                        </form>

                        <hr class="my-4">

                        <h6 class="fw-bold mb-3"><i class="fas fa-info-circle text-info me-2"></i>รูปแบบไฟล์ CSV</h6>
                        <p class="small text-muted mb-2">ไฟล์ CSV ต้องมี Column ดังนี้:</p>
                        <ul class="small text-muted">
                            <li><strong>QR_CODE</strong> - รหัส QR</li>
                            <li><strong>EMP_ID</strong> - รหัสพนักงาน</li>
                            <li><strong>EMP_NAME</strong> - ชื่อพนักงาน</li>
                            <li><strong>PLANT</strong> - โรงงาน</li>
                            <li><strong>STATUS</strong> - สถานะ (ไม่บังคับ, default: PENDING)</li>
                            <li><strong>IS_ATTENDED</strong> - เข้าร่วมงาน Y/N (ไม่บังคับ, default: N)</li>
                            <li><strong>TABLE_CODE</strong> - รหัสโต๊ะ (ไม่บังคับ)</li>
                            <li><strong>TABLE_NO</strong> - หมายเลขโต๊ะ (ไม่บังคับ)</li>
                        </ul>
                        <a href="template/employee_template.csv" download class="btn btn-outline-success btn-sm w-100">
                            <i class="fas fa-download me-2"></i>ดาวน์โหลด Template
                        </a>

                        <hr class="my-4">

                        <form method="POST" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบข้อมูลทั้งหมด?');">
                            <input type="hidden" name="clear_all" value="1">
                            <button type="submit" class="btn btn-outline-danger w-100">
                                <i class="fas fa-trash-alt me-2"></i>ลบข้อมูลทั้งหมด
                            </button>
                        </form>

                        <hr class="my-4">

                        <!-- Transfer to Lucky Draw Section -->
                        <h6 class="fw-bold mb-3"><i class="fas fa-gift text-warning me-2"></i>Transfer ไประบบจับรางวัล</h6>
                        <form method="POST" onsubmit="return confirm('ต้องการ Transfer ข้อมูลไปยังระบบจับรางวัลหรือไม่?');">
                            <input type="hidden" name="transfer_to_lucky_draw" value="1">
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="transfer_type" id="transferChecked" value="checked" checked>
                                    <label class="form-check-label small" for="transferChecked">
                                        <i class="fas fa-check-circle text-success me-1"></i>เฉพาะผู้ Check-in แล้ว
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="transfer_type" id="transferAll" value="all">
                                    <label class="form-check-label small" for="transferAll">
                                        <i class="fas fa-users text-primary me-1"></i>ทั้งหมด (Backup Plan)
                                    </label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-warning w-100">
                                <i class="fas fa-exchange-alt me-2"></i>Transfer ไปจับรางวัล
                            </button>
                        </form>

                        <hr class="my-4">

                        <!-- Full Reset Section -->
                        <h6 class="fw-bold mb-3 text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Reset ทั้งระบบ (Test)</h6>
                        <p class="small text-muted mb-3">
                            <strong>?? คำเตือน:</strong> จะคืนจำนวนรางวัล, ลบผู้ชนะ, ลบผู้เข้าร่วมจับรางวัล และ reset สถานะ Check-in ทั้งหมด
                        </p>
                        <form method="POST" onsubmit="return confirm('?? คำเตือน! \\n\\nการ Reset จะ:\\n- คืนจำนวนรางวัลกลับเป็นค่าเริ่มต้น\\n- ลบผู้ชนะรางวัลทั้งหมด\\n- ลบผู้เข้าร่วมจับรางวัลทั้งหมด\\n- Reset สถานะ Check-in เป็น PENDING\\n\\nยืนยันต้องการ Reset ทั้งระบบหรือไม่?');">
                            <input type="hidden" name="full_reset" value="1">
                            <button type="submit" class="btn btn-outline-danger w-100">
                                <i class="fas fa-undo-alt me-2"></i>Reset ทั้งระบบ
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Data Table Section -->
            <div class="col-lg-8">
                <!-- IS_ATTENDED Statistics -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <div class="card-body text-white text-center py-3">
                                <i class="fas fa-users fa-2x mb-2"></i>
                                <h3 class="fw-bold mb-0"><?= count($employees) ?></h3>
                                <small>พนักงานทั้งหมด</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                            <div class="card-body text-white text-center py-3">
                                <i class="fas fa-check-circle fa-2x mb-2"></i>
                                <h3 class="fw-bold mb-0"><?= $attendedYCount ?></h3>
                                <small>เข้าร่วมงาน (Y)</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);">
                            <div class="card-body text-white text-center py-3">
                                <i class="fas fa-times-circle fa-2x mb-2"></i>
                                <h3 class="fw-bold mb-0"><?= $attendedNCount ?></h3>
                                <small>ไม่เข้าร่วมงาน (N)</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card table-card">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold mb-0"><i class="fas fa-users text-primary me-2"></i>ข้อมูลพนักงานในระบบ</h5>
                            <span class="badge bg-primary fs-6"><?= count($employees) ?> รายการ</span>
                        </div>

                        <div class="table-responsive">
                            <table id="employeeTable" class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>QR_CODE</th>
                                        <th>EMP_ID</th>
                                        <th>EMP_NAME</th>
                                        <th>PLANT</th>
                                        <th>STATUS</th>
                                        <th>IS_ATTENDED</th>
                                        <th>TABLE_CODE</th>
                                        <th>TABLE_NO</th>
                                        <th>จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($employees as $index => $emp): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><code><?= htmlspecialchars($emp['QR_CODE']) ?></code></td>
                                            <td><strong><?= htmlspecialchars($emp['EMP_ID']) ?></strong></td>
                                            <td><?= htmlspecialchars($emp['EMP_NAME']) ?></td>
                                            <td><?= htmlspecialchars($emp['PLANT']) ?></td>
                                            <td>
                                                <span class="<?= $emp['STATUS'] === 'CHECKED' ? 'status-checked' : 'status-pending' ?>">
                                                    <?= htmlspecialchars($emp['STATUS']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?= ($emp['IS_ATTENDED'] ?? 'N') === 'Y' ? 'bg-success' : 'bg-secondary' ?>">
                                                    <?= htmlspecialchars($emp['IS_ATTENDED'] ?? 'N') ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($emp['TABLE_CODE'] ?? '') ?></td>
                                            <td><span class="badge bg-info"><?= htmlspecialchars($emp['TABLE_NO'] ?? '') ?></span></td>
                                            <td>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('ต้องการลบข้อมูลพนักงานนี้หรือไม่?');">
                                                    <input type="hidden" name="delete_emp_id" value="<?= htmlspecialchars($emp['EMP_ID']) ?>">
                                                    <button type="submit" class="btn btn-danger btn-delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#employeeTable').DataTable({
                language: {
                    search: "ค้นหา:",
                    lengthMenu: "แสดง _MENU_ รายการ",
                    info: "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
                    infoEmpty: "ไม่พบข้อมูล",
                    infoFiltered: "(กรองจากทั้งหมด _MAX_ รายการ)",
                    paginate: {
                        first: "หน้าแรก",
                        last: "หน้าสุดท้าย",
                        next: "ถัดไป",
                        previous: "ก่อนหน้า"
                    },
                    zeroRecords: "ไม่พบข้อมูลที่ตรงกัน"
                },
                pageLength: 10,
                order: [
                    [2, 'asc']
                ]
            });
        });

        // File input handling
        const fileInput = document.getElementById('excel_file');
        const uploadZone = document.getElementById('uploadZone');
        const fileNameDisplay = document.getElementById('fileName');
        const uploadBtn = document.getElementById('uploadBtn');

        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                fileNameDisplay.textContent = 'ไฟล์ที่เลือก: ' + this.files[0].name;
                fileNameDisplay.style.display = 'block';
                uploadBtn.disabled = false;
                uploadZone.style.borderColor = '#28a745';
            }
        });

        // Drag and drop
        uploadZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });

        uploadZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });

        uploadZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                fileNameDisplay.textContent = 'ไฟล์ที่เลือก: ' + files[0].name;
                fileNameDisplay.style.display = 'block';
                uploadBtn.disabled = false;
                uploadZone.style.borderColor = '#28a745';
            }
        });
    </script>
</body>

</html>