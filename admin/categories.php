<?php
// admin/categories.php - Manage Categories
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

// Handle actions (add, edit, delete)
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$error = '';

// Handle Delete
if ($action === 'delete' && $id > 0) {
    // Check if category is being used in any content
    $check_videos = mysqli_query($conn, "SELECT id FROM videos WHERE category_id = $id LIMIT 1");
    $check_blog = mysqli_query($conn, "SELECT id FROM blog_posts WHERE category_id = $id LIMIT 1");
    $check_news = mysqli_query($conn, "SELECT id FROM news WHERE category_id = $id LIMIT 1");
    $check_research = mysqli_query($conn, "SELECT id FROM research WHERE category_id = $id LIMIT 1");
    $check_creator = mysqli_query($conn, "SELECT id FROM creator WHERE category_id = $id LIMIT 1");
    
    if (mysqli_num_rows($check_videos) > 0 || mysqli_num_rows($check_blog) > 0 || 
        mysqli_num_rows($check_news) > 0 || mysqli_num_rows($check_research) > 0 ||
        mysqli_num_rows($check_creator) > 0) {
        $error = "Cannot delete category because it is being used by content.";
    } else {
        $result = mysqli_query($conn, "DELETE FROM categories WHERE id = $id");
        
        if ($result && mysqli_affected_rows($conn) > 0) {
            $message = "Category deleted successfully.";
        } else {
            $error = "Category not found or could not be deleted.";
        }
    }
    $action = 'list';
}

// Handle Add/Edit Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        // Get form data
        $name = mysqli_real_escape_string($conn, $_POST['name'] ?? '');
        $type = mysqli_real_escape_string($conn, $_POST['type'] ?? '');
        $description = mysqli_real_escape_string($conn, $_POST['description'] ?? '');
        $icon = mysqli_real_escape_string($conn, $_POST['icon'] ?? '');
        
        // Handle slug generation
        $slug_base = !empty($_POST['slug']) ? $_POST['slug'] : $name;
        $slug = createSlug($slug_base);
        $slug = mysqli_real_escape_string($conn, $slug);
        
        // Validate required fields
        if (empty($name) || empty($type)) {
            $error = "Name and Type are required.";
        } else {
            // Check if slug exists (for new categories)
            if ($action === 'add') {
                $check_slug = mysqli_query($conn, "SELECT id FROM categories WHERE slug = '$slug'");
                if (mysqli_num_rows($check_slug) > 0) {
                    $slug = $slug . '-' . time();
                }
            } else {
                // For edit, check if slug exists for other categories
                $check_slug = mysqli_query($conn, "SELECT id FROM categories WHERE slug = '$slug' AND id != $id");
                if (mysqli_num_rows($check_slug) > 0) {
                    $slug = $slug . '-' . time();
                }
            }
            
            if ($action === 'add') {
                // Insert new category
                $sql = "INSERT INTO categories (name, slug, type, description, icon, created_at) 
                        VALUES ('$name', '$slug', '$type', " . ($description ? "'$description'" : "NULL") . ", 
                        " . ($icon ? "'$icon'" : "NULL") . ", NOW())";
                
                $result = mysqli_query($conn, $sql);
                
                if ($result) {
                    $message = "Category added successfully.";
                    
                    // Redirect to list view
                    header("Location: categories.php?message=" . urlencode($message));
                    exit();
                } else {
                    $error = "Database error: " . mysqli_error($conn);
                }
                
            } else {
                // Update existing category
                $sql = "UPDATE categories SET
                        name = '$name',
                        slug = '$slug',
                        type = '$type',
                        description = " . ($description ? "'$description'" : "NULL") . ",
                        icon = " . ($icon ? "'$icon'" : "NULL") . "
                        WHERE id = $id";
                
                $result = mysqli_query($conn, $sql);
                
                if ($result) {
                    $message = "Category updated successfully.";
                    
                    // Redirect to list view
                    header("Location: categories.php?message=" . urlencode($message));
                    exit();
                } else {
                    $error = "Database error: " . mysqli_error($conn);
                }
            }
        }
    }
}

// Get category data for editing
$category = null;
if ($action === 'edit' && $id > 0) {
    $result = mysqli_query($conn, "SELECT * FROM categories WHERE id = $id");
    $category = mysqli_fetch_assoc($result);
    
    if (!$category) {
        $error = "Category not found.";
        $action = 'list';
    }
}

