<?php
// admin/creator.php - Manage MalamIromba Creator Content
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

// Define upload_image function if not exists
if (!function_exists('upload_image')) {
    function upload_image($file, $folder = 'creator') {
        $target_dir = __DIR__ . "/../uploads/" . $folder . "/";
        
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'File upload error: ' . $file['error']];
        }
        
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime_type, $allowed_types)) {
            return ['success' => false, 'error' => 'Only JPG, PNG, GIF, and WebP images are allowed.'];
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $target_file = $target_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            return ['success' => true, 'filename' => $filename];
        } else {
            return ['success' => false, 'error' => 'Failed to move uploaded file.'];
        }
    }
}

// Handle actions
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$error = '';

// Handle Delete
if ($action === 'delete' && $id > 0) {
    // Get the item to delete its image
    $get_item = mysqli_query($conn, "SELECT featured_image FROM creator WHERE id = $id");
    if ($get_item && mysqli_num_rows($get_item) > 0) {
        $item_data = mysqli_fetch_assoc($get_item);
        
        if (!empty($item_data['featured_image'])) {
            $image_path = __DIR__ . "/../uploads/creator/" . $item_data['featured_image'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
    }
    
    $result = mysqli_query($conn, "DELETE FROM creator WHERE id = $id");
    
    if ($result && mysqli_affected_rows($conn) > 0) {
        $message = "Item deleted successfully.";
    } else {
        $error = "Item not found or could not be deleted.";
    }
    $action = 'list';
}

// Handle Toggle Featured
if ($action === 'toggle_featured' && $id > 0) {
    $result = mysqli_query($conn, "UPDATE creator SET is_featured = NOT is_featured WHERE id = $id");
    if ($result) {
        $message = "Featured status updated.";
    }
    $action = 'list';
}

// Handle Toggle Publish
if ($action === 'toggle_publish' && $id > 0) {
    $result = mysqli_query($conn, "UPDATE creator SET is_published = NOT is_published WHERE id = $id");
    if ($result) {
        $message = "Publish status updated.";
    }
    $action = 'list';
}

// Handle Add/Edit Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        // Get form data
        $title = mysqli_real_escape_string($conn, $_POST['title'] ?? '');
        $content_type = mysqli_real_escape_string($conn, $_POST['content_type'] ?? 'blog');
        $category = mysqli_real_escape_string($conn, $_POST['category'] ?? '');
        $excerpt = mysqli_real_escape_string($conn, $_POST['excerpt'] ?? '');
        $content = mysqli_real_escape_string($conn, $_POST['content'] ?? '');
        $video_url = mysqli_real_escape_string($conn, $_POST['video_url'] ?? '');
        $video_duration = mysqli_real_escape_string($conn, $_POST['video_duration'] ?? '');
        $file_url = mysqli_real_escape_string($conn, $_POST['file_url'] ?? '');
        $author = mysqli_real_escape_string($conn, $_POST['author'] ?? 'Ibrahim Zubairu');
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $is_published = isset($_POST['is_published']) ? 1 : 0;
        $published_at = !empty($_POST['published_at']) ? "'" . mysqli_real_escape_string($conn, $_POST['published_at']) . "'" : 'NULL';
        
        // Handle slug
        $slug_base = !empty($_POST['slug']) ? $_POST['slug'] : $title;
        $slug = createSlug($slug_base);
        $slug = mysqli_real_escape_string($conn, $slug);
        
        // Check slug uniqueness
        if ($action === 'add') {
            $check_slug = mysqli_query($conn, "SELECT id FROM creator WHERE slug = '$slug'");
            if (mysqli_num_rows($check_slug) > 0) {
                $slug = $slug . '-' . time();
            }
        } else {
            $check_slug = mysqli_query($conn, "SELECT id FROM creator WHERE slug = '$slug' AND id != $id");
            if (mysqli_num_rows($check_slug) > 0) {
                $slug = $slug . '-' . time();
            }
        }
        
        // Handle featured image
        $featured_image = '';
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $upload_result = upload_image($_FILES['featured_image'], 'creator');
            if ($upload_result['success']) {
                $featured_image = $upload_result['filename'];
                
                if ($action === 'edit' && !empty($_POST['existing_image'])) {
                    $old_image = __DIR__ . "/../uploads/creator/" . $_POST['existing_image'];
                    if (file_exists($old_image)) {
                        unlink($old_image);
                    }
                }
            } else {
                $error = $upload_result['error'];
            }
        } elseif (isset($_POST['existing_image']) && !empty($_POST['existing_image'])) {
            $featured_image = mysqli_real_escape_string($conn, $_POST['existing_image']);
        }
        
        // Validate required
        if (empty($title)) {
            $error = "Title is required.";
        } else {
            if ($action === 'add') {
                $sql = "INSERT INTO creator (
                    title, slug, content_type, category, excerpt, content,
                    featured_image, video_url, video_duration, file_url, author,
                    is_featured, is_published, published_at, created_at
                ) VALUES (
                    '$title', '$slug', '$content_type', " . ($category ? "'$category'" : "NULL") . ",
                    " . ($excerpt ? "'$excerpt'" : "NULL") . ", " . ($content ? "'$content'" : "NULL") . ",
                    " . ($featured_image ? "'$featured_image'" : "NULL") . ",
                    " . ($video_url ? "'$video_url'" : "NULL") . ",
                    " . ($video_duration ? "'$video_duration'" : "NULL") . ",
                    " . ($file_url ? "'$file_url'" : "NULL") . ",
                    '$author', $is_featured, $is_published, $published_at, NOW()
                )";
                
                $result = mysqli_query($conn, $sql);
                
                if ($result) {
                    $message = "Item added successfully.";
                    header("Location: creator.php?message=" . urlencode($message));
                    exit();
                } else {
                    $error = "Database error: " . mysqli_error($conn);
                }
                
            } else {
                $featured_image_sql = $featured_image ? "featured_image = '$featured_image'," : "";
                
                $sql = "UPDATE creator SET
                    title = '$title',
                    slug = '$slug',
                    content_type = '$content_type',
                    category = " . ($category ? "'$category'" : "NULL") . ",
                    excerpt = " . ($excerpt ? "'$excerpt'" : "NULL") . ",
                    content = " . ($content ? "'$content'" : "NULL") . ",
                    $featured_image_sql
                    video_url = " . ($video_url ? "'$video_url'" : "NULL") . ",
                    video_duration = " . ($video_duration ? "'$video_duration'" : "NULL") . ",
                    file_url = " . ($file_url ? "'$file_url'" : "NULL") . ",
                    author = '$author',
                    is_featured = $is_featured,
                    is_published = $is_published,
                    published_at = $published_at,
                    updated_at = NOW()
                    WHERE id = $id";
                
                $result = mysqli_query($conn, $sql);
                
                if ($result) {
                    $message = "Item updated successfully.";
                    header("Location: creator.php?message=" . urlencode($message));
                    exit();
                } else {
                    $error = "Database error: " . mysqli_error($conn);
                }
            }
        }
    }
}

