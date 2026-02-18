<?php
// ============================================
// FORGOT PASSWORD PAGE
// auth/forgot_password.php
// ============================================

require_once '../config/config.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isLoggedIn()) {
    header("Location: ../user/index.php");
    exit();
}

$error       = '';
$success     = '';
$step        = 1;
$otp_verified = false;

// Step 1 — Send OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_otp'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email  = sanitizeInput($_POST['email']);
        $result = createPasswordResetRequest($email);
        if ($result['success']) {
            $_SESSION['reset_email']      = $email;
            $_SESSION['reset_token']      = $result['token'];
            $_SESSION['reset_started_at'] = time();
            $success = $result['message'];
            $step    = 2;
        } else {
            $error = $result['message'];
        }
    }
}

// Step 2 — Verify OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid request. Please try again.';
        $step  = 2;
    } else {
        $email  = $_SESSION['reset_email'] ?? '';
        $otp    = sanitizeInput($_POST['otp']);
        $result = verifyOTP($email, $otp);
        if ($result['success']) {
            $_SESSION['reset_token']  = $result['token'];
            $_SESSION['otp_verified'] = true;
            $step        = 3;
            $otp_verified = true;
        } else {
            $error = $result['message'];
            $step  = 2;
            $otp_verified = false;
            unset($_SESSION['reset_token'], $_SESSION['otp_verified']);
        }
    }
}

// Step 3 — Reset Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid request. Please try again.';
        $step  = 1;
    } elseif (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
        $error = 'Invalid session. Please start the password reset process again.';
        unset($_SESSION['reset_email'], $_SESSION['reset_token'], $_SESSION['otp_verified']);
        $step = 1;
    } else {
        $token            = $_SESSION['reset_token'] ?? '';
        $new_password     = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if ($new_password !== $confirm_password) {
            $error = 'Passwords do not match.';
            $step  = 3;
        } else {
            $result = resetPasswordWithToken($token, $new_password);
            if ($result['success']) {
                $success = $result['message'];
                unset($_SESSION['reset_email'], $_SESSION['reset_token'], $_SESSION['otp_verified']);
                $step = 4;
            } else {
                $error = $result['message'];
                $step  = 3;
            }
        }
    }
}

