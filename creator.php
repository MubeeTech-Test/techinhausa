<?php
// creator.php - MalamIromba Creator Content Listing
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = "MalamIromba - TechInHausa Creator";
$pageDesc = "View all MalamIromba content - videos, publications, tutorials, and other tech learning resources in Hausa.";

// Get filter from URL
$type_filter = isset($_GET['type']) ? sanitize($_GET['type']) : 'all';
$category_filter = isset($_GET['category']) ? sanitize($_GET['category']) : '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 9;
$offset = ($page - 1) * $limit;

// Build WHERE clause based on filters
$where = "WHERE is_published = 1";
if ($type_filter !== 'all') {
    $type_filter = mysqli_real_escape_string($conn, $type_filter);
    $where .= " AND content_type = '$type_filter'";
}
if (!empty($category_filter)) {
    $category_filter = mysqli_real_escape_string($conn, $category_filter);
    $where .= " AND category = '$category_filter'";
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) as count FROM creator $where";
$count_result = mysqli_query($conn, $count_query);
$total_rows = mysqli_fetch_assoc($count_result)['count'];
$total_pages = ceil($total_rows / $limit);

// Get creator content
$query = "
    SELECT * FROM creator 
    $where 
    ORDER BY 
        CASE 
            WHEN is_featured = 1 THEN 0 
            ELSE 1 
        END,
        published_at DESC 
    LIMIT $offset, $limit
";
$result = mysqli_query($conn, $query);

// Get unique content types for filter
$types_query = "
    SELECT DISTINCT content_type, 
           COUNT(*) as count 
    FROM creator 
    WHERE is_published = 1 
    GROUP BY content_type 
    ORDER BY content_type
";
$types_result = mysqli_query($conn, $types_query);

// Get unique categories for filter
$categories_query = "
    SELECT DISTINCT category, 
           COUNT(*) as count 
    FROM creator 
    WHERE is_published = 1 AND category IS NOT NULL AND category != '' 
    GROUP BY category 
    ORDER BY category
";
$categories_result = mysqli_query($conn, $categories_query);

// Get creator stats
$stats_query = "
    SELECT 
        COUNT(*) as total_items,
        SUM(CASE WHEN content_type = 'video' THEN 1 ELSE 0 END) as total_videos,
        SUM(CASE WHEN content_type = 'publication' THEN 1 ELSE 0 END) as total_publications,
        SUM(CASE WHEN content_type = 'tutorial' THEN 1 ELSE 0 END) as total_tutorials,
        SUM(views) as total_views
    FROM creator 
    WHERE is_published = 1
";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

include 'partials/header.php';
?>

