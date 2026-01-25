<?php
// ============================================
// MANAGE CATEGORIES PAGE
// admin/manage_categories.php
// ============================================

require_once '../config/config.php';
require_once '../includes/functions.php';

// Only Super Admins can access this page
requireSuperAdmin();

$page_title = "Manage Categories";

$error = '';
$success = '';

// Handle Add Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $category_name = sanitizeInput($_POST['category_name']);
    $description = sanitizeInput($_POST['description']);
    
    if (empty($category_name)) {
        $error = 'Category name is required';
    } else {
        // Check if category already exists
        $stmt = $conn->prepare("SELECT category_id FROM categories WHERE category_name = ?");
        $stmt->bind_param("s", $category_name);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Category already exists';
        } else {
            $stmt = $conn->prepare("INSERT INTO categories (category_name, description, status) VALUES (?, ?, 'active')");
            $stmt->bind_param("ss", $category_name, $description);
            
            if ($stmt->execute()) {
                $success = 'Category added successfully!';
            } else {
                $error = 'Failed to add category';
            }
        }
    }
}

// Handle Edit Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    $category_id = (int)$_POST['category_id'];
    $category_name = sanitizeInput($_POST['category_name']);
    $description = sanitizeInput($_POST['description']);
    
    if (empty($category_name)) {
        $error = 'Category name is required';
    } else {
        // Check if name already exists (excluding current category)
        $stmt = $conn->prepare("SELECT category_id FROM categories WHERE category_name = ? AND category_id != ?");
        $stmt->bind_param("si", $category_name, $category_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Category name already exists';
        } else {
            $stmt = $conn->prepare("UPDATE categories SET category_name = ?, description = ? WHERE category_id = ?");
            $stmt->bind_param("ssi", $category_name, $description, $category_id);
            
            if ($stmt->execute()) {
                $success = 'Category updated successfully!';
            } else {
                $error = 'Failed to update category';
            }
        }
    }
}

// Handle Toggle Status
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $category_id = (int)$_GET['id'];
    
    // Get current status
    $stmt = $conn->prepare("SELECT status FROM categories WHERE category_id = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $current_status = $stmt->get_result()->fetch_assoc()['status'];
    
    // Toggle status
    $new_status = ($current_status == 'active') ? 'inactive' : 'active';
    
    $stmt = $conn->prepare("UPDATE categories SET status = ? WHERE category_id = ?");
    $stmt->bind_param("si", $new_status, $category_id);
    
    if ($stmt->execute()) {
        $success = 'Category status updated successfully!';
    } else {
        $error = 'Failed to update status';
    }
}

// Handle Delete Category
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $category_id = (int)$_GET['id'];
    
    // Check if category has complaints
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM complaints WHERE category_id = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['count'];
    
    if ($count > 0) {
        $error = "Cannot delete category. It has $count complaint(s) associated with it.";
    } else {
        $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
        $stmt->bind_param("i", $category_id);
        
        if ($stmt->execute()) {
            $success = 'Category deleted successfully!';
        } else {
            $error = 'Failed to delete category';
        }
    }
}

// Get all categories
$categories = $conn->query("
    SELECT c.*, COUNT(comp.complaint_id) as complaint_count 
    FROM categories c
    LEFT JOIN complaints comp ON c.category_id = comp.category_id
    GROUP BY c.category_id
    ORDER BY c.category_name ASC
");

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

<div class="row">
    <!-- Add Category Form -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-plus-circle"></i> Add New Category
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="category_name" class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="category_name" name="category_name" 
                               placeholder="e.g., IT Support" required>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" 
                                  placeholder="Brief description of this category"></textarea>
                    </div>

                    <button type="submit" name="add_category" class="btn btn-primary w-100">
                        <i class="bi bi-plus-circle"></i> Add Category
                    </button>
                </form>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="card mt-3">
            <div class="card-header bg-light">
                <i class="bi bi-bar-chart"></i> Category Statistics
            </div>
            <div class="card-body">
                <?php
                $total_categories = $conn->query("SELECT COUNT(*) as total FROM categories")->fetch_assoc()['total'];
                $active_categories = $conn->query("SELECT COUNT(*) as total FROM categories WHERE status = 'active'")->fetch_assoc()['total'];
                ?>
                <div class="d-flex justify-content-between mb-2">
                    <span>Total Categories:</span>
                    <strong><?php echo $total_categories; ?></strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Active Categories:</span>
                    <strong class="text-success"><?php echo $active_categories; ?></strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Categories List -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-tags"></i> All Categories (<?php echo $categories->num_rows; ?>)</span>
                    <a href="index.php" class="btn btn-sm btn-light">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if ($categories->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Category Name</th>
                                    <th>Description</th>
                                    <th>Complaints</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($category = $categories->fetch_assoc()): ?>
                                <tr>
                                    <td><strong>#<?php echo $category['category_id']; ?></strong></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($category['category_name']); ?></strong>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php 
                                            $desc = $category['description'];
                                            echo $desc ? (strlen($desc) > 50 ? substr(htmlspecialchars($desc), 0, 50) . '...' : htmlspecialchars($desc)) : 'No description';
                                            ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo $category['complaint_count']; ?> complaint<?php echo $category['complaint_count'] != 1 ? 's' : ''; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($category['status'] == 'active'): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
    <!-- Edit Button -->
    <button type="button" class="btn btn-outline-primary" 
            data-bs-toggle="modal" 
            data-bs-target="#editModal<?php echo $category['category_id']; ?>">
        <i class="bi bi-pencil"></i>
    </button>
    
    <!-- Toggle Status -->
    <a href="?toggle_status=1&id=<?php echo $category['category_id']; ?>" 
       class="btn btn-outline-warning"
       onclick="return confirm('Toggle category status?');">
        <i class="bi bi-toggle-<?php echo $category['status'] == 'active' ? 'on' : 'off'; ?>"></i>
    </a>
    
    <!-- Delete Button -->
    <a href="?delete=1&id=<?php echo $category['category_id']; ?>" 
       class="btn btn-outline-danger"
       onclick="return confirm('Are you sure? This cannot be undone!');">
        <i class="bi bi-trash"></i>
    </a>
</div>
                                    </td>
                                </tr>

                                <!-- Edit Modal -->
                                <div class="modal fade" id="editModal<?php echo $category['category_id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST" action="">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Category</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="category_id" value="<?php echo $category['category_id']; ?>">
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Category Name <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" name="category_name" 
                                                               value="<?php echo htmlspecialchars($category['category_name']); ?>" required>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Description</label>
                                                        <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($category['description']); ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="edit_category" class="btn btn-primary">
                                                        <i class="bi bi-save"></i> Save Changes
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-tags" style="font-size: 4rem; color: #ddd;"></i>
                        <h5 class="mt-3 text-muted">No categories yet</h5>
                        <p class="text-muted">Add your first category using the form on the left</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</div> <!-- End page-content -->
</div> <!-- End main-content -->

<?php include '../includes/footer.php'; ?>