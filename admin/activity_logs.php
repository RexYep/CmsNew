<?php
// ============================================
// ACTIVITY LOGS PAGE
// admin/activity_logs.php
// ============================================

require_once '../config/config.php';
require_once '../includes/functions.php';

requireAdmin();

// Super admin only
if (!isSuperAdmin()) {
    header("Location: index.php");
    exit();
}

$page_title = "Activity Logs";

// Filter parameters
$action_filter = isset($_GET['action']) ? sanitizeInput($_GET['action']) : '';
$user_filter   = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$date_from     = isset($_GET['date_from']) ? sanitizeInput($_GET['date_from']) : '';
$date_to       = isset($_GET['date_to']) ? sanitizeInput($_GET['date_to']) : '';
$search_query  = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Pagination
$page             = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 20;
$offset           = ($page - 1) * $records_per_page;

// Build WHERE
$where_conditions = ["1=1"];
$params = [];
$types  = "";

if (!empty($action_filter)) {
    $where_conditions[] = "al.action = ?";
    $params[] = $action_filter;
    $types .= "s";
}

if (!empty($user_filter)) {
    $where_conditions[] = "al.user_id = ?";
    $params[] = $user_filter;
    $types .= "i";
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(al.created_at) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(al.created_at) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

if (!empty($search_query)) {
    $where_conditions[] = "(al.description LIKE ? OR al.ip_address LIKE ? OR u.full_name LIKE ?)";
    $sp = "%$search_query%";
    $params[] = $sp;
    $params[] = $sp;
    $params[] = $sp;
    $types .= "sss";
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Count total
$count_query = "
    SELECT COUNT(*) as total
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.user_id
    $where_clause
";
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages   = ceil($total_records / $records_per_page);

// Fetch logs
$query = "
    SELECT al.*, u.full_name, u.email, u.role
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.user_id
    $where_clause
    ORDER BY al.created_at DESC
    LIMIT ? OFFSET ?
";
$main_params = array_merge($params, [$records_per_page, $offset]);
$main_types  = $types . "ii";

$stmt = $conn->prepare($query);
$stmt->bind_param($main_types, ...$main_params);
$stmt->execute();
$logs = $stmt->get_result();

// Get distinct actions for filter dropdown
$actions_result = $conn->query("SELECT DISTINCT action FROM activity_logs ORDER BY action");
$actions = [];
while ($row = $actions_result->fetch_assoc()) {
    $actions[] = $row['action'];
}

// Suspicious activity count (5+ failed logins in last hour)
$suspicious_query = "
    SELECT al.ip_address, COUNT(*) as attempts, MAX(al.created_at) as last_attempt
    FROM activity_logs al
    WHERE al.action = 'login_failed'
    AND al.created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
    GROUP BY al.ip_address
    HAVING attempts >= 5
    ORDER BY attempts DESC
";
$suspicious = $conn->query($suspicious_query);

// Pagination URL helper
$pagination_params = http_build_query([
    'action'    => $action_filter,
    'user_id'   => $user_filter,
    'date_from' => $date_from,
    'date_to'   => $date_to,
    'search'    => $search_query,
]);

// Action badge helper
function getActionBadge($action)
{
    $badges = [
        'login_success'    => ['bg-success',  'bi-check-circle',       'Login Success'],
        'login_failed'     => ['bg-danger',   'bi-x-circle',           'Login Failed'],
        'login_2fa_sent'   => ['bg-info',     'bi-shield-lock',        '2FA Sent'],
        'logout'           => ['bg-secondary','bi-box-arrow-right',    'Logout'],
        'password_changed' => ['bg-warning',  'bi-key',                'Password Changed'],
    ];
    $b = $badges[$action] ?? ['bg-secondary', 'bi-activity', ucfirst(str_replace('_', ' ', $action))];
    return "<span class='badge {$b[0]}'><i class='bi {$b[1]} me-1'></i>{$b[2]}</span>";
}

include '../includes/header.php';
include '../includes/navbar.php';
?>

<!-- Suspicious Activity Alert -->
<?php if ($suspicious->num_rows > 0): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <strong>Suspicious Activity Detected!</strong>
    <?php echo $suspicious->num_rows; ?> IP address(es) have 5+ failed login attempts in the last hour.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="row mb-4">
    <?php
    $stats = [
        ['login_success',    'Successful Logins Today',  'bg-success', 'bi-check-circle'],
        ['login_failed',     'Failed Logins Today',      'bg-danger',  'bi-x-circle'],
        ['password_changed', 'Password Changes Today',   'bg-warning', 'bi-key'],
        ['logout',           'Logouts Today',            'bg-secondary','bi-box-arrow-right'],
    ];
foreach ($stats as [$action, $label, $color, $icon]):
    $count = $conn->query("
            SELECT COUNT(*) as c FROM activity_logs
            WHERE action = '$action' AND DATE(created_at) = CURDATE()
        ")->fetch_assoc()['c'];
    ?>
    <div class="col-md-3 mb-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle <?php echo $color; ?> bg-opacity-10 p-3">
                    <i class="bi <?php echo $icon; ?> <?php echo $color; ?> fs-4" style="color: inherit;"></i>
                </div>
                <div>
                    <div class="fw-bold fs-4"><?php echo $count; ?></div>
                    <small class="text-muted"><?php echo $label; ?></small>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Suspicious IPs (if any) -->
<?php
$suspicious->data_seek(0);
if ($suspicious->num_rows > 0):
    ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <i class="bi bi-shield-exclamation me-2"></i> Suspicious IP Addresses (5+ failed logins in last hour)
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>IP Address</th>
                            <th>Failed Attempts</th>
                            <th>Last Attempt</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($s = $suspicious->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($s['ip_address']); ?></strong></td>
                            <td><span class="badge bg-danger"><?php echo $s['attempts']; ?></span></td>
                            <td><small><?php echo formatDateTime($s['last_attempt']); ?></small></td>
                            <td>
                                <a href="?action=login_failed&search=<?php echo urlencode($s['ip_address']); ?>"
                                   class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-search"></i> View Attempts
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Filter Section -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="" class="row g-2 align-items-end">

                    <div class="col-md-2">
                        <label class="form-label">Action</label>
                        <select class="form-select form-select-sm" name="action">
                            <option value="">All Actions</option>
                            <?php foreach ($actions as $a): ?>
                            <option value="<?php echo $a; ?>" <?php echo $action_filter == $a ? 'selected' : ''; ?>>
                                <?php echo ucfirst(str_replace('_', ' ', $a)); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Date From</label>
                        <input type="date" class="form-control form-control-sm" name="date_from"
                               value="<?php echo $date_from; ?>">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Date To</label>
                        <input type="date" class="form-control form-control-sm" name="date_to"
                               value="<?php echo $date_to; ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Search (user, IP, description)</label>
                        <input type="text" class="form-control form-control-sm" name="search"
                               placeholder="Search..."
                               value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>

                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>

                    <div class="col-md-1">
                        <a href="activity_logs.php" class="btn btn-outline-secondary btn-sm w-100">
                            <i class="bi bi-x-circle"></i>
                        </a>
                    </div>

                </form>

                <?php if (!empty($action_filter) || !empty($search_query) || !empty($date_from) || !empty($date_to)): ?>
                <div class="mt-2">
                    <small class="text-muted">
                        <i class="bi bi-funnel"></i> Filters active —
                        <a href="activity_logs.php" class="text-decoration-none">Clear all</a>
                    </small>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<!-- Logs Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>
                    <i class="bi bi-shield-check"></i> Activity Logs
                    <span class="badge bg-secondary ms-1"><?php echo $total_records; ?></span>
                </span>
                <small class="text-muted">Showing latest activities</small>
            </div>
            <div class="card-body p-0">

                <?php if ($logs->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date & Time</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>IP Address</th>
                                <th>Browser</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($log = $logs->fetch_assoc()): ?>
                            <tr <?php echo $log['action'] === 'login_failed' ? 'class="table-danger bg-opacity-25"' : ''; ?>>
                                <td>
                                    <small><?php echo formatDateTime($log['created_at']); ?></small>
                                </td>
                                <td>
                                    <?php if (!empty($log['full_name'])): ?>
                                        <strong><?php echo htmlspecialchars($log['full_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($log['email']); ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo getActionBadge($log['action']); ?></td>
                                <td>
                                    <small><?php echo htmlspecialchars($log['description'] ?? '—'); ?></small>
                                </td>
                                <td>
                                    <code class="small"><?php echo htmlspecialchars($log['ip_address'] ?? '—'); ?></code>
                                </td>
                                <td>
                                    <small class="text-muted" title="<?php echo htmlspecialchars($log['user_agent'] ?? ''); ?>">
                                        <?php
                                            $ua = $log['user_agent'] ?? '';
                                if (str_contains($ua, 'Chrome')) {
                                    echo '<i class="bi bi-browser-chrome"></i> Chrome';
                                } elseif (str_contains($ua, 'Firefox')) {
                                    echo '<i class="bi bi-browser-firefox"></i> Firefox';
                                } elseif (str_contains($ua, 'Safari')) {
                                    echo '<i class="bi bi-browser-safari"></i> Safari';
                                } elseif (str_contains($ua, 'Edge')) {
                                    echo '<i class="bi bi-browser-edge"></i> Edge';
                                } else {
                                    echo '<i class="bi bi-browser-chrome"></i> Unknown';
                                }
                                ?>
                                    </small>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="p-3">
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center mb-0">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&<?php echo $pagination_params; ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            <?php
                            $start_page = max(1, $page - 2);
                    $end_page   = min($total_pages, $page + 2);
                    for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo $pagination_params; ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&<?php echo $pagination_params; ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>

                <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-shield-check" style="font-size: 4rem; color: #ddd;"></i>
                    <h5 class="mt-3 text-muted">No activity logs found</h5>
                    <?php if (!empty($action_filter) || !empty($search_query) || !empty($date_from)): ?>
                        <a href="activity_logs.php" class="btn btn-outline-secondary">Clear Filters</a>
                    <?php else: ?>
                        <p class="text-muted">Activity will appear here once users start logging in.</p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

</div><!-- End page-content -->
</div><!-- End main-content -->

<?php include '../includes/footer.php'; ?>