// Get categories list
$categories = [];
if ($action === 'list') {
    $result = mysqli_query($conn, "SELECT * FROM categories ORDER BY type, name ASC");
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $categories[] = $row;
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

// Content types for dropdown
$content_types = [
    'video' => 'Videos',
    'blog' => 'Blog Posts',
    'news' => 'News',
    'research' => 'Research',
    'creator' => 'Creator/MalamIromba'
];

// Common Font Awesome icons for suggestions
$common_icons = [
    'fa-code' => 'Code',
    'fa-globe' => 'Globe',
    'fa-robot' => 'Robot',
    'fa-newspaper' => 'Newspaper',
    'fa-flask' => 'Flask',
    'fa-book' => 'Book',
    'fa-video' => 'Video',
    'fa-music' => 'Music',
    'fa-camera' => 'Camera',
    'fa-gamepad' => 'Gamepad',
    'fa-mobile' => 'Mobile',
    'fa-database' => 'Database',
    'fa-cloud' => 'Cloud',
    'fa-lock' => 'Lock',
    'fa-gear' => 'Gear',
    'fa-heart' => 'Heart',
    'fa-star' => 'Star',
    'fa-bolt' => 'Bolt',
    'fa-leaf' => 'Leaf',
    'fa-crown' => 'Crown',
    'fa-graduation-cap' => 'Graduation',
    'fa-chart-line' => 'Analytics',
    'fa-microchip' => 'Microchip',
    'fa-wifi' => 'WiFi',
    'fa-brain' => 'Brain'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - TechInHausa Admin</title>
    
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
        
        .category-icon {
            width: 36px;
            height: 36px;
            background: rgba(211, 201, 254, 0.2);
            color: #031837;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .category-name {
            font-weight: 600;
            color: #031837;
        }
        
        .category-slug {
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
        
        .badge-video {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .badge-blog {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        
        .badge-news {
            background: #fff3e0;
            color: #f57c00;
        }
        
        .badge-research {
            background: #e8f5e8;
            color: #388e3c;
        }
        
        .badge-creator {
            background: #D3C9FE;
            color: #031837;
        }
        
        .usage-count {
            font-size: 0.9rem;
            font-weight: 600;
            color: #031837;
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
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group.full-width {
            grid-column: span 2;
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
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1em;
        }
        
        .icon-suggestions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .icon-suggestion {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.85rem;
        }
        
        .icon-suggestion:hover {
            background: #D3C9FE;
            border-color: #031837;
        }
        
        .icon-suggestion i {
            color: #031837;
        }
        
        .icon-preview {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: #f8f9fa;
            border-radius: 6px;
            margin-top: 0.5rem;
        }
        
        .icon-preview i {
            font-size: 1.2rem;
            color: #031837;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #e0e0e0;
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
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-group.full-width {
                grid-column: span 1;
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
                    <a href="categories.php" class="nav-link active">
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
                    <h1>
                        <?php if ($action === 'add'): ?>
                            <i class="fas fa-plus-circle"></i> Add Category
                        <?php elseif ($action === 'edit'): ?>
                            <i class="fas fa-edit"></i> Edit Category
                        <?php else: ?>
                            <i class="fas fa-folder"></i> Manage Categories
                        <?php endif; ?>
                    </h1>
                    <p>
                        <?php if ($action === 'list'): ?>
                            <?php echo count($categories); ?> categories total
                        <?php else: ?>
                            Fill in the details below
                        <?php endif; ?>
                    </p>
                </div>
                
                <div class="action-buttons">
                    <?php if ($action === 'list'): ?>
                        <a href="?action=add" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Category
                        </a>
                    <?php else: ?>
                        <a href="categories.php" class="btn btn-warning">
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
                <!-- Categories List -->
                <div class="table-container">
                    <?php if (empty($categories)): ?>
                        <div class="empty-state">
                            <i class="fas fa-folder-open"></i>
                            <h3>No Categories Found</h3>
                            <p>Get started by adding your first category.</p>
                            <a href="?action=add" class="btn btn-primary" style="margin-top: 1rem;">
                                <i class="fas fa-plus"></i> Add Category
                            </a>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Icon</th>
                                    <th>Name / Slug</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Usage</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $cat): 
                                    // Get usage count for each content type
                                    $usage_count = 0;
                                    $usage_details = [];
                                    
                                    if ($cat['type'] === 'video') {
                                        $count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM videos WHERE category_id = {$cat['id']}"))['total'];
                                        $usage_count += $count;
                                        if ($count > 0) $usage_details[] = "$count videos";
                                    } elseif ($cat['type'] === 'blog') {
                                        $count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM blog_posts WHERE category_id = {$cat['id']}"))['total'];
                                        $usage_count += $count;
                                        if ($count > 0) $usage_details[] = "$count blog posts";
                                    } elseif ($cat['type'] === 'news') {
                                        $count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM news WHERE category_id = {$cat['id']}"))['total'];
                                        $usage_count += $count;
                                        if ($count > 0) $usage_details[] = "$count news";
                                    } elseif ($cat['type'] === 'research') {
                                        $count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM research WHERE category_id = {$cat['id']}"))['total'];
                                        $usage_count += $count;
                                        if ($count > 0) $usage_details[] = "$count research";
                                    } elseif ($cat['type'] === 'creator') {
                                        $count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM creator WHERE category_id = {$cat['id']}"))['total'];
                                        $usage_count += $count;
                                        if ($count > 0) $usage_details[] = "$count creator items";
                                    }
                                ?>
                                <tr>
                                    <td>
                                        <div class="category-icon">
                                            <?php if (!empty($cat['icon'])): ?>
                                                <i class="fas <?php echo htmlspecialchars($cat['icon']); ?>"></i>
                                            <?php else: ?>
                                                <i class="fas fa-folder"></i>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="category-name"><?php echo htmlspecialchars($cat['name']); ?></div>
                                        <div class="category-slug"><?php echo htmlspecialchars($cat['slug']); ?></div>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $cat['type']; ?>">
                                            <?php 
                                            $type_labels = [
                                                'video' => 'Video',
                                                'blog' => 'Blog',
                                                'news' => 'News',
                                                'research' => 'Research',
                                                'creator' => 'Creator'
                                            ];
                                            echo $type_labels[$cat['type']] ?? ucfirst($cat['type']); 
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($cat['description'])): ?>
                                            <?php echo htmlspecialchars(substr($cat['description'], 0, 60)) . (strlen($cat['description']) > 60 ? '...' : ''); ?>
                                        <?php else: ?>
                                            <span style="color: #999;">No description</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="usage-count"><?php echo $usage_count; ?></div>
                                        <?php if (!empty($usage_details)): ?>
                                            <small style="color: #666;"><?php echo implode(', ', $usage_details); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-group">
                                            <a href="?action=edit&id=<?php echo $cat['id']; ?>" 
                                               class="action-icon" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?action=delete&id=<?php echo $cat['id']; ?>" 
                                               class="action-icon delete" title="Delete"
                                               onclick="return confirm('Are you sure you want to delete this category? This action cannot be undone.')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                
            <?php elseif ($action === 'add' || $action === 'edit'): ?>
                <!-- Add/Edit Category Form -->
                <div class="form-container">
                    <form method="POST">
                        <div class="form-grid">
                            <!-- Name -->
                            <div class="form-group full-width">
                                <label><i class="fas fa-tag"></i> Category Name *</label>
                                <input type="text" name="name" class="form-control" 
                                       value="<?php echo htmlspecialchars($category['name'] ?? ''); ?>" 
                                       placeholder="e.g., Programming, AI, Web Development" required>
                            </div>
                            
                            <!-- Slug -->
                            <div class="form-group">
                                <label><i class="fas fa-link"></i> Slug</label>
                                <input type="text" name="slug" class="form-control" 
                                       value="<?php echo htmlspecialchars($category['slug'] ?? ''); ?>"
                                       placeholder="Leave empty to auto-generate">
                            </div>
                            
                            <!-- Type -->
                            <div class="form-group">
                                <label><i class="fas fa-filter"></i> Content Type *</label>
                                <select name="type" class="form-control" required>
                                    <option value="">-- Select Type --</option>
                                    <?php foreach ($content_types as $type_value => $type_label): ?>
                                        <option value="<?php echo $type_value; ?>" 
                                            <?php echo (isset($category['type']) && $category['type'] == $type_value) ? 'selected' : ''; ?>>
                                            <?php echo $type_label; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Icon -->
                            <div class="form-group full-width">
                                <label><i class="fas fa-icons"></i> Icon (Font Awesome class)</label>
                                <input type="text" name="icon" class="form-control" id="iconInput"
                                       value="<?php echo htmlspecialchars($category['icon'] ?? ''); ?>" 
                                       placeholder="e.g., fa-code, fa-robot, fa-globe">
                                
                                <?php if (!empty($category['icon'])): ?>
                                <div class="icon-preview">
                                    <i class="fas <?php echo htmlspecialchars($category['icon']); ?>"></i>
                                    <span>Current icon: <?php echo htmlspecialchars($category['icon']); ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <div class="icon-suggestions">
                                    <?php foreach ($common_icons as $icon_class => $icon_name): ?>
                                        <div class="icon-suggestion" onclick="document.getElementById('iconInput').value = '<?php echo $icon_class; ?>'">
                                            <i class="fas <?php echo $icon_class; ?>"></i>
                                            <span><?php echo $icon_name; ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <small style="color: #666;">Click on an icon to use it, or type your own Font Awesome class</small>
                            </div>
                            
                            <!-- Description -->
                            <div class="form-group full-width">
                                <label><i class="fas fa-align-left"></i> Description</label>
                                <textarea name="description" class="form-control" rows="4" 
                                          placeholder="Describe what this category is for..."><?php echo htmlspecialchars($category['description'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <a href="categories.php" class="btn btn-warning">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> 
                                <?php echo $action === 'add' ? 'Save Category' : 'Update Category'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <script>
    // Auto-generate slug from name
    document.addEventListener('DOMContentLoaded', function() {
        const nameInput = document.querySelector('input[name="name"]');
        const slugInput = document.querySelector('input[name="slug"]');
        
        if (nameInput && slugInput && !slugInput.value) {
            nameInput.addEventListener('blur', function() {
                if (!slugInput.value) {
                    const slug = this.value
                        .toLowerCase()
                        .replace(/[^a-z0-9\s-]/g, '')
                        .replace(/\s+/g, '-')
                        .replace(/-+/g, '-')
                        .replace(/^-|-$/g, '');
                    slugInput.value = slug;
                }
            });
        }
    });
    </script>
</body>
</html>