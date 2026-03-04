<?php
// videos.php - TechInHausa Videos Listing Page
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = "Videos - TechInHausa";
$pageDesc = "Watch our tech and AI videos in Hausa language. Lessons, tutorials, and more.";

// Get current page for pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Get category filter
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query conditions
$where = "WHERE is_published = 1";
if ($category_id > 0) {
    $where .= " AND category_id = $category_id";
}
if (!empty($search)) {
    $search_esc = mysqli_real_escape_string($conn, $search);
    $where .= " AND (title LIKE '%$search_esc%' OR excerpt LIKE '%$search_esc%' OR description LIKE '%$search_esc%')";
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM videos $where";
$count_result = mysqli_query($conn, $count_query);
$total_videos = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_videos / $limit);

// Get videos
$query = "SELECT * FROM videos 
          $where 
          ORDER BY 
            CASE WHEN is_featured = 1 THEN 0 ELSE 1 END,
            published_at DESC 
          LIMIT $offset, $limit";

$videos = mysqli_query($conn, $query);

// Get categories for filter
$categories = mysqli_query($conn, "SELECT * FROM categories WHERE type = 'video' ORDER BY name");

// Get featured video for hero section
$featured_video = mysqli_query($conn, "SELECT * FROM videos WHERE is_featured = 1 AND is_published = 1 ORDER BY published_at DESC LIMIT 1");

include 'partials/header.php';
?>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <h1 class="page-title">Our Videos</h1>
        <p class="page-subtitle">Watch the latest videos and tutorials on tech and AI</p>
        
        <!-- Search Bar -->
        <div class="video-search">
            <form action="videos.php" method="GET" class="search-form">
                <input type="text" name="search" placeholder="Search videos..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit"><i class="fas fa-search"></i> Search</button>
            </form>
        </div>
    </div>
</section>

<!-- Video Categories -->
<section class="video-categories">
    <div class="container">
        <div class="categories-wrapper">
            <a href="videos.php" class="category-tab <?= !$category_id ? 'active' : '' ?>">
                <i class="fas fa-list"></i> All Videos
            </a>
            <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                <a href="videos.php?category=<?= $cat['id'] ?>" class="category-tab <?= $category_id == $cat['id'] ? 'active' : '' ?>">
                    <i class="fas <?= $cat['icon'] ?? 'fa-folder' ?>"></i> <?= htmlspecialchars($cat['name']) ?>
                </a>
            <?php endwhile; ?>
        </div>
    </div>
</section>

<!-- Featured Video Section (if exists) -->
<?php if (mysqli_num_rows($featured_video) > 0 && $page == 1 && !$category_id && empty($search)): 
    $featured = mysqli_fetch_assoc($featured_video);
    $video_id = getYouTubeId($featured['video_url']);
?>
<section class="featured-video-section">
    <div class="container">
        <div class="featured-video-card">
            <div class="featured-video-content">
                <span class="featured-badge"><i class="fas fa-star"></i> Featured Video</span>
                <h2><?= htmlspecialchars($featured['title']) ?></h2>
                <p><?= htmlspecialchars($featured['excerpt'] ?? '') ?></p>
                
                <div class="featured-meta">
                    <span><i class="fas fa-user"></i> <?= htmlspecialchars($featured['author'] ?? 'MalamIromba') ?></span>
                    <span><i class="fas fa-clock"></i> <?= htmlspecialchars($featured['video_duration'] ?? '') ?></span>
                    <span><i class="fas fa-calendar"></i> <?= formatDateHausa($featured['published_at']) ?></span>
                </div>
                
                <a href="video/<?= $featured['slug'] ?>" class="watch-now-btn">
                    <i class="fas fa-play-circle"></i> Watch Video
                </a>
            </div>
            <div class="featured-video-thumbnail">
                <a href="video/<?= $featured['slug'] ?>">
                    <img src="<?= !empty($featured['featured_image']) ? getImageUrl($featured['featured_image'], 'video') : "https://img.youtube.com/vi/{$video_id}/maxresdefault.jpg" ?>" 
                         alt="<?= htmlspecialchars($featured['title']) ?>">
                    <span class="play-button-large"><i class="fas fa-play-circle"></i></span>
                </a>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Videos Grid -->