<section class="creator-page-section">
    <div class="container">
        <!-- Creator Header with Stats -->
        <div class="creator-header">
            <div class="creator-profile">
                <div class="creator-avatar">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="creator-info">
                    <h1 class="creator-name">MalamIromba</h1>
                    <p class="creator-title">Ibrahim Zubairu - Founder & Tech Educator</p>
                    <div class="creator-bio-short">
                        <p>An educator and programmer who teaches modern technology and AI in the Hausa language to make tech education accessible to the Hausa community.</p>
                    </div>
                </div>
            </div>
            
            <div class="creator-stats">
                <div class="stat-item">
                    <span class="stat-number"><?= number_format($stats['total_items'] ?? 0) ?></span>
                    <span class="stat-label">Total Items</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= number_format($stats['total_videos'] ?? 0) ?></span>
                    <span class="stat-label">Videos</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= number_format($stats['total_publications'] ?? 0) ?></span>
                    <span class="stat-label">Publications</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= number_format($stats['total_views'] ?? 0) ?></span>
                    <span class="stat-label">Views</span>
                </div>
            </div>
        </div>
        
        <!-- Filter Bar -->
        <div class="filter-bar">
            <div class="filter-group">
                <label for="type-filter">Type:</label>
                <select id="type-filter" class="filter-select" onchange="filterByType(this.value)">
                    <option value="all" <?= $type_filter == 'all' ? 'selected' : '' ?>>All</option>
                    <?php 
                    if ($types_result && mysqli_num_rows($types_result) > 0):
                        while ($type = mysqli_fetch_assoc($types_result)): 
                            $type_name = ucfirst($type['content_type']);
                            $type_label = [
                                'video' => 'Video',
                                'blog' => 'Blog',
                                'publication' => 'Publication',
                                'tutorial' => 'Tutorial',
                                'course' => 'Course'
                            ][$type['content_type']] ?? $type_name;
                    ?>
                        <option value="<?= $type['content_type'] ?>" <?= $type_filter == $type['content_type'] ? 'selected' : '' ?>>
                            <?= $type_label ?> (<?= $type['count'] ?>)
                        </option>
                    <?php 
                        endwhile;
                    endif; 
                    ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="category-filter">Category:</label>
                <select id="category-filter" class="filter-select" onchange="filterByCategory(this.value)">
                    <option value="">All</option>
                    <?php 
                    if ($categories_result && mysqli_num_rows($categories_result) > 0):
                        while ($cat = mysqli_fetch_assoc($categories_result)): 
                    ?>
                        <option value="<?= $cat['category'] ?>" <?= $category_filter == $cat['category'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['category']) ?> (<?= $cat['count'] ?>)
                        </option>
                    <?php 
                        endwhile;
                    endif; 
                    ?>
                </select>
            </div>
            
            <div class="filter-group search-group">
                <form action="creator.php" method="GET" class="filter-search">
                    <input type="text" name="search" placeholder="Search..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>
        </div>
        
        <!-- Creator Content Grid -->
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <div class="creator-grid">
                <?php while ($item = mysqli_fetch_assoc($result)): ?>
                    <div class="creator-card <?= $item['content_type'] ?>">
                        <div class="creator-card-image">
                            <a href="<?= SITE_URL ?>/item.php?id=<?= $item['id'] ?>&slug=<?= $item['slug'] ?>">
                                <img src="<?= getImageUrl($item['featured_image'] ?? '', 'creator') ?>" 
                                     alt="<?= htmlspecialchars($item['title']) ?>"
                                     onerror="this.src='<?= SITE_URL ?>/assets/images/techinhausa-about.jpg'">
                                
                                <!-- Content Type Badge -->
                                <span class="content-type-badge <?= $item['content_type'] ?>">
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
                                        'blog' => 'Blog',
                                        'publication' => 'Publication',
                                        'tutorial' => 'Tutorial',
                                        'course' => 'Course'
                                    ];
                                    ?>
                                    <i class="fas <?= $type_icons[$item['content_type']] ?? 'fa-file' ?>"></i>
                                    <span><?= $type_labels[$item['content_type']] ?? ucfirst($item['content_type']) ?></span>
                                </span>
                                
                                <!-- Duration or File Indicator -->
                                <?php if ($item['content_type'] == 'video' && !empty($item['video_duration'])): ?>
                                    <span class="duration-badge">
                                        <i class="fas fa-clock"></i> <?= $item['video_duration'] ?>
                                    </span>
                                <?php elseif (($item['content_type'] == 'publication' || $item['content_type'] == 'tutorial') && !empty($item['file_url'])): ?>
                                    <span class="file-badge">
                                        <i class="fas fa-file-pdf"></i> PDF
                                    </span>
                                <?php endif; ?>
                                
                                <!-- Featured Badge -->
                                <?php if ($item['is_featured']): ?>
                                    <span class="featured-badge">
                                        <i class="fas fa-star"></i>
                                    </span>
                                <?php endif; ?>
                            </a>
                        </div>
                        
                        <div class="creator-card-content">
                            <?php if (!empty($item['category'])): ?>
                                <span class="creator-category">
                                    <i class="fas fa-folder"></i> <?= htmlspecialchars($item['category']) ?>
                                </span>
                            <?php endif; ?>
                            
                            <h3 class="creator-card-title">
                                <a href="<?= SITE_URL ?>/item.php?id=<?= $item['id'] ?>&slug=<?= $item['slug'] ?>">
                                    <?= htmlspecialchars($item['title']) ?>
                                </a>
                            </h3>
                            
                            <p class="creator-card-excerpt">
                                <?= truncateText($item['excerpt'] ?? $item['content'] ?? '', 100) ?>
                            </p>
                            
                            <div class="creator-card-meta">
                                <span class="creator-date">
                                    <i class="far fa-calendar-alt"></i> 
                                    <?= formatDateHausa($item['published_at'] ?? $item['created_at']) ?>
                                </span>
                                <span class="creator-views">
                                    <i class="fas fa-eye"></i> <?= number_format($item['views'] ?? 0) ?>
                                </span>
                            </div>
                            
                            <?php if ($item['content_type'] == 'video' && !empty($item['video_url'])): ?>
                                <a href="<?= $item['video_url'] ?>" target="_blank" class="watch-btn">
                                    <i class="fas fa-play"></i> Watch
                                </a>
                            <?php else: ?>
                                <a href="<?= SITE_URL ?>/item.php?id=<?= $item['id'] ?>&slug=<?= $item['slug'] ?>" class="read-btn">
                                    <i class="fas fa-book-open"></i> Read
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&type=<?= $type_filter ?>&category=<?= urlencode($category_filter) ?>" class="page-link prev">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?= $i ?>&type=<?= $type_filter ?>&category=<?= urlencode($category_filter) ?>" 
                           class="page-link <?= $i == $page ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?>&type=<?= $type_filter ?>&category=<?= urlencode($category_filter) ?>" class="page-link next">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-user-graduate"></i>
                <h3>No Items Available</h3>
                <p>MalamIromba will bring new content soon.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
