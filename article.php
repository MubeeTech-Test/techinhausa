<?php
// article.php - Single News Article Page
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Get slug from URL
$slug = isset($_GET['slug']) ? sanitize($_GET['slug']) : '';

if (empty($slug)) {
    header('Location: news.php');
    exit();
}

// Get article details
$query = "
    SELECT n.*, c.name as category_name, c.slug as category_slug, c.icon as category_icon
    FROM news n
    LEFT JOIN categories c ON n.category_id = c.id
    WHERE n.slug = '$slug' AND n.is_published = 1
    LIMIT 1
";

$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    // Article not found, redirect to news listing
    header('Location: news.php');
    exit();
}

$article = mysqli_fetch_assoc($result);

// Update view count
mysqli_query($conn, "UPDATE news SET views = views + 1 WHERE id = " . $article['id']);

// Get related articles (same category, exclude current)
$related_query = "
    SELECT id, title, slug, excerpt, featured_image, published_at
    FROM news
    WHERE category_id = " . ($article['category_id'] ?: 'NULL') . "
    AND id != " . $article['id'] . "
    AND is_published = 1
    ORDER BY published_at DESC
    LIMIT 3
";
$related_result = mysqli_query($conn, $related_query);

// Get recent articles
$recent_query = "
    SELECT id, title, slug, published_at
    FROM news
    WHERE is_published = 1 AND id != " . $article['id'] . "
    ORDER BY published_at DESC
    LIMIT 5
";
$recent_result = mysqli_query($conn, $recent_query);

// Get categories for sidebar
$categories_query = "
    SELECT c.*, COUNT(n.id) as article_count
    FROM categories c
    LEFT JOIN news n ON c.id = n.category_id AND n.is_published = 1
    WHERE c.type = 'news'
    GROUP BY c.id
    ORDER BY c.name ASC
";
$categories_result = mysqli_query($conn, $categories_query);

// Set page title and meta description
$pageTitle = htmlspecialchars($article['title']) . " - TechInHausa News";
$pageDesc = htmlspecialchars($article['excerpt'] ?? substr(strip_tags($article['content']), 0, 160));

include 'partials/header.php';
?>

