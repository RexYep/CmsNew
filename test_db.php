<?php
// Test database connection
require_once 'config/database.php';

echo "Testing database connection...\n";

if ($conn->connect_error) {
    echo "Database connection FAILED: " . $conn->connect_error . "\n";
} else {
    echo "Database connection SUCCESS\n";

    // Test if tables exist
    $tables = ['users', 'complaints', 'complaint_comments', 'notifications'];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            echo "Table '$table' exists\n";
        } else {
            echo "Table '$table' does NOT exist\n";
        }
    }

    // Test a simple query
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Users count: " . $row['count'] . "\n";
    } else {
        echo "Failed to query users table: " . $conn->error . "\n";
    }
}

$conn->close();
?>
