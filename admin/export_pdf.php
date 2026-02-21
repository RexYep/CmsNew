<?php
// ============================================
// EXPORT TO PDF
// admin/export_pdf.php
// ============================================

require_once '../config/config.php';
require_once '../includes/functions.php';

requireAdmin();

// Date range
$start_date = isset($_GET['start_date']) ? sanitizeInput($_GET['start_date']) : date('Y-m-01');
$end_date   = isset($_GET['end_date'])   ? sanitizeInput($_GET['end_date'])   : date('Y-m-d');

// WHERE clause for regular admins
$where_clause = "";
if (!isSuperAdmin()) {
    $admin_id     = $_SESSION['user_id'];
    $where_clause = "WHERE assigned_to = $admin_id";
}

$and_or = $where_clause ? "AND" : "WHERE";

// ── Queries ──────────────────────────────────────────────────────────────────
$total_complaints  = $conn->query("SELECT COUNT(*) as total FROM complaints $where_clause")->fetch_assoc()['total'];
$total_users       = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'")->fetch_assoc()['total'];
$pending_complaints= $conn->query("SELECT COUNT(*) as total FROM complaints $where_clause $and_or status = 'Pending'")->fetch_assoc()['total'];
$resolved_complaints=$conn->query("SELECT COUNT(*) as total FROM complaints $where_clause $and_or (status = 'Resolved' OR status = 'Closed')")->fetch_assoc()['total'];

$avg_resolution    = $conn->query("SELECT AVG(DATEDIFF(resolved_date, submitted_date)) as avg_days FROM complaints WHERE resolved_date IS NOT NULL")->fetch_assoc();
$avg_resolution_days = round($avg_resolution['avg_days'] ?? 0, 1);

