<?php
// ============================================
// MANAGE USERS PAGE
// admin/manage_users.php
// ============================================

require_once '../config/config.php';
require_once '../includes/functions.php';

requireAdmin();

$page_title = "Manage Users";

$error = '';
$success = '';

// Handle user status toggle
if (isset($_GET['toggle_status']) && isset($_GET['user_id'])) {
    $user_id = (int)$_GET['user_id'];
    
    // Get user info
    $stmt = $conn->prepare("SELECT role, status FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $target_user = $stmt->get_result()->fetch_assoc();
    
    // Permission check: Regular admin cannot toggle admin status
    if (!isSuperAdmin() && $target_user['role'] == 'admin') {
        $error = 'You do not have permission to change admin user status. Only Super Admins can do this.';
    } else {
        // Toggle status
        $new_status = ($target_user['status'] == 'active') ? 'inactive' : 'active';
        
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE user_id = ?");
        $stmt->bind_param("si", $new_status, $user_id);
        
        if ($stmt->execute()) {
            $success = "User status updated successfully!";
        } else {
            $error = "Failed to update user status.";
        }
    }
}

// Handle user deletion
if (isset($_GET['delete_user']) && isset($_GET['user_id'])) {
    $user_id = (int)$_GET['user_id'];
    
    // Only Super Admin can delete users
    if (!isSuperAdmin()) {
        $error = 'Only Super Admins can delete users. Regular Admins cannot delete accounts.';
    } else if ($user_id == $_SESSION['user_id']) {
        $error = "You cannot delete your own account!";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $success = "User deleted successfully!";
        } else {
            $error = "Failed to delete user.";
        }
    }
}

// Filter parameters
$role_filter = isset($_GET['role']) ? sanitizeInput($_GET['role']) : '';
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$search_query = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Build query - ONLY show approved users
$where_conditions = ["approval_status = 'approved'"];
$params = [];
$types = "";

if (!empty($role_filter)) {
    $where_conditions[] = "role = ?";
    $params[] = $role_filter;
    $types .= "s";
}

if (!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($search_query)) {
    $where_conditions[] = "(full_name LIKE ? OR email LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions); 

// Fetch users
$query = "SELECT * FROM users $where_clause ORDER BY created_at DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $users = $stmt->get_result();
} else {
    $users = $conn->query($query);
}

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

<!-- Permission Info -->
<?php if (!isSuperAdmin()): ?>
    <div class="alert alert-warning">
        <i class="bi bi-shield-exclamation"></i> <strong>Regular Admin:</strong> 
        You can manage complaints and view users, but only Super Admins can delete user accounts or change admin status.
    </div>
<?php endif; ?>

<!-- Filter Section -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role">
                            <option value="">All Roles</option>
                            <option value="user" <?php echo $role_filter == 'user' ? 'selected' : ''; ?>>User</option>
                            <option value="admin" <?php echo $role_filter == 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Status</option>
                            <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               placeholder="Name or email..." 
                               value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>
                    
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Filter
                        </button>
                    </div>
                    
                    <?php if (!empty($role_filter) || !empty($status_filter) || !empty($search_query)): ?>
                    <div class="col-12">
                        <a href="manage_users.php" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-x-circle"></i> Clear Filters
                        </a>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>


