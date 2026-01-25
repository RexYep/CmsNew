<?php
// ============================================
// GENERAL APPLICATION CONFIGURATION
// config/config.php
// ============================================

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Application Settings
define('SITE_NAME', 'Complaint Management System');
define('SITE_URL', 'http://localhost/complaint-management-system/');
define('ADMIN_EMAIL', 'cmsproperty278@gmail.com');

// Path Settings
define('BASE_PATH', dirname(__DIR__) . '/');
define('INCLUDES_PATH', BASE_PATH . 'includes/');
define('ASSETS_PATH', BASE_PATH . 'assets/');

// User Roles
define('ROLE_USER', 'user');
define('ROLE_ADMIN', 'admin');

// Complaint Status
define('STATUS_PENDING', 'Pending');
define('STATUS_IN_PROGRESS', 'In Progress');
define('STATUS_RESOLVED', 'Resolved');
define('STATUS_CLOSED', 'Closed');

// Complaint Priority
define('PRIORITY_LOW', 'Low');
define('PRIORITY_MEDIUM', 'Medium');
define('PRIORITY_HIGH', 'High');

// Pagination Settings
define('RECORDS_PER_PAGE', 10);

// Complaint Submission Limits
define('DAILY_COMPLAINT_LIMIT', 5);

// Login Security Settings
define('MAX_LOGIN_ATTEMPTS', 5);        // Maximum failed attempts before lockout
define('LOCKOUT_DURATION', 15);         // Lockout duration in minutes
define('ATTEMPT_RESET_TIME', 30);       // Reset failed attempts after X minutes of no attempts

// Include database connection
require_once 'database.php';

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

// Function to check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === ROLE_ADMIN;
}

// Function to check if user is regular user
function isUser() {
    return isset($_SESSION['role']) && $_SESSION['role'] === ROLE_USER;
}

// Function to redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: " . SITE_URL . "auth/login.php");
        exit();
    }
}

// Function to redirect if not admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: " . SITE_URL . "user/index.php");
        exit();
    }
}

// Function to sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to format date
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

// Function to format datetime
function formatDateTime($datetime) {
    return date('M d, Y h:i A', strtotime($datetime));
}

// Function to calculate days elapsed
function daysElapsed($date) {
    $start = new DateTime($date);
    $end = new DateTime();
    $interval = $start->diff($end);
    return $interval->days;
}

// Function to get status badge class
function getStatusBadge($status) {
    switch($status) {
        case STATUS_PENDING:
            return 'badge bg-warning text-dark';
        case STATUS_IN_PROGRESS:
            return 'badge bg-info text-white';
        case STATUS_RESOLVED:
            return 'badge bg-success';
        case STATUS_CLOSED:
            return 'badge bg-secondary';
        default:
            return 'badge bg-secondary';
    }
}

// Function to get priority badge class
function getPriorityBadge($priority) {
    switch($priority) {
        case PRIORITY_LOW:
            return 'badge bg-success';
        case PRIORITY_MEDIUM:
            return 'badge bg-warning text-dark';
        case PRIORITY_HIGH:
            return 'badge bg-danger';
        default:
            return 'badge bg-secondary';
    }
}

?>