<section class="article-page-section">
    <div class="container">
        <div class="article-layout">
            <!-- Main Article Content -->
            <main class="article-main">
                <article class="article-content-wrapper">
                    <!-- Article Header -->
                    <header class="article-header">
                        <?php if (!empty($article['category_name'])): ?>
                            <a href="news.php?category=<?= $article['category_slug'] ?>" class="article-category">
                                <?php if (!empty($article['category_icon'])): ?>
                                    <i class="fas <?= $article['category_icon'] ?>"></i>
                                <?php endif; ?>
                                <?= htmlspecialchars($article['category_name']) ?>
                            </a>
                        <?php endif; ?>
                        
                        <h1 class="article-title"><?= htmlspecialchars($article['title']) ?></h1>
                        
                        <div class="article-meta">
                            <span class="article-author">
                                <i class="fas fa-user"></i> <?= htmlspecialchars($article['author'] ?? 'TechInHausa') ?>
                            </span>
                            <span class="article-date">
                                <i class="far fa-calendar-alt"></i> <?= formatDate($article['published_at']) ?>
                            </span>
                            <span class="article-views">
                                <i class="fas fa-eye"></i> <?= number_format($article['views'] + 1) ?> views
                            </span>
                            <?php if ($article['is_featured']): ?>
                                <span class="article-featured">
                                    <i class="fas fa-star"></i> Featured
                                </span>
                            <?php endif; ?>
                        </div>
                    </header>
                    
                    <!-- Featured Image -->
                    <?php if (!empty($article['featured_image'])): ?>
                        <div class="article-featured-image">
                            <img src="<?= getImageUrl($article['featured_image'], 'news') ?>" 
                                 alt="<?= htmlspecialchars($article['title']) ?>">
                        </div>
                    <?php endif; ?>
                    
                    <!-- Article Content -->
                    <div class="article-content">
                        <?php if (!empty($article['excerpt'])): ?>
                            <div class="article-excerpt">
                                <?= nl2br(htmlspecialchars($article['excerpt'])) ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="article-body">
                            <?= nl2br(htmlspecialchars($article['content'])) ?>
                        </div>
                    </div>
                    
                    <!-- Article Footer -->
                    <footer class="article-footer">
                        <?php if (!empty($article['tags'])): ?>
                            <div class="article-tags">
                                <h4>Tags:</h4>
                                <div class="tags-list">
                                    <?php 
                                    $tags = explode(',', $article['tags']);
                                    foreach ($tags as $tag): 
                                        $tag = trim($tag);
                                        if (!empty($tag)):
                                    ?>
                                        <a href="news.php?tag=<?= urlencode($tag) ?>" class="tag">
                                            #<?= htmlspecialchars($tag) ?>
                                        </a>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Share Buttons -->
                        <div class="article-share">
                            <h4>Share:</h4>
                            <div class="share-buttons">
                                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(SITE_URL . '/article.php?slug=' . $article['slug']) ?>" 
                                   target="_blank" class="share-btn facebook" title="Share on Facebook">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <a href="https://twitter.com/intent/tweet?url=<?= urlencode(SITE_URL . '/article.php?slug=' . $article['slug']) ?>&text=<?= urlencode($article['title']) ?>" 
                                   target="_blank" class="share-btn twitter" title="Share on Twitter">
                                    <i class="fab fa-twitter"></i>
                                </a>
                                <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode(SITE_URL . '/article.php?slug=' . $article['slug']) ?>" 
                                   target="_blank" class="share-btn linkedin" title="Share on LinkedIn">
                                    <i class="fab fa-linkedin-in"></i>
                                </a>
                                <a href="https://wa.me/?text=<?= urlencode($article['title'] . ' - ' . SITE_URL . '/article.php?slug=' . $article['slug']) ?>" 
                                   target="_blank" class="share-btn whatsapp" title="Share on WhatsApp">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                                <a href="mailto:?subject=<?= urlencode($article['title']) ?>&body=<?= urlencode('Check out this article: ' . SITE_URL . '/article.php?slug=' . $article['slug']) ?>" 
                                   class="share-btn email" title="Share via Email">
                                    <i class="fas fa-envelope"></i>
                                </a>
                            </div>
                        </div>
                    </footer>
                </article>
            </main>
            
            <!-- Sidebar -->
            <aside class="article-sidebar">
                <!-- Categories Widget -->
                <?php if ($categories_result && mysqli_num_rows($categories_result) > 0): ?>
                    <div class="sidebar-widget">
                        <h3 class="widget-title">Categories</h3>
                        <ul class="category-list">
                            <?php while ($cat = mysqli_fetch_assoc($categories_result)): ?>
                                <li>
                                    <a href="news.php?category=<?= $cat['slug'] ?>">
                                        <?php if (!empty($cat['icon'])): ?>
                                            <i class="fas <?= $cat['icon'] ?>"></i>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($cat['name']) ?>
                                        <span class="count">(<?= $cat['article_count'] ?>)</span>
                                    </a>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <!-- Recent Articles Widget -->
                <?php if ($recent_result && mysqli_num_rows($recent_result) > 0): ?>
                    <div class="sidebar-widget">
                        <h3 class="widget-title">Recent News</h3>
                        <ul class="recent-list">
                            <?php while ($recent = mysqli_fetch_assoc($recent_result)): ?>
                                <li>
                                    <a href="<?= SITE_URL ?>/article.php?slug=<?= $recent['slug'] ?>">
                                        <i class="fas fa-arrow-right"></i>
                                        <?= htmlspecialchars($recent['title']) ?>
                                        <span class="recent-date">
                                            <?= formatDate($recent['published_at']) ?>
                                        </span>
                                    </a>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <!-- Related Articles Widget -->
                <?php if ($related_result && mysqli_num_rows($related_result) > 0): ?>
                    <div class="sidebar-widget">
                        <h3 class="widget-title">Related News</h3>
                        <div class="related-articles">
                            <?php while ($related = mysqli_fetch_assoc($related_result)): ?>
                                <div class="related-item">
                                    <?php if (!empty($related['featured_image'])): ?>
                                        <div class="related-image">
                                            <img src="<?= getImageUrl($related['featured_image'], 'news') ?>" 
                                                 alt="<?= htmlspecialchars($related['title']) ?>">
                                        </div>
                                    <?php endif; ?>
                                    <div class="related-content">
                                        <h4>
                                            <a href="<?= SITE_URL ?>/article.php?slug=<?= $related['slug'] ?>">
                                                <?= htmlspecialchars($related['title']) ?>
                                            </a>
                                        </h4>
                                        <span class="related-date">
                                            <i class="far fa-calendar-alt"></i> <?= formatDate($related['published_at']) ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Newsletter Widget -->
                <div class="sidebar-widget newsletter-widget">
                    <h3 class="widget-title">Newsletter</h3>
                    <p>Get the latest news delivered to your inbox</p>
                    <form id="sidebarNewsletter" class="sidebar-newsletter">
                        <input type="email" name="email" placeholder="Your email address" required>
                        <button type="submit"><i class="fas fa-paper-plane"></i></button>
                    </form>
                </div>
            </aside>
        </div>
    </div>
