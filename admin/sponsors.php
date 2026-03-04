<?php
// admin/sponsors.php - Manage Sponsors
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

// Define upload_image function (only if not already defined)
if (!function_exists('upload_image')) {
    function upload_image($file, $folder = 'sponsors') {
        $target_dir = __DIR__ . "/../uploads/" . $folder . "/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        // Check if file was uploaded
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'File upload error: ' . $file['error']];
        }
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp', 'image/svg+xml'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime_type, $allowed_types)) {
            return ['success' => false, 'error' => 'Only JPG, PNG, GIF, WebP, and SVG images are allowed.'];
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $target_file = $target_dir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            return ['success' => true, 'filename' => $filename];
        } else {
            return ['success' => false, 'error' => 'Failed to move uploaded file.'];
        }
    }
}

// Handle actions (add, edit, delete, toggle active)
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$error = '';

// Handle Delete
if ($action === 'delete' && $id > 0) {
    // Get the sponsor to delete its logo
    $get_sponsor = mysqli_query($conn, "SELECT logo_url FROM sponsors WHERE id = $id");
    if ($get_sponsor && mysqli_num_rows($get_sponsor) > 0) {
        $sponsor_data = mysqli_fetch_assoc($get_sponsor);
        
        // Delete the logo file if exists
        if (!empty($sponsor_data['logo_url'])) {
            $logo_path = __DIR__ . "/../uploads/sponsors/" . $sponsor_data['logo_url'];
            if (file_exists($logo_path)) {
                unlink($logo_path);
            }
        }
    }
    
    // Delete the sponsor
    $result = mysqli_query($conn, "DELETE FROM sponsors WHERE id = $id");
    
    if ($result && mysqli_affected_rows($conn) > 0) {
        $message = "Sponsor deleted successfully.";
    } else {
        $error = "Sponsor not found or could not be deleted.";
    }
    $action = 'list';
}

// Handle Toggle Active
if ($action === 'toggle_active' && $id > 0) {
    $result = mysqli_query($conn, "UPDATE sponsors SET is_active = NOT is_active WHERE id = $id");
    if ($result) {
        $message = "Sponsor status updated.";
    }
    $action = 'list';
}

// Handle Add/Edit Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        // Get form data
        $name = mysqli_real_escape_string($conn, $_POST['name'] ?? '');
        $website_url = mysqli_real_escape_string($conn, $_POST['website_url'] ?? '');
        $description = mysqli_real_escape_string($conn, $_POST['description'] ?? '');
        $sponsor_level = mysqli_real_escape_string($conn, $_POST['sponsor_level'] ?? 'gold');
        $display_order = isset($_POST['display_order']) ? (int)$_POST['display_order'] : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Handle logo upload
        $logo_url = '';
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $upload_result = upload_image($_FILES['logo'], 'sponsors');
            if ($upload_result['success']) {
                $logo_url = $upload_result['filename'];
                
                // If editing, delete old logo
                if ($action === 'edit' && !empty($_POST['existing_logo'])) {
                    $old_logo = __DIR__ . "/../uploads/sponsors/" . $_POST['existing_logo'];
                    if (file_exists($old_logo)) {
                        unlink($old_logo);
                    }
                }
            } else {
                $error = $upload_result['error'];
            }
        } elseif (isset($_POST['existing_logo']) && !empty($_POST['existing_logo'])) {
            $logo_url = mysqli_real_escape_string($conn, $_POST['existing_logo']);
        }
        
        // Validate required fields
        if (empty($name)) {
            $error = "Sponsor name is required.";
        } elseif (empty($logo_url) && $action === 'add') {
            $error = "Sponsor logo is required.";
        } else {
            if ($action === 'add') {
                // Insert new sponsor
                $sql = "INSERT INTO sponsors (
                    name, logo_url, website_url, description, 
                    sponsor_level, display_order, is_active, created_at
                ) VALUES (
                    '$name', '$logo_url', " . ($website_url ? "'$website_url'" : "NULL") . ",
                    " . ($description ? "'$description'" : "NULL") . ", '$sponsor_level', 
                    $display_order, $is_active, NOW()
                )";
                
                $result = mysqli_query($conn, $sql);
                
                if ($result) {
                    $message = "Sponsor added successfully.";
                    
                    // Redirect to list view
                    header("Location: sponsors.php?message=" . urlencode($message));
                    exit();
                } else {
                    $error = "Database error: " . mysqli_error($conn);
                }
                
            } else {
                // Update existing sponsor
                $logo_sql = $logo_url ? "logo_url = '$logo_url'," : "";
                
                $sql = "UPDATE sponsors SET
                    name = '$name',
                    $logo_sql
                    website_url = " . ($website_url ? "'$website_url'" : "NULL") . ",
                    description = " . ($description ? "'$description'" : "NULL") . ",
                    sponsor_level = '$sponsor_level',
                    display_order = $display_order,
                    is_active = $is_active
                    WHERE id = $id";
                
                $result = mysqli_query($conn, $sql);
                
                if ($result) {
                    $message = "Sponsor updated successfully.";
                    
                    // Redirect to list view
                    header("Location: sponsors.php?message=" . urlencode($message));
                    exit();
                } else {
                    $error = "Database error: " . mysqli_error($conn);
                }
            }
        }
    }
}

