<?php
require 'config/config.php'; // ÿÿÿÿลÿÿเÿÿืÿÿอมตÿÿอ Oracle

$emp_id   = trim($_POST['emp_id'] ?? '');
$emp_name = trim($_POST['emp_name'] ?? '');
$plant    = trim($_POST['plant'] ?? '');

if (empty($emp_id)) {
    header("Location: register.php");
    exit;
}

// 1. ตรวÿÿสอÿÿรหัสÿÿÿÿำ
$sql_check = "SELECT COUNT(*) AS TOTAL FROM EMP_CHECKIN WHERE EMP_ID = :id";
$stid_check = oci_parse($conn, $sql_check);
oci_bind_by_name($stid_check, ":id", $emp_id);
oci_execute($stid_check);
$row_check = oci_fetch_array($stid_check, OCI_ASSOC);

if ($row_check['TOTAL'] > 0) {
    // ÿÿÿÿÿÿÿÿเตือÿÿสวยÿÿ วÿÿาเÿÿยลÿÿทะเÿÿียÿÿÿÿลÿÿว
    echo "<!DOCTYPE html><html><head>
          <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
          <link rel='stylesheet' href='https://fonts.googleapis.com/css2?family=Sarabun&display=swap'>
          <style>body { font-family: 'Sarabun', sans-serif; background: #0f172a; }</style>
          </head><body><script>
            Swal.fire({
                title: 'ลÿÿทะเÿÿียÿÿÿÿÿÿÿÿลÿÿว!',
                text: 'รหัสÿÿÿÿัÿÿÿÿาÿÿ $emp_id มีÿÿÿÿอมูลÿÿÿÿระÿÿÿÿÿÿลÿÿวÿÿรัÿÿ',
                icon: 'info',
                timer: 2000,
                showConfirmButton: false,
                willClose: () => { window.location = 'view_old_ticket.php?emp_id=$emp_id'; }
            });
          </script></body></html>";
    exit;
}

// 2. ÿÿัÿÿทึÿÿÿÿÿÿอมูลÿÿหมÿÿ
$sql_ins = "INSERT INTO EMP_CHECKIN (QR_CODE, EMP_ID, EMP_NAME, PLANT, STATUS) VALUES (:id, :id, :name, :plant, 'PENDING')";
$stid_ins = oci_parse($conn, $sql_ins);
oci_bind_by_name($stid_ins, ":id", $emp_id);
oci_bind_by_name($stid_ins, ":name", $emp_name);
oci_bind_by_name($stid_ins, ":plant", $plant);
oci_execute($stid_ins, OCI_COMMIT_ON_SUCCESS);

// สรÿÿาÿÿ QR code URL ÿÿดยÿÿÿÿÿÿ API ภายÿÿอÿÿÿÿÿÿÿÿ HTTPS
$qr_proxied = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($emp_id);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลÿÿทะเÿÿียÿÿสำเรÿÿÿÿ | SAB 40th</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: #0f172a;
            /* ÿÿืÿÿÿÿหลัÿÿสีเÿÿÿÿมดูÿÿรีเมียม */
            font-family: 'Kanit', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
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

        .qr-area {
            padding: 40px;
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
            width: 200px;
            height: 200px;
        }

        .info-area {
            padding: 20px 30px 40px;
            border-top: 2px dashed #cbd5e1;
            color: #1e293b;
        }

        .label {
            font-size: 0.75rem;
            color: #64748b;
            text-transform: uppercase;
            font-weight: bold;
        }

        .value {
            font-size: 1.15rem;
            font-weight: 700;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>

    <div class="ticket-box">
        <div id="ticket-capture">
            <div class="ticket-head">
                <h5 class="fw-bold mb-0">SUCCESSFULLY REGISTERED</h5>
                <p class="small mb-0" style="opacity: 0.8;">Summit Auto Body 40th Anniversary</p>
            </div>

            <div class="qr-area">
                <div class="qr-border">
                    <img src="<?php echo $qr_proxied; ?>" alt="Scan Me">
                </div>
            </div>

            <div class="info-area">
                <div class="label">ÿÿืÿÿอÿÿÿÿัÿÿÿÿาÿÿ</div>
                <div class="value"><?php echo htmlspecialchars($emp_name); ?></div>

                <div class="row">
                    <div class="col-6">
                        <div class="label">รหัสÿÿÿÿัÿÿÿÿาÿÿ</div>
                        <div class="value"><?php echo htmlspecialchars($emp_id); ?></div>
                    </div>
                    <div class="col-6 text-end">
                        <div class="label">ÿÿรÿÿÿÿาÿÿ (Plant)</div>
                        <div class="value"><?php echo htmlspecialchars($plant); ?></div>
                    </div>
                </div>

                <div class="text-center mt-3 py-2 rounded-pill" style="background: #f1f5f9; font-size: 0.75rem;">
                    <i class="fas fa-check-circle text-success"></i> ลÿÿทะเÿÿียÿÿสำเรÿÿÿÿ
                </div>
            </div>
        </div>

        <div class="mt-4">
            <button onclick="downloadImg()" class="btn btn-primary btn-lg w-100 mb-3 shadow" style="border-radius: 15px; background: #10b981; border:none;">
                <i class="fas fa-download me-2"></i> ÿÿัÿÿทึÿÿเÿÿÿÿÿÿรูÿÿภาÿÿ
            </button>
            <a href="index.php" class="btn btn-outline-light w-100 border-0">
                <i class="fas fa-home me-1"></i> ÿÿลัÿÿหÿÿÿÿาหลัÿÿ
            </a>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script>
        function downloadImg() {
            const ticket = document.querySelector("#ticket-capture");
            html2canvas(ticket, {
                useCORS: true,
                scale: 3
            }).then(canvas => {
                let a = document.createElement('a');
                a.href = canvas.toDataURL("image/png");
                a.download = 'SAB-Ticket-<?php echo $emp_id; ?>.png';
                a.click();
            });
        }
    </script>

</body>

</html>