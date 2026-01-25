<?php
// ============================================
// GLOBAL UPDATE CHECKER - ADMIN
// admin/global_check.php
// ============================================

require_once '../config/config.php';
require_once '../includes/functions.php';

requireAdmin();

header('Content-Type: application/json');

$admin_id = $_SESSION['user_id'];

// Get unread notification count
$notification_count = getUnreadNotificationCount($admin_id);

// Get complaint status counts (all complaints or assigned to this admin)
if (isSuperAdmin()) {
    // Super admin sees all
    $result = $conn->query("
        SELECT 
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) as resolved,
            SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed
        FROM complaints
    ");
} else {
    // Regular admin sees only assigned complaints
    $stmt = $conn->prepare("
        SELECT 
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) as resolved,
            SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed
        FROM complaints
        WHERE assigned_to = ?
    ");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
}

$counts = $result->fetch_assoc();

// Get pending user approvals count (Super Admin only)
$pending_users = 0;
if (isSuperAdmin()) {
    $pending_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE approval_status = 'pending' AND role = 'user'")->fetch_assoc()['count'];
}

echo json_encode([
    'success' => true,
    'notification_count' => (int)$notification_count,
    'complaint_counts' => [
        'pending' => (int)$counts['pending'],
        'in_progress' => (int)$counts['in_progress'],
        'resolved' => (int)$counts['resolved'],
        'closed' => (int)$counts['closed']
    ],
    'pending_users' => (int)$pending_users,
    'timestamp' => time()
]);