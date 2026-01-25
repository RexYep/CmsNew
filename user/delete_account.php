<?php
// ============================================
// DELETE ACCOUNT PAGE
// user/delete_account.php
// ============================================

require_once '../config/config.php';
require_once '../includes/functions.php';

requireLogin();

if (isAdmin()) {
    header("Location: ../admin/index.php");
    exit();
}

$page_title = "Delete Account";
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user data
$user = getUserById($user_id);

// Get user's complaint statistics
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM complaints WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$complaint_count = $stmt->get_result()->fetch_assoc()['total'];

// Handle account deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    $password = $_POST['password'];
    $confirmation = $_POST['confirmation'];
    
    // Verify confirmation text
    if ($confirmation !== 'DELETE') {
        $error = 'Please type "DELETE" exactly to confirm account deletion.';
    } 
    // Verify password
    else {
        $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $db_password = $stmt->get_result()->fetch_assoc()['password'];
        
        if (!password_verify($password, $db_password)) {
            $error = 'Incorrect password. Account deletion cancelled.';
        } else {
            // Delete account permanently
            $result = deleteUserAccount($user_id);
            
            if ($result['success']) {
                // Send confirmation email
                sendAccountDeletionEmail($user['email'], $user['full_name']);
                
                // Logout and redirect
                session_unset();
                session_destroy();
                
                header("Location: ../auth/login.php?deleted=1");
                exit();
            } else {
                $error = $result['message'];
            }
        }
    }
}

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
                    <div class="card-header bg-danger text-white">
                <i class="bi bi-trash"></i> Delete My Account Permanently
            </div>
        <!-- Warning Alert -->
        <div class="alert alert-danger" role="alert">

            <p>You are about to permanently delete your account. This action <strong>CANNOT be undone</strong>.</p>
        </div>

        <!-- Account Deletion Card -->
        <div class="card border-danger">

            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- What Will Happen Section -->
                <div class="alert alert-warning">
                    <h5><i class="bi bi-exclamation-circle"></i> What Will Happen:</h5>
                    <ul class="mb-0">
                        <li><strong>All your personal information</strong> will be permanently deleted</li>
                        <li><strong><?php echo $complaint_count; ?> complaint(s)</strong> you submitted will be permanently deleted</li>
                        <li><strong>All your comments</strong> will be permanently removed</li>
                        <li><strong>All your attachments</strong> will be permanently deleted</li>
                        <li><strong>Your profile picture</strong> will be permanently removed</li>
                        <li><strong>You cannot recover your account</strong> after deletion</li>
                    </ul>
                </div>

                <!-- User Information Summary -->
                <div class="card mb-4 bg-light">
                    <div class="card-body">
                        <h6 class="card-title">Account to be deleted:</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($user['full_name']); ?></p>
                                <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Total Complaints:</strong> <?php echo $complaint_count; ?></p>
                                <p class="mb-1"><strong>Member Since:</strong> <?php echo formatDate($user['created_at']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Confirmation Form -->
                <form method="POST" action="" id="deleteForm">
                    <div class="mb-4">
                        <label for="password" class="form-label">
                            <strong>Enter Your Password to Confirm <span class="text-danger">*</span></strong>
                        </label>
                        <input type="password" 
                               class="form-control form-control-lg" 
                               id="password" 
                               name="password" 
                               placeholder="Enter your current password"
                               required>
                        <small class="text-muted">We need to verify your identity</small>
                    </div>

                    <div class="mb-4">
                        <label for="confirmation" class="form-label">
                            <strong>Type "DELETE" to confirm <span class="text-danger">*</span></strong>
                        </label>
                        <input type="text" 
                               class="form-control form-control-lg" 
                               id="confirmation" 
                               name="confirmation" 
                               placeholder="Type DELETE in capital letters"
                               required>
                        <small class="text-muted">Type exactly: <code>DELETE</code></small>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" 
                               type="checkbox" 
                               id="understand" 
                               required>
                        <label class="form-check-label" for="understand">
                            <strong>I understand that this action is permanent and cannot be undone</strong>
                        </label>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="profile.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Cancel
                        </a>
                        <button type="submit" 
                                name="confirm_delete" 
                                class="btn btn-danger btn-lg"
                                id="deleteButton"
                                disabled>
                            <i class="bi bi-trash"></i> Delete My Account Permanently
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Additional Warning -->
        <div class="alert alert-info mt-3">
            <i class="bi bi-info-circle"></i> 
            <strong>Need a break instead?</strong> 
            You can contact our admin to temporarily deactivate your account without losing your data.
        </div>
    </div>
</div>

</div> <!-- End page-content -->
</div> <!-- End main-content -->

<script>
// Enable delete button only when all conditions are met
document.getElementById('understand').addEventListener('change', function() {
    document.getElementById('deleteButton').disabled = !this.checked;
});

// Confirm before submit
document.getElementById('deleteForm').addEventListener('submit', function(e) {
    const confirmed = confirm(
        'FINAL WARNING \n\n' +
        'Are you absolutely sure you want to delete your account?\n\n' +
        'This will permanently delete:\n' +
        '• Your personal information\n' +
        '• All your complaints (<?php echo $complaint_count; ?> total)\n' +
        '• All your comments and attachments\n\n' +
        'This action CANNOT be undone!\n\n' +
        'Click OK to proceed with deletion, or Cancel to keep your account.'
    );
    
    if (!confirmed) {
        e.preventDefault();
    }
});

// Validate DELETE text in real-time
document.getElementById('confirmation').addEventListener('input', function() {
    if (this.value === 'DELETE') {
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
    } else {
        this.classList.remove('is-valid');
        if (this.value.length > 0) {
            this.classList.add('is-invalid');
        }
    }
});
</script>

<?php include '../includes/footer.php'; ?>