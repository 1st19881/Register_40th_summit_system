<?php

/**
 * Summit Auto Body Industry - 40th Anniversary Lucky Draw (PHP 7 + Oracle)
 * * Database Setup (Example):
 * CREATE TABLE employees (emp_id VARCHAR2(20) PRIMARY KEY, emp_name VARCHAR2(100), is_drawn NUMBER(1) DEFAULT 0);
 * CREATE TABLE prizes (prize_id NUMBER, prize_name VARCHAR2(500), prize_qty NUMBER DEFAULT 1, create_date DATE DEFAULT SYSDATE);
 * CREATE TABLE winners (winner_id NUMBER, emp_id VARCHAR2(20), emp_name VARCHAR2(100), prize_name VARCHAR2(100), draw_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
 */

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
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Connection failed: ' . $e['message']]);
        exit;
    }
    return $conn;
}

// --- API Routing for AJAX ---
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'get_data') {
    $conn = get_db_connection();

    try {
        // Get available employees
        $emp_query = "SELECT emp_id, emp_name, plant FROM employees WHERE is_drawn = 0 ORDER BY emp_id ASC";
        $stid_emp = oci_parse($conn, $emp_query);
        if (!$stid_emp) {
            throw new Exception('Failed to parse employee query');
        }
        if (!oci_execute($stid_emp)) {
            throw new Exception('Failed to execute employee query');
        }
        $employees = [];
        while ($row = oci_fetch_array($stid_emp, OCI_ASSOC)) {
            $employees[] = $row;
        }

        // Get prizes with remaining quantity
        $prize_query = "SELECT prize_id, prize_name, prize_qty FROM prizes ORDER BY prize_id ASC";
        $stid_prize = oci_parse($conn, $prize_query);
        if (!$stid_prize) {
            throw new Exception('Failed to parse prize query');
        }
        if (!oci_execute($stid_prize)) {
            throw new Exception('Failed to execute prize query');
        }
        $prizes = [];
        while ($row = oci_fetch_array($stid_prize, OCI_ASSOC)) {
            $prizes[] = [
                'id' => $row['PRIZE_ID'],
                'name' => $row['PRIZE_NAME'],
                'qty' => (int)$row['PRIZE_QTY']
            ];
        }

        // Get recent winners
        $win_query = "SELECT emp_id, emp_name, plant, prize_name FROM winners ORDER BY draw_date DESC";
        $stid_win = oci_parse($conn, $win_query);
        if (!$stid_win) {
            throw new Exception('Failed to parse winners query');
        }
        if (!oci_execute($stid_win)) {
            throw new Exception('Failed to execute winners query');
        }
        $winners = [];
        while ($row = oci_fetch_array($stid_win, OCI_ASSOC)) {
            $winners[] = $row;
        }

        // Free all statements
        oci_free_statement($stid_emp);
        oci_free_statement($stid_prize);
        oci_free_statement($stid_win);
        oci_close($conn);

        header('Content-Type: application/json');
        echo json_encode(['employees' => $employees, 'prizes' => $prizes, 'winners' => $winners]);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'draw' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $conn = get_db_connection();

    try {
        // Validate JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON input');
        }
        if (empty($input['prize'])) {
            throw new Exception('Prize name is required');
        }
        $selected_prize = trim($input['prize']);

        // 1. Check if prize still has quantity available
        $check_qty_query = "SELECT prize_qty FROM prizes WHERE prize_name = :prize";
        $chk_stid = oci_parse($conn, $check_qty_query);
        if (!$chk_stid) {
            throw new Exception('Failed to parse check quantity query');
        }
        oci_bind_by_name($chk_stid, ":prize", $selected_prize);
        if (!oci_execute($chk_stid)) {
            throw new Exception('Failed to execute check quantity query');
        }
        $qty_row = oci_fetch_array($chk_stid, OCI_ASSOC);
        oci_free_statement($chk_stid);

        if (!$qty_row || (int)$qty_row['PRIZE_QTY'] <= 0) {
            throw new Exception('รางวัลนี้หมดแล้ว กรุณาเลือกรางวัลอื่น');
        }

        // 2. Pick a random winner from DB
        $pick_query = "SELECT * FROM (SELECT emp_id, emp_name, plant FROM employees WHERE is_drawn = 0 ORDER BY dbms_random.value) WHERE rownum = 1";
        $stid = oci_parse($conn, $pick_query);
        if (!$stid) {
            throw new Exception('Failed to parse pick winner query');
        }
        if (!oci_execute($stid)) {
            throw new Exception('Failed to execute pick winner query');
        }
        $winner = oci_fetch_array($stid, OCI_ASSOC);

        if ($winner) {
            // 3. Update employee status
            $update_query = "UPDATE employees SET is_drawn = 1 WHERE emp_id = :id";
            $upd_stid = oci_parse($conn, $update_query);
            if (!$upd_stid) {
                throw new Exception('Failed to parse update query');
            }
            oci_bind_by_name($upd_stid, ":id", $winner['EMP_ID']);
            if (!oci_execute($upd_stid, OCI_DEFAULT)) {
                throw new Exception('Failed to update employee status');
            }

            // 4. Decrease prize quantity by 1
            $decrease_qty_query = "UPDATE prizes SET prize_qty = prize_qty - 1 WHERE prize_name = :prize AND prize_qty > 0";
            $dec_stid = oci_parse($conn, $decrease_qty_query);
            if (!$dec_stid) {
                throw new Exception('Failed to parse decrease quantity query');
            }
            oci_bind_by_name($dec_stid, ":prize", $selected_prize);
            if (!oci_execute($dec_stid, OCI_DEFAULT)) {
                throw new Exception('Failed to decrease prize quantity');
            }

            // 5. Get next WINNER_ID
            $max_id_query = "SELECT NVL(MAX(winner_id), 0) + 1 AS next_id FROM winners";
            $max_stid = oci_parse($conn, $max_id_query);
            if (!$max_stid) {
                throw new Exception('Failed to parse max ID query');
            }
            if (!oci_execute($max_stid)) {
                throw new Exception('Failed to execute max ID query');
            }
            $max_row = oci_fetch_array($max_stid, OCI_ASSOC);
            $next_winner_id = $max_row['NEXT_ID'];
            oci_free_statement($max_stid);

            // 6. Log to winners table with WINNER_ID
            $log_query = "INSERT INTO winners (winner_id, emp_id, emp_name, plant, prize_name) VALUES (:wid, :id, :name, :plant, :prize)";
            $log_stid = oci_parse($conn, $log_query);
            if (!$log_stid) {
                throw new Exception('Failed to parse insert winner query');
            }
            oci_bind_by_name($log_stid, ":wid", $next_winner_id);
            oci_bind_by_name($log_stid, ":id", $winner['EMP_ID']);
            oci_bind_by_name($log_stid, ":name", $winner['EMP_NAME']);
            oci_bind_by_name($log_stid, ":plant", $winner['PLANT']);
            oci_bind_by_name($log_stid, ":prize", $selected_prize);
            if (!oci_execute($log_stid, OCI_DEFAULT)) {
                throw new Exception('Failed to insert winner record');
            }

            // Commit transaction
            if (!oci_commit($conn)) {
                throw new Exception('Failed to commit transaction');
            }

            // Free all statements
            oci_free_statement($stid);
            oci_free_statement($upd_stid);
            oci_free_statement($dec_stid);
            oci_free_statement($log_stid);
            oci_close($conn);

            echo json_encode([
                'success' => true,
                'winner' => ['id' => $winner['EMP_ID'], 'name' => $winner['EMP_NAME'], 'plant' => $winner['PLANT']]
            ]);
        } else {
            oci_free_statement($stid);
            oci_close($conn);
            echo json_encode(['success' => false, 'message' => 'ไม่มีรายชื่อพนักงานเหลือให้สุ่ม']);
        }
    } catch (Exception $e) {
        // Rollback on error
        if (isset($conn)) {
            oci_rollback($conn);
            oci_close($conn);
        }
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'delete_winner' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $conn = get_db_connection();

    try {
        // Validate JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON input');
        }
        if (empty($input['emp_id'])) {
            throw new Exception('Employee ID is required');
        }
        $emp_id = trim($input['emp_id']);

        // 1. Get the prize name before deleting (to restore quantity)
        $get_prize_query = "SELECT prize_name FROM winners WHERE emp_id = :id";
        $get_stid = oci_parse($conn, $get_prize_query);
        if (!$get_stid) {
            throw new Exception('Failed to parse get prize query');
        }
        oci_bind_by_name($get_stid, ":id", $emp_id);
        if (!oci_execute($get_stid)) {
            throw new Exception('Failed to execute get prize query');
        }
        $prize_row = oci_fetch_array($get_stid, OCI_ASSOC);
        oci_free_statement($get_stid);

        if (!$prize_row) {
            throw new Exception('ไม่พบข้อมูลผู้ชนะ');
        }
        $prize_name = $prize_row['PRIZE_NAME'];

        // 2. Delete from winners table
        $delete_query = "DELETE FROM winners WHERE emp_id = :id";
        $del_stid = oci_parse($conn, $delete_query);
        if (!$del_stid) {
            throw new Exception('Failed to parse delete query');
        }
        oci_bind_by_name($del_stid, ":id", $emp_id);
        if (!oci_execute($del_stid, OCI_DEFAULT)) {
            throw new Exception('Failed to delete winner record');
        }
        oci_free_statement($del_stid);

        // 3. Restore prize quantity (+1)
        $restore_qty_query = "UPDATE prizes SET prize_qty = prize_qty + 1 WHERE prize_name = :prize";
        $res_stid = oci_parse($conn, $restore_qty_query);
        if (!$res_stid) {
            throw new Exception('Failed to parse restore quantity query');
        }
        oci_bind_by_name($res_stid, ":prize", $prize_name);
        if (!oci_execute($res_stid, OCI_DEFAULT)) {
            throw new Exception('Failed to restore prize quantity');
        }
        oci_free_statement($res_stid);

        // Commit transaction
        if (!oci_commit($conn)) {
            throw new Exception('Failed to commit transaction');
        }

        oci_close($conn);

        echo json_encode(['success' => true, 'message' => 'ลบผู้ชนะและคืนจำนวนรางวัลสำเร็จ']);
    } catch (Exception $e) {
        // Rollback on error
        if (isset($conn)) {
            oci_rollback($conn);
            oci_close($conn);
        }
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Summit Auto Body Industry - 40th Anniversary Lucky Draw</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Hide all scrollbars */
        html,
        body,
        * {
            scrollbar-width: none;
            /* Firefox */
            -ms-overflow-style: none;
            /* IE/Edge */
        }

        html::-webkit-scrollbar,
        body::-webkit-scrollbar,
        *::-webkit-scrollbar {
            display: none;
            /* Chrome, Safari, Opera */
        }

        body {
            font-family: 'Kanit', sans-serif;
            background: #0a0a0f;
            min-height: 100vh;
            color: #ffffff;
            overflow-x: hidden;
            overflow-y: auto;
            position: relative;
            padding: clamp(1rem, 3vw, 2rem);
            scrollbar-width: none;
            /* Firefox */
            -ms-overflow-style: none;
            /* IE/Edge */
        }

        body::-webkit-scrollbar {
            display: none;
            /* Chrome, Safari, Opera */
        }

        /* Luxury Dark Background with Gradient Orbs */
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                radial-gradient(ellipse at 20% 0%, rgba(191, 149, 63, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 0%, rgba(139, 92, 246, 0.1) 0%, transparent 50%),
                radial-gradient(ellipse at 40% 100%, rgba(191, 149, 63, 0.1) 0%, transparent 50%),
                radial-gradient(ellipse at 100% 50%, rgba(0, 51, 153, 0.1) 0%, transparent 40%),
                linear-gradient(180deg, #0a0a0f 0%, #1a1a2e 50%, #0a0a0f 100%);
            z-index: -2;
        }

        /* Animated Gold Particles */
        body::after {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image:
                radial-gradient(2px 2px at 20px 30px, rgba(191, 149, 63, 0.4), transparent),
                radial-gradient(2px 2px at 40px 70px, rgba(255, 215, 0, 0.3), transparent),
                radial-gradient(1px 1px at 90px 40px, rgba(252, 246, 186, 0.5), transparent),
                radial-gradient(2px 2px at 130px 80px, rgba(191, 149, 63, 0.3), transparent),
                radial-gradient(1px 1px at 160px 120px, rgba(255, 215, 0, 0.4), transparent);
            background-size: 200px 200px;
            animation: sparkle 20s linear infinite;
            z-index: -1;
            opacity: 0.6;
        }

        @keyframes sparkle {
            0% {
                transform: translateY(0);
            }

            100% {
                transform: translateY(-200px);
            }
        }

        /* Logo Watermark Background */
        .logo-watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 60%;
            max-width: 600px;
            height: auto;
            opacity: 0.05;
            z-index: -1;
            pointer-events: none;
            filter: grayscale(100%) brightness(2);
            animation: waveLogo 25s ease-in-out infinite;
        }

        @keyframes waveLogo {
            0% {
                transform: translate(-50%, -50%) rotate(0deg) scale(1);
            }

            33% {
                transform: translate(-51%, -51%) rotate(-1deg) scale(1.02);
            }

            66% {
                transform: translate(-49%, -49%) rotate(1deg) scale(1.02);
            }

            100% {
                transform: translate(-50%, -50%) rotate(0deg) scale(1);
            }
        }

        .gold-text {
            background: linear-gradient(to bottom, #8a6d3b 0%, #bf953f 22%, #fcf6ba 45%, #b38728 50%, #fbf5b7 55%, #aa771c 78%, #835022 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 800;
            filter: drop-shadow(0 2px 10px rgba(191, 149, 63, 0.5));
        }

        .blue-prize-text {
            background: linear-gradient(180deg, #bf953f 0%, #fcf6ba 25%, #bf953f 50%, #fcf6ba 75%, #bf953f 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 900;
            letter-spacing: -0.02em;
            filter: drop-shadow(0 0 20px rgba(191, 149, 63, 0.6)) drop-shadow(0 4px 15px rgba(0, 0, 0, 0.8));
            animation: goldGlow 3s ease-in-out infinite;
        }

        @keyframes goldGlow {

            0%,
            100% {
                filter: drop-shadow(0 0 20px rgba(191, 149, 63, 0.6)) drop-shadow(0 4px 15px rgba(0, 0, 0, 0.8));
            }

            50% {
                filter: drop-shadow(0 0 35px rgba(252, 246, 186, 0.9)) drop-shadow(0 0 60px rgba(191, 149, 63, 0.5)) drop-shadow(0 4px 20px rgba(0, 0, 0, 0.9));
            }
        }



        .glass-card {
            background: rgba(20, 20, 30, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(191, 149, 63, 0.3);
            border-radius: clamp(1rem, 3vw, 1.5rem);
            box-shadow: 0 20px 40px rgba(0, 51, 153, 0.1);
        }

        /* Premium Logo Glow Effect */
        .logo-shimmer {
            position: relative;
            display: inline-block;
        }

        .logo-shimmer img {
            filter: drop-shadow(0 0 15px rgba(191, 149, 63, 0.7)) drop-shadow(0 0 30px rgba(255, 215, 0, 0.4)) drop-shadow(0 0 45px rgba(0, 51, 153, 0.3)) drop-shadow(0 10px 25px rgba(0, 0, 0, 0.3));
            animation: premiumGlow 4s ease-in-out infinite, logoPulse 2s ease-in-out infinite;
            transition: all 0.4s ease;
        }

        .logo-shimmer:hover img {
            transform: scale(1.08);
            filter: drop-shadow(0 0 25px rgba(191, 149, 63, 1)) drop-shadow(0 0 50px rgba(255, 215, 0, 0.8)) drop-shadow(0 0 75px rgba(0, 51, 153, 0.5)) drop-shadow(0 15px 35px rgba(0, 0, 0, 0.4));
        }

        /* Shimmer Line Effect */
        .logo-shimmer::before {
            content: '';
            position: absolute;
            top: 0;
            left: -150%;
            width: 80%;
            height: 100%;
            background: linear-gradient(90deg,
                    transparent 0%,
                    rgba(255, 255, 255, 0.1) 20%,
                    rgba(255, 255, 255, 0.4) 40%,
                    rgba(255, 215, 0, 0.3) 50%,
                    rgba(255, 255, 255, 0.4) 60%,
                    rgba(255, 255, 255, 0.1) 80%,
                    transparent 100%);
            transform: skewX(-25deg);
            animation: shimmerPass 3s ease-in-out infinite;
            pointer-events: none;
            z-index: 10;
        }

        /* Outer Glow Ring */
        .logo-shimmer::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 120%;
            height: 120%;
            background: radial-gradient(ellipse at center,
                    rgba(191, 149, 63, 0.15) 0%,
                    rgba(255, 215, 0, 0.1) 30%,
                    rgba(0, 51, 153, 0.05) 60%,
                    transparent 70%);
            border-radius: 50%;
            animation: glowRing 3s ease-in-out infinite alternate;
            pointer-events: none;
            z-index: -1;
        }

        @keyframes premiumGlow {

            0%,
            100% {
                filter: drop-shadow(0 0 15px rgba(191, 149, 63, 0.7)) drop-shadow(0 0 30px rgba(255, 215, 0, 0.4)) drop-shadow(0 0 45px rgba(0, 51, 153, 0.3)) drop-shadow(0 10px 25px rgba(0, 0, 0, 0.3));
            }

            25% {
                filter: drop-shadow(0 0 20px rgba(252, 246, 186, 0.9)) drop-shadow(0 0 40px rgba(191, 149, 63, 0.6)) drop-shadow(0 0 60px rgba(0, 85, 204, 0.4)) drop-shadow(0 12px 30px rgba(0, 0, 0, 0.35));
            }

            50% {
                filter: drop-shadow(0 0 25px rgba(255, 223, 128, 1)) drop-shadow(0 0 50px rgba(255, 215, 0, 0.7)) drop-shadow(0 0 70px rgba(0, 51, 153, 0.5)) drop-shadow(0 15px 35px rgba(0, 0, 0, 0.4));
            }

            75% {
                filter: drop-shadow(0 0 22px rgba(255, 200, 100, 0.85)) drop-shadow(0 0 45px rgba(191, 149, 63, 0.55)) drop-shadow(0 0 65px rgba(0, 70, 180, 0.35)) drop-shadow(0 13px 32px rgba(0, 0, 0, 0.38));
            }
        }

        @keyframes logoPulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.02);
            }
        }

        @keyframes shimmerPass {
            0% {
                left: -150%;
                opacity: 0;
            }

            10% {
                opacity: 1;
            }

            50% {
                left: 150%;
                opacity: 1;
            }

            60%,
            100% {
                left: 150%;
                opacity: 0;
            }
        }

        @keyframes glowRing {
            0% {
                transform: translate(-50%, -50%) scale(1);
                opacity: 0.6;
            }

            100% {
                transform: translate(-50%, -50%) scale(1.15);
                opacity: 0.9;
            }
        }

        /* Responsive grid and sizing */
        @media (max-width: 1023px) {
            .glass-card {
                border-radius: 1.25rem;
            }
        }

        @media (min-width: 1024px) {
            .glass-card {
                border-radius: 1.5rem;
            }
        }

        /* Desktop optimizations */
        @media (min-width: 1200px) {
            body {
                padding: clamp(1.5rem, 2vw, 2.5rem);
            }
        }

        .char-box {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 40px;
            height: 55px;
            background: #ffffff;
            border: 2px solid #003399;
            border-radius: 8px;
            margin: 2px;
            font-size: 1.3rem;
            font-weight: 800;
            color: #003399;
            transition: all 0.2s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        /* Responsive char-box for different screen sizes */
        @media (min-width: 480px) {
            .char-box {
                width: 55px;
                height: 75px;
                border-radius: 10px;
                margin: 4px;
                font-size: 1.8rem;
                border-width: 2px;
            }
        }

        @media (min-width: 768px) {
            .char-box {
                width: 75px;
                height: 100px;
                border-radius: 12px;
                margin: 6px;
                font-size: 2.5rem;
                border-width: 3px;
            }
        }

        @media (min-width: 1024px) {
            .char-box {
                width: 95px;
                height: 125px;
                border-radius: 15px;
                margin: 8px;
                font-size: 3.5rem;
                border-width: 3px;
            }
        }

        @media (min-width: 1280px) {
            .char-box {
                width: 110px;
                height: 145px;
                border-radius: 18px;
                margin: 10px;
                font-size: 4.5rem;
                border-width: 4px;
            }
        }

        @media (min-width: 1536px) {
            .char-box {
                width: 130px;
                height: 170px;
                border-radius: 20px;
                margin: 12px;
                font-size: 5.5rem;
                border-width: 5px;
            }
        }

        .char-reveal {
            animation: lockIn 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
            background: linear-gradient(145deg, #003399, #001a4d);
            border-color: #ffffff;
            color: #ffffff;
            box-shadow: 0 0 30px rgba(0, 51, 153, 0.6), 0 4px 15px rgba(0, 0, 0, 0.4);
        }

        @keyframes lockIn {
            0% {
                transform: scale(1.4);
            }

            100% {
                transform: scale(1);
            }
        }

        /* Shake Animation for Suspense */
        @keyframes shake {

            0%,
            100% {
                transform: translateX(0) rotate(0deg);
            }

            10% {
                transform: translateX(-8px) rotate(-5deg);
            }

            20% {
                transform: translateX(8px) rotate(5deg);
            }

            30% {
                transform: translateX(-8px) rotate(-3deg);
            }

            40% {
                transform: translateX(8px) rotate(3deg);
            }

            50% {
                transform: translateX(-6px) rotate(-2deg);
            }

            60% {
                transform: translateX(6px) rotate(2deg);
            }

            70% {
                transform: translateX(-4px) rotate(-1deg);
            }

            80% {
                transform: translateX(4px) rotate(1deg);
            }

            90% {
                transform: translateX(-2px) rotate(0deg);
            }
        }

        @keyframes intensePulse {

            0%,
            100% {
                box-shadow: 0 0 5px rgba(0, 51, 153, 0.5);
                background: #fff;
            }

            50% {
                box-shadow: 0 0 25px rgba(191, 149, 63, 0.8), 0 0 50px rgba(0, 51, 153, 0.4);
                background: linear-gradient(145deg, #fff, #f0f0f0);
            }
        }

        .char-shaking {
            animation: shake 0.15s ease-in-out infinite, intensePulse 0.3s ease-in-out infinite;
            border-color: #bf953f !important;
        }

        .circle-btn.shaking {
            animation: shake 0.1s ease-in-out infinite;
        }

        .draw-container-shaking {
            animation: shake 0.2s ease-in-out infinite;
        }

        .circle-btn {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #003399 0%, #001a4d 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.4s;
            border: 4px solid #bf953f;
            box-shadow: 0 8px 30px rgba(0, 51, 153, 0.3);
        }

        /* Responsive circle-btn for different screen sizes */
        @media (min-width: 480px) {
            .circle-btn {
                width: 120px;
                height: 120px;
                border-width: 5px;
            }
        }

        @media (min-width: 768px) {
            .circle-btn {
                width: 150px;
                height: 150px;
                border-width: 6px;
            }
        }

        @media (min-width: 1024px) {
            .circle-btn {
                width: 180px;
                height: 180px;
                border-width: 7px;
            }
        }

        @media (min-width: 1280px) {
            .circle-btn {
                width: 200px;
                height: 200px;
                border-width: 8px;
            }
        }

        @media (min-width: 1536px) {
            .circle-btn {
                width: 230px;
                height: 230px;
                border-width: 9px;
            }
        }

        .circle-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .circle-btn:hover:not(:disabled) {
            transform: scale(1.05);
            box-shadow: 0 12px 40px rgba(0, 51, 153, 0.4);
        }

        .pulse-ring {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 2px solid #bf953f;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 0.6;
            }

            100% {
                transform: scale(1.6);
                opacity: 0;
            }
        }

        /* Button Logo Styling */
        .btn-logo {
            width: 65%;
            height: auto;
            object-fit: contain;
            filter: drop-shadow(0 0 8px rgba(255, 255, 255, 0.5)) drop-shadow(0 0 15px rgba(191, 149, 63, 0.4)) drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
            transition: all 0.3s ease;
            z-index: 1;
        }

        .circle-btn:hover .btn-logo {
            transform: scale(1.08);
            filter: drop-shadow(0 0 12px rgba(255, 255, 255, 0.7)) drop-shadow(0 0 25px rgba(191, 149, 63, 0.6)) drop-shadow(0 4px 8px rgba(0, 0, 0, 0.4));
        }

        .circle-btn:disabled .btn-logo {
            filter: grayscale(50%) opacity(0.6);
        }

        /* === DRAWING ANIMATIONS === */

        /* Logo Spin Animation */
        @keyframes logoSpin {
            0% {
                transform: rotate(0deg) scale(1);
            }

            25% {
                transform: rotate(90deg) scale(1.1);
            }

            50% {
                transform: rotate(180deg) scale(1);
            }

            75% {
                transform: rotate(270deg) scale(1.1);
            }

            100% {
                transform: rotate(360deg) scale(1);
            }
        }

        .btn-logo.spinning {
            animation: logoSpin 2s cubic-bezier(0.4, 0, 0.2, 1) infinite;
        }

        /* Button Golden Glow Animation */
        @keyframes goldenGlow {

            0%,
            100% {
                box-shadow: 0 0 20px rgba(191, 149, 63, 0.5),
                    0 0 40px rgba(191, 149, 63, 0.3),
                    0 0 60px rgba(191, 149, 63, 0.2),
                    inset 0 0 20px rgba(191, 149, 63, 0.1);
                border-color: #bf953f;
            }

            50% {
                box-shadow: 0 0 40px rgba(252, 246, 186, 0.8),
                    0 0 80px rgba(191, 149, 63, 0.6),
                    0 0 120px rgba(191, 149, 63, 0.4),
                    inset 0 0 40px rgba(252, 246, 186, 0.2);
                border-color: #fcf6ba;
            }
        }

        .circle-btn.drawing {
            animation: goldenGlow 0.8s ease-in-out infinite;
            pointer-events: none;
        }

        /* Multiple Ring Waves */
        @keyframes ringWave1 {
            0% {
                transform: scale(1);
                opacity: 0.8;
                border-width: 4px;
            }

            100% {
                transform: scale(2);
                opacity: 0;
                border-width: 1px;
            }
        }

        @keyframes ringWave2 {
            0% {
                transform: scale(1);
                opacity: 0.6;
                border-width: 3px;
            }

            100% {
                transform: scale(2.5);
                opacity: 0;
                border-width: 1px;
            }
        }

        @keyframes ringWave3 {
            0% {
                transform: scale(1);
                opacity: 0.4;
                border-width: 2px;
            }

            100% {
                transform: scale(3);
                opacity: 0;
                border-width: 1px;
            }
        }

        .ring-wave {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 4px solid #bf953f;
            pointer-events: none;
        }

        .ring-wave-1 {
            animation: ringWave1 1.5s ease-out infinite;
        }

        .ring-wave-2 {
            animation: ringWave2 1.5s ease-out infinite 0.3s;
        }

        .ring-wave-3 {
            animation: ringWave3 1.5s ease-out infinite 0.6s;
        }

        /* Particle Container */
        .particles-container {
            position: absolute;
            width: 100%;
            height: 100%;
            pointer-events: none;
            overflow: visible;
        }

        @keyframes particleFloat {
            0% {
                transform: translate(0, 0) scale(0);
                opacity: 1;
            }

            50% {
                opacity: 1;
            }

            100% {
                transform: translate(var(--tx), var(--ty)) scale(1);
                opacity: 0;
            }
        }

        .particle {
            position: absolute;
            width: 8px;
            height: 8px;
            background: linear-gradient(135deg, #bf953f, #fcf6ba);
            border-radius: 50%;
            top: 50%;
            left: 50%;
            animation: particleFloat 1.5s ease-out infinite;
        }

        /* Button Press Effect */
        @keyframes buttonPress {
            0% {
                transform: scale(0.85);
            }

            50% {
                transform: scale(0.75);
            }

            100% {
                transform: scale(0.85);
            }
        }

        .circle-btn.pressed {
            animation: buttonPress 0.3s ease-out;
        }

        .prize-tag {
            background: #003399;
            color: #fff;
            padding: 2px 12px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 800;
        }

        .custom-select {
            appearance: none;
            background: white;
            border: 1px solid #bf953f;
        }

        .prize-option {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .qty-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 20px;
            margin-left: 8px;
        }

        .qty-badge.empty {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        .delete-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(231, 76, 60, 0.3);
        }

        .delete-btn:hover {
            background: #c0392b;
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.5);
        }

        .winner-card {
            position: relative;
        }

        .winner-card:hover .delete-btn {
            opacity: 1;
        }

        /* Floating Action Button */
        .fab-container {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
        }

        .fab-btn {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: linear-gradient(135deg, #003399 0%, #001a4d 100%);
            border: 3px solid #bf953f;
            box-shadow: 0 8px 25px rgba(0, 51, 153, 0.5), 0 4px 10px rgba(0, 0, 0, 0.3);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: fabPulse 2s ease-in-out infinite;
        }

        .fab-btn:hover {
            transform: scale(1.1) rotate(10deg);
            box-shadow: 0 12px 35px rgba(0, 51, 153, 0.7), 0 6px 15px rgba(0, 0, 0, 0.4);
        }

        @keyframes fabPulse {

            0%,
            100% {
                box-shadow: 0 8px 25px rgba(0, 51, 153, 0.5), 0 4px 10px rgba(0, 0, 0, 0.3);
            }

            50% {
                box-shadow: 0 8px 35px rgba(0, 51, 153, 0.8), 0 4px 15px rgba(0, 0, 0, 0.4), 0 0 20px rgba(255, 255, 255, 0.5);
            }
        }

        .fab-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            font-size: 12px;
            font-weight: 800;
            min-width: 24px;
            height: 24px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 6px;
            border: 2px solid #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            animation: badgeBounce 1s ease-in-out infinite;
        }

        @keyframes badgeBounce {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }
        }

        /* Setup Modal */
        .setup-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .setup-modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .setup-modal {
            background: rgba(20, 20, 30, 0.95);
            border: 2px solid rgba(191, 149, 63, 0.5);
            border-radius: 24px;
            padding: 30px;
            width: 90%;
            max-width: 500px;
            max-height: 85vh;
            overflow-y: auto;
            transform: translateY(30px) scale(0.95);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.5), 0 0 40px rgba(191, 149, 63, 0.2);
        }

        .setup-modal-overlay.active .setup-modal {
            transform: translateY(0) scale(1);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(191, 149, 63, 0.3);
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(to right, #bf953f, #fcf6ba, #bf953f);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .modal-close {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(191, 149, 63, 0.3);
            color: #fff;
            font-size: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .modal-close:hover {
            background: rgba(239, 68, 68, 0.3);
            border-color: #ef4444;
            transform: rotate(90deg);
        }

        .modal-section {
            margin-bottom: 24px;
        }

        .modal-section-label {
            color: #bf953f;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 10px;
            display: block;
        }

        .modal-select {
            width: 100%;
            padding: 14px 18px;
            background: rgba(26, 26, 46, 0.8);
            border: 1px solid rgba(191, 149, 63, 0.3);
            border-radius: 14px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .modal-select:focus {
            outline: none;
            border-color: #bf953f;
            box-shadow: 0 0 15px rgba(191, 149, 63, 0.3);
        }

        .modal-info {
            margin-top: 8px;
            font-size: 0.85rem;
            font-style: italic;
        }

        .modal-employee-list {
            width: 100%;
            height: 180px;
            background: rgba(26, 26, 46, 0.8);
            border: 1px solid rgba(191, 149, 63, 0.3);
            border-radius: 14px;
            padding: 14px;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.8rem;
            overflow-y: auto;
            line-height: 1.6;
        }

        .modal-footer {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }

        .modal-btn {
            flex: 1;
            padding: 14px 20px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
        }

        .modal-btn-confirm {
            background: linear-gradient(135deg, #bf953f 0%, #fcf6ba 50%, #bf953f 100%);
            color: #1a1a2e;
        }

        .modal-btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(191, 149, 63, 0.4);
        }

        /* Hall of Fame Modal */
        .hof-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(15px);
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .hof-modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .hof-modal {
            background: rgba(20, 20, 30, 0.98);
            border: 2px solid rgba(191, 149, 63, 0.6);
            border-radius: 24px;
            padding: 0;
            width: 95%;
            max-width: 1200px;
            max-height: 90vh;
            overflow: hidden;
            transform: translateY(30px) scale(0.95);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.6), 0 0 60px rgba(191, 149, 63, 0.3);
        }

        .hof-modal-overlay.active .hof-modal {
            transform: translateY(0) scale(1);
        }

        .hof-header {
            background: linear-gradient(135deg, rgba(191, 149, 63, 0.2) 0%, rgba(0, 51, 153, 0.2) 100%);
            padding: 24px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid rgba(191, 149, 63, 0.3);
        }

        .hof-title {
            font-size: 1.8rem;
            font-weight: 800;
            background: linear-gradient(to right, #bf953f, #fcf6ba, #bf953f);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .hof-title-icon {
            font-size: 2.2rem;
            animation: trophyPulse 2s ease-in-out infinite;
        }

        @keyframes trophyPulse {

            0%,
            100% {
                transform: scale(1) rotate(0deg);
            }

            25% {
                transform: scale(1.1) rotate(-5deg);
            }

            75% {
                transform: scale(1.1) rotate(5deg);
            }
        }

        .hof-close {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(191, 149, 63, 0.4);
            color: #fff;
            font-size: 24px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .hof-close:hover {
            background: rgba(239, 68, 68, 0.3);
            border-color: #ef4444;
            transform: rotate(90deg) scale(1.1);
        }

        .hof-content {
            padding: 24px 30px;
            max-height: calc(90vh - 120px);
            overflow-y: auto;
        }

        .hof-content::-webkit-scrollbar {
            width: 8px;
        }

        .hof-content::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 4px;
        }

        .hof-content::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #bf953f, #aa771c);
            border-radius: 4px;
        }

        .hof-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .hof-stat-card {
            flex: 1;
            min-width: 150px;
            background: rgba(191, 149, 63, 0.1);
            border: 1px solid rgba(191, 149, 63, 0.3);
            border-radius: 16px;
            padding: 16px 20px;
            text-align: center;
        }

        .hof-stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(to bottom, #bf953f, #fcf6ba);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hof-stat-label {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.85rem;
            margin-top: 4px;
        }

        .hof-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
        }

        .hof-winner-card {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.95), rgba(245, 245, 245, 0.95));
            border-radius: 16px;
            padding: 16px;
            position: relative;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .hof-winner-card:hover {
            transform: translateY(-5px);
            border-color: #bf953f;
            box-shadow: 0 10px 30px rgba(191, 149, 63, 0.3);
        }

        .hof-winner-card .delete-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: all 0.3s ease;
        }

        .hof-winner-card:hover .delete-btn {
            opacity: 1;
        }

        .hof-winner-card .delete-btn:hover {
            background: #c0392b;
            transform: scale(1.1);
        }

        .hof-empty {
            text-align: center;
            padding: 60px 20px;
            color: rgba(255, 255, 255, 0.5);
        }

        .hof-empty-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        /* Hall of Fame FAB */
        .hof-fab {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: linear-gradient(135deg, #003399 0%, #001a4d 100%);
            border: 3px solid #bf953f;
            box-shadow: 0 8px 25px rgba(0, 51, 153, 0.5), 0 4px 10px rgba(0, 0, 0, 0.3);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .hof-fab:hover {
            transform: scale(1.1) rotate(-10deg);
            box-shadow: 0 12px 35px rgba(0, 51, 153, 0.7), 0 6px 15px rgba(0, 0, 0, 0.4);
        }

        .fab-container-left {
            position: fixed;
            bottom: 30px;
            right: 120px;
            z-index: 1000;
        }

        /* Animation for winner cards */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Fullscreen Button */
        .fullscreen-container {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
        }

        .fullscreen-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(20, 20, 30, 0.8);
            border: 2px solid rgba(191, 149, 63, 0.5);
            color: #bf953f;
            font-size: 18px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .fullscreen-btn:hover {
            background: rgba(191, 149, 63, 0.2);
            border-color: #bf953f;
            transform: scale(1.1);
            box-shadow: 0 0 20px rgba(191, 149, 63, 0.4);
        }

        /* Zoom Controls - Hidden by default, slide out on hover */
        .zoom-container {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 999;
            display: flex;
            gap: 8px;
            align-items: center;
            padding-left: 60px;
            opacity: 0;
            transform: translateX(-100px);
            transition: all 0.3s ease;
            pointer-events: none;
        }

        .fullscreen-container:hover+.zoom-container,
        .zoom-container:hover {
            opacity: 1;
            transform: translateX(0);
            pointer-events: auto;
        }

        .zoom-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(20, 20, 30, 0.8);
            border: 2px solid rgba(191, 149, 63, 0.5);
            color: #bf953f;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .zoom-btn:hover {
            background: rgba(191, 149, 63, 0.2);
            border-color: #bf953f;
            transform: scale(1.1);
            box-shadow: 0 0 15px rgba(191, 149, 63, 0.4);
        }

        .zoom-level {
            background: rgba(20, 20, 30, 0.8);
            border: 2px solid rgba(191, 149, 63, 0.5);
            color: #bf953f;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            backdrop-filter: blur(10px);
        }

        /* ===== Floating Gold Star Particles ===== */
        .bg-scene {
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse at 30% 20%, rgba(191, 149, 63, 0.10) 0%, transparent 50%),
                radial-gradient(ellipse at 70% 80%, rgba(191, 149, 63, 0.06) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 50%, rgba(10, 10, 15, 0.9) 0%, transparent 80%),
                linear-gradient(175deg, #0a0a0f 0%, #1a1a2e 40%, #0d1526 100%);
            z-index: -3;
            pointer-events: none;
        }

        .star-particles {
            position: fixed;
            inset: 0;
            z-index: 1;
            pointer-events: none;
            overflow: hidden;
        }

        .star-particle {
            position: absolute;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(252, 246, 186, 0.9), rgba(191, 149, 63, 0.4));
            animation: starFloat linear infinite;
        }

        @keyframes starFloat {
            0% {
                transform: translateY(100vh) scale(0);
                opacity: 0;
            }

            10% {
                opacity: 1;
            }

            90% {
                opacity: 0.8;
            }

            100% {
                transform: translateY(-10vh) scale(1);
                opacity: 0;
            }
        }

        /* Decorative gold lines */
        .deco-line {
            position: fixed;
            background: linear-gradient(90deg, transparent, rgba(191, 149, 63, 0.12), transparent);
            height: 1px;
            width: 100%;
            z-index: 1;
            pointer-events: none;
        }

        .deco-line-1 {
            top: 15%;
            animation: decoShift 12s ease-in-out infinite;
        }

        .deco-line-2 {
            top: 85%;
            animation: decoShift 12s ease-in-out infinite reverse;
        }

        .deco-line-3 {
            top: 50%;
            animation: decoShift 18s ease-in-out infinite;
            opacity: 0.5;
        }

        @keyframes decoShift {

            0%,
            100% {
                transform: translateX(-20%);
                opacity: 0.3;
            }

            50% {
                transform: translateX(20%);
                opacity: 0.8;
            }
        }
    </style>
