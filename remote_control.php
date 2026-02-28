<?php
require 'auth.php';

// --- Oracle Connection ---
$db_user = "hrmsit";
$db_pass = "ithrms";
$db_conn_str = "HRMS";

function get_db_conn()
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

// API
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'get_remote_data') {
    header('Content-Type: application/json');
    $conn = get_db_conn();

    try {
        $prize_query = "SELECT prize_id, prize_name, prize_qty FROM prizes ORDER BY prize_id ASC";
        $stid = oci_parse($conn, $prize_query);
        oci_execute($stid);
        $prizes = [];
        while ($row = oci_fetch_array($stid, OCI_ASSOC)) {
            $prizes[] = ['id' => $row['PRIZE_ID'], 'name' => $row['PRIZE_NAME'], 'qty' => (int)$row['PRIZE_QTY']];
        }
        oci_free_statement($stid);

        $emp_query = "SELECT COUNT(*) AS cnt FROM employees WHERE is_drawn = 0";
        $stid = oci_parse($conn, $emp_query);
        oci_execute($stid);
        $emp_row = oci_fetch_array($stid, OCI_ASSOC);
        $emp_count = (int)$emp_row['CNT'];
        oci_free_statement($stid);

        // Count total employees who have checked in (total in employees table)
        $total_emp_query = "SELECT COUNT(*) AS cnt FROM employees";
        $stid = oci_parse($conn, $total_emp_query);
        oci_execute($stid);
        $total_emp_row = oci_fetch_array($stid, OCI_ASSOC);
        $total_emp_count = (int)$total_emp_row['CNT'];
        oci_free_statement($stid);

        $win_query = "SELECT * FROM (SELECT emp_id, emp_name, plant, prize_name FROM winners ORDER BY draw_date DESC) WHERE rownum <= 10";
        $stid = oci_parse($conn, $win_query);
        oci_execute($stid);
        $winners = [];
        while ($row = oci_fetch_array($stid, OCI_ASSOC)) {
            $winners[] = $row;
        }
        oci_free_statement($stid);

        oci_close($conn);
        echo json_encode(['prizes' => $prizes, 'emp_count' => $emp_count, 'total_emp_count' => $total_emp_count, 'winners' => $winners]);
    } catch (Exception $e) {
        if (isset($conn)) oci_close($conn);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Remote Control | Lucky Draw</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --primary: #003399;
            --primary-dark: #001a4d;
            --gold: #bf953f;
            --gold-light: #fcf6ba;
            --bg-dark: #0a0a0f;
            --bg-card: rgba(20, 20, 30, 0.9);
            --border-gold: rgba(191, 149, 63, 0.3);
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.6);
            --success: #10b981;
            --danger: #ef4444;
        }

        body {
            font-family: 'Kanit', sans-serif;
            background: var(--bg-dark);
            min-height: 100vh;
            min-height: 100dvh;
            color: var(--text-primary);
            overflow-x: hidden;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse at 30% 10%, rgba(191, 149, 63, 0.12) 0%, transparent 50%),
                radial-gradient(ellipse at 70% 90%, rgba(0, 51, 153, 0.1) 0%, transparent 50%),
                linear-gradient(180deg, #0a0a0f 0%, #12122a 50%, #0a0a0f 100%);
            z-index: -1;
        }

        .page {
            max-width: 440px;
            margin: 0 auto;
            padding: 16px 20px;
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
        }

        /* ---- Top Bar ---- */
        .top-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 0 16px;
        }

        .back-btn {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s;
        }

        .back-btn:hover {
            color: var(--gold);
        }

        .conn-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.7rem;
            color: var(--text-secondary);
            padding: 5px 12px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .conn-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--success);
            transition: background 0.3s;
        }

        .conn-dot.off {
            background: var(--danger);
        }

        /* ---- Header ---- */
        .header {
            text-align: center;
            padding: 8px 0 4px;
        }

        .header-icon {
            width: 48px;
            height: 48px;
            margin: 0 auto 8px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border: 2px solid var(--gold);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: var(--gold-light);
        }

        .header h1 {
            font-size: 1.3rem;
            font-weight: 700;
            background: linear-gradient(to right, var(--gold), var(--gold-light), var(--gold));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header p {
            color: var(--text-secondary);
            font-size: 0.8rem;
            margin-top: 2px;
        }

        /* ---- Stats ---- */
        .stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin: 16px 0;
        }

        .stat-box {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-gold);
            border-radius: 16px;
            padding: 14px;
            text-align: center;
        }

        .stat-num {
            font-size: 1.8rem;
            font-weight: 800;
            background: linear-gradient(to bottom, var(--gold), var(--gold-light));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1.1;
        }

        .stat-lbl {
            font-size: 0.7rem;
            color: var(--text-secondary);
            margin-top: 2px;
        }

        /* ---- Prize Select ---- */
        .prize-card {
            background: var(--bg-card);
            border: 1px solid var(--border-gold);
            border-radius: 18px;
            padding: 18px 20px;
            backdrop-filter: blur(20px);
        }

        .prize-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--gold);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .prize-select {
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid var(--border-gold);
            border-radius: 12px;
            padding: 12px 40px 12px 14px;
            color: var(--text-primary);
            font-family: 'Kanit', sans-serif;
            font-size: 0.95rem;
            font-weight: 500;
            outline: none;
            cursor: pointer;
            transition: border-color 0.3s, box-shadow 0.3s;
            -webkit-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23bf953f' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
        }

        .prize-select:focus {
            border-color: var(--gold);
            box-shadow: 0 0 15px rgba(191, 149, 63, 0.2);
        }

        .prize-select option {
            background: #1a1a2e;
            color: white;
        }

        .prize-qty-tag {
            margin-top: 8px;
            font-size: 0.78rem;
            padding: 6px 12px;
            border-radius: 8px;
            display: none;
        }

        .prize-qty-tag.ok {
            display: block;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: var(--success);
        }

        .prize-qty-tag.empty {
            display: block;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: var(--danger);
        }

        /* ---- Big Circle Button ---- */
        .btn-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px 0 8px;
        }

        .spin-circle {
            position: relative;
            width: 160px;
            height: 160px;
            border-radius: 50%;
            background: linear-gradient(145deg, var(--primary), var(--primary-dark));
            border: 4px solid var(--gold);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow:
                0 0 30px rgba(0, 51, 153, 0.4),
                0 0 60px rgba(191, 149, 63, 0.15),
                inset 0 -4px 12px rgba(0, 0, 0, 0.3);
            -webkit-tap-highlight-color: transparent;
            user-select: none;
        }

        .spin-circle::before {
            content: '';
            position: absolute;
            inset: -8px;
            border-radius: 50%;
            border: 2px solid rgba(191, 149, 63, 0.15);
            animation: orbit-pulse 3s ease-in-out infinite;
        }

        .spin-circle::after {
            content: '';
            position: absolute;
            inset: -16px;
            border-radius: 50%;
            border: 1px solid rgba(191, 149, 63, 0.08);
            animation: orbit-pulse 3s ease-in-out infinite reverse;
        }

        @keyframes orbit-pulse {

            0%,
            100% {
                transform: scale(1);
                opacity: 0.5;
            }

            50% {
                transform: scale(1.05);
                opacity: 1;
            }
        }

        .spin-circle .icon {
            font-size: 3rem;
            color: var(--gold-light);
            transition: all 0.3s;
            filter: drop-shadow(0 2px 8px rgba(191, 149, 63, 0.5));
        }

        .spin-circle .label {
            font-size: 0.85rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.8);
            margin-top: 4px;
            letter-spacing: 1px;
        }

        .spin-circle:hover:not(.disabled) {
            transform: scale(1.06);
            box-shadow:
                0 0 40px rgba(0, 51, 153, 0.5),
                0 0 80px rgba(191, 149, 63, 0.25),
                inset 0 -4px 12px rgba(0, 0, 0, 0.3);
        }

        .spin-circle:active:not(.disabled) {
            transform: scale(0.95);
        }

        .spin-circle.disabled {
            opacity: 0.4;
            cursor: not-allowed;
            filter: grayscale(0.3);
        }

        .spin-circle.disabled::before,
        .spin-circle.disabled::after {
            animation: none;
            opacity: 0.2;
        }

        /* No check-in warning */
        .no-checkin-msg {
            text-align: center;
            margin-top: 12px;
            padding: 10px 18px;
            border-radius: 12px;
            background: rgba(250, 204, 21, 0.08);
            border: 1px solid rgba(250, 204, 21, 0.25);
            color: #fbbf24;
            font-size: 0.82rem;
            font-weight: 500;
            display: none;
            align-items: center;
            justify-content: center;
            gap: 8px;
            animation: fade-in 0.3s ease;
        }

        .no-checkin-msg.show {
            display: flex;
        }

        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(-6px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Spinning state */
        .spin-circle.spinning {
            animation: spin-glow 1.2s ease-in-out infinite;
            pointer-events: none;
        }

        .spin-circle.spinning .icon {
            animation: spin-icon 1s linear infinite;
        }

        .spin-circle.spinning::before {
            animation: orbit-spin 1.5s linear infinite;
            border-color: var(--gold);
            border-style: dashed;
        }

        @keyframes spin-glow {

            0%,
            100% {
                box-shadow: 0 0 30px rgba(0, 51, 153, 0.4), 0 0 60px rgba(191, 149, 63, 0.15);
                border-color: var(--gold);
            }

            50% {
                box-shadow: 0 0 50px rgba(0, 51, 153, 0.7), 0 0 100px rgba(191, 149, 63, 0.4);
                border-color: var(--gold-light);
            }
        }

        @keyframes spin-icon {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        @keyframes orbit-spin {
            0% {
                transform: rotate(0deg) scale(1);
            }

            100% {
                transform: rotate(360deg) scale(1);
            }
        }

        /* ---- Status Bar ---- */
        .status {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 0.82rem;
            font-weight: 500;
            margin-top: 16px;
            transition: all 0.3s;
        }

        .status .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .status.idle {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: var(--text-secondary);
        }

        .status.idle .dot {
            background: rgba(255, 255, 255, 0.3);
        }

        .status.pending {
            background: rgba(250, 204, 21, 0.08);
            border: 1px solid rgba(250, 204, 21, 0.25);
            color: #fbbf24;
        }

        .status.pending .dot {
            background: #fbbf24;
            animation: blink 1s infinite;
        }

        .status.processing {
            background: rgba(59, 130, 246, 0.08);
            border: 1px solid rgba(59, 130, 246, 0.25);
            color: #60a5fa;
        }

        .status.processing .dot {
            background: #60a5fa;
            animation: blink 0.5s infinite;
        }

        .status.done {
            background: rgba(16, 185, 129, 0.08);
            border: 1px solid rgba(16, 185, 129, 0.25);
            color: var(--success);
        }

        .status.done .dot {
            background: var(--success);
        }

        .status.error {
            background: rgba(239, 68, 68, 0.08);
            border: 1px solid rgba(239, 68, 68, 0.25);
            color: var(--danger);
        }

        .status.error .dot {
            background: var(--danger);
        }

        @keyframes blink {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.2;
            }
        }

        /* ---- Winner Card ---- */
        .winner-card {
            display: none;
            text-align: center;
            padding: 20px;
            margin-top: 16px;
            border-radius: 18px;
            background: linear-gradient(145deg, rgba(191, 149, 63, 0.12), rgba(0, 51, 153, 0.08));
            border: 2px solid var(--gold);
        }

        .winner-card.show {
            display: block;
            animation: pop-in 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes pop-in {
            from {
                opacity: 0;
                transform: scale(0.85) translateY(10px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .winner-card .trophy {
            font-size: 2.5rem;
            margin-bottom: 4px;
            color: var(--gold);
        }

        .winner-card .w-label {
            font-size: 0.75rem;
            color: var(--gold);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .winner-card .w-name {
            font-size: 1.4rem;
            font-weight: 800;
            margin: 4px 0;
        }

        .winner-card .w-id {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--gold-light);
        }

        .winner-card .w-prize {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-top: 6px;
        }

        /* ---- Recent List ---- */
        .recent-card {
            background: var(--bg-card);
            border: 1px solid var(--border-gold);
            border-radius: 18px;
            padding: 18px 20px;
            backdrop-filter: blur(20px);
            margin-top: 16px;
        }

        .recent-title {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--gold);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .recent-list {
            display: flex;
            flex-direction: column;
            gap: 6px;
            max-height: 240px;
            overflow-y: auto;
        }

        .recent-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 12px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            transition: all 0.2s;
        }

        .recent-item:hover {
            border-color: var(--border-gold);
            background: rgba(191, 149, 63, 0.04);
        }

        .r-info {
            display: flex;
            flex-direction: column;
            gap: 1px;
        }

        .r-name {
            font-weight: 600;
            font-size: 0.85rem;
        }

        .r-sub {
            font-size: 0.7rem;
            color: var(--text-secondary);
        }

        .r-tag {
            font-size: 0.65rem;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 6px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            white-space: nowrap;
        }

        .empty-msg {
            text-align: center;
            padding: 24px;
            color: var(--text-secondary);
            font-size: 0.8rem;
        }

        .empty-msg i {
            display: block;
            font-size: 1.5rem;
            margin-bottom: 6px;
            opacity: 0.3;
        }

        /* ---- Reset Button ---- */
        .reset-row {
            display: none;
            justify-content: center;
            margin-top: 10px;
        }

        .reset-row.show {
            display: flex;
        }

        .reset-btn {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: var(--danger);
            font-family: 'Kanit', sans-serif;
            font-size: 0.78rem;
            font-weight: 500;
            padding: 8px 20px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .reset-btn:hover {
            background: rgba(239, 68, 68, 0.2);
            border-color: var(--danger);
        }
    </style>
</head>

<body>
    <div class="page">

        <!-- Top Bar -->
        <div class="top-bar">
            <a href="lucky_draw.php" class="back-btn">
                <i class="fas fa-chevron-left"></i> Lucky Draw
            </a>
            <div class="conn-badge">
                <div id="connDot" class="conn-dot"></div>
                <span id="connText">Online</span>
            </div>
        </div>

        <!-- Header -->
        <div class="header">
            <div class="header-icon"><i class="fas fa-gamepad"></i></div>
            <h1>Remote Control</h1>
            <p>Control the lucky draw from this device</p>
        </div>

        <!-- Stats -->
        <div class="stats">
            <div class="stat-box">
                <div id="statEmp" class="stat-num">-</div>
                <div class="stat-lbl">Remaining</div>
            </div>
            <div class="stat-box">
                <div id="statWin" class="stat-num">-</div>
                <div class="stat-lbl">Winners</div>
            </div>
        </div>

        <!-- Prize Select -->
        <div class="prize-card">
            <div class="prize-label"><i class="fas fa-gift"></i> Select Prize</div>
            <select id="prizeSelect" class="prize-select">
                <option value="">Loading...</option>
            </select>
            <div id="prizeQty" class="prize-qty-tag"></div>
        </div>

        <!-- Big Spin Button -->
        <div class="btn-area">
            <div id="spinBtn" class="spin-circle disabled" onclick="sendSpin()">
                <i class="fas fa-play icon"></i>
                <span class="label">SPIN</span>
            </div>
            <div id="noCheckinMsg" class="no-checkin-msg">
                <i class="fas fa-exclamation-triangle"></i>
                <span>ยังไม่มีพนักงาน Check In</span>
            </div>
        </div>

        <!-- Status -->
        <div id="statusBar" class="status idle">
            <div class="dot"></div>
            <span id="statusText">Ready</span>
        </div>

        <!-- Reset Button (shows when stuck) -->
        <div id="resetRow" class="reset-row">
            <button class="reset-btn" onclick="clearCommands()">
                <i class="fas fa-undo"></i> Reset stuck commands
            </button>
        </div>

        <!-- Winner -->
        <div id="winnerCard" class="winner-card">
            <div class="trophy"><i class="fas fa-trophy"></i></div>
            <div class="w-label">Winner</div>
            <div id="wName" class="w-name">-</div>
            <div id="wId" class="w-id">-</div>
            <div id="wPrize" class="w-prize">-</div>
        </div>

        <!-- Recent Winners -->
        <div class="recent-card">
            <div class="recent-title"><i class="fas fa-crown"></i> Recent Winners</div>
            <div id="recentList" class="recent-list">
                <div class="empty-msg"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
            </div>
        </div>

    </div>

    <script>
        const prizeSelect = document.getElementById('prizeSelect');
        const spinBtn = document.getElementById('spinBtn');
        const spinIcon = spinBtn.querySelector('.icon');
        const spinLabel = spinBtn.querySelector('.label');
        const statusBar = document.getElementById('statusBar');
        const statusText = document.getElementById('statusText');
        const winnerCard = document.getElementById('winnerCard');

        let currentCmdId = null;
        let pollInterval = null;
        let busy = false;
        let hasCheckedIn = false; // track if any employee has checked in

        // ---- Load Data ----
        async function loadData() {
            try {
                const res = await fetch('?action=get_remote_data');
                const data = await res.json();

                document.getElementById('statEmp').textContent = data.emp_count || 0;
                document.getElementById('statWin').textContent = data.winners ? data.winners.length : 0;

                // Check if any employees have checked in
                hasCheckedIn = (data.total_emp_count || 0) > 0;
                const noCheckinEl = document.getElementById('noCheckinMsg');
                if (!hasCheckedIn) {
                    noCheckinEl.classList.add('show');
                    disableBtn();
                } else {
                    noCheckinEl.classList.remove('show');
                }

                if (data.prizes && data.prizes.length > 0) {
                    prizeSelect.innerHTML = data.prizes.map(p => {
                        const dis = p.qty <= 0 ? 'disabled' : '';
                        return `<option value="${p.name}" data-qty="${p.qty}" ${dis}>${p.name} (${p.qty})</option>`;
                    }).join('');
                    updateQty();
                    if (!busy && hasCheckedIn) enableBtn();
                } else {
                    prizeSelect.innerHTML = '<option value="">No prizes</option>';
                }

                renderRecent(data.winners || []);
                setConn(true);
            } catch (e) {
                console.error(e);
                setConn(false);
            }
        }

        function updateQty() {
            const opt = prizeSelect.options[prizeSelect.selectedIndex];
            const tag = document.getElementById('prizeQty');
            if (!opt || !opt.value) {
                tag.className = 'prize-qty-tag';
                return;
            }
            const qty = parseInt(opt.dataset.qty) || 0;
            if (qty > 0) {
                tag.className = 'prize-qty-tag ok';
                tag.innerHTML = '<i class="fas fa-check-circle"></i> ' + qty + ' remaining';
                if (!busy && hasCheckedIn) enableBtn();
            } else {
                tag.className = 'prize-qty-tag empty';
                tag.innerHTML = '<i class="fas fa-times-circle"></i> Out of stock';
                disableBtn();
            }
        }
        prizeSelect.addEventListener('change', updateQty);

        function enableBtn() {
            spinBtn.classList.remove('disabled');
        }

        function disableBtn() {
            spinBtn.classList.add('disabled');
        }

        function renderRecent(winners) {
            const el = document.getElementById('recentList');
            if (!winners.length) {
                el.innerHTML = '<div class="empty-msg"><i class="fas fa-trophy"></i> No winners yet</div>';
                return;
            }
            el.innerHTML = winners.map(w => `
                <div class="recent-item">
                    <div class="r-info">
                        <span class="r-name">${w.EMP_NAME}</span>
                        <span class="r-sub">${w.EMP_ID}${w.PLANT ? ' - ' + w.PLANT : ''}</span>
                    </div>
                    <span class="r-tag">${w.PRIZE_NAME}</span>
                </div>
            `).join('');
        }

        function setConn(ok) {
            document.getElementById('connDot').className = 'conn-dot' + (ok ? '' : ' off');
            document.getElementById('connText').textContent = ok ? 'Online' : 'Offline';
        }

        function setStatus(type, msg) {
            statusBar.className = 'status ' + type;
            statusText.textContent = msg;
        }

        // ---- Send Spin ----
        async function sendSpin() {
            if (busy || spinBtn.classList.contains('disabled')) return;

            const prize = prizeSelect.value;
            if (!prize) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Select a prize',
                    text: 'Please select a prize first',
                    confirmButtonColor: '#bf953f',
                    background: '#1a1a2e',
                    color: '#fff'
                });
                return;
            }

            busy = true;
            spinBtn.classList.add('spinning');
            spinIcon.className = 'fas fa-sync-alt icon';
            spinLabel.textContent = 'SPINNING';
            winnerCard.classList.remove('show');
            setStatus('pending', 'Sending command to display...');

            try {
                const res = await fetch('lucky_draw.php?action=remote_spin', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        prize
                    })
                });
                const data = await res.json();

                if (data.success) {
                    currentCmdId = data.cmd_id;
                    setStatus('processing', 'Display is spinning...');
                    pollInterval = setInterval(pollResult, 2000);
                } else {
                    setStatus('error', data.message);
                    resetBtn();
                }
            } catch (e) {
                setStatus('error', 'Connection failed');
                resetBtn();
            }
        }

        // ---- Poll Result ----
        async function pollResult() {
            if (!currentCmdId) return;
            try {
                const res = await fetch('lucky_draw.php?action=command_result&cmd_id=' + currentCmdId);
                const data = await res.json();

                if (data.success && data.status === 'DONE' && data.result) {
                    clearInterval(pollInterval);
                    pollInterval = null;

                    document.getElementById('wName').textContent = data.result.name || '-';
                    document.getElementById('wId').textContent = data.result.id || '-';
                    document.getElementById('wPrize').textContent = data.result.prize || '-';
                    winnerCard.classList.add('show');

                    setStatus('done', 'Winner found!');
                    resetBtn();
                    currentCmdId = null;
                    setTimeout(loadData, 1500);
                }
            } catch (e) {
                console.log('poll error:', e);
            }
        }

        function resetBtn() {
            busy = false;
            spinBtn.classList.remove('spinning');
            spinIcon.className = 'fas fa-play icon';
            spinLabel.textContent = 'SPIN';
            updateQty();
        }

        // ---- Clear stuck commands ----
        async function clearCommands() {
            try {
                const res = await fetch('lucky_draw.php?action=clear_commands', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    setStatus('idle', 'Ready');
                    document.getElementById('resetRow').classList.remove('show');
                    resetBtn();
                    if (pollInterval) {
                        clearInterval(pollInterval);
                        pollInterval = null;
                    }
                    currentCmdId = null;
                    loadData();
                }
            } catch (e) {
                console.error('clear error:', e);
            }
        }

        // Override setStatus to show/hide reset button
        const _origSetStatus = setStatus;
        setStatus = function(type, msg) {
            _origSetStatus(type, msg);
            document.getElementById('resetRow').classList.toggle('show', type === 'error');
        };

        // ---- Init ----
        loadData();
        setInterval(() => {
            if (!busy) loadData();
        }, 10000);
    </script>
</body>

</html>