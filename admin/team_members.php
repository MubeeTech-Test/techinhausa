<?php
// admin/team_members.php - Manage Team Members
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/functions.php";

// Require admin login
require_admin_login();

// Define upload_image function if not exists
if (!function_exists('upload_image')) {
    function upload_image($file, $folder = 'team') {
        $target_dir = __DIR__ . "/../uploads/" . $folder . "/";
        
        // Create directory if it doesn't exist
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
    // Get image to delete
    $get_member = mysqli_query($conn, "SELECT image FROM team_members WHERE id = $id");
    if ($get_member && mysqli_num_rows($get_member) > 0) {
        $member_data = mysqli_fetch_assoc($get_member);
        if (!empty($member_data['image'])) {
            $image_path = __DIR__ . "/../uploads/team/" . $member_data['image'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
    }
    
    $result = mysqli_query($conn, "DELETE FROM team_members WHERE id = $id");
    if ($result && mysqli_affected_rows($conn) > 0) {
        $message = "Team member deleted successfully.";
    } else {
        $error = "Team member not found or could not be deleted.";
    }
    $action = 'list';
}

// Handle Toggle Active
if ($action === 'toggle_active' && $id > 0) {
    $result = mysqli_query($conn, "UPDATE team_members SET is_active = NOT is_active WHERE id = $id");
    if ($result) {
        $message = "Team member status updated.";
    }
    $action = 'list';
}

// Handle Add/Edit Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        $name = mysqli_real_escape_string($conn, $_POST['name'] ?? '');
        $position = mysqli_real_escape_string($conn, $_POST['position'] ?? '');
        $bio = mysqli_real_escape_string($conn, $_POST['bio'] ?? '');
        $twitter = mysqli_real_escape_string($conn, $_POST['twitter'] ?? '');
        $linkedin = mysqli_real_escape_string($conn, $_POST['linkedin'] ?? '');
        $github = mysqli_real_escape_string($conn, $_POST['github'] ?? '');
        $display_order = (int)($_POST['display_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Handle image upload
        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_result = upload_image($_FILES['image'], 'team');
            if ($upload_result['success']) {
                $image = $upload_result['filename'];
                
                // If editing, delete old image
                if ($action === 'edit' && !empty($_POST['existing_image'])) {
                    $old_image = __DIR__ . "/../uploads/team/" . $_POST['existing_image'];
                    if (file_exists($old_image)) {
                        unlink($old_image);
                    }
                }
            } else {
                $error = $upload_result['error'];
            }
        } elseif (isset($_POST['existing_image']) && !empty($_POST['existing_image'])) {
            $image = mysqli_real_escape_string($conn, $_POST['existing_image']);
        }
        
        if (empty($name) || empty($position)) {
            $error = "Name and position are required.";
        } else {
            if ($action === 'add') {
                $sql = "INSERT INTO team_members (name, position, bio, image, twitter, linkedin, github, display_order, is_active, created_at) 
                        VALUES ('$name', '$position', " . ($bio ? "'$bio'" : "NULL") . ", " . ($image ? "'$image'" : "NULL") . ", 
                        " . ($twitter ? "'$twitter'" : "NULL") . ", " . ($linkedin ? "'$linkedin'" : "NULL") . ", 
                        " . ($github ? "'$github'" : "NULL") . ", $display_order, $is_active, NOW())";
                
                $result = mysqli_query($conn, $sql);
                
                if ($result) {
                    $message = "Team member added successfully.";
                    header("Location: team_members.php?message=" . urlencode($message));
                    exit();
                } else {
                    $error = "Database error: " . mysqli_error($conn);
                }
            } else {
                $image_sql = $image ? "image = '$image'," : "";
                
                $sql = "UPDATE team_members SET
                        name = '$name',
                        position = '$position',
                        bio = " . ($bio ? "'$bio'" : "NULL") . ",
                        $image_sql
                        twitter = " . ($twitter ? "'$twitter'" : "NULL") . ",
                        linkedin = " . ($linkedin ? "'$linkedin'" : "NULL") . ",
                        github = " . ($github ? "'$github'" : "NULL") . ",
                        display_order = $display_order,
                        is_active = $is_active,
                        updated_at = NOW()
                        WHERE id = $id";
                
                $result = mysqli_query($conn, $sql);
                
                if ($result) {
                    $message = "Team member updated successfully.";
                    header("Location: team_members.php?message=" . urlencode($message));
                    exit();
                } else {
                    $error = "Database error: " . mysqli_error($conn);
                }
            }
        }
    }
}

// Get member data for editing
$member = null;
if ($action === 'edit' && $id > 0) {
    $result = mysqli_query($conn, "SELECT * FROM team_members WHERE id = $id");
    $member = mysqli_fetch_assoc($result);
    if (!$member) {
        $error = "Team member not found.";
        $action = 'list';
    }
}

// Get members list
$members = [];
if ($action === 'list') {
    $result = mysqli_query($conn, "SELECT * FROM team_members ORDER BY display_order ASC, id DESC");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $members[] = $row;
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
    <title>Manage Team Members - TechInHausa Admin</title>
    
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
        
        .nav-section {
            margin: 1.5rem 0 0.5rem;
            font-size: 0.7rem;
            text-transform: uppercase;
            opacity: 0.5;
            color: #D3C9FE;
        }
        
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
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
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
        
        .member-photo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .member-name {
            font-weight: 600;
            color: #031837;
        }
        
        .member-position {
            color: #D3C9FE;
            font-size: 0.85rem;
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
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #e0e0e0;
        }
        
        .social-input-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .social-prefix {
            background: #f0f0f0;
            padding: 0.75rem 1rem;
            border-radius: 8px 0 0 8px;
            border: 1px solid #e0e0e0;
            border-right: none;
            color: #666;
        }
        
        .social-input {
            border-radius: 0 8px 8px 0;
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
                    </a>
                </li>
                <li class="nav-item">
                    <a href="blog.php" class="nav-link">
                        <i class="fas fa-blog"></i>
                        <span>Blog Posts</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="news.php" class="nav-link">
                        <i class="fas fa-newspaper"></i>
                        <span>News</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="research.php" class="nav-link">
                        <i class="fas fa-flask"></i>
                        <span>Research</span>
                    </a>
                </li>
                
                <li class="nav-section">About</li>
                <li class="nav-item">
                    <a href="team_members.php" class="nav-link active">
                        <i class="fas fa-users"></i>
                        <span>Team Members</span>
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
                            <i class="fas fa-user-plus"></i> Add Team Member
                        <?php elseif ($action === 'edit'): ?>
                            <i class="fas fa-user-edit"></i> Edit Team Member
                        <?php else: ?>
                            <i class="fas fa-users"></i> Manage Team Members
                        <?php endif; ?>
                    </h1>
                    <p>
                        <?php if ($action === 'list'): ?>
                            <?php echo count($members); ?> team members total
                        <?php else: ?>
                            Fill in the details below
                        <?php endif; ?>
                    </p>
                </div>
                
                <div class="action-buttons">
                    <?php if ($action === 'list'): ?>
                        <a href="?action=add" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Team Member
                        </a>
                    <?php else: ?>
                        <a href="team_members.php" class="btn btn-warning">
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
                <!-- Team Members List -->
                <div class="table-container">
                    <?php if (empty($members)): ?>
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <h3>No Team Members Found</h3>
                            <p>Get started by adding your first team member.</p>
                            <a href="?action=add" class="btn btn-primary" style="margin-top: 1rem;">
                                <i class="fas fa-plus"></i> Add Team Member
                            </a>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Photo</th>
                                    <th>Name</th>
                                    <th>Position</th>
                                    <th>Social Links</th>
                                    <th>Display Order</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($members as $member): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($member['image'])): ?>
                                            <img src="<?php echo SITE_URL; ?>/uploads/team/<?php echo htmlspecialchars($member['image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($member['name']); ?>"
                                                 class="member-photo">
                                        <?php else: ?>
                                            <div style="width: 50px; height: 50px; background: #f0f0f0; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-user" style="color: #ccc;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="member-name"><?php echo htmlspecialchars($member['name']); ?></div>
                                        <?php if (!empty($member['bio'])): ?>
                                            <small style="color: #999;"><?php echo truncateText($member['bio'], 50); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="member-position"><?php echo htmlspecialchars($member['position']); ?></div>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 8px;">
                                            <?php if (!empty($member['twitter'])): ?>
                                                <a href="<?php echo htmlspecialchars($member['twitter']); ?>" target="_blank" style="color: #1DA1F2;"><i class="fab fa-twitter"></i></a>
                                            <?php endif; ?>
                                            <?php if (!empty($member['linkedin'])): ?>
                                                <a href="<?php echo htmlspecialchars($member['linkedin']); ?>" target="_blank" style="color: #0077B5;"><i class="fab fa-linkedin-in"></i></a>
                                            <?php endif; ?>
                                            <?php if (!empty($member['github'])): ?>
                                                <a href="<?php echo htmlspecialchars($member['github']); ?>" target="_blank" style="color: #333;"><i class="fab fa-github"></i></a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?php echo $member['display_order']; ?></td>
                                    <td>
                                        <?php if ($member['is_active']): ?>
                                            <span class="badge badge-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-group">
                                            <a href="?action=edit&id=<?php echo $member['id']; ?>" 
                                               class="action-icon" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?action=toggle_active&id=<?php echo $member['id']; ?>" 
                                               class="action-icon" title="<?php echo $member['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                <i class="fas <?php echo $member['is_active'] ? 'fa-eye-slash' : 'fa-eye'; ?>"></i>
                                            </a>
                                            <a href="?action=delete&id=<?php echo $member['id']; ?>" 
                                               class="action-icon delete" title="Delete"
                                               onclick="return confirm('Are you sure you want to delete this team member? This action cannot be undone.')">
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
                <!-- Add/Edit Team Member Form -->
                <div class="form-container">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-grid">
                            <!-- Name -->
                            <div class="form-group full-width">
                                <label><i class="fas fa-user"></i> Full Name *</label>
                                <input type="text" name="name" class="form-control" 
                                       value="<?php echo htmlspecialchars($member['name'] ?? ''); ?>" required>
                            </div>
                            
                            <!-- Position -->
                            <div class="form-group full-width">
                                <label><i class="fas fa-briefcase"></i> Position *</label>
                                <input type="text" name="position" class="form-control" 
                                       value="<?php echo htmlspecialchars($member['position'] ?? ''); ?>" 
                                       placeholder="e.g., Founder & CEO, Head of Content" required>
                            </div>
                            
                            <!-- Bio -->
                            <div class="form-group full-width">
                                <label><i class="fas fa-align-left"></i> Bio</label>
                                <textarea name="bio" class="form-control" rows="4"><?php echo htmlspecialchars($member['bio'] ?? ''); ?></textarea>
                            </div>
                            
                            <!-- Photo -->
                            <div class="form-group full-width">
                                <label><i class="fas fa-camera"></i> Photo</label>
                                <input type="file" name="image" class="form-control" accept="image/*">
                                <?php if (!empty($member['image'])): ?>
                                    <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($member['image']); ?>">
                                    <div class="current-image">
                                        <img src="<?php echo SITE_URL; ?>/uploads/team/<?php echo htmlspecialchars($member['image']); ?>" alt="Current photo">
                                        <span>Current photo: <?php echo htmlspecialchars($member['image']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <small style="color: #666;">Recommended: Square image, at least 300x300px</small>
                            </div>
                            
                            <!-- Twitter -->
                            <div class="form-group">
                                <label><i class="fab fa-twitter" style="color: #1DA1F2;"></i> Twitter URL</label>
                                <div class="social-input-group">
                                    <span class="social-prefix"><i class="fab fa-twitter"></i></span>
                                    <input type="url" name="twitter" class="form-control social-input" 
                                           value="<?php echo htmlspecialchars($member['twitter'] ?? ''); ?>"
                                           placeholder="https://twitter.com/username">
                                </div>
                            </div>
                            
                            <!-- LinkedIn -->
                            <div class="form-group">
                                <label><i class="fab fa-linkedin-in" style="color: #0077B5;"></i> LinkedIn URL</label>
                                <div class="social-input-group">
                                    <span class="social-prefix"><i class="fab fa-linkedin-in"></i></span>
                                    <input type="url" name="linkedin" class="form-control social-input" 
                                           value="<?php echo htmlspecialchars($member['linkedin'] ?? ''); ?>"
                                           placeholder="https://linkedin.com/in/username">
                                </div>
                            </div>
                            
                            <!-- GitHub -->
                            <div class="form-group">
                                <label><i class="fab fa-github"></i> GitHub URL</label>
                                <div class="social-input-group">
                                    <span class="social-prefix"><i class="fab fa-github"></i></span>
                                    <input type="url" name="github" class="form-control social-input" 
                                           value="<?php echo htmlspecialchars($member['github'] ?? ''); ?>"
                                           placeholder="https://github.com/username">
                                </div>
                            </div>
                            
                            <!-- Display Order -->
                            <div class="form-group">
                                <label><i class="fas fa-sort"></i> Display Order</label>
                                <input type="number" name="display_order" class="form-control" 
                                       value="<?php echo htmlspecialchars($member['display_order'] ?? '0'); ?>"
                                       min="0">
                                <small style="color: #666;">Lower numbers appear first</small>
                            </div>
                            
                            <!-- Active Status -->
                            <div class="form-group">
                                <label><i class="fas fa-toggle-on"></i> Status</label>
                                <div class="form-check">
                                    <input type="checkbox" name="is_active" id="is_active" value="1"
                                        <?php echo (!isset($member['is_active']) || $member['is_active']) ? 'checked' : ''; ?>>
                                    <label for="is_active">Active</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <a href="team_members.php" class="btn btn-warning">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> 
                                <?php echo $action === 'add' ? 'Save Team Member' : 'Update Team Member'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>