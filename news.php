<?php
// news.php - TechInHausa News Listing
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = "News - TechInHausa";
$pageDesc = "Latest technology and AI news in Hausa language. Stay updated with what's happening in the tech world.";

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 9;
$offset = ($page - 1) * $limit;

// Get total news count
$total_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM news WHERE is_published = 1");
$total_row = mysqli_fetch_assoc($total_result);
$total_news = $total_row['count'];
$total_pages = ceil($total_news / $limit);

// Get news with category
$news_query = "
    SELECT n.*, c.name as category_name, c.slug as category_slug 
    FROM news n
    LEFT JOIN categories c ON n.category_id = c.id
    WHERE n.is_published = 1 
    ORDER BY n.published_at DESC 
    LIMIT $offset, $limit
";

$news_result = mysqli_query($conn, $news_query);

// Get featured news for sidebar
$featured_news = mysqli_query($conn, "
    SELECT id, title, slug, featured_image, published_at 
    FROM news 
    WHERE is_published = 1 AND is_featured = 1 
    ORDER BY published_at DESC 
    LIMIT 5
");

// Get categories for filtering
$categories = mysqli_query($conn, "
    SELECT c.*, COUNT(n.id) as news_count 
    FROM categories c
    LEFT JOIN news n ON c.id = n.category_id AND n.is_published = 1
    WHERE c.type = 'news'
    GROUP BY c.id
    ORDER BY c.name ASC
");

include 'partials/header.php';
?>

<section class="news-page-section">
    <div class="container">
        <div class="news-header">
            <h1 class="page-title">News</h1>
            <p class="page-subtitle">Latest news and happenings in the tech world</p>
        </div>
        
        <div class="news-layout">
            <!-- Main News Grid -->
            <div class="news-main">
                <?php if (mysqli_num_rows($news_result) > 0): ?>
                    <div class="news-grid">
                        <?php while ($news = mysqli_fetch_assoc($news_result)): ?>
                            <article class="news-card">
                                <div class="news-card-image">
                                    <a href="<?= SITE_URL ?>/article.php?id=<?= $news['id'] ?>&slug=<?= $news['slug'] ?>">
                                        <img src="<?= getImageUrl($news['featured_image'] ?? '', 'news') ?>" 
                                             alt="<?= htmlspecialchars($news['title']) ?>"
                                             onerror="this.src='<?= SITE_URL ?>/assets/images/techinhausa-about.jpg'">
                                        <!-- NO PLAY BUTTON - This is an article, not a video -->
                                    </a>
                                </div>
                                
                                <div class="news-card-content">
                                    <div class="news-meta-top">
                                        <?php if (!empty($news['category_name'])): ?>
                                            <a href="?category=<?= $news['category_slug'] ?>" class="news-category">
                                                <?= htmlspecialchars($news['category_name']) ?>
                                            </a>
                                        <?php endif; ?>
                                        <span class="news-date">
                                            <i class="far fa-calendar-alt"></i> 
                                            <?= formatDateHausa($news['published_at']) ?>
                                        </span>
                                    </div>
                                    
                                    <h2 class="news-title">
                                        <a href="<?= SITE_URL ?>/article.php?id=<?= $news['id'] ?>&slug=<?= $news['slug'] ?>">
                                            <?= htmlspecialchars($news['title']) ?>
                                        </a>
                                    </h2>
                                    
                                    <p class="news-excerpt">
                                        <?= truncateText($news['excerpt'] ?? $news['content'], 150) ?>
                                    </p>
                                    
                                    <div class="news-meta-bottom">
                                        <span class="news-author">
                                            <i class="fas fa-user"></i> <?= htmlspecialchars($news['author'] ?? 'MalamIromba') ?>
                                        </span>
                                        <span class="news-views">
                                            <i class="fas fa-eye"></i> <?= number_format($news['views'] ?? 0) ?>
                                        </span>
                                        <a href="<?= SITE_URL ?>/article.php?id=<?= $news['id'] ?>&slug=<?= $news['slug'] ?>" class="read-more-link">
                                            Read More <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </article>
                        <?php endwhile; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?>" class="page-link prev">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?page=<?= $i ?>" class="page-link <?= $i == $page ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?= $page + 1 ?>" class="page-link next">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-newspaper"></i>
                        <h3>No News Available</h3>
                        <p>We will bring you latest news soon.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <aside class="news-sidebar">
                <!-- Categories Widget -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">Categories</h3>
                    <ul class="category-list">
                        <?php if ($categories && mysqli_num_rows($categories) > 0): ?>
                            <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                                <li>
                                    <a href="?category=<?= $cat['slug'] ?>">
                                        <?= htmlspecialchars($cat['name']) ?>
                                        <span class="count">(<?= $cat['news_count'] ?>)</span>
                                    </a>
                                </li>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <!-- Featured News Widget -->
                <?php if ($featured_news && mysqli_num_rows($featured_news) > 0): ?>
                    <div class="sidebar-widget">
                        <h3 class="widget-title">Featured News</h3>
                        <div class="featured-news-list">
                            <?php while ($featured = mysqli_fetch_assoc($featured_news)): ?>
                                <div class="featured-news-item">
                                    <a href="<?= SITE_URL ?>/article.php?id=<?= $featured['id'] ?>&slug=<?= $featured['slug'] ?>" class="featured-news-link">
                                        <div class="featured-news-image">
                                            <img src="<?= getImageUrl($featured['featured_image'] ?? '', 'news') ?>" 
                                                 alt="<?= htmlspecialchars($featured['title']) ?>"
                                                 onerror="this.src='<?= SITE_URL ?>/assets/images/techinhausa-about.jpg'">
                                        </div>
                                        <div class="featured-news-content">
                                            <h4><?= htmlspecialchars($featured['title']) ?></h4>
                                            <span class="featured-news-date">
                                                <i class="far fa-calendar-alt"></i> 
                                                <?= formatDateHausa($featured['published_at']) ?>
                                            </span>
                                        </div>
                                    </a>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Social Widget -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">Follow Us</h3>
                    <div class="social-widget">
                        <a href="#" class="social-widget-link facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="social-widget-link twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="social-widget-link instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="social-widget-link youtube">
                            <i class="fab fa-youtube"></i>
                        </a>
                        <a href="#" class="social-widget-link telegram">
                            <i class="fab fa-telegram-plane"></i>
                        </a>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</section>

<style>
/* News Page Styles */
.news-page-section {
    padding: 60px 0;
    background: #f5f7fb;
}

.news-header {
    text-align: center;
    margin-bottom: 50px;
}

.page-title {
    font-size: 2.5rem;
    color: #031837;
    margin-bottom: 15px;
    position: relative;
    padding-bottom: 15px;
}

.page-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 4px;
    background: #D3C9FE;
    border-radius: 2px;
}

.page-subtitle {
    color: #666;
    font-size: 1.1rem;
}

.news-layout {
    display: grid;
    grid-template-columns: 2.5fr 1fr;
    gap: 40px;
}

/* News Grid */
.news-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 30px;
}

