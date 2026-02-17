<?php
// ============================================
// DATABASE CONNECTION CONFIGURATION
// config/database.php
// ============================================

// Database Configuration
// Uses environment variables in production (Render + Aiven)
// Falls back to local values for development (XAMPP)

$host     = getenv('DATABASE_HOST') ?: 'localhost';
$username = getenv('DATABASE_USER') ?: 'root';
$password = getenv('DATABASE_PASSWORD') ?: 'Rynld.21';
$dbname   = getenv('DATABASE_NAME') ?: 'complaint_management_system';
$port     = (int)(getenv('DATABASE_PORT') ?: 3306);

// Detect if running in production (Render)
$is_production = getenv('DATABASE_HOST') !== false;

if ($is_production) {
    // ============================================
    // PRODUCTION: Aiven MySQL with SSL
    // ============================================
    $conn = mysqli_init();

    if (!$conn) {
        die("mysqli_init failed");
    }

    // Enable SSL (required for Aiven)
    mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);

    $connected = mysqli_real_connect(
        $conn,
        $host,
        $username,
        $password,
        $dbname,
        $port,
        NULL,
        MYSQLI_CLIENT_SSL
    );

    if (!$connected) {
        die("Connection failed: " . mysqli_connect_error());
    }

} else {
    // ============================================
    // LOCAL DEVELOPMENT: Standard MySQL (XAMPP)
    // ============================================
    $conn = new mysqli($host, $username, $password, $dbname, $port);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
}

// Set charset for proper character support
$conn->set_charset("utf8mb4");

// Set timezone
date_default_timezone_set('Asia/Manila');

// Function to close database connection
function closeConnection() {
    global $conn;
    if ($conn) {
        $conn->close();
    }
}
?>