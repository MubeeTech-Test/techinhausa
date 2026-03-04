<?php
// includes/auth.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

/**
 * Start session if not already started
 */
function ensureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    ensureSession();
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Require authentication - redirect to login if not authenticated
 */
function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/admin/login.php');
        exit();
    }
}

/**
 * Alias for requireAuth() - for compatibility with dashboard.php
 */
function require_admin_login() {
    requireAuth();
}

/**
 * Login user
 */
function login($username, $password) {
    global $conn;
    
    // Sanitize input
    $username = mysqli_real_escape_string($conn, $username);
    
    // Query user - REMOVED is_active condition
    $query = "SELECT * FROM users WHERE username = '$username' LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        error_log("Login query error: " . mysqli_error($conn));
        return ['success' => false, 'message' => 'Database error occurred'];
    }
    
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        // Verify password
        if (password_verify($password, $user['password_hash'])) {
            ensureSession();
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_name'] = $user['full_name'];
            $_SESSION['admin_role'] = $user['role'];
            
            // Update last login
            $update = "UPDATE users SET last_login = NOW() WHERE id = " . $user['id'];
            mysqli_query($conn, $update);
            
            return ['success' => true, 'message' => 'Login successful'];
        } else {
            return ['success' => false, 'message' => 'Invalid password'];
        }
    }
    
    return ['success' => false, 'message' => 'Username not found'];
}

/**
 * Logout user
 */
function logout() {
    ensureSession();
    $_SESSION = array();
    session_destroy();
}

/**
 * Get current user info
 */
function getCurrentUser() {
    ensureSession();
    
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['admin_user_id'] ?? null,
        'username' => $_SESSION['admin_username'] ?? null,
        'name' => $_SESSION['admin_name'] ?? null,
        'role' => $_SESSION['admin_role'] ?? null
    ];
}

/**
 * Alias for getCurrentUser() - for compatibility
 */
function get_current_admin($conn) {
    return getCurrentUser();
}

/**
 * Check if user has specific role
 */
function hasRole($role) {
    $user = getCurrentUser();
    return $user && $user['role'] === $role;
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return hasRole('admin');
}

/**
 * Generate password hash
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Create new user
 */
function createUser($username, $password, $email, $full_name, $role = 'editor') {
    global $conn;
    
    // Check if username exists
    $check = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
    if (mysqli_num_rows($check) > 0) {
        return ['success' => false, 'message' => 'Username already exists'];
    }
    
    // Check if email exists
    $check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
    if (mysqli_num_rows($check) > 0) {
        return ['success' => false, 'message' => 'Email already exists'];
    }
    
    $password_hash = hashPassword($password);
    $full_name = mysqli_real_escape_string($conn, $full_name);
    
    $query = "INSERT INTO users (username, password_hash, email, full_name, role) 
              VALUES ('$username', '$password_hash', '$email', '$full_name', '$role')";
    
    if (mysqli_query($conn, $query)) {
        return ['success' => true, 'message' => 'User created successfully', 'id' => mysqli_insert_id($conn)];
    } else {
        return ['success' => false, 'message' => 'Error creating user: ' . mysqli_error($conn)];
    }
}

/**
 * Time ago function for activity display
 */
function time_ago($datetime) {
    if (empty($datetime)) return 'N/A';
    
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return $diff . ' seconds ago';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 2592000) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $time);
    }
}

// If this file is accessed directly for password hashing
if (isset($_GET['hash']) && isset($_GET['password'])) {
    header('Content-Type: text/plain');
    echo "Password hash for '" . $_GET['password'] . "':\n";
    echo hashPassword($_GET['password']);
    exit;
}
?>