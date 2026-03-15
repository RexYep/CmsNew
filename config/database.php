<?php

// DATABASE CONNECTION CONFIGURATION
// config/database.php


$host = getenv('DATABASE_HOST') ?: 'localhost';
$username = getenv('DATABASE_USER') ?: '';
$password = getenv('DATABASE_PASSWORD') ?: '';
$dbname   = getenv('DATABASE_NAME') ?: '';
$port     = (int)(getenv('DATABASE_PORT') ?: 0);

$is_production = getenv('DATABASE_HOST') !== false;

if ($is_production) {
  
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
    // LOCAL DEVELOPMENT: Standard MySQL (XAMPP)
   
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