<!-- Users Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-people"></i> All Users (<?php echo $users->num_rows; ?>)</span>
                    <a href="index.php" class="btn btn-sm btn-light">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if ($users->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                   <?php if ($role_filter == 'user' || empty($role_filter)): ?>
    <th>Today's Submissions</th>
<?php endif; ?>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($user = $users->fetch_assoc()): ?>
                                <?php 
                                // Check if current admin can manage this user
                                $can_toggle_status = true;
                                $can_delete = isSuperAdmin(); // Only super admin can delete
                                
                                if (!isSuperAdmin() && $user['role'] == 'admin') {
                                    $can_toggle_status = false;
                                }
                                ?>
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
                                   <?php if ($role_filter == 'user' || empty($role_filter)): ?>
    <td>
        <?php if ($user['role'] == 'user'): ?>
            <?php 
            $today_count = getTodayComplaintsCount($user['user_id']);
            ?>
            <span class="badge <?php echo $today_count >= DAILY_COMPLAINT_LIMIT ? 'bg-danger' : 'bg-success'; ?>">
                <?php echo $today_count; ?> / <?php echo DAILY_COMPLAINT_LIMIT; ?>
            </span>
        <?php else: ?>
            <span class="badge bg-secondary">N/A</span>
        <?php endif; ?>
    </td>
<?php endif; ?>
                                    <td>
                                        <?php if ($user['role'] == 'admin'): ?>
                                            <?php if ($user['admin_level'] == 'super_admin'): ?>
                                                <span class="badge bg-danger">
                                                    <i class="bi bi-shield-fill-check"></i> Super Admin
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">
                                                    <i class="bi bi-shield-check"></i> Admin
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-info">
                                                <i class="bi bi-person"></i> User
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($user['status'] == 'active'): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?php echo formatDate($user['created_at']); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($user['user_id'] == $_SESSION['user_id']): ?>
                                            <span class="badge bg-primary">You</span>
                                        <?php elseif (!$can_toggle_status && !$can_delete): ?>
                                            <span class="badge bg-secondary" data-bs-toggle="tooltip" title="Only Super Admin can manage admin users">
                                                <i class="bi bi-lock"></i> Protected
                                            </span>
                                        <?php else: ?>
                                        <div class="btn-group btn-group-sm" role="group">
    <?php if ($can_toggle_status): ?>
    <a href="?toggle_status=1&user_id=<?php echo $user['user_id']; ?>" 
       class="btn btn-outline-warning"
       onclick="return confirm('Are you sure you want to <?php echo $user['status'] == 'active' ? 'deactivate' : 'activate'; ?> this user?');">
        <i class="bi bi-toggle-<?php echo $user['status'] == 'active' ? 'off' : 'on'; ?>"></i>
    </a>
    <?php endif; ?>
    
    <?php if ($can_delete): ?>
    <a href="?delete_user=1&user_id=<?php echo $user['user_id']; ?>" 
       class="btn btn-outline-danger"
       onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone!');">
        <i class="bi bi-trash"></i>
    </a>
    <?php else: ?>
    <button class="btn btn-outline-secondary" disabled 
            data-bs-toggle="tooltip" title="Only Super Admin can delete users">
        <i class="bi bi-trash"></i>
    </button>
    <?php endif; ?>
</div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-people" style="font-size: 4rem; color: #ddd;"></i>
                        <h5 class="mt-3 text-muted">No users found</h5>
                        <?php if (!empty($role_filter) || !empty($status_filter) || !empty($search_query)): ?>
                            <p class="text-muted">Try adjusting your filters</p>
                            <a href="manage_users.php" class="btn btn-outline-primary">Clear Filters</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- User Statistics -->
<div class="row mt-4">
    <div class="col-md-4">
        <div class="card stats-card" style="border-left-color: #667eea;">
            <div class="card-body">
                <h6 class="text-muted">Total Users</h6>
          <h3><?php echo $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'user' AND approval_status = 'approved'")->fetch_assoc()['total']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stats-card" style="border-left-color: #dc3545;">
            <div class="card-body">
                <h6 class="text-muted">Total Admins</h6>
                <h3><?php echo $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'admin' AND approval_status = 'approved'")->fetch_assoc()['total']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stats-card" style="border-left-color: #28a745;">
            <div class="card-body">
                <h6 class="text-muted">Active Users</h6>
                <h3><?php echo $conn->query("SELECT COUNT(*) as total FROM users WHERE status = 'active' AND approval_status = 'approved'")->fetch_assoc()['total']; ?></h3>
            </div>
        </div>
    </div>
</div>

</div> <!-- End page-content -->
</div> <!-- End main-content -->

<?php include '../includes/footer.php'; ?>