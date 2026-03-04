<?php
// research.php - TechInHausa Research Papers Listing
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = "Research - TechInHausa";
$pageDesc = "Latest research papers and academic publications on technology and AI.";

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 6;
$offset = ($page - 1) * $limit;

// Get filter from URL
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$year_filter = isset($_GET['year']) ? (int)$_GET['year'] : 0;

// Build WHERE clause
$where = "WHERE is_published = 1";
if ($category_filter > 0) {
    $where .= " AND category_id = $category_filter";
}
if ($year_filter > 0) {
    $where .= " AND YEAR(published_at) = $year_filter";
}

// Get total research papers count
$count_query = "SELECT COUNT(*) as count FROM research $where";
$count_result = mysqli_query($conn, $count_query);
$total_rows = mysqli_fetch_assoc($count_result)['count'];
$total_pages = ceil($total_rows / $limit);

// Get research papers with category
$query = "
    SELECT r.*, c.name as category_name, c.slug as category_slug, c.icon as category_icon
    FROM research r
    LEFT JOIN categories c ON r.category_id = c.id
    $where 
    ORDER BY 
        CASE 
            WHEN r.is_featured = 1 THEN 0 
            ELSE 1 
        END,
        r.published_at DESC 
    LIMIT $offset, $limit
";
$result = mysqli_query($conn, $query);

// Get categories for filter
$categories_query = "
    SELECT c.*, COUNT(r.id) as paper_count 
    FROM categories c
    LEFT JOIN research r ON c.id = r.category_id AND r.is_published = 1
    WHERE c.type = 'research'
    GROUP BY c.id
    ORDER BY c.name ASC
";
$categories_result = mysqli_query($conn, $categories_query);

// Get publication years for filter
$years_query = "
    SELECT DISTINCT YEAR(published_at) as year, 
           COUNT(*) as count 
    FROM research 
    WHERE is_published = 1 AND published_at IS NOT NULL
    GROUP BY YEAR(published_at)
    ORDER BY year DESC
";
$years_result = mysqli_query($conn, $years_query);

// Get featured research for sidebar
$featured_query = "
    SELECT id, title, slug, excerpt, featured_image, author, published_at
    FROM research 
    WHERE is_published = 1 AND is_featured = 1 
    ORDER BY published_at DESC 
    LIMIT 5
";
$featured_result = mysqli_query($conn, $featured_query);

// Get stats
$stats_query = "
    SELECT 
        COUNT(*) as total_papers,
        COUNT(DISTINCT author) as total_authors,
        SUM(views) as total_views,
        AVG(views) as avg_views
    FROM research 
    WHERE is_published = 1
";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

include 'partials/header.php';
?>

