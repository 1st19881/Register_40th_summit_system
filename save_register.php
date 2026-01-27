<?php
require 'config/config.php'; // ไฟล์เชื่อมต่อ Oracle

$emp_id   = $_POST['emp_id'] ?? '';
$emp_name = $_POST['emp_name'] ?? '';
$plant    = $_POST['plant'] ?? '';

if (empty($emp_id)) { header("Location: register.php"); exit; }

// 1. ตรวจสอบรหัสซ้ำ
$sql_check = "SELECT COUNT(*) AS TOTAL FROM EMP_CHECKIN WHERE EMP_ID = :id";
$stid_check = oci_parse($conn, $sql_check);
oci_bind_by_name($stid_check, ":id", $emp_id);
oci_execute($stid_check);
$row_check = oci_fetch_array($stid_check, OCI_ASSOC);

if ($row_check['TOTAL'] > 0) {
    // แจ้งเตือนสวยๆ ว่าเคยลงทะเบียนแล้ว
    echo "<!DOCTYPE html><html><head>
          <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
          <link rel='stylesheet' href='https://fonts.googleapis.com/css2?family=Sarabun&display=swap'>
          <style>body { font-family: 'Sarabun', sans-serif; background: #0f172a; }</style>
          </head><body><script>
            Swal.fire({
                title: 'ลงทะเบียนไปแล้ว!',
                text: 'รหัสพนักงาน $emp_id มีข้อมูลในระบบแล้วครับ',
                icon: 'info',
                confirmButtonText: 'รับบัตรใบเดิม',
                confirmButtonColor: '#3085d6'
            }).then(() => { window.location = 'view_old_ticket.php?emp_id=$emp_id'; });
          </script></body></html>";
    exit;
}

// 2. บันทึกข้อมูลใหม่
$sql_ins = "INSERT INTO EMP_CHECKIN (QR_CODE, EMP_ID, EMP_NAME, PLANT, STATUS) VALUES (:id, :id, :name, :plant, 'PENDING')";
$stid_ins = oci_parse($conn, $sql_ins);
oci_bind_by_name($stid_ins, ":id", $emp_id);
oci_bind_by_name($stid_ins, ":name", $emp_name);
oci_bind_by_name($stid_ins, ":plant", $plant);
oci_execute($stid_ins, OCI_COMMIT_ON_SUCCESS);

$qr_url = "get_qr.php?url=" . urlencode("https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . $emp_id);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลงทะเบียนสำเร็จ | SAB 40th</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #0f172a; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; color: white; }
        .ticket-card { background: white; color: #333; border-radius: 20px; width: 340px; overflow: hidden; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .ticket-header { background: #1e3c72; color: white; padding: 20px; }
        .qr-frame { padding: 30px; background: #f8fafc; }
        .qr-frame img { width: 200px; height: 200px; }
        .info-section { padding: 20px; border-top: 2px dashed #ddd; text-align: left; }
    </style>
</head>
<body>

<div class="ticket-container">
    <div id="ticket-capture" class="ticket-card">
        <div class="ticket-header">
            <h5 class="fw-bold mb-0">SUCCESSFULLY REGISTERED</h5>
            <small>Summit Auto Body 40th Anniversary</small>
        </div>
        <div class="qr-frame">
            <img src="<?php echo $qr_proxied; ?>" alt="QR Code">
        </div>
        <div class="info-section">
            <p class="mb-1 small text-muted text-uppercase">Employee Name</p>
            <h5 class="fw-bold mb-3"><?php echo htmlspecialchars($emp_name); ?></h5>
            <div class="row">
                <div class="col-6">
                    <p class="mb-0 small text-muted">ID: <?php echo htmlspecialchars($emp_id); ?></p>
                </div>
                <div class="col-6 text-end">
                    <p class="mb-0 small text-muted">Plant: <?php echo htmlspecialchars($plant); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <button onclick="downloadImg()" class="btn btn-success w-100 btn-lg fw-bold mb-3">📸 บันทึกเป็นรูปภาพ</button>
        <a href="index.php" class="btn btn-outline-light w-100">กลับหน้าหลัก</a>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
function downloadImg() {
    const ticket = document.querySelector("#ticket-capture");
    html2canvas(ticket, { useCORS: true, scale: 3 }).then(canvas => {
        let a = document.createElement('a');
        a.href = canvas.toDataURL("image/png");
        a.download = 'SAB-Ticket-<?php echo $emp_id; ?>.png';
        a.click();
    });
}
</script>

</body>
</html>