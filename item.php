<?php
// item.php - Single Creator Content Page
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Get item ID or slug
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$slug = isset($_GET['slug']) ? sanitize($_GET['slug']) : '';

if ($id <= 0 && empty($slug)) {
    header('Location: creator.php');
    exit();
}

// Build query based on ID or slug
if ($id > 0) {
    $where = "id = $id";
} else {
    $slug = mysqli_real_escape_string($conn, $slug);
    $where = "slug = '$slug'";
}

// Get item details
$query = "SELECT * FROM creator WHERE $where AND is_published = 1 LIMIT 1";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) === 0) {
    header('Location: creator.php');
    exit();
}

$item = mysqli_fetch_assoc($result);

// Update view count
mysqli_query($conn, "UPDATE creator SET views = views + 1 WHERE id = {$item['id']}");

// Get related items (same category or content type)
$related_query = "
    SELECT * FROM creator 
    WHERE id != {$item['id']} 
    AND is_published = 1 
    AND (category = '{$item['category']}' OR content_type = '{$item['content_type']}')
    ORDER BY 
        CASE 
            WHEN is_featured = 1 THEN 0 
            ELSE 1 
        END,
        published_at DESC 
    LIMIT 3
";
$related_result = mysqli_query($conn, $related_query);

// Get creator stats for sidebar
$stats_query = "
    SELECT 
        COUNT(*) as total_items,
        SUM(CASE WHEN content_type = 'video' THEN 1 ELSE 0 END) as total_videos,
        SUM(views) as total_views,
        AVG(views) as avg_views
    FROM creator 
    WHERE is_published = 1
";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

$pageTitle = $item['title'] . " - MalamIromba | TechInHausa";
$pageDesc = $item['excerpt'] ?? substr(strip_tags($item['content']), 0, 160);

include 'partials/header.php';
?>