<section class="research-page-section">
    <div class="container">
        <!-- Research Header -->
        <div class="research-header">
            <h1 class="page-title">Research & Publications</h1>
            <p class="page-subtitle">Latest research papers and academic publications on technology and AI</p>
        </div>
        
        <!-- Research Stats Bar -->
        <div class="research-stats-bar">
            <div class="stat-item">
                <span class="stat-number"><?= number_format($stats['total_papers'] ?? 0) ?></span>
                <span class="stat-label">Research Papers</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?= number_format($stats['total_authors'] ?? 0) ?></span>
                <span class="stat-label">Authors</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?= number_format($stats['total_views'] ?? 0) ?></span>
                <span class="stat-label">Views</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?= round($stats['avg_views'] ?? 0) ?></span>
                <span class="stat-label">Avg. Views</span>
            </div>
        </div>
        
        <div class="research-layout">
            <!-- Main Research Grid -->
            <div class="research-main">
                <!-- Filter Bar -->
                <div class="filter-bar">
                    <form action="research.php" method="GET" class="filter-form">
                        <div class="filter-group">
                            <label for="category">Category:</label>
                            <select name="category" id="category" class="filter-select" onchange="this.form.submit()">
                                <option value="0">All Categories</option>
                                <?php 
                                if ($categories_result && mysqli_num_rows($categories_result) > 0):
                                    while ($cat = mysqli_fetch_assoc($categories_result)): 
                                ?>
                                    <option value="<?= $cat['id'] ?>" <?= $category_filter == $cat['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?> (<?= $cat['paper_count'] ?>)
                                    </option>
                                <?php 
                                    endwhile;
                                endif; 
                                ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="year">Year:</label>
                            <select name="year" id="year" class="filter-select" onchange="this.form.submit()">
                                <option value="0">All Years</option>
                                <?php 
                                if ($years_result && mysqli_num_rows($years_result) > 0):
                                    while ($year = mysqli_fetch_assoc($years_result)): 
                                ?>
                                    <option value="<?= $year['year'] ?>" <?= $year_filter == $year['year'] ? 'selected' : '' ?>>
                                        <?= $year['year'] ?> (<?= $year['count'] ?>)
                                    </option>
                                <?php 
                                    endwhile;
                                endif; 
                                ?>
                            </select>
                        </div>
                        
                        <?php if ($category_filter > 0 || $year_filter > 0): ?>
                            <a href="research.php" class="clear-filter">
                                <i class="fas fa-times"></i> Clear Filters
                            </a>
                        <?php endif; ?>
                    </form>
                    
                    <div class="search-box">
                        <form action="research.php" method="GET" class="search-form">
                            <input type="text" name="search" placeholder="Search research..." 
                                   value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                            <button type="submit"><i class="fas fa-search"></i></button>
                        </form>
                    </div>
                </div>
                
                <!-- Research Papers Grid -->
                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                    <div class="research-grid">
                        <?php while ($paper = mysqli_fetch_assoc($result)): ?>
                            <div class="research-card">
                                <div class="research-card-header">
                                    <?php if (!empty($paper['featured_image'])): ?>
                                        <div class="research-card-image">
                                            <a href="<?= SITE_URL ?>/paper.php?id=<?= $paper['id'] ?>&slug=<?= $paper['slug'] ?>">
                                                <img src="<?= getImageUrl($paper['featured_image'], 'research') ?>" 
                                                     alt="<?= htmlspecialchars($paper['title']) ?>"
                                                     onerror="this.src='<?= SITE_URL ?>/assets/images/techinhausa-about.jpg'">
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="research-card-badges">
                                        <?php if (!empty($paper['category_name'])): ?>
                                            <span class="research-category-badge">
                                                <i class="fas <?= $paper['category_icon'] ?? 'fa-folder' ?>"></i>
                                                <?= htmlspecialchars($paper['category_name']) ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if ($paper['is_featured']): ?>
                                            <span class="featured-badge">
                                                <i class="fas fa-star"></i> Featured
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($paper['file_url'])): ?>
                                            <span class="pdf-badge">
                                                <i class="fas fa-file-pdf"></i> PDF
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="research-card-content">
                                    <h3 class="research-card-title">
                                        <a href="<?= SITE_URL ?>/paper.php?id=<?= $paper['id'] ?>&slug=<?= $paper['slug'] ?>">
                                            <?= htmlspecialchars($paper['title']) ?>
                                        </a>
                                    </h3>
                                    
                                    <div class="research-card-meta">
                                        <span class="research-author">
                                            <i class="fas fa-user"></i> <?= htmlspecialchars($paper['author'] ?? 'MalamIromba') ?>
                                        </span>
                                        <span class="research-date">
                                            <i class="far fa-calendar-alt"></i> 
                                            <?= date('Y', strtotime($paper['published_at'] ?? $paper['created_at'])) ?>
                                        </span>
                                        <span class="research-views">
                                            <i class="fas fa-eye"></i> <?= number_format($paper['views'] ?? 0) ?>
                                        </span>
                                    </div>
                                    
                                    <?php if (!empty($paper['excerpt'])): ?>
                                        <p class="research-excerpt">
                                            <?= truncateText($paper['excerpt'], 120) ?>
                                        </p>
                                    <?php elseif (!empty($paper['abstract'])): ?>
                                        <p class="research-excerpt">
                                            <?= truncateText($paper['abstract'], 120) ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="research-card-footer">
                                        <a href="<?= SITE_URL ?>/paper.php?id=<?= $paper['id'] ?>&slug=<?= $paper['slug'] ?>" class="read-more-btn">
                                            Read More <i class="fas fa-arrow-right"></i>
                                        </a>
                                        
                                        <?php if (!empty($paper['file_url'])): ?>
                                            <a href="<?= $paper['file_url'] ?>" target="_blank" class="download-pdf-btn" title="Download PDF">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (!empty($paper['tags'])): ?>
                                        <div class="research-tags">
                                            <?php 
                                            $tags = explode(',', $paper['tags']);
                                            $tags = array_slice($tags, 0, 3);
                                            foreach ($tags as $tag): 
                                                $tag = trim($tag);
                                                if (!empty($tag)):
                                            ?>
                                                <span class="tag">#<?= htmlspecialchars($tag) ?></span>
                                            <?php 
                                                endif;
                                            endforeach; 
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?>&category=<?= $category_filter ?>&year=<?= $year_filter ?>" class="page-link prev">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?page=<?= $i ?>&category=<?= $category_filter ?>&year=<?= $year_filter ?>" 
                                   class="page-link <?= $i == $page ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?= $page + 1 ?>&category=<?= $category_filter ?>&year=<?= $year_filter ?>" class="page-link next">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-flask"></i>
                        <h3>No Research Papers Yet</h3>
                        <p>New research papers will be available soon.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <aside class="research-sidebar">
                <!-- Featured Research Widget -->
                <?php if ($featured_result && mysqli_num_rows($featured_result) > 0): ?>
                    <div class="sidebar-widget">
                        <h3 class="widget-title">Featured Research</h3>
                        <div class="featured-research-list">
                            <?php while ($featured = mysqli_fetch_assoc($featured_result)): ?>
                                <div class="featured-research-item">
                                    <a href="<?= SITE_URL ?>/paper.php?id=<?= $featured['id'] ?>&slug=<?= $featured['slug'] ?>" class="featured-research-link">
                                        <?php if (!empty($featured['featured_image'])): ?>
                                            <div class="featured-research-image">
                                                <img src="<?= getImageUrl($featured['featured_image'], 'research') ?>" 
                                                     alt="<?= htmlspecialchars($featured['title']) ?>">
                                            </div>
                                        <?php endif; ?>
                                        <div class="featured-research-content">
                                            <h4><?= htmlspecialchars($featured['title']) ?></h4>
                                            <span class="featured-research-author">
                                                <i class="fas fa-user"></i> <?= htmlspecialchars($featured['author'] ?? 'MalamIromba') ?>
                                            </span>
                                            <span class="featured-research-date">
                                                <i class="far fa-calendar-alt"></i> <?= date('Y', strtotime($featured['published_at'])) ?>
                                            </span>
                                        </div>
                                    </a>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Categories Widget -->
                <?php if ($categories_result && mysqli_num_rows($categories_result) > 0): ?>
                    <div class="sidebar-widget">
                        <h3 class="widget-title">Categories</h3>
                        <ul class="category-list">
                            <?php 
                            mysqli_data_seek($categories_result, 0); // Reset pointer
                            while ($cat = mysqli_fetch_assoc($categories_result)): 
                            ?>
                                <li>
                                    <a href="?category=<?= $cat['id'] ?>">
                                        <?php if (!empty($cat['icon'])): ?>
                                            <i class="fas <?= $cat['icon'] ?>"></i>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($cat['name']) ?>
                                        <span class="count">(<?= $cat['paper_count'] ?>)</span>
                                    </a>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <!-- Publication Years Widget -->
                <?php if ($years_result && mysqli_num_rows($years_result) > 0): ?>
                    <div class="sidebar-widget">
                        <h3 class="widget-title">Years</h3>
                        <ul class="years-list">
                            <?php 
                            mysqli_data_seek($years_result, 0); // Reset pointer
                            while ($year = mysqli_fetch_assoc($years_result)): 
                            ?>
                                <li>
                                    <a href="?year=<?= $year['year'] ?>" class="<?= $year_filter == $year['year'] ? 'active' : '' ?>">
                                        <?= $year['year'] ?>
                                        <span class="count">(<?= $year['count'] ?>)</span>
                                    </a>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <!-- Newsletter Widget -->
                <div class="sidebar-widget newsletter-widget">
                    <h3 class="widget-title">Join Our Newsletter</h3>
                    <p>Get the latest research papers delivered to your inbox</p>
                    <form id="sidebarNewsletter" class="sidebar-newsletter">
                        <input type="email" name="email" placeholder="Your email" required>
                        <button type="submit"><i class="fas fa-paper-plane"></i></button>
                    </form>
                </div>
            </aside>
        </div>
    </div>
