<?php
// ============================================
// GET ONLINE USERS (AJAX Endpoint)
// includes/get_online_users.php
// ============================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

requireLogin();

header('Content-Type: application/json');

$current_user_id = $_SESSION['user_id'];
$is_admin = isAdmin();

// Get online users based on role
if ($is_admin) {
    // Admins see online users (regular users)
    $stmt = $conn->prepare("
        SELECT user_id, full_name, email, role, last_activity, profile_picture,
               TIMESTAMPDIFF(MINUTE, last_activity, NOW()) as minutes_ago
        FROM users 
        WHERE role = 'user' 
          AND is_online = 1 
          AND status = 'active'
          AND approval_status = 'approved'
        ORDER BY last_activity DESC
        LIMIT 20
    ");
} else {
    // Users see online admins
    $stmt = $conn->prepare("
        SELECT user_id, full_name, email, role, admin_level, last_activity, profile_picture,
               TIMESTAMPDIFF(MINUTE, last_activity, NOW()) as minutes_ago
        FROM users 
        WHERE role = 'admin' 
          AND is_online = 1 
          AND status = 'active'
        ORDER BY last_activity DESC
    ");
}

$stmt->execute();
$result = $stmt->get_result();

$online_users = [];
while ($row = $result->fetch_assoc()) {
    $online_users[] = [
        'user_id' => $row['user_id'],
        'full_name' => $row['full_name'],
        'email' => $row['email'],
        'role' => $row['role'],
        'admin_level' => $row['admin_level'] ?? null,
        'profile_picture' => $row['profile_picture'],
        'minutes_ago' => (int)$row['minutes_ago'],
        'last_activity' => $row['last_activity']
    ];
}

// Get total online count
$count_query = $is_admin 
    ? "SELECT COUNT(*) as count FROM users WHERE role = 'user' AND is_online = 1 AND status = 'active' AND approval_status = 'approved'"
    : "SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND is_online = 1 AND status = 'active'";
    
$total_online = $conn->query($count_query)->fetch_assoc()['count'];

echo json_encode([
    'success' => true,
    'online_users' => $online_users,
    'total_online' => (int)$total_online,
    'timestamp' => time()
]);