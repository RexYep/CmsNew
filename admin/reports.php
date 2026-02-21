<?php
// ============================================
// REPORTS PAGE
// admin/reports.php
// ============================================

require_once '../config/config.php';
require_once '../includes/functions.php';

requireAdmin();

$page_title = "Reports & Analytics";

// Build WHERE clause for regular admins
$where_clause = "";
if (!isSuperAdmin()) {
    $admin_id = $_SESSION['user_id'];
    $where_clause = "WHERE assigned_to = $admin_id";
}

// Get date range filter
$start_date = isset($_GET['start_date']) ? sanitizeInput($_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? sanitizeInput($_GET['end_date']) : date('Y-m-d');

// Overall Statistics
$total_complaints = $conn->query("SELECT COUNT(*) as total FROM complaints $where_clause")->fetch_assoc()['total'];
$total_users = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'")->fetch_assoc()['total'];
$pending_complaints = $conn->query("SELECT COUNT(*) as total FROM complaints $where_clause " . ($where_clause ? "AND" : "WHERE") . " status = 'Pending'")->fetch_assoc()['total'];
$resolved_complaints = $conn->query("SELECT COUNT(*) as total FROM complaints $where_clause " . ($where_clause ? "AND" : "WHERE") . " (status = 'Resolved' OR status = 'Closed')")->fetch_assoc()['total'];

// Complaints by Status
$status_stats = $conn->query("
    SELECT status, COUNT(*) as count 
    FROM complaints 
    $where_clause
    GROUP BY status
    ORDER BY count DESC
");

// Complaints by Priority
$priority_stats = $conn->query("
    SELECT priority, COUNT(*) as count 
    FROM complaints 
    $where_clause
    GROUP BY priority
    ORDER BY FIELD(priority, 'High', 'Medium', 'Low')
");

// Complaints by Category
$category_stats = $conn->query("
    SELECT cat.category_name, COUNT(c.complaint_id) as count
    FROM categories cat
    LEFT JOIN complaints c ON cat.category_id = c.category_id
    " . ($where_clause ? str_replace("WHERE", "AND", $where_clause) : "") . "
    GROUP BY cat.category_id, cat.category_name
    ORDER BY count DESC
");

// Complaints over time (last 7 days)
$daily_stats = $conn->query("
    SELECT DATE(submitted_date) as date, COUNT(*) as count
    FROM complaints
    $where_clause " . ($where_clause ? "AND" : "WHERE") . " submitted_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(submitted_date)
    ORDER BY date ASC
");

// Top 5 most active users
$top_users = $conn->query("
    SELECT u.full_name, u.email, COUNT(c.complaint_id) as complaint_count
    FROM users u
    LEFT JOIN complaints c ON u.user_id = c.user_id
    WHERE u.role = 'user'
    GROUP BY u.user_id
    ORDER BY complaint_count DESC
    LIMIT 5
");

// Average resolution time
$avg_resolution = $conn->query("
    SELECT AVG(DATEDIFF(resolved_date, submitted_date)) as avg_days
    FROM complaints
    WHERE resolved_date IS NOT NULL
")->fetch_assoc();

$avg_resolution_days = round($avg_resolution['avg_days'] ?? 0, 1);

// Complaints in date range
$stmt = $conn->prepare("
    SELECT COUNT(*) as count
    FROM complaints
    WHERE DATE(submitted_date) BETWEEN ? AND ?
");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$range_complaints = $stmt->get_result()->fetch_assoc()['count'];

include '../includes/header.php';
include '../includes/navbar.php';
?>

<?php if (!isSuperAdmin()): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> <strong>Regular Admin:</strong> 
        Reports show only complaints assigned to you.
    </div>
<?php endif; ?>

<!-- Date Range Filter -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" 
                               value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" 
                               value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel"></i> Apply Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Summary Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stats-card" style="border-left-color: #667eea;">
            <div class="card-body">
                <h6 class="text-muted">Total Complaints</h6>
                <h2><?php echo $total_complaints; ?></h2>
                <small class="text-muted">All time</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card" style="border-left-color: #ffc107;">
            <div class="card-body">
                <h6 class="text-muted">Pending</h6>
                <h2><?php echo $pending_complaints; ?></h2>
                <small class="text-muted">Awaiting action</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card" style="border-left-color: #28a745;">
            <div class="card-body">
                <h6 class="text-muted">Resolved</h6>
                <h2><?php echo $resolved_complaints; ?></h2>
                <small class="text-muted">Completed</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card" style="border-left-color: #17a2b8;">
            <div class="card-body">
                <h6 class="text-muted">Avg Resolution</h6>
                <h2><?php echo $avg_resolution_days; ?></h2>
                <small class="text-muted">Days</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Complaints by Status -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-pie-chart"></i> Complaints by Status
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Count</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($stat = $status_stats->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <span class="<?php echo getStatusBadge($stat['status']); ?>">
                                    <?php echo $stat['status']; ?>
                                </span>
                            </td>
                            <td><strong><?php echo $stat['count']; ?></strong></td>
                            <td>
                                <?php 
                                $percentage = $total_complaints > 0 ? round(($stat['count'] / $total_complaints) * 100, 1) : 0;
                                ?>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 10px; min-width: 60px;">
                                        <div class="progress-bar" role="progressbar" 
                                             style="width: <?php echo max($percentage, 3); ?>%"
                                             aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                    </div>
                                    <span class="text-muted" style="font-size: 0.82rem; min-width: 38px; text-align: right;">
                                        <?php echo $percentage; ?>%
                                    </span>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Complaints by Priority -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-bar-chart"></i> Complaints by Priority
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Priority</th>
                            <th>Count</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($stat = $priority_stats->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <span class="<?php echo getPriorityBadge($stat['priority']); ?>">
                                    <?php echo $stat['priority']; ?>
                                </span>
                            </td>
                            <td><strong><?php echo $stat['count']; ?></strong></td>
                            <td>
                                <?php 
                                $percentage = $total_complaints > 0 ? round(($stat['count'] / $total_complaints) * 100, 1) : 0;
                                ?>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 10px; min-width: 60px;">
                                        <div class="progress-bar bg-<?php echo $stat['priority'] == 'High' ? 'danger' : ($stat['priority'] == 'Medium' ? 'warning' : 'success'); ?>" 
                                             role="progressbar" 
                                             style="width: <?php echo max($percentage, 3); ?>%"
                                             aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                    </div>
                                    <span class="text-muted" style="font-size: 0.82rem; min-width: 38px; text-align: right;">
                                        <?php echo $percentage; ?>%
                                    </span>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Complaints by Category -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-tags"></i> Complaints by Category
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($stat = $category_stats->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($stat['category_name']); ?></td>
                            <td><strong><?php echo $stat['count']; ?></strong></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Top Users -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-star"></i> Top 5 Most Active Users
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Complaints</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($top_users->num_rows > 0): ?>
                            <?php while ($user = $top_users->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><small><?php echo htmlspecialchars($user['email']); ?></small></td>
                                <td><strong><?php echo $user['complaint_count']; ?></strong></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted">No data available</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Daily Complaints (Last 7 Days) -->
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-graph-up"></i> Complaints Over Time (Last 7 Days)
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Complaints</th>
                            <th>Visual</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $max_count = 0;
                        $daily_data = [];
                        while ($stat = $daily_stats->fetch_assoc()) {
                            $daily_data[] = $stat;
                            if ($stat['count'] > $max_count) $max_count = $stat['count'];
                        }
                        
                        foreach ($daily_data as $stat): 
                        $bar_width = $max_count > 0 ? ($stat['count'] / $max_count) * 100 : 0;
                        ?>
                        <tr>
                            <td><?php echo formatDate($stat['date']); ?></td>
                            <td><strong><?php echo $stat['count']; ?></strong></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 10px; min-width: 60px;">
                                        <div class="progress-bar bg-primary" role="progressbar" 
                                             style="width: <?php echo max($bar_width, 3); ?>%"
                                             aria-valuenow="<?php echo $bar_width; ?>" aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                    </div>
                                    <span class="text-muted" style="font-size: 0.82rem; min-width: 38px; text-align: right;">
                                        <?php echo $stat['count']; ?> filed
                                    </span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Export Options -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="mb-3"><i class="bi bi-download"></i> Export Options</h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-success" onclick="window.print();">
                        <i class="bi bi-printer"></i> Print Report
                    </button>
                    <a href="export_pdf.php?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>" 
                       target="_blank" class="btn btn-outline-primary">
                        <i class="bi bi-file-pdf"></i> Export to PDF
                    </a>
                    <a href="export_excel.php?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>" 
                       class="btn btn-outline-info">
                        <i class="bi bi-file-excel"></i> Export to Excel
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

</div> <!-- End page-content -->
</div> <!-- End main-content -->

<?php include '../includes/footer.php'; ?>