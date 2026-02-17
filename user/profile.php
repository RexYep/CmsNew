<?php
// ============================================
// USER PROFILE PAGE
// user/profile.php
// ============================================

require_once '../config/config.php';
require_once '../includes/functions.php';

requireLogin();

if (isAdmin()) {
    header("Location: ../admin/index.php");
    exit();
}

$page_title = "My Profile";

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user data
$user = getUserById($user_id);

// Handle profile picture upload with CLOUDINARY
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_picture'])) {
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!in_array($_FILES['profile_picture']['type'], $allowed_types)) {
            $error = 'Only JPG, PNG, and GIF images are allowed';
        } 
        // Validate file size (max 2MB for avatars)
        elseif ($_FILES['profile_picture']['size'] > 2 * 1024 * 1024) {
            $error = 'Avatar size must not exceed 2MB';
        } 
        else {
            // Delete old avatar from Cloudinary if exists
            if (!empty($user['avatar_public_id'])) {
                deleteFromCloudinary($user['avatar_public_id'], 'image');
            }
            
            // Upload new avatar to Cloudinary
            $upload_result = uploadToCloudinary($_FILES['profile_picture'], 'avatars');
            
            if ($upload_result['success']) {
                $avatar_url = $upload_result['url'];
                $avatar_public_id = $upload_result['public_id'];
                
                // Update database
                $stmt = $conn->prepare("UPDATE users SET profile_picture = ?, avatar_url = ?, avatar_public_id = ? WHERE user_id = ?");
                // Store cloudinary URL in profile_picture for backward compatibility
                $stmt->bind_param("sssi", $avatar_url, $avatar_url, $avatar_public_id, $user_id);
                
                if ($stmt->execute()) {
                    $success = 'Profile picture updated successfully!';
                    $_SESSION['avatar_url'] = $avatar_url; // Update session
                    $user = getUserById($user_id); // Refresh user data
                } else {
                    $error = 'Database error: ' . $stmt->error;
                }
            } else {
                $error = 'Upload failed: ' . $upload_result['error'];
            }
        }
    } else {
        $error = 'Please select a file to upload';
    }
}

