<?php
// includes/db.php - Simplified
require_once __DIR__ . '/config.php';

// Create MySQLi connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection with environment-aware error handling
if (!$conn) {
    error_log("Database connection failed: " . mysqli_connect_error());
    
    // Show different messages based on environment
    if (strpos(SITE_URL, 'localhost') !== false || strpos(SITE_URL, '127.0.0.1') !== false) {
        die("Connection failed: " . mysqli_connect_error());
    } else {
        die("We're experiencing technical difficulties. Please try again later.");
    }
}

// Set charset
mysqli_set_charset($conn, "utf8mb4");

// Make sure SITE_URL is in GLOBALS
$GLOBALS['SITE_URL'] = SITE_URL;
?>