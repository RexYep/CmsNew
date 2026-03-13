<?php
// ============================================
// LOGIN PAGE
// audmin/login.php
// ============================================

require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/security_helper.php';
require_once '../includes/recaptcha_helper.php';

if (isLoggedIn()) {
    logActivity('login_success', 'Logged in via trusted device');
    header("Location: index.php");
    exit();
}

$error   = '';
$success = '';
$result  = [];

if (isset($_GET['error']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($_GET['error'] === 'max_attempts') {
        $error = 'Too many incorrect verification attempts. Please login again.';
    } elseif ($_GET['error'] === 'session_expired') {
        $error = 'Verification session expired. Please login again.';
    }
}

if (isset($_GET['deleted']) && $_GET['deleted'] == 1) {
    $success = 'Your account has been permanently deleted. You can create a new account if you wish to return.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ========== SPAM PROTECTION START ==========
    // Validate honeypot, CSRF, and rate limiting
    $validation = validateFormProtection('login', 5, 60); // 5 attempts per 60 seconds

    if (!$validation['valid']) {
        $error = implode('<br>', $validation['errors']);
    } else {
        // Validate reCAPTCHA (if configured)
        if (isRecaptchaConfigured()) {
            $recaptcha = validateRecaptchaFromPost(0.5); // Score threshold: 0.5
            if (!$recaptcha['success']) {
                $error = $recaptcha['message'];
            }
        }

        // If all validations passed,proceed with login
        if (empty($error)) {
            $email    = sanitizeInput($_POST['email']);
            $password = $_POST['password'];


            $stmt = $conn->prepare("SELECT role FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();

            if ($row && $row['role'] !== ROLE_ADMIN) {
                $error = 'Account not found.';
            } else {
                $result = loginUser($email, $password);
                if ($result['success']) {
                    $user_id = $_SESSION['user_id'];

                    // Clean expired trusted devices
                    cleanExpiredDevices($user_id);

                    // Check if trusted device
                    if (isTrustedDevice($user_id)) {
                        logActivity('login_success', 'Logged in via trusted device');
                        header("Location: index.php");
                        exit();
                    }

                    $rate = checkRateLimit('2fa_send_' . $user_id, 5, 300); // 5 logins per 5 min
                    if (!$rate['allowed']) {
                        session_unset();
                        session_destroy();
                        $error = 'Too many login attempts. Please wait a few minutes before trying again.';
                    } else {
                        // New device — generate and send 2FA code
                        $otp = generate2FACode($user_id);
                        if ($otp) {
                            $sent = send2FAEmail($_SESSION['email'], $_SESSION['full_name'], $otp);
                            if ($sent) {
                                // Store temp session for 2FA — remove full session until verified
                                $temp = [
                                    'user_id'   => $_SESSION['user_id'],
                                    'full_name' => $_SESSION['full_name'],
                                    'email'     => $_SESSION['email'],
                                    'role'      => $_SESSION['role'],
                                ];
                                if (isset($_SESSION['admin_level'])) {
                                    $temp['admin_level'] = $_SESSION['admin_level'];
                                }
                                session_unset();
                                $_SESSION['2fa_user_id']    = $temp['user_id'];
                                $_SESSION['2fa_full_name']  = $temp['full_name'];
                                $_SESSION['2fa_email']      = $temp['email'];
                                $_SESSION['2fa_role']       = $temp['role'];
                                $_SESSION['2fa_started_at'] = time();
                                logActivity('login_2fa_sent', '2FA code sent — awaiting verification', $temp['user_id']);
                                header("Location: verify_2fa.php");
                                exit();
                            }
                        }//
                    }
                    session_unset();
                    session_destroy();
                    $error = 'Unable to send verification code. Please try again later.';
                } else {
                    logActivity('login_failed', 'Failed login attempt for: ' . $email, null);
                    $error = $result['message'];
                }
            }
        }
    }
    // ========== SPAM PROTECTION END ==========
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <?php echo loadRecaptchaScript(); ?>
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

        /* Background grid */
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

        /* Glow orbs */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            pointer-events: none;
            z-index: 0;
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

        /* Card */
        .auth-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 44px 40px;
            width: 100%;
            max-width: 440px;
            position: relative;
            z-index: 1;
            box-shadow: 0 30px 80px rgba(0,0,0,0.5);
            animation: slideUp 0.5s ease both;
        }

        @keyframes slideUp {
            from { opacity:0; transform: translateY(24px); }
            to   { opacity:1; transform: translateY(0); }
        }

        /* Top accent line */
        .auth-card::before {
            content: '';
            position: absolute;
            top: 0; left: 10%; right: 10%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--cyan), transparent);
            border-radius: 2px;
        }

        /* Brand */
        .auth-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 32px;
            text-decoration: none;
        }

        .brand-icon {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, var(--cyan), var(--cyan-2));
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
            color: var(--navy);
        }

        .brand-text {
            font-family: 'Sora', sans-serif;
            font-weight: 800;
            font-size: 1.2rem;
            color: #fff;
        }

        .brand-text span { color: var(--cyan); }

        /* Header */
        .auth-header { text-align: center; margin-bottom: 32px; }

        .auth-header h1 {
            font-family: 'Sora', sans-serif;
            font-weight: 700;
            font-size: 1.7rem;
            color: #fff;
            margin-bottom: 6px;
        }

        .auth-header p {
            font-size: 0.9rem;
            color: var(--muted);
        }

        /* Form */
        .form-label {
            font-size: 0.82rem;
            font-weight: 600;
            color: #c5d3e0;
            margin-bottom: 6px;
            letter-spacing: 0.3px;
        }

        .input-wrap {
            position: relative;
            margin-bottom: 18px;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: 1rem;
            pointer-events: none;
            z-index: 2;
        }

        .form-control {
            background: var(--navy-2);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: #fff;
            padding: 12px 14px 12px 42px;
            font-size: 0.92rem;
            width: 100%;
            transition: all 0.2s;
            font-family: 'DM Sans', sans-serif;
        }

        .form-control::placeholder { color: var(--muted); opacity: 0.7; }

        .form-control:focus {
            outline: none;
            border-color: var(--cyan);
            background: var(--navy-3);
            color: #fff;
            box-shadow: 0 0 0 3px rgba(0,194,224,0.12);
        }

        .form-control:focus + .input-icon,
        .input-wrap:focus-within .input-icon { color: var(--cyan); }

        /* Password toggle */
        .pass-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--muted);
            cursor: pointer;
            padding: 0;
            font-size: 1rem;
            z-index: 2;
            transition: color 0.2s;
        }
        .pass-toggle:hover { color: var(--cyan); }

        .form-control.has-toggle { padding-right: 42px; }

        /* Remember + forgot row */
        .form-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            margin-top: -4px;
        }

        .form-check-input {
            background-color: var(--navy-2);
            border-color: var(--border);
            width: 16px; height: 16px;
        }

        .form-check-input:checked {
            background-color: var(--cyan);
            border-color: var(--cyan);
        }

        .form-check-label {
            font-size: 0.83rem;
            color: var(--muted);
            cursor: pointer;
        }

        .forgot-link {
            font-size: 0.83rem;
            color: var(--cyan);
            text-decoration: none;
            transition: color 0.2s;
        }
        .forgot-link:hover { color: var(--cyan-2); }

        /* Submit button */
        .btn-submit {
            width: 100%;
            padding: 13px;
            background: var(--cyan);
            color: var(--navy);
            font-family: 'Sora', sans-serif;
            font-weight: 700;
            font-size: 0.95rem;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.25s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit:hover {
            background: var(--cyan-2);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0,194,224,0.35);
        }

        .btn-submit:active { transform: translateY(0); }

        /* Divider */
        .auth-divider {
            text-align: center;
            margin: 24px 0;
            position: relative;
        }

        .auth-divider::before {
            content: '';
            position: absolute;
            top: 50%; left: 0; right: 0;
            height: 1px;
            background: var(--border);
        }

        .auth-divider span {
            position: relative;
            background: var(--card);
            padding: 0 12px;
            font-size: 0.78rem;
            color: var(--muted);
        }

        /* Footer link */
        .auth-footer {
            text-align: center;
            font-size: 0.87rem;
            color: var(--muted);
        }

        .auth-footer a {
            color: var(--cyan);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }
        .auth-footer a:hover { color: var(--cyan-2); }

        /* Back to home */
        .back-home {
            position: fixed;
            top: 20px; left: 20px;
            display: flex; align-items: center; gap: 7px;
            font-size: 0.83rem;
            color: var(--muted);
            text-decoration: none;
            transition: color 0.2s;
            z-index: 10;
            background: rgba(13,27,42,0.7);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border);
            padding: 7px 14px;
            border-radius: 50px;
        }
        .back-home:hover { color: var(--cyan); border-color: rgba(0,194,224,0.3); }

        /* Alerts */
        .alert-custom {
            border-radius: 12px;
            padding: 13px 16px;
            font-size: 0.87rem;
            margin-bottom: 20px;
            border: 1px solid;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .alert-error {
            background: rgba(220,53,69,0.1);
            border-color: rgba(220,53,69,0.25);
            color: #f8a5ae;
        }

        .alert-success {
            background: rgba(39,201,63,0.1);
            border-color: rgba(39,201,63,0.25);
            color: #86efac;
        }

        .alert-custom i { flex-shrink: 0; margin-top: 2px; }

        .alert-link-custom { color: inherit; text-decoration: underline; opacity: 0.85; }

        /* Responsive */
        @media (max-width: 480px) {
            .auth-card { padding: 32px 24px; border-radius: 20px; }
            .auth-header h1 { font-size: 1.4rem; }
        }


    </style>
</head>
<body>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>


    <div class="auth-card">
        <!-- Brand -->
        <a href="" class="auth-brand">
            <div class="brand-icon"><i class="bi bi-clipboard2-check-fill"></i></div>
            <span class="brand-text">CMS<span>.</span></span>
        </a>

        <!-- Header -->
        <div class="auth-header">
            <h1>Welcome Back</h1>
            <p>Admin Portal Login</p>
        </div>

        <!-- Alerts -->
        <?php if (!empty($error)): ?>
<div class="alert-custom alert-error">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <div>
        <?php echo $error; ?>
        <?php if (isset($result['locked']) && $result['locked'] && isset($result['role']) === false): ?>
        <div class="mt-1" style="font-size:0.82rem; opacity:0.85;">
            Your account will be unlocked automatically. You can also
            <a href="forgot_password.php" class="alert-link-custom">reset your password</a>.
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

        <?php if (!empty($success)): ?>
        <div class="alert-custom alert-success">
            <i class="bi bi-check-circle-fill"></i>
            <div><?php echo $success; ?></div>
        </div>
        <?php endif; ?>

        <!-- Form -->
    <form method="POST" action="" data-recaptcha="login">
         <?php formProtection(); ?>
            <!-- Email -->
            <label class="form-label">Email Address</label>
            <div class="input-wrap">
                <i class="bi bi-envelope input-icon"></i>
                <input type="email" class="form-control" name="email" required
                       placeholder="you@email.com"
                       value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
            </div>

            <!-- Password -->
            <label class="form-label">Password</label>
            <div class="input-wrap">
                <i class="bi bi-lock input-icon"></i>
                <input type="password" class="form-control has-toggle" name="password"
                       id="passwordInput" required placeholder="Enter your password">
                <button type="button" class="pass-toggle" id="togglePass">
                    <i class="bi bi-eye" id="toggleIcon"></i>
                </button>
            </div>

            <!-- Remember + Forgot -->
            <div class="form-meta">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>
                <a href="forgot_password.php" class="forgot-link">Forgot password?</a>
            </div>

            <button type="submit" class="btn-submit">
                <i class="bi bi-box-arrow-in-right"></i> Login
            </button>
            <?php if (isRecaptchaConfigured()) {
                echo displayRecaptchaBadge();
            } ?>
        </form>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const togglePass = document.getElementById('togglePass');
        const passInput  = document.getElementById('passwordInput');
        const toggleIcon = document.getElementById('toggleIcon');

        togglePass.addEventListener('click', () => {
            const isPass = passInput.type === 'password';
            passInput.type = isPass ? 'text' : 'password';
            toggleIcon.className = isPass ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    </script>
</body>
</html>