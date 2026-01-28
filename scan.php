<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Scanner | SAB 40th Anniversary</title>
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
            background: linear-gradient(-45deg, #1e3c72, #2a5298, #003399, #1a2a6c);
            background-size: 400% 400%;
            animation: gradientFlow 15s ease infinite;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        @keyframes gradientFlow {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        /* Floating particles */
        body::before,
        body::after {
            content: "";
            position: fixed;
            border-radius: 50%;
            background: rgba(255, 215, 0, 0.1);
            animation: float 20s infinite ease-in-out;
        }

        body::before {
            width: 300px;
            height: 300px;
            top: -150px;
            left: -150px;
            animation-delay: 0s;
        }

        body::after {
            width: 400px;
            height: 400px;
            bottom: -200px;
            right: -200px;
            animation-delay: 7s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translate(0, 0) rotate(0deg);
            }

            25% {
                transform: translate(50px, 50px) rotate(90deg);
            }

            50% {
                transform: translate(0, 100px) rotate(180deg);
            }

            75% {
                transform: translate(-50px, 50px) rotate(270deg);
            }
        }

        .container {
            max-width: 600px;
            width: 100%;
            position: relative;
            z-index: 10;
        }

        .scanner-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 40px;
            padding: 40px 30px;
            box-shadow:
                0 30px 80px rgba(0, 0, 0, 0.3),
                0 0 0 1px rgba(255, 215, 0, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.9);
            transform-style: preserve-3d;
            animation: cardFloat 6s ease-in-out infinite;
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

        /* Header */
        .header-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .gold-badge {
            display: inline-block;
            background: linear-gradient(135deg, #bf953f 0%, #fcf6ba 50%, #b38728 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 4rem;
            font-weight: 800;
            line-height: 1;
            text-shadow: 0 4px 20px rgba(191, 149, 63, 0.3);
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

        .title-text {
            color: #003399;
            font-size: 2rem;
            font-weight: 800;
            margin: 10px 0;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .subtitle {
            color: #666;
            font-size: 0.9rem;
            font-weight: 400;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        /* Scanner Area */
        #reader {
            border-radius: 24px;
            overflow: hidden;
            background: #000;
            border: 4px solid transparent;
            background-image:
                linear-gradient(white, white),
                linear-gradient(135deg, #bf953f, #fcf6ba, #b38728);
            background-origin: border-box;
            background-clip: padding-box, border-box;
            box-shadow:
                0 20px 40px rgba(0, 51, 153, 0.2),
                0 0 20px rgba(191, 149, 63, 0.3);
            margin-bottom: 25px;
            position: relative;
        }

        #reader::before {
            content: "";
            position: absolute;
            top: -4px;
            left: -4px;
            right: -4px;
            bottom: -4px;
            background: linear-gradient(45deg, #bf953f, #fcf6ba, #b38728, #bf953f);
            background-size: 300% 300%;
            border-radius: 24px;
            z-index: -1;
            animation: borderGlow 3s ease infinite;
        }

        @keyframes borderGlow {

            0%,
            100% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }
        }

        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: linear-gradient(135deg, #003399, #0055cc);
            color: white;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0, 51, 153, 0.3);
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

        /* Button */
        .home-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            width: 100%;
            padding: 16px 32px;
            background: linear-gradient(135deg, #bf953f 0%, #fcf6ba 50%, #b38728 100%);
            color: #003399;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow:
                0 10px 30px rgba(191, 149, 63, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.6);
        }

        .home-btn:hover {
            transform: translateY(-2px);
            box-shadow:
                0 15px 40px rgba(191, 149, 63, 0.5),
                inset 0 1px 0 rgba(255, 255, 255, 0.8);
            color: #001a4d;
        }

        .home-btn:active {
            transform: translateY(0);
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
        @media (max-width: 576px) {
            .scanner-card {
                padding: 30px 20px;
                border-radius: 30px;
            }

            .gold-badge {
                font-size: 3rem;
            }

            .title-text {
                font-size: 1.5rem;
            }

            .subtitle {
                font-size: 0.75rem;
            }
        }

        /* Tablet & Desktop */
        @media (min-width: 768px) {
            .scanner-card {
                padding: 50px 40px;
            }

            .gold-badge {
                font-size: 5rem;
            }

            .title-text {
                font-size: 2.5rem;
            }

            .subtitle {
                font-size: 1rem;
            }

            #reader {
                margin-bottom: 30px;
            }

            .home-btn {
                font-size: 1.2rem;
                padding: 18px 36px;
            }
        }

        /* Large Desktop */
        @media (min-width: 1024px) {
            .scanner-card {
                padding: 60px 50px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="scanner-card">
            <div class="header-section">
                <div class="gold-badge">40<sup style="font-size: 0.5em;">th</sup></div>
                <h1 class="title-text">SAB Scanner</h1>
                <p class="subtitle">Summit Auto Body Industry</p>
            </div>

            <center>
                <div class="status-badge">
                    <span class="status-dot"></span>
                    <span>กล้องพร้อมสแกน</span>
                </div>
            </center>

            <div id="reader"></div>

            <a href="index.php" class="home-btn">
                <i class="fas fa-home"></i>
                <span>กลับหน้าหลัก</span>
            </a>
        </div>
    </div>

    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
        const html5QrcodeScanner = new Html5QrcodeScanner("reader", {
            fps: 20,
            qrbox: {
                width: 250,
                height: 250
            },
            aspectRatio: 1.0
        });

        function onScanSuccess(qrData) {
            html5QrcodeScanner.pause(true);

            let fd = new FormData();
            fd.append('qr', qrData);

            fetch('checkin_api.php', {
                    method: 'POST',
                    body: fd
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: '✓ เช็คอินสำเร็จ!',
                            html: `<div style="font-size: 1.1rem; color: #003399;"><b>${data.name}</b></div><div style="color: #666; margin-top: 8px;">โรงงาน: ${data.plant}</div>`,
                            timer: 2500,
                            showConfirmButton: false,
                            background: '#fff',
                            customClass: {
                                popup: 'animated-popup'
                            }
                        });
                        new Audio('https://www.soundjay.com/buttons/beep-07.mp3').play();
                    } else if (data.status === 'already') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'เช็คอินแล้ว',
                            text: `คุณ ${data.name} ได้ลงทะเบียนเข้างานแล้ว`,
                            timer: 2500,
                            showConfirmButton: false
                        });
                        new Audio('https://www.soundjay.com/buttons/beep-05.mp3').play();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'แสกนไม่สำเร็จ',
                            text: data.message,
                            timer: 2500,
                            showConfirmButton: false
                        });
                        new Audio('https://www.soundjay.com/buttons/beep-05.mp3').play();
                    }

                    setTimeout(() => {
                        html5QrcodeScanner.resume();
                    }, 3000);
                })
                .catch(err => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'ไม่สามารถเชื่อมต่อ Server ได้',
                        timer: 2500,
                        showConfirmButton: false
                    });
                    html5QrcodeScanner.resume();
                });
        }

        html5QrcodeScanner.render(onScanSuccess);
    </script>
</body>

</html>