.news-card {
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

.news-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 30px rgba(211, 201, 254, 0.3);
    border-color: #D3C9FE;
}

.news-card-image {
    position: relative;
    aspect-ratio: 16/9;
    overflow: hidden;
}

.news-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s;
}

.news-card:hover .news-card-image img {
    transform: scale(1.1);
}

.news-card-content {
    padding: 25px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.news-meta-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    font-size: 0.85rem;
}

.news-category {
    background: #D3C9FE;
    color: #031837;
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 600;
    text-decoration: none;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.news-category:hover {
    background: #b8a9fe;
}

.news-date {
    color: #666;
    display: flex;
    align-items: center;
    gap: 5px;
}

.news-date i {
    color: #D3C9FE;
}

.news-title {
    font-size: 1.3rem;
    margin-bottom: 15px;
    line-height: 1.4;
}

.news-title a {
    color: #031837;
    text-decoration: none;
    transition: color 0.3s;
}

.news-title a:hover {
    color: #D3C9FE;
}

.news-excerpt {
    color: #666;
    line-height: 1.7;
    margin-bottom: 20px;
    flex: 1;
}

.news-meta-bottom {
    display: flex;
    align-items: center;
    gap: 20px;
    font-size: 0.9rem;
    color: #666;
    border-top: 1px solid #eee;
    padding-top: 15px;
}

.news-author i,
.news-views i {
    color: #D3C9FE;
    margin-right: 5px;
}

.read-more-link {
    margin-left: auto;
    color: #031837;
    text-decoration: none;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: all 0.3s;
}

.read-more-link:hover {
    color: #D3C9FE;
    gap: 8px;
}

/* Sidebar */
.news-sidebar {
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
    font-size: 1.3rem;
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

/* Category List */
.category-list {
    list-style: none;
}

.category-list li {
    margin-bottom: 12px;
    border-bottom: 1px solid #f0f0f0;
    padding-bottom: 12px;
}

.category-list li:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.category-list a {
    color: #666;
    text-decoration: none;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.3s;
}

.category-list a:hover {
    color: #031837;
    transform: translateX(5px);
}

.category-list .count {
    background: #f0f4ff;
    color: #031837;
    padding: 2px 8px;
    border-radius: 20px;
    font-size: 0.8rem;
}

/* Featured News List */
.featured-news-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.featured-news-item {
    border-bottom: 1px solid #f0f0f0;
    padding-bottom: 20px;
}

.featured-news-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.featured-news-link {
    display: flex;
    gap: 15px;
    text-decoration: none;
}

.featured-news-image {
    width: 80px;
    height: 60px;
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
}

.featured-news-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.featured-news-content {
    flex: 1;
}

.featured-news-content h4 {
    color: #031837;
    font-size: 0.95rem;
    margin-bottom: 5px;
    line-height: 1.4;
}

.featured-news-date {
    color: #666;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    gap: 3px;
}

.featured-news-date i {
    color: #D3C9FE;
    font-size: 0.7rem;
}

/* Social Widget */
.social-widget {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.social-widget-link {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    color: white;
    transition: all 0.3s;
}

.social-widget-link:hover {
    transform: translateY(-3px);
}

.social-widget-link.facebook { background: #1877f2; }
.social-widget-link.twitter { background: #1da1f2; }
.social-widget-link.instagram { background: linear-gradient(45deg, #f09433, #d62976, #962fbf); }
.social-widget-link.youtube { background: #ff0000; }
.social-widget-link.telegram { background: #0088cc; }

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin-top: 50px;
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
    .news-layout {
        grid-template-columns: 1fr;
    }
    
    .news-sidebar {
        position: static;
    }
    
    .news-grid {
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    }
}

@media (max-width: 768px) {
    .page-title {
        font-size: 2rem;
    }
    
    .news-meta-bottom {
        flex-wrap: wrap;
    }
    
    .read-more-link {
        margin-left: 0;
    }
}

@media (max-width: 480px) {
    .news-grid {
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