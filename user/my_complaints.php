<?php
// ============================================
// MY COMPLAINTS PAGE (with Archive Feature)
// user/my_complaints.php
// ============================================

require_once '../config/config.php';
require_once '../includes/functions.php';

requireLogin();

if (isAdmin()) {
    header("Location: ../admin/index.php");
    exit();
}

$page_title = "My Complaints";
$user_id = $_SESSION['user_id'];

// ============================================
// HANDLE ARCHIVE ACTION
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_complaint'])) {
    $complaint_id   = (int)$_POST['complaint_id'];
    $archive_reason = sanitizeInput($_POST['archive_reason'] ?? '');

    // Only allow archiving Resolved or Closed complaints owned by this user
    $stmt = $conn->prepare("
        SELECT complaint_id, status FROM complaints
        WHERE complaint_id = ? AND user_id = ? AND is_archived = 0
        AND status IN ('Resolved', 'Closed')
    ");
    $stmt->bind_param("ii", $complaint_id, $user_id);
    $stmt->execute();

    if ($stmt->get_result()->num_rows > 0) {
        $stmt = $conn->prepare("
            UPDATE complaints
            SET is_archived = 1, archived_by = ?, archived_at = NOW(), archive_reason = ?
            WHERE complaint_id = ?
        ");
        $stmt->bind_param("isi", $user_id, $archive_reason, $complaint_id);
        if ($stmt->execute()) {
            $success = "Complaint #$complaint_id has been archived successfully.";
        } else {
            $error = "Failed to archive complaint. Please try again.";
        }
    } else {
        $error = "Only Resolved or Closed complaints can be archived.";
    }
}

$approval_filter = isset($_GET['approval_status']) ? sanitizeInput($_GET['approval_status']) : '';

// Filter parameters
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$search_query  = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Pagination
$page             = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = RECORDS_PER_PAGE;
$offset           = ($page - 1) * $records_per_page;

// Build query — exclude archived complaints from main list
$where_conditions = ["c.user_id = ?", "c.is_archived = 0"];
$params = [$user_id];
$types  = "i";

if (!empty($status_filter)) {
    $where_conditions[] = "c.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($approval_filter)) {
    $where_conditions[] = "c.approval_status = ?";
    $params[] = $approval_filter;
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

// Count total records
$count_query = "SELECT COUNT(*) as total FROM complaints c WHERE $where_clause";
$stmt = $conn->prepare($count_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$total_records = $stmt->get_result()->fetch_assoc()['total'];
$total_pages   = ceil($total_records / $records_per_page);

// Count archived (for badge in link)
$stmt_archived_count = $conn->prepare("SELECT COUNT(*) as total FROM complaints WHERE user_id = ? AND is_archived = 1");
$stmt_archived_count->bind_param("i", $user_id);
$stmt_archived_count->execute();
$archived_count = $stmt_archived_count->get_result()->fetch_assoc()['total'];

// Fetch complaints
$query = "
    SELECT c.*, cat.category_name,
           (SELECT COUNT(*) FROM complaint_comments WHERE complaint_id = c.complaint_id) as comment_count
    FROM complaints c
    LEFT JOIN categories cat ON c.category_id = cat.category_id
    WHERE $where_clause
    ORDER BY c.submitted_date DESC
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

<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <!-- Filter by Status -->
                    <div class="col-md-3">
                        <label for="status" class="form-label">Filter by Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Status</option>
                            <option value="Pending"     <?php echo $status_filter == 'Pending'     ? 'selected' : ''; ?>>Pending</option>
                            <option value="In Progress" <?php echo $status_filter == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="Resolved"    <?php echo $status_filter == 'Resolved'    ? 'selected' : ''; ?>>Resolved</option>
                            <option value="Closed"      <?php echo $status_filter == 'Closed'      ? 'selected' : ''; ?>>Closed</option>
                        </select>
                    </div>

                    <!-- Approval Status -->
                    <div class="col-md-3">
                        <label for="approval_status" class="form-label">Approval Status</label>
                        <select class="form-select" id="approval_status" name="approval_status">
                            <option value="">All</option>
                            <option value="pending_review"      <?php echo $approval_filter == 'pending_review'      ? 'selected' : ''; ?>>Pending Review</option>
                            <option value="approved"            <?php echo $approval_filter == 'approved'            ? 'selected' : ''; ?>>Approved</option>
                            <option value="changes_requested"   <?php echo $approval_filter == 'changes_requested'   ? 'selected' : ''; ?>>Changes Requested</option>
                            <option value="rejected"            <?php echo $approval_filter == 'rejected'            ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>

                    <!-- Search -->
                    <div class="col-md-4">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search"
                               placeholder="Search by subject or description..."
                               value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>

                    <!-- Filter Button -->
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-list-ul"></i> My Complaints (<?php echo $total_records; ?>)</span>
                <div class="d-flex gap-2 align-items-center">
                    <!-- Archive Link -->
                    <a href="archived_complaints.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-archive"></i> Archived
                        <?php if ($archived_count > 0): ?>
                            <span class="badge bg-secondary"><?php echo $archived_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <!-- New Complaint Button -->
                    <?php
                    $limit_check = checkDailyComplaintLimit($user_id);
                    if ($limit_check['can_submit']):
                    ?>
                        <a href="submit_complaint.php" class="btn btn-sm btn-light">
                            <i class="bi bi-plus-circle"></i> New Complaint
                            <span class="badge bg-primary"><?php echo $limit_check['remaining']; ?> left</span>
                        </a>
                    <?php else: ?>
                        <button class="btn btn-sm btn-secondary" disabled title="Daily limit reached">
                            <i class="bi bi-exclamation-circle"></i> Limit Reached
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <?php if ($complaints->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Subject</th>
                                    <th>Approval</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Submitted</th>
                                    <th>Days Pending</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($complaint = $complaints->fetch_assoc()): ?>
                                <tr>
                                    <td><strong>#<?php echo $complaint['complaint_id']; ?></strong></td>
                                    <td>
                                        <div style="max-width: 300px;">
                                            <strong><?php echo htmlspecialchars($complaint['subject']); ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo substr(htmlspecialchars($complaint['description']), 0, 80) . '...'; ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        if ($complaint['approval_status'] == 'pending_review') {
                                            echo '<span class="badge bg-warning text-dark">Pending</span>';
                                        } elseif ($complaint['approval_status'] == 'approved') {
                                            echo '<span class="badge bg-success">✓ Approved</span>';
                                        } elseif ($complaint['approval_status'] == 'rejected') {
                                            echo '<span class="badge bg-danger">✗ Rejected</span>';
                                        } elseif ($complaint['approval_status'] == 'changes_requested') {
                                            echo '<span class="badge bg-info">📝 Edit</span>';
                                        }
                                        ?>
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
                                    <td><?php echo formatDate($complaint['submitted_date']); ?></td>
                                    <td>
                                        <?php
                                        $days  = daysElapsed($complaint['submitted_date']);
                                        $color = $days > 7 ? 'text-danger' : ($days > 3 ? 'text-warning' : 'text-muted');
                                        ?>
                                        <span class="<?php echo $color; ?>">
                                            <?php echo $days; ?> day<?php echo $days != 1 ? 's' : ''; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <!-- View Button -->
                                            <a href="complaint_details.php?id=<?php echo $complaint['complaint_id']; ?>"
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> View
                                            </a>

                                            <?php if ($complaint['comment_count'] > 0): ?>
                                                <span class="badge bg-info comment-badge">
                                                    <i class="bi bi-chat-dots-fill"></i> <?php echo $complaint['comment_count']; ?>
                                                </span>
                                            <?php endif; ?>

                                            <!-- Archive Button — only for Resolved or Closed -->
                                            <?php if (in_array($complaint['status'], ['Resolved', 'Closed'])): ?>
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-secondary"
                                                        title="Archive this complaint"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#archiveModal"
                                                        data-id="<?php echo $complaint['complaint_id']; ?>"
                                                        data-subject="<?php echo htmlspecialchars($complaint['subject']); ?>">
                                                    <i class="bi bi-archive"></i>
                                                </button>
                                            <?php endif; ?>
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
                                <a class="page-link" href="?page=<?php echo $page-1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_query); ?>">Previous</a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_query); ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page+1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_query); ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 4rem; color: #ddd;"></i>
                        <h5 class="mt-3 text-muted">No complaints found</h5>
                        <?php if (!empty($status_filter) || !empty($search_query)): ?>
                            <p class="text-muted">Try adjusting your filters</p>
                            <a href="my_complaints.php" class="btn btn-outline-primary">Clear Filters</a>
                        <?php else: ?>
                            <p class="text-muted">You haven't submitted any complaints yet</p>
                            <a href="submit_complaint.php" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Submit Your First Complaint
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</div> <!-- End Main Content -->

<!-- Archive Confirmation Modal -->
<div class="modal fade" id="archiveModal" tabindex="-1" aria-labelledby="archiveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title" id="archiveModalLabel">
                    <i class="bi bi-archive"></i> Archive Complaint
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <p>You are about to archive: <strong id="archiveSubject"></strong></p>
                    <p class="text-muted">Archived complaints are hidden from your main list but can be restored anytime from <strong>Archived Complaints</strong>.</p>

                    <input type="hidden" name="complaint_id" id="archiveComplaintId">

                    <div class="mb-3">
                        <label for="archive_reason" class="form-label">Reason (Optional)</label>
                        <input type="text" class="form-control" name="archive_reason" id="archive_reason"
                               placeholder="e.g. Issue resolved, no longer relevant...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x"></i> Cancel
                    </button>
                    <button type="submit" name="archive_complaint" class="btn btn-secondary">
                        <i class="bi bi-archive"></i> Archive Complaint
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Pass complaint data to archive modal
document.addEventListener('DOMContentLoaded', function () {
    const archiveModal = document.getElementById('archiveModal');
    archiveModal.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        document.getElementById('archiveComplaintId').value = btn.getAttribute('data-id');
        document.getElementById('archiveSubject').textContent = btn.getAttribute('data-subject');
        document.getElementById('archive_reason').value = '';
    });
});