$status_stats   = $conn->query("SELECT status, COUNT(*) as count FROM complaints $where_clause GROUP BY status ORDER BY count DESC");
$priority_stats = $conn->query("SELECT priority, COUNT(*) as count FROM complaints $where_clause GROUP BY priority ORDER BY FIELD(priority,'High','Medium','Low')");
$category_stats = $conn->query("
    SELECT cat.category_name, COUNT(c.complaint_id) as count
    FROM categories cat
    LEFT JOIN complaints c ON cat.category_id = c.category_id
    " . ($where_clause ? str_replace("WHERE","AND",$where_clause) : "") . "
    GROUP BY cat.category_id, cat.category_name
    ORDER BY count DESC
");
$top_users = $conn->query("
    SELECT u.full_name, u.email, COUNT(c.complaint_id) as complaint_count
    FROM users u
    LEFT JOIN complaints c ON u.user_id = c.user_id
    WHERE u.role = 'user'
    GROUP BY u.user_id
    ORDER BY complaint_count DESC
    LIMIT 5
");

// ── Fetch complaints list in date range ──────────────────────────────────────
$stmt = $conn->prepare("
    SELECT c.complaint_id, c.subject, c.status, c.priority,
           cat.category_name, u.full_name, c.submitted_date
    FROM complaints c
    LEFT JOIN categories cat ON c.category_id = cat.category_id
    LEFT JOIN users u ON c.user_id = u.user_id
    WHERE DATE(c.submitted_date) BETWEEN ? AND ?
    ORDER BY c.submitted_date DESC
");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$complaints_list = $stmt->get_result();

// ── Output HTML designed for printing / PDF ──────────────────────────────────
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Complaints Report - <?php echo $start_date; ?> to <?php echo $end_date; ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #222; padding: 30px; }

        /* Header */
        .report-header { text-align: center; margin-bottom: 24px; border-bottom: 3px solid #4f46e5; padding-bottom: 14px; }
        .report-header h1 { font-size: 22px; color: #4f46e5; }
        .report-header p  { color: #555; margin-top: 4px; }

        /* Summary cards */
        .summary { display: flex; gap: 12px; margin-bottom: 24px; }
        .card { flex: 1; border: 1px solid #ddd; border-radius: 6px; padding: 12px; }
        .card h4 { font-size: 11px; color: #888; margin-bottom: 4px; text-transform: uppercase; }
        .card .num { font-size: 26px; font-weight: bold; color: #333; }

        /* Section titles */
        .section-title { font-size: 14px; font-weight: bold; margin: 20px 0 8px; color: #4f46e5;
                         border-left: 4px solid #4f46e5; padding-left: 8px; }

        /* Tables */
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        thead tr { background: #4f46e5; color: #fff; }
        th, td { border: 1px solid #ddd; padding: 7px 10px; text-align: left; }
        tbody tr:nth-child(even) { background: #f7f7fb; }

        /* Badges */
        .badge { padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: bold; }
        .badge-pending  { background:#fff3cd; color:#856404; }
        .badge-in-progress { background:#cce5ff; color:#004085; }
        .badge-resolved { background:#d4edda; color:#155724; }
        .badge-closed   { background:#d6d8db; color:#383d41; }
        .badge-high     { background:#f8d7da; color:#721c24; }
        .badge-medium   { background:#fff3cd; color:#856404; }
        .badge-low      { background:#d4edda; color:#155724; }

        /* Footer */
        .report-footer { margin-top: 30px; text-align: center; font-size: 10px; color: #aaa; border-top: 1px solid #eee; padding-top: 10px; }

        @media print {
            body { padding: 15px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<!-- Print / Save button (hidden when printing) -->
<div class="no-print" style="margin-bottom:16px; display:flex; gap:10px;">
    <button onclick="window.print()" style="padding:8px 18px; background:#4f46e5; color:#fff; border:none; border-radius:5px; cursor:pointer; font-size:13px;">
        🖨️ Print / Save as PDF
    </button>
    <a href="reports.php" style="padding:8px 18px; background:#eee; color:#333; border:none; border-radius:5px; text-decoration:none; font-size:13px;">← Back</a>
</div>

<!-- Report Header -->
<div class="report-header">
    <h1>📋 Complaints Management Report</h1>
    <p>Date Range: <strong><?php echo $start_date; ?></strong> to <strong><?php echo $end_date; ?></strong></p>
    <p>Generated: <?php echo date('F d, Y h:i A'); ?></p>
</div>

<!-- Summary Cards -->
<div class="summary">
    <div class="card">
        <h4>Total Complaints</h4>
        <div class="num"><?php echo $total_complaints; ?></div>
    </div>
    <div class="card">
        <h4>Pending</h4>
        <div class="num"><?php echo $pending_complaints; ?></div>
    </div>
    <div class="card">
        <h4>Resolved / Closed</h4>
        <div class="num"><?php echo $resolved_complaints; ?></div>
    </div>
    <div class="card">
        <h4>Avg Resolution</h4>
        <div class="num"><?php echo $avg_resolution_days; ?> <span style="font-size:14px;">days</span></div>
    </div>
    <div class="card">
        <h4>Total Users</h4>
        <div class="num"><?php echo $total_users; ?></div>
    </div>
</div>

<!-- By Status -->
<div class="section-title">Complaints by Status</div>
<table>
    <thead><tr><th>Status</th><th>Count</th><th>Percentage</th></tr></thead>
    <tbody>
        <?php while ($s = $status_stats->fetch_assoc()):
            $pct = $total_complaints > 0 ? round(($s['count']/$total_complaints)*100,1) : 0;
            $cls = strtolower(str_replace(' ','-',$s['status']));
        ?>
        <tr>
            <td><span class="badge badge-<?php echo $cls; ?>"><?php echo $s['status']; ?></span></td>
            <td><?php echo $s['count']; ?></td>
            <td><?php echo $pct; ?>%</td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<!-- By Priority -->
<div class="section-title">Complaints by Priority</div>
<table>
    <thead><tr><th>Priority</th><th>Count</th><th>Percentage</th></tr></thead>
    <tbody>
        <?php while ($p = $priority_stats->fetch_assoc()):
            $pct = $total_complaints > 0 ? round(($p['count']/$total_complaints)*100,1) : 0;
        ?>
        <tr>
            <td><span class="badge badge-<?php echo strtolower($p['priority']); ?>"><?php echo $p['priority']; ?></span></td>
            <td><?php echo $p['count']; ?></td>
            <td><?php echo $pct; ?>%</td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<!-- By Category -->
<div class="section-title">Complaints by Category</div>
<table>
    <thead><tr><th>Category</th><th>Count</th></tr></thead>
    <tbody>
        <?php while ($c = $category_stats->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($c['category_name']); ?></td>
            <td><?php echo $c['count']; ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<!-- Top Users -->
<div class="section-title">Top 5 Most Active Users</div>
<table>
    <thead><tr><th>Full Name</th><th>Email</th><th>Complaints Filed</th></tr></thead>
    <tbody>
        <?php while ($u = $top_users->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($u['full_name']); ?></td>
            <td><?php echo htmlspecialchars($u['email']); ?></td>
            <td><?php echo $u['complaint_count']; ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<!-- Complaints List in Date Range -->
<div class="section-title">Complaints Filed (<?php echo $start_date; ?> to <?php echo $end_date; ?>)</div>
<table>
    <thead>
        <tr>
            <th>#</th>
        <th>Subject</th>
            <th>Category</th>
            <th>Filed By</th>
            <th>Status</th>
            <th>Priority</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($complaints_list->num_rows > 0):
              $i = 1;
              while ($row = $complaints_list->fetch_assoc()):
                  $s_cls = strtolower(str_replace(' ','-',$row['status']));
                  $p_cls = strtolower($row['priority']);
        ?>
        <tr>
            <td><?php echo $i++; ?></td>
            <td><?php echo htmlspecialchars($row['subject']); ?></td>
            <td><?php echo htmlspecialchars($row['category_name'] ?? 'N/A'); ?></td>
            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
            <td><span class="badge badge-<?php echo $s_cls; ?>"><?php echo $row['status']; ?></span></td>
            <td><span class="badge badge-<?php echo $p_cls; ?>"><?php echo $row['priority']; ?></span></td>
            <td><?php echo date('M d, Y', strtotime($row['submitted_date'])); ?></td>
        </tr>
        <?php endwhile; else: ?>
        <tr><td colspan="7" style="text-align:center;color:#999;">No complaints found in this date range.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<div class="report-footer">
    Complaint Management System &mdash; Report generated on <?php echo date('F d, Y \a\t h:i A'); ?>
</div>

</body>
</html>