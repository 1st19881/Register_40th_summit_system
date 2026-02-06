<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลงทะเบียนเข้างาน | Summit Auto Body</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #0f172a 0%, #1a2744 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Sarabun', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 1rem;
        }

        .register-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 1.5rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 450px;
            border: 2px solid rgba(241, 196, 15, 0.1);
        }

        .form-control,
        .form-select {
            border-radius: 0.625rem;
            padding: 0.75rem;
            margin-bottom: 0.3125rem;
            font-size: 1rem;
            border: 1px solid #ddd;
            transition: all 0.3s ease;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #27ae60;
            box-shadow: 0 0 5px rgba(39, 174, 96, 0.3);
        }

        .btn-register {
            background: #f1c40f;
            border: none;
            border-radius: 0.625rem;
            padding: 0.9rem 1.5rem;
            font-weight: bold;
            font-size: 1rem;
            transition: all 0.3s ease;
            cursor: pointer;
            min-height: 44px;
            color: #0f172a;
        }

        .btn-register:hover {
            background: #f9d854;
            transform: scale(1.02);
            box-shadow: 0 8px 20px rgba(241, 196, 15, 0.3);
        }

        .btn-register:active {
            transform: scale(0.98);
        }

        .logo-box {
            font-size: clamp(2rem, 8vw, 3.5rem);
            color: #f1c40f;
            margin-bottom: 1.25rem;
        }

        h2 {
            font-size: clamp(1.5rem, 5vw, 2rem);
            margin-bottom: 1rem !important;
            color: #0f172a;
        }

        p {
            font-size: clamp(0.875rem, 3vw, 1rem);
        }

        .form-label {
            font-size: clamp(0.875rem, 2.5vw, 0.95rem);
            color: #0f172a;
        }

        .text-muted {
            opacity: 0.7;
            color: #555 !important;
        }

        a {
            transition: color 0.3s ease;
            color: #f1c40f;
            font-weight: 600;
        }

        a:hover {
            color: #f9d854 color: #219150 !important;
        }

        /* Mobile (Extra Small) - 320px to 576px */
        @media (max-width: 575.98px) {
            body {
                padding: 0.75rem;
            }

            .register-card {
                padding: 1.5rem;
                border-radius: 1.25rem;
            }

            .mb-3 {
                margin-bottom: 0.75rem !important;
            }

            .mb-4 {
                margin-bottom: 1rem !important;
            }

            .form-control,
            .form-select {
                padding: 0.7rem 0.6rem;
                font-size: 16px;
            }

            hr {
                margin: 1rem 0;
            }
        }

        /* Small devices (Tablet) - 576px to 768px */
        @media (min-width: 576px) and (max-width: 767.98px) {
            .register-card {
                padding: 2rem 1.75rem;
            }
        }

        /* Medium devices and up - 768px and above */
        @media (min-width: 768px) {
            body {
                padding: 2rem;
            }

            .register-card {
                padding: 2.5rem;
            }
        }

        /* Large devices - 992px and above */
        @media (min-width: 992px) {
            .register-card {
                padding: 3rem;
            }
        }

        /* Extra Large devices - 1200px and above */
        @media (min-width: 1200px) {
            .register-card {
                padding: 3.5rem;
            }
        }

        /* High DPI displays */
        @media (-webkit-min-device-pixel-ratio: 2),
        (min-resolution: 192dpi) {
            body {
                padding: 1.5rem;
            }
        }

        /* Landscape orientation */
        @media (orientation: landscape) and (max-height: 600px) {
            body {
                padding: 0.5rem;
            }

            .register-card {
                padding: 1.5rem;
                max-width: 600px;
            }

            h2 {
                margin-bottom: 0.5rem !important;
            }
        }

        /* Touch device optimizations */
        @media (hover: none) and (pointer: coarse) {
            .btn-register {
                padding: 1rem 1.5rem;
                min-height: 48px;
            }

            .form-control,
            .form-select {
                padding: 0.8rem;
            }
        }
    </style>
</head>

<body>

    <div class="register-card text-center">
        <div class="logo-box">
            <i class="fas fa-user-circle"></i>
        </div>
        <h2 class="fw-bold mb-4">ลงทะเบียนเข้างาน</h2>
        <p class="text-muted mb-4">กรุณากรอกข้อมูลเพื่อรับ QR Code สำหรับสแกนเข้างาน</p>

        <form action="save_register.php" method="POST">
            <div class="mb-3 text-start">
                <label class="form-label fw-bold"><i class="fas fa-id-badge me-2"></i>รหัสพนักงาน</label>
                <input type="text" name="emp_id" class="form-control" placeholder="ตัวอย่าง: B12345" required>
            </div>

            <div class="mb-3 text-start">
                <label class="form-label fw-bold"><i class="fas fa-user me-2"></i>ชื่อ-นามสกุล</label>
                <input type="text" name="emp_name" class="form-control" placeholder="ไม่ต้องใส่คำนำหน้าชื่อ" required>
            </div>

            <div class="mb-4 text-start">
                <label class="form-label fw-bold"><i class="fas fa-industry me-2"></i>เลือก Plant</label>
                <select name="plant" class="form-select" required>
                    <option value="" selected disabled>กรุณาเลือกโรงงาน...</option>
                    <option value="Plant A">Plant A</option>
                    <option value="Plant B">Plant B</option>
                    <option value="Plant C">Plant C</option>
                </select>
            </div>

            <button type="submit" class="btn btn-success btn-register w-100 mb-3 text-white">
                <i class="fas fa-qrcode me-2"></i>ลงทะเบียนรับ QR Code
            </button>
        </form>

        <hr>
        <div class="mt-3">
            <p class="mb-0 text-muted small">เคยลงทะเบียนแล้ว?</p>
            <a href="view_old_ticket.php" class="text-decoration-none fw-bold">คลิกที่นี่เพื่อรับบัตรใบเดิม</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>