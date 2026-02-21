<?php
// ============================================
// REGISTRATION PAGE
// auth/register.php
// ============================================

require_once '../config/config.php';
require_once '../includes/functions.php';

if (isLoggedIn()) {
    header("Location: " . (isAdmin() ? '../admin/index.php' : '../user/index.php'));
    exit();
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name        = sanitizeInput($_POST['full_name']);
    $email            = sanitizeInput($_POST['email']);
    $phone            = sanitizeInput($_POST['phone']);
    $password         = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $result = registerUser($full_name, $email, $phone, $password);
        if ($result['success']) {
            $success   = 'Registration successful! Your account is pending approval. You will receive an email once your account is approved by an administrator.';
            $full_name = $email = $phone = '';
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — <?php echo SITE_NAME; ?></title>
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
            align-items: flex-start;
            justify-content: center;
            padding: 40px 20px;
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
            width: 100%;
            max-width: 500px;
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
            font-size: 1.1rem; color: var(--navy);
        }
        .brand-text {
            font-family: 'Sora', sans-serif;
            font-weight: 800; font-size: 1.2rem; color: #fff;
        }
        .brand-text span { color: var(--cyan); }

        .auth-header { text-align: center; margin-bottom: 32px; }
        .auth-header h1 {
            font-family: 'Sora', sans-serif;
            font-weight: 700; font-size: 1.7rem; color: #fff; margin-bottom: 6px;
        }
        .auth-header p { font-size: 0.9rem; color: var(--muted); }

        .form-label {
            font-size: 0.82rem;
            font-weight: 600;
            color: #c5d3e0;
            margin-bottom: 6px;
            letter-spacing: 0.3px;
            display: block;
        }

        .input-wrap {
            position: relative;
            margin-bottom: 18px;
        }

        .input-icon {
            position: absolute;
            left: 14px; top: 50%;
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
            border-color: var(--cyan);
            background: var(--navy-3);
            color: #fff;
            box-shadow: 0 0 0 3px rgba(0,194,224,0.12);
        }

        .input-wrap:focus-within .input-icon { color: var(--cyan); }

        .form-control.has-toggle { padding-right: 42px; }

        .pass-toggle {
            position: absolute;
            right: 14px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            color: var(--muted);
            cursor: pointer; padding: 0; font-size: 1rem; z-index: 2;
            transition: color 0.2s;
        }
        .pass-toggle:hover { color: var(--cyan); }

        /* Password strength */
        .pass-hint {
            font-size: 0.76rem;
            color: var(--muted);
            margin-top: -12px;
            margin-bottom: 16px;
            padding-left: 2px;
        }

        /* Strength bar */
        .strength-bar {
            height: 3px;
            border-radius: 2px;
            margin-top: 8px;
            background: var(--navy-3);
            overflow: hidden;
        }
        .strength-fill {
            height: 100%;
            width: 0;
            border-radius: 2px;
            transition: all 0.3s;
        }

        /* Terms */
        .terms-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 24px;
        }
        .form-check-input {
            background-color: var(--navy-2);
            border-color: var(--border);
            width: 16px; height: 16px;
            flex-shrink: 0;
            margin-top: 3px;
        }
        .form-check-input:checked {
            background-color: var(--cyan);
            border-color: var(--cyan);
        }
        .terms-label {
            font-size: 0.83rem;
            color: var(--muted);
            line-height: 1.5;
        }
        .terms-label a {
            color: var(--cyan);
            text-decoration: none;
            font-weight: 600;
        }
        .terms-label a:hover { color: var(--cyan-2); }

        .btn-submit {
            width: 100%;
            padding: 13px;
            background: var(--cyan);
            color: var(--navy);
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

        .auth-divider {
            text-align: center; margin: 24px 0; position: relative;
        }
        .auth-divider::before {
            content: ''; position: absolute;
            top: 50%; left: 0; right: 0; height: 1px; background: var(--border);
        }
        .auth-divider span {
            position: relative; background: var(--card);
            padding: 0 12px; font-size: 0.78rem; color: var(--muted);
        }

        .auth-footer { text-align: center; font-size: 0.87rem; color: var(--muted); }
        .auth-footer a {
            color: var(--cyan); text-decoration: none; font-weight: 600; transition: color 0.2s;
        }
        .auth-footer a:hover { color: var(--cyan-2); }

        .back-home {
            position: fixed; top: 20px; left: 20px;
            display: flex; align-items: center; gap: 7px;
            font-size: 0.83rem; color: var(--muted);
            text-decoration: none; transition: color 0.2s;
            z-index: 10;
            background: rgba(13,27,42,0.7);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border);
            padding: 7px 14px; border-radius: 50px;
        }
        .back-home:hover { color: var(--cyan); border-color: rgba(0,194,224,0.3); }

        .alert-custom {
            border-radius: 12px; padding: 13px 16px;
            font-size: 0.87rem; margin-bottom: 20px;
            border: 1px solid;
            display: flex; align-items: flex-start; gap: 10px;
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

        /* Pending badge */
        .pending-info {
            background: rgba(0,194,224,0.07);
            border: 1px solid rgba(0,194,224,0.2);
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 0.8rem;
            color: var(--muted);
            margin-top: 16px;
            display: flex; gap: 8px; align-items: flex-start;
        }
        .pending-info i { color: var(--cyan); flex-shrink: 0; margin-top: 2px; }

        @media (max-width: 480px) {
            body { padding: 20px 16px; }
            .auth-card { padding: 28px 20px; border-radius: 20px; }
            .auth-header h1 { font-size: 1.4rem; }
            .back-home { display: none; }
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
            <h1>Create Account</h1>
            <p>Register to <?php echo SITE_NAME; ?></p>
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
            <div>
                <strong>Registration submitted!</strong><br>
                Your account is pending admin approval. We'll email you once it's approved.
            </div>
        </div>
        <?php else: ?>
        <!-- Form (hide after success) -->
        <form method="POST" action="" id="registerForm">
            <!-- Full Name -->
            <label class="form-label">Full Name <span style="color:#f72585;">*</span></label>
            <div class="input-wrap">
                <i class="bi bi-person input-icon"></i>
                <input type="text" class="form-control" name="full_name" required
                       placeholder="Juan Dela Cruz"
                       value="<?php echo isset($full_name) ? htmlspecialchars($full_name) : ''; ?>">
            </div>

            <!-- Email -->
            <label class="form-label">Email Address <span style="color:#f72585;">*</span></label>
            <div class="input-wrap">
                <i class="bi bi-envelope input-icon"></i>
                <input type="email" class="form-control" name="email" required
                       placeholder="you@email.com"
                       value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
            </div>

            <!-- Phone -->
            <label class="form-label">Phone Number <span style="color:var(--muted); font-weight:400;">(optional)</span></label>
            <div class="input-wrap">
                <i class="bi bi-telephone input-icon"></i>
                <input type="tel" class="form-control" name="phone"
                       placeholder="09123456789"
                       value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>">
            </div>

            <!-- Password -->
            <label class="form-label">Password <span style="color:#f72585;">*</span></label>
            <div class="input-wrap">
                <i class="bi bi-lock input-icon"></i>
                <input type="password" class="form-control has-toggle" name="password"
                       id="passwordInput" required placeholder="Min. 8 characters">
                <button type="button" class="pass-toggle" id="togglePass">
                    <i class="bi bi-eye" id="toggleIcon"></i>
                </button>
            </div>
            <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
            <p class="pass-hint" id="passHint">Use uppercase, lowercase & numbers</p>

            <!-- Confirm Password -->
            <label class="form-label">Confirm Password <span style="color:#f72585;">*</span></label>
            <div class="input-wrap">
                <i class="bi bi-lock-fill input-icon"></i>
                <input type="password" class="form-control" name="confirm_password"
                       id="confirmInput" required placeholder="Re-enter your password">
            </div>

            <!-- Terms -->
            <div class="terms-row">
                <input class="form-check-input" type="checkbox" id="terms" required>
                <label class="terms-label" for="terms">
                    I agree to the <a href="terms.php" target="_blank">Terms and Conditions</a> and <a href="privacy.php" target="_blank">Privacy Policy</a>
                </label>
            </div>

            <button type="submit" class="btn-submit">
                <i class="bi bi-person-plus-fill"></i> Create Account
            </button>
        </form>

        <div class="pending-info">
            <i class="bi bi-info-circle-fill"></i>
            New accounts require admin approval before login. This usually takes 1–2 business days.
        </div>
        <?php endif; ?>

        <div class="auth-divider"><span>or</span></div>

        <div class="auth-footer">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password toggle
        const togglePass = document.getElementById('togglePass');
        const passInput  = document.getElementById('passwordInput');
        const toggleIcon = document.getElementById('toggleIcon');
        if (togglePass) {
            togglePass.addEventListener('click', () => {
                const isPass = passInput.type === 'password';
                passInput.type = isPass ? 'text' : 'password';
                toggleIcon.className = isPass ? 'bi bi-eye-slash' : 'bi bi-eye';
            });
        }

        // Password strength
        const strengthFill = document.getElementById('strengthFill');
        const passHint     = document.getElementById('passHint');
        if (passInput) {
            passInput.addEventListener('input', () => {
                const val = passInput.value;
                let score = 0;
                if (val.length >= 8)          score++;
                if (/[A-Z]/.test(val))        score++;
                if (/[a-z]/.test(val))        score++;
                if (/[0-9]/.test(val))        score++;
                if (/[^A-Za-z0-9]/.test(val)) score++;

                const colors = ['#dc3545','#ffc107','#ffc107','#20c997','#00c2e0'];
                const labels = ['Too short','Weak','Fair','Good','Strong'];
                const width  = [20, 40, 60, 80, 100];

                if (val.length === 0) {
                    strengthFill.style.width = '0';
                    passHint.textContent = 'Use uppercase, lowercase & numbers';
                    passHint.style.color = 'var(--muted)';
                } else {
                    const i = Math.max(0, score - 1);
                    strengthFill.style.width  = width[i] + '%';
                    strengthFill.style.background = colors[i];
                    passHint.textContent = labels[i];
                    passHint.style.color = colors[i];
                }
            });
        }

        // Confirm password live check
        const confirmInput = document.getElementById('confirmInput');
        if (confirmInput) {
            confirmInput.addEventListener('input', () => {
                if (confirmInput.value && confirmInput.value !== passInput.value) {
                    confirmInput.style.borderColor = 'rgba(220,53,69,0.5)';
                } else {
                    confirmInput.style.borderColor = confirmInput.value ? 'rgba(39,201,63,0.4)' : 'var(--border)';
                }
            });
        }

        // Submit validation
        const form = document.getElementById('registerForm');
        if (form) {
            form.addEventListener('submit', (e) => {
                const pass    = passInput ? passInput.value : '';
                const confirm = confirmInput ? confirmInput.value : '';
                if (pass !== confirm) {
                    e.preventDefault();
                    confirmInput.style.borderColor = 'rgba(220,53,69,0.5)';
                    confirmInput.focus();
                }
            });
        }
    </script>
</body>
</html>