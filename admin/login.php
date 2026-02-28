<?php
// ============================================
// ADMIN LOGIN PAGE
// admin/login.php
// ============================================

require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/security_helper.php';
require_once '../includes/recaptcha_helper.php';

// If already logged in as admin, redirect to dashboard
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: index.php");
    } else {
        // Logged in but as user — boot them out
        session_unset();
        session_destroy();
        header("Location: login.php");
    }
    exit();
}

$error   = '';
$success = '';
$result  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $validation = validateFormProtection('admin_login', 5, 60);

    if (!$validation['valid']) {
        $error = implode('<br>', $validation['errors']);
    } else {
        if (isRecaptchaConfigured()) {
            $recaptcha = validateRecaptchaFromPost(0.5);
            if (!$recaptcha['success']) {
                $error = $recaptcha['message'];
            }
        }

       if (empty($error)) {
            $email    = sanitizeInput($_POST['email']);
            $password = $_POST['password'];

            // Pre-check role BEFORE calling loginUser()
            $stmt = $conn->prepare("SELECT role FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();

           if ($row && $row['role'] !== ROLE_ADMIN) {
    $error = 'Invalid credentials.';
    }       else {
                 $result = loginUser($email, $password);
                if ($result['success']) {
                    header("Location: index.php");
                    exit();
                } else {
                    $error = $result['message'];
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — <?php echo SITE_NAME; ?></title>
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
            /* Admin accent — slightly different tint para distinct */
            --admin-accent: rgba(247,37,133,0.15);
            --admin-border: rgba(247,37,133,0.25);
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
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            pointer-events: none;
            z-index: 0;
        }
        .orb-1 {
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(247,37,133,0.1) 0%, transparent 70%);
            top: -100px; right: -100px;
        }
        .orb-2 {
            width: 300px; height: 300px;
            background: radial-gradient(circle, rgba(0,194,224,0.08) 0%, transparent 70%);
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

        /* Admin accent line — pink/magenta instead of cyan */
        .auth-card::before {
            content: '';
            position: absolute;
            top: 0; left: 10%; right: 10%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #f72585, transparent);
            border-radius: 2px;
        }

        .auth-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 8px;
            text-decoration: none;
        }

        .brand-icon {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, #f72585, #b5179e);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
            color: #fff;
        }

        .brand-text {
            font-family: 'Sora', sans-serif;
            font-weight: 800;
            font-size: 1.2rem;
            color: #fff;
        }
        .brand-text span { color: #f72585; }

        /* Admin badge below brand */
        .admin-badge {
            display: flex;
            justify-content: center;
            margin-bottom: 28px;
        }
        .admin-badge span {
            background: var(--admin-accent);
            border: 1px solid var(--admin-border);
            color: #f72585;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            padding: 4px 12px;
            border-radius: 50px;
        }

        .auth-header { text-align: center; margin-bottom: 32px; }
        .auth-header h1 {
            font-family: 'Sora', sans-serif;
            font-weight: 700;
            font-size: 1.7rem;
            color: #fff;
            margin-bottom: 6px;
        }
        .auth-header p { font-size: 0.9rem; color: var(--muted); }

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
            transition: color 0.2s;
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
            border-color: #f72585;
            background: var(--navy-3);
            color: #fff;
            box-shadow: 0 0 0 3px rgba(247,37,133,0.1);
        }
        .input-wrap:focus-within .input-icon { color: #f72585; }
        .form-control.has-toggle { padding-right: 42px; }

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
        .pass-toggle:hover { color: #f72585; }

        .form-meta {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 24px;
            margin-top: -4px;
        }

        .forgot-link {
            font-size: 0.83rem;
            color: var(--cyan);
            text-decoration: none;
            transition: color 0.2s;
        }
        .forgot-link:hover { color: var(--cyan-2); }

        .btn-submit {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #f72585, #b5179e);
            color: #fff;
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
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(247,37,133,0.35);
        }
        .btn-submit:active { transform: translateY(0); }

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
        .alert-custom i { flex-shrink: 0; margin-top: 2px; }
        .alert-link-custom { color: inherit; text-decoration: underline; opacity: 0.85; }

        /* Security notice at bottom */
        .security-notice {
            margin-top: 24px;
            padding: 10px 14px;
            background: rgba(247,37,133,0.06);
            border: 1px solid rgba(247,37,133,0.15);
            border-radius: 10px;
            display: flex;
            gap: 8px;
            align-items: flex-start;
            font-size: 0.78rem;
            color: var(--muted);
        }
        .security-notice i { color: #f72585; flex-shrink: 0; margin-top: 2px; }

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
        <a href="../index.php" class="auth-brand">
            <div class="brand-icon"><i class="bi bi-shield-lock-fill"></i></div>
            <span class="brand-text">CMS<span>.</span></span>
        </a>

        <!-- Admin Badge -->
        <div class="admin-badge">
            <span><i class="bi bi-shield-fill me-1"></i> Admin Portal</span>
        </div>

        <!-- Header -->
        <div class="auth-header">
            <h1>Admin Access</h1>
            <p>Restricted to authorized personnel only</p>
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

        <!-- Form -->
        <form method="POST" action="" data-recaptcha="admin_login">
            <?php formProtection(); ?>

            <!-- Email -->
            <label class="form-label">Email Address</label>
            <div class="input-wrap">
                <i class="bi bi-envelope input-icon"></i>
                <input type="email" class="form-control" name="email" required
                       placeholder="admin@email.com"
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

            <div class="form-meta">
               <a href="forgot_password.php" 
                class="forgot-link">Forgot password?</a>
            </div>

            <button type="submit" class="btn-submit">
                <i class="bi bi-shield-lock"></i> Login
            </button>
            <?php if (isRecaptchaConfigured()) echo displayRecaptchaBadge(); ?>
        </form>

        <!-- Security Notice -->
        <div class="security-notice">
            <i class="bi bi-info-circle-fill"></i>
            <span>This is a restricted area. Unauthorized access attempts are logged and monitored.</span>
        </div>
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