// Handle Move Up/Down for display order
if (($action === 'move_up' || $action === 'move_down') && $id > 0) {
    // Get current display order
    $current = mysqli_query($conn, "SELECT display_order FROM sponsors WHERE id = $id");
    if ($current && mysqli_num_rows($current) > 0) {
        $current_order = mysqli_fetch_assoc($current)['display_order'];
        
        if ($action === 'move_up') {
            // Find sponsor with next lower display order
            $swap = mysqli_query($conn, "SELECT id, display_order FROM sponsors WHERE display_order < $current_order ORDER BY display_order DESC LIMIT 1");
        } else {
            // Find sponsor with next higher display order
            $swap = mysqli_query($conn, "SELECT id, display_order FROM sponsors WHERE display_order > $current_order ORDER BY display_order ASC LIMIT 1");
        }
        
        if ($swap && mysqli_num_rows($swap) > 0) {
            $swap_data = mysqli_fetch_assoc($swap);
            
            // Swap display orders
            mysqli_query($conn, "UPDATE sponsors SET display_order = {$swap_data['display_order']} WHERE id = $id");
            mysqli_query($conn, "UPDATE sponsors SET display_order = $current_order WHERE id = {$swap_data['id']}");
            
            $message = "Display order updated.";
        }
    }
    $action = 'list';
}

// Get sponsor data for editing
$sponsor = null;
if ($action === 'edit' && $id > 0) {
    $result = mysqli_query($conn, "SELECT * FROM sponsors WHERE id = $id");
    $sponsor = mysqli_fetch_assoc($result);
    
    if (!$sponsor) {
        $error = "Sponsor not found.";
        $action = 'list';
    }
}