// Get item for editing
$item = null;
if ($action === 'edit' && $id > 0) {
    $result = mysqli_query($conn, "SELECT * FROM creator WHERE id = $id");
    $item = mysqli_fetch_assoc($result);
    
    if (!$item) {
        $error = "Item not found.";
        $action = 'list';
    }
}

// Get all items
$items = [];
if ($action === 'list') {
    $result = mysqli_query($conn, "
        SELECT * FROM creator 
        ORDER BY is_featured DESC, created_at DESC
    ");
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $items[] = $row;
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
    <title>MalamIromba Content - TechInHausa Admin</title>
    
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
        
        /* Sidebar */
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
        
        /* Table */
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
        
        .item-thumb {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .item-title {
            font-weight: 600;
            color: #031837;
        }
        
        .item-meta {
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
        
        .badge-video {
            background: #dc3545;
            color: white;
        }
        
        .badge-blog {
            background: #28a745;
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
        
        /* Form */
        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            max-width: 900px;
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
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #e0e0e0;
        }
        
        .type-hint {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.25rem;
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
                    <a href="creator.php" class="nav-link active">
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
                            <i class="fas fa-plus-circle"></i> Add New Item
                        <?php elseif ($action === 'edit'): ?>
                            <i class="fas fa-edit"></i> Edit Item
                        <?php else: ?>
                            <i class="fas fa-user-graduate"></i> MalamIromba Content
                        <?php endif; ?>
                    </h1>
                    <p>
                        <?php if ($action === 'list'): ?>
                            <?php echo count($items); ?> items total
                        <?php else: ?>
                            Personal Education Initiative by Ibrahim Zubairu
                        <?php endif; ?>
                    </p>
                </div>
                
                <div class="action-buttons">
                    <?php if ($action === 'list'): ?>
                        <a href="?action=add" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add New Item
                        </a>
                    <?php else: ?>
                        <a href="creator.php" class="btn btn-warning">
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
                <!-- Items List -->
                <div class="table-container">
                    <?php if (empty($items)): ?>
                        <div class="empty-state">
                            <i class="fas fa-user-graduate"></i>
                            <h3>No Items Found</h3>
                            <p>Add your first MalamIromba content item.</p>
                            <a href="?action=add" class="btn btn-primary" style="margin-top: 1rem;">
                                <i class="fas fa-plus"></i> Add New Item
                            </a>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Category</th>
                                    <th>Author</th>
                                    <th>Views</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($item['featured_image'])): ?>
                                            <img src="<?php echo SITE_URL; ?>/uploads/creator/<?php echo htmlspecialchars($item['featured_image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['title']); ?>"
                                                 class="item-thumb">
                                        <?php else: ?>
                                            <div style="width: 60px; height: 60px; background: #f0f0f0; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-image" style="color: #ccc;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="item-title"><?php echo htmlspecialchars($item['title']); ?></div>
                                        <?php if (!empty($item['excerpt'])): ?>
                                            <div class="item-meta"><?php echo htmlspecialchars(substr($item['excerpt'], 0, 50)) . '...'; ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $item['content_type'] === 'video' ? 'badge-video' : 'badge-blog'; ?>">
                                            <?php echo $item['content_type'] === 'video' ? 'Video' : ucfirst($item['content_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['category'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($item['author']); ?></td>
                                    <td><?php echo number_format($item['views'] ?? 0); ?></td>
                                    <td>
                                        <?php if ($item['is_featured']): ?>
                                            <span class="badge badge-featured">Featured</span>
                                        <?php endif; ?>
                                        <?php if ($item['is_published']): ?>
                                            <span class="badge badge-success">Published</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Draft</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div><?php echo date('M j, Y', strtotime($item['published_at'] ?? $item['created_at'])); ?></div>
                                    </td>
                                    <td>
                                        <div class="action-group">
                                            <a href="?action=edit&id=<?php echo $item['id']; ?>" 
                                               class="action-icon" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?action=toggle_featured&id=<?php echo $item['id']; ?>" 
                                               class="action-icon" title="<?php echo $item['is_featured'] ? 'Remove Featured' : 'Make Featured'; ?>">
                                                <i class="fas fa-star" style="color: <?php echo $item['is_featured'] ? '#D3C9FE' : '#666'; ?>"></i>
                                            </a>
                                            <a href="?action=toggle_publish&id=<?php echo $item['id']; ?>" 
                                               class="action-icon" title="<?php echo $item['is_published'] ? 'Unpublish' : 'Publish'; ?>">
                                                <i class="fas <?php echo $item['is_published'] ? 'fa-eye' : 'fa-eye-slash'; ?>"></i>
                                            </a>
                                            <a href="<?php echo SITE_URL; ?>/creator/<?php echo $item['slug']; ?>" 
                                               class="action-icon" title="View" target="_blank">
                                                <i class="fas fa-external-link-alt"></i>
                                            </a>
                                            <a href="?action=delete&id=<?php echo $item['id']; ?>" 
                                               class="action-icon delete" title="Delete"
                                               onclick="return confirm('Are you sure you want to delete this item? This action cannot be undone.')">
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
                <!-- Add/Edit Form -->
                <div class="form-container">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-grid">
                            <!-- Title -->
                            <div class="form-group full-width">
                                <label><i class="fas fa-heading"></i> Title *</label>
                                <input type="text" name="title" class="form-control" 
                                       value="<?php echo htmlspecialchars($item['title'] ?? ''); ?>" required>
                            </div>
                            
                            <!-- Slug -->
                            <div class="form-group">
                                <label><i class="fas fa-link"></i> Slug</label>
                                <input type="text" name="slug" class="form-control" 
                                       value="<?php echo htmlspecialchars($item['slug'] ?? ''); ?>"
                                       placeholder="Leave empty to auto-generate">
                            </div>
                            
                            <!-- Content Type -->
                            <div class="form-group">
                                <label><i class="fas fa-tag"></i> Content Type</label>
                                <select name="content_type" class="form-control" id="contentType">
                                    <option value="blog" <?php echo (isset($item['content_type']) && $item['content_type'] === 'blog') ? 'selected' : ''; ?>>Blog Post</option>
                                    <option value="video" <?php echo (isset($item['content_type']) && $item['content_type'] === 'video') ? 'selected' : ''; ?>>Video</option>
                                    <option value="publication" <?php echo (isset($item['content_type']) && $item['content_type'] === 'publication') ? 'selected' : ''; ?>>Publication</option>
                                    <option value="achievement" <?php echo (isset($item['content_type']) && $item['content_type'] === 'achievement') ? 'selected' : ''; ?>>Achievement</option>
                                    <option value="tutorial" <?php echo (isset($item['content_type']) && $item['content_type'] === 'tutorial') ? 'selected' : ''; ?>>Tutorial</option>
                                    <option value="course" <?php echo (isset($item['content_type']) && $item['content_type'] === 'course') ? 'selected' : ''; ?>>Course</option>
                                </select>
                            </div>
                            
                            <!-- Category -->
                            <div class="form-group">
                                <label><i class="fas fa-folder"></i> Category</label>
                                <input type="text" name="category" class="form-control" 
                                       value="<?php echo htmlspecialchars($item['category'] ?? ''); ?>"
                                       placeholder="e.g., AI, Programming, Web Dev">
                            </div>
                            
                            <!-- Author -->
                            <div class="form-group">
                                <label><i class="fas fa-user"></i> Author</label>
                                <input type="text" name="author" class="form-control" 
                                       value="<?php echo htmlspecialchars($item['author'] ?? 'Ibrahim Zubairu'); ?>">
                            </div>
                            
                            <!-- Video URL (conditional) -->
                            <div class="form-group" id="videoUrlGroup">
                                <label><i class="fab fa-youtube"></i> Video URL</label>
                                <input type="url" name="video_url" class="form-control" 
                                       value="<?php echo htmlspecialchars($item['video_url'] ?? ''); ?>"
                                       placeholder="https://www.youtube.com/watch?v=...">
                            </div>
                            
                            <!-- Video Duration -->
                            <div class="form-group" id="videoDurationGroup">
                                <label><i class="fas fa-clock"></i> Video Duration</label>
                                <input type="text" name="video_duration" class="form-control" 
                                       value="<?php echo htmlspecialchars($item['video_duration'] ?? ''); ?>"
                                       placeholder="MM:SS">
                            </div>
                            
                            <!-- File URL (for publications) -->
                            <div class="form-group" id="fileUrlGroup">
                                <label><i class="fas fa-file-pdf"></i> File URL</label>
                                <input type="text" name="file_url" class="form-control" 
                                       value="<?php echo htmlspecialchars($item['file_url'] ?? ''); ?>"
                                       placeholder="/uploads/publications/filename.pdf">
                                <div class="type-hint">Path to PDF or downloadable file</div>
                            </div>
                            
                            <!-- Featured Image -->
                            <div class="form-group full-width">
                                <label><i class="fas fa-image"></i> Featured Image</label>
                                <input type="file" name="featured_image" class="form-control" accept="image/*">
                                <?php if (!empty($item['featured_image'])): ?>
                                    <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($item['featured_image']); ?>">
                                    <div class="current-image">
                                        <img src="<?php echo SITE_URL; ?>/uploads/creator/<?php echo htmlspecialchars($item['featured_image']); ?>" alt="Current image">
                                        <span>Current: <?php echo htmlspecialchars($item['featured_image']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="type-hint">Recommended: 800x600px</div>
                            </div>
                            
                            <!-- Excerpt -->
                            <div class="form-group full-width">
                                <label><i class="fas fa-paragraph"></i> Excerpt (Short description)</label>
                                <textarea name="excerpt" class="form-control" rows="2"><?php echo htmlspecialchars($item['excerpt'] ?? ''); ?></textarea>
                            </div>
                            
                            <!-- Full Content -->
                            <div class="form-group full-width">
                                <label><i class="fas fa-align-left"></i> Full Content</label>
                                <textarea name="content" class="form-control" rows="6"><?php echo htmlspecialchars($item['content'] ?? ''); ?></textarea>
                            </div>
                            
                            <!-- Publish Date -->
                            <div class="form-group">
                                <label><i class="fas fa-calendar"></i> Publish Date</label>
                                <input type="datetime-local" name="published_at" class="form-control" 
                                       value="<?php echo isset($item['published_at']) ? date('Y-m-d\TH:i', strtotime($item['published_at'])) : ''; ?>">
                            </div>
                            
                            <!-- Options -->
                            <div class="form-group">
                                <label><i class="fas fa-cog"></i> Options</label>
                                <div class="form-check">
                                    <input type="checkbox" name="is_featured" id="is_featured" value="1"
                                        <?php echo (isset($item['is_featured']) && $item['is_featured']) ? 'checked' : ''; ?>>
                                    <label for="is_featured">Featured on homepage</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="is_published" id="is_published" value="1"
                                        <?php echo (!isset($item['is_published']) || $item['is_published']) ? 'checked' : ''; ?>>
                                    <label for="is_published">Published</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <a href="creator.php" class="btn btn-warning">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> 
                                <?php echo $action === 'add' ? 'Save Item' : 'Update Item'; ?>
                            </button>
                        </div>
                    </form>
                </div>
                
                <script>
                // Show/hide fields based on content type
                document.getElementById('contentType').addEventListener('change', function() {
                    const type = this.value;
                    const videoUrlGroup = document.getElementById('videoUrlGroup');
                    const videoDurationGroup = document.getElementById('videoDurationGroup');
                    const fileUrlGroup = document.getElementById('fileUrlGroup');
                    
                    if (type === 'video') {
                        videoUrlGroup.style.display = 'block';
                        videoDurationGroup.style.display = 'block';
                        fileUrlGroup.style.display = 'none';
                    } else if (type === 'publication') {
                        videoUrlGroup.style.display = 'none';
                        videoDurationGroup.style.display = 'none';
                        fileUrlGroup.style.display = 'block';
                    } else {
                        videoUrlGroup.style.display = 'none';
                        videoDurationGroup.style.display = 'none';
                        fileUrlGroup.style.display = 'none';
                    }
                });
                
                // Trigger on load
                document.addEventListener('DOMContentLoaded', function() {
                    document.getElementById('contentType').dispatchEvent(new Event('change'));
                });
                </script>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>