</section>

<style>
/* Article Page Styles */
.article-page-section {
    padding: 60px 0;
    background: #f5f7fb;
}

.article-layout {
    display: grid;
    grid-template-columns: 2.5fr 1fr;
    gap: 40px;
}

/* Main Article */
.article-main {
    background: white;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    border: 1px solid rgba(211, 201, 254, 0.2);
}

/* Article Header */
.article-header {
    margin-bottom: 30px;
}

.article-category {
    display: inline-block;
    background: #D3C9FE;
    color: #031837;
    padding: 5px 15px;
    border-radius: 30px;
    font-size: 0.85rem;
    font-weight: 600;
    text-decoration: none;
    margin-bottom: 15px;
    transition: all 0.3s;
}

.article-category:hover {
    background: #b8a9fe;
    transform: translateY(-2px);
}

.article-category i {
    margin-right: 5px;
}

.article-title {
    font-size: 2.5rem;
    color: #031837;
    margin-bottom: 20px;
    line-height: 1.3;
}

.article-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    color: #666;
    font-size: 0.95rem;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.article-meta span {
    display: flex;
    align-items: center;
    gap: 5px;
}

.article-meta i {
    color: #D3C9FE;
}

.article-featured {
    background: rgba(211, 201, 254, 0.2);
    padding: 3px 10px;
    border-radius: 20px;
    color: #031837;
    font-weight: 600;
    font-size: 0.8rem;
}

/* Featured Image */
.article-featured-image {
    margin-bottom: 30px;
    border-radius: 15px;
    overflow: hidden;
}

.article-featured-image img {
    width: 100%;
    height: auto;
    display: block;
}

/* Article Content */
.article-excerpt {
    background: #f8f9ff;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 30px;
    font-size: 1.1rem;
    font-style: italic;
    color: #031837;
    border-left: 4px solid #D3C9FE;
}

.article-body {
    line-height: 1.8;
    color: #444;
}

.article-body p {
    margin-bottom: 20px;
}

.article-body h2 {
    font-size: 1.8rem;
    color: #031837;
    margin: 30px 0 15px;
}

.article-body h3 {
    font-size: 1.4rem;
    color: #031837;
    margin: 25px 0 15px;
}

.article-body ul,
.article-body ol {
    margin-bottom: 20px;
    padding-left: 20px;
}

.article-body li {
    margin-bottom: 10px;
}

