<?php
// admin/contact_submissions.php - Manage Contact Submissions
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

$message = '';
$error = '';

// Handle marking as read/replied
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $id = (int)$_GET['mark_read'];
    $result = mysqli_query($conn, "UPDATE contact_submissions SET is_read = 1 WHERE id = $id");
    if ($result) {
        $message = "Message marked as read.";
    }
}

if (isset($_GET['mark_replied']) && is_numeric($_GET['mark_replied'])) {
    $id = (int)$_GET['mark_replied'];
    $result = mysqli_query($conn, "UPDATE contact_submissions SET is_replied = 1, replied_at = NOW(), replied_by = {$_SESSION['admin_user_id']} WHERE id = $id");
    if ($result) {
        $message = "Message marked as replied.";
    }
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $result = mysqli_query($conn, "DELETE FROM contact_submissions WHERE id = $id");
    if ($result) {
        $message = "Message deleted successfully.";
    }
}

// Get filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$where = "1=1";
switch ($filter) {
    case 'unread':
        $where = "is_read = 0";
        break;
    case 'read':
        $where = "is_read = 1";
        break;
    case 'replied':
        $where = "is_replied = 1";
        break;
}

// Get submissions
$submissions = mysqli_query($conn, "
    SELECT c.*, u.username as replied_by_name 
    FROM contact_submissions c
    LEFT JOIN users u ON c.replied_by = u.id
    WHERE $where
    ORDER BY c.created_at DESC
");

// Get counts
$total_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM contact_submissions"))['count'];
$unread_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM contact_submissions WHERE is_read = 0"))['count'];
$replied_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM contact_submissions WHERE is_replied = 1"))['count'];

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
    <title>Contact Messages - TechInHausa Admin</title>
    
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
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #031837 0%, #0a2a4a 100%);
            color: #D3C9FE;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .stat-content {
            flex: 1;
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #031837;
            line-height: 1.2;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.85rem;
        }
        
        /* Filters */
        .filters {
            background: white;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .filter-btn {
            padding: 8px 20px;
            border: none;
            border-radius: 20px;
            background: #f0f4ff;
            color: #031837;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        
        .filter-btn:hover,
        .filter-btn.active {
            background: #031837;
            color: white;
        }
        
        .filter-btn i {
            margin-right: 5px;
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
        
        tr.unread {
            background: #fff9e6;
            font-weight: 500;
        }
        
        .message-subject {
            font-weight: 600;
            color: #031837;
            margin-bottom: 5px;
        }
        
        .message-preview {
            color: #666;
            font-size: 0.9rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .sender-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .sender-name {
            font-weight: 600;
            color: #031837;
        }
        
        .sender-email {
            color: #666;
            font-size: 0.85rem;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-read {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-unread {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-replied {
            background: #cce5ff;
            color: #004085;
        }
        
        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 0.8rem;
            cursor: pointer;
            text-decoration: none;
            color: white;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .action-btn.view { background: #031837; }
        .action-btn.view:hover { background: #0a2a4a; }
        .action-btn.read { background: #28a745; }
        .action-btn.read:hover { background: #218838; }
        .action-btn.replied { background: #007bff; }
        .action-btn.replied:hover { background: #0056b3; }
        .action-btn.delete { background: #dc3545; }
        .action-btn.delete:hover { background: #c82333; }
        
        .datetime {
            font-size: 0.8rem;
            color: #666;
        }
        
        /* Modal */
        .message-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .message-modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }
        
        .modal-content h2 {
            color: #031837;
            margin-bottom: 20px;
            padding-right: 30px;
        }
        
        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }
        
        .modal-close:hover {
            color: #dc3545;
        }
        
        .modal-sender {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .modal-sender p {
            margin: 5px 0;
        }
        
        .modal-message {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            line-height: 1.8;
            white-space: pre-wrap;
        }
        
        /* Responsive */
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
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .filters {
                justify-content: center;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .action-btn {
                width: 100%;
                justify-content: center;
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
                    <a href="contact_submissions.php" class="nav-link active">
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
                    <h1><i class="fas fa-envelope"></i> Contact Messages</h1>
                    <p>Manage contact form submissions</p>
                </div>
                <div class="action-buttons">
                    <a href="?filter=all" class="btn btn-primary btn-sm">All Messages</a>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $total_count; ?></div>
                        <div class="stat-label">Total Messages</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-envelope-open"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $unread_count; ?></div>
                        <div class="stat-label">Unread</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $total_count - $unread_count; ?></div>
                        <div class="stat-label">Read</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-reply"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $replied_count; ?></div>
                        <div class="stat-label">Replied</div>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="filters">
                <a href="?filter=all" class="filter-btn <?= $filter == 'all' ? 'active' : '' ?>">
                    <i class="fas fa-list"></i> All
                </a>
                <a href="?filter=unread" class="filter-btn <?= $filter == 'unread' ? 'active' : '' ?>">
                    <i class="fas fa-envelope"></i> Unread
                </a>
                <a href="?filter=read" class="filter-btn <?= $filter == 'read' ? 'active' : '' ?>">
                    <i class="fas fa-envelope-open"></i> Read
                </a>
                <a href="?filter=replied" class="filter-btn <?= $filter == 'replied' ? 'active' : '' ?>">
                    <i class="fas fa-reply"></i> Replied
                </a>
            </div>
            
            <!-- Messages -->
            <?php if ($message): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <!-- Messages Table -->
            <div class="table-container">
                <?php if ($submissions && mysqli_num_rows($submissions) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Sender</th>
                                <th>Message</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($submissions)): ?>
                                <tr class="<?= !$row['is_read'] ? 'unread' : '' ?>">
                                    <td>
                                        <div class="sender-info">
                                            <span class="sender-name"><?= htmlspecialchars($row['name']) ?></span>
                                            <span class="sender-email"><?= htmlspecialchars($row['email']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="message-subject"><?= htmlspecialchars($row['subject']) ?></div>
                                        <div class="message-preview"><?= htmlspecialchars(substr($row['message'], 0, 100)) ?>...</div>
                                    </td>
                                    <td>
                                        <?php if (!$row['is_read']): ?>
                                            <span class="badge badge-unread">Unread</span>
                                        <?php else: ?>
                                            <span class="badge badge-read">Read</span>
                                        <?php endif; ?>
                                        
                                        <?php if ($row['is_replied']): ?>
                                            <span class="badge badge-replied" style="margin-left: 5px;">Replied</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="datetime"><?= date('M j, Y', strtotime($row['created_at'])) ?></div>
                                        <div class="datetime"><?= date('g:i A', strtotime($row['created_at'])) ?></div>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <a href="#" onclick="viewMessage(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['name'])) ?>', '<?= htmlspecialchars($row['email']) ?>', '<?= htmlspecialchars(addslashes($row['subject'])) ?>', '<?= htmlspecialchars(addslashes($row['message'])) ?>')" class="action-btn view">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            
                                            <?php if (!$row['is_read']): ?>
                                                <a href="?mark_read=<?= $row['id'] ?>" class="action-btn read">
                                                    <i class="fas fa-check"></i> Mark Read
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if (!$row['is_replied']): ?>
                                                <a href="mailto:<?= $row['email'] ?>?subject=Re: <?= urlencode($row['subject']) ?>" class="action-btn replied">
                                                    <i class="fas fa-reply"></i> Reply
                                                </a>
                                                <a href="?mark_replied=<?= $row['id'] ?>" class="action-btn replied" onclick="return confirm('Are you sure you want to mark this message as replied?')">
                                                    <i class="fas fa-check-double"></i> Mark Replied
                                                </a>
                                            <?php endif; ?>
                                            
                                            <a href="?delete=<?= $row['id'] ?>" class="action-btn delete" onclick="return confirm('Are you sure you want to delete this message? This action cannot be undone.')">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-envelope-open" style="font-size: 3rem; color: #ddd; margin-bottom: 15px; display: block;"></i>
                        <h3>No Messages Found</h3>
                        <p style="color: #666;">There are no contact messages to display.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- Message View Modal -->
    <div class="message-modal" id="messageModal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
            <h2 id="modalSubject"></h2>
            <div class="modal-sender">
                <p><strong>From:</strong> <span id="modalName"></span> (<span id="modalEmail"></span>)</p>
            </div>
            <div class="modal-message" id="modalMessage"></div>
        </div>
    </div>
    
    <script>
    function viewMessage(id, name, email, subject, message) {
        document.getElementById('modalSubject').textContent = subject;
        document.getElementById('modalName').textContent = name;
        document.getElementById('modalEmail').textContent = email;
        document.getElementById('modalMessage').textContent = message;
        document.getElementById('messageModal').classList.add('active');
        
        // Optionally mark as read when viewed
        fetch(`?mark_read=${id}`, { method: 'HEAD' });
    }
    
    function closeModal() {
        document.getElementById('messageModal').classList.remove('active');
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('messageModal');
        if (event.target == modal) {
            modal.classList.remove('active');
        }
    }
    </script>
</body>
</html>