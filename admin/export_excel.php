<?php
// ============================================
// EXPORT TO EXCEL (CSV-based, opens in Excel)
// admin/export_excel.php
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

// ── Summary stats ─────────────────────────────────────────────────────────────
$total_complaints   = $conn->query("SELECT COUNT(*) as total FROM complaints $where_clause")->fetch_assoc()['total'];
$total_users        = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'")->fetch_assoc()['total'];
$pending_complaints = $conn->query("SELECT COUNT(*) as total FROM complaints $where_clause $and_or status = 'Pending'")->fetch_assoc()['total'];
$resolved_complaints= $conn->query("SELECT COUNT(*) as total FROM complaints $where_clause $and_or (status = 'Resolved' OR status = 'Closed')")->fetch_assoc()['total'];
$avg_resolution     = $conn->query("SELECT AVG(DATEDIFF(resolved_date, submitted_date)) as avg_days FROM complaints WHERE resolved_date IS NOT NULL")->fetch_assoc();
$avg_days           = round($avg_resolution['avg_days'] ?? 0, 1);

// ── Status breakdown ─────────────────────────────────────────────────────────
$status_stats = $conn->query("SELECT status, COUNT(*) as count FROM complaints $where_clause GROUP BY status ORDER BY count DESC");

// ── Priority breakdown ────────────────────────────────────────────────────────
$priority_stats = $conn->query("SELECT priority, COUNT(*) as count FROM complaints $where_clause GROUP BY priority ORDER BY FIELD(priority,'High','Medium','Low')");

// ── Category breakdown ────────────────────────────────────────────────────────
$category_stats = $conn->query("
    SELECT cat.category_name, COUNT(c.complaint_id) as count
    FROM categories cat
    LEFT JOIN complaints c ON cat.category_id = c.category_id
    " . ($where_clause ? str_replace("WHERE","AND",$where_clause) : "") . "
    GROUP BY cat.category_id, cat.category_name
    ORDER BY count DESC
");

// ── Top users ─────────────────────────────────────────────────────────────────
$top_users = $conn->query("
    SELECT u.full_name, u.email, COUNT(c.complaint_id) as complaint_count
    FROM users u
    LEFT JOIN complaints c ON u.user_id = c.user_id
    WHERE u.role = 'user'
    GROUP BY u.user_id
    ORDER BY complaint_count DESC
    LIMIT 5
");

// ── Full complaints list in date range ────────────────────────────────────────
$stmt = $conn->prepare("
    SELECT c.complaint_id, c.subject, c.description, c.status, c.priority,
           cat.category_name, u.full_name, u.email,
           c.submitted_date, c.resolved_date,
           DATEDIFF(IFNULL(c.resolved_date, CURDATE()), c.submitted_date) as days_open
    FROM complaints c
    LEFT JOIN categories cat ON c.category_id = cat.category_id
    LEFT JOIN users u ON c.user_id = u.user_id
    WHERE DATE(c.submitted_date) BETWEEN ? AND ?
    ORDER BY c.submitted_date DESC
");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$complaints_list = $stmt->get_result();

// ── Set headers for Excel download ────────────────────────────────────────────
$filename = "complaints_report_{$start_date}_to_{$end_date}.xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// ── Helper: escape a cell value ───────────────────────────────────────────────
function excelCell($value) {
    // Prefix with tab to prevent Excel interpreting values as formulas
    $value = str_replace('"', '""', $value);
    return '"' . $value . '"';
}

// ── Output starts here (tab-separated, Excel-compatible) ─────────────────────

// ── SHEET: SUMMARY ────────────────────────────────────────────────────────────
echo "COMPLAINT MANAGEMENT SYSTEM - REPORT\r\n";
echo "Date Range:\t" . excelCell($start_date . " to " . $end_date) . "\r\n";
echo "Generated:\t" . excelCell(date('F d, Y h:i A')) . "\r\n";
echo "\r\n";

echo "SUMMARY STATISTICS\r\n";
echo "Metric\tValue\r\n";
echo "Total Complaints\t$total_complaints\r\n";
echo "Total Users\t$total_users\r\n";
echo "Pending Complaints\t$pending_complaints\r\n";
echo "Resolved / Closed\t$resolved_complaints\r\n";
echo "Avg Resolution Time\t$avg_days days\r\n";
echo "\r\n";

// ── By Status ─────────────────────────────────────────────────────────────────
echo "COMPLAINTS BY STATUS\r\n";
echo "Status\tCount\tPercentage\r\n";
while ($s = $status_stats->fetch_assoc()) {
    $pct = $total_complaints > 0 ? round(($s['count'] / $total_complaints) * 100, 1) : 0;
    echo excelCell($s['status']) . "\t" . $s['count'] . "\t" . $pct . "%\r\n";
}
echo "\r\n";

// ── By Priority ───────────────────────────────────────────────────────────────
echo " COMPLAINTS BY PRIORITY\r\n";
echo "Priority\tCount\tPercentage\r\n";
while ($p = $priority_stats->fetch_assoc()) {
    $pct = $total_complaints > 0 ? round(($p['count'] / $total_complaints) * 100, 1) : 0;
    echo excelCell($p['priority']) . "\t" . $p['count'] . "\t" . $pct . "%\r\n";
}
echo "\r\n";

// ── By Category ───────────────────────────────────────────────────────────────
echo "COMPLAINTS BY CATEGORY\r\n";
echo "Category\tCount\r\n";
while ($c = $category_stats->fetch_assoc()) {
    echo excelCell($c['category_name']) . "\t" . $c['count'] . "\r\n";
}
echo "\r\n";

// ── Top Users ─────────────────────────────────────────────────────────────────
echo "TOP 5 MOST ACTIVE USERS\r\n";
echo "Full Name\tEmail\tComplaints Filed\r\n";
while ($u = $top_users->fetch_assoc()) {
    echo excelCell($u['full_name']) . "\t" . excelCell($u['email']) . "\t" . $u['complaint_count'] . "\r\n";
}
echo "\r\n";

// ── Full List ─────────────────────────────────────────────────────────────────
echo "COMPLAINTS LIST ($start_date to $end_date)\r\n";
echo "ID\tSubject\tCategory\tFiled By\tFiled By Email\tStatus\tPriority\tDate Submitted\tDate Resolved\tDays Open\r\n";

if ($complaints_list->num_rows > 0) {
    while ($row = $complaints_list->fetch_assoc()) {
        echo $row['complaint_id'] . "\t"
           . excelCell($row['subject']) . "\t"
           . excelCell($row['category_name'] ?? 'N/A') . "\t"
           . excelCell($row['full_name']) . "\t"
           . excelCell($row['email']) . "\t"
           . excelCell($row['status']) . "\t"
           . excelCell($row['priority']) . "\t"
           . excelCell(date('M d, Y', strtotime($row['submitted_date']))) . "\t"
           . excelCell($row['resolved_date'] ? date('M d, Y', strtotime($row['resolved_date'])) : 'Not yet resolved') . "\t"
           . $row['days_open'] . "\r\n";
    }
} else {
    echo "No complaints found in this date range.\r\n";
}

exit;