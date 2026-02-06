<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Check-in | SAB 40th Anniversary</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Kanit', sans-serif;
            background: #0a0a1a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(0.5rem, 5vw, 2rem);
            position: relative;
            overflow: hidden;
        }

        /* Modern Mesh Gradient Background */
        .bg-gradient {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                radial-gradient(ellipse at 20% 0%, rgba(120, 0, 255, 0.4) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 0%, rgba(0, 112, 255, 0.3) 0%, transparent 50%),
                radial-gradient(ellipse at 40% 100%, rgba(255, 0, 128, 0.3) 0%, transparent 50%),
                radial-gradient(ellipse at 100% 50%, rgba(0, 200, 255, 0.2) 0%, transparent 40%),
                linear-gradient(180deg, #0a0a1a 0%, #1a1a3a 100%);
            z-index: 0;
        }

        /* Animated Floating Orbs */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(60px);
            animation: orbFloat 20s ease-in-out infinite;
            z-index: 1;
        }

        .orb-1 {
            width: 400px;
            height: 400px;
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.5), rgba(59, 130, 246, 0.3));
            top: -10%;
            left: -10%;
            animation-delay: 0s;
        }

        .orb-2 {
            width: 350px;
            height: 350px;
            background: linear-gradient(135deg, rgba(236, 72, 153, 0.4), rgba(239, 68, 68, 0.3));
            bottom: -10%;
            right: -5%;
            animation-delay: -7s;
        }

        .orb-3 {
            width: 250px;
            height: 250px;
            background: linear-gradient(135deg, rgba(34, 211, 238, 0.4), rgba(16, 185, 129, 0.3));
            top: 50%;
            right: 10%;
            animation-delay: -14s;
        }

        @keyframes orbFloat {

            0%,
            100% {
                transform: translate(0, 0) scale(1);
            }

            25% {
                transform: translate(30px, -30px) scale(1.05);
            }

            50% {
                transform: translate(-20px, 20px) scale(0.95);
            }

            75% {
                transform: translate(-30px, -20px) scale(1.02);
            }
        }

        /* Grid Pattern Overlay */
        .grid-pattern {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: 2;
        }

        /* Background Logo Watermark */
        .bg-logo-watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 80vmin;
            max-width: 600px;
            height: auto;
            opacity: 0.05;
            pointer-events: none;
            z-index: 1;
        }

        .container {
            max-width: 420px;
            width: 100%;
            position: relative;
            z-index: 10;
            padding: 0 1rem;
        }

        .checkin-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 2rem 1.75rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
        }

        @keyframes cardFloat {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        /* Header (Compact) */
        .header-section {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .gold-badge {
            display: inline-block;
            background: linear-gradient(135deg, #bf953f 0%, #fcf6ba 50%, #b38728 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1;
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {

            0%,
            100% {
                filter: brightness(1);
            }

            50% {
                filter: brightness(1.3);
            }
        }

        .subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
            font-weight: 400;
            margin-top: 0.5rem;
            letter-spacing: 0.5px;
        }

        /* Logo Image - Prominent Style */
        .logo-img {
            max-width: 220px;
            height: auto;
            margin-bottom: 0.75rem;
            filter: drop-shadow(0 0 20px rgba(191, 149, 63, 0.6)) drop-shadow(0 0 40px rgba(191, 149, 63, 0.3)) drop-shadow(0 8px 20px rgba(0, 0, 0, 0.4));
            animation: logoGlow 3s ease-in-out infinite;
            transition: transform 0.3s ease;
        }

        .logo-img:hover {
            transform: scale(1.05);
        }

        @keyframes logoGlow {

            0%,
            100% {
                filter: drop-shadow(0 0 20px rgba(191, 149, 63, 0.6)) drop-shadow(0 0 40px rgba(191, 149, 63, 0.3)) drop-shadow(0 8px 20px rgba(0, 0, 0, 0.4));
            }

            50% {
                filter: drop-shadow(0 0 30px rgba(252, 246, 186, 0.8)) drop-shadow(0 0 60px rgba(191, 149, 63, 0.5)) drop-shadow(0 8px 20px rgba(0, 0, 0, 0.4));
            }
        }

        /* Input Section */
        .input-section {
            margin-bottom: 1.5rem;
        }

        .input-label {
            display: block;
            color: rgba(255, 255, 255, 0.9);
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 0.75rem;
            text-align: center;
        }

        .input-label i {
            margin-right: 0.5rem;
            color: #bf953f;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .emp-input {
            width: 100%;
            padding: 1rem 1.25rem;
            font-family: 'Kanit', sans-serif;
            font-size: 1.25rem;
            font-weight: 600;
            color: #1a1a2e;
            background: rgba(255, 255, 255, 0.95);
            border: 2px solid transparent;
            border-radius: 16px;
            outline: none;
            transition: all 0.3s ease;
            text-align: center;
            letter-spacing: 3px;
        }

        .emp-input::placeholder {
            color: #999;
            font-weight: 400;
            letter-spacing: 0;
            font-size: 0.95rem;
        }

        .emp-input:focus {
            background: white;
            border-color: #8b5cf6;
            box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.3),
                0 10px 30px rgba(0, 0, 0, 0.2);
        }

        /* Checkin Button */
        .checkin-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            width: 100%;
            padding: 0.9rem 1.5rem;
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
            color: white;
            border: none;
            border-radius: 14px;
            font-family: 'Kanit', sans-serif;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(139, 92, 246, 0.4);
            margin-bottom: 0.75rem;
        }

        .checkin-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(139, 92, 246, 0.5);
            background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%);
        }

        .checkin-btn:active {
            transform: translateY(0);
        }

        .checkin-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .checkin-btn i {
            font-size: 1.1em;
        }

        /* Home Button */
        .home-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            padding: 0.8rem 1.25rem;
            background: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 14px;
            font-size: 0.95rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .home-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            transform: translateY(-2px);
        }

        .home-btn:active {
            transform: translateY(0);
        }

        /* Status indicator */
        .status-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 1.5rem;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, rgba(0, 51, 153, 0.1), rgba(0, 85, 204, 0.1));
            border-radius: 50px;
            color: #003399;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .status-dot {
            width: 10px;
            height: 10px;
            background: #4ade80;
            border-radius: 50%;
            animation: pulse 2s infinite;
            box-shadow: 0 0 10px #4ade80;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.5;
                transform: scale(1.2);
            }
        }

        /* Loading spinner */
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

        /* SweetAlert Custom */
        .swal2-popup {
            font-family: 'Kanit', sans-serif !important;
            border-radius: 30px !important;
            padding: 30px !important;
        }

        .swal2-success .swal2-success-ring {
            border-color: rgba(0, 51, 153, 0.3) !important;
        }

        .swal2-success .swal2-success-line-tip,
        .swal2-success .swal2-success-line-long {
            background-color: #003399 !important;
        }

        /* Responsive */
        @media (max-width: 400px) {
            .checkin-card {
                padding: 1.5rem;
                border-radius: 1.5rem;
            }

            .emp-input {
                padding: 0.875rem 0.875rem 0.875rem 3rem;
            }
        }

        @media (min-width: 768px) {
            .container {
                max-width: 450px;
            }

            .checkin-card {
                padding: 3rem;
            }
        }

        /* Touch device optimizations */
        @media (hover: none) and (pointer: coarse) {

            .checkin-btn,
            .home-btn {
                min-height: 52px;
            }
        }
    </style>