</section>

<style>
/* Research Page Styles */
.research-page-section {
    padding: 60px 0;
    background: #f5f7fb;
}

.research-header {
    text-align: center;
    margin-bottom: 40px;
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

/* Research Stats Bar */
.research-stats-bar {
    background: white;
    border-radius: 15px;
    padding: 30px;
    margin-bottom: 40px;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    border: 1px solid rgba(211, 201, 254, 0.2);
}

.research-stats-bar .stat-item {
    text-align: center;
    border-right: 1px solid rgba(211, 201, 254, 0.3);
    padding: 0 20px;
}

.research-stats-bar .stat-item:last-child {
    border-right: none;
}

.research-stats-bar .stat-number {
    display: block;
    font-size: 2rem;
    font-weight: 700;
    color: #031837;
    margin-bottom: 5px;
}

.research-stats-bar .stat-label {
    color: #666;
    font-size: 0.9rem;
}

/* Research Layout */
.research-layout {
    display: grid;
    grid-template-columns: 2.5fr 1fr;
    gap: 40px;
}

/* Filter Bar */
.filter-bar {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    border: 1px solid rgba(211, 201, 254, 0.2);
}

.filter-form {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    align-items: center;
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 8px;
}

.filter-group label {
    color: #031837;
    font-weight: 500;
    font-size: 0.9rem;
}

.filter-select {
    padding: 8px 12px;
    border: 1px solid rgba(211, 201, 254, 0.3);
    border-radius: 6px;
    font-size: 0.9rem;
    min-width: 150px;
    background: white;
}

.filter-select:focus {
    outline: none;
    border-color: #D3C9FE;
}

.clear-filter {
    color: #666;
    text-decoration: none;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 5px 10px;
    border-radius: 5px;
    transition: all 0.3s;
}

.clear-filter:hover {
    color: #dc3545;
    background: rgba(220, 53, 69, 0.1);
}

.search-box {
    flex: 0 0 250px;
}

.search-form {
    display: flex;
    gap: 5px;
}

.search-form input {
    flex: 1;
    padding: 8px 12px;
    border: 1px solid rgba(211, 201, 254, 0.3);
    border-radius: 6px;
    font-size: 0.9rem;
}

.search-form input:focus {
    outline: none;
    border-color: #D3C9FE;
}

.search-form button {
    padding: 8px 15px;
    background: #031837;
    border: none;
    border-radius: 6px;
    color: white;
    cursor: pointer;
    transition: all 0.3s;
}

.search-form button:hover {
    background: #D3C9FE;
    color: #031837;
}

/* Research Grid */
.research-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 30px;
    margin-bottom: 40px;
}

