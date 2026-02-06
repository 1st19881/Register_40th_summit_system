<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Check-in System | Main Menu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .hero-section {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 60px 0;
            margin-bottom: 40px;
            border-bottom-left-radius: 50px;
            border-bottom-right-radius: 50px;
        }

        .menu-card {
            border: none;
            border-radius: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
            height: 100%;
        }

        .menu-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .icon-box {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .btn-portal {
            text-decoration: none;
            color: inherit;
        }

        .hero-logo {
            max-width: 220px;
            height: auto;
            margin-bottom: 25px;
            filter: drop-shadow(0 4px 15px rgba(0, 0, 0, 0.3));
            animation: logoFloat 3s ease-in-out infinite;
        }

        @keyframes logoFloat {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }
    </style>
</head>

<body>

    <header class="hero-section text-center">
        <div class="container">
            <img src="logo/logo.png" alt="Summit Auto Body Industry 40th Anniversary" class="hero-logo">
            <h1 class="display-4 fw-bold">ระบบลงทะเบียนเข้างาน</h1>
            <p class="lead">กรุณาเลือกรายการที่ต้องการดำเนินการ</p>
        </div>
    </header>

    <div class="container">
        <div class="row g-4 justify-content-center">

            <!-- ปิดเมนูลงทะเบียนใหม่ และ รับบัตรใบเดิม ชั่วคราว
            <div class="col-md-4 col-sm-6">
                <a href="register.php" class="btn-portal">
                    <div class="card menu-card text-center p-4">
                        <div class="icon-box text-success">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <h3 class="fw-bold">ลงทะเบียนใหม่</h3>
                        <p class="text-muted">สำหรับพนักงานที่ยังไม่มี QR Code เข้างาน</p>
                    </div>
                </a>
            </div>

            <div class="col-md-4 col-sm-6">
                <a href="re_print.php" class="btn-portal">
                    <div class="card menu-card text-center p-4">
                        <div class="icon-box text-primary">
                            <i class="fas fa-id-card"></i>
                        </div>
                        <h3 class="fw-bold">รับบัตรใบเดิม</h3>
                        <p class="text-muted">กรณีทำรูปหาย หรือต้องการเรียกดู QR เดิม</p>
                    </div>
                </a>
            </div>
            -->

            <div class="col-md-4 col-sm-6">
                <a href="scan.php" class="btn-portal">
                    <div class="card menu-card text-center p-4" style="border-top: 5px solid #ffc107;">
                        <div class="icon-box text-warning">
                            <i class="fas fa-qrcode"></i>
                        </div>
                        <h3 class="fw-bold">เช็คอินเข้างาน</h3>
                        <p class="text-muted text-danger fw-bold">* สำหรับเจ้าหน้าที่เท่านั้น</p>
                    </div>
                </a>
            </div>

            <div class="col-md-4 col-sm-6">
                <a href="dashboard.php" class="btn-portal">
                    <div class="card menu-card text-center p-4">
                        <div class="icon-box text-dark">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="fw-bold">รายงานสรุปผล</h3>
                        <p class="text-muted">ดูจำนวนผู้เข้าร่วมงานแบบ Real-time</p>
                    </div>
                </a>
            </div>
            <div class="col-md-4 col-sm-6">
                <a href="upload_employee.php" class="btn-portal">
                    <div class="card menu-card text-center p-4">
                        <div class="icon-box text-info">
                            <i class="fas fa-upload"></i>
                        </div>
                        <h3 class="fw-bold">อัพโหลดข้อมูลพนักงาน</h3>
                        <p class="text-muted">นำเข้าข้อมูลพนักงานจากไฟล์ CSV</p>
                    </div>
                </a>
            </div>

            <div class="col-md-4 col-sm-6">
                <a href="manage_prizes.php" class="btn-portal">
                    <div class="card menu-card text-center p-4">
                        <div class="icon-box text-secondary">
                            <i class="fas fa-gift"></i>
                        </div>
                        <h3 class="fw-bold">จัดการของรางวัล</h3>
                        <p class="text-muted">เพิ่ม แก้ไข ลบ รายการของรางวัล</p>
                    </div>
                </a>
            </div>

            <div class="col-md-4 col-sm-6">
                <a href="lucky_draw.php" class="btn-portal">
                    <div class="card menu-card text-center p-4" style="border-top: 5px solid #bf953f;">
                        <div class="icon-box" style="color: #bf953f;">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <h3 class="fw-bold">จับรางวัล</h3>
                        <p class="text-muted text-danger fw-bold">* สำหรับเจ้าหน้าที่เท่านั้น</p>
                    </div>
                </a>
            </div>

        </div>

        <footer class="mt-5 text-center text-muted">
            <p>? 2026 Summit Auto Body Industry - QR Check-in System</p>
    </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>