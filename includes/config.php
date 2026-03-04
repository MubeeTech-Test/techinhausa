<?php
// includes/config.php - Railway Optimized Version
session_start();

// ============================================
// RAILWAY ENVIRONMENT DETECTION
// ============================================
if (getenv('RAILWAY_ENVIRONMENT') || getenv('MYSQLHOST')) {
    // We're on Railway!
    define('DB_HOST', getenv('MYSQLHOST') ?: 'localhost');
    define('DB_NAME', getenv('MYSQLDATABASE') ?: 'railway');
    define('DB_USER', getenv('MYSQLUSER') ?: 'root');
    define('DB_PASS', getenv('MYSQLPASSWORD') ?: '');
    define('SITE_URL', 'https://' . getenv('RAILWAY_STATIC_URL') ?: 'techinhausa.up.railway.app');
    define('IS_PRODUCTION', true);
} else {
    // Local development
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'techinHausa');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    
    // Auto-detect local URL
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    define('SITE_URL', rtrim($protocol . $host . $base_dir, '/'));
    define('IS_PRODUCTION', false);
}

// ============================================
// COMMON CONFIGURATION (works everywhere)
// ============================================
define('SITE_NAME', 'TechInHausa');
define('SITE_DESC', 'Technology and AI content in Hausa language');

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
if (defined('IS_PRODUCTION') && IS_PRODUCTION) {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
}

// Make SITE_URL globally available
$GLOBALS['SITE_URL'] = SITE_URL;
?>