// ── Comment badge logic (unchanged from original) ──
document.addEventListener('DOMContentLoaded', function() {
    const lastSeenCounts = JSON.parse(localStorage.getItem('lastSeenCommentCounts') || '{}');

    document.querySelectorAll('.comment-badge').forEach(badge => {
        const row = badge.closest('tr');
        const complaintLink = row.querySelector('a[href*="complaint_details.php"]');

        if (complaintLink) {
            const url = new URL(complaintLink.href);
            const complaintId = url.searchParams.get('id');
            const currentCount = parseInt(badge.textContent.trim().match(/\d+/)[0]);
            const lastSeenCount = lastSeenCounts[complaintId] || 0;

            if (currentCount <= lastSeenCount) {
                badge.style.transition = 'all 0.3s ease';
                badge.style.opacity = '0';
                badge.style.transform = 'scale(0) translateX(20px)';
                setTimeout(() => badge.remove(), 300);
            } else {
                const newComments = currentCount - lastSeenCount;
                if (newComments < currentCount) {
                    badge.innerHTML = `<i class="bi bi-chat-dots-fill"></i> ${newComments} NEW`;
                    badge.classList.add('badge-new');
                    badge.style.animation = 'pulse 2s infinite';
                } else {
                    badge.classList.add('badge-unread');
                    badge.style.animation = 'pulse 2s infinite';
                }
            }
        }
    });
});

const style = document.createElement('style');
style.textContent = `
    @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.08); } }
    .badge-new {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        box-shadow: 0 0 10px rgba(102, 126, 234, 0.5);
        font-weight: bold;
    }
    .badge-unread {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important;
        box-shadow: 0 0 10px rgba(245, 87, 108, 0.5);
    }
    [data-theme="dark"] .badge-new,
    [data-theme="dark"] .badge-unread { border: 1px solid rgba(255,255,255,0.3); }
`;
document.head.appendChild(style);
</script>

<?php include '../includes/footer.php'; ?>