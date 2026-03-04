<?php
// admin/users.php - Manage Admin Users
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/functions.php";

// Get MySQLi connection
global $conn;

// Require admin login
require_admin_login();

// Only admin can manage users
if (!isAdmin()) {
    header("Location: dashboard.php?error=unauthorized");
    exit();
}

// Get counts for sidebar badges
$total_videos_draft = 0;
$count_videos = mysqli_query($conn, "SELECT COUNT(*) as count FROM videos WHERE is_published = 0");
if ($count_videos) {
    $total_videos_draft = mysqli_fetch_assoc($count_videos)['count'];
}

$total_blog_draft = 0;
$count_blog = mysqli_query($conn, "SELECT COUNT(*) as count FROM blog_posts WHERE is_published = 0");
if ($count_blog) {
    $total_blog_draft = mysqli_fetch_assoc($count_blog)['count'];
}

$total_news_draft = 0;
$count_news = mysqli_query($conn, "SELECT COUNT(*) as count FROM news WHERE is_published = 0");
if ($count_news) {
    $total_news_draft = mysqli_fetch_assoc($count_news)['count'];
}

$total_research_draft = 0;
$count_research = mysqli_query($conn, "SELECT COUNT(*) as count FROM research WHERE is_published = 0");
if ($count_research) {
    $total_research_draft = mysqli_fetch_assoc($count_research)['count'];
}

$total_creator_draft = 0;
$count_creator = mysqli_query($conn, "SELECT COUNT(*) as count FROM creator WHERE is_published = 0");
if ($count_creator) {
    $total_creator_draft = mysqli_fetch_assoc($count_creator)['count'];
}

$total_team_inactive = 0;
$count_team = mysqli_query($conn, "SELECT COUNT(*) as count FROM team_members WHERE is_active = 0");
if ($count_team) {
    $total_team_inactive = mysqli_fetch_assoc($count_team)['count'];
}

$unread_contacts = 0;
$count_contacts = mysqli_query($conn, "SELECT COUNT(*) as count FROM contact_submissions WHERE is_read = 0");
if ($count_contacts) {
    $unread_contacts = mysqli_fetch_assoc($count_contacts)['count'];
}

$total_subscribers = 0;
$count_subs = mysqli_query($conn, "SELECT COUNT(*) as count FROM subscribers");
if ($count_subs) {
    $total_subscribers = mysqli_fetch_assoc($count_subs)['count'];
}

// Handle actions
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$error = '';

// Handle Delete
if ($action === 'delete' && $id > 0) {
    // Don't allow deleting yourself
    $current_user = get_current_admin($conn);
    if ($current_user['id'] == $id) {
        $error = "You cannot delete your own account.";
    } else {
        $result = mysqli_query($conn, "DELETE FROM users WHERE id = $id");
        
        if ($result && mysqli_affected_rows($conn) > 0) {
            $message = "User deleted successfully.";
        } else {
            $error = "User not found or could not be deleted.";
        }
    }
    $action = 'list';
}

// Handle Add/Edit Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        // Get form data
        $username = mysqli_real_escape_string($conn, $_POST['username'] ?? '');
        $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
        $full_name = mysqli_real_escape_string($conn, $_POST['full_name'] ?? '');
        $role = mysqli_real_escape_string($conn, $_POST['role'] ?? 'editor');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validate required fields
        if (empty($username) || empty($email) || empty($full_name)) {
            $error = "Username, email, and full name are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } elseif ($action === 'add' && empty($password)) {
            $error = "Password is required for new users.";
        } elseif (!empty($password) && $password !== $confirm_password) {
            $error = "Passwords do not match.";
        } elseif (!empty($password) && strlen($password) < 6) {
            $error = "Password must be at least 6 characters long.";
        } else {
            
            if ($action === 'add') {
                // Check if username exists
                $check = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
                if (mysqli_num_rows($check) > 0) {
                    $error = "Username already exists.";
                }
                
                // Check if email exists
                $check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
                if (mysqli_num_rows($check) > 0) {
                    $error = "Email already exists.";
                }
                
                if (empty($error)) {
                    // Hash password
                    $password_hash = hashPassword($password);
                    
                    // Insert new user
                    $sql = "INSERT INTO users (username, password_hash, email, full_name, role, created_at) 
                            VALUES ('$username', '$password_hash', '$email', '$full_name', '$role', NOW())";
                    
                    $result = mysqli_query($conn, $sql);
                    
                    if ($result) {
                        $message = "User added successfully.";
                        header("Location: users.php?message=" . urlencode($message));
                        exit();
                    } else {
                        $error = "Database error: " . mysqli_error($conn);
                    }
                }
                
            } else {
                // Update existing user
                $check = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username' AND id != $id");
                if (mysqli_num_rows($check) > 0) {
                    $error = "Username already exists.";
                }
                
                $check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email' AND id != $id");
                if (mysqli_num_rows($check) > 0) {
                    $error = "Email already exists.";
                }
                
                if (empty($error)) {
                    if (!empty($password)) {
                        // Update with new password
                        $password_hash = hashPassword($password);
                        $sql = "UPDATE users SET 
                                username = '$username',
                                email = '$email',
                                full_name = '$full_name',
                                role = '$role',
                                password_hash = '$password_hash',
                                updated_at = NOW()
                                WHERE id = $id";
                    } else {
                        // Update without changing password
                        $sql = "UPDATE users SET 
                                username = '$username',
                                email = '$email',
                                full_name = '$full_name',
                                role = '$role',
                                updated_at = NOW()
                                WHERE id = $id";
                    }
                    
                    $result = mysqli_query($conn, $sql);
                    
                    if ($result) {
                        $message = "User updated successfully.";
                        header("Location: users.php?message=" . urlencode($message));
                        exit();
                    } else {
                        $error = "Database error: " . mysqli_error($conn);
                    }
                }
            }
        }
    }
}

