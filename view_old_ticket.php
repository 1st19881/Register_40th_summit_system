<?php
require 'auth.php';
require 'config/config.php'; // ไฟล์เชื่อมต่อ Oracle

$emp_id = $_GET['emp_id'] ?? '';

if (empty($emp_id)) {
    header("Location: re_print.php");
    exit;
}

// 1. ดึงข้อมูลพนักงานจาก Oracle
$sql = "SELECT EMP_NAME, PLANT, QR_CODE FROM EMP_CHECKIN WHERE EMP_ID = :id";
$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":id", $emp_id);
oci_execute($stid);
$row = oci_fetch_array($stid, OCI_ASSOC);

if (!$row) {
    echo "<!DOCTYPE html><html><head><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body><script>
            Swal.fire({
                icon: 'error',
                title: 'ไม่พบข้อมูล',
                text: 'รหัสพนักงานนี้ยังไม่ได้ลงทะเบียนครับ',
                timer: 2000,
                showConfirmButton: false,
                willClose: () => { window.location = 'register.php'; }
            });
          </script></body></html>";
    exit;
}

// จัดเตรียม URL สำหรับ QR Code โดยใช้ HTTPS API โดยตรง
$qr_proxied = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($row['QR_CODE']);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เรียกดูบัตรเข้างาน | Summit Auto Body</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: #0f172a;
            /* พื้นหลังสีเข้มดูพรีเมียม */
            font-family: 'Kanit', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
        }

        .ticket-box {
            width: 100%;
            max-width: 380px;
        }

        #ticket-capture {
            background: white;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }

        .ticket-head {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }

        .ticket-head h5 {
            font-size: 1.25rem;
        }

        .ticket-head p {
            font-size: 0.85rem;
        }

        .qr-area {
            padding: 40px 20px;
            text-align: center;
            background: #f8fafc;
        }

        .qr-border {
            background: white;
            padding: 15px;
            border-radius: 20px;
            display: inline-block;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
        }

        .qr-border img {
            width: 100%;
            max-width: 200px;
            height: auto;
            aspect-ratio: 1;
        }

        .info-area {
            padding: 25px 20px 30px;
            border-top: 2px dashed #cbd5e1;
            color: #1e293b;
        }

        .label {
            font-size: 0.7rem;
            color: #64748b;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .value {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 15px;
            word-break: break-word;
        }

        .info-row {
            display: flex;
            gap: 10px;
            margin-bottom: 0;
        }

        .info-row>div {
            flex: 1;
            min-width: 0;
        }

        .badge-check {
            font-size: 0.75rem;
            padding: 8px 12px;
        }

        /* Responsive Styles */
        @media (max-width: 576px) {
            body {
                padding: 10px;
            }

            .ticket-box {
                max-width: 100%;
            }

            .qr-area {
                padding: 25px 15px;
            }

            .qr-border img {
                max-width: 150px;
            }

            .info-area {
                padding: 20px 15px 25px;
            }

            .value {
                font-size: 0.95rem;
            }

            .ticket-head {
                padding: 25px 15px;
            }

            .ticket-head h5 {
                font-size: 1.1rem;
            }

            .btn-lg {
                padding: 0.7rem 1.5rem;
                font-size: 0.95rem;
            }
        }

        @media (max-width: 360px) {
            .qr-border img {
                max-width: 120px;
            }

            .info-area {
                padding: 15px 12px 20px;
            }

            .value {
                font-size: 0.9rem;
                margin-bottom: 12px;
            }

            .label {
                font-size: 0.65rem;
            }
        }

        @media (min-width: 768px) {
            .ticket-box {
                max-width: 400px;
            }

            .qr-area {
                padding: 45px 30px;
            }

            .qr-border img {
                max-width: 220px;
            }
        }

        /* Touch-friendly buttons */
        @media (hover: none) and (pointer: coarse) {
            button {
                min-height: 44px;
            }
        }
    </style>
</head>

<body>

    <div class="ticket-box">
        <div id="ticket-capture">
            <div class="ticket-head">
                <h5 class="fw-bold mb-0">EVENT TICKET</h5>
                <p class="small mb-0" style="opacity: 0.8;">Summit Auto Body 40th Anniversary</p>
            </div>

            <div class="qr-area">
                <div class="qr-border">
                    <img src="<?php echo $qr_proxied; ?>" alt="Scan Me">
                </div>
            </div>

            <div class="info-area">
                <div class="label">ชื่อพนักงาน</div>
                <div class="value"><?php echo htmlspecialchars($row['EMP_NAME']); ?></div>

                <div class="info-row">
                    <div>
                        <div class="label">รหัสพนักงาน</div>
                        <div class="value"><?php echo htmlspecialchars($emp_id); ?></div>
                    </div>
                    <div>
                        <div class="label">โรงงาน (Plant)</div>
                        <div class="value"><?php echo htmlspecialchars($row['PLANT']); ?></div>
                    </div>
                </div>

                <div class="text-center mt-3 py-2 px-3 rounded-pill badge-check" style="background: #f1f5f9; font-size: 0.75rem;">
                    <i class="fas fa-check-circle text-primary"></i> ข้อมูลลงทะเบียนถูกต้อง
                </div>
            </div>
        </div>

        <div class="mt-4">
            <!-- <button onclick="saveAsImage()" class="btn btn-primary btn-lg w-100 mb-3 shadow" style="border-radius: 15px; background: #10b981; border:none;">
                <i class="fas fa-download me-2"></i> บันทึกเป็นรูปภาพ
            </button> -->
            <a href="index.php" class="btn btn-outline-light w-100 border-0">
                <i class="fas fa-home me-1"></i> กลับหน้าหลัก
            </a>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script>
        function saveAsImage() {
            const ticket = document.querySelector("#ticket-capture");
            html2canvas(ticket, {
                useCORS: true, // สำคัญ: เพื่อให้ดึงรูปจากต่าง Domain ได้
                scale: 3, // เพื่อความคมชัดสูง
                backgroundColor: null
            }).then(canvas => {
                const link = document.createElement('a');
                link.download = 'SAB40-Ticket-<?php echo $emp_id; ?>.png';
                link.href = canvas.toDataURL("image/png");
                link.click();
            });
        }
    </script>

</body>

</html>