<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

requireSuperAdmin();

$page_title = "Pending User Approvals";

$success = '';
$error = '';

// Handle approval/rejection
if (isset($_GET['action']) && isset($_GET['user_id'])) {
    $user_id = (int)$_GET['user_id'];
    $action = $_GET['action'];
    
    // Get user details
    $stmt = $conn->prepare("SELECT full_name, email FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE users SET approval_status = 'approved' WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        
      if ($stmt->execute()) {
    // Send approval email
    $email_sent = sendApprovalEmail($user['email'], $user['full_name'], 'approved');
    
    // Create notification for user
    createNotification($user_id, "Account Approved", "Your account has been approved. You can now login and use the system.", 'success');
    
    if ($email_sent) {
        $success = "User approved successfully! Email notification sent to " . htmlspecialchars($user['email']);
    } else {
        $success = "User approved successfully! However, email notification failed to send. The email address may be invalid.";
    }
} else {
            $error = "Failed to approve user.";
        }
    } elseif ($action === 'reject') {
    // Delete user completely instead of updating status
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        // Send rejection email
        $email_sent = sendApprovalEmail($user['email'], $user['full_name'], 'rejected');
        
        if ($email_sent) {
            $success = "User rejected. Email notification sent.";
        } else {
            $success = "User rejected. However, email notification failed to send.";
        }
    } else {
        $error = "Failed to reject user.";
    }
}
}

// Get pending users
$pending_users = $conn->query("
    SELECT user_id, full_name, email, phone, created_at, profile_picture 
    FROM users 
    WHERE approval_status = 'pending' AND role = 'user'
    ORDER BY created_at DESC
");

// Get counts
$pending_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE approval_status = 'pending' AND role = 'user'")->fetch_assoc()['count'];
$approved_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE approval_status = 'approved' AND role = 'user'")->fetch_assoc()['count'];
$rejected_count = 0;

include '../includes/header.php';
include '../includes/navbar.php';
?>

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

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card stats-card" style="border-left-color: #ffc107;">
            <div class="card-body">
                <h6 class="text-muted">Pending Approval</h6>
                <h2><?php echo $pending_count; ?></h2>
                <small class="text-muted">Awaiting review</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stats-card" style="border-left-color: #28a745;">
            <div class="card-body">
                <h6 class="text-muted">Approved</h6>
                <h2><?php echo $approved_count; ?></h2>
                <small class="text-muted">Active users</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stats-card" style="border-left-color: #dc3545;">
            <div class="card-body">
                <h6 class="text-muted">Rejected</h6>
                <h2><?php echo $rejected_count; ?></h2>
                <small class="text-muted">Declined</small>
            </div>
        </div>
    </div>
</div>

<!-- Pending Users Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-hourglass-split"></i> Pending User Approvals (<?php echo $pending_count; ?>)</span>
                    <a href="manage_users.php" class="btn btn-sm btn-light">
                        <i class="bi bi-people"></i> All Users
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if ($pending_users->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Registered</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($user = $pending_users->fetch_assoc()): ?>
                                <tr>
                                    <td><strong>#<?php echo $user['user_id']; ?></strong></td>
                                    <td>
                                         <div class="d-flex align-items-center">
        <?php if ($user['profile_picture'] && file_exists('../' . $user['profile_picture'])): ?>
            <img src="<?php echo SITE_URL . $user['profile_picture']; ?>" 
                 class="rounded-circle me-2" 
                 width="35" 
                 height="35" 
                 style="object-fit: cover;"
                 alt="<?php echo htmlspecialchars($user['full_name']); ?>">
        <?php else: ?>
            <div class="user-avatar me-2" style="width: 35px; height: 35px; font-size: 0.9rem;">
                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
            </div>
        <?php endif; ?>
        <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
    </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                    <td>
    <?php
    // Quick email validation check
    list($u, $d) = explode('@', $user['email']);
    $domain_valid = checkdnsrr($d, 'MX');
    ?>
    <?php if ($domain_valid): ?>
        <span class="badge bg-success"><i class="bi bi-check-circle"></i> Valid</span>
    <?php else: ?>
        <span class="badge bg-danger" title="Email domain may not exist">
            <i class="bi bi-exclamation-triangle"></i> Suspicious
        </span>
    <?php endif; ?>
</td>
                                    <td>
                                        <small><?php echo formatDateTime($user['created_at']); ?></small>
                                    </td>
                                    <td>
                                      <div class="btn-group btn-group-sm">
    <a href="?action=approve&user_id=<?php echo $user['user_id']; ?>" 
       class="btn btn-success"
       onclick="return confirm('Approve this user? An email notification will be sent.');">
        <i class="bi bi-check-circle"></i> Approve
    </a>
    <a href="?action=reject&user_id=<?php echo $user['user_id']; ?>" 
       class="btn btn-danger"
       onclick="return confirm('Reject this user? An email notification will be sent.');">
        <i class="bi bi-x-circle"></i> Reject
    </a>
</div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-check-circle" style="font-size: 4rem; color: #28a745;"></i>
                        <h5 class="mt-3 text-muted">No Pending Approvals</h5>
                        <p class="text-muted">All user registrations have been processed</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</div> <!-- End page-content -->
</div> <!-- End main-content -->

<?php include '../includes/footer.php'; ?>
