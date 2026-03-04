<?php
// admin/subscribers.php - Manage Newsletter Subscribers
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/functions.php";

// Get MySQLi connection
global $conn;

// Require admin login
require_admin_login();

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
    $result = mysqli_query($conn, "DELETE FROM subscribers WHERE id = $id");
    
    if ($result && mysqli_affected_rows($conn) > 0) {
        $message = "Subscriber deleted successfully.";
    } else {
        $error = "Subscriber not found or could not be deleted.";
    }
    $action = 'list';
}

// Handle Toggle Active
if ($action === 'toggle_active' && $id > 0) {
    $result = mysqli_query($conn, "UPDATE subscribers SET is_active = NOT is_active WHERE id = $id");
    if ($result) {
        $message = "Subscriber status updated.";
    }
    $action = 'list';
}

// Handle Export to CSV
if ($action === 'export') {
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=subscribers_' . date('Y-m-d') . '.csv');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Add headers
    fputcsv($output, ['ID', 'Email', 'Name', 'Subscribed Date', 'Status']);
    
    // Get all subscribers
    $result = mysqli_query($conn, "SELECT * FROM subscribers ORDER BY subscribed_at DESC");
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            fputcsv($output, [
                $row['id'],
                $row['email'],
                $row['name'] ?? '',
                date('Y-m-d H:i:s', strtotime($row['subscribed_at'])),
                $row['is_active'] ? 'Active' : 'Inactive'
            ]);
        }
    }
    
    fclose($output);
    exit();
}

// Handle Bulk Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $bulk_action = $_POST['bulk_action'];
    $selected_ids = isset($_POST['selected_ids']) ? $_POST['selected_ids'] : [];
    
    if (empty($selected_ids)) {
        $error = "No subscribers selected.";
    } else {
        $ids = implode(',', array_map('intval', $selected_ids));
        
        if ($bulk_action === 'activate') {
            mysqli_query($conn, "UPDATE subscribers SET is_active = 1 WHERE id IN ($ids)");
            $message = "Selected subscribers activated.";
        } elseif ($bulk_action === 'deactivate') {
            mysqli_query($conn, "UPDATE subscribers SET is_active = 0 WHERE id IN ($ids)");
            $message = "Selected subscribers deactivated.";
        } elseif ($bulk_action === 'delete') {
            // Confirm with JavaScript first
            mysqli_query($conn, "DELETE FROM subscribers WHERE id IN ($ids)");
            $message = "Selected subscribers deleted.";
        }
    }
}

// Handle Add Subscriber Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subscriber'])) {
    $email = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
    $name = mysqli_real_escape_string($conn, trim($_POST['name'] ?? ''));
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($email)) {
        $error = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Check if email already exists
        $check = mysqli_query($conn, "SELECT id FROM subscribers WHERE email = '$email'");
        if (mysqli_num_rows($check) > 0) {
            $error = "Email already exists in subscribers list.";
        } else {
            $sql = "INSERT INTO subscribers (email, name, is_active, subscribed_at) 
                    VALUES ('$email', " . ($name ? "'$name'" : "NULL") . ", $is_active, NOW())";
            
            if (mysqli_query($conn, $sql)) {
                $message = "Subscriber added successfully.";
            } else {
                $error = "Database error: " . mysqli_error($conn);
            }
        }
    }
}

// Get filter parameters
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query conditions
$where_conditions = [];
if ($filter_status === 'active') {
    $where_conditions[] = "is_active = 1";
} elseif ($filter_status === 'inactive') {
    $where_conditions[] = "is_active = 0";
}

if (!empty($search)) {
    $search = mysqli_real_escape_string($conn, $search);
    $where_conditions[] = "(email LIKE '%$search%' OR name LIKE '%$search%')";
}

$where_sql = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get subscribers with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM subscribers $where_sql";
$count_result = mysqli_query($conn, $count_query);
$total_subscribers_paged = $count_result ? mysqli_fetch_assoc($count_result)['total'] : 0;
$total_pages = ceil($total_subscribers_paged / $limit);

// Get subscribers for current page
$query = "SELECT * FROM subscribers $where_sql ORDER BY subscribed_at DESC LIMIT $offset, $limit";
$result = mysqli_query($conn, $query);

