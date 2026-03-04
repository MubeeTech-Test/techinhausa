<?php
// admin/dashboard.php - TechInHausa Admin Dashboard
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/functions.php";

// Require admin login
require_admin_login();

/* ===========================
   SAFE COUNT FUNCTION
=========================== */
function safe_count($conn, $table, $condition = "") {
    $check = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if ($check && mysqli_num_rows($check) === 1) {
        $sql = "SELECT COUNT(*) AS total FROM `$table`";
        if ($condition) {
            $sql .= " WHERE " . $condition;
        }
        $q = mysqli_query($conn, $sql);
        if ($q) {
            $r = mysqli_fetch_assoc($q);
            return (int) $r['total'];
        }
    }
    return 0;
}

/* ===========================
   GET COUNTS FOR ALL TABLES
=========================== */

// Users (Admins)
$total_users = safe_count($conn, "users");

// Content counts by type
$total_videos = safe_count($conn, "videos", "is_published = 1");
$total_videos_draft = safe_count($conn, "videos", "is_published = 0");

$total_blog = safe_count($conn, "blog_posts", "is_published = 1");
$total_blog_draft = safe_count($conn, "blog_posts", "is_published = 0");

$total_news = safe_count($conn, "news", "is_published = 1");
$total_news_draft = safe_count($conn, "news", "is_published = 0");

$total_research = safe_count($conn, "research", "is_published = 1");
$total_research_draft = safe_count($conn, "research", "is_published = 0");

// Creator/MalamIromba counts
$total_creator = safe_count($conn, "creator", "is_published = 1");
$total_creator_draft = safe_count($conn, "creator", "is_published = 0");

// Team Members counts
$total_team_members = safe_count($conn, "team_members", "is_active = 1");
$total_team_members_inactive = safe_count($conn, "team_members", "is_active = 0");

// Contact Submissions counts
$total_contact_submissions = safe_count($conn, "contact_submissions");
$unread_contact_submissions = safe_count($conn, "contact_submissions", "is_read = 0");
$unreplied_contact_submissions = safe_count($conn, "contact_submissions", "is_replied = 0");

// Total content (all types combined)
$total_content = $total_videos + $total_blog + $total_news + $total_research + $total_creator;
$total_drafts = $total_videos_draft + $total_blog_draft + $total_news_draft + $total_research_draft + $total_creator_draft;

// Featured content
$total_featured = safe_count($conn, "videos", "is_featured = 1 AND is_published = 1") +
                  safe_count($conn, "blog_posts", "is_featured = 1 AND is_published = 1") +
                  safe_count($conn, "news", "is_featured = 1 AND is_published = 1") +
                  safe_count($conn, "research", "is_featured = 1 AND is_published = 1") +
                  safe_count($conn, "creator", "is_featured = 1 AND is_published = 1");

// Categories
$total_categories = safe_count($conn, "categories");

// Tags
$total_tags = safe_count($conn, "tags");

// Media Features
$total_media_features = safe_count($conn, "media_features");
$featured_media = safe_count($conn, "media_features", "is_featured = 1 AND is_active = 1");

// Sponsors
$total_sponsors = safe_count($conn, "sponsors");
$active_sponsors = safe_count($conn, "sponsors", "is_active = 1");

// Subscribers
$total_subscribers = safe_count($conn, "subscribers");
$active_subscribers = safe_count($conn, "subscribers", "is_active = 1");

// Get total views across all content
$total_views = 0;
$tables = ['videos', 'blog_posts', 'news', 'research', 'creator'];
foreach ($tables as $table) {
    $q = mysqli_query($conn, "SELECT SUM(views) AS total FROM $table");
    if ($q) {
        $r = mysqli_fetch_assoc($q);
        $total_views += (int) ($r['total'] ?? 0);
    }
}

// Get recent content by fetching separately and combining in PHP
$recent_content = [];