</head>

<body>
    <!-- Modern Background Elements -->
    <div class="bg-gradient"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <div class="grid-pattern"></div>

    <!-- Logo Watermark on Background -->
    <img src="logo/logo.png" alt="" class="bg-logo-watermark">

    <div class="container">
        <div class="checkin-card">
            <!-- Header Logo -->
            <div class="header-section">
                <img src="logo/logo.png" alt="40th Anniversary" class="logo-img">
                <p class="subtitle">เช็คอินเข้างาน • 28 ก.พ. 2569</p>
            </div>

            <div class="input-section">
                <label class="input-label" for="empId">
                    <i class="fas fa-id-card"></i> กรอกรหัสพนักงาน
                </label>
                <div class="input-wrapper">
                    <input type="text" id="empId" class="emp-input" placeholder="กรุณากรอกรหัสพนักงาน" autocomplete="off"
                        inputmode="text" maxlength="10" autofocus pattern="[A-Za-z0-9]*"
                        style="text-transform: uppercase;">
                </div>
            </div>

            <button type="button" id="checkinBtn" class="checkin-btn">
                <i class="fas fa-sign-in-alt"></i>
                <span>เช็คอินเข้างาน</span>
            </button>

            <a href="index.php" class="home-btn">
                <i class="fas fa-home"></i>
                <span>กลับหน้าหลัก</span>
            </a>
        </div>
    </div>

    <script>
        const empInput = document.getElementById('empId');
        const checkinBtn = document.getElementById('checkinBtn');
        let isProcessing = false;

        // Focus input on page load
        window.addEventListener('load', () => {
            empInput.focus();
        });

        // Filter input: allow only A-Z, 0-9 and convert to uppercase
        empInput.addEventListener('input', (e) => {
            // Remove any characters that are not A-Z or 0-9
            let value = e.target.value.replace(/[^A-Za-z0-9]/g, '');
            // Convert to uppercase
            e.target.value = value.toUpperCase();
        });

        // Prevent paste of invalid characters
        empInput.addEventListener('paste', (e) => {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            // Filter and uppercase pasted content
            const filteredText = pastedText.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
            // Insert at cursor position
            const start = e.target.selectionStart;
            const end = e.target.selectionEnd;
            const currentValue = e.target.value;
            const newValue = currentValue.substring(0, start) + filteredText + currentValue.substring(end);
            e.target.value = newValue.substring(0, 10); // respect maxlength
        });

        // Handle Enter key
        empInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                doCheckin();
            }
        });

        // Handle button click
        checkinBtn.addEventListener('click', doCheckin);

        function doCheckin() {
            if (isProcessing) return;

            // Convert to uppercase and trim
            const empId = empInput.value.trim().toUpperCase();

            if (!empId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'กรุณากรอกรหัสพนักงาน',
                    text: 'โปรดระบุรหัสพนักงานของคุณ',
                    timer: 500,
                    showConfirmButton: false
                });
                empInput.focus();
                return;
            }

            isProcessing = true;
            checkinBtn.disabled = true;
            checkinBtn.innerHTML = '<div class="spinner"></div><span>กำลังตรวจสอบ...</span>';

            let fd = new FormData();
            fd.append('qr', empId); // ใช้ 'qr' เพื่อให้ API เดิมใช้งานได้

            fetch('checkin_api.php', {
                    method: 'POST',
                    body: fd
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: '✅ เช็คอินสำเร็จ!',
                            html: `<div style="font-size: 1.2rem; color: #003399; font-weight: 600;"><b>${data.name}</b></div>
                                   <div style="color: #666; margin-top: 10px; font-size: 1rem;">โรงงาน: ${data.plant}</div>
                                   <div style="color: #bf953f; margin-top: 15px; font-size: 1.3rem; font-weight: 700;">🪑 โต๊ะ: ${data.table_code || '-'}</div>`,
                            showConfirmButton: true,
                            confirmButtonText: 'ปิด',
                            confirmButtonColor: '#8b5cf6',
                            background: '#fff',
                            customClass: {
                                popup: 'animated-popup'
                            }
                        });
                        empInput.value = '';
                    } else if (data.status === 'already') {
                        Swal.fire({
                            icon: 'info',
                            title: 'เช็คอินแล้ว',
                            html: `<div style="color: #666;">คุณ <b>${data.name}</b><br>ได้ลงทะเบียนเข้างานแล้ว</div>`,
                            timer: 500,
                            showConfirmButton: false
                        });
                        empInput.value = ''; // ล้างค่าเพื่อพิมพ์รหัสถัดไป
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'ไม่สำเร็จ',
                            text: data.message || 'กรุณาลองใหม่อีกครั้ง',
                            timer: 500,
                            showConfirmButton: false
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
                        timer: 500,
                        showConfirmButton: false
                    });
                    resetButton();
                    empInput.focus();
                });
        }

        function resetButton() {
            isProcessing = false;
            checkinBtn.disabled = false;
            checkinBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i><span>เช็คอินเข้างาน</span>';
        }
    </script>
</body>

</html>