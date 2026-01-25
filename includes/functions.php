<?php
// ============================================
// HELPER FUNCTIONS
// includes/functions.php
// ============================================

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Function to validate email
function isValidEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to validate phone number
function isValidPhone($phone)
{
    return preg_match('/^[0-9]{10,15}$/', $phone);
}

// Function to validate password strength
function isStrongPassword($password)
{
    // At least 8 characters, one uppercase, one lowercase, one number
    return strlen($password) >= 8 &&
        preg_match('/[A-Z]/', $password) &&
        preg_match('/[a-z]/', $password) &&
        preg_match('/[0-9]/', $password);
}

// Function to hash password
function hashPassword($password)
{
    return password_hash($password, PASSWORD_DEFAULT);
}

// Function to verify password
function verifyPassword($password, $hash)
{
    return password_verify($password, $hash);
}

// Function to generate random token
function generateToken($length = 32)
{
    return bin2hex(random_bytes($length));
}

// Function to register a new user
function registerUser($full_name, $email, $phone, $password)
{
    global $conn;

    // Validate inputs
    if (empty($full_name) || empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'All fields are required'];
    }

    if (!isValidEmail($email)) {
        return ['success' => false, 'message' => 'Invalid email format'];
    }
    // Check if email domain exists (basic validation)
    list($user, $domain) = explode('@', $email);
    if (!checkdnsrr($domain, 'MX')) {
        return ['success' => false, 'message' => 'Email domain does not exist. Please use a valid email address.'];
    }

    if (!empty($phone) && !isValidPhone($phone)) {
        return ['success' => false, 'message' => 'Invalid phone number format'];
    }

    if (!isStrongPassword($password)) {
        return ['success' => false, 'message' => 'Password must be at least 8 characters with uppercase, lowercase, and numbers'];
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return ['success' => false, 'message' => 'Email already registered'];
    }

    // Hash password
    $hashed_password = hashPassword($password);

    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password, role) VALUES (?, ?, ?, ?, 'user')");
    $stmt->bind_param("ssss", $full_name, $email, $phone, $hashed_password);

    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Registration successful'];
    } else {
        return ['success' => false, 'message' => 'Registration failed. Please try again'];
    }
}

// Function to login user
function loginUser($email, $password)
{
    global $conn;

    if (empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'Email and password are required'];
    }

    $ip_address = getClientIP();

    // Check if account is locked
    $lock_status = isAccountLocked($email);
    if ($lock_status['locked']) {
        return [
            'success' => false,
            'message' => 'Account is locked due to too many failed login attempts. Please try again in ' . $lock_status['remaining_minutes'] . ' minute(s).',
            'locked' => true,
            'unlock_time' => $lock_status['unlock_time']
        ];
    }

    // Fetch user from database
    $stmt = $conn->prepare("SELECT user_id, full_name, email, password, role, admin_level, status, approval_status, failed_login_attempts FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // Record failed attempt
        recordFailedLogin($email, $ip_address);
        return ['success' => false, 'message' => 'Invalid email or password'];
    }

    $user = $result->fetch_assoc();

    // Verify password
    if (!verifyPassword($password, $user['password'])) {
        // Record failed attempt
        $attempt_result = recordFailedLogin($email, $ip_address);

        if ($attempt_result['locked']) {
            return [
                'success' => false,
                'message' => 'Too many failed login attempts! Your account has been locked for ' . LOCKOUT_DURATION . ' minutes.',
                'locked' => true
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Invalid email or password. ' . $attempt_result['remaining'] . ' attempt(s) remaining before account lockout.',
                'attempts_remaining' => $attempt_result['remaining']
            ];
        }
    }

    // Check if account is active
    if ($user['status'] !== 'active') {
        return ['success' => false, 'message' => 'Your account is inactive. Contact administrator'];
    }

    // Check if account is approved
    if (isset($user['approval_status']) && $user['approval_status'] === 'pending') {
        return ['success' => false, 'message' => 'Your account is pending approval. You will receive an email once approved.'];
    }

    if (isset($user['approval_status']) && $user['approval_status'] === 'rejected') {
        return ['success' => false, 'message' => 'Your account has been rejected. Please contact administrator.'];
    }

    // Successful login - record it and reset attempts
    recordSuccessfulLogin($email, $ip_address);

    // Set session variables
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];

    // Set admin_level if user is admin
    if ($user['role'] === 'admin') {
        $_SESSION['admin_level'] = $user['admin_level'] ?? 'admin';
    }

    return ['success' => true, 'message' => 'Login successful', 'role' => $user['role']];
}



