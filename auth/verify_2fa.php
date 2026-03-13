<?php
// ============================================
// 2FA VERIFICATION PAGE
// auth/verify_2fa.php
// ============================================

require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/security_helper.php';

// Must have 2FA session
if (!isset($_SESSION['2fa_user_id'])) {
    header("Location: login.php");
    exit();
}

// Already fully logged in
if (isLoggedIn()) {
    logActivity('login_success', 'Logged in');
    header("Location: ../user/index.php");
    exit();
}

// 2FA session timeout — 10 minutes
if (isset($_SESSION['2fa_started_at']) && (time() - $_SESSION['2fa_started_at']) > 600) {
    session_unset();
    session_destroy();
    header("Location: login.php?error=session_expired");
    exit();
}

$error   = '';
$success = '';
$user_id = $_SESSION['2fa_user_id'];

// Handle resend
if (isset($_GET['resend'])) {
    $rate = checkRateLimit('2fa_resend_' . $user_id, 3, 300); // 3 resends per 5 min
    if (!$rate['allowed']) {
        $error = $rate['message'];
    } else {
        recordRateLimitAttempt('2fa_resend_' . $user_id);
        $otp  = generate2FACode($user_id);
        $sent = $otp ? send2FAEmail($_SESSION['2fa_email'], $_SESSION['2fa_full_name'], $otp) : false;
        if ($sent) {
            $success = 'A new verification code has been sent to your email.';
        } else {
            $error = 'Failed to resend code. Please try again.';
        }
    }
}

// Handle OTP submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_code'])) {
    $otp    = sanitizeInput($_POST['otp_code']);
    $result = verify2FACode($user_id, $otp);

    if ($result['success']) {
        // Restore full session
        $_SESSION['user_id']   = $_SESSION['2fa_user_id'];
        $_SESSION['full_name'] = $_SESSION['2fa_full_name'];
        $_SESSION['email']     = $_SESSION['2fa_email'];
        $_SESSION['role']      = $_SESSION['2fa_role'];
        if (isset($_SESSION['2fa_admin_level'])) {
            $_SESSION['admin_level'] = $_SESSION['2fa_admin_level'];
        }

        // Clear 2FA session vars
        unset(
            $_SESSION['2fa_user_id'],
            $_SESSION['2fa_full_name'],
            $_SESSION['2fa_email'],
            $_SESSION['2fa_role'],
            $_SESSION['2fa_admin_level'],
            $_SESSION['2fa_started_at']
        );

        // Trust this device?
        if (isset($_POST['trust_device']) && $_POST['trust_device'] === '1') {
            saveTrustedDevice($user_id);
        }
        logActivity('login_success', 'Logged in via 2FA verification');
        header("Location: ../user/index.php");
        exit();

    } elseif (!empty($result['max_reached'])) {
        // Too many attempts — back to login
        session_unset();
        session_destroy();
        logActivity('login_failed', 'Too many incorrect 2FA attempts', $user_id);
        header("Location: login.php?error=max_attempts");
        exit();
    } else {
        $error = $result['message'];
    }
}