// Get user data for editing
$user = null;
if ($action === 'edit' && $id > 0) {
    $result = mysqli_query($conn, "SELECT * FROM users WHERE id = $id");
    $user = mysqli_fetch_assoc($result);
    
    if (!$user) {
        $error = "User not found.";
        $action = 'list';
    }
}

// Get users list
$users = [];
if ($action === 'list') {
    $result = mysqli_query($conn, "SELECT id, username, email, full_name, role, last_login, created_at FROM users ORDER BY created_at DESC");
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
        }
    }
}

// Get message from redirect
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}

// Get current admin info
$current_admin = get_current_admin($conn);
$admin_name = $current_admin['name'] ?? $current_admin['username'] ?? 'Admin';
$admin_role = $current_admin['role'] ?? 'Administrator';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - TechInHausa Admin</title>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fb;
            color: #333;
        }
        
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar styles */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #031837 0%, #02122b 100%);
            color: white;
            padding: 2rem 1.5rem;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(211, 201, 254, 0.2);
        }
        
        .sidebar-header h2 {
            font-size: 1.5rem;
            color: #D3C9FE;
        }
        
        .sidebar-header p {
            font-size: 0.85rem;
            opacity: 0.8;
        }
        
        .admin-info {
            background: rgba(211, 201, 254, 0.1);
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .admin-name {
            font-weight: 600;
        }
        
        .admin-role {
            font-size: 0.85rem;
            background: #D3C9FE;
            color: #031837;
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            margin-top: 0.5rem;
        }
        
        .nav-menu {
            list-style: none;
        }
        
        .nav-item {
            margin-bottom: 0.5rem;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(211, 201, 254, 0.15);
            color: white;
            transform: translateX(5px);
        }
        
        .nav-link i {
            color: #D3C9FE;
        }
        
        .nav-badge {
            background: #D3C9FE;
            color: #031837;
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 20px;
            margin-left: auto;
            font-weight: 600;
        }
        
        .nav-section {
            margin: 1.5rem 0 0.5rem;
            font-size: 0.7rem;
            text-transform: uppercase;
            opacity: 0.5;
            color: #D3C9FE;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
        }
        
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background: white;
            padding: 1rem 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .page-title h1 {
            font-size: 1.8rem;
            color: #031837;
        }
        
        .page-title p {
            color: #666;
            font-size: 0.9rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background: #031837;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0a2a4a;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #000;
        }
        
        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
        }
        
        .message {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border-left: 4px solid;
        }
        
        .message.success {
            border-left-color: #28a745;
            background: #d4edda;
            color: #155724;
        }
        
        .message.error {
            border-left-color: #dc3545;
            background: #f8d7da;
            color: #721c24;
        }
        
        .message.warning {
            border-left-color: #ffc107;
            background: #fff3cd;
            color: #856404;
        }
        
        /* Table Styles */
        .table-container {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            text-align: left;
            padding: 1rem 0.75rem;
            color: #666;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        td {
            padding: 1rem 0.75rem;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }
        
        tr:hover td {
            background: #f8f9fa;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #031837 0%, #0a2a4a 100%);
            color: #D3C9FE;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .user-name {
            font-weight: 600;
            color: #031837;
        }
        
        .user-email {
            font-size: 0.8rem;
            color: #666;
            margin-top: 0.25rem;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-info {
            background: #cce5ff;
            color: #004085;
        }
        
        .badge-primary {
            background: #D3C9FE;
            color: #031837;
        }
        
        .badge-admin {
            background: #031837;
            color: #D3C9FE;
        }
        
        .badge-editor {
            background: #6c757d;
            color: white;
        }
        
        .action-group {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .action-icon {
            color: #666;
            font-size: 1.1rem;
            transition: all 0.3s;
        }
        
        .action-icon:hover {
            color: #031837;
            transform: scale(1.1);
        }
        
        .action-icon.delete:hover {
            color: #dc3545;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #999;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #ddd;
        }
        
        /* Form Styles */
        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            max-width: 600px;
            margin: 0 auto;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }
        
        .form-group label i {
            color: #D3C9FE;
            margin-right: 0.5rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #D3C9FE;
            box-shadow: 0 0 0 3px rgba(211, 201, 254, 0.2);
        }
        
        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1em;
        }
        
        .form-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }
        
        .password-hint {
            font-size: 0.8rem;
            color: #666;
            margin-top: 0.25rem;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #e0e0e0;
        }
        
        .current-user-badge {
            background: #f0f0f0;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            margin-left: 8px;
        }
        
        @media (max-width: 1024px) {
            .sidebar {
                width: 80px;
                padding: 1rem 0.5rem;
            }
            
            .sidebar-header h2, .sidebar-header p, .admin-info .admin-name, .admin-role, .nav-link span {
                display: none;
            }
            
            .nav-link {
                justify-content: center;
                padding: 0.75rem;
            }
            
            .main-content {
                margin-left: 80px;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .top-bar {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .form-row {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>TechInHausa</h2>
                <p>Admin Panel</p>
            </div>
            
            <div class="admin-info">
                <div class="admin-name"><?php echo htmlspecialchars($admin_name); ?></div>
                <div class="admin-role"><?php echo htmlspecialchars($admin_role); ?></div>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-section">Main</li>
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                <li class="nav-section">Content</li>
                <li class="nav-item">
                    <a href="videos.php" class="nav-link">
                        <i class="fas fa-play-circle"></i>
                        <span>Videos</span>
                        <?php if ($total_videos_draft > 0): ?>
                        <span class="nav-badge"><?php echo $total_videos_draft; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="blog.php" class="nav-link">
                        <i class="fas fa-blog"></i>
                        <span>Blog Posts</span>
                        <?php if ($total_blog_draft > 0): ?>
                        <span class="nav-badge"><?php echo $total_blog_draft; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="news.php" class="nav-link">
                        <i class="fas fa-newspaper"></i>
                        <span>News</span>
                        <?php if ($total_news_draft > 0): ?>
                        <span class="nav-badge"><?php echo $total_news_draft; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="research.php" class="nav-link">
                        <i class="fas fa-flask"></i>
                        <span>Research</span>
                        <?php if ($total_research_draft > 0): ?>
                        <span class="nav-badge"><?php echo $total_research_draft; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="creator.php" class="nav-link">
                        <i class="fas fa-user-graduate"></i>
                        <span>MalamIromba</span>
                        <?php if ($total_creator_draft > 0): ?>
                        <span class="nav-badge"><?php echo $total_creator_draft; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                
                <li class="nav-section">Organization</li>
                <li class="nav-item">
                    <a href="categories.php" class="nav-link">
                        <i class="fas fa-folder"></i>
                        <span>Categories</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="tags.php" class="nav-link">
                        <i class="fas fa-tags"></i>
                        <span>Tags</span>
                    </a>
                </li>
                
                <li class="nav-section">Team & People</li>
                <li class="nav-item">
                    <a href="team_members.php" class="nav-link">
                        <i class="fas fa-users"></i>
                        <span>Team Members</span>
                        <?php if ($total_team_inactive > 0): ?>
                        <span class="nav-badge"><?php echo $total_team_inactive; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                
                <li class="nav-section">Features</li>
                <li class="nav-item">
                    <a href="media_features.php" class="nav-link">
                        <i class="fas fa-trophy"></i>
                        <span>Media Features</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="sponsors.php" class="nav-link">
                        <i class="fas fa-handshake"></i>
                        <span>Sponsors</span>
                    </a>
                </li>
                
                <li class="nav-section">Communications</li>
                <li class="nav-item">
                    <a href="contact_submissions.php" class="nav-link">
                        <i class="fas fa-envelope"></i>
                        <span>Contact Messages</span>
                        <?php if ($unread_contacts > 0): ?>
                        <span class="nav-badge"><?php echo $unread_contacts; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="subscribers.php" class="nav-link">
                        <i class="fas fa-envelope-open-text"></i>
                        <span>Newsletter</span>
                        <span class="nav-badge"><?php echo $total_subscribers; ?></span>
                    </a>
                </li>
                
                <li class="nav-section">System</li>
                <li class="nav-item">
                    <a href="users.php" class="nav-link active">
                        <i class="fas fa-users-cog"></i>
                        <span>Admin Users</span>
                    </a>
                </li>
                
                <li class="nav-section">Account</li>
                <li class="nav-item">
                    <a href="profile.php" class="nav-link">
                        <i class="fas fa-user-circle"></i>
                        <span>My Profile</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link" style="color: #D3C9FE;">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="page-title">
                    <h1>
                        <?php if ($action === 'add'): ?>
                            <i class="fas fa-user-plus"></i> Add New User
                        <?php elseif ($action === 'edit'): ?>
                            <i class="fas fa-user-edit"></i> Edit User
                        <?php else: ?>
                            <i class="fas fa-users-cog"></i> Manage Users
                        <?php endif; ?>
                    </h1>
                    <p>
                        <?php if ($action === 'list'): ?>
                            <?php echo count($users); ?> total users
                        <?php else: ?>
                            Fill in the user details below
                        <?php endif; ?>
                    </p>
                </div>
                
                <div class="action-buttons">
                    <?php if ($action === 'list'): ?>
                        <a href="?action=add" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Add New User
                        </a>
                    <?php else: ?>
                        <a href="users.php" class="btn btn-warning">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Messages -->
            <?php if ($message): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <!-- Content Area -->
            <?php if ($action === 'list'): ?>
                <!-- Users List -->
                <div class="table-container">
                    <?php if (empty($users)): ?>
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <h3>No Users Found</h3>
                            <p>Get started by adding your first user.</p>
                            <a href="?action=add" class="btn btn-primary" style="margin-top: 1rem;">
                                <i class="fas fa-user-plus"></i> Add New User
                            </a>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Last Login</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div class="user-avatar">
                                                <?php echo strtoupper(substr($user['full_name'] ?? $user['username'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div class="user-name">
                                                    <?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?>
                                                    <?php if ($current_admin['id'] == $user['id']): ?>
                                                        <span class="current-user-badge">(You)</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($user['email']); ?></div>
                                    </td>
                                    <td>
                                        <?php if ($user['role'] === 'admin'): ?>
                                            <span class="badge badge-admin">Admin</span>
                                        <?php else: ?>
                                            <span class="badge badge-editor">Editor</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($user['last_login'])): ?>
                                            <div><?php echo date('M j, Y', strtotime($user['last_login'])); ?></div>
                                            <small style="color: #999;"><?php echo time_ago($user['last_login']); ?></small>
                                        <?php else: ?>
                                            <span style="color: #999;">Never</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div><?php echo date('M j, Y', strtotime($user['created_at'])); ?></div>
                                    </td>
                                    <td>
                                        <div class="action-group">
                                            <a href="?action=edit&id=<?php echo $user['id']; ?>" 
                                               class="action-icon" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($current_admin['id'] != $user['id']): ?>
                                                <a href="?action=delete&id=<?php echo $user['id']; ?>" 
                                                   class="action-icon delete" title="Delete"
                                                   onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                
            <?php elseif ($action === 'add' || $action === 'edit'): ?>
                <!-- Add/Edit User Form -->
                <div class="form-container">
                    <form method="POST">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Full Name *</label>
                            <input type="text" name="full_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-at"></i> Username *</label>
                                <input type="text" name="username" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-envelope"></i> Email *</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-key"></i> Password <?php echo $action === 'edit' ? '(Leave blank to keep current)' : '*'; ?></label>
                                <input type="password" name="password" class="form-control" 
                                       <?php echo $action === 'add' ? 'required' : ''; ?>>
                                <?php if ($action === 'add'): ?>
                                    <div class="password-hint">Minimum 6 characters</div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-key"></i> Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control"
                                       <?php echo $action === 'add' ? 'required' : ''; ?>>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-shield-alt"></i> Role</label>
                            <select name="role" class="form-control">
                                <option value="editor" <?php echo (isset($user['role']) && $user['role'] === 'editor') ? 'selected' : ''; ?>>Editor</option>
                                <option value="admin" <?php echo (isset($user['role']) && $user['role'] === 'admin') ? 'selected' : ''; ?>>Administrator</option>
                            </select>
                            <small style="color: #666;">Administrators have full access, Editors can manage content only</small>
                        </div>
                        
                        <div class="form-actions">
                            <a href="users.php" class="btn btn-warning">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> 
                                <?php echo $action === 'add' ? 'Create User' : 'Update User'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>