$subscribers = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $subscribers[] = $row;
    }
}

// Get statistics
$stats = [];
$stats['total'] = $total_subscribers;
$stats['active'] = 0;
$stats['inactive'] = 0;
$stats['today'] = 0;
$stats['this_week'] = 0;
$stats['this_month'] = 0;

$active_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM subscribers WHERE is_active = 1");
if ($active_query) {
    $stats['active'] = mysqli_fetch_assoc($active_query)['count'];
}

$inactive_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM subscribers WHERE is_active = 0");
if ($inactive_query) {
    $stats['inactive'] = mysqli_fetch_assoc($inactive_query)['count'];
}

$today_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM subscribers WHERE DATE(subscribed_at) = CURDATE()");
if ($today_query) {
    $stats['today'] = mysqli_fetch_assoc($today_query)['count'];
}

$week_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM subscribers WHERE YEARWEEK(subscribed_at) = YEARWEEK(NOW())");
if ($week_query) {
    $stats['this_week'] = mysqli_fetch_assoc($week_query)['count'];
}

$month_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM subscribers WHERE MONTH(subscribed_at) = MONTH(NOW()) AND YEAR(subscribed_at) = YEAR(NOW())");
if ($month_query) {
    $stats['this_month'] = mysqli_fetch_assoc($month_query)['count'];
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
    <title>Manage Subscribers - TechInHausa Admin</title>
    
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
        
        .btn-info {
            background: #17a2b8;
            color: white;
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
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            background: rgba(211, 201, 254, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #031837;
        }
        
        .stat-content h3 {
            font-size: 1.8rem;
            color: #031837;
            line-height: 1.2;
        }
        
        .stat-content p {
            color: #666;
            font-size: 0.9rem;
        }
        
        /* Filter Bar */
        .filter-bar {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .filter-tabs {
            display: flex;
            gap: 0.5rem;
        }
        
        .filter-tab {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            background: #f0f0f0;
            color: #666;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        
        .filter-tab:hover,
        .filter-tab.active {
            background: #031837;
            color: white;
        }
        
        .search-box {
            display: flex;
            gap: 0.5rem;
        }
        
        .search-box input {
            padding: 0.5rem 1rem;
            border: 1px solid #e0e0e0;
            border-radius: 20px;
            width: 250px;
            font-size: 0.9rem;
        }
        
        .search-box button {
            padding: 0.5rem 1rem;
            background: #031837;
            color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
        }
        
        /* Table Styles */
        .table-container {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            overflow-x: auto;
        }
        
        .bulk-actions {
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        .bulk-actions select {
            padding: 0.5rem;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
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
        
        .subscriber-email {
            font-weight: 600;
            color: #031837;
        }
        
        .subscriber-name {
            color: #666;
            font-size: 0.9rem;
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
        
        .badge-secondary {
            background: #e2e3e5;
            color: #383d41;
        }
        
        .action-group {
            display: flex;
            gap: 0.5rem;
        }
        
        .action-icon {
            color: #666;
            font-size: 1rem;
            transition: all 0.3s;
            padding: 0.25rem;
        }
        
        .action-icon:hover {
            color: #031837;
            transform: scale(1.1);
        }
        
        .action-icon.delete:hover {
            color: #dc3545;
        }
        
        .checkbox-col {
            width: 40px;
            text-align: center;
        }
        
        .checkbox-col input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #031837;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }
        
        .page-link {
            padding: 0.5rem 0.75rem;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            color: #666;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .page-link:hover,
        .page-link.active {
            background: #031837;
            color: white;
            border-color: #031837;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .modal-header h2 {
            color: #031837;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }
        
        .modal-close:hover {
            color: #dc3545;
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
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #D3C9FE;
            box-shadow: 0 0 0 3px rgba(211, 201, 254, 0.2);
        }
        
        .form-check {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-check input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #031837;
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
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
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
            
            .filter-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box {
                width: 100%;
            }
            
            .search-box input {
                flex: 1;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
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
                    <a href="subscribers.php" class="nav-link active">
                        <i class="fas fa-envelope-open-text"></i>
                        <span>Newsletter</span>
                        <span class="nav-badge"><?php echo $total_subscribers; ?></span>
                    </a>
                </li>
                
                <li class="nav-section">Users</li>
                <li class="nav-item">
                    <a href="users.php" class="nav-link">
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
                    <h1><i class="fas fa-envelope-open-text"></i> Manage Subscribers</h1>
                    <p><?php echo $stats['total']; ?> total subscribers</p>
                </div>
                
                <div class="action-buttons">
                    <button class="btn btn-success" onclick="openAddModal()">
                        <i class="fas fa-plus"></i> Add Subscriber
                    </button>
                    <a href="?action=export" class="btn btn-info">
                        <i class="fas fa-download"></i> Export CSV
                    </a>
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
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total']; ?></h3>
                        <p>Total Subscribers</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['active']; ?></h3>
                        <p>Active</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-slash"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['inactive']; ?></h3>
                        <p>Inactive</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['today']; ?></h3>
                        <p>Today</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-week"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['this_week']; ?></h3>
                        <p>This Week</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['this_month']; ?></h3>
                        <p>This Month</p>
                    </div>
                </div>
            </div>
            
            <!-- Filter Bar -->
            <div class="filter-bar">
                <div class="filter-tabs">
                    <a href="?status=all<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                       class="filter-tab <?php echo $filter_status === 'all' ? 'active' : ''; ?>">All</a>
                    <a href="?status=active<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                       class="filter-tab <?php echo $filter_status === 'active' ? 'active' : ''; ?>">Active</a>
                    <a href="?status=inactive<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                       class="filter-tab <?php echo $filter_status === 'inactive' ? 'active' : ''; ?>">Inactive</a>
                </div>
                
                <form method="GET" class="search-box">
                    <input type="hidden" name="status" value="<?php echo $filter_status; ?>">
                    <input type="text" name="search" placeholder="Search by email or name..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>
            
            <!-- Subscribers Table -->
            <div class="table-container">
                <form method="POST" id="bulkActionForm">
                    <div class="bulk-actions">
                        <input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)">
                        <label for="selectAll">Select All</label>
                        
                        <select name="bulk_action" id="bulkAction">
                            <option value="">Bulk Actions</option>
                            <option value="activate">Activate</option>
                            <option value="deactivate">Deactivate</option>
                            <option value="delete">Delete</option>
                        </select>
                        
                        <button type="submit" class="btn btn-primary btn-sm" onclick="return confirmBulkAction()">
                            Apply
                        </button>
                    </div>
                    
                    <?php if (empty($subscribers)): ?>
                        <div class="empty-state">
                            <i class="fas fa-envelope-open-text"></i>
                            <h3>No Subscribers Found</h3>
                            <p>
                                <?php if (!empty($search)): ?>
                                    No subscribers matching "<?php echo htmlspecialchars($search); ?>"
                                <?php else: ?>
                                    Get started by adding your first subscriber.
                                <?php endif; ?>
                            </p>
                            <?php if (!empty($search)): ?>
                                <a href="subscribers.php" class="btn btn-primary" style="margin-top: 1rem;">
                                    Clear Search
                                </a>
                            <?php else: ?>
                                <button class="btn btn-primary" style="margin-top: 1rem;" onclick="openAddModal()">
                                    <i class="fas fa-plus"></i> Add Subscriber
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th class="checkbox-col">
                                        <input type="checkbox" id="selectAllHeader" onclick="toggleSelectAll(this)">
                                    </th>
                                    <th>Email</th>
                                    <th>Name</th>
                                    <th>Subscribed</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subscribers as $subscriber): ?>
                                <tr>
                                    <td class="checkbox-col">
                                        <input type="checkbox" name="selected_ids[]" value="<?php echo $subscriber['id']; ?>" 
                                               class="subscriber-checkbox">
                                    </td>
                                    <td>
                                        <div class="subscriber-email"><?php echo htmlspecialchars($subscriber['email']); ?></div>
                                    </td>
                                    <td>
                                        <?php if (!empty($subscriber['name'])): ?>
                                            <div class="subscriber-name"><?php echo htmlspecialchars($subscriber['name']); ?></div>
                                        <?php else: ?>
                                            <span style="color: #999;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div><?php echo date('M j, Y', strtotime($subscriber['subscribed_at'])); ?></div>
                                        <small style="color: #999;"><?php echo time_ago($subscriber['subscribed_at']); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($subscriber['is_active']): ?>
                                            <span class="badge badge-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-group">
                                            <a href="?action=toggle_active&id=<?php echo $subscriber['id']; ?>" 
                                               class="action-icon" 
                                               title="<?php echo $subscriber['is_active'] ? 'Deactivate' : 'Activate'; ?>"
                                               onclick="return confirm('Toggle status for <?php echo htmlspecialchars(addslashes($subscriber['email'])); ?>?')">
                                                <i class="fas <?php echo $subscriber['is_active'] ? 'fa-toggle-on' : 'fa-toggle-off'; ?>" 
                                                   style="color: <?php echo $subscriber['is_active'] ? '#28a745' : '#666'; ?>"></i>
                                            </a>
                                            <a href="?action=delete&id=<?php echo $subscriber['id']; ?>" 
                                               class="action-icon delete" 
                                               title="Delete"
                                               onclick="return confirm('Are you sure you want to delete <?php echo htmlspecialchars(addslashes($subscriber['email'])); ?>? This action cannot be undone.')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search); ?>" 
                                       class="page-link">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <?php if ($i >= $page - 2 && $i <= $page + 2): ?>
                                        <a href="?page=<?php echo $i; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search); ?>" 
                                           class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search); ?>" 
                                       class="page-link">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </form>
            </div>
        </main>
    </div>
    
    <!-- Add Subscriber Modal -->
    <div class="modal" id="addModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-plus-circle"></i> Add New Subscriber</h2>
                <button class="modal-close" onclick="closeAddModal()">&times;</button>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email *</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Name (Optional)</label>
                    <input type="text" name="name" class="form-control">
                </div>
                
                <div class="form-group">
                    <label class="form-check">
                        <input type="checkbox" name="is_active" checked>
                        <span>Active (receive newsletters)</span>
                    </label>
                </div>
                
                <div class="form-actions" style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" class="btn btn-warning" onclick="closeAddModal()">Cancel</button>
                    <button type="submit" name="add_subscriber" class="btn btn-primary">
                        <i class="fas fa-save"></i> Add Subscriber
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Toggle select all checkboxes
        function toggleSelectAll(source) {
            const checkboxes = document.querySelectorAll('.subscriber-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = source.checked;
            });
            
            // Sync both select all checkboxes
            document.getElementById('selectAll').checked = source.checked;
            document.getElementById('selectAllHeader').checked = source.checked;
        }
        
        // Confirm bulk action
        function confirmBulkAction() {
            const bulkAction = document.getElementById('bulkAction').value;
            const selectedCount = document.querySelectorAll('.subscriber-checkbox:checked').length;
            
            if (selectedCount === 0) {
                alert('Please select at least one subscriber.');
                return false;
            }
            
            if (bulkAction === 'delete') {
                return confirm(`Are you sure you want to delete ${selectedCount} subscriber(s)? This action cannot be undone.`);
            } else if (bulkAction === 'activate' || bulkAction === 'deactivate') {
                return confirm(`Are you sure you want to ${bulkAction} ${selectedCount} subscriber(s)?`);
            } else if (bulkAction === '') {
                alert('Please select an action.');
                return false;
            }
            
            return true;
        }
        
        // Modal functions
        function openAddModal() {
            document.getElementById('addModal').classList.add('active');
        }
        
        function closeAddModal() {
            document.getElementById('addModal').classList.remove('active');
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('addModal');
            if (event.target === modal) {
                modal.classList.remove('active');
            }
        }
        
        // Sync select all checkboxes
        document.querySelectorAll('.subscriber-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const allCheckboxes = document.querySelectorAll('.subscriber-checkbox');
                const allChecked = Array.from(allCheckboxes).every(cb => cb.checked);
                document.getElementById('selectAll').checked = allChecked;
                document.getElementById('selectAllHeader').checked = allChecked;
            });
        });
    </script>
</body>
</html>