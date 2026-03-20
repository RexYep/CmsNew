<?php
// ============================================
// GET NEW LOGS — AJAX ENDPOINT
// admin/get_new_logs.php
// ============================================

require_once '../config/config.php';
require_once '../includes/functions.php';

requireAdmin();

if (!isSuperAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit();
}

header('Content-Type: application/json');

$last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

// Fetch logs newer than last_id
$stmt = $conn->prepare("
    SELECT al.*, u.full_name, u.email, u.role
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.user_id
    WHERE al.id > ?
    ORDER BY al.created_at DESC
    LIMIT 50
");
$stmt->bind_param("i", $last_id);
$stmt->execute();
$result = $stmt->get_result();

$logs = [];
while ($row = $result->fetch_assoc()) {
    // Format browser
    $ua = $row['user_agent'] ?? '';
    if (str_contains($ua, 'Edg/'))                                   $browser = 'Edge';
    elseif (str_contains($ua, 'OPR/'))                               $browser = 'Opera';
    elseif (str_contains($ua, 'Firefox'))                            $browser = 'Firefox';
    elseif (str_contains($ua, 'Safari') && !str_contains($ua, 'Chrome')) $browser = 'Safari';
    elseif (str_contains($ua, 'Chrome'))                             $browser = 'Chrome/Brave';
    else                                                             $browser = 'Unknown';

    $logs[] = [
        'id'          => $row['id'],
        'created_at'  => formatDateTime($row['created_at']),
        'full_name'   => $row['full_name'] ?? '',
        'email'       => $row['email'] ?? '',
        'action'      => $row['action'],
        'description' => $row['description'] ?? '',
        'ip_address'  => $row['ip_address'] ?? '',
        'browser'     => $browser,
        'user_agent'  => $row['user_agent'] ?? '',
    ];
}

// Get latest stats for today
$stats = [];
foreach (['login_success', 'login_failed', 'password_changed', 'logout'] as $action) {
    $r = $conn->query("
        SELECT COUNT(*) as c FROM activity_logs
        WHERE action = '$action' AND DATE(created_at) = CURDATE()
    ")->fetch_assoc();
    $stats[$action] = (int)$r['c'];
}

// Get latest log id
$latest = $conn->query("SELECT MAX(id) as max_id FROM activity_logs")->fetch_assoc();

echo json_encode([
    'logs'      => $logs,
    'stats'     => $stats,
    'latest_id' => (int)($latest['max_id'] ?? 0),
]);