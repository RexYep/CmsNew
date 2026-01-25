<?php
// ============================================
// DATABASE CONNECTION CONFIGURATION
// config/database.php
// ============================================

// Database Configuration Constants
define('DB_HOST', 'localhost');      // Database host
define('DB_USER', 'root');           // Database username
define('DB_PASS', 'Rynld.21');               // Database password (empty for XAMPP default)
define('DB_NAME', 'complaint_management_system');  // Database name

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4 for proper character support
$conn->set_charset("utf8mb4");

// Function to close database connection
function closeConnection() {
    global $conn;
    if ($conn) {
        $conn->close();
    }
}

// Optional: Set timezone
date_default_timezone_set('Asia/Manila'); // Change to your timezone

?>