/* Research Card */
.research-card {
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

.research-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 30px rgba(211, 201, 254, 0.3);
    border-color: #D3C9FE;
}

.research-card-header {
    position: relative;
}

.research-card-image {
    aspect-ratio: 16/9;
    overflow: hidden;
}

.research-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s;
}

.research-card:hover .research-card-image img {
    transform: scale(1.1);
}

.research-card-badges {
    position: absolute;
    top: 15px;
    left: 15px;
    right: 15px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    z-index: 2;
}

.research-category-badge {
    background: rgba(3, 24, 55, 0.9);
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.featured-badge {
    background: #D3C9FE;
    color: #031837;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    margin-left: auto;
}

.pdf-badge {
    background: #dc3545;
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.research-card-content {
    padding: 25px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.research-card-title {
    font-size: 1.2rem;
    margin-bottom: 15px;
    line-height: 1.4;
}

.research-card-title a {
    color: #031837;
    text-decoration: none;
    transition: color 0.3s;
}

.research-card-title a:hover {
    color: #D3C9FE;
}

.research-card-meta {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
    font-size: 0.85rem;
    color: #666;
    flex-wrap: wrap;
}

.research-card-meta i {
    color: #D3C9FE;
    margin-right: 5px;
}

.research-excerpt {
    color: #666;
    line-height: 1.7;
    margin-bottom: 20px;
    flex: 1;
}

.research-card-footer {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
}

.read-more-btn {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 15px;
    background: #031837;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.3s;
}

.read-more-btn:hover {
    background: #D3C9FE;
    color: #031837;
    transform: translateY(-2px);
}

.download-pdf-btn {
    width: 40px;
    height: 40px;
    background: #dc3545;
    color: white;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.3s;
}

.download-pdf-btn:hover {
    background: #c82333;
    transform: translateY(-2px);
}

.research-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.research-tags .tag {
    background: #f0f4ff;
    color: #031837;
    padding: 3px 10px;
    border-radius: 15px;
    font-size: 0.7rem;
    font-weight: 500;
}

/* Sidebar */
.research-sidebar {
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

/* Featured Research List */
.featured-research-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.featured-research-item {
    border-bottom: 1px solid #f0f0f0;
    padding-bottom: 15px;
}

.featured-research-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.featured-research-link {
    display: flex;
    gap: 12px;
    text-decoration: none;
}

.featured-research-image {
    width: 70px;
    height: 50px;
    border-radius: 6px;
    overflow: hidden;
    flex-shrink: 0;
}

.featured-research-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.featured-research-content {
    flex: 1;
}

.featured-research-content h4 {
    color: #031837;
    font-size: 0.9rem;
    margin-bottom: 5px;
    line-height: 1.4;
}

.featured-research-author,
.featured-research-date {
    color: #666;
    font-size: 0.75rem;
    display: inline-flex;
    align-items: center;
    gap: 3px;
    margin-right: 10px;
}

.featured-research-author i,
.featured-research-date i {
    color: #D3C9FE;
}

/* Category List */
.category-list,
.years-list {
    list-style: none;
}

.category-list li,
.years-list li {
    margin-bottom: 10px;
    border-bottom: 1px solid #f0f0f0;
    padding-bottom: 10px;
}

.category-list li:last-child,
.years-list li:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.category-list a,
.years-list a {
    color: #666;
    text-decoration: none;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.3s;
}

.category-list a:hover,
.years-list a:hover,
.category-list a.active,
.years-list a.active {
    color: #031837;
    transform: translateX(5px);
}

.category-list i {
    color: #D3C9FE;
    margin-right: 8px;
}

.category-list .count,
.years-list .count {
    background: #f0f4ff;
    color: #031837;
    padding: 2px 8px;
    border-radius: 20px;
    font-size: 0.75rem;
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

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 40px;
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
@media (max-width: 1200px) {
    .research-grid {
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    }
}

@media (max-width: 992px) {
    .research-layout {
        grid-template-columns: 1fr;
    }
    
    .research-sidebar {
        position: static;
    }
    
    .research-stats-bar {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .research-stats-bar .stat-item {
        border-right: none;
        border-bottom: 1px solid rgba(211, 201, 254, 0.3);
        padding-bottom: 15px;
    }
    
    .research-stats-bar .stat-item:last-child {
        border-bottom: none;
    }
}

@media (max-width: 768px) {
    .filter-bar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-group {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-box {
        width: 100%;
    }
    
    .research-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .page-title {
        font-size: 2rem;
    }
    
    .research-stats-bar {
        grid-template-columns: 1fr;
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
                    body: JSON.stringify({email, source: 'research'})
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