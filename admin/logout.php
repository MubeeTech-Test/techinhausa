<?php
// admin/logout.php
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/auth.php";

// Logout the admin using the correct function name
logout();  // Changed from logout_admin() to logout()

// Redirect to login page
header('Location: login.php');
exit();