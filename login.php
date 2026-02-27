<?php
session_start();

// ถ้า login แล้ว ให้ redirect ไปหน้า index
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

// กำหนด User/Password สำหรับ login (สามารถเพิ่มได้)
$valid_users = [
    'admin' => ['password' => 'admin1234', 'role' => 'admin', 'name' => 'ผู้ดูแลระบบ'],
    'staff' => ['password' => 'staff1234', 'role' => 'staff', 'name' => 'เจ้าหน้าที่'],
];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    } elseif (isset($valid_users[$username]) && $valid_users[$username]['password'] === $password) {
        // Login สำเร็จ
        $_SESSION['user_logged_in'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['user_role'] = $valid_users[$username]['role'];
        $_SESSION['user_display_name'] = $valid_users[$username]['name'];
        $_SESSION['login_time'] = date('Y-m-d H:i:s');

        // Remember me - ตั้ง cookie 7 วัน
        if (isset($_POST['remember']) && $_POST['remember'] === '1') {
            $token = bin2hex(random_bytes(32));
            $_SESSION['remember_token'] = $token;
            setcookie('remember_token', $token, time() + (7 * 24 * 60 * 60), '/');
            setcookie('remember_user', $username, time() + (7 * 24 * 60 * 60), '/');
        }

        // Redirect ไปหน้าที่ต้องการ หรือ index
        $redirect = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : 'index.php';
        unset($_SESSION['redirect_after_login']);
        header('Location: ' . $redirect);
        exit;
    } else {
        $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ | SAB 40th Anniversary</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Kanit', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0a0a1a;
            overflow: hidden;
            position: relative;
        }

        /* ===== Animated Background ===== */
        .bg-gradient {
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse at 20% 0%, rgba(120, 0, 255, 0.3) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 0%, rgba(0, 112, 255, 0.2) 0%, transparent 50%),
                radial-gradient(ellipse at 40% 100%, rgba(191, 149, 63, 0.2) 0%, transparent 50%),
                radial-gradient(ellipse at 100% 50%, rgba(0, 200, 255, 0.15) 0%, transparent 40%),
                linear-gradient(180deg, #0a0a1a 0%, #1a1a3a 100%);
            z-index: 0;
        }

        /* Grid Pattern */
        .grid-pattern {
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.015) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.015) 1px, transparent 1px);
            background-size: 60px 60px;
            z-index: 1;
        }

        /* Floating Orbs */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            animation: orbFloat 20s ease-in-out infinite;
            z-index: 1;
        }

        .orb-1 {
            width: 350px;
            height: 350px;
            background: linear-gradient(135deg, rgba(191, 149, 63, 0.3), rgba(252, 246, 186, 0.15));
            top: -10%;
            left: -5%;
            animation-delay: 0s;
        }

        .orb-2 {
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.25), rgba(139, 92, 246, 0.2));
            bottom: -10%;
            right: -5%;
            animation-delay: -7s;
        }

        .orb-3 {
            width: 200px;
            height: 200px;
            background: linear-gradient(135deg, rgba(191, 149, 63, 0.2), rgba(16, 185, 129, 0.15));
            top: 40%;
            right: 20%;
            animation-delay: -14s;
        }

        @keyframes orbFloat {

            0%,
            100% {
                transform: translate(0, 0) scale(1);
            }

            25% {
                transform: translate(30px, -30px) scale(1.05);
            }

            50% {
                transform: translate(-20px, 20px) scale(0.95);
            }

            75% {
                transform: translate(-30px, -20px) scale(1.02);
            }
        }

        /* Background Logo Watermark */
        .bg-logo-watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 70vmin;
            max-width: 500px;
            height: auto;
            opacity: 0.04;
            pointer-events: none;
            z-index: 1;
        }

        /* ===== Login Card ===== */
        .login-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 420px;
            padding: 0 1.25rem;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-radius: 28px;
            padding: 2.5rem 2rem;
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow:
                0 30px 60px rgba(0, 0, 0, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            animation: cardAppear 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes cardAppear {
            from {
                opacity: 0;
                transform: translateY(40px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Header */
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-img {
            max-width: 180px;
            height: auto;
            margin-bottom: 1rem;
            filter: drop-shadow(0 0 25px rgba(191, 149, 63, 0.5));
            animation: logoGlow 4s ease-in-out infinite;
        }

        @keyframes logoGlow {

            0%,
            100% {
                filter: drop-shadow(0 0 20px rgba(191, 149, 63, 0.5));
            }

            50% {
                filter: drop-shadow(0 0 35px rgba(252, 246, 186, 0.7));
            }
        }

        .login-title {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #bf953f 0%, #fcf6ba 50%, #b38728 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.25rem;
        }

        .login-subtitle {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.9rem;
            font-weight: 300;
        }

        /* Form */
        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            padding-left: 4px;
        }

        .form-label i {
            margin-right: 0.4rem;
            color: #bf953f;
            font-size: 0.85rem;
        }

        .input-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 0.9rem 1.1rem 0.9rem 3rem;
            font-family: 'Kanit', sans-serif;
            font-size: 1rem;
            font-weight: 400;
            color: #fff;
            background: rgba(255, 255, 255, 0.08);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 14px;
            outline: none;
            transition: all 0.3s ease;
            caret-color: #bf953f;
        }

        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.3);
            font-weight: 300;
        }

        .form-input:focus {
            border-color: rgba(191, 149, 63, 0.5);
            background: rgba(255, 255, 255, 0.12);
            box-shadow: 0 0 0 4px rgba(191, 149, 63, 0.12);
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.35);
            font-size: 1rem;
            transition: color 0.3s ease;
        }

        .form-input:focus+.input-icon,
        .form-input:not(:placeholder-shown)+.input-icon {
            color: #bf953f;
        }

        .toggle-password {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.35);
            cursor: pointer;
            font-size: 1rem;
            padding: 4px;
            transition: color 0.3s ease;
        }

        .toggle-password:hover {
            color: rgba(255, 255, 255, 0.7);
        }

        /* Remember me */
        .remember-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .remember-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.85rem;
            cursor: pointer;
            user-select: none;
        }

        .remember-label input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #bf953f;
            cursor: pointer;
        }

        /* Login Button */
        .login-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            width: 100%;
            padding: 0.95rem;
            background: linear-gradient(135deg, #bf953f 0%, #b38728 50%, #aa771c 100%);
            color: #fff;
            border: none;
            border-radius: 14px;
            font-family: 'Kanit', sans-serif;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 8px 25px rgba(191, 149, 63, 0.3);
            position: relative;
            overflow: hidden;
            letter-spacing: 0.5px;
        }

        .login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .login-btn:hover::before {
            left: 100%;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(191, 149, 63, 0.45);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .login-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* Error */
        .error-msg {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            color: #fca5a5;
            font-size: 0.9rem;
            animation: shakeError 0.5s ease;
        }

        @keyframes shakeError {

            0%,
            100% {
                transform: translateX(0);
            }

            20% {
                transform: translateX(-8px);
            }

            40% {
                transform: translateX(8px);
            }

            60% {
                transform: translateX(-5px);
            }

            80% {
                transform: translateX(5px);
            }
        }

        .error-msg i {
            font-size: 1.1rem;
            color: #f87171;
        }

        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.25rem;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .login-footer p {
            color: rgba(255, 255, 255, 0.3);
            font-size: 0.8rem;
            font-weight: 300;
        }

        /* Spinner */
        .spinner {
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Responsive */
        @media (max-width: 400px) {
            .login-card {
                padding: 2rem 1.5rem;
                border-radius: 24px;
            }

            .login-title {
                font-size: 1.3rem;
            }
        }

        @media (min-width: 768px) {
            .login-container {
                max-width: 440px;
            }

            .login-card {
                padding: 3rem 2.5rem;
            }
        }
    </style>
</head>

<body>
    <!-- Background -->
    <div class="bg-gradient"></div>
    <div class="grid-pattern"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <img src="logo/logo.png" alt="" class="bg-logo-watermark">

    <div class="login-container">
        <div class="login-card">
            <!-- Header -->
            <div class="login-header">
                <img src="logo/logo.png" alt="SAB 40th Anniversary" class="logo-img">
                <h1 class="login-title">เข้าสู่ระบบ</h1>
                <p class="login-subtitle">ระบบลงทะเบียนงานครบรอบ 40 ปี</p>
            </div>

            <!-- Error Message -->
            <?php if ($error): ?>
                <div class="error-msg">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label class="form-label" for="username">
                        <i class="fas fa-user"></i> ชื่อผู้ใช้
                    </label>
                    <div class="input-wrapper">
                        <input type="text" id="username" name="username" class="form-input"
                            placeholder="กรอกชื่อผู้ใช้" autocomplete="username" autofocus
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                        <i class="fas fa-user input-icon"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">
                        <i class="fas fa-lock"></i> รหัสผ่าน
                    </label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" class="form-input"
                            placeholder="กรอกรหัสผ่าน" autocomplete="current-password">
                        <i class="fas fa-lock input-icon"></i>
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="remember-row">
                    <label class="remember-label">
                        <input type="checkbox" name="remember" value="1">
                        จดจำการเข้าสู่ระบบ
                    </label>
                </div>

                <button type="submit" class="login-btn" id="loginBtn">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>เข้าสู่ระบบ</span>
                </button>
            </form>

            <!-- Footer -->
            <div class="login-footer">
                <p>? 2026 Summit Auto Body Industry</p>
            </div>
        </div>
    </div>

    <script>
        // Toggle Password Visibility
        function togglePassword() {
            const pwd = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                pwd.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        // Form Submit Loading
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('loginBtn');
            btn.disabled = true;
            btn.innerHTML = '<div class="spinner"></div><span>กำลังตรวจสอบ...</span>';
        });

        // Enter key support
        document.getElementById('password').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('loginForm').submit();
            }
        });
    </script>
</body>

</html>