// Restore step from session
if (isset($_SESSION['reset_email']) && !isset($_POST['send_otp'])) $step = 2;
if (isset($_SESSION['otp_verified']) && $_SESSION['otp_verified'] === true && isset($_SESSION['reset_token'])) {
    $step         = 3;
    $otp_verified = true;
}
if ($step === 3 && (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true)) {
    $error = 'Invalid session. Please start over.';
    unset($_SESSION['reset_email'], $_SESSION['reset_token'], $_SESSION['otp_verified']);
    $step = 1;
}
if (isset($_SESSION['reset_started_at']) && (time() - $_SESSION['reset_started_at']) > 1800) {
    $error = 'Session expired. Please start over.';
    unset($_SESSION['reset_email'], $_SESSION['reset_token'], $_SESSION['otp_verified'], $_SESSION['reset_started_at']);
    $step = 1;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — <?php echo SITE_NAME; ?></title>
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
            padding: 40px 20px;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed; inset: 0;
            background-image:
                linear-gradient(rgba(0,194,224,0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0,194,224,0.05) 1px, transparent 1px);
            background-size: 60px 60px;
            pointer-events: none;
        }

        .orb { position: fixed; border-radius: 50%; filter: blur(80px); pointer-events: none; z-index: 0; }
        .orb-1 {
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(0,194,224,0.12) 0%, transparent 70%);
            top: -80px; right: -80px;
        }
        .orb-2 {
            width: 300px; height: 300px;
            background: radial-gradient(circle, rgba(247,37,133,0.07) 0%, transparent 70%);
            bottom: 0; left: -80px;
        }

        .auth-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 44px 40px;
            width: 100%; max-width: 440px;
            position: relative; z-index: 1;
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
            display: flex; align-items: center; justify-content: center;
            gap: 10px; margin-bottom: 32px; text-decoration: none;
        }
        .brand-icon {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, var(--cyan), var(--cyan-2));
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; color: var(--navy);
        }
        .brand-text { font-family:'Sora',sans-serif; font-weight:800; font-size:1.2rem; color:#fff; }
        .brand-text span { color: var(--cyan); }

        .auth-header { text-align:center; margin-bottom:28px; }
        .auth-header h1 { font-family:'Sora',sans-serif; font-weight:700; font-size:1.7rem; color:#fff; margin-bottom:6px; }
        .auth-header p { font-size:0.9rem; color:var(--muted); }

        /* Step indicator */
        .steps {
            display: flex;
            align-items: center;
            margin-bottom: 32px;
        }

        .step-item {
            flex: 1;
            text-align: center;
            position: relative;
        }

        .step-circle {
            width: 36px; height: 36px;
            border-radius: 50%;
            border: 2px solid var(--border);
            background: var(--navy-2);
            display: flex; align-items: center; justify-content: center;
            font-family: 'Sora', sans-serif;
            font-weight: 700; font-size: 0.85rem;
            color: var(--muted);
            margin: 0 auto 6px;
            transition: all 0.3s;
            position: relative; z-index: 1;
        }

        .step-item.active .step-circle {
            border-color: var(--cyan);
            color: var(--cyan);
            background: rgba(0,194,224,0.1);
            box-shadow: 0 0 0 4px rgba(0,194,224,0.1);
        }

        .step-item.done .step-circle {
            border-color: #27c93f;
            background: rgba(39,201,63,0.15);
            color: #27c93f;
        }

        .step-label {
            font-size: 0.72rem;
            color: var(--muted);
            font-weight: 500;
        }

        .step-item.active .step-label { color: var(--cyan); }
        .step-item.done .step-label   { color: #27c93f; }

        .step-line {
            flex: 0 0 40px;
            height: 2px;
            background: var(--border);
            margin-bottom: 20px;
            transition: background 0.3s;
        }

        .step-line.done { background: rgba(39,201,63,0.4); }

        /* Form elements */
        .form-label {
            font-size: 0.82rem; font-weight: 600; color: #c5d3e0;
            margin-bottom: 6px; letter-spacing: 0.3px; display: block;
        }

        .input-wrap { position: relative; margin-bottom: 18px; }

        .input-icon {
            position: absolute; left: 14px; top: 50%;
            transform: translateY(-50%);
            color: var(--muted); font-size: 1rem;
            pointer-events: none; z-index: 2; transition: color 0.2s;
        }

        .form-control {
            background: var(--navy-2);
            border: 1px solid var(--border);
            border-radius: 12px; color: #fff;
            padding: 12px 14px 12px 42px;
            font-size: 0.92rem; width: 100%;
            transition: all 0.2s; font-family: 'DM Sans', sans-serif;
        }
        .form-control::placeholder { color: var(--muted); opacity: 0.7; }
        .form-control:focus {
            outline: none; border-color: var(--cyan);
            background: var(--navy-3); color: #fff;
            box-shadow: 0 0 0 3px rgba(0,194,224,0.12);
        }
        .input-wrap:focus-within .input-icon { color: var(--cyan); }

        /* OTP input */
        .otp-input {
            text-align: center;
            letter-spacing: 12px;
            font-family: 'Sora', sans-serif;
            font-weight: 700; font-size: 1.6rem;
            padding: 14px 14px 14px 28px;
        }

        /* Pass toggle */
        .has-toggle { padding-right: 42px; }
        .pass-toggle {
            position: absolute; right: 14px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            color: var(--muted); cursor: pointer;
            padding: 0; font-size: 1rem; z-index: 2;
            transition: color 0.2s;
        }
        .pass-toggle:hover { color: var(--cyan); }

        .btn-submit {
            width: 100%; padding: 13px;
            background: var(--cyan); color: var(--navy);
            font-family: 'Sora', sans-serif;
            font-weight: 700; font-size: 0.95rem;
            border: none; border-radius: 12px;
            cursor: pointer; transition: all 0.25s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-submit:hover {
            background: var(--cyan-2);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0,194,224,0.35);
        }
        .btn-submit:active { transform: translateY(0); }

        .btn-ghost {
            width: 100%; padding: 11px;
            background: transparent; color: var(--muted);
            font-family: 'DM Sans', sans-serif;
            font-weight: 500; font-size: 0.87rem;
            border: 1px solid var(--border); border-radius: 12px;
            cursor: pointer; transition: all 0.25s; margin-top: 10px;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-ghost:hover { border-color: rgba(0,194,224,0.3); color: var(--cyan); }

        .alert-custom {
            border-radius: 12px; padding: 13px 16px;
            font-size: 0.87rem; margin-bottom: 20px;
            border: 1px solid;
            display: flex; align-items: flex-start; gap: 10px;
        }
        .alert-error   { background: rgba(220,53,69,0.1); border-color: rgba(220,53,69,0.25); color: #f8a5ae; }
        .alert-success { background: rgba(39,201,63,0.1);  border-color: rgba(39,201,63,0.25); color: #86efac; }
        .alert-info    { background: rgba(0,194,224,0.08); border-color: rgba(0,194,224,0.2);  color: #7dd3e8; }
        .alert-custom i { flex-shrink: 0; margin-top: 2px; }

        /* Info hint */
        .hint-text {
            font-size: 0.78rem; color: var(--muted);
            margin-top: -10px; margin-bottom: 16px; padding-left: 2px;
        }

        .auth-footer { text-align:center; font-size:0.87rem; color:var(--muted); margin-top: 24px; }
        .auth-footer a { color:var(--cyan); text-decoration:none; font-weight:600; transition:color 0.2s; }
        .auth-footer a:hover { color:var(--cyan-2); }

        .back-home {
            position: fixed; top: 20px; left: 20px;
            display: flex; align-items: center; gap: 7px;
            font-size: 0.83rem; color: var(--muted);
            text-decoration: none; transition: color 0.2s; z-index: 10;
            background: rgba(13,27,42,0.7); backdrop-filter: blur(10px);
            border: 1px solid var(--border); padding: 7px 14px; border-radius: 50px;
        }
        .back-home:hover { color: var(--cyan); border-color: rgba(0,194,224,0.3); }

        /* Success state */
        .success-icon {
            width: 80px; height: 80px;
            background: rgba(39,201,63,0.12);
            border: 2px solid rgba(39,201,63,0.3);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 2.2rem; color: #27c93f;
            margin: 0 auto 20px;
            animation: popIn 0.4s ease both;
        }

        @keyframes popIn {
            from { transform: scale(0.5); opacity: 0; }
            to   { transform: scale(1);   opacity: 1; }
        }

        @media (max-width: 480px) {
            body { padding: 20px 16px; }
            .auth-card { padding: 28px 20px; border-radius: 20px; }
            .auth-header h1 { font-size: 1.4rem; }
            .back-home { display: none; }
            .otp-input { font-size: 1.3rem; letter-spacing: 8px; }
        }
    </style>
</head>
<body>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>

    <a href="../index.php" class="back-home">
        <i class="bi bi-arrow-left"></i> Back to Home
    </a>

    <div class="auth-card">
        <!-- Brand -->
        <a href="../index.php" class="auth-brand">
            <div class="brand-icon"><i class="bi bi-clipboard2-check-fill"></i></div>
            <span class="brand-text">CMS<span>.</span></span>
        </a>

        <!-- Header -->
        <div class="auth-header">
            <h1>Reset Password</h1>
            <p>Recover access to your account</p>
        </div>

        <?php if ($step < 4): ?>
        <!-- Step Indicators -->
        <div class="steps">
            <div class="step-item <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'done' : ''; ?>">
                <div class="step-circle">
                    <?php echo $step > 1 ? '<i class="bi bi-check-lg"></i>' : '<i class="bi bi-envelope"></i>'; ?>
                </div>
                <div class="step-label">Email</div>
            </div>

            <div class="step-line <?php echo $step > 1 ? 'done' : ''; ?>"></div>

            <div class="step-item <?php echo $step >= 2 ? 'active' : ''; ?> <?php echo $step > 2 ? 'done' : ''; ?>">
                <div class="step-circle">
                    <?php echo $step > 2 ? '<i class="bi bi-check-lg"></i>' : '<i class="bi bi-shield-lock"></i>'; ?>
                </div>
                <div class="step-label">OTP</div>
            </div>

            <div class="step-line <?php echo $step > 2 ? 'done' : ''; ?>"></div>

            <div class="step-item <?php echo $step >= 3 ? 'active' : ''; ?>">
                <div class="step-circle"><i class="bi bi-lock"></i></div>
                <div class="step-label">New Password</div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Alerts -->
        <?php if (!empty($error)): ?>
        <div class="alert-custom alert-error">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div><?php echo $error; ?></div>
        </div>
        <?php endif; ?>

        <?php if (!empty($success) && $step === 2): ?>
        <div class="alert-custom alert-success">
            <i class="bi bi-check-circle-fill"></i>
            <div><?php echo $success; ?></div>
        </div>
        <?php endif; ?>

        <!-- STEP 1: Email -->
        <?php if ($step === 1): ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <label class="form-label">Registered Email Address</label>
            <div class="input-wrap">
                <i class="bi bi-envelope input-icon"></i>
                <input type="email" class="form-control" name="email" required placeholder="you@email.com">
            </div>
            <button type="submit" name="send_otp" class="btn-submit">
                <i class="bi bi-send-fill"></i> Send OTP Code
            </button>
        </form>

        <!-- STEP 2: OTP -->
        <?php elseif ($step === 2): ?>
        <div class="alert-custom alert-info">
            <i class="bi bi-info-circle-fill"></i>
            <div>
                OTP sent to <strong><?php echo htmlspecialchars($_SESSION['reset_email']); ?></strong>.<br>
                <span style="font-size:0.8rem; opacity:0.85;">Check your inbox and spam folder. Valid for 15 minutes.</span>
            </div>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <label class="form-label">Enter 6-Digit OTP</label>
            <div class="input-wrap">
                <i class="bi bi-key input-icon"></i>
                <input type="text" class="form-control otp-input" name="otp"
                       required maxlength="6" placeholder="000000"
                       inputmode="numeric" autocomplete="one-time-code">
            </div>
            <button type="submit" name="verify_otp" class="btn-submit">
                <i class="bi bi-shield-check-fill"></i> Verify OTP
            </button>
        </form>
        <a href="forgot_password.php" class="btn-ghost" onclick="return confirm('Start the process over?');">
            <i class="bi bi-arrow-counterclockwise"></i> Didn't receive code? Try again
        </a>

        <!-- STEP 3: New Password -->
        <?php elseif ($step === 3): ?>
        <?php if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true): ?>
            <div class="alert-custom alert-error">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div>Invalid session. Please start the password reset process again.</div>
            </div>
            <a href="forgot_password.php" class="btn-submit" style="text-decoration:none;">
                <i class="bi bi-arrow-counterclockwise"></i> Start Over
            </a>
        <?php else: ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <label class="form-label">New Password</label>
            <div class="input-wrap">
                <i class="bi bi-lock input-icon"></i>
                <input type="password" class="form-control has-toggle" name="new_password"
                       id="newPass" required placeholder="Min. 8 characters">
                <button type="button" class="pass-toggle" id="toggleNew">
                    <i class="bi bi-eye" id="newIcon"></i>
                </button>
            </div>
            <p class="hint-text"><i class="bi bi-info-circle"></i> Min 8 chars — uppercase, lowercase, and numbers</p>

            <label class="form-label">Confirm New Password</label>
            <div class="input-wrap">
                <i class="bi bi-lock-fill input-icon"></i>
                <input type="password" class="form-control has-toggle" name="confirm_password"
                       id="confirmPass" required placeholder="Re-enter your password">
                <button type="button" class="pass-toggle" id="toggleConfirm">
                    <i class="bi bi-eye" id="confirmIcon"></i>
                </button>
            </div>

            <button type="submit" name="reset_password" class="btn-submit">
                <i class="bi bi-shield-check-fill"></i> Reset Password
            </button>
        </form>
        <?php endif; ?>

        <!-- STEP 4: Success -->
        <?php else: ?>
        <div class="text-center">
            <div class="success-icon">
                <i class="bi bi-check-lg"></i>
            </div>
            <h2 style="font-family:'Sora',sans-serif; font-weight:700; color:#fff; margin-bottom:10px;">Password Reset!</h2>
            <p style="color:var(--muted); font-size:0.9rem; margin-bottom:28px;">
                Your password has been successfully updated. You can now login with your new password.
            </p>
            <a href="login.php" class="btn-submit" style="text-decoration:none;">
                <i class="bi bi-box-arrow-in-right"></i> Go to Login
            </a>
        </div>
        <?php endif; ?>

        <?php if ($step < 4): ?>
        <div class="auth-footer">
            <a href="login.php"><i class="bi bi-arrow-left"></i> Back to Login</a>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // OTP auto-format (numbers only)
        const otpInput = document.querySelector('.otp-input');
        if (otpInput) {
            otpInput.addEventListener('input', () => {
                otpInput.value = otpInput.value.replace(/\D/g, '').slice(0, 6);
            });
            // Auto-submit when 6 digits entered
            otpInput.addEventListener('input', () => {
                if (otpInput.value.length === 6) {
                    otpInput.closest('form').querySelector('button[type=submit]').focus();
                }
            });
        }

        // Password toggles
        function makeToggle(btnId, inputId, iconId) {
            const btn  = document.getElementById(btnId);
            const inp  = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (!btn || !inp || !icon) return;
            btn.addEventListener('click', () => {
                const isPass = inp.type === 'password';
                inp.type     = isPass ? 'text' : 'password';
                icon.className = isPass ? 'bi bi-eye-slash' : 'bi bi-eye';
            });
        }

        makeToggle('toggleNew',     'newPass',     'newIcon');
        makeToggle('toggleConfirm', 'confirmPass', 'confirmIcon');

        // Live confirm match
        const confirmPass = document.getElementById('confirmPass');
        const newPass     = document.getElementById('newPass');
        if (confirmPass && newPass) {
            confirmPass.addEventListener('input', () => {
                confirmPass.style.borderColor = confirmPass.value === newPass.value
                    ? 'rgba(39,201,63,0.4)' : 'rgba(220,53,69,0.5)';
            });
        }
    </script>
</body>
</html>