// Mask email for display
$email_display = $_SESSION['2fa_email'] ?? '';
if (!empty($email_display)) {
    [$local, $domain] = explode('@', $email_display);
    $masked = substr($local, 0, 2) . str_repeat('*', max(2, strlen($local) - 2)) . '@' . $domain;
} else {
    $masked = '***@***.com';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Login — <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy:   #0d1b2a;
            --navy-2: #132236;
            --navy-3: #1a2f48;
            --cyan:   #00c2e0;
            --cyan-2: #00e5ff;
            --muted:  #8fa3b8;
            --border: rgba(0,194,224,0.15);
            --card:   #111e2e;
        }

        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--navy);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(0,194,224,0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0,194,224,0.05) 1px, transparent 1px);
            background-size: 60px 60px;
            pointer-events: none;
        }

        .orb {
            position: fixed; border-radius: 50%;
            filter: blur(80px); pointer-events: none; z-index: 0;
        }
        .orb-1 {
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(0,194,224,0.12) 0%, transparent 70%);
            top: -100px; right: -100px;
        }
        .orb-2 {
            width: 300px; height: 300px;
            background: radial-gradient(circle, rgba(247,37,133,0.07) 0%, transparent 70%);
            bottom: -80px; left: -80px;
        }

        .auth-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 44px 40px;
            width: 100%;
            max-width: 420px;
            position: relative;
            z-index: 1;
            box-shadow: 0 30px 80px rgba(0,0,0,0.5);
            animation: slideUp 0.5s ease both;
        }

        @keyframes slideUp {
            from { opacity:0; transform: translateY(24px); }
            to   { opacity:1; transform: translateY(0); }
        }

        .auth-card::before {
            content: '';
            position: absolute;
            top: 0; left: 10%; right: 10%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--cyan), transparent);
            border-radius: 2px;
        }

        .auth-brand {
            display: flex; align-items: center;
            justify-content: center; gap: 10px;
            margin-bottom: 32px; text-decoration: none;
        }
        .brand-icon {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, var(--cyan), var(--cyan-2));
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; color: var(--navy);
        }
        .brand-text {
            font-family: 'Sora', sans-serif;
            font-weight: 800; font-size: 1.2rem; color: #fff;
        }
        .brand-text span { color: var(--cyan); }

        .auth-header { text-align: center; margin-bottom: 28px; }
        .auth-header .shield-icon {
            width: 64px; height: 64px;
            background: rgba(0,194,224,0.1);
            border: 1px solid rgba(0,194,224,0.25);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 16px;
            font-size: 1.8rem; color: var(--cyan);
        }
        .auth-header h1 {
            font-family: 'Sora', sans-serif;
            font-weight: 700; font-size: 1.6rem; color: #fff; margin-bottom: 8px;
        }
        .auth-header p { font-size: 0.88rem; color: var(--muted); line-height: 1.6; }
        .auth-header .email-highlight {
            color: #fff; font-weight: 600;
        }

        /* OTP Input */
        .otp-input {
            background: var(--navy-2);
            border: 2px solid var(--border);
            border-radius: 16px;
            color: #fff;
            padding: 16px;
            font-size: 2rem;
            font-weight: 700;
            font-family: 'Sora', sans-serif;
            width: 100%;
            text-align: center;
            letter-spacing: 12px;
            transition: all 0.2s;
            margin-bottom: 6px;
        }
        .otp-input::placeholder {
            color: var(--muted); opacity: 0.4;
            letter-spacing: 8px; font-size: 1.5rem;
        }
        .otp-input:focus {
            outline: none;
            border-color: var(--cyan);
            background: var(--navy-3);
            box-shadow: 0 0 0 3px rgba(0,194,224,0.12);
        }

        .otp-hint {
            font-size: 0.76rem; color: var(--muted);
            text-align: center; margin-bottom: 20px;
        }

        /* Trust device checkbox */
        .trust-row {
            display: flex; align-items: flex-start;
            gap: 10px; margin-bottom: 24px;
            padding: 14px;
            background: rgba(0,194,224,0.05);
            border: 1px solid rgba(0,194,224,0.12);
            border-radius: 12px;
        }
        .form-check-input {
            background-color: var(--navy-2);
            border-color: var(--border);
            width: 16px; height: 16px;
            flex-shrink: 0; margin-top: 3px;
        }
        .form-check-input:checked {
            background-color: var(--cyan);
            border-color: var(--cyan);
        }
        .trust-label {
            font-size: 0.83rem; color: var(--muted); line-height: 1.5;
        }
        .trust-label strong { color: #c5d3e0; }

        /* Submit button */
        .btn-submit {
            width: 100%; padding: 13px;
            background: var(--cyan); color: var(--navy);
            font-family: 'Sora', sans-serif;
            font-weight: 700; font-size: 0.95rem;
            border: none; border-radius: 12px;
            cursor: pointer; transition: all 0.25s;
            display: flex; align-items: center;
            justify-content: center; gap: 8px;
        }
        .btn-submit:hover {
            background: var(--cyan-2);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0,194,224,0.35);
        }
        .btn-submit:active { transform: translateY(0); }

        /* Alerts */
        .alert-custom {
            border-radius: 12px; padding: 13px 16px;
            font-size: 0.87rem; margin-bottom: 20px;
            border: 1px solid;
            display: flex; align-items: flex-start; gap: 10px;
        }
        .alert-error {
            background: rgba(220,53,69,0.1);
            border-color: rgba(220,53,69,0.25); color: #f8a5ae;
        }
        .alert-success {
            background: rgba(39,201,63,0.1);
            border-color: rgba(39,201,63,0.25); color: #86efac;
        }
        .alert-custom i { flex-shrink: 0; margin-top: 2px; }

        /* Footer */
        .auth-footer {
            text-align: center; margin-top: 20px;
            font-size: 0.85rem; color: var(--muted);
        }
        .auth-footer a {
            color: var(--cyan); text-decoration: none;
            font-weight: 600; transition: color 0.2s;
        }
        .auth-footer a:hover { color: var(--cyan-2); }
        .auth-footer .separator {
            margin: 0 8px; opacity: 0.4;
        }

        /* Timer */
        .timer-text {
            font-size: 0.78rem; color: var(--muted);
            text-align: center; margin-bottom: 20px;
        }
        .timer-text span { color: var(--cyan); font-weight: 600; }

        @media (max-width: 480px) {
            .auth-card { padding: 32px 24px; border-radius: 20px; }
            .auth-header h1 { font-size: 1.4rem; }
            .otp-input { font-size: 1.6rem; letter-spacing: 8px; }
        }
    </style>