/* Creator Page Styles */
.creator-page-section {
    padding: 60px 0;
    background: #f5f7fb;
}

/* Creator Header */
.creator-header {
    background: white;
    border-radius: 20px;
    padding: 40px;
    margin-bottom: 40px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    border: 1px solid rgba(211, 201, 254, 0.2);
}

.creator-profile {
    display: flex;
    gap: 30px;
    align-items: center;
    margin-bottom: 30px;
}

.creator-avatar {
    width: 120px;
    height: 120px;
    background: linear-gradient(135deg, #031837, #0a2a4a);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 4px solid #D3C9FE;
}

.creator-avatar i {
    font-size: 4rem;
    color: #D3C9FE;
}

.creator-info {
    flex: 1;
}

.creator-name {
    font-size: 2.5rem;
    color: #031837;
    margin-bottom: 5px;
}

.creator-title {
    color: #D3C9FE;
    font-size: 1.2rem;
    margin-bottom: 15px;
    font-weight: 500;
}

.creator-bio-short p {
    color: #666;
    line-height: 1.6;
    max-width: 600px;
}

/* Creator Stats */
.creator-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    padding-top: 30px;
    border-top: 2px solid rgba(211, 201, 254, 0.2);
}

.creator-stats .stat-item {
    text-align: center;
}

.creator-stats .stat-number {
    display: block;
    font-size: 2rem;
    font-weight: 700;
    color: #031837;
    margin-bottom: 5px;
}

.creator-stats .stat-label {
    color: #666;
    font-size: 0.9rem;
}

/* Filter Bar */
.filter-bar {
    background: white;
    border-radius: 15px;
    padding: 20px 30px;
    margin-bottom: 40px;
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    align-items: center;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    border: 1px solid rgba(211, 201, 254, 0.2);
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 10px;
}

.filter-group label {
    color: #031837;
    font-weight: 500;
    font-size: 0.9rem;
}

.filter-select {
    padding: 10px 15px;
    border: 1px solid rgba(211, 201, 254, 0.3);
    border-radius: 8px;
    font-size: 0.9rem;
    min-width: 150px;
    background: white;
    cursor: pointer;
}

.filter-select:focus {
    outline: none;
    border-color: #D3C9FE;
}

.search-group {
    margin-left: auto;
}

.filter-search {
    display: flex;
    gap: 5px;
}

.filter-search input {
    padding: 10px 15px;
    border: 1px solid rgba(211, 201, 254, 0.3);
    border-radius: 8px;
    font-size: 0.9rem;
    width: 250px;
}

.filter-search input:focus {
    outline: none;
    border-color: #D3C9FE;
}

.filter-search button {
    padding: 10px 15px;
    background: #031837;
    border: none;
    border-radius: 8px;
    color: white;
    cursor: pointer;
    transition: all 0.3s;
}

.filter-search button:hover {
    background: #D3C9FE;
    color: #031837;
}

/* Creator Grid */
.creator-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 30px;
    margin-bottom: 50px;
}

/* Creator Card */
.creator-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    transition: all 0.3s;
    border: 1px solid rgba(211, 201, 254, 0.2);
    height: 100%;
    display: flex;
    flex-direction: column;
}

.creator-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 30px rgba(211, 201, 254, 0.3);
    border-color: #D3C9FE;
}

.creator-card-image {
    position: relative;
    aspect-ratio: 16/9;
    overflow: hidden;
}

.creator-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s;
}

.creator-card:hover .creator-card-image img {
    transform: scale(1.1);
}