// Handle profile picture delete
if (isset($_GET['delete_picture'])) {
    // Delete from Cloudinary if exists
    if (!empty($user['avatar_public_id'])) {
        $delete_result = deleteFromCloudinary($user['avatar_public_id'], 'image');
    }
    
    // Update database
    $stmt = $conn->prepare("UPDATE users SET profile_picture = NULL, avatar_url = NULL, avatar_public_id = NULL WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        $success = 'Profile picture deleted successfully';
        unset($_SESSION['avatar_url']);
        $user = getUserById($user_id);
    } else {
        $error = 'Failed to delete profile picture';
    }
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    
    $full_name = sanitizeInput($_POST['full_name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    
    $result = updateUserProfile($user_id, $full_name, $email, $phone);
    
    if ($result['success']) {
        $success = $result['message'];
        $user = getUserById($user_id); // Refresh user data
    } else {
        $error = $result['message'];
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password !== $confirm_password) {
        $error = 'New passwords do not match';
    } else {
        $result = changePassword($user_id, $current_password, $new_password);
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="row">
    <!-- Profile Information -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-person-circle"></i> Profile Information
            </div>
            <div class="card-body">
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

                <form method="POST" action="">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Account Type</label>
                            <input type="text" class="form-control" value="<?php echo ucfirst($user['role']); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Account Status</label>
                            <input type="text" class="form-control" value="<?php echo ucfirst($user['status']); ?>" readonly>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Member Since</label>
                            <input type="text" class="form-control" 
                                   value="<?php echo formatDateTime($user['created_at']); ?>" readonly>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="bi bi-save"></i> Update Profile
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Change Password -->
        <div class="card mt-3">
            <div class="card-header">
                <i class="bi bi-key"></i> Change Password
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="new_password" name="new_password" minlength="8" required>
                        </div>
                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8" required>
                        </div>
                    </div>

                    <button type="submit" name="change_password" class="btn btn-warning">
                        <i class="bi bi-shield-lock"></i> Change Password
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Profile Card -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="position-relative d-inline-block mb-3">
                <?php
                // Display avatar - prefer Cloudinary URL, fallback to profile_picture
                if (!empty($user['avatar_url'])) {
                    // Use Cloudinary with optimization
                    $avatar_display = getOptimizedImageUrl($user['avatar_url'], 150, 150);
                    $has_avatar = true;
                } elseif (!empty($user['profile_picture']) && file_exists('../' . $user['profile_picture'])) {
                    // Fallback to local file
                    $avatar_display = SITE_URL . $user['profile_picture'];
                    $has_avatar = true;
                } else {
                    $has_avatar = false;
                }
                ?>
                
                <?php if ($has_avatar): ?>
                    <img src="<?php echo htmlspecialchars($avatar_display); ?>" 
                         class="rounded-circle" 
                         width="150" 
                         height="150" 
                         style="object-fit: cover; border: 4px solid #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.1);"
                         alt="Profile Picture">
                <?php else: ?>
                    <div class="user-avatar mx-auto" style="width: 150px; height: 150px; font-size: 4rem; border: 4px solid #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
            
            <!-- Upload Button -->
            <button type="button" class="btn btn-sm btn-primary rounded-circle position-absolute" 
                    style="bottom: 5px; right: 5px; width: 35px; height: 35px; padding: 0; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.2);"
                    data-bs-toggle="modal" 
                    data-bs-target="#uploadPictureModal">
                <i class="bi bi-camera"></i>
            </button>
                </div>
                <h5 class="mb-1"><?php echo htmlspecialchars($user['full_name']); ?></h5>
                <p class="text-muted mb-3"><?php echo htmlspecialchars($user['email']); ?></p>
                
                <div class="d-grid gap-2">
                    <span class="badge bg-primary p-2">
                        <i class="bi bi-person"></i> <?php echo ucfirst($user['role']); ?> Account
                    </span>
                    <span class="badge bg-success p-2">
                        <i class="bi bi-check-circle"></i> <?php echo ucfirst($user['status']); ?>
                    </span>
                </div>

                <hr>

                <div class="text-start">
                    <small class="text-muted d-block mb-1">
                        <i class="bi bi-calendar-check"></i> Joined: <?php echo formatDate($user['created_at']); ?>
                    </small>
                    <?php if (!empty($user['phone'])): ?>
                    <small class="text-muted d-block">
                        <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($user['phone']); ?>
                    </small>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="card mt-3">
            <div class="card-header bg-light">
                <i class="bi bi-graph-up"></i> My Statistics
            </div>
            <div class="card-body">
                <?php
                // Get user's complaint statistics
                $stmt = $conn->prepare("SELECT COUNT(*) as total FROM complaints WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $total = $stmt->get_result()->fetch_assoc()['total'];

                $stmt = $conn->prepare("SELECT COUNT(*) as total FROM complaints WHERE user_id = ? AND status = 'Pending'");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $pending = $stmt->get_result()->fetch_assoc()['total'];

                $stmt = $conn->prepare("SELECT COUNT(*) as total FROM complaints WHERE user_id = ? AND status = 'Resolved'");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $resolved = $stmt->get_result()->fetch_assoc()['total'];
                ?>
                
                <div class="d-flex justify-content-between mb-2">
                    <span>Total Complaints:</span>
                    <strong><?php echo $total; ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Pending:</span>
                    <strong class="text-warning"><?php echo $pending; ?></strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Resolved:</span>
                    <strong class="text-success"><?php echo $resolved; ?></strong>
                </div>
            </div>
        </div>

<!-- Danger Zone -->
        <div class="card mt-3 border-danger">
            <div class="card-body">
                <h6 class="text-danger">Delete Account</h6>
                <p class="mb-3">
                    Once you delete your account, there is no going back. Please be certain.
                </p>
                <a href="delete_account.php" class="btn btn-outline-danger">
                    <i class="bi bi-trash"></i> Delete My Account
                </a>
            </div>
        </div>
    </div>
</div>

</div> <!-- End page-content -->

</div> <!-- End page-content -->
</div> <!-- End main-content -->

<!-- Upload Picture Modal -->
<div class="modal fade" id="uploadPictureModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-camera"></i> Update Profile Picture
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <!-- Current Picture Preview -->
                    <div class="text-center mb-3">
                        <div id="imagePreview" class="mb-3">
                            <?php if ($has_avatar): ?>
                                <img src="<?php echo htmlspecialchars($avatar_display); ?>" 
                                     class="rounded-circle" 
                                     width="150" 
                                     height="150" 
                                     style="object-fit: cover;"
                                     id="currentImage">
                            <?php else: ?>
                                <div class="user-avatar mx-auto" style="width: 150px; height: 150px; font-size: 4rem;">
                                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- File Input -->
                    <div class="mb-3">
                        <label class="form-label">Choose Picture</label>
                        <input type="file" 
                               class="form-control" 
                               name="profile_picture" 
                               accept="image/jpeg,image/png,image/jpg,image/gif"
                               id="profilePictureInput"
                               required>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> JPG, PNG, GIF - Max 2MB - Will be uploaded to Cloudinary
                        </small>
                    </div>

                    <!-- Preview -->
                    <div id="previewContainer" style="display: none;">
                        <label class="form-label">Preview:</label>
                        <div class="text-center">
                            <img id="preview" class="rounded-circle" width="150" height="150" style="object-fit: cover;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <?php if ($has_avatar): ?>
                        <a href="?delete_picture=1" 
                           class="btn btn-danger me-auto"
                           onclick="return confirm('Delete profile picture from Cloudinary?');">
                            <i class="bi bi-trash"></i> Delete
                        </a>
                    <?php endif; ?>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="upload_picture" class="btn btn-primary">
                        <i class="bi bi-upload"></i> Upload to Cloud
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Preview image before upload
document.getElementById('profilePictureInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview').src = e.target.result;
            document.getElementById('previewContainer').style.display = 'block';
        }
        reader.readAsDataURL(file);
    }
});
</script>

<?php include '../includes/footer.php'; ?>