.article-body blockquote {
    background: #f8f9ff;
    padding: 20px;
    border-radius: 12px;
    margin: 20px 0;
    border-left: 4px solid #D3C9FE;
    font-style: italic;
}

/* Article Footer */
.article-footer {
    margin-top: 40px;
    padding-top: 30px;
    border-top: 1px solid #eee;
}

/* Tags */
.article-tags {
    margin-bottom: 30px;
}

.article-tags h4 {
    color: #031837;
    font-size: 1rem;
    margin-bottom: 10px;
}

.tags-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.tags-list .tag {
    background: #f0f4ff;
    color: #031837;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    text-decoration: none;
    transition: all 0.3s;
}

.tags-list .tag:hover {
    background: #D3C9FE;
    transform: translateY(-2px);
}

/* Share Buttons */
.article-share h4 {
    color: #031837;
    font-size: 1rem;
    margin-bottom: 15px;
}

.share-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.share-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    text-decoration: none;
    transition: all 0.3s;
}

.share-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.share-btn.facebook { background: #1877f2; }
.share-btn.twitter { background: #1da1f2; }
.share-btn.linkedin { background: #0077b5; }
.share-btn.whatsapp { background: #25d366; }
.share-btn.email { background: #666; }

/* Sidebar */
.article-sidebar {
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

/* Recent List */
.recent-list {
    list-style: none;
}

.recent-list li {
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #f0f0f0;
}

.recent-list li:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.recent-list a {
    color: #666;
    text-decoration: none;
    display: block;
    transition: all 0.3s;
}

.recent-list a:hover {
    color: #031837;
    transform: translateX(5px);
}

.recent-list i {
    color: #D3C9FE;
    margin-right: 8px;
    font-size: 0.8rem;
}

.recent-date {
    display: block;
    font-size: 0.75rem;
    color: #999;
    margin-top: 5px;
    margin-left: 23px;
}

/* Related Articles */
.related-articles {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.related-item {
    display: flex;
    gap: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #f0f0f0;
}

.related-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.related-image {
    width: 80px;
    height: 60px;
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
}

.related-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.related-content {
    flex: 1;
}

.related-content h4 {
    font-size: 0.95rem;
    margin-bottom: 5px;
    line-height: 1.4;
}

.related-content h4 a {
    color: #031837;
    text-decoration: none;
    transition: color 0.3s;
}

.related-content h4 a:hover {
    color: #D3C9FE;
}

.related-date {
    font-size: 0.75rem;
    color: #666;
    display: flex;
    align-items: center;
    gap: 3px;
}

.related-date i {
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

/* Back Link */
.back-link {
    margin-top: 30px;
    text-align: center;
}

.back-to-news {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 30px;
    background: transparent;
    color: #031837;
    text-decoration: none;
    border: 2px solid #D3C9FE;
    border-radius: 50px;
    font-weight: 600;
    transition: all 0.3s;
}

.back-to-news:hover {
    background: #D3C9FE;
    color: #031837;
    transform: translateY(-2px);
}

/* Responsive */
@media (max-width: 992px) {
    .article-layout {
        grid-template-columns: 1fr;
    }
    
    .article-sidebar {
        position: static;
    }
}

@media (max-width: 768px) {
    .article-main {
        padding: 25px;
    }
    
    .article-title {
        font-size: 2rem;
    }
    
    .article-meta {
        gap: 15px;
        font-size: 0.85rem;
    }
    
    .article-excerpt {
        padding: 20px;
        font-size: 1rem;
    }
}

@media (max-width: 480px) {
    .article-title {
        font-size: 1.5rem;
    }
    
    .article-meta {
        flex-direction: column;
        gap: 10px;
    }
    
    .sidebar-newsletter {
        flex-direction: column;
    }
    
    .sidebar-newsletter button {
        width: 100%;
    }
    
    .related-item {
        flex-direction: column;
    }
    
    .related-image {
        width: 100%;
        height: 120px;
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
                    body: JSON.stringify({email, source: 'article'})
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