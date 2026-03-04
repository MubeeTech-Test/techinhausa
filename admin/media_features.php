<?php
// admin/media_features.php - Manage Media Features & Recognition
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
    function upload_image($file, $folder = 'media') {
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

// Handle actions (add, edit, delete, toggle featured, toggle active)
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$error = '';

// Handle Delete
if ($action === 'delete' && $id > 0) {
    // Get the media feature to delete its images
    $get_media = mysqli_query($conn, "SELECT outlet_logo, featured_image FROM media_features WHERE id = $id");
    if ($get_media && mysqli_num_rows($get_media) > 0) {
        $media_data = mysqli_fetch_assoc($get_media);
        
        // Delete logo file if exists
        if (!empty($media_data['outlet_logo'])) {
            $logo_path = __DIR__ . "/../uploads/media/" . $media_data['outlet_logo'];
            if (file_exists($logo_path)) {
                unlink($logo_path);
            }
        }
        
        // Delete featured image if exists
        if (!empty($media_data['featured_image'])) {
            $image_path = __DIR__ . "/../uploads/media/" . $media_data['featured_image'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
    }
    
    // Delete the media feature
    $result = mysqli_query($conn, "DELETE FROM media_features WHERE id = $id");
    
    if ($result && mysqli_affected_rows($conn) > 0) {
        $message = "Media feature deleted successfully.";
    } else {
        $error = "Media feature not found or could not be deleted.";
    }
    $action = 'list';
}

// Handle Toggle Featured
if ($action === 'toggle_featured' && $id > 0) {
    $result = mysqli_query($conn, "UPDATE media_features SET is_featured = NOT is_featured WHERE id = $id");
    if ($result) {
        $message = "Media feature featured status updated.";
    }
    $action = 'list';
}

// Handle Toggle Active
if ($action === 'toggle_active' && $id > 0) {
    $result = mysqli_query($conn, "UPDATE media_features SET is_active = NOT is_active WHERE id = $id");
    if ($result) {
        $message = "Media feature active status updated.";
    }
    $action = 'list';
}

// Handle Add/Edit Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        // Get form data
        $outlet_name = mysqli_real_escape_string($conn, $_POST['outlet_name'] ?? '');
        $title = mysqli_real_escape_string($conn, $_POST['title'] ?? '');
        $description = mysqli_real_escape_string($conn, $_POST['description'] ?? '');
        $article_url = mysqli_real_escape_string($conn, $_POST['article_url'] ?? '');
        $feature_date = !empty($_POST['feature_date']) ? "'" . mysqli_real_escape_string($conn, $_POST['feature_date']) . "'" : 'NULL';
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $display_order = (int)($_POST['display_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Initialize image variables
        $outlet_logo = '';
        $featured_image = '';
        
        // Handle outlet logo upload
        if (isset($_FILES['outlet_logo']) && $_FILES['outlet_logo']['error'] === UPLOAD_ERR_OK) {
            $upload_result = upload_image($_FILES['outlet_logo'], 'media');
            if ($upload_result['success']) {
                $outlet_logo = $upload_result['filename'];
                
                // If editing, delete old logo
                if ($action === 'edit' && !empty($_POST['existing_logo'])) {
                    $old_logo = __DIR__ . "/../uploads/media/" . $_POST['existing_logo'];
                    if (file_exists($old_logo)) {
                        unlink($old_logo);
                    }
                }
            } else {
                $error = $upload_result['error'];
            }
        } elseif (isset($_POST['existing_logo']) && !empty($_POST['existing_logo'])) {
            $outlet_logo = mysqli_real_escape_string($conn, $_POST['existing_logo']);
        }
        
        // Handle featured image upload
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $upload_result = upload_image($_FILES['featured_image'], 'media');
            if ($upload_result['success']) {
                $featured_image = $upload_result['filename'];
                
                // If editing, delete old featured image
                if ($action === 'edit' && !empty($_POST['existing_featured'])) {
                    $old_image = __DIR__ . "/../uploads/media/" . $_POST['existing_featured'];
                    if (file_exists($old_image)) {
                        unlink($old_image);
                    }
                }
            } else {
                $error = $upload_result['error'];
            }
        } elseif (isset($_POST['existing_featured']) && !empty($_POST['existing_featured'])) {
            $featured_image = mysqli_real_escape_string($conn, $_POST['existing_featured']);
        }
        
        // Validate required fields
        if (empty($outlet_name) || empty($title)) {
            $error = "Outlet name and title are required.";
        } else {
            if ($action === 'add') {
                // Insert new media feature
                $sql = "INSERT INTO media_features (
                    outlet_name, outlet_logo, title, description, featured_image,
                    article_url, feature_date, is_featured, display_order, is_active, created_at
                ) VALUES (
                    '$outlet_name', " . ($outlet_logo ? "'$outlet_logo'" : "NULL") . ", 
                    '$title', " . ($description ? "'$description'" : "NULL") . ",
                    " . ($featured_image ? "'$featured_image'" : "NULL") . ",
                    " . ($article_url ? "'$article_url'" : "NULL") . ", 
                    $feature_date, $is_featured, $display_order, $is_active, NOW()
                )";
                
                $result = mysqli_query($conn, $sql);
                
                if ($result) {
                    $message = "Media feature added successfully.";
                    
                    // Redirect to list view
                    header("Location: media_features.php?message=" . urlencode($message));
                    exit();
                } else {
                    $error = "Database error: " . mysqli_error($conn);
                }
                
            } else {
                // Update existing media feature
                $outlet_logo_sql = $outlet_logo ? "outlet_logo = '$outlet_logo'," : "";
                $featured_image_sql = $featured_image ? "featured_image = '$featured_image'," : "";
                
                $sql = "UPDATE media_features SET
                    outlet_name = '$outlet_name',
                    $outlet_logo_sql
                    title = '$title',
                    description = " . ($description ? "'$description'" : "NULL") . ",
                    $featured_image_sql
                    article_url = " . ($article_url ? "'$article_url'" : "NULL") . ",
                    feature_date = $feature_date,
                    is_featured = $is_featured,
                    display_order = $display_order,
                    is_active = $is_active
                    WHERE id = $id";
                
                $result = mysqli_query($conn, $sql);
                
                if ($result) {
                    $message = "Media feature updated successfully.";
                    
                    // Redirect to list view
                    header("Location: media_features.php?message=" . urlencode($message));
                    exit();
                } else {
                    $error = "Database error: " . mysqli_error($conn);
                }
            }
        }
    }
}

// Get media feature data for editing
$media_item = null;
if ($action === 'edit' && $id > 0) {
    $result = mysqli_query($conn, "SELECT * FROM media_features WHERE id = $id");
    $media_item = mysqli_fetch_assoc($result);
    
    if (!$media_item) {
        $error = "Media feature not found.";
        $action = 'list';
    }
}

// Get media features list
$media_items = [];
if ($action === 'list') {
    $result = mysqli_query($conn, "
        SELECT * FROM media_features 
        ORDER BY is_featured DESC, display_order ASC, feature_date DESC, created_at DESC
    ");
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $media_items[] = $row;
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
    <title>Manage Media Features - TechInHausa Admin</title>
    
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
        
        .media-logo-sm {
            width: 60px;
            height: 40px;
            object-fit: contain;
            border-radius: 4px;
            background: #f5f5f5;
            padding: 4px;
        }
        
        .media-image-sm {
            width: 60px;
            height: 40px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .media-title {
            font-weight: 600;
            color: #031837;
        }
        
        .media-outlet {
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
        
        .badge-featured {
            background: #D3C9FE;
            color: #031837;
        }
        
        .badge-active {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-inactive {
            background: #f8d7da;
            color: #721c24;
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
        
        .form-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 0.5rem;
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
            width: 60px;
            height: 40px;
            object-fit: contain;
            border-radius: 4px;
            background: white;
            padding: 4px;
        }
        
        .image-hint {
            font-size: 0.85rem;
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
        
        /* Order input */
        .order-input {
            width: 80px;
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
                    <a href="media_features.php" class="nav-link active">
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
                            <i class="fas fa-plus-circle"></i> Add Media Feature
                        <?php elseif ($action === 'edit'): ?>
                            <i class="fas fa-edit"></i> Edit Media Feature
                        <?php else: ?>
                            <i class="fas fa-trophy"></i> Manage Media Features
                        <?php endif; ?>
                    </h1>
                    <p>
                        <?php if ($action === 'list'): ?>
                            <?php echo count($media_items); ?> media features total
                        <?php else: ?>
                            Fill in the details below
                        <?php endif; ?>
                    </p>
                </div>
                
                <div class="action-buttons">
                    <?php if ($action === 'list'): ?>
                        <a href="?action=add" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Media Feature
                        </a>
                    <?php else: ?>
                        <a href="media_features.php" class="btn btn-warning">
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
                <!-- Media Features List -->
                <div class="table-container">
                    <?php if (empty($media_items)): ?>
                        <div class="empty-state">
                            <i class="fas fa-trophy"></i>
                            <h3>No Media Features Found</h3>
                            <p>Get started by adding your first media feature.</p>
                            <a href="?action=add" class="btn btn-primary" style="margin-top: 1rem;">
                                <i class="fas fa-plus"></i> Add Media Feature
                            </a>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Logo</th>
                                    <th>Image</th>
                                    <th>Outlet & Title</th>
                                    <th>Date</th>
                                    <th>Order</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($media_items as $media): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($media['outlet_logo'])): ?>
                                            <img src="<?php echo SITE_URL; ?>/uploads/media/<?php echo htmlspecialchars($media['outlet_logo']); ?>" 
                                                 alt="<?php echo htmlspecialchars($media['outlet_name']); ?>"
                                                 class="media-logo-sm">
                                        <?php else: ?>
                                            <div style="width: 60px; height: 40px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-newspaper" style="color: #ccc;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($media['featured_image'])): ?>
                                            <img src="<?php echo SITE_URL; ?>/uploads/media/<?php echo htmlspecialchars($media['featured_image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($media['title']); ?>"
                                                 class="media-image-sm">
                                        <?php else: ?>
                                            <div style="width: 60px; height: 40px; background: #f0f0f0; border-radius: 4px;"></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="media-title"><?php echo htmlspecialchars($media['title']); ?></div>
                                        <div class="media-outlet">
                                            <i class="fas fa-building"></i> <?php echo htmlspecialchars($media['outlet_name']); ?>
                                        </div>
                                        <?php if (!empty($media['description'])): ?>
                                            <div class="media-outlet">
                                                <?php echo htmlspecialchars(substr($media['description'], 0, 60)) . '...'; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($media['feature_date'])): ?>
                                            <?php echo date('M j, Y', strtotime($media['feature_date'])); ?>
                                        <?php else: ?>
                                            <span style="color: #999;">No date</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">Order: <?php echo $media['display_order']; ?></span>
                                    </td>
                                    <td>
                                        <?php if ($media['is_featured']): ?>
                                            <span class="badge badge-featured">Featured</span>
                                        <?php endif; ?>
                                        <?php if ($media['is_active']): ?>
                                            <span class="badge badge-active">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-inactive">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-group">
                                            <a href="?action=edit&id=<?php echo $media['id']; ?>" 
                                               class="action-icon" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?action=toggle_featured&id=<?php echo $media['id']; ?>" 
                                               class="action-icon" title="<?php echo $media['is_featured'] ? 'Remove Featured' : 'Make Featured'; ?>">
                                                <i class="fas fa-star" style="color: <?php echo $media['is_featured'] ? '#D3C9FE' : '#666'; ?>"></i>
                                            </a>
                                            <a href="?action=toggle_active&id=<?php echo $media['id']; ?>" 
                                               class="action-icon" title="<?php echo $media['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                <i class="fas <?php echo $media['is_active'] ? 'fa-eye' : 'fa-eye-slash'; ?>"></i>
                                            </a>
                                            <?php if (!empty($media['article_url'])): ?>
                                                <a href="<?php echo htmlspecialchars($media['article_url']); ?>" 
                                                   class="action-icon" title="View Article" target="_blank">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="?action=delete&id=<?php echo $media['id']; ?>" 
                                               class="action-icon delete" title="Delete"
                                               onclick="return confirm('Are you sure you want to delete this media feature? This action cannot be undone.')">
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
                <!-- Add/Edit Media Feature Form -->
                <div class="form-container">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-grid">
                            <!-- Outlet Name -->
                            <div class="form-group full-width">
                                <label><i class="fas fa-building"></i> Outlet Name *</label>
                                <input type="text" name="outlet_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($media_item['outlet_name'] ?? ''); ?>" 
                                       placeholder="e.g., TechCrunch, BBC Hausa" required>
                            </div>
                            
                            <!-- Title -->
                            <div class="form-group full-width">
                                <label><i class="fas fa-heading"></i> Title *</label>
                                <input type="text" name="title" class="form-control" 
                                       value="<?php echo htmlspecialchars($media_item['title'] ?? ''); ?>" 
                                       placeholder="e.g., TechInHausa: Bringing Tech Education to Northern Nigeria" required>
                            </div>
                            
                            <!-- Outlet Logo -->
                            <div class="form-group">
                                <label><i class="fas fa-image"></i> Outlet Logo</label>
                                <input type="file" name="outlet_logo" class="form-control" accept="image/*">
                                <?php if (!empty($media_item['outlet_logo'])): ?>
                                    <input type="hidden" name="existing_logo" value="<?php echo htmlspecialchars($media_item['outlet_logo']); ?>">
                                    <div class="current-image">
                                        <img src="<?php echo SITE_URL; ?>/uploads/media/<?php echo htmlspecialchars($media_item['outlet_logo']); ?>" alt="Current logo">
                                        <span>Current logo: <?php echo htmlspecialchars($media_item['outlet_logo']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="image-hint">Recommended: Square format, PNG or SVG preferred</div>
                            </div>
                            
                            <!-- Featured Image -->
                            <div class="form-group">
                                <label><i class="fas fa-image"></i> Featured Image</label>
                                <input type="file" name="featured_image" class="form-control" accept="image/*">
                                <?php if (!empty($media_item['featured_image'])): ?>
                                    <input type="hidden" name="existing_featured" value="<?php echo htmlspecialchars($media_item['featured_image']); ?>">
                                    <div class="current-image">
                                        <img src="<?php echo SITE_URL; ?>/uploads/media/<?php echo htmlspecialchars($media_item['featured_image']); ?>" alt="Current image">
                                        <span>Current image</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Article URL -->
                            <div class="form-group full-width">
                                <label><i class="fas fa-link"></i> Article URL</label>
                                <input type="url" name="article_url" class="form-control" 
                                       value="<?php echo htmlspecialchars($media_item['article_url'] ?? ''); ?>" 
                                       placeholder="https://example.com/article">
                            </div>
                            
                            <!-- Feature Date -->
                            <div class="form-group">
                                <label><i class="fas fa-calendar"></i> Feature Date</label>
                                <input type="date" name="feature_date" class="form-control" 
                                       value="<?php echo isset($media_item['feature_date']) ? $media_item['feature_date'] : ''; ?>">
                            </div>
                            
                            <!-- Display Order -->
                            <div class="form-group">
                                <label><i class="fas fa-sort"></i> Display Order</label>
                                <input type="number" name="display_order" class="form-control order-input" 
                                       value="<?php echo htmlspecialchars($media_item['display_order'] ?? '0'); ?>" 
                                       min="0" step="1">
                                <div class="image-hint">Lower numbers appear first</div>
                            </div>
                            
                            <!-- Description -->
                            <div class="form-group full-width">
                                <label><i class="fas fa-align-left"></i> Description</label>
                                <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($media_item['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <!-- Status Options -->
                            <div class="form-group">
                                <label><i class="fas fa-cog"></i> Options</label>
                                <div class="form-check">
                                    <input type="checkbox" name="is_featured" id="is_featured" value="1"
                                        <?php echo (isset($media_item['is_featured']) && $media_item['is_featured']) ? 'checked' : ''; ?>>
                                    <label for="is_featured">Feature this media</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="is_active" id="is_active" value="1"
                                        <?php echo (!isset($media_item['is_active']) || $media_item['is_active']) ? 'checked' : ''; ?>>
                                    <label for="is_active">Active</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <a href="media_features.php" class="btn btn-warning">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> 
                                <?php echo $action === 'add' ? 'Save Media Feature' : 'Update Media Feature'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>