</head>

<body class="flex flex-col items-center">

    <!-- Starfield Background Effect -->
    <div class="bg-scene"></div>
    <div class="deco-line deco-line-1"></div>
    <div class="deco-line deco-line-2"></div>
    <div class="deco-line deco-line-3"></div>
    <div class="star-particles" id="starParticles"></div>

    <!-- Logo Watermark Background -->
    <img src="logo/logo.png" alt="" class="logo-watermark">

    <!-- Draw Engine - Full Width -->
    <main class="w-full max-w-7xl px-4 md:px-6">
        <section class="w-full">
            <div class="p-8 md:p-12 lg:p-16 flex flex-col items-center justify-between relative overflow-hidden" style="min-height: clamp(600px, 80vh, 900px);">



                <div class="relative text-center pt-16 md:pt-20 lg:pt-24 z-10">
                    <p class="text-[#bf953f] text-base md:text-lg tracking-widest uppercase mb-3 font-bold">Drawing For</p>
                    <h3 id="displayPrize" class="text-4xl md:text-6xl lg:text-8xl font-black blue-prize-text tracking-wide uppercase px-4">---</h3>
                </div>

                <div class="relative flex flex-col items-center z-10 w-full py-8 md:py-12">
                    <div id="winnerIDDisplay" class="flex flex-wrap justify-center items-center mb-8 md:mb-12">
                        <div class="flex gap-3 md:gap-5">
                            <?php for ($i = 0; $i < 7; $i++): ?><div class="char-box border-dashed" style="background: #1a1a2e; color: #4b5563; border-color: #4b5563;">?</div><?php endfor; ?>
                        </div>
                    </div>
                    <div id="winnerNameDisplay" class="min-h-[100px] md:min-h-[140px] text-center flex flex-col items-center justify-center"></div>
                    <p id="displayPrizeQty" class="text-2xl md:text-3xl lg:text-4xl text-white font-bold mt-4"></p>
                </div>

                <div class="relative flex flex-col items-center gap-6 md:gap-8 mt-4 md:mt-6 z-10">
                    <button onclick="startDrawing()" id="drawBtn" class="circle-btn" style="transform: scale(0.85);">
                        <div id="pulseEffect" class="pulse-ring"></div>
                        <img src="logo/logo.png" class="btn-logo" alt="Logo">
                    </button>
                </div>
            </div>
        </section>
    </main>

    <!-- Fullscreen Button -->
    <div class="fullscreen-container">
        <button id="fullscreenBtn" class="fullscreen-btn" onclick="toggleFullscreen()" title="ขยายเต็มหน้าจอ">
            <i id="fullscreenIcon" class="fas fa-expand"></i>
        </button>
    </div>

    <!-- Zoom Controls -->
    <div class="zoom-container">
        <button class="zoom-btn" onclick="zoomOut()" title="ย่อ (Ctrl + -)">−</button>
        <span id="zoomLevel" class="zoom-level">100%</span>
        <button class="zoom-btn" onclick="zoomIn()" title="ขยาย (Ctrl + +)">+</button>
        <button class="zoom-btn" onclick="resetZoom()" title="รีเซ็ต" style="font-size: 12px;">⟲</button>
    </div>

    <!-- Hall of Fame FAB -->
    <div class="fab-container-left">
        <button class="hof-fab" onclick="openHofModal()" title="ดูรายชื่อผู้โชคดี">
            🏆
        </button>
        <span id="hofBadge" class="fab-badge">0</span>
    </div>

    <!-- Floating Action Button -->
    <div class="fab-container">
        <button class="fab-btn" onclick="openSetupModal()" title="ตั้งค่าการสุ่ม">
            ⚙️
        </button>
        <span id="fabBadge" class="fab-badge">0</span>
    </div>

    <!-- Setup Modal -->
    <div id="setupModal" class="setup-modal-overlay" onclick="closeModalOnOverlay(event)">
        <div class="setup-modal">
            <div class="modal-header">
                <h3 class="modal-title">⚙️ ตั้งค่าการสุ่มรางวัล</h3>
                <button class="modal-close" onclick="closeSetupModal()">✕</button>
            </div>

            <div class="modal-section">
                <label class="modal-section-label">🎁 เลือกรางวัลที่จะสุ่ม</label>
                <select id="prizeSelect" class="modal-select"></select>
                <div id="prizeQtyInfo" class="modal-info text-[#bf953f]/70"></div>
            </div>

            <div class="modal-section">
                <div class="flex justify-between items-center mb-2">
                    <label class="modal-section-label" style="margin-bottom: 0;">👥 พนักงานที่รอสุ่ม</label>
                    <span id="countDisplay" class="text-xs text-[#fcf6ba] font-bold bg-[#bf953f]/20 px-3 py-1 rounded-full">0 รายชื่อ</span>
                </div>
                <div id="namesPreview" class="modal-employee-list">
                    กำลังโหลดข้อมูลจากฐานข้อมูล...
                </div>
            </div>

            <div class="modal-footer">
                <button class="modal-btn modal-btn-confirm" onclick="closeSetupModal()">
                    ✓ ตกลง
                </button>
            </div>
        </div>
    </div>

    <!-- Hall of Fame Modal -->
    <div id="hofModal" class="hof-modal-overlay" onclick="closeHofModalOnOverlay(event)">
        <div class="hof-modal">
            <div class="hof-header">
                <h3 class="hof-title">
                    <span class="hof-title-icon">🏆</span>
                    Hall of Frame
                    <span id="winnerCount" class="text-base font-normal bg-[#bf953f]/30 px-4 py-2 rounded-full text-white">0 ผู้โชคดี</span>
                </h3>
                <button class="hof-close" onclick="closeHofModal()">✕</button>
            </div>
            <div class="hof-content">
                <div class="hof-stats">
                    <div class="hof-stat-card">
                        <div id="statTotalWinners" class="hof-stat-value">0</div>
                        <div class="hof-stat-label">ผู้โชคดีทั้งหมด</div>
                    </div>
                    <div class="hof-stat-card">
                        <div id="statTotalPrizes" class="hof-stat-value">0</div>
                        <div class="hof-stat-label">รางวัลที่แจกแล้ว</div>
                    </div>
                </div>
                <div id="winnerLog" class="hof-grid"></div>
            </div>
        </div>
    </div>

    <script>
        const prizeSelect = document.getElementById('prizeSelect');
        const prizeQtyInfo = document.getElementById('prizeQtyInfo');
        const countDisplay = document.getElementById('countDisplay');
        const namesPreview = document.getElementById('namesPreview');
        const winnerIDDisplay = document.getElementById('winnerIDDisplay');
        const winnerNameDisplay = document.getElementById('winnerNameDisplay');
        const displayPrize = document.getElementById('displayPrize');
        const displayPrizeQty = document.getElementById('displayPrizeQty');
        const drawBtn = document.getElementById('drawBtn');
        const winnerLog = document.getElementById('winnerLog');

        let isDrawing = false;
        let prizesData = []; // Store prizes data with quantities
        const charPool = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

        // Fullscreen toggle function
        function toggleFullscreen() {
            const icon = document.getElementById('fullscreenIcon');

            if (!document.fullscreenElement && !document.webkitFullscreenElement) {
                // Enter fullscreen
                if (document.documentElement.requestFullscreen) {
                    document.documentElement.requestFullscreen();
                } else if (document.documentElement.webkitRequestFullscreen) {
                    document.documentElement.webkitRequestFullscreen();
                }
                icon.classList.remove('fa-expand');
                icon.classList.add('fa-compress');
            } else {
                // Exit fullscreen
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                }
                icon.classList.remove('fa-compress');
                icon.classList.add('fa-expand');
            }
        }

        // Update icon when fullscreen changes
        document.addEventListener('fullscreenchange', updateFullscreenIcon);
        document.addEventListener('webkitfullscreenchange', updateFullscreenIcon);

        function updateFullscreenIcon() {
            const icon = document.getElementById('fullscreenIcon');
            if (document.fullscreenElement || document.webkitFullscreenElement) {
                icon.classList.remove('fa-expand');
                icon.classList.add('fa-compress');
            } else {
                icon.classList.remove('fa-compress');
                icon.classList.add('fa-expand');
            }
        }

        // Zoom functionality
        let currentZoom = 100;
        const minZoom = 50;
        const maxZoom = 200;
        const zoomStep = 10;

        function updateZoomDisplay() {
            document.getElementById('zoomLevel').textContent = currentZoom + '%';
            document.body.style.zoom = currentZoom + '%';
        }

        function zoomIn() {
            if (currentZoom < maxZoom) {
                currentZoom += zoomStep;
                updateZoomDisplay();
            }
        }

        function zoomOut() {
            if (currentZoom > minZoom) {
                currentZoom -= zoomStep;
                updateZoomDisplay();
            }
        }

        function resetZoom() {
            currentZoom = 100;
            updateZoomDisplay();
        }

        // Keyboard shortcuts for zoom (Ctrl + Plus/Minus)
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey) {
                if (e.key === '+' || e.key === '=') {
                    e.preventDefault();
                    zoomIn();
                } else if (e.key === '-') {
                    e.preventDefault();
                    zoomOut();
                } else if (e.key === '0') {
                    e.preventDefault();
                    resetZoom();
                }
            }
        });

        // === SOUND EFFECTS SYSTEM ===
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        let audioCtx = null;

        function initAudio() {
            if (!audioCtx) {
                audioCtx = new AudioContext();
            }
            if (audioCtx.state === 'suspended') {
                audioCtx.resume();
            }
        }

        // Drum roll sound for suspense
        function playDrumRoll(duration = 2000) {
            initAudio();
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            const lfo = audioCtx.createOscillator();
            const lfoGain = audioCtx.createGain();

            // LFO for tremolo effect
            lfo.frequency.value = 20; // Roll speed
            lfoGain.gain.value = 50;
            lfo.connect(lfoGain);
            lfoGain.connect(osc.frequency);

            osc.type = 'triangle';
            osc.frequency.value = 150;
            osc.connect(gain);
            gain.connect(audioCtx.destination);

            // Gradual volume increase
            gain.gain.setValueAtTime(0.1, audioCtx.currentTime);
            gain.gain.linearRampToValueAtTime(0.3, audioCtx.currentTime + duration / 1000);

            lfo.start();
            osc.start();
            osc.stop(audioCtx.currentTime + duration / 1000);
            lfo.stop(audioCtx.currentTime + duration / 1000);

            return {
                stop: () => {
                    try {
                        osc.stop();
                        lfo.stop();
                    } catch (e) {}
                }
            };
        }

        // ?? Slot Machine sound - casino style
        function playSlotMachine(duration = 2000) {
            initAudio();
            const endTime = audioCtx.currentTime + duration / 1000;

            // Create repeating "click-click-click" sounds like slot reels
            const clickInterval = setInterval(() => {
                if (audioCtx.currentTime >= endTime) {
                    clearInterval(clickInterval);
                    return;
                }

                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();

                osc.type = 'square';
                osc.frequency.value = 200 + Math.random() * 300;
                osc.connect(gain);
                gain.connect(audioCtx.destination);

                gain.gain.setValueAtTime(0.15, audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.05);

                osc.start();
                osc.stop(audioCtx.currentTime + 0.05);
            }, 80);

            return {
                stop: () => clearInterval(clickInterval)
            };
        }

        // ?? Heartbeat sound - pulsing tension
        function playHeartbeat(duration = 2000) {
            initAudio();
            const endTime = audioCtx.currentTime + duration / 1000;
            let beatCount = 0;

            const beatInterval = setInterval(() => {
                if (audioCtx.currentTime >= endTime) {
                    clearInterval(beatInterval);
                    return;
                }

                // Two-beat pattern: lub-dub
                const isLub = beatCount % 2 === 0;
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();

                osc.type = 'sine';
                osc.frequency.value = isLub ? 60 : 80;
                osc.connect(gain);
                gain.connect(audioCtx.destination);

                const volume = 0.3 + (beatCount / 20) * 0.2; // Gradually louder
                gain.gain.setValueAtTime(volume, audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.15);

                osc.start();
                osc.stop(audioCtx.currentTime + 0.15);
                beatCount++;
            }, 200);

            return {
                stop: () => clearInterval(beatInterval)
            };
        }

        // ?? Arcade sound - retro game style
        function playArcade(duration = 2000) {
            initAudio();
            const endTime = audioCtx.currentTime + duration / 1000;

            const notes = [262, 330, 392, 523, 659, 784]; // C major scale
            let noteIndex = 0;

            const arcadeInterval = setInterval(() => {
                if (audioCtx.currentTime >= endTime) {
                    clearInterval(arcadeInterval);
                    return;
                }

                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();

                osc.type = 'square';
                osc.frequency.value = notes[noteIndex % notes.length];
                osc.connect(gain);
                gain.connect(audioCtx.destination);

                gain.gain.setValueAtTime(0.15, audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.08);

                osc.start();
                osc.stop(audioCtx.currentTime + 0.08);
                noteIndex++;
            }, 100);

            return {
                stop: () => clearInterval(arcadeInterval)
            };
        }

        // ?? Rising Tension sound - building up
        function playRisingTension(duration = 2000) {
            initAudio();
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();

            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(80, audioCtx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(800, audioCtx.currentTime + duration / 1000);
            osc.connect(gain);
            gain.connect(audioCtx.destination);

            gain.gain.setValueAtTime(0.05, audioCtx.currentTime);
            gain.gain.linearRampToValueAtTime(0.25, audioCtx.currentTime + duration / 1000);

            osc.start();
            osc.stop(audioCtx.currentTime + duration / 1000);

            return {
                stop: () => {
                    try {
                        osc.stop();
                    } catch (e) {}
                }
            };
        }

        // Play selected shake sound
        function playSelectedShakeSound(duration = 2000) {
            const soundType = document.getElementById('soundSelect').value;
            switch (soundType) {
                case 'slotmachine':
                    return playSlotMachine(duration);
                case 'heartbeat':
                    return playHeartbeat(duration);
                case 'arcade':
                    return playArcade(duration);
                case 'rising':
                    return playRisingTension(duration);
                case 'drumroll':
                default:
                    return playDrumRoll(duration);
            }
        }

        // Test button function
        function testSelectedSound() {
            playSelectedShakeSound(1500); // Play for 1.5 seconds
        }

        // Tick sound for each character reveal
        function playTick() {
            initAudio();
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();

            osc.type = 'sine';
            osc.frequency.value = 800 + Math.random() * 400; // Vary pitch
            osc.connect(gain);
            gain.connect(audioCtx.destination);

            gain.gain.setValueAtTime(0.3, audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.1);

            osc.start();
            osc.stop(audioCtx.currentTime + 0.1);
        }

        // Fanfare sound for winner announcement
        function playFanfare() {
            initAudio();
            const notes = [523.25, 659.25, 783.99, 1046.50]; // C5, E5, G5, C6

            notes.forEach((freq, i) => {
                setTimeout(() => {
                    const osc = audioCtx.createOscillator();
                    const gain = audioCtx.createGain();

                    osc.type = 'square';
                    osc.frequency.value = freq;
                    osc.connect(gain);
                    gain.connect(audioCtx.destination);

                    gain.gain.setValueAtTime(0.2, audioCtx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.4);

                    osc.start();
                    osc.stop(audioCtx.currentTime + 0.4);
                }, i * 150);
            });

            // Final chord
            setTimeout(() => {
                [523.25, 659.25, 783.99, 1046.50].forEach(freq => {
                    const osc = audioCtx.createOscillator();
                    const gain = audioCtx.createGain();

                    osc.type = 'sine';
                    osc.frequency.value = freq;
                    osc.connect(gain);
                    gain.connect(audioCtx.destination);

                    gain.gain.setValueAtTime(0.15, audioCtx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 1);

                    osc.start();
                    osc.stop(audioCtx.currentTime + 1);
                });
            }, 600);
        }

        // Suspense building sound
        function playSuspense() {
            initAudio();
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();

            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(100, audioCtx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(400, audioCtx.currentTime + 0.5);
            osc.connect(gain);
            gain.connect(audioCtx.destination);

            gain.gain.setValueAtTime(0.1, audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.5);

            osc.start();
            osc.stop(audioCtx.currentTime + 0.5);
        }

        async function fetchData() {
            const res = await fetch('?action=get_data');
            const data = await res.json();

            // Store prizes data
            prizesData = data.prizes;

            // Populate Prizes with quantity info
            prizeSelect.innerHTML = data.prizes.map(p => {
                const qtyClass = p.qty <= 0 ? 'empty' : '';
                return `<option value="${p.name}" data-qty="${p.qty}" ${p.qty <= 0 ? 'disabled' : ''}>${p.name} (เหลือ ${p.qty} รางวัล)</option>`;
            }).join('');

            updatePrizeDisplay();

            // Update Counts
            countDisplay.innerText = `${data.employees.length} รายชื่อ`;
            namesPreview.innerText = data.employees.map(e => `${e.EMP_ID}: ${e.EMP_NAME}`).join('\n');

            // Winner Log - Updated for Hall of Fame Modal
            if (data.winners.length > 0) {
                winnerLog.innerHTML = data.winners.map((w, index) => `
                    <div class="hof-winner-card" style="animation: fadeInUp 0.3s ease-out ${index * 0.05}s both;">
                        <button class="delete-btn" onclick="deleteWinner('${w.EMP_ID}', '${w.EMP_NAME}')" title="ลบผู้ชนะ (คืนรางวัล)">
                            🗑
                        </button>
                        <div class="prize-tag mb-2">${w.PRIZE_NAME}</div>
                        <div class="text-[#003399] font-bold text-lg">${w.EMP_ID}</div>
                        <div class="text-gray-600 text-sm truncate">${w.EMP_NAME}</div>
                        <div class="text-[#bf953f] text-xs mt-1">${w.PLANT || ''}</div>
                    </div>
                `).join('');
            } else {
                winnerLog.innerHTML = `
                    <div class="hof-empty" style="grid-column: 1 / -1;">
                        <div class="hof-empty-icon">🎰</div>
                        <p>ยังไม่มีผู้โชคดี</p>
                        <p class="text-sm mt-2">เริ่มสุ่มรางวัลเพื่อดูรายชื่อผู้ชนะที่นี่</p>
                    </div>
                `;
            }

            // Update floating button badge with employee count
            updateBadgeCount(data.employees.length);

            // Update winner count in Hall of Fame header and badge
            updateWinnerCount(data.winners.length);
            updateHofBadge(data.winners.length);
            updateHofStats(data.winners.length, data.prizes);
        }

        function updatePrizeDisplay() {
            const selectedOption = prizeSelect.options[prizeSelect.selectedIndex];
            if (selectedOption) {
                const qty = parseInt(selectedOption.dataset.qty) || 0;
                displayPrize.innerText = prizeSelect.value || "---";

                if (qty > 0) {
                    displayPrizeQty.innerHTML = `คงเหลือ ${qty} รางวัล`;
                    prizeQtyInfo.innerHTML = `<span class="text-green-600">✓ รางวัลนี้ยังเหลือ ${qty} รางวัล</span>`;
                    drawBtn.disabled = false;
                } else {
                    displayPrizeQty.innerHTML = `<span class="text-red-400">หมดแล้ว!</span>`;
                    prizeQtyInfo.innerHTML = `<span class="text-red-500">✗ รางวัลนี้หมดแล้ว กรุณาเลือกรางวัลอื่น</span>`;
                    drawBtn.disabled = true;
                }
            }
        }

        prizeSelect.addEventListener('change', updatePrizeDisplay);

        async function startDrawing() {
            if (isDrawing) return;
            const prize = prizeSelect.value;
            if (!prize) {
                Swal.fire({
                    icon: 'warning',
                    title: 'ยังไม่ได้เลือกรางวัล',
                    text: 'กรุณาเลือกรางวัลก่อนทำการสุ่ม',
                    confirmButtonText: 'ตกลง',
                    confirmButtonColor: '#bf953f',
                    background: '#1a1a2e',
                    color: '#ffffff',
                    iconColor: '#f0c040'
                });
                return;
            }

            // Check quantity before drawing
            const selectedOption = prizeSelect.options[prizeSelect.selectedIndex];
            const qty = parseInt(selectedOption.dataset.qty) || 0;
            if (qty <= 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'รางวัลหมดแล้ว!',
                    text: 'รางวัลนี้หมดแล้ว กรุณาเลือกรางวัลอื่น',
                    confirmButtonText: 'ตกลง',
                    confirmButtonColor: '#bf953f',
                    background: '#1a1a2e',
                    color: '#ffffff',
                    iconColor: '#e74c3c'
                });
                return;
            }

            isDrawing = true;
            drawBtn.disabled = true;
            winnerNameDisplay.innerHTML = "";

            // Call Server to pick winner
            const res = await fetch('?action=draw', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    prize
                })
            });
            const data = await res.json();

            if (!data.success) {
                Swal.fire({
                    icon: 'info',
                    title: 'ไม่สามารถสุ่มได้',
                    text: data.message,
                    confirmButtonText: 'ตกลง',
                    confirmButtonColor: '#bf953f',
                    background: '#1a1a2e',
                    color: '#ffffff',
                    iconColor: '#3498db'
                });
                isDrawing = false;
                drawBtn.disabled = false;
                return;
            }

            const winner = data.winner;
            const idToReveal = winner.id.padEnd(7, " ").substring(0, 7).split("");
            winnerIDDisplay.innerHTML = "";

            // Create char boxes first
            const charElements = idToReveal.map(() => {
                const el = document.createElement('div');
                el.className = 'char-box';
                winnerIDDisplay.appendChild(el);
                return el;
            });

            // === SHAKE PHASE - สร้างความตื่นเต้นก่อนสุ่ม ===
            winnerNameDisplay.innerHTML = `<p class="text-[#bf953f] text-lg md:text-xl font-bold animate-pulse">🎰 กำลังสุ่มผู้โชคดี...</p>`;

            // ?? Play Arcade sound
            playArcade(2000);

            // === ADD DRAWING ANIMATIONS ===
            // Button press effect
            drawBtn.classList.add('pressed');
            setTimeout(() => drawBtn.classList.remove('pressed'), 300);

            // Add drawing class for golden glow
            drawBtn.classList.add('drawing');

            // Add logo spinning
            const btnLogo = drawBtn.querySelector('.btn-logo');
            if (btnLogo) btnLogo.classList.add('spinning');

            // Add ring waves
            const ringWaves = [];
            for (let i = 1; i <= 3; i++) {
                const ring = document.createElement('div');
                ring.className = `ring-wave ring-wave-${i}`;
                drawBtn.appendChild(ring);
                ringWaves.push(ring);
            }

            // Add shaking class to all char boxes
            charElements.forEach(el => {
                el.classList.add('char-shaking');
                el.innerText = '?';
            });

            // Add shaking to draw button too
            drawBtn.classList.add('shaking');

            // Shake for 2 seconds with random characters shuffling
            const shakeStartTime = Date.now();
            const shakeDuration = 2000; // 2 seconds of shaking

            await new Promise((resolve) => {
                const shakeInterval = setInterval(() => {
                    charElements.forEach(el => {
                        el.innerText = charPool[Math.floor(Math.random() * charPool.length)];
                    });

                    if (Date.now() - shakeStartTime >= shakeDuration) {
                        clearInterval(shakeInterval);
                        resolve();
                    }
                }, 50); // Fast shuffle during shake
            });

            // Remove shaking classes
            charElements.forEach(el => {
                el.classList.remove('char-shaking');
            });
            drawBtn.classList.remove('shaking');

            // === REMOVE DRAWING ANIMATIONS ===
            drawBtn.classList.remove('drawing');
            if (btnLogo) btnLogo.classList.remove('spinning');

            // Remove ring waves
            ringWaves.forEach(ring => ring.remove());

            winnerNameDisplay.innerHTML = `<p class="text-[#ffff] text-lg md:text-xl font-bold">🎉 เปิดรางวัล!</p>`;
            playSuspense(); // ?? Play suspense sound
            await new Promise(r => setTimeout(r, 300)); // Brief pause before reveal

            // === REVEAL PHASE - เปิดตัวอักษรทีละตัว ===
            let revealed = 0;
            const shuffle = setInterval(() => {
                charElements.forEach((el, i) => {
                    if (i >= revealed) el.innerText = charPool[Math.floor(Math.random() * charPool.length)];
                });
            }, 60);

            for (let i = 0; i < charElements.length; i++) {
                await new Promise(r => setTimeout(r, 800 + (i * 300)));
                revealed++;
                charElements[i].innerText = idToReveal[i].trim() || "\u00A0";
                charElements[i].classList.add('char-reveal');
                playTick(); // ?? Play tick sound for each reveal
            }
            clearInterval(shuffle);

            winnerNameDisplay.innerHTML = `<p class="text-[#003399]/50 animate-pulse">ขอแสดงความยินดีกับ...</p>`;
            await new Promise(r => setTimeout(r, 1000));

            // ?? Play fanfare when announcing winner
            playFanfare();

            winnerNameDisplay.innerHTML = `
                <div class="name-reveal">
                    <span class="text-3xl md:text-5xl lg:text-6xl font-black text-white">${winner.name} ${winner.plant ? '(' + winner.plant + ')' : ''}</span>
                </div>
            `;

            confetti({
                particleCount: 200,
                spread: 100,
                origin: {
                    y: 0.6
                },
                colors: ['#003399', '#bf953f', '#ffffff']
            });

            isDrawing = false;
            drawBtn.disabled = false;
            fetchData(); // Refresh data from DB
        }

        async function deleteWinner(empId, empName) {

            try {
                const res = await fetch('?action=delete_winner', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        emp_id: empId
                    })
                });
                const data = await res.json();

                if (data.success) {
                    // Show success toast
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2500,
                        timerProgressBar: true,
                        background: '#1a1a2e',
                        color: '#ffffff',
                        didOpen: (toast) => {
                            toast.onmouseenter = Swal.stopTimer;
                            toast.onmouseleave = Swal.resumeTimer;
                        }
                    });
                    Toast.fire({
                        icon: 'success',
                        title: `ลบ ${empName} สำเร็จ`
                    });
                    // Refresh data ทันที
                    await fetchData();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: data.message,
                        confirmButtonText: 'ตกลง',
                        confirmButtonColor: '#bf953f',
                        background: '#1a1a2e',
                        color: '#ffffff'
                    });
                }
            } catch (err) {
                console.error('Error deleting winner:', err);
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: err.message,
                    confirmButtonText: 'ตกลง',
                    confirmButtonColor: '#bf953f',
                    background: '#1a1a2e',
                    color: '#ffffff'
                });
            }
        }

        window.onload = fetchData;

        // === MODAL FUNCTIONS ===
        function openSetupModal() {
            document.getElementById('setupModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeSetupModal() {
            document.getElementById('setupModal').classList.remove('active');
            document.body.style.overflow = '';
        }

        function closeModalOnOverlay(event) {
            if (event.target.id === 'setupModal') {
                closeSetupModal();
            }
        }



        // Update badge count when data changes
        function updateBadgeCount(count) {
            const badge = document.getElementById('fabBadge');
            badge.textContent = count;
            badge.style.display = count > 0 ? 'flex' : 'none';
        }

        // Update winner count display
        function updateWinnerCount(count) {
            const winnerCountEl = document.getElementById('winnerCount');
            if (winnerCountEl) {
                winnerCountEl.textContent = count + ' ผู้โชคดี';
            }
        }

        // === HALL OF FAME MODAL FUNCTIONS ===
        function openHofModal() {
            document.getElementById('hofModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeHofModal() {
            document.getElementById('hofModal').classList.remove('active');
            document.body.style.overflow = '';
        }

        function closeHofModalOnOverlay(event) {
            if (event.target.id === 'hofModal') {
                closeHofModal();
            }
        }

        // Update Hall of Fame badge count
        function updateHofBadge(count) {
            const badge = document.getElementById('hofBadge');
            if (badge) {
                badge.textContent = count;
                badge.style.display = count > 0 ? 'flex' : 'none';
            }
        }

        // Update Hall of Fame statistics
        function updateHofStats(winnersCount, prizes) {
            const totalWinnersEl = document.getElementById('statTotalWinners');
            const totalPrizesEl = document.getElementById('statTotalPrizes');

            if (totalWinnersEl) {
                totalWinnersEl.textContent = winnersCount;
            }

            if (totalPrizesEl) {
                // Calculate total prizes given (original qty - current qty)
                totalPrizesEl.textContent = winnersCount;
            }
        }

        // Keyboard shortcuts handler
        document.addEventListener('keydown', function(e) {
            // Escape: close modals
            if (e.key === 'Escape') {
                closeSetupModal();
                closeHofModal();
            }

            // Spacebar or Enter: start lucky draw
            if (e.key === ' ' || e.key === 'Enter') {
                // Don't trigger if a modal is open
                const setupModalOpen = document.getElementById('setupModal').classList.contains('active');
                const hofModalOpen = document.getElementById('hofModal').classList.contains('active');
                // Don't trigger if SweetAlert is showing
                const swalOpen = document.querySelector('.swal2-container');

                if (setupModalOpen || hofModalOpen || swalOpen) return;

                e.preventDefault(); // Prevent page scroll on spacebar
                startDrawing();
            }
        });
    </script>

    <!-- Star Particles Initialization -->
    <script>
        (function initStarParticles() {
            const container = document.getElementById('starParticles');
            if (!container) return;
            const count = 40;
            for (let i = 0; i < count; i++) {
                const p = document.createElement('div');
                p.className = 'star-particle';
                const size = (Math.random() * 3 + 1.5);
                p.style.left = Math.random() * 100 + '%';
                p.style.width = size + 'px';
                p.style.height = size + 'px';
                p.style.animationDuration = (Math.random() * 14 + 8) + 's';
                p.style.animationDelay = (Math.random() * 12) + 's';
                if (size > 3) {
                    p.style.boxShadow = '0 0 6px rgba(252, 246, 186, 0.6)';
                }
                container.appendChild(p);
            }
        })();
    </script>
</body>

</html>