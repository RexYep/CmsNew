<?php
// ============================================
// COMPLAINT DETAILS PAGE
// user/complaint_details.php
// ============================================

require_once '../config/config.php';
require_once '../includes/functions.php';

requireLogin();

if (isAdmin()) {
    header("Location: ../admin/index.php");
    exit();
}

$page_title = "Complaint Details";

$user_id = $_SESSION['user_id'];
$complaint_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch complaint details
$stmt = $conn->prepare("
    SELECT c.*, cat.category_name, u.full_name as admin_name
    FROM complaints c
    LEFT JOIN categories cat ON c.category_id = cat.category_id
    LEFT JOIN users u ON c.assigned_to = u.user_id
    WHERE c.complaint_id = ? AND c.user_id = ?
");
$stmt->bind_param("ii", $complaint_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: my_complaints.php");
    exit();
}

$complaint = $result->fetch_assoc();

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $comment_text = sanitizeInput($_POST['comment']);
    
    $result = addComment($complaint_id, $user_id, $comment_text);
    
    if ($result['success']) {
        // Notify admin if user posted
        $admins = $conn->query("SELECT user_id FROM users WHERE role = 'admin' AND status = 'active'");
        while ($admin = $admins->fetch_assoc()) {
            createNotification(
                $admin['user_id'], 
                "New Comment on Complaint #$complaint_id",
                $_SESSION['full_name'] . " added a comment: " . substr($comment_text, 0, 100) . "...",
                'info',
                $complaint_id
            );
        }
        
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

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="row mb-3">
    <div class="col-12">
        <a href="my_complaints.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to My Complaints
        </a>
    </div>
</div>

<div class="row">
    <!-- Complaint Details -->
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
                    <div class="col-md-6">
                        <strong>Category:</strong><br>
                        <span class="badge bg-light text-dark">
                            <?php echo htmlspecialchars($complaint['category_name']); ?>
                        </span>
                    </div>
                    <div class="col-md-6">
                        <strong>Priority:</strong><br>
                        <span class="<?php echo getPriorityBadge($complaint['priority']); ?>">
                            <?php echo $complaint['priority']; ?>
                        </span>
                    </div>
                </div>

                <div class="mb-3">
                    <strong>Description:</strong>
                   <p class="mt-2 complaint-description" style="white-space: pre-wrap; padding: 15px; border-radius: 5px;">
    <?php echo htmlspecialchars($complaint['description']); ?>
</p>
                </div>

                <?php if ($attachments->num_rows > 0): ?>
                <div class="mb-3">
                    <strong><i class="bi bi-paperclip"></i> Attachments:</strong>
                    <div class="mt-2">
                        <?php while ($file = $attachments->fetch_assoc()): 
                            $file_url = SITE_URL . $file['file_path'];
                            $is_image = in_array(strtolower(pathinfo($file['file_name'], PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif']);
                        ?>
                            <div class="mb-3 p-3" style="background: #f8f9fa; border-radius: 5px;">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-file-earmark me-2"></i>
                                    <div class="flex-grow-1">
                                        <strong><?php echo htmlspecialchars($file['file_name']); ?></strong><br>
                                        <small class="text-muted">
                                            <?php echo number_format($file['file_size'] / 1024, 2); ?> KB - 
                                            Uploaded: <?php echo formatDateTime($file['uploaded_date']); ?>
                                        </small>
                                    </div>
                                    <a href="<?php echo $file_url; ?>" download class="btn btn-sm btn-outline-primary me-2">
                                        <i class="bi bi-download"></i> Download
                                    </a>
                                    <a href="<?php echo $file_url; ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                </div>
                                
                                <?php if ($is_image): ?>
                                    <div class="mt-2">
                                        <img src="<?php echo $file_url; ?>" 
                                             alt="<?php echo htmlspecialchars($file['file_name']); ?>" 
                                             class="img-fluid" 
                                             style="max-width: 100%; max-height: 400px; border-radius: 5px; cursor: pointer;"
                                             onclick="window.open('<?php echo $file_url; ?>', '_blank')">
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($complaint['admin_response'])): ?>
                <div class="alert alert-info">
                    <strong><i class="bi bi-chat-left-text"></i> Admin Response:</strong>
                    <p class="mb-0 mt-2" style="white-space: pre-wrap;"><?php echo htmlspecialchars($complaint['admin_response']); ?></p>
                </div>
                <?php endif; ?>

                <?php if ($complaint['status'] === 'Resolved' || $complaint['status'] === 'Closed'): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> 
                    This complaint has been <?php echo strtolower($complaint['status']); ?>.
                    <?php if (!empty($complaint['resolved_date'])): ?>
                        <br><small>Resolved on: <?php echo formatDateTime($complaint['resolved_date']); ?></small>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Complaint History -->
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
            <div class="card-header bg-primary text-white">
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
                                                    <span class="badge bg-warning text-dark ms-1">Admin</span>
                                                <?php else: ?>
                                                    <span class="badge bg-info ms-1">User</span>
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
                    <h6 class="mb-3"><i class="bi bi-plus-circle"></i> Add a Comment</h6>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <textarea class="form-control" name="comment" rows="3" 
                                      placeholder="Type your comment or question here..." required></textarea>
                            <small class="text-muted">
                                <i class="bi bi-info-circle"></i> You can ask questions or provide additional information about your complaint.
                            </small>
                        </div>
                        <button type="submit" name="add_comment" class="btn btn-primary">
                            <i class="bi bi-send"></i> Post Comment
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar Info -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
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
                    <strong>Submitted Date:</strong>
                    <div class="text-muted"><?php echo formatDateTime($complaint['submitted_date']); ?></div>
                </div>

                <div class="mb-3">
                    <strong>Last Updated:</strong>
                    <div class="text-muted"><?php echo formatDateTime($complaint['updated_date']); ?></div>
                </div>

                <div class="mb-3">
                    <strong>Days Pending:</strong>
                    <div class="text-muted">
                        <?php 
                        $days = daysElapsed($complaint['submitted_date']);
                        echo $days . ' day' . ($days != 1 ? 's' : '');
                        ?>
                    </div>
                </div>

                <?php if (!empty($complaint['admin_name'])): ?>
                <div class="mb-3">
                    <strong>Assigned To:</strong>
                    <div class="text-muted"><?php echo htmlspecialchars($complaint['admin_name']); ?></div>
                </div>
                <?php endif; ?>

                <hr>

                <div class="mb-3">
                    <strong>Status Legend:</strong>
                    <div class="mt-2">
                        <div class="mb-1"><span class="badge bg-warning text-dark">Pending</span> - Awaiting review</div>
                        <div class="mb-1"><span class="badge bg-info text-white">In Progress</span> - Being processed</div>
                        <div class="mb-1"><span class="badge bg-success">Resolved</span> - Issue resolved</div>
                        <div class="mb-1"><span class="badge bg-secondary">Closed</span> - Complaint closed</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Support Card -->
        <div class="card mt-3">
            <div class="card-header bg-light">
                <i class="bi bi-headset"></i> Need Help?
            </div>
            <div class="card-body">
                <p class="mb-2"><small>If you need to follow up on this complaint:</small></p>
                <p class="mb-0">
                    <i class="bi bi-envelope"></i> <a href="mailto:<?php echo ADMIN_EMAIL; ?>"><?php echo ADMIN_EMAIL; ?></a>
                </p>
            </div>
        </div>
    </div>
</div>

</div> <!-- End Main Content -->

<!-- Auto-Refresh Script -->
<script>
let lastStatus = '<?php echo $complaint['status']; ?>';
let lastResponse = <?php echo json_encode($complaint['admin_response']); ?>;
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
        animation: slideIn 0.3s ease;
    `;
    indicator.innerHTML = '<i class="bi bi-arrow-repeat" style="animation: spin 1s linear infinite;"></i> Checking for updates...';
    document.body.appendChild(indicator);
    
    // Add spinner animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        @keyframes slideIn {
            from { transform: translateY(100px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateY(0); opacity: 1; }
            to { transform: translateY(100px); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
}

// Show/hide indicator
function showIndicator(show = true) {
    const indicator = document.getElementById('autoRefreshIndicator');
    if (indicator) {
        indicator.style.display = show ? 'flex' : 'none';
    }
}

// Show toast notification
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        background: ${type === 'success' ? '#28a745' : type === 'info' ? '#17a2b8' : '#ffc107'};
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        z-index: 10000;
        min-width: 300px;
        animation: slideIn 0.3s ease;
    `;
    toast.innerHTML = `
        <div style="display: flex; align-items: center; gap: 10px;">
            <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'info' ? 'info-circle' : 'exclamation-triangle'}-fill" style="font-size: 1.5rem;"></i>
            <div>
                <strong>${type === 'success' ? '✓ Updated!' : type === 'info' ? 'ℹ Info' : '⚠ Notice'}</strong>
                <div style="font-size: 14px; margin-top: 5px;">${message}</div>
            </div>
        </div>
    `;
    document.body.appendChild(toast);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

// Check for updates
async function checkForUpdates() {
    if (isChecking) return;
    
    isChecking = true;
    showIndicator(true);
    
    try {
        const response = await fetch('check_complaint_updates.php?id=<?php echo $complaint_id; ?>');
        const data = await response.json();
        
        if (data.success) {
            let hasChanges = false;
            
            // Check status change
            if (data.status !== lastStatus) {
                updateStatus(data.status, lastStatus);
                lastStatus = data.status;
                hasChanges = true;
            }
            
            // Check admin response change
            if (data.admin_response !== lastResponse) {
                updateAdminResponse(data.admin_response);
                lastResponse = data.admin_response;
                if (!hasChanges) hasChanges = true;
            }
            
            // Check new comments
            if (data.comment_count > lastCommentCount) {
                const newCommentsCount = data.comment_count - lastCommentCount;
                showToast(`${newCommentsCount} new comment(s) added!`, 'info');
                lastCommentCount = data.comment_count;
                
                // Reload page to show new comments
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            }
        }
    } catch (error) {
        console.error('Error checking updates:', error);
    } finally {
        isChecking = false;
        showIndicator(false);
    }
}

// Update status badge
function updateStatus(newStatus, oldStatus) {
    const statusBadge = document.querySelector('.card-header span[class*="badge"]');
    if (statusBadge) {
        // Remove old classes
        statusBadge.className = '';
        
        // Add new class
        const badgeClass = {
            'Pending': 'badge bg-warning text-dark',
            'In Progress': 'badge bg-info text-white',
            'Resolved': 'badge bg-success',
            'Closed': 'badge bg-secondary'
        };
        statusBadge.className = badgeClass[newStatus] || 'badge bg-secondary';
        statusBadge.textContent = newStatus;
        
        // Show notification
        showToast(`Status updated: ${oldStatus} → ${newStatus}`, 'success');
        
        // Add pulse animation
        statusBadge.style.animation = 'pulse 0.5s ease';
        setTimeout(() => statusBadge.style.animation = '', 500);
    }
}

// Update admin response
function updateAdminResponse(newResponse) {
    const responseAlert = document.querySelector('.alert-info');
    
    if (newResponse && newResponse.trim()) {
        if (responseAlert) {
            // Update existing response
            const responseParagraph = responseAlert.querySelector('p');
            if (responseParagraph) {
                responseParagraph.textContent = newResponse;
            }
        } else {
            // Create new response box
            const descriptionDiv = document.querySelector('.mb-3:has(.complaint-description)');
            if (descriptionDiv) {
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-info';
                alertDiv.innerHTML = `
                    <strong><i class="bi bi-chat-left-text"></i> Admin Response:</strong>
                    <p class="mb-0 mt-2" style="white-space: pre-wrap;">${newResponse}</p>
                `;
                descriptionDiv.insertAdjacentElement('afterend', alertDiv);
            }
        }
        
        showToast('Admin added a response to your complaint!', 'info');
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    createStatusIndicator();
    
    // Start checking every 30 seconds
    checkInterval = setInterval(checkForUpdates, 30000);
    
    // Also check when page becomes visible (user switches back to tab)
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            checkForUpdates();
        }
    });
});

// Stop checking when leaving page
window.addEventListener('beforeunload', function() {
    clearInterval(checkInterval);
});
</script>

<?php include '../includes/footer.php'; ?>