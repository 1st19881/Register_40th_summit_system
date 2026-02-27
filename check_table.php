<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เช็คหมายเลขโต๊ะ | SAB 40th Anniversary</title>
    <meta name="description" content="ตรวจสอบหมายเลขโต๊ะสำหรับงาน SAB 40th Anniversary">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Kanit', sans-serif;
            background: #0b1121;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(0.5rem, 4vw, 2rem);
            position: relative;
            overflow-x: hidden;
        }

        /* ===== Deep Midnight Background ===== */
        .bg-scene {
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse at 30% 20%, rgba(191, 149, 63, 0.12) 0%, transparent 50%),
                radial-gradient(ellipse at 70% 80%, rgba(191, 149, 63, 0.08) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 50%, rgba(15, 23, 42, 0.9) 0%, transparent 80%),
                linear-gradient(175deg, #0b1121 0%, #111d35 40%, #0d1526 100%);
            z-index: 0;
        }

        /* Floating Gold Particles */
        .particles {
            position: fixed;
            inset: 0;
            z-index: 1;
            pointer-events: none;
            overflow: hidden;
        }

        .particle {
            position: absolute;
            width: 3px;
            height: 3px;
            background: radial-gradient(circle, rgba(252, 246, 186, 0.9), rgba(191, 149, 63, 0.4));
            border-radius: 50%;
            animation: particleFloat linear infinite;
        }

        @keyframes particleFloat {
            0% {
                transform: translateY(100vh) scale(0);
                opacity: 0;
            }

            10% {
                opacity: 1;
            }

            90% {
                opacity: 1;
            }

            100% {
                transform: translateY(-10vh) scale(1);
                opacity: 0;
            }
        }

        /* Decorative gold lines */
        .deco-line {
            position: fixed;
            background: linear-gradient(90deg, transparent, rgba(191, 149, 63, 0.15), transparent);
            height: 1px;
            width: 100%;
            z-index: 1;
        }

        .deco-line-1 {
            top: 15%;
            animation: decoShift 12s ease-in-out infinite;
        }

        .deco-line-2 {
            top: 85%;
            animation: decoShift 12s ease-in-out infinite reverse;
        }

        @keyframes decoShift {

            0%,
            100% {
                transform: translateX(-20%);
                opacity: 0.5;
            }

            50% {
                transform: translateX(20%);
                opacity: 1;
            }
        }

        /* ===== Main Container ===== */
        .main-container {
            max-width: 440px;
            width: 100%;
            position: relative;
            z-index: 10;
        }

        /* ===== Header ===== */
        .page-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .logo-wrap {
            margin-bottom: 0.5rem;
        }

        .logo-wrap img {
            max-width: 180px;
            height: auto;
            filter: drop-shadow(0 0 25px rgba(191, 149, 63, 0.5));
            animation: logoPulse 4s ease-in-out infinite;
        }

        @keyframes logoPulse {

            0%,
            100% {
                filter: drop-shadow(0 0 25px rgba(191, 149, 63, 0.5));
            }

            50% {
                filter: drop-shadow(0 0 40px rgba(252, 246, 186, 0.7));
            }
        }

        .page-header h1 {
            font-size: 1.1rem;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.6);
            letter-spacing: 4px;
            text-transform: uppercase;
        }

        /* ===== Search Card ===== */
        .search-card {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.07), rgba(255, 255, 255, 0.03));
            border: 1px solid rgba(191, 149, 63, 0.25);
            border-radius: 20px;
            padding: 2rem 1.75rem;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            box-shadow:
                0 20px 60px rgba(0, 0, 0, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        .search-title {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .search-title .icon-circle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background: linear-gradient(135deg, rgba(191, 149, 63, 0.2), rgba(191, 149, 63, 0.05));
            border: 1px solid rgba(191, 149, 63, 0.3);
            margin-bottom: 0.75rem;
        }

        .search-title .icon-circle i {
            font-size: 1.5rem;
            background: linear-gradient(135deg, #bf953f, #fcf6ba);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .search-title h2 {
            font-size: 1.3rem;
            font-weight: 600;
            color: #fff;
        }

        .search-title p {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.5);
            margin-top: 0.25rem;
        }

        /* Input */
        .input-group {
            margin-bottom: 1.25rem;
        }

        .input-field {
            width: 100%;
            padding: 1rem 1.25rem;
            font-family: 'Kanit', sans-serif;
            font-size: 1.3rem;
            font-weight: 700;
            color: #fff;
            background: rgba(255, 255, 255, 0.08);
            border: 2px solid rgba(255, 255, 255, 0.12);
            border-radius: 14px;
            outline: none;
            text-align: center;
            letter-spacing: 4px;
            transition: all 0.3s ease;
            caret-color: #bf953f;
        }

        .input-field::placeholder {
            color: rgba(255, 255, 255, 0.25);
            font-weight: 400;
            letter-spacing: 0;
            font-size: 0.9rem;
        }

        .input-field:focus {
            border-color: rgba(191, 149, 63, 0.6);
            background: rgba(255, 255, 255, 0.12);
            box-shadow: 0 0 0 4px rgba(191, 149, 63, 0.15), 0 0 30px rgba(191, 149, 63, 0.1);
        }

        /* Buttons */
        .btn-search {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            width: 100%;
            padding: 0.9rem;
            background: linear-gradient(135deg, #bf953f 0%, #b38728 50%, #aa771c 100%);
            color: #fff;
            border: none;
            border-radius: 14px;
            font-family: 'Kanit', sans-serif;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 8px 25px rgba(191, 149, 63, 0.3);
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }

        .btn-search::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-search:hover::before {
            left: 100%;
        }

        .btn-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(191, 149, 63, 0.45);
        }

        .btn-search:active {
            transform: translateY(0);
        }

        .btn-search:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .btn-home {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            padding: 0.75rem;
            margin-top: 0.75rem;
            background: transparent;
            color: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            font-family: 'Kanit', sans-serif;
            font-size: 0.9rem;
            font-weight: 400;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-home:hover {
            color: rgba(255, 255, 255, 0.8);
            border-color: rgba(255, 255, 255, 0.25);
            background: rgba(255, 255, 255, 0.05);
        }

        /* ===== TICKET RESULT CARD ===== */
        .ticket-wrapper {
            display: none;
            animation: ticketReveal 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .ticket-wrapper.show {
            display: block;
        }

        @keyframes ticketReveal {
            from {
                opacity: 0;
                transform: translateY(40px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .ticket {
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow:
                0 25px 60px rgba(0, 0, 0, 0.4),
                0 0 0 1px rgba(255, 255, 255, 0.1);
            position: relative;
        }

        /* Ticket Top - Status */
        .ticket-header {
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .ticket-header.status-done {
            background: linear-gradient(135deg, #059669, #10b981);
            color: #fff;
        }

        .ticket-header.status-pending {
            background: linear-gradient(135deg, #d97706, #f59e0b);
            color: #fff;
        }

        .ticket-header .status-left {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .ticket-header .status-right {
            font-size: 0.75rem;
            opacity: 0.8;
            font-weight: 400;
        }

        /* Ticket Body - Employee Info */
        .ticket-body {
            padding: 24px;
            text-align: center;
        }

        .emp-avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            font-size: 1.6rem;
            color: #4338ca;
        }

        .emp-name {
            font-size: 1.6rem;
            font-weight: 700;
            color: #1e293b;
            line-height: 1.3;
            margin-bottom: 4px;
        }

        .emp-detail {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.95rem;
            color: #64748b;
            font-weight: 500;
            background: #f1f5f9;
            padding: 4px 14px;
            border-radius: 50px;
        }

        .emp-detail i {
            color: #94a3b8;
            font-size: 0.85rem;
        }

        /* Dotted Tear Line */
        .ticket-tear {
            position: relative;
            height: 32px;
            display: flex;
            align-items: center;
        }

        .ticket-tear::before {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            border-top: 2px dashed #e2e8f0;
        }

        .ticket-tear .notch {
            position: absolute;
            width: 32px;
            height: 32px;
            background: #0b1121;
            border-radius: 50%;
        }

        .ticket-tear .notch-left {
            left: -16px;
        }

        .ticket-tear .notch-right {
            right: -16px;
        }

        /* Ticket Bottom - Table Number */
        .ticket-table {
            padding: 20px 24px 28px;
            text-align: center;
            background: linear-gradient(180deg, #fffbeb 0%, #fef3c7 100%);
        }

        .table-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            color: #92400e;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 8px;
        }

        .table-badge i {
            font-size: 0.75rem;
        }

        .table-num {
            font-size: 5rem;
            font-weight: 900;
            color: #b45309;
            line-height: 1;
            text-shadow: 2px 3px 0 rgba(180, 83, 9, 0.1);
            animation: numBounce 0.7s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes numBounce {
            0% {
                transform: scale(0);
                opacity: 0;
            }

            60% {
                transform: scale(1.15);
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .table-zone {
            font-size: 0.85rem;
            color: #a16207;
            font-weight: 500;
            margin-top: 6px;
        }

        /* Ticket Footer */
        .ticket-footer {
            padding: 14px 24px;
            background: #fefce8;
            text-align: center;
            font-size: 0.8rem;
            color: #a16207;
            border-top: 1px solid rgba(217, 119, 6, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .ticket-footer i {
            font-size: 0.9rem;
        }

        /* Action Buttons Under Ticket */
        .ticket-actions {
            margin-top: 1rem;
            display: flex;
            gap: 0.75rem;
        }

        .btn-again {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.8rem;
            background: rgba(99, 102, 241, 0.15);
            color: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(99, 102, 241, 0.3);
            border-radius: 12px;
            font-family: 'Kanit', sans-serif;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-again:hover {
            background: rgba(99, 102, 241, 0.25);
            border-color: rgba(99, 102, 241, 0.5);
            transform: translateY(-2px);
        }

        .btn-back {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.8rem;
            background: rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            font-family: 'Kanit', sans-serif;
            font-size: 0.9rem;
            font-weight: 400;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            color: rgba(255, 255, 255, 0.8);
            border-color: rgba(255, 255, 255, 0.2);
        }

        /* ===== Confetti ===== */
        .confetti-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 100;
            overflow: hidden;
        }

        .confetti-piece {
            position: absolute;
            top: -10px;
            animation: confettiFall linear forwards;
        }

        @keyframes confettiFall {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 1;
            }

            100% {
                transform: translateY(100vh) rotate(720deg);
                opacity: 0;
            }
        }

        /* ===== Spinner ===== */
        .spinner {
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* ===== Responsive ===== */
        @media (max-width: 400px) {
            .search-card {
                padding: 1.5rem 1.25rem;
                border-radius: 16px;
            }

            .ticket-body {
                padding: 20px 16px;
            }

            .emp-name {
                font-size: 1.3rem;
            }

            .table-num {
                font-size: 4rem;
            }

            .ticket-table {
                padding: 16px 16px 24px;
            }
        }

        @media (min-width: 768px) {
            .main-container {
                max-width: 460px;
            }

            .search-card {
                padding: 2.5rem 2.25rem;
            }
        }

        @media (hover: none) and (pointer: coarse) {

            .btn-search,
            .btn-again,
            .btn-back,
            .btn-home {
                min-height: 52px;
            }
        }
    </style>
</head>

<body>
    <!-- Background -->
    <div class="bg-scene"></div>
    <div class="deco-line deco-line-1"></div>
    <div class="deco-line deco-line-2"></div>
    <div class="particles" id="particles"></div>

    <div class="main-container">
        <!-- Header -->
        <div class="page-header">
            <div class="logo-wrap">
                <img src="logo/logo.png" alt="40th Anniversary">
            </div>
            <h1>Check Your Table</h1>
        </div>

        <!-- Search Section -->
        <div id="searchSection">
            <div class="search-card">
                <div class="search-title">
                    <div class="icon-circle">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h2>ค้นหาหมายเลขโต๊ะ</h2>
                    <p>กรอกรหัสพนักงานเพื่อตรวจสอบ</p>
                </div>

                <div class="input-group">
                    <input type="text" id="empId" class="input-field" placeholder="กรอกรหัสพนักงาน"
                        autocomplete="off" inputmode="text" maxlength="10" autofocus pattern="[A-Za-z0-9]*"
                        style="text-transform: uppercase;">
                </div>

                <button type="button" id="searchBtn" class="btn-search">
                    <i class="fas fa-search"></i>
                    <span>ค้นหาโต๊ะ</span>
                </button>

            </div>
        </div>

        <!-- Ticket Result -->
        <div id="ticketWrapper" class="ticket-wrapper">
            <div class="ticket">
                <!-- Status Header -->
                <div id="ticketHeader" class="ticket-header status-done">
                    <div class="status-left">
                        <i class="fas fa-check-circle"></i>
                        <span id="statusText">เช็คอินแล้ว</span>
                    </div>
                    <div class="status-right">
                        <span>28 ก.พ. 2569</span>
                    </div>
                </div>

                <!-- Employee Info -->
                <div class="ticket-body">
                    <div class="emp-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div id="resultName" class="emp-name">-</div>
                    <div class="emp-detail">
                        <i class="fas fa-building"></i>
                        <span id="resultPlant">-</span>
                    </div>
                </div>

                <!-- Tear Line -->
                <div class="ticket-tear">
                    <div class="notch notch-left"></div>
                    <div class="notch notch-right"></div>
                </div>

                <!-- Table Number -->
                <div class="ticket-table">
                    <div class="table-badge">
                        <i class="fas fa-chair"></i>
                        หมายเลขโต๊ะ
                    </div>
                    <div id="resultTable" class="table-num">-</div>
                    <div class="table-zone">SAB 40th Anniversary Summit</div>
                </div>

                <!-- Footer -->
                <div class="ticket-footer">
                    <i class="fas fa-camera"></i>
                    <span>กรุณาแคปหน้าจอเพื่อยืนยัน</span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="ticket-actions">
                <button type="button" id="resetBtn" class="btn-again">
                    <i class="fas fa-redo-alt"></i>
                    <span>ค้นหาใหม่</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Confetti Container -->
    <div id="confettiContainer" class="confetti-container"></div>

    <script>
        // ===== Particles =====
        (function initParticles() {
            const container = document.getElementById('particles');
            const count = 25;
            for (let i = 0; i < count; i++) {
                const p = document.createElement('div');
                p.className = 'particle';
                p.style.left = Math.random() * 100 + '%';
                p.style.width = p.style.height = (Math.random() * 3 + 1.5) + 'px';
                p.style.animationDuration = (Math.random() * 12 + 8) + 's';
                p.style.animationDelay = (Math.random() * 10) + 's';
                container.appendChild(p);
            }
        })();

        // ===== Confetti Effect =====
        function launchConfetti() {
            const container = document.getElementById('confettiContainer');
            container.innerHTML = '';
            const colors = ['#bf953f', '#fcf6ba', '#b38728', '#4f46e5', '#ec4899', '#10b981', '#f59e0b', '#ef4444'];
            const shapes = ['square', 'circle'];

            for (let i = 0; i < 60; i++) {
                const piece = document.createElement('div');
                piece.className = 'confetti-piece';
                const color = colors[Math.floor(Math.random() * colors.length)];
                const shape = shapes[Math.floor(Math.random() * shapes.length)];
                const size = Math.random() * 8 + 5;

                piece.style.left = Math.random() * 100 + '%';
                piece.style.width = size + 'px';
                piece.style.height = size + (shape === 'square' ? 0 : size * 0.6) + 'px';
                piece.style.background = color;
                piece.style.borderRadius = shape === 'circle' ? '50%' : '2px';
                piece.style.animationDuration = (Math.random() * 2 + 2) + 's';
                piece.style.animationDelay = (Math.random() * 0.8) + 's';

                container.appendChild(piece);
            }

            setTimeout(() => container.innerHTML = '', 4000);
        }

        // ===== DOM =====
        const empInput = document.getElementById('empId');
        const searchBtn = document.getElementById('searchBtn');
        const resetBtn = document.getElementById('resetBtn');
        const ticketWrapper = document.getElementById('ticketWrapper');
        const searchSection = document.getElementById('searchSection');
        let isProcessing = false;

        window.addEventListener('load', () => empInput.focus());

        // Input filter
        empInput.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
        });

        // Paste filter
        empInput.addEventListener('paste', (e) => {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text');
            const filtered = text.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
            const s = e.target.selectionStart;
            const end = e.target.selectionEnd;
            const cur = e.target.value;
            e.target.value = (cur.substring(0, s) + filtered + cur.substring(end)).substring(0, 10);
        });

        // Enter key
        empInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                doSearch();
            }
        });

        searchBtn.addEventListener('click', doSearch);

        // Reset
        resetBtn.addEventListener('click', () => {
            ticketWrapper.classList.remove('show');
            searchSection.style.display = 'block';
            empInput.value = '';
            setTimeout(() => empInput.focus(), 100);
        });

        function doSearch() {
            if (isProcessing) return;

            const empId = empInput.value.trim().toUpperCase();

            if (!empId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'กรุณากรอกรหัสพนักงาน',
                    text: 'โปรดระบุรหัสพนักงานของคุณ',
                    timer: 1500,
                    showConfirmButton: false
                });
                empInput.focus();
                return;
            }

            isProcessing = true;
            searchBtn.disabled = true;
            searchBtn.innerHTML = '<div class="spinner"></div><span>กำลังค้นหา...</span>';

            let fd = new FormData();
            fd.append('emp_id', empId);

            fetch('check_table_api.php', {
                    method: 'POST',
                    body: fd
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Populate ticket
                        document.getElementById('resultName').textContent = data.name;
                        document.getElementById('resultPlant').textContent = data.plant;
                        document.getElementById('resultTable').textContent = data.table_code || '-';

                        // Status
                        const header = document.getElementById('ticketHeader');
                        const statusText = document.getElementById('statusText');
                        const ticketFooter = document.querySelector('.ticket-footer');
                        if (data.is_checked_in) {
                            header.className = 'ticket-header status-done';
                            statusText.innerHTML = '<i class="fas fa-check-circle"></i> เช็คอินแล้ว';
                            ticketFooter.style.display = 'flex';
                        } else {
                            header.className = 'ticket-header status-pending';
                            statusText.innerHTML = '<i class="fas fa-clock"></i> ยังไม่ได้เช็คอิน';
                            ticketFooter.style.display = 'none';
                        }

                        // Show ticket
                        searchSection.style.display = 'none';
                        ticketWrapper.classList.add('show');

                        // Confetti!
                        launchConfetti();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'ไม่พบข้อมูล',
                            text: data.message || 'กรุณาตรวจสอบรหัสพนักงานอีกครั้ง',
                            confirmButtonText: 'ตกลง'
                        });
                    }

                    resetButton();
                    empInput.focus();
                })
                .catch(err => {
                    console.error('Error:', err);
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถเชื่อมต่อ Server ได้',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    resetButton();
                    empInput.focus();
                });
        }

        function resetButton() {
            isProcessing = false;
            searchBtn.disabled = false;
            searchBtn.innerHTML = '<i class="fas fa-search"></i><span>ค้นหาโต๊ะ</span>';
        }
    </script>
</body>

</html>