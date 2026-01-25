<?php
// ============================================
// UPDATE USER ACTIVITY (AJAX Endpoint)
// includes/update_activity.php
// ============================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

// Only process if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit();
}

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];

// Update last activity and set online status
$stmt = $conn->prepare("UPDATE users SET last_activity = NOW(), is_online = 1 WHERE user_id = ?");
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    // Also mark users as offline if no activity for 5 minutes
    $conn->query("UPDATE users SET is_online = 0 WHERE last_activity < DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
    
    echo json_encode(['success' => true, 'timestamp' => time()]);
} else {
    echo json_encode(['success' => false, 'error' => 'Update failed']);
}