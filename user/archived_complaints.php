<?php
// ============================================
// USER ARCHIVED COMPLAINTS PAGE
// user/archived_complaints.php
// ============================================

require_once '../config/config.php';
require_once '../includes/functions.php';

requireLogin();

if (isAdmin()) {
    header("Location: ../admin/index.php");
    exit();
}

$page_title = "Archived Complaints";
$user_id = $_SESSION['user_id'];

// Handle restore action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_complaint'])) {
    $complaint_id = (int)$_POST['complaint_id'];

    // Verify ownership
    $stmt = $conn->prepare("SELECT complaint_id FROM complaints WHERE complaint_id = ? AND user_id = ? AND is_archived = 1");
    $stmt->bind_param("ii", $complaint_id, $user_id);
    $stmt->execute();

    if ($stmt->get_result()->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE complaints SET is_archived = 0, archived_by = NULL, archived_at = NULL, archive_reason = NULL WHERE complaint_id = ?");
        $stmt->bind_param("i", $complaint_id);
        if ($stmt->execute()) {
            $success = "Complaint #$complaint_id has been restored successfully.";
        } else {
            $error = "Failed to restore complaint. Please try again.";
        }
    } else {
        $error = "Complaint not found or you don't have permission.";
    }
}

// Filter parameters
$status_filter  = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$search_query   = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Pagination
$page             = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = RECORDS_PER_PAGE;
$offset           = ($page - 1) * $records_per_page;

// Build query — only archived complaints for this user
$where_conditions = ["c.user_id = ?", "c.is_archived = 1"];
$params = [$user_id];
$types  = "i";

if (!empty($status_filter)) {
    $where_conditions[] = "c.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($search_query)) {
    $where_conditions[] = "(c.subject LIKE ? OR c.description LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$where_clause = implode(" AND ", $where_conditions);

// Count total
$count_query = "SELECT COUNT(*) as total FROM complaints c WHERE $where_clause";
$stmt = $conn->prepare($count_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$total_records = $stmt->get_result()->fetch_assoc()['total'];
$total_pages   = ceil($total_records / $records_per_page);

// Fetch archived complaints
$query = "
    SELECT c.*, cat.category_name, archiver.full_name as archived_by_name
    FROM complaints c
    LEFT JOIN categories cat ON c.category_id = cat.category_id
    LEFT JOIN users archiver ON c.archived_by = archiver.user_id
    WHERE $where_clause
    ORDER BY c.archived_at DESC
    LIMIT ? OFFSET ?
";
$params[] = $records_per_page;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$complaints = $stmt->get_result();

include '../includes/header.php';
include '../includes/navbar.php';
?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Filter Section -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-3">
                        <label for="status" class="form-label">Filter by Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Status</option>
                            <option value="Resolved"     <?php echo $status_filter == 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                            <option value="Closed"       <?php echo $status_filter == 'Closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search"
                               placeholder="Search by subject or description..."
                               value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Filter
                        </button>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <a href="archived_complaints.php" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-x-circle"></i> Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Archived Complaints Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>
                    <i class="bi bi-archive"></i> Archived Complaints
                    <span class="badge bg-secondary ms-1"><?php echo $total_records; ?></span>
                </span>
                <a href="my_complaints.php" class="btn btn-sm btn-light">
                    <i class="bi bi-arrow-left"></i> Back to My Complaints
                </a>
            </div>
            <div class="card-body">

                <?php if ($complaints->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Subject</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Submitted</th>
                                    <th>Archived On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($complaint = $complaints->fetch_assoc()): ?>
                             <tr class="archived-row">
                                    <td><strong>#<?php echo $complaint['complaint_id']; ?></strong></td>
                                    <td>
                                        <div style="max-width: 280px;">
                                            <strong><?php echo htmlspecialchars($complaint['subject']); ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo substr(htmlspecialchars($complaint['description']), 0, 70) . '...'; ?>
                                            </small>
                                            <?php if (!empty($complaint['archive_reason'])): ?>
                                                <br>
                                                <small class="text-muted fst-italic">
                                                    <i class="bi bi-archive"></i> <?php echo htmlspecialchars($complaint['archive_reason']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            <?php echo htmlspecialchars($complaint['category_name'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="<?php echo getStatusBadge($complaint['status']); ?>">
                                            <?php echo $complaint['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="<?php echo getPriorityBadge($complaint['priority']); ?>">
                                            <?php echo $complaint['priority']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?php echo formatDate($complaint['submitted_date']); ?></small>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <i class="bi bi-calendar-x"></i>
                                            <?php echo formatDate($complaint['archived_at']); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="complaint_details.php?id=<?php echo $complaint['complaint_id']; ?>"
                                               class="btn btn-sm btn-outline-primary" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-3">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_query); ?>">Previous</a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_query); ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_query); ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-archive" style="font-size: 4rem; color: #ddd;"></i>
                        <h5 class="mt-3 text-muted">No archived complaints</h5>
                        <?php if (!empty($status_filter) || !empty($search_query)): ?>
                            <p class="text-muted">Try adjusting your filters</p>
                            <a href="archived_complaints.php" class="btn btn-outline-secondary">Clear Filters</a>
                        <?php else: ?>
                            <p class="text-muted">You haven't archived any complaints yet.</p>
                            <a href="my_complaints.php" class="btn btn-outline-primary">
                                <i class="bi bi-arrow-left"></i> Back to My Complaints
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

</div><!-- End Main Content -->

<?php include '../includes/footer.php'; ?>