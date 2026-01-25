<?php
// ============================================
// GLOBAL UPDATE CHECKER - USER
// user/global_check.php
// ============================================

require_once '../config/config.php';
require_once '../includes/functions.php';

requireLogin();

if (isAdmin()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];

// Get unread notification count
$notification_count = getUnreadNotificationCount($user_id);

// Get complaint status counts
$stmt = $conn->prepare("
    SELECT 
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) as resolved,
        SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed
    FROM complaints
    WHERE user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$counts = $stmt->get_result()->fetch_assoc();

echo json_encode([
    'success' => true,
    'notification_count' => (int)$notification_count,
    'complaint_counts' => [
        'pending' => (int)$counts['pending'],
        'in_progress' => (int)$counts['in_progress'],
        'resolved' => (int)$counts['resolved'],
        'closed' => (int)$counts['closed']
    ],
    'timestamp' => time()
]);