// Function to check if user is super admin
function isSuperAdmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === ROLE_ADMIN &&
        isset($_SESSION['admin_level']) && $_SESSION['admin_level'] === 'super_admin';
}

// Function to require super admin access
function requireSuperAdmin()
{
    requireLogin();
    if (!isSuperAdmin()) {
        header("Location: " . SITE_URL . "admin/index.php");
        exit();
    }
}

// Function to logout user
function logoutUser()
{
    session_unset();
    session_destroy();
    header("Location: " . SITE_URL . "auth/login.php");
    exit();
}

// Function to get user by ID
function getUserById($user_id)
{
    global $conn;

    $stmt = $conn->prepare("SELECT user_id, full_name, email, phone, role, status, profile_picture, created_at FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc();
}

// Function to update user profile
function updateUserProfile($user_id, $full_name, $email, $phone)
{
    global $conn;

    if (empty($full_name) || empty($email)) {
        return ['success' => false, 'message' => 'Name and email are required'];
    }

    if (!isValidEmail($email)) {
        return ['success' => false, 'message' => 'Invalid email format'];
    }

    if (!empty($phone) && !isValidPhone($phone)) {
        return ['success' => false, 'message' => 'Invalid phone number format'];
    }

    // Check if email is already used by another user
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return ['success' => false, 'message' => 'Email already used by another account'];
    }

    // Update user
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE user_id = ?");
    $stmt->bind_param("sssi", $full_name, $email, $phone, $user_id);

    if ($stmt->execute()) {
        // Update session
        $_SESSION['full_name'] = $full_name;
        $_SESSION['email'] = $email;
        return ['success' => true, 'message' => 'Profile updated successfully'];
    } else {
        return ['success' => false, 'message' => 'Update failed. Please try again'];
    }
}


// Function to change password
function changePassword($user_id, $current_password, $new_password)
{
    global $conn;

    if (empty($current_password) || empty($new_password)) {
        return ['success' => false, 'message' => 'All fields are required'];
    }

    if (!isStrongPassword($new_password)) {
        return ['success' => false, 'message' => 'New password must be at least 8 characters with uppercase, lowercase, and numbers'];
    }

    // Get current password hash
    $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Verify current password
    if (!verifyPassword($current_password, $user['password'])) {
        return ['success' => false, 'message' => 'Current password is incorrect'];
    }

    // Hash new password
    $hashed_password = hashPassword($new_password);

    // Update password
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    $stmt->bind_param("si", $hashed_password, $user_id);

    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Password changed successfully'];
    } else {
        return ['success' => false, 'message' => 'Password change failed. Please try again'];
    }
}

// Function to get all categories
function getAllCategories()
{
    global $conn;

    $result = $conn->query("SELECT * FROM categories WHERE status = 'active' ORDER BY category_name ASC");
    $categories = [];

    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }

    return $categories;
}

// Function to show alert message
function showAlert($message, $type = 'info')
{
    $alertClass = 'alert-' . $type;
    echo "<div class='alert $alertClass alert-dismissible fade show' role='alert'>
            $message
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
          </div>";
}
// Function to create notification
function createNotification($user_id, $title, $message, $type = 'info', $complaint_id = null)
{
    global $conn;

    $stmt = $conn->prepare("INSERT INTO notifications (user_id, complaint_id, title, message, type) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisss", $user_id, $complaint_id, $title, $message, $type);

    return $stmt->execute();
}

// Function to get unread notification count
function getUnreadNotificationCount($user_id)
{
    global $conn;

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc()['count'];
}

