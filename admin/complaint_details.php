<?php
// ============================================
// ADMIN COMPLAINT DETAILS PAGE
// admin/complaint_details.php
// ============================================

require_once '../config/config.php';
require_once '../includes/functions.php';

requireAdmin();

$page_title = "Complaint Details";

$admin_id = $_SESSION['user_id'];
$complaint_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$error = '';
$success = '';

// Fetch complaint details
$stmt = $conn->prepare("
    SELECT c.*, cat.category_name, u.full_name as user_name, u.email as user_email, u.phone as user_phone,
           admin.full_name as assigned_admin_name
    FROM complaints c
    LEFT JOIN categories cat ON c.category_id = cat.category_id
    LEFT JOIN users u ON c.user_id = u.user_id
    LEFT JOIN users admin ON c.assigned_to = admin.user_id
    WHERE c.complaint_id = ?
");
$stmt->bind_param("i", $complaint_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: manage_complaints.php");
    exit();
}

$complaint = $result->fetch_assoc();

// Check if regular admin has permission to view this complaint
if (!isSuperAdmin() && $complaint['assigned_to'] != $_SESSION['user_id']) {
    header("Location: manage_complaints.php");
    exit();
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $comment_text = sanitizeInput($_POST['comment']);
    
    $result = addComment($complaint_id, $admin_id, $comment_text);
    
    if ($result['success']) {
        // Notify user if admin posted
        createNotification(
            $complaint['user_id'], 
            "Admin Replied to Complaint #$complaint_id",
            "Admin responded: " . substr($comment_text, 0, 100) . "...",
            'info',
            $complaint_id
        );
        
        $success = $result['message'];
        header("Location: complaint_details.php?id=$complaint_id#comments");
        exit();
    } else {
        $error = $result['message'];
    }
}

// Get comments
$comments = getComplaintComments($complaint_id);
$comment_count = getCommentCount($complaint_id);

// Fetch attachments
$stmt_attachments = $conn->prepare("SELECT * FROM complaint_attachments WHERE complaint_id = ? ORDER BY uploaded_date ASC");
$stmt_attachments->bind_param("i", $complaint_id);
$stmt_attachments->execute();
$attachments = $stmt_attachments->get_result();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = sanitizeInput($_POST['status']);
    $admin_response = sanitizeInput($_POST['admin_response']);
    $old_status = $complaint['status'];
    
    // Update complaint
    $stmt = $conn->prepare("UPDATE complaints SET status = ?, admin_response = ?, updated_date = NOW() WHERE complaint_id = ?");
    $stmt->bind_param("ssi", $new_status, $admin_response, $complaint_id);
    
    if ($stmt->execute()) {
        // If status is resolved, set resolved_date
        if ($new_status === 'Resolved' || $new_status === 'Closed') {
            $stmt = $conn->prepare("UPDATE complaints SET resolved_date = NOW() WHERE complaint_id = ?");
            $stmt->bind_param("i", $complaint_id);
            $stmt->execute();
        }
        
        // Log to history
        $comment = "Status updated by admin" . (!empty($admin_response) ? " with response" : "");
        $stmt = $conn->prepare("INSERT INTO complaint_history (complaint_id, changed_by, old_status, new_status, comment) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisss", $complaint_id, $admin_id, $old_status, $new_status, $comment);
        $stmt->execute();
        
        // Create notification for user
        $notif_title = "Complaint #$complaint_id Status Updated";
        $notif_message = "Your complaint status has been changed from '$old_status' to '$new_status'.";
        if (!empty($admin_response)) {
            $notif_message .= " Admin response: " . substr($admin_response, 0, 100) . "...";
        }
        $notif_type = ($new_status == 'Resolved' || $new_status == 'Closed') ? 'success' : 'info';
        createNotification($complaint['user_id'], $notif_title, $notif_message, $notif_type, $complaint_id);
        
        $success = "Complaint updated successfully!";
        
        // Refresh complaint data
        $stmt = $conn->prepare("
            SELECT c.*, cat.category_name, u.full_name as user_name, u.email as user_email, u.phone as user_phone,
                   admin.full_name as assigned_admin_name
            FROM complaints c
            LEFT JOIN categories cat ON c.category_id = cat.category_id
            LEFT JOIN users u ON c.user_id = u.user_id
            LEFT JOIN users admin ON c.assigned_to = admin.user_id
            WHERE c.complaint_id = ?
        ");
        $stmt->bind_param("i", $complaint_id);
        $stmt->execute();
        $complaint = $stmt->get_result()->fetch_assoc();
        
    } else {
        $error = "Failed to update complaint. Please try again.";
    }
}

// Handle assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_complaint'])) {
    $assigned_admin = (int)$_POST['assigned_to'];
    
    // Check if the target is a regular admin (not super admin)
    $stmt = $conn->prepare("SELECT role, admin_level FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $assigned_admin);
    $stmt->execute();
    $target_admin = $stmt->get_result()->fetch_assoc();
    
    // Only allow assignment to regular admins (not super admins)
    if (!isSuperAdmin() && $target_admin['admin_level'] == 'super_admin') {
        $error = 'You cannot assign complaints to Super Admins. Please assign to a Regular Admin.';
    } else {
        $stmt = $conn->prepare("UPDATE complaints SET assigned_to = ? WHERE complaint_id = ?");
        $stmt->bind_param("ii", $assigned_admin, $complaint_id);
        
        if ($stmt->execute()) {
            // Log to history
            $stmt = $conn->prepare("INSERT INTO complaint_history (complaint_id, changed_by, new_status, comment) VALUES (?, ?, ?, 'Complaint assigned to admin')");
            $status = $complaint['status'];
            $stmt->bind_param("iis", $complaint_id, $admin_id, $status);
            $stmt->execute();
            
            $success = "Complaint assigned successfully!";
            
            // Refresh data
            header("Location: complaint_details.php?id=" . $complaint_id);
            exit();
        } else {
            $error = "Failed to assign complaint.";
        }
    }
}

// Fetch complaint history
$stmt = $conn->prepare("
    SELECT h.*, u.full_name 
    FROM complaint_history h
    JOIN users u ON h.changed_by = u.user_id
    WHERE h.complaint_id = ?
    ORDER BY h.changed_date DESC
");
$stmt->bind_param("i", $complaint_id);
$stmt->execute();
$history = $stmt->get_result();

// Get all admins for assignment dropdown
// If Super Admin: show all admins
// If Regular Admin: show only regular admins (not super admins)
if (isSuperAdmin()) {
    $admins = $conn->query("SELECT user_id, full_name, admin_level FROM users WHERE role = 'admin' AND status = 'active' ORDER BY full_name ASC");
} else {
    $admins = $conn->query("SELECT user_id, full_name, admin_level FROM users WHERE role = 'admin' AND admin_level = 'admin' AND status = 'active' ORDER BY full_name ASC");
}

include '../includes/header.php';
include '../includes/navbar.php';
?>

<?php if (!isSuperAdmin() && empty($complaint['assigned_to'])): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i> <strong>Note:</strong> 
        This complaint is not assigned to anyone yet. Only Super Admin can assign it.
    </div>
<?php endif; ?>

<div class="row mb-3">
    <div class="col-12">
        <a href="manage_complaints.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to All Complaints
        </a>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Main Complaint Details -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-file-text"></i> Complaint #<?php echo $complaint['complaint_id']; ?></span>
                    <span class="<?php echo getStatusBadge($complaint['status']); ?>">
                        <?php echo $complaint['status']; ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <h4 class="mb-3"><?php echo htmlspecialchars($complaint['subject']); ?></h4>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Category:</strong><br>
                        <span class="badge bg-light text-dark">
                            <?php echo htmlspecialchars($complaint['category_name']); ?>
                        </span>
                    </div>
                    <div class="col-md-4">
                        <strong>Priority:</strong><br>
                        <span class="<?php echo getPriorityBadge($complaint['priority']); ?>">
                            <?php echo $complaint['priority']; ?>
                        </span>
                    </div>
                    <div class="col-md-4">
                        <strong>Days Pending:</strong><br>
                        <?php 
                        $days = daysElapsed($complaint['submitted_date']);
                        $color = $days > 7 ? 'text-danger' : ($days > 3 ? 'text-warning' : 'text-success');
                        ?>
                        <span class="<?php echo $color; ?>">
                            <strong><?php echo $days; ?> day<?php echo $days != 1 ? 's' : ''; ?></strong>
                        </span>
                    </div>
                </div>

                <div class="mb-4">
                    <strong>Description:</strong>
                   <p class="mt-2 complaint-description" style="white-space: pre-wrap; padding: 15px; border-radius: 5px;">
    <?php echo htmlspecialchars($complaint['description']); ?>
</p>
                </div>

                <?php if ($attachments->num_rows > 0): ?>
                <div class="mb-4">
                    <strong><i class="bi bi-paperclip"></i> Attachments (<?php echo $attachments->num_rows; ?>):</strong>
                    <div class="mt-3">
                        <div class="row">
                            <?php while ($file = $attachments->fetch_assoc()): 
                                $file_url = SITE_URL . $file['file_path'];
                                $is_image = in_array(strtolower(pathinfo($file['file_name'], PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif']);
                            ?>
                                <div class="col-md-6 mb-3">
                                    <div class="p-3 attachment-box" style="border-radius: 5px; border-left: 3px solid #667eea;">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="bi bi-file-earmark-text fs-4 me-2 text-primary"></i>
                                            <div class="flex-grow-1">
                                                <strong><?php echo htmlspecialchars($file['file_name']); ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo number_format($file['file_size'] / 1024, 2); ?> KB
                                                </small>
                                            </div>
                                        </div>
                                        
                                        <?php if ($is_image): ?>
                                            <div class="mb-2">
                                                <img src="<?php echo $file_url; ?>" 
                                                     alt="<?php echo htmlspecialchars($file['file_name']); ?>" 
                                                     class="img-fluid" 
                                                     style="max-width: 100%; max-height: 200px; border-radius: 5px; cursor: pointer;"
                                                     onclick="window.open('<?php echo $file_url; ?>', '_blank')">
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="d-flex gap-2">
                                            <a href="<?php echo $file_url; ?>" target="_blank" class="btn btn-sm btn-outline-primary flex-grow-1">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                            <a href="<?php echo $file_url; ?>" download class="btn btn-sm btn-outline-success flex-grow-1">
                                                <i class="bi bi-download"></i> Download
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="mb-3">
                    <strong>User Information:</strong><br>
                    <div class="mt-2">
                        <i class="bi bi-person"></i> <?php echo htmlspecialchars($complaint['user_name']); ?><br>
                        <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($complaint['user_email']); ?><br>
                        <?php if (!empty($complaint['user_phone'])): ?>
                        <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($complaint['user_phone']); ?>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($complaint['admin_response'])): ?>
                <div class="alert alert-info">
                    <strong><i class="bi bi-chat-left-text"></i> Current Admin Response:</strong>
                    <p class="mb-0 mt-2" style="white-space: pre-wrap;"><?php echo htmlspecialchars($complaint['admin_response']); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Update Status Form -->
        <div class="card mt-3">
            <div class="card-header">
                <i class="bi bi-pencil-square"></i> Update Complaint Status
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="Pending" <?php echo $complaint['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="In Progress" <?php echo $complaint['status'] == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="Resolved" <?php echo $complaint['status'] == 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                                <option value="Closed" <?php echo $complaint['status'] == 'Closed' ? 'selected' : ''; ?>>Closed</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="admin_response" class="form-label">Admin Response</label>
                        <textarea class="form-control" id="admin_response" name="admin_response" rows="5" 
                                  placeholder="Enter your response to the user..."><?php echo htmlspecialchars($complaint['admin_response'] ?? ''); ?></textarea>
                        <small class="text-muted">This message will be visible to the user</small>
                    </div>

                    <button type="submit" name="update_status" class="btn btn-primary">
                        <i class="bi bi-save"></i> Update Complaint
                    </button>
                </form>
            </div>
        </div>

        <!-- History -->
        <div class="card mt-3">
            <div class="card-header">
                <i class="bi bi-clock-history"></i> Complaint History
            </div>
            <div class="card-body">
                <?php if ($history->num_rows > 0): ?>
                    <div class="timeline">
                        <?php while ($h = $history->fetch_assoc()): ?>
                        <div class="timeline-item mb-3 pb-3" style="border-left: 2px solid #e0e0e0; padding-left: 20px; position: relative;">
                            <div style="position: absolute; left: -8px; top: 0; width: 14px; height: 14px; background: #667eea; border-radius: 50%;"></div>
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong><?php echo htmlspecialchars($h['full_name']); ?></strong>
                                    <?php if ($h['old_status'] && $h['new_status']): ?>
                                        changed status from 
                                        <span class="badge bg-secondary"><?php echo $h['old_status']; ?></span> to 
                                        <span class="<?php echo getStatusBadge($h['new_status']); ?>"><?php echo $h['new_status']; ?></span>
                                    <?php elseif ($h['new_status']): ?>
                                        set status to 
                                        <span class="<?php echo getStatusBadge($h['new_status']); ?>"><?php echo $h['new_status']; ?></span>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted"><?php echo formatDateTime($h['changed_date']); ?></small>
                            </div>
                            <?php if (!empty($h['comment'])): ?>
                                <div class="mt-2 text-muted">
                                    <i class="bi bi-chat-quote"></i> <?php echo htmlspecialchars($h['comment']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">No history available</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Comments Section -->
        <div class="card mt-3" id="comments">
            <div class="card-header bg-warning">
                <i class="bi bi-chat-dots-fill"></i> Comments & Discussion (<?php echo $comment_count; ?>)
            </div>
            <div class="card-body">
                <!-- Existing Comments -->
                <?php if ($comments->num_rows > 0): ?>
                    <div class="mb-4">
                        <?php while ($comment = $comments->fetch_assoc()): ?>
                            <div class="comment-item mb-3 p-3" style="background: <?php echo $comment['role'] == 'admin' ? '#fff3cd' : '#e3f2fd'; ?>; border-radius: 8px; border-left: 4px solid <?php echo $comment['role'] == 'admin' ? '#ffc107' : '#667eea'; ?>;">
                                <div class="d-flex align-items-start">
                                    <div class="user-avatar me-2" style="width: 40px; height: 40px; font-size: 1rem; background: <?php echo $comment['role'] == 'admin' ? 'linear-gradient(135deg, #ffc107 0%, #ff9800 100%)' : 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'; ?>;">
                                        <?php echo strtoupper(substr($comment['full_name'], 0, 1)); ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <div>
                                                <strong><?php echo htmlspecialchars($comment['full_name']); ?></strong>
                                                <?php if ($comment['role'] == 'admin'): ?>
                                                    <span class="badge bg-warning text-dark ms-1">
                                                        <i class="bi bi-shield-check"></i> Admin
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-info ms-1">
                                                        <i class="bi bi-person"></i> User
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($comment['user_id'] == $_SESSION['user_id']): ?>
                                                    <span class="badge bg-primary ms-1">You</span>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted">
                                                <i class="bi bi-clock"></i> <?php echo formatDateTime($comment['created_at']); ?>
                                            </small>
                                        </div>
                                        <p class="mb-0" style="white-space: pre-wrap;"><?php echo htmlspecialchars($comment['comment']); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-3 mb-4">
                        <i class="bi bi-chat-left-text" style="font-size: 3rem; color: #ddd;"></i>
                        <p class="text-muted mb-0">No comments yet. Start the conversation!</p>
                    </div>
                <?php endif; ?>

                <!-- Add Comment Form -->
                <div class="add-comment-section">
                    <h6 class="mb-3"><i class="bi bi-plus-circle"></i> Add Admin Comment</h6>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <textarea class="form-control" name="comment" rows="3" 
                                      placeholder="Reply to user or add internal notes..." required></textarea>
                            <small class="text-muted">
                                <i class="bi bi-info-circle"></i> User will be notified when you post a comment.
                            </small>
                        </div>
                        <button type="submit" name="add_comment" class="btn btn-warning">
                            <i class="bi bi-send"></i> Post Comment
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Complaint Info -->
        <div class="card">
            <div class="card-header bg-light">
                <i class="bi bi-info-circle"></i> Complaint Information
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Tracking ID:</strong>
                    <div class="mt-1">
                        <span class="badge bg-dark" style="font-size: 1rem;">
                            #<?php echo $complaint['complaint_id']; ?>
                        </span>
                    </div>
                </div>

                <div class="mb-3">
                    <strong>Submitted:</strong>
                    <div class="text-muted"><?php echo formatDateTime($complaint['submitted_date']); ?></div>
                </div>

                <div class="mb-3">
                    <strong>Last Updated:</strong>
                    <div class="text-muted"><?php echo formatDateTime($complaint['updated_date']); ?></div>
                </div>

                <?php if (!empty($complaint['resolved_date'])): ?>
                <div class="mb-3">
                    <strong>Resolved:</strong>
                    <div class="text-muted"><?php echo formatDateTime($complaint['resolved_date']); ?></div>
                </div>
                <?php endif; ?>

                <?php if (!empty($complaint['assigned_admin_name'])): ?>
                <div class="mb-3">
                    <strong>Assigned To:</strong>
                    <div class="text-muted"><?php echo htmlspecialchars($complaint['assigned_admin_name']); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Assign Complaint -->
        <?php if (isSuperAdmin()): ?>
        <div class="card mt-3">
            <div class="card-header bg-light">
                <i class="bi bi-person-check"></i> Assign Complaint
            </div>
            <div class="card-body">
                <?php if ($admins->num_rows > 0): ?>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="assigned_to" class="form-label">Assign to Admin</label>
                            <select class="form-select" id="assigned_to" name="assigned_to" required>
                                <option value="">-- Select Admin --</option>
                                <?php while ($admin = $admins->fetch_assoc()): ?>
                                    <option value="<?php echo $admin['user_id']; ?>" 
                                        <?php echo $complaint['assigned_to'] == $admin['user_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($admin['full_name']); ?>
                                        <?php if (isSuperAdmin() && $admin['admin_level'] == 'super_admin'): ?>
                                            (Super Admin)
                                        <?php endif; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <?php if (!isSuperAdmin()): ?>
                                <small class="text-muted">
                                    <i class="bi bi-info-circle"></i> You can only assign to Regular Admins
                                </small>
                            <?php endif; ?>
                        </div>
                        <button type="submit" name="assign_complaint" class="btn btn-info btn-sm w-100">
                            <i class="bi bi-person-plus"></i> Assign
                        </button>
                    </form>
                <?php else: ?>
                    <p class="text-muted mb-0">
                        <?php if (isSuperAdmin()): ?>
                            No admins available for assignment.
                        <?php else: ?>
                            No Regular Admins available. Only Super Admins can assign to Super Admins.
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="card mt-3">
            <div class="card-header bg-light">
                <i class="bi bi-person-check"></i> Assignment
            </div>
            <div class="card-body">
                <p class="text-muted mb-0">
                    <i class="bi bi-info-circle"></i> This complaint is assigned to you. Only Super Admin can reassign complaints.
                </p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="card mt-3">
            <div class="card-header bg-light">
                <i class="bi bi-lightning"></i> Quick Actions
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-sm btn-outline-success" onclick="window.print();">
                        <i class="bi bi-printer"></i> Print Details
                    </button>
                    <a href="mailto:<?php echo $complaint['user_email']; ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-envelope"></i> Email User
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

</div> <!-- End page-content -->
</div> <!-- End main-content -->
<!-- Auto-Refresh Script -->
<script>
let lastCommentCount = <?php echo $comment_count; ?>;
let isChecking = false;
let checkInterval;

// Create status indicator
function createStatusIndicator() {
    const indicator = document.createElement('div');
    indicator.id = 'autoRefreshIndicator';
    indicator.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: rgba(102, 126, 234, 0.95);
        color: white;
        padding: 12px 20px;
        border-radius: 25px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        display: none;
        align-items: center;
        gap: 10px;
        z-index: 1000;
        font-size: 14px;
    `;
    indicator.innerHTML = '<i class="bi bi-arrow-repeat" style="animation: spin 1s linear infinite;"></i> Checking for updates...';
    document.body.appendChild(indicator);
    
    const style = document.createElement('style');
    style.textContent = `
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(style);
}

function showIndicator(show = true) {
    const indicator = document.getElementById('autoRefreshIndicator');
    if (indicator) {
        indicator.style.display = show ? 'flex' : 'none';
    }
}

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        background: ${type === 'info' ? '#17a2b8' : '#28a745'};
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        z-index: 10000;
        min-width: 300px;
    `;
    toast.innerHTML = `
        <div style="display: flex; align-items: center; gap: 10px;">
            <i class="bi bi-info-circle-fill" style="font-size: 1.5rem;"></i>
            <div>
                <strong>ℹ Update</strong>
                <div style="font-size: 14px; margin-top: 5px;">${message}</div>
            </div>
        </div>
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => toast.remove(), 5000);
}

async function checkForUpdates() {
    if (isChecking) return;
    
    isChecking = true;
    showIndicator(true);
    
    try {
        const response = await fetch('check_complaint_updates.php?id=<?php echo $complaint_id; ?>');
        const data = await response.json();
        
        if (data.success) {
            // Check new comments from user
            if (data.comment_count > lastCommentCount) {
                const newCommentsCount = data.comment_count - lastCommentCount;
                showToast(`User added ${newCommentsCount} new comment(s)!`, 'info');
                lastCommentCount = data.comment_count;
                
                // Reload to show new comments
                setTimeout(() => window.location.reload(), 2000);
            }
        }
    } catch (error) {
        console.error('Error checking updates:', error);
    } finally {
        isChecking = false;
        showIndicator(false);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    createStatusIndicator();
    checkInterval = setInterval(checkForUpdates, 30000);
    
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            checkForUpdates();
        }
    });
});

window.addEventListener('beforeunload', function() {
    clearInterval(checkInterval);
});
</script>

<?php include '../includes/footer.php'; ?>