// Get recent videos
$videos_query = mysqli_query($conn, "
    SELECT id, title, 'video' as content_type, is_published, created_at, 
           COALESCE(author, 'Admin') as author 
    FROM videos 
    ORDER BY created_at DESC 
    LIMIT 5
");
if ($videos_query) {
    while ($row = mysqli_fetch_assoc($videos_query)) {
        $recent_content[] = $row;
    }
}

// Get recent blog posts
$blog_query = mysqli_query($conn, "
    SELECT id, title, 'blog' as content_type, is_published, created_at, 
           COALESCE(author, 'Admin') as author 
    FROM blog_posts 
    ORDER BY created_at DESC 
    LIMIT 5
");
if ($blog_query) {
    while ($row = mysqli_fetch_assoc($blog_query)) {
        $recent_content[] = $row;
    }
}

// Get recent news
$news_query = mysqli_query($conn, "
    SELECT id, title, 'news' as content_type, is_published, created_at, 
           COALESCE(author, 'Admin') as author 
    FROM news 
    ORDER BY created_at DESC 
    LIMIT 5
");
if ($news_query) {
    while ($row = mysqli_fetch_assoc($news_query)) {
        $recent_content[] = $row;
    }
}

// Get recent research
$research_query = mysqli_query($conn, "
    SELECT id, title, 'research' as content_type, is_published, created_at, 
           COALESCE(author, 'Admin') as author 
    FROM research 
    ORDER BY created_at DESC 
    LIMIT 5
");
if ($research_query) {
    while ($row = mysqli_fetch_assoc($research_query)) {
        $recent_content[] = $row;
    }
}

// Get recent creator content
$creator_query = mysqli_query($conn, "
    SELECT id, title, 'creator' as content_type, is_published, created_at, 
           COALESCE(author, 'Ibrahim Zubairu') as author 
    FROM creator 
    ORDER BY created_at DESC 
    LIMIT 5
");
if ($creator_query) {
    while ($row = mysqli_fetch_assoc($creator_query)) {
        $recent_content[] = $row;
    }
}

// Sort by created_at date (newest first)
usort($recent_content, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Limit to 5 items
$recent_content = array_slice($recent_content, 0, 5);

// Get recent subscribers
$recent_subscribers = mysqli_query($conn, "
    SELECT email, name, subscribed_at 
    FROM subscribers 
    WHERE is_active = 1 
    ORDER BY subscribed_at DESC 
    LIMIT 5
");

// Get recent contact submissions
$recent_contacts = mysqli_query($conn, "
    SELECT id, name, email, subject, is_read, created_at 
    FROM contact_submissions 
    ORDER BY created_at DESC 
    LIMIT 5
");

// Get most viewed content by fetching separately
$most_viewed = [];

// Get most viewed videos
$videos_most = mysqli_query($conn, "
    SELECT title, 'video' as content_type, views 
    FROM videos 
    WHERE is_published = 1 
    ORDER BY views DESC 
    LIMIT 5
");
if ($videos_most) {
    while ($row = mysqli_fetch_assoc($videos_most)) {
        $most_viewed[] = $row;
    }
}

// Get most viewed blog posts
$blog_most = mysqli_query($conn, "
    SELECT title, 'blog' as content_type, views 
    FROM blog_posts 
    WHERE is_published = 1 
    ORDER BY views DESC 
    LIMIT 5
");
if ($blog_most) {
    while ($row = mysqli_fetch_assoc($blog_most)) {
        $most_viewed[] = $row;
    }
}

// Get most viewed news
$news_most = mysqli_query($conn, "
    SELECT title, 'news' as content_type, views 
    FROM news 
    WHERE is_published = 1 
    ORDER BY views DESC 
    LIMIT 5
");
if ($news_most) {
    while ($row = mysqli_fetch_assoc($news_most)) {
        $most_viewed[] = $row;
    }
}

// Get most viewed research
$research_most = mysqli_query($conn, "
    SELECT title, 'research' as content_type, views 
    FROM research 
    WHERE is_published = 1 
    ORDER BY views DESC 
    LIMIT 5
");
if ($research_most) {
    while ($row = mysqli_fetch_assoc($research_most)) {
        $most_viewed[] = $row;
    }
}

// Get most viewed creator content
$creator_most = mysqli_query($conn, "
    SELECT title, 'creator' as content_type, views 
    FROM creator 
    WHERE is_published = 1 
    ORDER BY views DESC 
    LIMIT 5
");
if ($creator_most) {
    while ($row = mysqli_fetch_assoc($creator_most)) {
        $most_viewed[] = $row;
    }
}

// Sort by views (highest first)
usort($most_viewed, function($a, $b) {
    return $b['views'] - $a['views'];
});

// Limit to 5 items
$most_viewed = array_slice($most_viewed, 0, 5);

// Get content counts by month for chart (last 6 months)
$monthly_data = ['labels' => [], 'values' => []];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $month_name = date('M', strtotime("-$i months"));
    
    $total = 0;
    foreach ($tables as $table) {
        $q = mysqli_query($conn, "
            SELECT COUNT(*) as total 
            FROM $table 
            WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month'
        ");
        if ($q) {
            $r = mysqli_fetch_assoc($q);
            $total += (int) ($r['total'] ?? 0);
        }
    }
    
    $monthly_data['labels'][] = $month_name;
    $monthly_data['values'][] = $total;
}

// Get current admin info
$current_admin = get_current_admin($conn);
$admin_name = $current_admin['full_name'] ?? $current_admin['username'] ?? 'Admin';
$admin_role = $current_admin['role'] ?? 'Administrator';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard – TechInHausa CMS</title>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js for analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fb;
            padding: 0;
            color: #333;
        }
        
        .dashboard-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar - Using TechInHausa colors */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #031837 0%, #02122b 100%);
            color: white;
            padding: 2rem 1.5rem;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 100;
        }
        
        .sidebar-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(211, 201, 254, 0.2);
        }
        
        .sidebar-header h2 {
            font-size: 1.5rem;
            margin-bottom: 0.25rem;
            color: #D3C9FE;
        }
        
        .sidebar-header p {
            font-size: 0.85rem;
            opacity: 0.8;
        }
        
        .admin-info {
            background: rgba(211, 201, 254, 0.1);
            padding: 1.5rem 1rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            text-align: center;
            border: 1px solid rgba(211, 201, 254, 0.2);
        }
        
        .admin-name {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
        
        .admin-role {
            font-size: 0.85rem;
            background: #D3C9FE;
            color: #031837;
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
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
            border-left: 3px solid #D3C9FE;
        }
        
        .nav-link i {
            width: 20px;
            font-size: 1.1rem;
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
            letter-spacing: 1px;
            opacity: 0.5;
            padding-left: 1rem;
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
        
        .date-display {
            background: #f0f4ff;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            color: #031837;
            font-weight: 500;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
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
            gap: 1.5rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(211, 201, 254, 0.2);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #031837 0%, #0a2a4a 100%);
            color: #D3C9FE;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }
        
        .stat-content {
            flex: 1;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #031837;
            line-height: 1.2;
        }
        
        .stat-sub {
            font-size: 0.85rem;
            color: #999;
            margin-top: 0.25rem;
        }
        
        .stat-link {
            display: inline-block;
            margin-top: 0.5rem;
            color: #031837;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .stat-link:hover {
            color: #D3C9FE;
        }
        
        /* Section Title */
        .section-title {
            font-size: 1.4rem;
            color: #031837;
            margin: 2rem 0 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e0e0e0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .section-title i {
            color: #D3C9FE;
        }
        
        /* Cards Grid */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .dashboard-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        
        .card-icon {
            width: 60px;
            height: 60px;
            background: #f0f4ff;
            color: #031837;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin: 0 auto 1rem;
        }
        
        .card-value {
            font-size: 2rem;
            font-weight: 700;
            color: #031837;
            margin-bottom: 0.25rem;
        }
        
        .card-label {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .card-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: #D3C9FE;
            color: #031837;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .card-btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: #031837;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.9rem;
            transition: background 0.3s ease;
        }
        
        .card-btn:hover {
            background: #0a2a4a;
        }
        
        .card-btn-sm {
            padding: 0.25rem 0.75rem;
            font-size: 0.8rem;
        }
        
        /* Chart Container */
        .chart-container {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        
        .chart-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
        }
        
        /* Recent Activity */
        .recent-activity {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        
        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .activity-header h3 {
            color: #031837;
        }
        
        .activity-list {
            list-style: none;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            background: #f0f4ff;
            color: #031837;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .activity-icon.video { background: #e3f2fd; color: #1976d2; }
        .activity-icon.blog { background: #f3e5f5; color: #7b1fa2; }
        .activity-icon.news { background: #fff3e0; color: #f57c00; }
        .activity-icon.research { background: #e8f5e8; color: #388e3c; }
        .activity-icon.creator { background: #D3C9FE; color: #031837; }
        .activity-icon.contact { background: #ffe0e0; color: #dc3545; }
        .activity-icon.team { background: #cce5ff; color: #004085; }
        
        .activity-details {
            flex: 1;
        }
        
        .activity-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .activity-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.8rem;
            color: #999;
        }
        
        .activity-time {
            font-size: 0.8rem;
            color: #999;
        }
        
        .activity-badge {
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 20px;
            background: #e0e0e0;
            color: #666;
        }
        
        .activity-badge.published { background: #c8e6c9; color: #2e7d32; }
        .activity-badge.draft { background: #ffecb3; color: #b26a00; }
        .activity-badge.unread { background: #ffcdd2; color: #c62828; }
        .activity-badge.read { background: #bbdefb; color: #0d47a1; }
        
        /* Quick Stats */
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .quick-stat {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
        }
        
        .quick-stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #031837;
        }
        
        .quick-stat-label {
            font-size: 0.8rem;
            color: #666;
        }
        
        /* Footer */
        .dashboard-footer {
            margin-top: 3rem;
            padding: 1.5rem;
            background: white;
            border-radius: 12px;
            text-align: center;
            color: #666;
            font-size: 0.9rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .footer-heart {
            color: #D3C9FE;
        }
        
        .mubeetech {
            color: #031837;
            font-weight: 600;
            text-decoration: none;
        }
        
        .mubeetech:hover {
            color: #D3C9FE;
        }
        
        /* Quick Actions */
        .quick-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 2rem;
        }
        
        .action-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: white;
            color: #031837;
            text-decoration: none;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border: 1px solid #e0e0e0;
        }
        
        .action-btn:hover {
            background: #031837;
            color: white;
            transform: translateY(-2px);
        }
        
        .action-btn:hover i {
            color: #D3C9FE;
        }
        
        .action-btn i {
            color: #D3C9FE;
            transition: color 0.3s ease;
        }
        
        /* Most Viewed List */
        .most-viewed-list {
            list-style: none;
        }
        
        .most-viewed-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .most-viewed-item:last-child {
            border-bottom: none;
        }
        
        .most-viewed-title {
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .most-viewed-type {
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            background: #f0f4ff;
            color: #031837;
            border-radius: 20px;
            margin-left: 0.5rem;
        }
        
        .most-viewed-views {
            font-size: 0.9rem;
            font-weight: 600;
            color: #031837;
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
            
            .nav-link i {
                margin: 0;
                font-size: 1.3rem;
            }
            
            .main-content {
                margin-left: 80px;
            }
            
            .chart-row {
                grid-template-columns: 1fr;
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
            
            .cards-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .badge-primary {
            background: #cce5ff;
            color: #004085;
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>TechInHausa</h2>
                <p>Admin Panel</p>
            </div>
            
            <div class="admin-info">
                <div class="admin-name"><?php echo htmlspecialchars($admin_name); ?></div>
                <div class="admin-role">
                    <?php 
                    $role_display = str_replace('_', ' ', $admin_role);
                    echo ucwords($role_display); 
                    ?>
                </div>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-section">Main</li>
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link active">
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
                
                <!-- Creator/MalamIromba Section -->
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
                <!-- Team Members -->
                <li class="nav-item">
                    <a href="team_members.php" class="nav-link">
                        <i class="fas fa-users"></i>
                        <span>Team Members</span>
                        <?php if ($total_team_members_inactive > 0): ?>
                        <span class="nav-badge"><?php echo $total_team_members_inactive; ?> inactive</span>
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
                <!-- Contact Submissions -->
                <li class="nav-item">
                    <a href="contact_submissions.php" class="nav-link">
                        <i class="fas fa-envelope"></i>
                        <span>Contact Messages</span>
                        <?php if ($unread_contact_submissions > 0): ?>
                        <span class="nav-badge"><?php echo $unread_contact_submissions; ?> new</span>
                        <?php endif; ?>
                    </a>
                </li>
                
                <li class="nav-section">Users</li>
                <li class="nav-item">
                    <a href="subscribers.php" class="nav-link">
                        <i class="fas fa-envelope-open-text"></i>
                        <span>Newsletter</span>
                        <span class="nav-badge"><?php echo $total_subscribers; ?></span>
                    </a>
                </li>
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
                    <h1>Dashboard</h1>
                    <p>Welcome back, <?php echo htmlspecialchars($admin_name); ?>! Here's what's happening with TechInHausa.</p>
                </div>
                <div class="date-display">
                    <i class="fas fa-calendar-alt me-2"></i>
                    <?php echo date('l, F j, Y'); ?>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="videos.php?action=add" class="action-btn">
                    <i class="fas fa-plus-circle"></i>
                    <span>New Video</span>
                </a>
                <a href="blog.php?action=add" class="action-btn">
                    <i class="fas fa-plus-circle"></i>
                    <span>New Blog Post</span>
                </a>
                <a href="news.php?action=add" class="action-btn">
                    <i class="fas fa-plus-circle"></i>
                    <span>New News</span>
                </a>
                <a href="research.php?action=add" class="action-btn">
                    <i class="fas fa-plus-circle"></i>
                    <span>New Research</span>
                </a>
                <!-- Quick action for Creator -->
                <a href="creator.php?action=add" class="action-btn">
                    <i class="fas fa-plus-circle"></i>
                    <span>New Creator Item</span>
                </a>
                <!-- Quick action for Team Members -->
                <a href="team_members.php?action=add" class="action-btn">
                    <i class="fas fa-plus-circle"></i>
                    <span>Add Team Member</span>
                </a>
            </div>
            
            <!-- Key Statistics Cards -->
            <div class="stats-grid">
                <!-- Total Content -->
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Total Content</div>
                        <div class="stat-value"><?php echo $total_content; ?></div>
                        <div class="stat-sub">
                            <span class="text-success"><?php echo $total_content; ?> published</span> | 
                            <span class="text-warning"><?php echo $total_drafts; ?> drafts</span>
                        </div>
                    </div>
                </div>
                
                <!-- Total Views -->
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Total Views</div>
                        <div class="stat-value"><?php echo number_format($total_views); ?></div>
                        <div class="stat-sub">Across all content</div>
                    </div>
                </div>
                
                <!-- Contact Submissions -->
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Contact Messages</div>
                        <div class="stat-value"><?php echo $total_contact_submissions; ?></div>
                        <div class="stat-sub">
                            <span class="text-warning"><?php echo $unread_contact_submissions; ?> unread</span> | 
                            <span class="text-info"><?php echo $unreplied_contact_submissions; ?> unreplied</span>
                        </div>
                    </div>
                </div>
                
                <!-- Team Members -->
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Team Members</div>
                        <div class="stat-value"><?php echo $total_team_members; ?></div>
                        <div class="stat-sub">
                            <span class="text-success"><?php echo $total_team_members; ?> active</span>
                            <?php if ($total_team_members_inactive > 0): ?>
                            | <span class="text-muted"><?php echo $total_team_members_inactive; ?> inactive</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Content Type Cards -->
            <h2 class="section-title">
                <i class="fas fa-chart-pie"></i> Content Overview
            </h2>
            
            <div class="cards-grid">
                <div class="dashboard-card">
                    <div class="card-icon">
                        <i class="fas fa-play-circle"></i>
                    </div>
                    <div class="card-value"><?php echo $total_videos; ?></div>
                    <div class="card-label">Videos</div>
                    <?php if ($total_videos_draft > 0): ?>
                    <div class="card-badge"><?php echo $total_videos_draft; ?> drafts</div>
                    <?php endif; ?>
                    <a href="videos.php" class="card-btn card-btn-sm">Manage</a>
                </div>
                
                <div class="dashboard-card">
                    <div class="card-icon">
                        <i class="fas fa-blog"></i>
                    </div>
                    <div class="card-value"><?php echo $total_blog; ?></div>
                    <div class="card-label">Blog Posts</div>
                    <?php if ($total_blog_draft > 0): ?>
                    <div class="card-badge"><?php echo $total_blog_draft; ?> drafts</div>
                    <?php endif; ?>
                    <a href="blog.php" class="card-btn card-btn-sm">Manage</a>
                </div>
                
                <div class="dashboard-card">
                    <div class="card-icon">
                        <i class="fas fa-newspaper"></i>
                    </div>
                    <div class="card-value"><?php echo $total_news; ?></div>
                    <div class="card-label">News</div>
                    <?php if ($total_news_draft > 0): ?>
                    <div class="card-badge"><?php echo $total_news_draft; ?> drafts</div>
                    <?php endif; ?>
                    <a href="news.php" class="card-btn card-btn-sm">Manage</a>
                </div>
                
                <div class="dashboard-card">
                    <div class="card-icon">
                        <i class="fas fa-flask"></i>
                    </div>
                    <div class="card-value"><?php echo $total_research; ?></div>
                    <div class="card-label">Research</div>
                    <?php if ($total_research_draft > 0): ?>
                    <div class="card-badge"><?php echo $total_research_draft; ?> drafts</div>
                    <?php endif; ?>
                    <a href="research.php" class="card-btn card-btn-sm">Manage</a>
                </div>
                
                <!-- Creator Card -->
                <div class="dashboard-card">
                    <div class="card-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="card-value"><?php echo $total_creator; ?></div>
                    <div class="card-label">MalamIromba</div>
                    <?php if ($total_creator_draft > 0): ?>
                    <div class="card-badge"><?php echo $total_creator_draft; ?> drafts</div>
                    <?php endif; ?>
                    <a href="creator.php" class="card-btn card-btn-sm">Manage</a>
                </div>
            </div>
            
            <!-- Secondary Stats Cards -->
            <div class="cards-grid">
                <div class="dashboard-card">
                    <div class="card-icon">
                        <i class="fas fa-folder"></i>
                    </div>
                    <div class="card-value"><?php echo $total_categories; ?></div>
                    <div class="card-label">Categories</div>
                    <a href="categories.php" class="card-btn card-btn-sm">Manage</a>
                </div>
                
                <div class="dashboard-card">
                    <div class="card-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="card-value"><?php echo $total_tags; ?></div>
                    <div class="card-label">Tags</div>
                    <a href="tags.php" class="card-btn card-btn-sm">Manage</a>
                </div>
                
                <div class="dashboard-card">
                    <div class="card-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="card-value"><?php echo $total_media_features; ?></div>
                    <div class="card-label">Media Features</div>
                    <a href="media_features.php" class="card-btn card-btn-sm">Manage</a>
                </div>
                
                <div class="dashboard-card">
                    <div class="card-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <div class="card-value"><?php echo $total_sponsors; ?></div>
                    <div class="card-label">Sponsors</div>
                    <div class="card-badge"><?php echo $active_sponsors; ?> active</div>
                    <a href="sponsors.php" class="card-btn card-btn-sm">Manage</a>
                </div>
            </div>
            
            <!-- Team & Communications Cards -->
            <h2 class="section-title">
                <i class="fas fa-users"></i> Team & Communications
            </h2>
            
            <div class="cards-grid">
                <!-- Team Members Card -->
                <div class="dashboard-card">
                    <div class="card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="card-value"><?php echo $total_team_members; ?></div>
                    <div class="card-label">Team Members</div>
                    <?php if ($total_team_members_inactive > 0): ?>
                    <div class="card-badge"><?php echo $total_team_members_inactive; ?> inactive</div>
                    <?php endif; ?>
                    <a href="team_members.php" class="card-btn card-btn-sm">Manage</a>
                </div>
                
                <!-- Contact Submissions Card -->
                <div class="dashboard-card">
                    <div class="card-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="card-value"><?php echo $total_contact_submissions; ?></div>
                    <div class="card-label">Contact Messages</div>
                    <?php if ($unread_contact_submissions > 0): ?>
                    <div class="card-badge"><?php echo $unread_contact_submissions; ?> unread</div>
                    <?php endif; ?>
                    <a href="contact_submissions.php" class="card-btn card-btn-sm">Manage</a>
                </div>
                
                <!-- Subscribers Card -->
                <div class="dashboard-card">
                    <div class="card-icon">
                        <i class="fas fa-envelope-open-text"></i>
                    </div>
                    <div class="card-value"><?php echo $total_subscribers; ?></div>
                    <div class="card-label">Newsletter</div>
                    <div class="card-badge"><?php echo $active_subscribers; ?> active</div>
                    <a href="subscribers.php" class="card-btn card-btn-sm">Manage</a>
                </div>
                
                <!-- Admin Users Card -->
                <div class="dashboard-card">
                    <div class="card-icon">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <div class="card-value"><?php echo $total_users; ?></div>
                    <div class="card-label">Admin Users</div>
                    <a href="users.php" class="card-btn card-btn-sm">Manage</a>
                </div>
            </div>
            
            <!-- Chart Section -->
            <div class="chart-row">
                <!-- Content Creation Chart -->
                <div class="chart-container">
                    <h3 style="margin-bottom: 1rem; color: #031837;">Content Creation (Last 6 Months)</h3>
                    <canvas id="contentChart" style="width:100%; max-height:300px;"></canvas>
                </div>
                
                <!-- Most Viewed Content -->
                <div class="chart-container">
                    <h3 style="margin-bottom: 1rem; color: #031837;">Most Viewed Content</h3>
                    <ul class="most-viewed-list">
                        <?php 
                        if (!empty($most_viewed)):
                            foreach ($most_viewed as $item):
                        ?>
                        <li class="most-viewed-item">
                            <div>
                                <span class="most-viewed-title"><?php echo htmlspecialchars(substr($item['title'], 0, 30)) . '...'; ?></span>
                                <span class="most-viewed-type"><?php echo $item['content_type']; ?></span>
                            </div>
                            <span class="most-viewed-views"><?php echo number_format($item['views']); ?></span>
                        </li>
                        <?php 
                            endforeach;
                        else:
                        ?>
                        <li class="most-viewed-item">
                            <span>No content views yet</span>
                        </li>
                        <?php endif; ?>
                    </ul>
                    
                    <div class="quick-stats" style="margin-top: 1rem;">
                        <div class="quick-stat">
                            <div class="quick-stat-value"><?php echo $total_categories; ?></div>
                            <div class="quick-stat-label">Categories</div>
                        </div>
                        <div class="quick-stat">
                            <div class="quick-stat-value"><?php echo $total_tags; ?></div>
                            <div class="quick-stat-label">Tags</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity Row -->
            <div class="chart-row">
                <!-- Recent Content -->
                <div class="recent-activity">
                    <div class="activity-header">
                        <h3><i class="fas fa-clock me-2"></i>Recent Content</h3>
                        <a href="#" style="color: #031837;">View All</a>
                    </div>
                    
                    <ul class="activity-list">
                        <?php 
                        if (!empty($recent_content)):
                            foreach ($recent_content as $item):
                                $icon_class = $item['content_type'];
                                $status_class = $item['is_published'] ? 'published' : 'draft';
                                $status_text = $item['is_published'] ? 'published' : 'draft';
                        ?>
                        <li class="activity-item">
                            <div class="activity-icon <?php echo $icon_class; ?>">
                                <?php 
                                $icons = [
                                    'video' => 'fa-play-circle',
                                    'blog' => 'fa-blog',
                                    'news' => 'fa-newspaper',
                                    'research' => 'fa-flask',
                                    'creator' => 'fa-user-graduate'
                                ];
                                $icon = $icons[$item['content_type']] ?? 'fa-file';
                                ?>
                                <i class="fas <?php echo $icon; ?>"></i>
                            </div>
                            <div class="activity-details">
                                <div class="activity-title">
                                    <?php echo htmlspecialchars(substr($item['title'], 0, 50)) . (strlen($item['title']) > 50 ? '...' : ''); ?>
                                </div>
                                <div class="activity-meta">
                                    <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($item['author'] ?? 'Admin'); ?></span>
                                    <span><i class="far fa-clock"></i> <?php echo time_ago($item['created_at']); ?></span>
                                </div>
                            </div>
                            <span class="activity-badge <?php echo $status_class; ?>">
                                <?php echo $status_text; ?>
                            </span>
                        </li>
                        <?php 
                            endforeach;
                        else:
                        ?>
                        <li class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <div class="activity-details">
                                <div class="activity-title">No recent content</div>
                                <div class="activity-time">Start creating content</div>
                            </div>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <!-- Recent Contact Submissions -->
                <div class="recent-activity">
                    <div class="activity-header">
                        <h3><i class="fas fa-envelope me-2"></i>Recent Contact Messages</h3>
                        <a href="contact_submissions.php" style="color: #031837;">View All</a>
                    </div>
                    
                    <ul class="activity-list">
                        <?php 
                        if ($recent_contacts && mysqli_num_rows($recent_contacts) > 0):
                            while ($contact = mysqli_fetch_assoc($recent_contacts)):
                                $read_class = $contact['is_read'] ? 'read' : 'unread';
                                $read_text = $contact['is_read'] ? 'read' : 'unread';
                        ?>
                        <li class="activity-item">
                            <div class="activity-icon contact">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="activity-details">
                                <div class="activity-title">
                                    <?php echo htmlspecialchars($contact['subject']); ?>
                                </div>
                                <div class="activity-meta">
                                    <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($contact['name']); ?></span>
                                    <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($contact['email']); ?></span>
                                    <span><i class="far fa-clock"></i> <?php echo time_ago($contact['created_at']); ?></span>
                                </div>
                            </div>
                            <span class="activity-badge <?php echo $read_class; ?>">
                                <?php echo $read_text; ?>
                            </span>
                        </li>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <li class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <div class="activity-details">
                                <div class="activity-title">No contact messages</div>
                                <div class="activity-time">Messages will appear here</div>
                            </div>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            
            <!-- Recent Subscribers Row -->
            <div class="chart-row">
                <!-- Recent Subscribers -->
                <div class="recent-activity">
                    <div class="activity-header">
                        <h3><i class="fas fa-users me-2"></i>Recent Subscribers</h3>
                        <a href="subscribers.php" style="color: #031837;">View All</a>
                    </div>
                    
                    <ul class="activity-list">
                        <?php 
                        if ($recent_subscribers && mysqli_num_rows($recent_subscribers) > 0):
                            while ($sub = mysqli_fetch_assoc($recent_subscribers)):
                        ?>
                        <li class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div class="activity-details">
                                <div class="activity-title">
                                    <?php echo htmlspecialchars($sub['email']); ?>
                                </div>
                                <div class="activity-meta">
                                    <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($sub['name'] ?? 'No name'); ?></span>
                                    <span><i class="far fa-clock"></i> <?php echo time_ago($sub['subscribed_at']); ?></span>
                                </div>
                            </div>
                        </li>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <li class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <div class="activity-details">
                                <div class="activity-title">No recent subscribers</div>
                                <div class="activity-time">Promote your newsletter</div>
                            </div>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <!-- Quick Stats Placeholder -->
                <div class="recent-activity">
                    <div class="activity-header">
                        <h3><i class="fas fa-chart-simple me-2"></i>Quick Stats</h3>
                    </div>
                    
                    <div class="quick-stats">
                        <div class="quick-stat">
                            <div class="quick-stat-value"><?php echo $total_team_members; ?></div>
                            <div class="quick-stat-label">Team Members</div>
                        </div>
                        <div class="quick-stat">
                            <div class="quick-stat-value"><?php echo $total_contact_submissions; ?></div>
                            <div class="quick-stat-label">Contact Messages</div>
                        </div>
                        <div class="quick-stat">
                            <div class="quick-stat-value"><?php echo $unread_contact_submissions; ?></div>
                            <div class="quick-stat-label">Unread</div>
                        </div>
                        <div class="quick-stat">
                            <div class="quick-stat-value"><?php echo $total_subscribers; ?></div>
                            <div class="quick-stat-label">Subscribers</div>
                        </div>
                    </div>
                    
                    <div style="margin-top: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                        <p style="color: #666; margin-bottom: 0.5rem;"><i class="fas fa-info-circle" style="color: #D3C9FE;"></i> Team members help build trust with your audience.</p>
                        <a href="team_members.php" style="color: #031837; text-decoration: none; font-weight: 600;">Manage Team →</a>
                    </div>
                </div>
            </div>
            
            <!-- Footer with Mubeetech credit -->
            <div class="dashboard-footer">
                <p>
                    &copy; <?php echo date('Y'); ?> TechInHausa CMS. All rights reserved.
                    <br>
                    Developed by 
                    <a href="https://mubeetech.com.ng" target="_blank" class="mubeetech">Mubeetech</a>
                    for MalamIromba
                </p>
            </div>
        </main>
    </div>
    
    <!-- Chart.js Scripts -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Content Creation Chart
        const ctx = document.getElementById('contentChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($monthly_data['labels']); ?>,
                datasets: [{
                    label: 'Content Created',
                    data: <?php echo json_encode($monthly_data['values']); ?>,
                    borderColor: '#031837',
                    backgroundColor: 'rgba(211, 201, 254, 0.2)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#D3C9FE',
                    pointBorderColor: '#031837',
                    pointRadius: 5
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    });
    </script>
</body>
</html>