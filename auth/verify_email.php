<?php
// auth/verify_email.php
require_once '../config/config.php';
require_once '../includes/functions.php';

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$error   = '';
$success = '';

if (empty($token)) {
    $error = 'Invalid verification link.';
} else {
    // Find user with this token
    $stmt = $conn->prepare("
        SELECT user_id, full_name, email, email_verified, token_expires_at 
        FROM users 
        WHERE verification_token = ? AND role = 'user'
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        $error = 'Invalid or already used verification link.';
    } elseif ($user['email_verified'] == 1) {
        $success = 'Your email is already verified. Please wait for admin approval.';
    } elseif (strtotime($user['token_expires_at']) < time()) {
        // Expired — delete the account so they can re-register
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user['user_id']);
        $stmt->execute();
        $error = 'Your verification link has expired. Please register again.';
    } else {
        // Mark as verified
        $stmt = $conn->prepare("
            UPDATE users 
            SET email_verified = 1, 
                verification_token = NULL, 
                token_expires_at = NULL 
            WHERE user_id = ?
        ");
        $stmt->bind_param("i", $user['user_id']);
        if ($stmt->execute()) {
            $success = 'Email verified successfully! Your account is now pending admin approval. You will receive an email once approved.';
        } else {
            $error = 'Verification failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification — <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #0d1b2a; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { background: #111e2e; border: 1px solid rgba(0,194,224,0.15); border-radius: 20px; padding: 40px; max-width: 480px; width: 100%; text-align: center; color: white; }
        .icon { font-size: 4rem; margin-bottom: 20px; }
        .btn-primary { background: #00c2e0; border: none; color: #0d1b2a; font-weight: bold; padding: 12px 28px; border-radius: 10px; }
        .btn-primary:hover { background: #00e5ff; color: #0d1b2a; }
    </style>
</head>
<body>
    <div class="card">
        <?php if (!empty($success)): ?>
            <div class="icon">✅</div>
            <h3>Email Verified!</h3>
            <p class="text-muted"><?php echo $success; ?></p>
            <a href="login.php" class="btn btn-primary mt-3">
                <i class="bi bi-box-arrow-in-right"></i> Go to Login
            </a>
        <?php else: ?>
            <div class="icon">❌</div>
            <h3>Verification Failed</h3>
            <p class="text-muted"><?php echo $error; ?></p>
            <a href="register.php" class="btn btn-primary mt-3">
                <i class="bi bi-person-plus"></i> Register Again
            </a>
        <?php endif; ?>
    </div>
</body>
</html>