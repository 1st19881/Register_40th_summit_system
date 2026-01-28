<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลงทะเบียนเข้างาน | Summit Auto Body</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }

        .register-card {
            background: white;
            padding: 40px;
            border-radius: 25px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
        }

        .form-control,
        .form-select {
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 5px;
        }

        .btn-register {
            background: #27ae60;
            border: none;
            border-radius: 10px;
            padding: 15px;
            font-weight: bold;
            font-size: 1.1rem;
            transition: 0.3s;
        }

        .btn-register:hover {
            background: #219150;
            transform: scale(1.02);
        }

        .logo-box {
            font-size: 3rem;
            color: #1e3c72;
            margin-bottom: 20px;
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