<section class="videos-grid-section">
    <div class="container">
        <?php if (mysqli_num_rows($videos) > 0): ?>
            <div class="videos-grid">
                <?php while ($video = mysqli_fetch_assoc($videos)): 
                    $video_id = getYouTubeId($video['video_url']);
                    $thumbnail = !empty($video['featured_image']) 
                        ? getImageUrl($video['featured_image'], 'video') 
                        : "https://img.youtube.com/vi/{$video_id}/mqdefault.jpg";
                ?>
                    <div class="video-card">
                        <div class="video-thumbnail">
                            <a href="video/<?= $video['slug'] ?>">
                                <img src="<?= $thumbnail ?>" 
                                     alt="<?= htmlspecialchars($video['title']) ?>"
                                     loading="lazy"
                                     onerror="this.src='<?= SITE_URL ?>/assets/images/video-placeholder.jpg'">
                                <?php if (!empty($video['video_duration'])): ?>
                                    <span class="video-duration"><?= $video['video_duration'] ?></span>
                                <?php endif; ?>
                                <span class="play-button"><i class="fas fa-play-circle"></i></span>
                            </a>
                        </div>
                        <div class="video-info">
                            <h3 class="video-title">
                                <a href="video/<?= $video['slug'] ?>">
                                    <?= htmlspecialchars($video['title']) ?>
                                </a>
                            </h3>
                            <div class="video-meta">
                                <span class="video-views">
                                    <i class="fas fa-eye"></i> <?= number_format($video['views'] ?? 0) ?>
                                </span>
                                <span class="video-date">
                                    <i class="fas fa-calendar-alt"></i> <?= formatDateHausa($video['published_at']) ?>
                                </span>
                            </div>
                            <?php if (!empty($video['excerpt'])): ?>
                                <p class="video-excerpt"><?= htmlspecialchars(substr($video['excerpt'], 0, 80)) ?>...</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="videos.php?page=<?= $page-1 ?><?= $category_id ? '&category='.$category_id : '' ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>" 
                           class="page-link">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="videos.php?page=<?= $i ?><?= $category_id ? '&category='.$category_id : '' ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>" 
                           class="page-link <?= $i == $page ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="videos.php?page=<?= $page+1 ?><?= $category_id ? '&category='.$category_id : '' ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>" 
                           class="page-link">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-video-slash"></i>
                <h3>No Videos Found</h3>
                <p>No videos match your search. Try different keywords.</p>
                <a href="videos.php" class="btn btn-primary">View All Videos</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
/* Page Header */
.page-header {
    background: linear-gradient(135deg, #031837 0%, #0a2a4a 100%);
    color: white;
    padding: 60px 0;
    text-align: center;
}

.page-title {
    font-size: 2.5rem;
    margin-bottom: 15px;
}

.page-subtitle {
    font-size: 1.1rem;
    opacity: 0.9;
    margin-bottom: 30px;
}

.video-search {
    max-width: 500px;
    margin: 0 auto;
}

.video-search .search-form {
    display: flex;
    gap: 10px;
}

.video-search input {
    flex: 1;
    padding: 15px 20px;
    border: none;
    border-radius: 50px;
    font-size: 1rem;
    outline: none;
}

.video-search button {
    padding: 15px 30px;
    background: #D3C9FE;
    border: none;
    border-radius: 50px;
    color: #031837;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.video-search button:hover {
    background: #b8a9fe;
    transform: translateY(-2px);
}

/* Video Categories */
.video-categories {
    background: white;
    padding: 20px 0;
    border-bottom: 1px solid rgba(211, 201, 254, 0.3);
}

.categories-wrapper {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: center;
}

.category-tab {
    padding: 10px 20px;
    background: #f0f4ff;
    border-radius: 50px;
    color: #031837;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.category-tab:hover,
.category-tab.active {
    background: #031837;
    color: white;
    transform: translateY(-2px);
}

.category-tab i {
    font-size: 0.9rem;
}

/* Featured Video */
.featured-video-section {
    padding: 40px 0;
}

.featured-video-card {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    border: 1px solid rgba(211, 201, 254, 0.3);
}

.featured-video-content {
    padding: 40px;
}

.featured-badge {
    display: inline-block;
    padding: 5px 15px;
    background: #D3C9FE;
    color: #031837;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 20px;
}

.featured-video-content h2 {
    font-size: 2rem;
    color: #031837;
    margin-bottom: 15px;
}

.featured-video-content p {
    color: #666;
    line-height: 1.8;
    margin-bottom: 20px;
}

.featured-meta {
    display: flex;
    gap: 20px;
    margin-bottom: 25px;
    color: #888;
}

.featured-meta i {
    color: #D3C9FE;
    margin-right: 5px;
}

.watch-now-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 15px 35px;
    background: #031837;
    color: white;
    text-decoration: none;
    border-radius: 50px;
    font-weight: 600;
    transition: all 0.3s;
}

.watch-now-btn:hover {
    background: #0a2a4a;
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(3, 24, 55, 0.3);
}

.watch-now-btn i {
    color: #D3C9FE;
    font-size: 1.2rem;
}

.featured-video-thumbnail {
    position: relative;
    overflow: hidden;
}

.featured-video-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s;
}

