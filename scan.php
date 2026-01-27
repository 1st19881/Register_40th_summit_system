<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Scanner | SAB 40th Anniversary</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { 
            background: radial-gradient(circle, #2a5298 0%, #1e3c72 100%); 
            color: white; 
            font-family: 'Sarabun', sans-serif; 
            min-height: 100vh;
        }
        .scanner-card {
            max-width: 500px;
            margin: 40px auto;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            padding: 20px;
            border: 2px solid rgba(255, 215, 0, 0.3); /* สีทองจางๆ ตามธีม */
        }
        #reader { 
            border-radius: 20px; 
            overflow: hidden; 
            background: #000;
            border: 4px solid #f1c40f !important;
        }
        .header-logo { max-width: 200px; margin-bottom: 20px; }
        .swal2-popup { font-family: 'Sarabun', sans-serif !important; border-radius: 20px !important; }
    </style>
</head>
<body>

<div class="container text-center">
    <div class="scanner-card shadow-lg">
        <div class="py-3">
            <h2 class="fw-bold mb-0" style="color: #f1c40f;">SAB SCANNER</h2>
            <p class="small text-uppercase tracking-widest">40th Anniversary Celebration</p>
        </div>

        <div id="reader" class="mb-4"></div>

        <div class="d-grid gap-2">
            <a href="index.php" class="btn btn-outline-warning rounded-pill">
                <i class="fas fa-home me-2"></i> กลับหน้าหลัก
            </a>
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode"></script>
<script>
    const html5QrcodeScanner = new Html5QrcodeScanner("reader", { 
        fps: 20, 
        qrbox: { width: 250, height: 250 },
        aspectRatio: 1.0
    });

    function onScanSuccess(qrData) {
        html5QrcodeScanner.pause(true); // หยุดแสกนชั่วคราวเพื่อประมวลผล

        let fd = new FormData();
        fd.append('qr', qrData);

        fetch('checkin_api.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                // เช็คอินสำเร็จ
                Swal.fire({
                    icon: 'success',
                    title: 'เช็คอินสำเร็จ!',
                    html: `ยินดีต้อนรับคุณ <b class="text-primary">${data.name}</b><br>โรงงาน: ${data.plant}`,
                    timer: 2500,
                    showConfirmButton: false
                });
                new Audio('https://www.soundjay.com/buttons/beep-07.mp3').play();
            } 
            else if(data.status === 'already') {
                // กรณีแสกนซ้ำ
                Swal.fire({
                    icon: 'warning',
                    title: 'เช็คอินเข้างานแล้ว',
                    text: `คุณ ${data.name} ได้ลงทะเบียนเข้างานไปก่อนหน้านี้แล้วครับ`,
                    confirmButtonColor: '#f1c40f'
                });
                new Audio('https://www.soundjay.com/buttons/beep-05.mp3').play();
            }
            else {
                // กรณีรหัสผิด หรือไม่พบข้อมูล
                Swal.fire({
                    icon: 'error',
                    title: 'แสกนไม่สำเร็จ',
                    text: data.message, // "รหัสพนักงานผิด"
                    confirmButtonColor: '#e74c3c'
                });
                new Audio('https://www.soundjay.com/buttons/beep-05.mp3').play();
            }

            // รอ 3 วินาทีแล้วเริ่มแสกนคนถัดไป
            setTimeout(() => { html5QrcodeScanner.resume(); }, 3000);
        })
        .catch(err => {
            Swal.fire('Error', 'ไม่สามารถเชื่อมต่อ Server ได้', 'error');
            html5QrcodeScanner.resume();
        });
    }

    html5QrcodeScanner.render(onScanSuccess);
</script>
</body>
</html>