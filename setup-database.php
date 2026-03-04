<?php
// setup-database.php - RUN ONCE THEN DELETE!
require_once 'includes/config.php';
require_once 'includes/db.php';

echo "<h1>Database Setup</h1>";

// Check if database is already populated
$check = mysqli_query($conn, "SHOW TABLES");
if ($check && mysqli_num_rows($check) > 0) {
    echo "<p style='color:orange'>Database already has tables. Skipping import.</p>";
    echo "<p><a href='index.php'>Go to homepage</a></p>";
    exit;
}

// Read SQL file
$sql = file_get_contents('database.sql');
if (!$sql) {
    die("<p style='color:red'>Could not read database.sql file</p>");
}

// Execute multi-query
if (mysqli_multi_query($conn, $sql)) {
    echo "<p style='color:green'>✓ Database imported successfully!</p>";
    
    // Clear any pending results
    while (mysqli_next_result($conn)) {;}
} else {
    echo "<p style='color:red'>Error: " . mysqli_error($conn) . "</p>";
}

echo "<p><a href='index.php'>Go to homepage</a></p>";
echo "<p><strong style='color:red'>IMPORTANT: Delete this file now!</strong></p>";
?>