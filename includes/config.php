<?php
// includes/config.php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'techinHausa');
define('DB_USER', 'root');
define('DB_PASS', '');

// Dynamically detect the base URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$script_name = $_SERVER['SCRIPT_NAME'];

// Get the base directory (remove file name and /admin if present)
$base_dir = str_replace('\\', '/', dirname($script_name));
if (strpos($base_dir, '/admin') !== false) {
    $base_dir = dirname($base_dir); // Remove /admin from path
}

// Define SITE_URL dynamically
define('SITE_URL', rtrim($protocol . $host . $base_dir, '/'));
define('SITE_NAME', 'TechInHausa');
define('SITE_DESC', 'Technology and AI content in Hausa language');

// Make SITE_URL available globally
$GLOBALS['SITE_URL'] = SITE_URL;

// Paths
define('ROOT_PATH', dirname(__DIR__) . '/');
define('INCLUDES_PATH', ROOT_PATH . 'includes/');
define('PARTIALS_PATH', ROOT_PATH . 'partials/');
define('ADMIN_PATH', ROOT_PATH . 'admin/');

// Upload configuration
define('UPLOAD_DIR', ROOT_PATH . 'uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB

// Timezone
date_default_timezone_set('Africa/Lagos');

// Error reporting - smart detection
if ($host === 'localhost' || strpos($host, '127.0.0.1') !== false || strpos($host, '::1') !== false) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}
?>