.featured-video-thumbnail:hover img {
    transform: scale(1.05);
}

.play-button-large {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 5rem;
    color: white;
    opacity: 0.8;
    transition: all 0.3s;
    text-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

.featured-video-thumbnail:hover .play-button-large {
    opacity: 1;
    transform: translate(-50%, -50%) scale(1.1);
    color: #D3C9FE;
}

/* Videos Grid */
.videos-grid-section {
    padding: 60px 0;
    background: #f5f7fb;
}

.videos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 30px;
    margin-bottom: 40px;
}

.video-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    transition: all 0.3s;
    border: 1px solid rgba(211, 201, 254, 0.2);
}

.video-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 30px rgba(211, 201, 254, 0.3);
    border-color: #D3C9FE;
}

.video-thumbnail {
    position: relative;
    aspect-ratio: 16/9;
    overflow: hidden;
}

.video-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s;
}

.video-card:hover .video-thumbnail img {
    transform: scale(1.1);
}

.video-duration {
    position: absolute;
    bottom: 10px;
    right: 10px;
    background: rgba(0,0,0,0.8);
    color: white;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
    z-index: 2;
}

.play-button {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 3rem;
    color: white;
    opacity: 0.8;
    transition: all 0.3s;
    text-shadow: 0 2px 10px rgba(0,0,0,0.3);
    z-index: 2;
}

.video-card:hover .play-button {
    opacity: 1;
    transform: translate(-50%, -50%) scale(1.1);
    color: #D3C9FE;
}

.video-info {
    padding: 20px;
}

.video-title {
    font-size: 1.1rem;
    margin-bottom: 10px;
    line-height: 1.4;
}

.video-title a {
    color: #031837;
    text-decoration: none;
    transition: color 0.3s;
}

.video-title a:hover {
    color: #D3C9FE;
}

.video-meta {
    display: flex;
    gap: 15px;
    font-size: 0.85rem;
    color: #888;
    margin-bottom: 10px;
}

.video-meta i {
    color: #D3C9FE;
    margin-right: 5px;
}

.video-excerpt {
    color: #666;
    font-size: 0.9rem;
    line-height: 1.5;
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 40px;
}

.page-link {
    padding: 12px 18px;
    background: white;
    color: #031837;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s;
    border: 1px solid rgba(211, 201, 254, 0.3);
}

.page-link:hover,
.page-link.active {
    background: #031837;
    color: white;
    border-color: #031837;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
}

.empty-state i {
    font-size: 4rem;
    color: #ddd;
    margin-bottom: 20px;
}

.empty-state h3 {
    color: #031837;
    margin-bottom: 10px;
}

.empty-state p {
    color: #666;
    margin-bottom: 20px;
}

.btn-primary {
    display: inline-block;
    padding: 12px 30px;
    background: #031837;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s;
}

.btn-primary:hover {
    background: #0a2a4a;
    transform: translateY(-2px);
}

/* Responsive */
@media (max-width: 992px) {
    .featured-video-card {
        grid-template-columns: 1fr;
    }
    
    .featured-video-thumbnail {
        order: -1;
    }
}

@media (max-width: 768px) {
    .page-title {
        font-size: 2rem;
    }
    
    .video-search .search-form {
        flex-direction: column;
    }
    
    .featured-video-content {
        padding: 30px;
    }
    
    .featured-video-content h2 {
        font-size: 1.5rem;
    }
    
    .featured-meta {
        flex-wrap: wrap;
    }
}

@media (max-width: 480px) {
    .videos-grid {
        grid-template-columns: 1fr;
    }
    
    .pagination {
        gap: 5px;
    }
    
    .page-link {
        padding: 8px 12px;
    }
}
</style>

<?php include 'partials/footer.php'; ?>