</head>
<body>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>

    <div class="auth-card">
        <!-- Brand -->
        <a href="../index.php" class="auth-brand">
            <div class="brand-icon"><i class="bi bi-clipboard2-check-fill"></i></div>
            <span class="brand-text">CMS<span>.</span></span>
        </a>

        <!-- Header -->
        <div class="auth-header">
            <div class="shield-icon">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
            <h1>Verify Your Login</h1>
            <p>
                We sent a 6-digit verification code to<br>
                <span class="email-highlight"><?php echo htmlspecialchars($masked); ?></span>
            </p>
        </div>

        <!-- Alerts -->
        <?php if (!empty($error)): ?>
        <div class="alert-custom alert-error">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div><?php echo $error; ?></div>
        </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
        <div class="alert-custom alert-success">
            <i class="bi bi-check-circle-fill"></i>
            <div><?php echo $success; ?></div>
        </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST" action="">
            <input type="hidden" name="verify_code" value="1">

            <input type="text" class="otp-input" name="otp_code"
                   id="otpInput"
                   placeholder="······"
                   maxlength="6"
                   autocomplete="one-time-code"
                   inputmode="numeric"
                   required
                   autofocus>
            <p class="otp-hint">Enter the 6-digit code from your email</p>

            <!-- Trust device -->
            <div class="trust-row">
                <input class="form-check-input" type="checkbox"
                       name="trust_device" value="1" id="trustDevice">
                <label class="trust-label" for="trustDevice">
                    <strong>Trust this device for 30 days</strong><br>
                    You won't need a verification code on this device for 30 days.
                    Don't check this on public or shared computers.
                </label>
            </div>

            <button type="submit" class="btn-submit">
                <i class="bi bi-shield-check"></i> Verify & Login
            </button>
        </form>

        <!-- Timer & Resend -->
        <div class="timer-text" id="timerText">
            Code expires in <span id="countdown">10:00</span>
        </div>

        <!-- Footer links -->
        <div class="auth-footer">
            <a href="?resend=1">
                <i class="bi bi-arrow-clockwise"></i> Resend Code
            </a>
            <span class="separator">|</span>
            <a href="login.php">
                <i class="bi bi-arrow-left"></i> Back to Login
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // OTP input — numbers only, auto-submit at 6 digits
        const otpInput = document.getElementById('otpInput');
        if (otpInput) {
            otpInput.addEventListener('input', () => {
                otpInput.value = otpInput.value.replace(/\D/g, '').slice(0, 6);
                if (otpInput.value.length === 6) {
                    otpInput.closest('form').submit();
                }
            });
        }

        // Countdown timer — 10 minutes
        let timeLeft = 600;
        const countdown = document.getElementById('countdown');
        const timer = setInterval(() => {
            timeLeft--;
            const m = String(Math.floor(timeLeft / 60)).padStart(2, '0');
            const s = String(timeLeft % 60).padStart(2, '0');
            if (countdown) countdown.textContent = `${m}:${s}`;
            if (timeLeft <= 0) {
                clearInterval(timer);
                if (countdown) countdown.textContent = 'Expired';
                // Redirect back to login
                window.location.href = 'login.php?error=session_expired';
            }
        }, 1000);
    </script>
</body>
</html>