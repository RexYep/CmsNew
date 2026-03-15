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
$user_id    = $_SESSION['user_id'];

// Filter parameters
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$search_query  = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$month_filter  = isset($_GET['month']) ? (int)$_GET['month'] : '';
$year_filter   = isset($_GET['year']) ? (int)$_GET['year'] : '';

// Pagination
$page             = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = RECORDS_PER_PAGE;
$offset           = ($page - 1) * $records_per_page;

// Build query
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

if (!empty($month_filter)) {
    $where_conditions[] = "MONTH(c.submitted_date) = ?";
    $params[] = $month_filter;
    $types .= "i";
}

if (!empty($year_filter)) {
    $where_conditions[] = "YEAR(c.submitted_date) = ?";
    $params[] = $year_filter;
    $types .= "i";
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

// Pagination URL helper
$pagination_params = http_build_query([
    'status' => $status_filter,
    'month'  => $month_filter,
    'year'   => $year_filter,
    'search' => $search_query,
]);

include '../includes/header.php';
include '../includes/navbar.php';
?>

<!-- Filter Section -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="" class="row g-2 align-items-end">

                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">All Status</option>
                            <option value="Resolved" <?php echo $status_filter == 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                            <option value="Closed"   <?php echo $status_filter == 'Closed' ? 'selected' : ''; ?>>Closed</option>
                            <option value="Rejected" <?php echo $status_filter == 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Month</label>
                        <select class="form-select" name="month">
                            <option value="">All Months</option>
                            <?php
                            $months = ['January','February','March','April','May','June',
                                       'July','August','September','October','November','December'];
foreach ($months as $i => $m):
    ?>
                            <option value="<?php echo $i + 1; ?>" <?php echo $month_filter == $i + 1 ? 'selected' : ''; ?>>
                                <?php echo $m; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-1">
                        <label class="form-label">Year</label>
                        <select class="form-select" name="year">
                            <option value="">All</option>
                            <?php for ($y = (int)date('Y'); $y >= (int)date('Y') - 3; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php echo $year_filter == $y ? 'selected' : ''; ?>>
                                <?php echo $y; ?>
                            </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label">Search</label>
                        <input type="text" class="form-control" name="search"
                               placeholder="Search by subject or description..."
                               value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>

                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>

                    <div class="col-md-1">
                        <a href="archived_complaints.php" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-x-circle"></i>
                        </a>
                    </div>

                </form>

                <?php if (!empty($status_filter) || !empty($search_query) || !empty($month_filter) || !empty($year_filter)): ?>
                <div class="mt-2">
                    <small class="text-muted">
                        <i class="bi bi-funnel"></i> Filters active —
                        <a href="archived_complaints.php" class="text-decoration-none">Clear all</a>
                    </small>
                </div>
                <?php endif; ?>

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
                                        <a href="complaint_details.php?id=<?php echo $complaint['complaint_id']; ?>"
                                           class="btn btn-sm btn-outline-primary" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
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
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&<?php echo $pagination_params; ?>">Previous</a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo $pagination_params; ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&<?php echo $pagination_params; ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-archive" style="font-size: 4rem; color: #ddd;"></i>
                        <h5 class="mt-3 text-muted">No archived complaints</h5>
                        <?php if (!empty($status_filter) || !empty($search_query) || !empty($month_filter) || !empty($year_filter)): ?>
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