/* Content Type Badge */
.content-type-badge {
    position: absolute;
    top: 15px;
    left: 15px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 5px;
    z-index: 2;
    color: #031837;
}

.content-type-badge.video {
    background: #ff6b6b;
    color: white;
}

.content-type-badge.blog {
    background: #4ecdc4;
    color: white;
}

.content-type-badge.publication {
    background: #feca57;
    color: #031837;
}

.content-type-badge.tutorial {
    background: #54a0ff;
    color: white;
}

.content-type-badge.course {
    background: #5f27cd;
    color: white;
}

/* Duration Badge */
.duration-badge {
    position: absolute;
    bottom: 15px;
    right: 15px;
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    display: flex;
    align-items: center;
    gap: 5px;
    z-index: 2;
}

/* File Badge */
.file-badge {
    position: absolute;
    bottom: 15px;
    right: 15px;
    background: #dc3545;
    color: white;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    display: flex;
    align-items: center;
    gap: 5px;
    z-index: 2;
}

/* Featured Badge */
.featured-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    width: 30px;
    height: 30px;
    background: #D3C9FE;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #031837;
    z-index: 2;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.creator-card-content {
    padding: 25px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.creator-category {
    display: inline-block;
    background: rgba(211, 201, 254, 0.2);
    color: #031837;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    margin-bottom: 15px;
    align-self: flex-start;
}

.creator-category i {
    color: #D3C9FE;
}

.creator-card-title {
    font-size: 1.2rem;
    margin-bottom: 15px;
    line-height: 1.4;
}

.creator-card-title a {
    color: #031837;
    text-decoration: none;
    transition: color 0.3s;
}

.creator-card-title a:hover {
    color: #D3C9FE;
}

.creator-card-excerpt {
    color: #666;
    line-height: 1.7;
    margin-bottom: 20px;
    flex: 1;
}

.creator-card-meta {
    display: flex;
    gap: 20px;
    font-size: 0.85rem;
    color: #666;
    margin-bottom: 20px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.creator-card-meta i {
    color: #D3C9FE;
    margin-right: 5px;
}

.watch-btn,
.read-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 20px;
    background: #031837;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s;
    border: 1px solid transparent;
}

.watch-btn:hover,
.read-btn:hover {
    background: #D3C9FE;
    color: #031837;
    transform: translateY(-2px);
}

.watch-btn i,
.read-btn i {
    color: #D3C9FE;
    transition: color 0.3s;
}

.watch-btn:hover i,
.read-btn:hover i {
    color: #031837;
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.page-link {
    padding: 10px 18px;
    border: 1px solid rgba(211, 201, 254, 0.3);
    border-radius: 8px;
    text-decoration: none;
    color: #031837;
    font-weight: 500;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.page-link:hover,
.page-link.active {
    background: #031837;
    color: white;
    border-color: #031837;
}

.page-link.prev,
.page-link.next {
    background: #f0f4ff;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 80px 20px;
    background: white;
    border-radius: 15px;
    border: 2px dashed #D3C9FE;
}

.empty-state i {
    font-size: 4rem;
    color: #D3C9FE;
    margin-bottom: 20px;
}

.empty-state h3 {
    color: #031837;
    margin-bottom: 10px;
}

.empty-state p {
    color: #666;
}

/* Responsive */
@media (max-width: 992px) {
    .creator-profile {
        flex-direction: column;
        text-align: center;
    }
    
    .creator-bio-short p {
        margin: 0 auto;
    }
    
    .creator-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .filter-bar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-group {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-group {
        margin-left: 0;
    }
    
    .filter-search {
        width: 100%;
    }
    
    .filter-search input {
        width: 100%;
    }
    
    .creator-grid {
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    }
}

@media (max-width: 768px) {
    .creator-name {
        font-size: 2rem;
    }
    
    .creator-avatar {
        width: 100px;
        height: 100px;
    }
    
    .creator-avatar i {
        font-size: 3rem;
    }
}

@media (max-width: 480px) {
    .creator-header {
        padding: 25px;
    }
    
    .creator-stats {
        grid-template-columns: 1fr;
    }
    
    .creator-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function filterByType(type) {
    const url = new URL(window.location.href);
    url.searchParams.set('type', type);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

function filterByCategory(category) {
    const url = new URL(window.location.href);
    if (category) {
        url.searchParams.set('category', category);
    } else {
        url.searchParams.delete('category');
    }
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}
</script>

<?php include 'partials/footer.php'; ?>