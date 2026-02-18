<?php
// ============================================
// GENERAL APPLICATION CONFIGURATION
// config/config.php
// ============================================

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
 use Cloudinary\Configuration\Configuration;
// LOAD .env FILE (LOCAL DEVELOPMENT ONLY)

$env_file = dirname(__DIR__) . '/.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comment lines
        if (strpos(trim($line), '#') === 0) continue;
        // Skip lines without =
        if (strpos($line, '=') === false) continue;

        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);

        // Only set if not already set by the system
        if (!getenv($key)) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}
// Path Settings
define('BASE_PATH',     dirname(__DIR__) . '/');
define('INCLUDES_PATH', BASE_PATH . 'includes/');
define('ASSETS_PATH',   BASE_PATH . 'assets/');

// ============================================
// APPLICATION SETTINGS
// ============================================
define('SITE_NAME', 'Complaint Management System');
define('SITE_URL',  getenv('SITE_URL') ?: 'http://localhost/cms3/');
define('ADMIN_EMAIL', 'cmsprop233@gmail.com');


// User Roles
define('ROLE_USER',  'user');
define('ROLE_ADMIN', 'admin');

// Complaint Status
define('STATUS_PENDING',     'Pending');
define('STATUS_IN_PROGRESS', 'In Progress');
define('STATUS_RESOLVED',    'Resolved');
define('STATUS_CLOSED',      'Closed');

// Complaint Priority
define('PRIORITY_LOW',    'Low');
define('PRIORITY_MEDIUM', 'Medium');
define('PRIORITY_HIGH',   'High');

// Pagination Settings
define('RECORDS_PER_PAGE', 10);

// Complaint Submission Limits
define('DAILY_COMPLAINT_LIMIT', 5);

// Login Security Settings
define('MAX_LOGIN_ATTEMPTS', 5);  // Maximum failed attempts before lockout
define('LOCKOUT_DURATION',   15); // Lockout duration in minutes
define('ATTEMPT_RESET_TIME', 30); // Reset failed attempts after X minutes

// ============================================
// CLOUDINARY CONFIGURATION
// Values come from .env (local) or
// Render Dashboard (production)
// ============================================
define('CLOUDINARY_CLOUD_NAME', getenv('CLOUDINARY_CLOUD_NAME') ?: '');
define('CLOUDINARY_API_KEY',    getenv('CLOUDINARY_API_KEY')    ?: '');
define('CLOUDINARY_API_SECRET', getenv('CLOUDINARY_API_SECRET') ?: '');

// Initialize Cloudinary SDK
if (file_exists(BASE_PATH . 'vendor/autoload.php')) {
    require_once BASE_PATH . 'vendor/autoload.php';

    if (CLOUDINARY_CLOUD_NAME && CLOUDINARY_API_KEY && CLOUDINARY_API_SECRET) {
       

        Configuration::instance([
            'cloud' => [
                'cloud_name' => CLOUDINARY_CLOUD_NAME,
                'api_key'    => CLOUDINARY_API_KEY,
                'api_secret' => CLOUDINARY_API_SECRET,
            ],
            'url' => ['secure' => true]
        ]);
    }
}

// Include Cloudinary helper functions
if (file_exists(INCLUDES_PATH . 'cloudinary_helper.php')) {
    require_once INCLUDES_PATH . 'cloudinary_helper.php';
}

// ============================================
// INCLUDE DATABASE CONNECTION
// ============================================
require_once 'database.php';

// ============================================
// HELPER FUNCTIONS
// ============================================

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === ROLE_ADMIN;
}

function isUser() {
    return isset($_SESSION['role']) && $_SESSION['role'] === ROLE_USER;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: " . SITE_URL . "auth/login.php");
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: " . SITE_URL . "user/index.php");
        exit();
    }
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('M d, Y h:i A', strtotime($datetime));
}

function daysElapsed($date) {
    $start    = new DateTime($date);
    $end      = new DateTime();
    $interval = $start->diff($end);
    return $interval->days;
}

function getStatusBadge($status) {
    $badges = [
        'Pending'     => 'badge bg-warning text-dark',
        'Assigned'    => 'badge bg-info',
        'In Progress' => 'badge bg-primary',
        'On Hold'     => 'badge bg-secondary',
        'Resolved'    => 'badge bg-success',
        'Closed'      => 'badge bg-dark'
    ];
    return $badges[$status] ?? 'badge bg-secondary';
}

function getPriorityBadge($priority) {
    switch ($priority) {
        case PRIORITY_LOW:    return 'badge bg-success';
        case PRIORITY_MEDIUM: return 'badge bg-warning text-dark';
        case PRIORITY_HIGH:   return 'badge bg-danger';
        default:              return 'badge bg-secondary';
    }
}
?>