<article class="creator-single">
    <div class="container">
        <!-- Back to Creator -->
        <div class="back-to-creator">
            <a href="creator.php" class="back-link">
                <i class="fas fa-arrow-left"></i> View All MalamIromba Content
            </a>
        </div>
        
        <div class="creator-single-wrapper">
            <!-- Main Content -->
            <div class="creator-single-main">
                <!-- Content Type Header -->
                <div class="content-type-header <?= $item['content_type'] ?>">
                    <?php 
                    $type_icons = [
                        'video' => 'fa-play-circle',
                        'blog' => 'fa-blog',
                        'publication' => 'fa-file-pdf',
                        'tutorial' => 'fa-graduation-cap',
                        'course' => 'fa-book-open'
                    ];
                    $type_labels = [
                        'video' => 'Video',
                        'blog' => 'Article',
                        'publication' => 'Publication',
                        'tutorial' => 'Tutorial',
                        'course' => 'Course'
                    ];
                    ?>
                    <div class="content-type-icon">
                        <i class="fas <?= $type_icons[$item['content_type']] ?? 'fa-file' ?>"></i>
                    </div>
                    <div class="content-type-info">
                        <span class="content-type-label"><?= $type_labels[$item['content_type']] ?? ucfirst($item['content_type']) ?></span>
                        <?php if (!empty($item['category'])): ?>
                            <span class="content-category"><?= htmlspecialchars($item['category']) ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($item['is_featured']): ?>
                        <span class="featured-tag">
                            <i class="fas fa-star"></i> Featured
                        </span>
                    <?php endif; ?>
                </div>
                
                <!-- Title -->
                <h1 class="creator-single-title"><?= htmlspecialchars($item['title']) ?></h1>
                
                <!-- Meta Information -->
                <div class="creator-single-meta">
                    <span class="meta-item">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($item['author'] ?? 'MalamIromba') ?>
                    </span>
                    <span class="meta-item">
                        <i class="far fa-calendar-alt"></i> <?= formatDate($item['published_at'] ?? $item['created_at']) ?>
                    </span>
                    <span class="meta-item">
                        <i class="fas fa-eye"></i> <?= number_format($item['views'] + 1) ?> views
                    </span>
                    
                    <?php if ($item['content_type'] == 'video' && !empty($item['video_duration'])): ?>
                        <span class="meta-item">
                            <i class="fas fa-clock"></i> <?= $item['video_duration'] ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <!-- Featured Image (if not video) -->
                <?php if ($item['content_type'] != 'video' && !empty($item['featured_image'])): ?>
                    <div class="creator-featured-image">
                        <img src="<?= getImageUrl($item['featured_image'], 'creator') ?>" 
                             alt="<?= htmlspecialchars($item['title']) ?>"
                             onerror="this.src='<?= SITE_URL ?>/assets/images/techinhausa-about.jpg'">
                    </div>
                <?php endif; ?>
                
                <!-- Video Embed (if video) -->
                <?php if ($item['content_type'] == 'video' && !empty($item['video_url'])): ?>
                    <div class="video-embed-container">
                        <?php 
                        $video_id = getYouTubeId($item['video_url']);
                        if ($video_id): 
                        ?>
                            <iframe 
                                src="https://www.youtube.com/embed/<?= $video_id ?>" 
                                title="<?= htmlspecialchars($item['title']) ?>"
                                frameborder="0" 
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                allowfullscreen>
                            </iframe>
                        <?php else: ?>
                            <div class="video-link-container">
                                <a href="<?= $item['video_url'] ?>" target="_blank" class="video-external-link">
                                    <i class="fab fa-youtube"></i> Watch on YouTube
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Excerpt (if exists) -->
                <?php if (!empty($item['excerpt'])): ?>
                    <div class="creator-excerpt">
                        <p><?= nl2br(htmlspecialchars($item['excerpt'])) ?></p>
                    </div>
                <?php endif; ?>
                
                <!-- Full Content -->
                <?php if (!empty($item['content'])): ?>
                    <div class="creator-content">
                        <?= nl2br(htmlspecialchars($item['content'])) ?>
                    </div>
                <?php endif; ?>
                
                <!-- File Download (for publications/tutorials) -->
                <?php if (($item['content_type'] == 'publication' || $item['content_type'] == 'tutorial') && !empty($item['file_url'])): ?>
                    <div class="file-download-section">
                        <h3>Download Publication</h3>
                        <a href="<?= $item['file_url'] ?>" target="_blank" class="download-btn">
                            <i class="fas fa-file-pdf"></i> Download PDF
                        </a>
                    </div>
                <?php endif; ?>
                
                <!-- Creator Stats in Content -->
                <?php if (!empty($item['years_active']) || !empty($item['projects_count']) || !empty($item['students_count'])): ?>
                    <div class="creator-stats-box">
                        <h3>MalamIromba's Stats</h3>
                        <div class="stats-grid">
                            <?php if (!empty($item['years_active'])): ?>
                                <div class="stat-box">
                                    <span class="stat-box-number"><?= $item['years_active'] ?></span>
                                    <span class="stat-box-label">Years Active</span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($item['projects_count'])): ?>
                                <div class="stat-box">
                                    <span class="stat-box-number"><?= $item['projects_count'] ?>+</span>
                                    <span class="stat-box-label">Projects</span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($item['students_count'])): ?>
                                <div class="stat-box">
                                    <span class="stat-box-number"><?= $item['students_count'] ?></span>
                                    <span class="stat-box-label">Students</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Share Buttons -->
                <div class="share-section">
                    <h3>Share this:</h3>
                    <div class="share-buttons">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(SITE_URL . '/item.php?id=' . $item['id']) ?>" 
                           target="_blank" class="share-btn facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?= urlencode(SITE_URL . '/item.php?id=' . $item['id']) ?>&text=<?= urlencode($item['title']) ?>" 
                           target="_blank" class="share-btn twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="https://wa.me/?text=<?= urlencode($item['title'] . ' ' . SITE_URL . '/item.php?id=' . $item['id']) ?>" 
                           target="_blank" class="share-btn whatsapp">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                        <a href="https://t.me/share/url?url=<?= urlencode(SITE_URL . '/item.php?id=' . $item['id']) ?>&text=<?= urlencode($item['title']) ?>" 
                           target="_blank" class="share-btn telegram">
                            <i class="fab fa-telegram-plane"></i>
                        </a>
                        <a href="mailto:?subject=<?= urlencode($item['title']) ?>&body=<?= urlencode($item['title'] . ' - ' . SITE_URL . '/item.php?id=' . $item['id']) ?>" 
                           class="share-btn email">
                            <i class="fas fa-envelope"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <aside class="creator-single-sidebar">
                <!-- Creator Profile Widget -->
                <div class="sidebar-widget creator-profile-widget">
                    <div class="widget-creator-avatar">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h3 class="widget-creator-name">MalamIromba</h3>
                    <p class="widget-creator-bio">Ibrahim Zubairu - Founder & Tech Educator</p>
                    
                    <div class="widget-creator-stats">
                        <div class="widget-stat">
                            <span class="widget-stat-number"><?= number_format($stats['total_items'] ?? 0) ?></span>
                            <span class="widget-stat-label">Items</span>
                        </div>
                        <div class="widget-stat">
                            <span class="widget-stat-number"><?= number_format($stats['total_videos'] ?? 0) ?></span>
                            <span class="widget-stat-label">Videos</span>
                        </div>
                        <div class="widget-stat">
                            <span class="widget-stat-number"><?= number_format($stats['total_views'] ?? 0) ?></span>
                            <span class="widget-stat-label">Views</span>
                        </div>
                    </div>
                    
                    <a href="creator.php" class="widget-view-all">
                        View All Content <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <!-- Related Items -->
                <?php if ($related_result && mysqli_num_rows($related_result) > 0): ?>
                    <div class="sidebar-widget related-widget">
                        <h3 class="widget-title">Related Items</h3>
                        <div class="related-items-list">
                            <?php while ($related = mysqli_fetch_assoc($related_result)): ?>
                                <div class="related-item">
                                    <a href="item.php?id=<?= $related['id'] ?>&slug=<?= $related['slug'] ?>" class="related-item-link">
                                        <div class="related-item-image">
                                            <img src="<?= getImageUrl($related['featured_image'] ?? '', 'creator') ?>" 
                                                 alt="<?= htmlspecialchars($related['title']) ?>">
                                            <span class="related-item-type <?= $related['content_type'] ?>">
                                                <i class="fas <?= $type_icons[$related['content_type']] ?? 'fa-file' ?>"></i>
                                            </span>
                                        </div>
                                        <div class="related-item-content">
                                            <h4><?= htmlspecialchars($related['title']) ?></h4>
                                            <span class="related-item-date">
                                                <i class="far fa-calendar-alt"></i> 
                                                <?= formatDate($related['published_at'] ?? $related['created_at']) ?>
                                            </span>
                                        </div>
                                    </a>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Newsletter Widget -->
                <div class="sidebar-widget newsletter-widget">
                    <h3 class="widget-title">Join Our Newsletter</h3>
                    <p>Get new MalamIromba content delivered straight to your inbox</p>
                    <form id="sidebarNewsletter" class="sidebar-newsletter">
                        <input type="email" name="email" placeholder="Your email" required>
                        <button type="submit"><i class="fas fa-paper-plane"></i></button>
                    </form>
                </div>
            </aside>
        </div>
    </div>
