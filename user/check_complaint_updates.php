<?php
// ============================================
// CHECK COMPLAINT UPDATES (AJAX Endpoint)
// user/check_complaint_updates.php
// ============================================

require_once '../config/config.php';
require_once '../includes/functions.php';

requireLogin();

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'No complaint ID provided']);
    exit();
}

$complaint_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Fetch current complaint data
$stmt = $conn->prepare("
    SELECT status, admin_response, updated_date 
    FROM complaints 
    WHERE complaint_id = ? AND user_id = ?
");
$stmt->bind_param("ii", $complaint_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Complaint not found']);
    exit();
}

$complaint = $result->fetch_assoc();

// Get latest comment count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM complaint_comments WHERE complaint_id = ?");
$stmt->bind_param("i", $complaint_id);
$stmt->execute();
$comment_count = $stmt->get_result()->fetch_assoc()['count'];

// Get latest comment (if any)
$stmt = $conn->prepare("
    SELECT cc.*, u.full_name, u.role 
    FROM complaint_comments cc
    JOIN users u ON cc.user_id = u.user_id
    WHERE cc.complaint_id = ?
    ORDER BY cc.created_at DESC
    LIMIT 1
");
$stmt->bind_param("i", $complaint_id);
$stmt->execute();
$latest_comment = $stmt->get_result()->fetch_assoc();

echo json_encode([
    'success' => true,
    'status' => $complaint['status'],
    'admin_response' => $complaint['admin_response'],
    'updated_date' => $complaint['updated_date'],
    'comment_count' => (int)$comment_count,
    'latest_comment' => $latest_comment
]);