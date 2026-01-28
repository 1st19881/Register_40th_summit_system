<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>เรียกดูบัตรเข้างานเดิม</title>
    <style>
        body {
            font-family: sans-serif;
            background: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .search-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        input {
            width: 100%;
            padding: 12px;
            margin: 15px 0;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
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
        <a href="register.php" style="font-size: 14px; color: #7f8c8d;">ยังไม่ได้ลงทะเบียน? คลิกที่นี่</a>
    </div>
</body>

</html>