</article>

<style>
/* Creator Single Styles */
.creator-single {
    padding: 60px 0;
    background: #f5f7fb;
}

/* Back Link */
.back-to-creator {
    margin-bottom: 30px;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #031837;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s;
}

.back-link:hover {
    color: #D3C9FE;
    transform: translateX(-5px);
}

/* Creator Single Wrapper */
.creator-single-wrapper {
    display: grid;
    grid-template-columns: 2.5fr 1fr;
    gap: 40px;
}

/* Main Content */
.creator-single-main {
    background: white;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    border: 1px solid rgba(211, 201, 254, 0.2);
}

/* Content Type Header */
.content-type-header {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px 20px;
    border-radius: 12px;
    margin-bottom: 30px;
    color: white;
}

.content-type-header.video {
    background: linear-gradient(135deg, #ff6b6b, #ee5253);
}

.content-type-header.blog {
    background: linear-gradient(135deg, #4ecdc4, #45b7aa);
}

.content-type-header.publication {
    background: linear-gradient(135deg, #feca57, #ff9f43);
    color: #031837;
}

.content-type-header.tutorial {
    background: linear-gradient(135deg, #54a0ff, #2e86de);
}

.content-type-header.course {
    background: linear-gradient(135deg, #5f27cd, #341f97);
}

.content-type-icon {
    width: 50px;
    height: 50px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.content-type-icon i {
    font-size: 1.5rem;
}

.content-type-info {
    flex: 1;
}

.content-type-label {
    display: block;
    font-size: 0.9rem;
    opacity: 0.9;
    margin-bottom: 3px;
}

.content-category {
    display: block;
    font-weight: 600;
    font-size: 1.1rem;
}

.featured-tag {
    background: rgba(255, 255, 255, 0.2);
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    gap: 5px;
}

/* Title */
.creator-single-title {
    font-size: 2.2rem;
    color: #031837;
    margin-bottom: 20px;
    line-height: 1.3;
}

/* Meta */
.creator-single-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.meta-item {
    color: #666;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 5px;
}

.meta-item i {
    color: #D3C9FE;
}

/* Featured Image */
.creator-featured-image {
    margin-bottom: 30px;
    border-radius: 15px;
    overflow: hidden;
}

.creator-featured-image img {
    width: 100%;
    height: auto;
    display: block;
}

/* Video Embed */
.video-embed-container {
    position: relative;
    padding-bottom: 56.25%; /* 16:9 Aspect Ratio */
    height: 0;
    overflow: hidden;
    margin-bottom: 30px;
    border-radius: 15px;
}

.video-embed-container iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border: none;
}

.video-link-container {
    text-align: center;
    padding: 40px;
    background: #f8f9ff;
    border-radius: 15px;
}

.video-external-link {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 15px 30px;
    background: #ff0000;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s;
}

.video-external-link:hover {
    background: #cc0000;
    transform: translateY(-2px);
}

.video-external-link i {
    font-size: 1.5rem;
}

/* Excerpt */
.creator-excerpt {
    background: #f8f9ff;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 30px;
    border-left: 4px solid #D3C9FE;
    font-size: 1.1rem;
    color: #555;
    font-style: italic;
}

/* Content */
.creator-content {
    font-size: 1.1rem;
    line-height: 1.9;
    color: #333;
    margin-bottom: 30px;
}

.creator-content p {
    margin-bottom: 25px;
}

.creator-content h2,
.creator-content h3 {
    color: #031837;
    margin: 30px 0 15px;
}

.creator-content ul,
.creator-content ol {
    margin: 20px 0;
    padding-left: 30px;
}

.creator-content li {
    margin-bottom: 10px;
}

.creator-content blockquote {
    background: #f8f9ff;
    border-left: 4px solid #D3C9FE;
    padding: 20px 30px;
    font-style: italic;
    margin: 30px 0;
    border-radius: 0 10px 10px 0;
}

/* File Download */
.file-download-section {
    background: #f8f9ff;
    padding: 30px;
    border-radius: 12px;
    margin: 30px 0;
    text-align: center;
}

.file-download-section h3 {
    color: #031837;
    margin-bottom: 20px;
}

.download-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 15px 30px;
    background: #dc3545;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s;
}

.download-btn:hover {
    background: #c82333;
    transform: translateY(-2px);
}

/* Creator Stats Box */
.creator-stats-box {
    background: #f8f9ff;
    padding: 30px;
    border-radius: 12px;
    margin: 30px 0;
}

.creator-stats-box h3 {
    color: #031837;
    margin-bottom: 20px;
    text-align: center;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

.stat-box {
    text-align: center;
}

.stat-box-number {
    display: block;
    font-size: 2rem;
    font-weight: 700;
    color: #031837;
    margin-bottom: 5px;
}

.stat-box-label {
    color: #666;
    font-size: 0.9rem;
}

/* Share Section */
.share-section {
    margin-top: 40px;
    padding-top: 30px;
    border-top: 1px solid #eee;
}

.share-section h3 {
    color: #031837;
    font-size: 1rem;
    margin-bottom: 15px;
}

.share-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.share-btn {
    width: 45px;
    height: 45px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    color: white;
    transition: all 0.3s;
}

.share-btn:hover {
    transform: translateY(-3px) scale(1.1);
}

.share-btn.facebook { background: #1877f2; }
.share-btn.twitter { background: #1da1f2; }
.share-btn.whatsapp { background: #25d366; }
.share-btn.telegram { background: #0088cc; }
.share-btn.email { background: #666; }

/* Sidebar */
.creator-single-sidebar {
    position: sticky;
    top: 100px;
    align-self: start;
}

.sidebar-widget {
    background: white;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    border: 1px solid rgba(211, 201, 254, 0.2);
}

.widget-title {
    color: #031837;
    font-size: 1.2rem;
    margin-bottom: 20px;
    position: relative;
    padding-bottom: 10px;
}

.widget-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 40px;
    height: 3px;
    background: #D3C9FE;
    border-radius: 2px;
}

/* Creator Profile Widget */
.creator-profile-widget {
    text-align: center;
}

.widget-creator-avatar {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, #031837, #0a2a4a);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    border: 3px solid #D3C9FE;
}

.widget-creator-avatar i {
    font-size: 3rem;
    color: #D3C9FE;
}

.widget-creator-name {
    color: #031837;
    font-size: 1.3rem;
    margin-bottom: 5px;
}

.widget-creator-bio {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 20px;
}

.widget-creator-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    margin-bottom: 20px;
    padding: 15px 0;
    border-top: 1px solid #eee;
    border-bottom: 1px solid #eee;
}

.widget-stat-number {
    display: block;
    font-size: 1.2rem;
    font-weight: 700;
    color: #031837;
}

.widget-stat-label {
    font-size: 0.7rem;
    color: #666;
    text-transform: uppercase;
}

.widget-view-all {
    display: inline-block;
    color: #031837;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s;
}

.widget-view-all:hover {
    color: #D3C9FE;
    transform: translateX(5px);
}

.widget-view-all i {
    margin-left: 5px;
}

/* Related Items */
.related-items-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.related-item-link {
    display: flex;
    gap: 12px;
    text-decoration: none;
    transition: all 0.3s;
}

.related-item-link:hover {
    transform: translateX(5px);
}

.related-item-image {
    position: relative;
    width: 70px;
    height: 50px;
    border-radius: 6px;
    overflow: hidden;
    flex-shrink: 0;
}

.related-item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.related-item-type {
    position: absolute;
    top: 2px;
    right: 2px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.6rem;
}

.related-item-type.video { background: #ff6b6b; color: white; }
.related-item-type.blog { background: #4ecdc4; color: white; }
.related-item-type.publication { background: #feca57; color: #031837; }
.related-item-type.tutorial { background: #54a0ff; color: white; }

.related-item-content {
    flex: 1;
}

.related-item-content h4 {
    color: #031837;
    font-size: 0.9rem;
    margin-bottom: 3px;
    line-height: 1.4;
}

.related-item-date {
    color: #666;
    font-size: 0.7rem;
    display: flex;
    align-items: center;
    gap: 3px;
}

.related-item-date i {
    color: #D3C9FE;
}

/* Newsletter Widget */
.newsletter-widget p {
    color: #666;
    margin-bottom: 15px;
    font-size: 0.9rem;
}

.sidebar-newsletter {
    display: flex;
    gap: 10px;
}

.sidebar-newsletter input {
    flex: 1;
    padding: 12px;
    border: 1px solid rgba(211, 201, 254, 0.3);
    border-radius: 8px;
    font-size: 0.9rem;
}

.sidebar-newsletter input:focus {
    outline: none;
    border-color: #D3C9FE;
}

.sidebar-newsletter button {
    width: 45px;
    height: 45px;
    background: #031837;
    border: none;
    border-radius: 8px;
    color: white;
    cursor: pointer;
    transition: all 0.3s;
}

.sidebar-newsletter button:hover {
    background: #D3C9FE;
    color: #031837;
    transform: translateY(-2px);
}

/* Responsive */
@media (max-width: 992px) {
    .creator-single-wrapper {
        grid-template-columns: 1fr;
    }
    
    .creator-single-sidebar {
        position: static;
    }
    
    .creator-single-title {
        font-size: 1.8rem;
    }
}

@media (max-width: 768px) {
    .creator-single-main {
        padding: 25px;
    }
    
    .content-type-header {
        flex-wrap: wrap;
    }
    
    .featured-tag {
        margin-left: 0;
        width: 100%;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .creator-single-meta {
        flex-direction: column;
        gap: 10px;
    }
}

@media (max-width: 480px) {
    .share-buttons {
        justify-content: center;
    }
    
    .sidebar-newsletter {
        flex-direction: column;
    }
    
    .sidebar-newsletter button {
        width: 100%;
    }
}
</style>

<script>
// Sidebar Newsletter Form
document.addEventListener('DOMContentLoaded', function() {
    const sidebarForm = document.getElementById('sidebarNewsletter');
    if (sidebarForm) {
        sidebarForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const email = this.querySelector('input[type="email"]').value;
            const button = this.querySelector('button');
            const originalText = button.innerHTML;
            
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            button.disabled = true;
            
            try {
                const response = await fetch('subscribe.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({email, source: 'creator-single'})
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Thank you! Your email has been added to our list.');
                    this.reset();
                } else {
                    alert(result.message || 'An error occurred. Please try again.');
                }
            } catch (error) {
                alert('An error occurred. Please try again.');
            }
            
            button.innerHTML = originalText;
            button.disabled = false;
        });
    }
});
</script>

<?php include 'partials/footer.php'; ?>