// Get sponsors list
$sponsors = [];
if ($action === 'list') {
    $result = mysqli_query($conn, "SELECT * FROM sponsors ORDER BY display_order ASC, created_at DESC");
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $sponsors[] = $row;
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

// Sponsor level options
$sponsor_levels = [
    'platinum' => 'Platinum',
    'gold' => 'Gold',
    'silver' => 'Silver',
    'bronze' => 'Bronze'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Sponsors - TechInHausa Admin</title>
    
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
        
        .sponsor-logo {
            width: 80px;
            height: 60px;
            object-fit: contain;
            border-radius: 6px;
            background: #f5f5f5;
            padding: 5px;
        }
        
        .sponsor-name {
            font-weight: 600;
            color: #031837;
        }
        
        .sponsor-level-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .level-platinum {
            background: linear-gradient(135deg, #e5e4e2, #d4d4d4);
            color: #031837;
        }
        
        .level-gold {
            background: linear-gradient(135deg, #ffd700, #ffb800);
            color: #031837;
        }
        
        .level-silver {
            background: linear-gradient(135deg, #c0c0c0, #a0a0a0);
            color: white;
        }
        
        .level-bronze {
            background: linear-gradient(135deg, #cd7f32, #b06e2b);
            color: white;
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
        
        .order-controls {
            display: flex;
            gap: 0.25rem;
        }
        
        .order-btn {
            color: #666;
            font-size: 1rem;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .order-btn:hover {
            color: #031837;
            transform: scale(1.1);
        }
        
        .order-btn.disabled {
            color: #ccc;
            pointer-events: none;
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
        
        .form-check {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .form-check input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #031837;
        }
        
        .current-image {
            margin-top: 0.5rem;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .current-image img {
            width: 80px;
            height: 60px;
            object-fit: contain;
            border-radius: 4px;
            background: #f5f5f5;
            padding: 5px;
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
                    <a href="sponsors.php" class="nav-link active">
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
                            <i class="fas fa-plus-circle"></i> Add New Sponsor
                        <?php elseif ($action === 'edit'): ?>
                            <i class="fas fa-edit"></i> Edit Sponsor
                        <?php else: ?>
                            <i class="fas fa-handshake"></i> Manage Sponsors
                        <?php endif; ?>
                    </h1>
                    <p>
                        <?php if ($action === 'list'): ?>
                            <?php echo count($sponsors); ?> sponsors total
                        <?php else: ?>
                            Fill in the details below
                        <?php endif; ?>
                    </p>
                </div>
                
                <div class="action-buttons">
                    <?php if ($action === 'list'): ?>
                        <a href="?action=add" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add New Sponsor
                        </a>
                    <?php else: ?>
                        <a href="sponsors.php" class="btn btn-warning">
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
                <!-- Sponsors List -->
                <div class="table-container">
                    <?php if (empty($sponsors)): ?>
                        <div class="empty-state">
                            <i class="fas fa-handshake"></i>
                            <h3>No Sponsors Found</h3>
                            <p>Get started by adding your first sponsor.</p>
                            <a href="?action=add" class="btn btn-primary" style="margin-top: 1rem;">
                                <i class="fas fa-plus"></i> Add New Sponsor
                            </a>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Logo</th>
                                    <th>Name</th>
                                    <th>Website</th>
                                    <th>Level</th>
                                    <th>Order</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sponsors as $sponsor): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($sponsor['logo_url'])): ?>
                                            <img src="<?php echo SITE_URL; ?>/uploads/sponsors/<?php echo htmlspecialchars($sponsor['logo_url']); ?>" 
                                                 alt="<?php echo htmlspecialchars($sponsor['name']); ?>"
                                                 class="sponsor-logo">
                                        <?php else: ?>
                                            <div style="width: 80px; height: 60px; background: #f0f0f0; border-radius: 6px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-image" style="color: #ccc;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="sponsor-name"><?php echo htmlspecialchars($sponsor['name']); ?></div>
                                        <?php if (!empty($sponsor['description'])): ?>
                                            <div class="news-meta">
                                                <?php echo htmlspecialchars(substr($sponsor['description'], 0, 60)) . '...'; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($sponsor['website_url'])): ?>
                                            <a href="<?php echo htmlspecialchars($sponsor['website_url']); ?>" target="_blank" style="color: #031837;">
                                                <i class="fas fa-external-link-alt"></i> Visit
                                            </a>
                                        <?php else: ?>
                                            <span style="color: #999;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="sponsor-level-badge level-<?php echo $sponsor['sponsor_level']; ?>">
                                            <?php echo ucfirst($sponsor['sponsor_level']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="order-controls">
                                            <?php if ($sponsor !== reset($sponsors)): ?>
                                                <a href="?action=move_up&id=<?php echo $sponsor['id']; ?>" class="order-btn" title="Move Up">
                                                    <i class="fas fa-arrow-up"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="order-btn disabled"><i class="fas fa-arrow-up"></i></span>
                                            <?php endif; ?>
                                            
                                            <span style="margin: 0 5px; color: #666;"><?php echo $sponsor['display_order']; ?></span>
                                            
                                            <?php if ($sponsor !== end($sponsors)): ?>
                                                <a href="?action=move_down&id=<?php echo $sponsor['id']; ?>" class="order-btn" title="Move Down">
                                                    <i class="fas fa-arrow-down"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="order-btn disabled"><i class="fas fa-arrow-down"></i></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($sponsor['is_active']): ?>
                                            <span class="badge badge-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-group">
                                            <a href="?action=edit&id=<?php echo $sponsor['id']; ?>" 
                                               class="action-icon" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?action=toggle_active&id=<?php echo $sponsor['id']; ?>" 
                                               class="action-icon" title="<?php echo $sponsor['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                <i class="fas <?php echo $sponsor['is_active'] ? 'fa-toggle-on' : 'fa-toggle-off'; ?>" 
                                                   style="color: <?php echo $sponsor['is_active'] ? '#28a745' : '#666'; ?>"></i>
                                            </a>
                                            <a href="?action=delete&id=<?php echo $sponsor['id']; ?>" 
                                               class="action-icon delete" title="Delete"
                                               onclick="return confirm('Are you sure you want to delete this sponsor? This action cannot be undone.')">
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
                <!-- Add/Edit Sponsor Form -->
                <div class="form-container">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-grid">
                            <!-- Sponsor Name -->
                            <div class="form-group full-width">
                                <label><i class="fas fa-building"></i> Sponsor Name *</label>
                                <input type="text" name="name" class="form-control" 
                                       value="<?php echo htmlspecialchars($sponsor['name'] ?? ''); ?>" required>
                            </div>
                            
                            <!-- Logo Upload -->
                            <div class="form-group full-width">
                                <label><i class="fas fa-image"></i> Sponsor Logo *</label>
                                <input type="file" name="logo" class="form-control" accept="image/*" 
                                       <?php echo $action === 'add' ? 'required' : ''; ?>>
                                <?php if (!empty($sponsor['logo_url'])): ?>
                                    <input type="hidden" name="existing_logo" value="<?php echo htmlspecialchars($sponsor['logo_url']); ?>">
                                    <div class="current-image">
                                        <img src="<?php echo SITE_URL; ?>/uploads/sponsors/<?php echo htmlspecialchars($sponsor['logo_url']); ?>" alt="Current logo">
                                        <span>Current logo: <?php echo htmlspecialchars($sponsor['logo_url']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <small style="color: #666;">Recommended: Transparent PNG or SVG, max size 2MB</small>
                            </div>
                            
                            <!-- Website URL -->
                            <div class="form-group full-width">
                                <label><i class="fas fa-globe"></i> Website URL</label>
                                <input type="url" name="website_url" class="form-control" 
                                       value="<?php echo htmlspecialchars($sponsor['website_url'] ?? ''); ?>"
                                       placeholder="https://example.com">
                            </div>
                            
                            <!-- Sponsor Level -->
                            <div class="form-group">
                                <label><i class="fas fa-trophy"></i> Sponsor Level</label>
                                <select name="sponsor_level" class="form-control">
                                    <?php foreach ($sponsor_levels as $value => $label): ?>
                                        <option value="<?php echo $value; ?>" 
                                            <?php echo (isset($sponsor['sponsor_level']) && $sponsor['sponsor_level'] == $value) ? 'selected' : ''; ?>>
                                            <?php echo $label; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Display Order -->
                            <div class="form-group">
                                <label><i class="fas fa-sort-numeric-down"></i> Display Order</label>
                                <input type="number" name="display_order" class="form-control" 
                                       value="<?php echo htmlspecialchars($sponsor['display_order'] ?? '0'); ?>"
                                       min="0" step="1">
                                <small style="color: #666;">Lower numbers appear first</small>
                            </div>
                            
                            <!-- Description -->
                            <div class="form-group full-width">
                                <label><i class="fas fa-align-left"></i> Description</label>
                                <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($sponsor['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <!-- Status -->
                            <div class="form-group full-width">
                                <div class="form-check">
                                    <input type="checkbox" name="is_active" id="is_active" value="1"
                                        <?php echo (!isset($sponsor['is_active']) || $sponsor['is_active']) ? 'checked' : ''; ?>>
                                    <label for="is_active">Active (visible on site)</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <a href="sponsors.php" class="btn btn-warning">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> 
                                <?php echo $action === 'add' ? 'Save Sponsor' : 'Update Sponsor'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>