// Function to get recent notifications
function getRecentNotifications($user_id, $limit = 5)
{
    global $conn;

    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();

    return $stmt->get_result();
}

// Function to mark notification as read
function markNotificationAsRead($notification_id)
{
    global $conn;

    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ?");
    $stmt->bind_param("i", $notification_id);

    return $stmt->execute();
}

// Function to mark all notifications as read
function markAllNotificationsAsRead($user_id)
{
    global $conn;

    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);

    return $stmt->execute();
}

// Function to add comment
function addComment($complaint_id, $user_id, $comment)
{
    global $conn;

    if (empty($comment)) {
        return ['success' => false, 'message' => 'Comment cannot be empty'];
    }

    $stmt = $conn->prepare("INSERT INTO complaint_comments (complaint_id, user_id, comment) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $complaint_id, $user_id, $comment);

    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Comment added successfully'];
    } else {
        return ['success' => false, 'message' => 'Failed to add comment'];
    }
}

// Function to get comments for a complaint
function getComplaintComments($complaint_id)
{
    global $conn;

    $stmt = $conn->prepare("
        SELECT c.*, u.full_name, u.role 
        FROM complaint_comments c
        JOIN users u ON c.user_id = u.user_id
        WHERE c.complaint_id = ?
        ORDER BY c.created_at ASC
    ");
    $stmt->bind_param("i", $complaint_id);
    $stmt->execute();

    return $stmt->get_result();
}

// Function to get comment count
function getCommentCount($complaint_id)
{
    global $conn;

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM complaint_comments WHERE complaint_id = ?");
    $stmt->bind_param("i", $complaint_id);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc()['count'];
}

// Function to send email using PHPMailer
function sendEmail($to, $subject, $message)
{
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'cmsproperty278@gmail.com';        // ⚠️ CHANGE THIS to your Gmail
        $mail->Password   = 'wuat cpva ncok muqw';   // ⚠️ CHANGE THIS to your App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('cmsproperty278@gmail.com', SITE_NAME);  // ⚠️ CHANGE THIS
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log error for debugging
        error_log("Email Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Function to send approval email
function sendApprovalEmail($user_email, $user_name, $status)
{
    if ($status === 'approved') {
        $subject = "Account Approved - " . SITE_NAME;
        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f4f4f4; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 20px auto; background: white; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
                .header h1 { margin: 0; font-size: 28px; }
                .content { padding: 30px; }
                .content h2 { color: #667eea; margin-top: 0; }
                .button { display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; color: #6c757d; font-size: 12px; background: #f8f9fa; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎉 " . SITE_NAME . "</h1>
                </div>
                <div class='content'>
                    <h2>Hello " . htmlspecialchars($user_name) . ",</h2>
                    <p style='font-size: 16px;'>Great news! Your account has been <strong style='color: #28a745;'>approved</strong> by our administrator.</p>
                    <p>You can now login and start using our complaint management system.</p>
                    <div style='text-align: center;'>
                        <a href='" . SITE_URL . "auth/login.php' class='button'>Login to Your Account</a>
                    </div>
                    <p>If you have any questions, feel free to contact us at <a href='mailto:" . ADMIN_EMAIL . "'>" . ADMIN_EMAIL . "</a></p>
                    <p style='margin-top: 30px;'>Best regards,<br><strong>" . SITE_NAME . " Team</strong></p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " " . SITE_NAME . ". All rights reserved.</p>
                    <p>This is an automated email. Please do not reply directly to this message.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    } else {
        $subject = "Account Registration Status - " . SITE_NAME;
        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f4f4f4; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 20px auto; background: white; }
                .header { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 30px; text-align: center; }
                .header h1 { margin: 0; font-size: 28px; }
                .content { padding: 30px; }
                .content h2 { color: #dc3545; margin-top: 0; }
                .footer { text-align: center; padding: 20px; color: #6c757d; font-size: 12px; background: #f8f9fa; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>" . SITE_NAME . "</h1>
                </div>
                <div class='content'>
                    <h2>Hello " . htmlspecialchars($user_name) . ",</h2>
                    <p style='font-size: 16px;'>We regret to inform you that your account registration has been <strong style='color: #dc3545;'>not approved</strong> at this time.</p>
                    <p>If you believe this is a mistake or need more information, please contact our support team:</p>
                    <p><strong>Email:</strong> <a href='mailto:" . ADMIN_EMAIL . "'>" . ADMIN_EMAIL . "</a></p>
                    <p style='margin-top: 30px;'>Best regards,<br><strong>" . SITE_NAME . " Team</strong></p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " " . SITE_NAME . ". All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    return sendEmail($user_email, $subject, $message);
}
// Function to check daily complaint limit
function checkDailyComplaintLimit($user_id)
{
    global $conn;

    $today = date('Y-m-d');

    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM complaints 
        WHERE user_id = ? 
        AND DATE(submitted_date) = ?
    ");
    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    return [
        'count' => (int)$result['count'],
        'limit' => DAILY_COMPLAINT_LIMIT,
        'remaining' => DAILY_COMPLAINT_LIMIT - (int)$result['count'],
        'can_submit' => (int)$result['count'] < DAILY_COMPLAINT_LIMIT
    ];
}

// Function to get user's complaints today
function getTodayComplaintsCount($user_id)
{
    global $conn;

    $today = date('Y-m-d');

    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM complaints 
        WHERE user_id = ? 
        AND DATE(submitted_date) = ?
    ");
    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();

    return (int)$stmt->get_result()->fetch_assoc()['count'];
}
// Function to check if account is locked
function isAccountLocked($email)
{
    global $conn;

    $stmt = $conn->prepare("SELECT account_locked_until FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return ['locked' => false];
    }

    $user = $result->fetch_assoc();

    if ($user['account_locked_until'] && strtotime($user['account_locked_until']) > time()) {
        $unlock_time = strtotime($user['account_locked_until']);
        $remaining_minutes = ceil(($unlock_time - time()) / 60);

        return [
            'locked' => true,
            'unlock_time' => $user['account_locked_until'],
            'remaining_minutes' => $remaining_minutes
        ];
    }

    // If lockout expired, unlock account
    if ($user['account_locked_until']) {
        $stmt = $conn->prepare("UPDATE users SET failed_login_attempts = 0, account_locked_until = NULL WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
    }

    return ['locked' => false];
}

// Function to record failed login attempt
function recordFailedLogin($email, $ip_address)
{
    global $conn;

    // Log the attempt
    $stmt = $conn->prepare("INSERT INTO login_attempts (email, ip_address, success) VALUES (?, ?, 0)");
    $stmt->bind_param("ss", $email, $ip_address);
    $stmt->execute();

    // Update user's failed attempts
    $stmt = $conn->prepare("
        UPDATE users 
        SET failed_login_attempts = failed_login_attempts + 1,
            last_failed_login = NOW()
        WHERE email = ?
    ");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    // Check if lockout needed
    $stmt = $conn->prepare("SELECT failed_login_attempts FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $attempts = $user['failed_login_attempts'];

        if ($attempts >= MAX_LOGIN_ATTEMPTS) {
            // Lock the account
            $lockout_until = date('Y-m-d H:i:s', strtotime('+' . LOCKOUT_DURATION . ' minutes'));

            $stmt = $conn->prepare("UPDATE users SET account_locked_until = ? WHERE email = ?");
            $stmt->bind_param("ss", $lockout_until, $email);
            $stmt->execute();

            return [
                'locked' => true,
                'attempts' => $attempts,
                'lockout_duration' => LOCKOUT_DURATION
            ];
        }

        return [
            'locked' => false,
            'attempts' => $attempts,
            'remaining' => MAX_LOGIN_ATTEMPTS - $attempts
        ];
    }

    return ['locked' => false, 'attempts' => 1, 'remaining' => MAX_LOGIN_ATTEMPTS - 1];
}

// Function to record successful login
function recordSuccessfulLogin($email, $ip_address)
{
    global $conn;

    // Log successful attempt
    $stmt = $conn->prepare("INSERT INTO login_attempts (email, ip_address, success) VALUES (?, ?, 1)");
    $stmt->bind_param("ss", $email, $ip_address);
    $stmt->execute();

    // Reset failed attempts
    $stmt = $conn->prepare("
        UPDATE users 
        SET failed_login_attempts = 0,
            last_failed_login = NULL,
            account_locked_until = NULL
        WHERE email = ?
    ");
    $stmt->bind_param("s", $email);
    $stmt->execute();
}

// Function to get client IP address
function getClientIP()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}
// Function to generate OTP
function generateOTP($length = 6)
{
    return str_pad(rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

// Function to create password reset request
function createPasswordResetRequest($email)
{
    global $conn;

    // Check if email exists
    $stmt = $conn->prepare("SELECT user_id, full_name FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'No account found with this email address'];
    }

    $user = $result->fetch_assoc();

    // Generate OTP and token
    $otp = generateOTP(6);
    $token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes')); // OTP valid for 15 minutes

    // Delete old reset requests for this user
    $stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    // Insert new reset request
    $stmt = $conn->prepare("INSERT INTO password_resets (user_id, email, otp_code, token, expires_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user['user_id'], $email, $otp, $token, $expires_at);

    if ($stmt->execute()) {
        // Send OTP email
        $subject = "Password Reset OTP - " . SITE_NAME;
        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f4f4f4; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 20px auto; background: white; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; }
                .otp-box { background: #f8f9fa; border: 2px dashed #667eea; padding: 20px; text-align: center; margin: 20px 0; border-radius: 10px; }
                .otp-code { font-size: 32px; font-weight: bold; color: #667eea; letter-spacing: 5px; }
                .footer { text-align: center; padding: 20px; color: #6c757d; font-size: 12px; background: #f8f9fa; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔐 Password Reset Request</h1>
                </div>
                <div class='content'>
                    <h2>Hello " . htmlspecialchars($user['full_name']) . ",</h2>
                    <p>We received a request to reset your password. Use the OTP code below to proceed:</p>
                    
                    <div class='otp-box'>
                        <div style='font-size: 14px; color: #6c757d; margin-bottom: 10px;'>Your OTP Code</div>
                        <div class='otp-code'>" . $otp . "</div>
                        <div style='font-size: 12px; color: #6c757d; margin-top: 10px;'>Valid for 15 minutes</div>
                    </div>
                    
                    <p><strong>Important:</strong></p>
                    <ul>
                        <li>Do not share this code with anyone</li>
                        <li>This code expires in 15 minutes</li>
                        <li>If you didn't request this, please ignore this email</li>
                    </ul>
                    
                    <p style='margin-top: 30px;'>Best regards,<br><strong>" . SITE_NAME . " Team</strong></p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " " . SITE_NAME . ". All rights reserved.</p>
                    <p>This is an automated email. Please do not reply.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        sendEmail($email, $subject, $message);

        return [
            'success' => true,
            'message' => 'OTP has been sent to your email',
            'token' => $token
        ];
    }

    return ['success' => false, 'message' => 'Failed to create reset request'];
}

// Function to verify OTP
function verifyOTP($email, $otp)
{
    global $conn;

    $stmt = $conn->prepare("
        SELECT reset_id, token, user_id 
        FROM password_resets 
        WHERE email = ? AND otp_code = ? AND is_used = 0 AND expires_at > NOW()
    ");
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Invalid or expired OTP'];
    }

    $reset = $result->fetch_assoc();

    return [
        'success' => true,
        'message' => 'OTP verified successfully',
        'token' => $reset['token'],
        'user_id' => $reset['user_id']
    ];
}

// Function to reset password with token
function resetPasswordWithToken($token, $new_password)
{
    global $conn;

    // Verify token
    $stmt = $conn->prepare("
        SELECT user_id, email 
        FROM password_resets 
        WHERE token = ? AND is_used = 0 AND expires_at > NOW()
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Invalid or expired reset token'];
    }

    $reset = $result->fetch_assoc();

    // Validate password
    if (!isStrongPassword($new_password)) {
        return ['success' => false, 'message' => 'Password must be at least 8 characters with uppercase, lowercase, and numbers'];
    }

    // Update password
    $hashed_password = hashPassword($new_password);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    $stmt->bind_param("si", $hashed_password, $reset['user_id']);

    if ($stmt->execute()) {
        // Mark token as used
        $stmt = $conn->prepare("UPDATE password_resets SET is_used = 1 WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();

        // Reset failed login attempts
        $stmt = $conn->prepare("UPDATE users SET failed_login_attempts = 0, account_locked_until = NULL WHERE user_id = ?");
        $stmt->bind_param("i", $reset['user_id']);
        $stmt->execute();

        return ['success' => true, 'message' => 'Password reset successfully'];
    }

    return ['success' => false, 'message' => 'Failed to reset password'];
}
// Function to upload profile picture
function uploadProfilePicture($file, $user_id)
{
    // Allowed file types
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
    $max_size = 2 * 1024 * 1024; // 2MB

    // Validate file
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'message' => 'No file uploaded'];
    }

    // Check file type
    $file_type = $file['type'];
    if (!in_array($file_type, $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF are allowed'];
    }

    // Check file size
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File too large. Maximum size is 2MB'];
    }

    // Get file extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    // Generate unique filename
    $new_filename = 'user_' . $user_id . '_' . time() . '.' . $extension;

    // Upload directory
    $upload_dir = dirname(__DIR__) . '/uploads/avatars/';
    $upload_path = $upload_dir . $new_filename;
    $db_path = 'uploads/avatars/' . $new_filename;

    // Create directory if not exists
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        // Delete old profile picture (if exists and not default)
        global $conn;
        $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $old_pic = $result->fetch_assoc()['profile_picture'];
            if ($old_pic && file_exists(dirname(__DIR__) . '/' . $old_pic)) {
                // Don't delete default avatar
                if (!strpos($old_pic, 'default-avatar')) {
                    unlink(dirname(__DIR__) . '/' . $old_pic);
                }
            }
        }

        // Update database
        $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
        $stmt->bind_param("si", $db_path, $user_id);

        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Profile picture updated successfully',
                'file_path' => $db_path
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to update database'];
        }
    } else {
        return ['success' => false, 'message' => 'Failed to upload file'];
    }
}

// Function to delete profile picture
function deleteProfilePicture($user_id)
{
    global $conn;

    $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $pic = $result->fetch_assoc()['profile_picture'];

        if ($pic && file_exists(dirname(__DIR__) . '/' . $pic)) {
            unlink(dirname(__DIR__) . '/' . $pic);
        }

        // Set to NULL in database
        $stmt = $conn->prepare("UPDATE users SET profile_picture = NULL WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);

        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Profile picture deleted'];
        }
    }

    return ['success' => false, 'message' => 'Failed to delete picture'];
}

// Function to get user avatar (with fallback)
function getUserAvatar($user_id = null, $size = 'md')
{
    global $conn;

    $sizes = [
        'sm' => 35,
        'md' => 42,
        'lg' => 100,
        'xl' => 150
    ];

    $dimension = $sizes[$size] ?? 42;

    if ($user_id) {
        $stmt = $conn->prepare("SELECT profile_picture, full_name FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            if ($user['profile_picture'] && file_exists(dirname(__DIR__) . '/' . $user['profile_picture'])) {
                return '<img src="' . SITE_URL . $user['profile_picture'] . '" class="rounded-circle" width="' . $dimension . '" height="' . $dimension . '" style="object-fit: cover;" alt="Profile">';
            } else {
                // Show initials
                $initial = strtoupper(substr($user['full_name'], 0, 1));
                return '<div class="user-avatar" style="width: ' . $dimension . 'px; height: ' . $dimension . 'px; font-size: ' . ($dimension * 0.4) . 'px;">' . $initial . '</div>';
            }
        }
    }

    // Default avatar
    return '<div class="user-avatar" style="width: ' . $dimension . 'px; height: ' . $dimension . 'px;"><i class="bi bi-person"></i></div>';
}


// Function to delete user account permanently
function deleteUserAccount($user_id)
{
    global $conn;
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get user info before deletion
        $stmt = $conn->prepare("SELECT email, full_name, profile_picture FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        // 1. Delete profile picture file if exists
        if ($user['profile_picture'] && file_exists(dirname(__DIR__) . '/' . $user['profile_picture'])) {
            unlink(dirname(__DIR__) . '/' . $user['profile_picture']);
        }
        
        // 2. Delete complaint attachments files and records
        $stmt = $conn->prepare("
            SELECT ca.file_path 
            FROM complaint_attachments ca
            JOIN complaints c ON ca.complaint_id = c.complaint_id
            WHERE c.user_id = ?
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $attachments = $stmt->get_result();
        
        while ($attachment = $attachments->fetch_assoc()) {
            if (file_exists(dirname(__DIR__) . '/' . $attachment['file_path'])) {
                unlink(dirname(__DIR__) . '/' . $attachment['file_path']);
            }
        }
        
        // 3. Delete complaint attachments records (CASCADE will handle this, but explicit is safer)
        $stmt = $conn->prepare("
            DELETE ca FROM complaint_attachments ca
            JOIN complaints c ON ca.complaint_id = c.complaint_id
            WHERE c.user_id = ?
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // 4. Delete complaint comments (CASCADE will handle this)
        $stmt = $conn->prepare("
            DELETE cc FROM complaint_comments cc
            JOIN complaints c ON cc.complaint_id = c.complaint_id
            WHERE c.user_id = ?
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // 5. Delete complaint history (CASCADE will handle this)
        $stmt = $conn->prepare("
            DELETE ch FROM complaint_history ch
            JOIN complaints c ON ch.complaint_id = c.complaint_id
            WHERE c.user_id = ?
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // 6. Delete notifications
        $stmt = $conn->prepare("DELETE FROM notifications WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // 7. Delete password reset tokens
        $stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // 8. Delete login attempts
        $stmt = $conn->prepare("DELETE FROM login_attempts WHERE email = ?");
        $stmt->bind_param("s", $user['email']);
        $stmt->execute();
        
        // 9. Delete all complaints (CASCADE will handle related records)
        $stmt = $conn->prepare("DELETE FROM complaints WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // 10. Finally, delete the user
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        return ['success' => true, 'message' => 'Account deleted successfully'];
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        error_log("Account deletion error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to delete account. Please try again.'];
    }
}

// Function to send account deletion confirmation email
function sendAccountDeletionEmail($email, $name)
{
    $subject = "Account Deleted - " . SITE_NAME;
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f4f4f4; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 20px auto; background: white; }
            .header { background: #dc3545; color: white; padding: 30px; text-align: center; }
            .header h1 { margin: 0; font-size: 28px; }
            .content { padding: 30px; }
            .footer { text-align: center; padding: 20px; color: #6c757d; font-size: 12px; background: #f8f9fa; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>👋 Account Deleted</h1>
            </div>
            <div class='content'>
                <h2>Goodbye " . htmlspecialchars($name) . ",</h2>
                <p>Your account has been <strong>permanently deleted</strong> from " . SITE_NAME . ".</p>
                
                <p><strong>What was deleted:</strong></p>
                <ul>
                    <li>All your personal information</li>
                    <li>All your submitted complaints</li>
                    <li>All your comments and attachments</li>
                    <li>Your profile picture</li>
                </ul>
                
                <p>We're sorry to see you go. If you change your mind, you're welcome to create a new account anytime.</p>
                
                <p>If you didn't request this deletion, please contact us immediately at <a href='mailto:" . ADMIN_EMAIL . "'>" . ADMIN_EMAIL . "</a></p>
                
                <p style='margin-top: 30px;'>Best regards,<br><strong>" . SITE_NAME . " Team</strong></p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " " . SITE_NAME . ". All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $message);
}
