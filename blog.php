<?php
// blog.php - TechInHausa Blog Listing
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = "Blog - TechInHausa";
$pageDesc = "Latest blog posts about tech, programming, and AI in the Hausa language. Tutorials and tips to enhance your knowledge.";

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 6;
$offset = ($page - 1) * $limit;

// Get total blog posts count
$total_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM blog_posts WHERE is_published = 1");
$total_row = mysqli_fetch_assoc($total_result);
$total_posts = $total_row['count'];
$total_pages = ceil($total_posts / $limit);

// Get blog posts with category
$blog_query = "
    SELECT b.*, c.name as category_name, c.slug as category_slug, c.icon as category_icon
    FROM blog_posts b
    LEFT JOIN categories c ON b.category_id = c.id
    WHERE b.is_published = 1 
    ORDER BY b.published_at DESC 
    LIMIT $offset, $limit
";

$blog_result = mysqli_query($conn, $blog_query);

// Get featured posts for sidebar
$featured_posts = mysqli_query($conn, "
    SELECT id, title, slug, featured_image, published_at 
    FROM blog_posts 
    WHERE is_published = 1 AND is_featured = 1 
    ORDER BY published_at DESC 
    LIMIT 5
");

// Get popular posts by views
$popular_posts = mysqli_query($conn, "
    SELECT id, title, slug, views 
    FROM blog_posts 
    WHERE is_published = 1 
    ORDER BY views DESC 
    LIMIT 5
");

// Get categories for filtering
$categories = mysqli_query($conn, "
    SELECT c.*, COUNT(b.id) as post_count 
    FROM categories c
    LEFT JOIN blog_posts b ON c.id = b.category_id AND b.is_published = 1
    WHERE c.type = 'blog'
    GROUP BY c.id
    ORDER BY c.name ASC
");

include 'partials/header.php';
?>

<section class="blog-page-section">
    <div class="container">
        <div class="blog-header">
            <h1 class="page-title">Blog</h1>
            <p class="page-subtitle">Tutorials, tips, and articles to enhance your tech knowledge</p>
        </div>
        
        <div class="blog-layout">
            <!-- Main Blog Grid -->
            <div class="blog-main">
                <?php if (mysqli_num_rows($blog_result) > 0): ?>
                    <div class="blog-grid">
                        <?php while ($post = mysqli_fetch_assoc($blog_result)): ?>
                            <article class="blog-card">
                                <div class="blog-card-image">
                                    <a href="<?= SITE_URL ?>/post.php?id=<?= $post['id'] ?>&slug=<?= $post['slug'] ?>">
                                        <img src="<?= getImageUrl($post['featured_image'] ?? '', 'blog') ?>" 
                                             alt="<?= htmlspecialchars($post['title']) ?>"
                                             onerror="this.src='<?= SITE_URL ?>/assets/images/techinhausa-about.jpg'">
                                        <?php if ($post['is_featured']): ?>
                                            <span class="featured-badge">
                                                <i class="fas fa-star"></i> Featured
                                            </span>
                                        <?php endif; ?>
                                    </a>
                                </div>
                                
                                <div class="blog-card-content">
                                    <div class="blog-meta-top">
                                        <?php if (!empty($post['category_name'])): ?>
                                            <a href="?category=<?= $post['category_slug'] ?>" class="blog-category">
                                                <?php if (!empty($post['category_icon'])): ?>
                                                    <i class="fas <?= $post['category_icon'] ?>"></i>
                                                <?php endif; ?>
                                                <?= htmlspecialchars($post['category_name']) ?>
                                            </a>
                                        <?php endif; ?>
                                        <span class="blog-date">
                                            <i class="far fa-calendar-alt"></i> 
                                            <?= formatDateHausa($post['published_at']) ?>
                                        </span>
                                    </div>
                                    
                                    <h2 class="blog-title">
                                        <a href="<?= SITE_URL ?>/post.php?id=<?= $post['id'] ?>&slug=<?= $post['slug'] ?>">
                                            <?= htmlspecialchars($post['title']) ?>
                                        </a>
                                    </h2>
                                    
                                    <p class="blog-excerpt">
                                        <?= truncateText($post['excerpt'] ?? $post['content'], 120) ?>
                                    </p>
                                    
                                    <div class="blog-meta-bottom">
                                        <span class="blog-author">
                                            <i class="fas fa-user"></i> <?= htmlspecialchars($post['author'] ?? 'MalamIromba') ?>
                                        </span>
                                        <span class="blog-views">
                                            <i class="fas fa-eye"></i> <?= number_format($post['views'] ?? 0) ?>
                                        </span>
                                        <a href="<?= SITE_URL ?>/post.php?id=<?= $post['id'] ?>&slug=<?= $post['slug'] ?>" class="read-more-link">
                                            Read More <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                    
                                    <?php if (!empty($post['tags'])): ?>
                                        <div class="blog-tags">
                                            <?php 
                                            $tags = explode(',', $post['tags']);
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
                        <i class="fas fa-blog"></i>
                        <h3>No Blog Posts Yet</h3>
                        <p>We will bring you new tutorials soon.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <aside class="blog-sidebar">
                <!-- Search Widget -->
                <div class="sidebar-widget search-widget">
                    <h3 class="widget-title">Search</h3>
                    <form action="blog.php" method="GET" class="search-form">
                        <input type="text" name="s" placeholder="Search blog..." value="<?= isset($_GET['s']) ? htmlspecialchars($_GET['s']) : '' ?>">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                </div>
                
                <!-- Categories Widget -->
                <?php if ($categories && mysqli_num_rows($categories) > 0): ?>
                    <div class="sidebar-widget">
                        <h3 class="widget-title">Categories</h3>
                        <ul class="category-list">
                            <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                                <li>
                                    <a href="?category=<?= $cat['slug'] ?>">
                                        <?php if (!empty($cat['icon'])): ?>
                                            <i class="fas <?= $cat['icon'] ?>"></i>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($cat['name']) ?>
                                        <span class="count">(<?= $cat['post_count'] ?>)</span>
                                    </a>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <!-- Featured Posts Widget -->
                <?php if ($featured_posts && mysqli_num_rows($featured_posts) > 0): ?>
                    <div class="sidebar-widget">
                        <h3 class="widget-title">Featured Posts</h3>
                        <div class="featured-posts-list">
                            <?php while ($featured = mysqli_fetch_assoc($featured_posts)): ?>
                                <div class="featured-post-item">
                                    <a href="<?= SITE_URL ?>/post.php?id=<?= $featured['id'] ?>&slug=<?= $featured['slug'] ?>" class="featured-post-link">
                                        <?php if (!empty($featured['featured_image'])): ?>
                                            <div class="featured-post-image">
                                                <img src="<?= getImageUrl($featured['featured_image'], 'blog') ?>" 
                                                     alt="<?= htmlspecialchars($featured['title']) ?>">
                                            </div>
                                        <?php endif; ?>
                                        <div class="featured-post-content">
                                            <h4><?= htmlspecialchars($featured['title']) ?></h4>
                                            <span class="featured-post-date">
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
                
                <!-- Popular Posts Widget -->
                <?php if ($popular_posts && mysqli_num_rows($popular_posts) > 0): ?>
                    <div class="sidebar-widget">
                        <h3 class="widget-title">Popular Posts</h3>
                        <ul class="popular-posts-list">
                            <?php while ($popular = mysqli_fetch_assoc($popular_posts)): ?>
                                <li>
                                    <a href="<?= SITE_URL ?>/post.php?id=<?= $popular['id'] ?>&slug=<?= $popular['slug'] ?>">
                                        <i class="fas fa-arrow-right"></i>
                                        <?= htmlspecialchars($popular['title']) ?>
                                        <span class="views-count"><?= number_format($popular['views']) ?> views</span>
                                    </a>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <!-- Newsletter Widget -->
                <div class="sidebar-widget newsletter-widget">
                    <h3 class="widget-title">Join Our Newsletter</h3>
                    <p>Receive new tutorials directly to your email</p>
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
/* Blog Page Styles */
.blog-page-section {
    padding: 60px 0;
    background: #f5f7fb;
}

.blog-header {
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

.blog-layout {
    display: grid;
    grid-template-columns: 2.5fr 1fr;
    gap: 40px;
}

/* Blog Grid */
.blog-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 30px;
}

.blog-card {
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

.blog-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 30px rgba(211, 201, 254, 0.3);
    border-color: #D3C9FE;
}

.blog-card-image {
    position: relative;
    aspect-ratio: 16/9;
    overflow: hidden;
}

.blog-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s;
}

.blog-card:hover .blog-card-image img {
    transform: scale(1.1);
}

.featured-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    background: linear-gradient(135deg, #D3C9FE, #b8a9fe);
    color: #031837;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 5px;
    z-index: 2;
}

.blog-card-content {
    padding: 25px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.blog-meta-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    font-size: 0.85rem;
}

.blog-category {
    background: rgba(211, 201, 254, 0.2);
    color: #031837;
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 600;
    text-decoration: none;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.blog-category:hover {
    background: #D3C9FE;
}

.blog-date {
    color: #666;
    display: flex;
    align-items: center;
    gap: 5px;
}

.blog-date i {
    color: #D3C9FE;
}

.blog-title {
    font-size: 1.3rem;
    margin-bottom: 15px;
    line-height: 1.4;
}

.blog-title a {
    color: #031837;
    text-decoration: none;
    transition: color 0.3s;
}

.blog-title a:hover {
    color: #D3C9FE;
}

.blog-excerpt {
    color: #666;
    line-height: 1.7;
    margin-bottom: 20px;
    flex: 1;
}

.blog-meta-bottom {
    display: flex;
    align-items: center;
    gap: 15px;
    font-size: 0.9rem;
    color: #666;
    border-top: 1px solid #eee;
    padding-top: 15px;
    margin-bottom: 15px;
}

.blog-author i,
.blog-views i {
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

.blog-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

.blog-tags .tag {
    background: #f0f4ff;
    color: #031837;
    padding: 3px 10px;
    border-radius: 15px;
    font-size: 0.7rem;
    font-weight: 500;
}

/* Sidebar */
.blog-sidebar {
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

/* Search Widget */
.search-widget .search-form {
    display: flex;
    gap: 10px;
}

.search-widget input {
    flex: 1;
    padding: 12px;
    border: 1px solid rgba(211, 201, 254, 0.3);
    border-radius: 8px;
    font-size: 0.9rem;
}

.search-widget input:focus {
    outline: none;
    border-color: #D3C9FE;
}

.search-widget button {
    width: 45px;
    height: 45px;
    background: #031837;
    border: none;
    border-radius: 8px;
    color: white;
    cursor: pointer;
    transition: all 0.3s;
}

.search-widget button:hover {
    background: #D3C9FE;
    color: #031837;
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
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
}

.category-list a:hover {
    color: #031837;
    transform: translateX(5px);
}

.category-list i {
    color: #D3C9FE;
    width: 20px;
}

.category-list .count {
    margin-left: auto;
    background: #f0f4ff;
    color: #031837;
    padding: 2px 8px;
    border-radius: 20px;
    font-size: 0.8rem;
}

/* Featured Posts List */
.featured-posts-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.featured-post-item {
    border-bottom: 1px solid #f0f0f0;
    padding-bottom: 20px;
}

.featured-post-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.featured-post-link {
    display: flex;
    gap: 15px;
    text-decoration: none;
}

.featured-post-image {
    width: 80px;
    height: 60px;
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
}

.featured-post-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.featured-post-content {
    flex: 1;
}

.featured-post-content h4 {
    color: #031837;
    font-size: 0.95rem;
    margin-bottom: 5px;
    line-height: 1.4;
}

.featured-post-date {
    color: #666;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    gap: 3px;
}

.featured-post-date i {
    color: #D3C9FE;
}

/* Popular Posts List */
.popular-posts-list {
    list-style: none;
}

.popular-posts-list li {
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #f0f0f0;
}

.popular-posts-list li:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.popular-posts-list a {
    color: #666;
    text-decoration: none;
    display: block;
    transition: all 0.3s;
}

.popular-posts-list a:hover {
    color: #031837;
    transform: translateX(5px);
}

.popular-posts-list i {
    color: #D3C9FE;
    margin-right: 8px;
    font-size: 0.8rem;
}

.popular-posts-list .views-count {
    display: block;
    font-size: 0.75rem;
    color: #999;
    margin-top: 5px;
    margin-left: 23px;
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
    .blog-layout {
        grid-template-columns: 1fr;
    }
    
    .blog-sidebar {
        position: static;
    }
    
    .blog-grid {
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    }
}

@media (max-width: 768px) {
    .page-title {
        font-size: 2rem;
    }
    
    .blog-meta-bottom {
        flex-wrap: wrap;
    }
    
    .read-more-link {
        margin-left: 0;
    }
}

@media (max-width: 480px) {
    .blog-grid {
        grid-template-columns: 1fr;
    }
    
    .pagination {
        gap: 5px;
    }
    
    .page-link {
        padding: 8px 12px;
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
                    body: JSON.stringify({email, source: 'blog'})
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