<?php require 'auth.php'; ?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>เรียกดูบัตรเข้างานเดิม</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Sarabun', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1a2744 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 1rem;
        }

        .search-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 1.5rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
            text-align: center;
            border: 2px solid rgba(241, 196, 15, 0.1);
        }

        h2 {
            font-size: clamp(1.5rem, 5vw, 1.8rem);
            color: #0f172a;
            margin: 0 0 0.5rem 0;
            font-weight: bold;
        }

        p {
            font-size: clamp(0.875rem, 3vw, 1rem);
            color: #555;
            margin: 0.5rem 0 1.5rem 0;
        }

        input {
            width: 100%;
            padding: 0.75rem;
            margin: 0.75rem 0;
            border: 2px solid #ddd;
            border-radius: 0.625rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        input:focus {
            outline: none;
            border-color: #f1c40f;
            box-shadow: 0 0 8px rgba(241, 196, 15, 0.3);
        }

        input::placeholder {
            color: #999;
        }

        button {
            width: 100%;
            padding: 0.9rem 1.5rem;
            background: #f1c40f;
            color: #0f172a;
            border: none;
            border-radius: 0.625rem;
            cursor: pointer;
            font-weight: bold;
            font-size: clamp(0.95rem, 2.5vw, 1rem);
            transition: all 0.3s ease;
            min-height: 44px;
            font-family: inherit;
        }

        button:hover {
            background: #f9d854;
            transform: scale(1.02);
            box-shadow: 0 8px 20px rgba(241, 196, 15, 0.3);
        }

        button:active {
            transform: scale(0.98);
        }

        a {
            font-size: clamp(0.8rem, 2vw, 0.9rem);
            color: #f1c40f;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
            display: inline-block;
            margin-top: 1rem;
        }

        a:hover {
            color: #f9d854;
        }

        /* Mobile (Extra Small) - 320px to 576px */
        @media (max-width: 575.98px) {
            body {
                padding: 0.75rem;
            }

            .search-card {
                padding: 1.5rem;
                border-radius: 1.25rem;
            }

            input {
                font-size: 16px;
                padding: 0.7rem 0.6rem;
            }

            button {
                padding: 0.85rem 1rem;
            }
        }

        /* Small devices (Tablet) - 576px to 768px */
        @media (min-width: 576px) and (max-width: 767.98px) {
            .search-card {
                padding: 2rem 1.75rem;
            }
        }

        /* Medium devices and up - 768px and above */
        @media (min-width: 768px) {
            body {
                padding: 2rem;
            }

            .search-card {
                padding: 2.5rem;
            }
        }

        /* Large devices - 992px and above */
        @media (min-width: 992px) {
            .search-card {
                padding: 3rem;
            }
        }

        /* Extra Large devices - 1200px and above */
        @media (min-width: 1200px) {
            .search-card {
                padding: 3.5rem;
            }
        }

        /* Landscape orientation */
        @media (orientation: landscape) and (max-height: 600px) {
            body {
                padding: 0.5rem;
                min-height: auto;
            }

            .search-card {
                padding: 1.5rem;
                max-width: 500px;
            }

            h2 {
                margin-bottom: 0.25rem;
            }

            p {
                margin: 0.25rem 0 0.75rem 0;
            }
        }

        /* Touch device optimizations */
        @media (hover: none) and (pointer: coarse) {

            input,
            button {
                padding: 0.8rem;
            }

            button {
                min-height: 48px;
            }
        }
    </style>
</head>

<body>
    <div class="search-card">
        <h2>ค้นหาบัตรเข้างาน</h2>
        <p>กรอกรหัสพนักงานเพื่อรับ QR Code เดิมของคุณ</p>
        <form action="view_old_ticket.php" method="GET">
            <input type="text" name="emp_id" placeholder="ระบุรหัสพนักงานของคุณ" required>
            <button type="submit">ค้นหาข้อมูล</button>
        </form>
        <br>
        <a href="register.php" style="display: inline-block;"><i class="fas fa-arrow-left me-2"></i>ยังไม่ได้ลงทะเบียน? คลิกที่นี่</a>
    </div>
</body>

</html>