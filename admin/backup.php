<?php
// ============================================
// DATABASE BACKUP PAGE
// admin/backup.php
// ============================================

require_once '../config/config.php';
require_once '../includes/functions.php';

requireAdmin();

// Super admin only
if (!isSuperAdmin()) {
    header("Location: index.php");
    exit();
}

$page_title = "Database Backup";
$error      = '';
$success    = '';

// Handle backup download
if (isset($_GET['download']) && $_GET['download'] === '1') {

    // CSRF check
    if (!isset($_GET['token']) || $_GET['token'] !== ($_SESSION['backup_token'] ?? '')) {
        die('Invalid request.');
    }

    $tables_to_backup = [
        'users',
        'complaints',
        'categories',
        'complaint_attachments',
        'complaint_updates',
        'notifications',
        'password_resets',
        'trusted_devices',
        'two_fa_codes',
        'rate_limit',
        'activity_logs',
    ];

    $filename = 'cms_backup_' . date('Y-m-d_H-i-s') . '.sql';

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Output SQL header
    echo "-- ============================================\n";
    echo "-- CMS Database Backup\n";
    echo "-- Generated: " . date('Y-m-d H:i:s') . " (Asia/Manila)\n";
    echo "-- ============================================\n\n";
    echo "SET FOREIGN_KEY_CHECKS=0;\n";
    echo "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n";
    echo "SET NAMES utf8mb4;\n\n";

    foreach ($tables_to_backup as $table) {

        // Check if table exists
        $check = $conn->query("SHOW TABLES LIKE '$table'");
        if ($check->num_rows === 0) continue;

        echo "-- ============================================\n";
        echo "-- Table: $table\n";
        echo "-- ============================================\n\n";

        // DROP + CREATE TABLE
        $create_result = $conn->query("SHOW CREATE TABLE `$table`");
        if ($create_result) {
            $create_row = $create_result->fetch_row();
            echo "DROP TABLE IF EXISTS `$table`;\n";
            echo $create_row[1] . ";\n\n";
        }

        // INSERT DATA
        $data = $conn->query("SELECT * FROM `$table`");
        if ($data && $data->num_rows > 0) {
            // Get column names
            $fields      = $data->fetch_fields();
            $field_names = array_map(fn($f) => '`' . $f->name . '`', $fields);
            $columns     = implode(', ', $field_names);

            echo "INSERT INTO `$table` ($columns) VALUES\n";

            $rows  = [];
            $data->data_seek(0);
            while ($row = $data->fetch_row()) {
                $values = array_map(function ($val) use ($conn) {
                    if ($val === null) return 'NULL';
                    return "'" . $conn->real_escape_string($val) . "'";
                }, $row);
                $rows[] = '(' . implode(', ', $values) . ')';
            }
            echo implode(",\n", $rows) . ";\n\n";
        } else {
            echo "-- No data in $table\n\n";
        }
    }

    echo "SET FOREIGN_KEY_CHECKS=1;\n";
    echo "-- Backup complete.\n";

    // Log activity
    logActivity('backup_created', 'Database backup downloaded');

    exit();
}

// Generate CSRF token for download
$_SESSION['backup_token'] = bin2hex(random_bytes(16));

// Get table stats
$tables_info = [];
$tables_to_show = [
    'users'               => 'Users',
    'complaints'          => 'Complaints',
    'categories'          => 'Categories',
    'complaint_updates'   => 'Complaint Updates',
    'notifications'       => 'Notifications',
    'password_resets'     => 'Password Resets',
    'trusted_devices'     => 'Trusted Devices',
    'two_fa_codes'        => '2FA Codes',
    'rate_limit'          => 'Rate Limit',
    'activity_logs'       => 'Activity Logs',
];

foreach ($tables_to_show as $table => $label) {
    $check = $conn->query("SHOW TABLES LIKE '$table'");
    if ($check->num_rows > 0) {
        $count = $conn->query("SELECT COUNT(*) as c FROM `$table`")->fetch_assoc()['c'];
        $size  = $conn->query("
            SELECT ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024, 2) AS size_kb
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table'
        ")->fetch_assoc()['size_kb'];
        $tables_info[] = ['name' => $table, 'label' => $label, 'count' => $count, 'size' => $size ?? 0];
    }
}

// Get last backup log
$last_backup = $conn->query("
    SELECT created_at FROM activity_logs
    WHERE action = 'backup_created'
    ORDER BY created_at DESC LIMIT 1
")->fetch_assoc();

include '../includes/header.php';
include '../includes/navbar.php';
?>

<!-- Header Card -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1"><i class="bi bi-database-down me-2 text-primary"></i>Database Backup</h4>
                        <p class="text-muted mb-0">Export a complete backup of the system database as a <code>.sql</code> file.</p>
                    </div>
                    <div class="text-end">
                        <?php if ($last_backup): ?>
                        <small class="text-muted d-block">
                            <i class="bi bi-clock me-1"></i>
                            Last backup: <strong><?php echo formatDateTime($last_backup['created_at']); ?></strong>
                        </small>
                        <?php else: ?>
                        <small class="text-muted">No backups yet</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Table Stats -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-table me-2"></i> Tables to be Backed Up
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Table</th>
                                <th>Records</th>
                                <th>Size</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tables_info as $info): ?>
                            <tr>
                                <td>
                                    <i class="bi bi-table text-muted me-2"></i>
                                    <strong><?php echo $info['label']; ?></strong>
                                    <small class="text-muted ms-1">(<?php echo $info['name']; ?>)</small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark"><?php echo number_format($info['count']); ?> rows</span>
                                </td>
                                <td>
                                    <small class="text-muted"><?php echo $info['size']; ?> KB</small>
                                </td>
                                <td>
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle me-1"></i>Ready
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Download Button -->
<div class="row">
    <div class="col-md-6">
        <div class="card border-primary">
            <div class="card-body text-center py-4">
                <i class="bi bi-download text-primary" style="font-size: 3rem;"></i>
                <h5 class="mt-3">Download Backup</h5>
                <p class="text-muted">
                    Downloads a complete <code>.sql</code> file containing all table structures and data.
                    You can use this to restore the database if needed.
                </p>
                <a href="backup.php?download=1&token=<?php echo $_SESSION['backup_token']; ?>"
                   class="btn btn-primary btn-lg"
                   onclick="return confirm('Download database backup now?');">
                    <i class="bi bi-database-down me-2"></i>
                    Download Backup — <?php echo date('M d, Y'); ?>
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card bg-light border-0">
            <div class="card-body py-4">
                <h6><i class="bi bi-info-circle text-primary me-2"></i>How to Restore</h6>
                <ol class="text-muted small mb-0">
                    <li class="mb-2">Download the <code>.sql</code> backup file</li>
                    <li class="mb-2">Open your database management tool (phpMyAdmin, TablePlus, etc.)</li>
                    <li class="mb-2">Select your database</li>
                    <li class="mb-2">Click <strong>Import</strong> and select the <code>.sql</code> file</li>
                    <li>Click <strong>Execute</strong> — done!</li>
                </ol>
                <hr>
                <h6><i class="bi bi-shield-check text-success me-2"></i>Best Practices</h6>
                <ul class="text-muted small mb-0">
                    <li>Backup regularly — at least once a week</li>
                    <li>Store backups in a secure location</li>
                    <li>Test restoring occasionally</li>
                </ul>
            </div>
        </div>
    </div>
</div>

</div><!-- End page-content -->
</div><!-- End main-content -->

<?php include '../includes/footer.php'; ?>