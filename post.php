<?php
// article.php - Single News Article
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Get article ID or slug
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$slug = isset($_GET['slug']) ? sanitize($_GET['slug']) : '';

if ($id <= 0 && empty($slug)) {
    header('Location: news.php');
    exit();
}

// Build query based on ID or slug
if ($id > 0) {
    $where = "n.id = $id";
} else {
    $slug = mysqli_real_escape_string($conn, $slug);
    $where = "n.slug = '$slug'";
}

// Get article details
$query = "
    SELECT n.*, c.name as category_name, c.slug as category_slug
    FROM news n
    LEFT JOIN categories c ON n.category_id = c.id
    WHERE $where AND n.is_published = 1
    LIMIT 1
";

$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) === 0) {
    header('Location: news.php');
    exit();
}

$article = mysqli_fetch_assoc($result);

// Update view count
mysqli_query($conn, "UPDATE news SET views = views + 1 WHERE id = {$article['id']}");

// Get related articles (same category)
$related_query = "
    SELECT id, title, slug, featured_image, excerpt, published_at
    FROM news
    WHERE category_id = {$article['category_id']} 
    AND id != {$article['id']}
    AND is_published = 1
    ORDER BY published_at DESC
    LIMIT 3
";
$related_result = mysqli_query($conn, $related_query);

// Get previous and next articles
$prev_query = "
    SELECT id, title, slug 
    FROM news 
    WHERE published_at < '{$article['published_at']}' 
    AND is_published = 1 
    ORDER BY published_at DESC 
    LIMIT 1
";
$prev_result = mysqli_query($conn, $prev_query);
$prev_article = mysqli_fetch_assoc($prev_result);

$next_query = "
    SELECT id, title, slug 
    FROM news 
    WHERE published_at > '{$article['published_at']}' 
    AND is_published = 1 
    ORDER BY published_at ASC 
    LIMIT 1
";
$next_result = mysqli_query($conn, $next_query);
$next_article = mysqli_fetch_assoc($next_result);

$pageTitle = $article['title'] . " - TechInHausa";
$pageDesc = $article['excerpt'] ?? substr(strip_tags($article['content']), 0, 160);

include 'partials/header.php';
?>

<article class="single-article">
    <div class="container">
        <!-- Article Header -->
        <header class="article-header">
            <div class="article-meta-top">
                <?php if (!empty($article['category_name'])): ?>
                    <a href="news.php?category=<?= $article['category_slug'] ?>" class="article-category">
                        <?= htmlspecialchars($article['category_name']) ?>
                    </a>
                <?php endif; ?>
                
                <span class="article-date">
                    <i class="far fa-calendar-alt"></i> 
                    <?= formatDateHausa($article['published_at']) ?>
                </span>
                
                <span class="article-author">
                    <i class="fas fa-user"></i> 
                    <?= htmlspecialchars($article['author'] ?? 'MalamIromba') ?>
                </span>
                
                <span class="article-views">
                    <i class="fas fa-eye"></i> 
                    <?= number_format($article['views'] + 1) ?> views
                </span>
            </div>
            
            <h1 class="article-title"><?= htmlspecialchars($article['title']) ?></h1>
            
            <?php if (!empty($article['excerpt'])): ?>
                <p class="article-excerpt"><?= htmlspecialchars($article['excerpt']) ?></p>
            <?php endif; ?>
        </header>
        
        <!-- Featured Image -->
        <?php if (!empty($article['featured_image'])): ?>
            <div class="article-featured-image">
                <img src="<?= getImageUrl($article['featured_image'], 'news') ?>" 
                     alt="<?= htmlspecialchars($article['title']) ?>"
                     onerror="this.src='<?= SITE_URL ?>/assets/images/techinhausa-about.jpg'">
                <!-- NO PLAY BUTTON - This is an article, not a video -->
            </div>
        <?php endif; ?>
        
        <!-- Article Content -->
        <div class="article-content-wrapper">
            <div class="article-main">
                <div class="article-content">
                    <?= nl2br(htmlspecialchars($article['content'])) ?>
                </div>
                
                <!-- Tags -->
                <?php if (!empty($article['tags'])): ?>
                    <div class="article-tags">
                        <h3><i class="fas fa-tags"></i> Tags:</h3>
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
                    <h3>Share this article:</h3>
                    <div class="share-buttons">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(SITE_URL . '/article.php?id=' . $article['id']) ?>" 
                           target="_blank" class="share-btn facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?= urlencode(SITE_URL . '/article.php?id=' . $article['id']) ?>&text=<?= urlencode($article['title']) ?>" 
                           target="_blank" class="share-btn twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="https://wa.me/?text=<?= urlencode($article['title'] . ' ' . SITE_URL . '/article.php?id=' . $article['id']) ?>" 
                           target="_blank" class="share-btn whatsapp">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                        <a href="https://t.me/share/url?url=<?= urlencode(SITE_URL . '/article.php?id=' . $article['id']) ?>&text=<?= urlencode($article['title']) ?>" 
                           target="_blank" class="share-btn telegram">
                            <i class="fab fa-telegram-plane"></i>
                        </a>
                        <a href="mailto:?subject=<?= urlencode($article['title']) ?>&body=<?= urlencode($article['title'] . ' - ' . SITE_URL . '/article.php?id=' . $article['id']) ?>" 
                           class="share-btn email">
                            <i class="fas fa-envelope"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Post Navigation -->
                <div class="post-navigation">
                    <?php if ($prev_article): ?>
                        <a href="article.php?id=<?= $prev_article['id'] ?>&slug=<?= $prev_article['slug'] ?>" class="post-nav prev">
                            <i class="fas fa-arrow-left"></i>
                            <div class="post-nav-content">
                                <span>Previous article</span>
                                <h4><?= htmlspecialchars($prev_article['title']) ?></h4>
                            </div>
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($next_article): ?>
                        <a href="article.php?id=<?= $next_article['id'] ?>&slug=<?= $next_article['slug'] ?>" class="post-nav next">
                            <div class="post-nav-content">
                                <span>Next article</span>
                                <h4><?= htmlspecialchars($next_article['title']) ?></h4>
                            </div>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Sidebar -->
            <aside class="article-sidebar">
                <!-- Related Articles -->
                <?php if ($related_result && mysqli_num_rows($related_result) > 0): ?>
                    <div class="sidebar-widget">
                        <h3 class="widget-title">Related Articles</h3>
                        <div class="related-articles">
                            <?php while ($related = mysqli_fetch_assoc($related_result)): ?>
                                <div class="related-article">
                                    <a href="article.php?id=<?= $related['id'] ?>&slug=<?= $related['slug'] ?>" class="related-article-link">
                                        <?php if (!empty($related['featured_image'])): ?>
                                            <div class="related-article-image">
                                                <img src="<?= getImageUrl($related['featured_image'], 'news') ?>" 
                                                     alt="<?= htmlspecialchars($related['title']) ?>">
                                            </div>
                                        <?php endif; ?>
                                        <div class="related-article-content">
                                            <h4><?= htmlspecialchars($related['title']) ?></h4>
                                            <span class="related-article-date">
                                                <i class="far fa-calendar-alt"></i> 
                                                <?= formatDateHausa($related['published_at']) ?>
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
                    <p>Receive latest news directly to your email</p>
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
/* Single Article Styles */
.single-article {
    padding: 60px 0;
    background: #f5f7fb;
}

/* Article Header */
.article-header {
    max-width: 800px;
    margin: 0 auto 40px;
    text-align: center;
}

.article-meta-top {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
    margin-bottom: 20px;
    font-size: 0.9rem;
}

.article-category {
    background: #D3C9FE;
    color: #031837;
    padding: 5px 15px;
    border-radius: 20px;
    font-weight: 600;
    text-decoration: none;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.article-category:hover {
    background: #b8a9fe;
}

.article-date,
.article-author,
.article-views {
    color: #666;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.article-date i,
.article-author i,
.article-views i {
    color: #D3C9FE;
}

.article-title {
    font-size: 2.8rem;
    color: #031837;
    margin-bottom: 20px;
    line-height: 1.3;
}

.article-excerpt {
    font-size: 1.2rem;
    color: #666;
    font-style: italic;
    max-width: 600px;
    margin: 0 auto;
}

/* Featured Image */
.article-featured-image {
    max-width: 900px;
    margin: 0 auto 50px;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
}

.article-featured-image img {
    width: 100%;
    height: auto;
    display: block;
}

/* Article Content Wrapper */
.article-content-wrapper {
    display: grid;
    grid-template-columns: 2.5fr 1fr;
    gap: 40px;
    max-width: 1200px;
    margin: 0 auto;
}

/* Article Main Content */
.article-main {
    background: white;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    border: 1px solid rgba(211, 201, 254, 0.2);
}

.article-content {
    font-size: 1.1rem;
    line-height: 1.9;
    color: #333;
    margin-bottom: 40px;
}

.article-content p {
    margin-bottom: 25px;
}

.article-content h2,
.article-content h3 {
    color: #031837;
    margin: 30px 0 15px;
}

.article-content h2 {
    font-size: 1.8rem;
}

.article-content h3 {
    font-size: 1.4rem;
}

.article-content ul,
.article-content ol {
    margin: 20px 0;
    padding-left: 30px;
}

.article-content li {
    margin-bottom: 10px;
}

.article-content blockquote {
    background: #f8f9ff;
    border-left: 4px solid #D3C9FE;
    padding: 20px 30px;
    font-style: italic;
    margin: 30px 0;
    border-radius: 0 10px 10px 0;
}

.article-content img {
    max-width: 100%;
    height: auto;
    border-radius: 10px;
    margin: 20px 0;
}

/* Tags */
.article-tags {
    margin-bottom: 30px;
    padding: 20px 0;
    border-top: 1px solid #eee;
    border-bottom: 1px solid #eee;
}

.article-tags h3 {
    color: #031837;
    font-size: 1rem;
    margin-bottom: 10px;
}

.article-tags h3 i {
    color: #D3C9FE;
}

.tags-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.tag {
    background: #f0f4ff;
    color: #031837;
    padding: 6px 15px;
    border-radius: 20px;
    text-decoration: none;
    font-size: 0.85rem;
    transition: all 0.3s;
}

.tag:hover {
    background: #D3C9FE;
    transform: translateY(-2px);
}

/* Share Buttons */
.article-share {
    margin-bottom: 30px;
}

.article-share h3 {
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
    transform: translateY(-3px);
}

.share-btn.facebook { background: #1877f2; }
.share-btn.twitter { background: #1da1f2; }
.share-btn.whatsapp { background: #25d366; }
.share-btn.telegram { background: #0088cc; }
.share-btn.email { background: #666; }

/* Post Navigation */
.post-navigation {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    margin-top: 40px;
    padding-top: 30px;
    border-top: 2px solid #f0f0f0;
}

.post-nav {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: #f8f9ff;
    border-radius: 12px;
    text-decoration: none;
    transition: all 0.3s;
    border: 1px solid rgba(211, 201, 254, 0.2);
}

.post-nav:hover {
    background: #D3C9FE;
    transform: translateY(-2px);
}

.post-nav.prev {
    text-align: left;
}

.post-nav.next {
    text-align: right;
    justify-content: flex-end;
}

.post-nav i {
    color: #031837;
    font-size: 1.2rem;
}

.post-nav-content span {
    color: #666;
    font-size: 0.8rem;
    display: block;
    margin-bottom: 5px;
}

.post-nav-content h4 {
    color: #031837;
    font-size: 0.95rem;
    line-height: 1.4;
}

.post-nav:hover .post-nav-content h4 {
    color: #031837;
}

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

/* Related Articles */
.related-articles {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.related-article-link {
    display: flex;
    gap: 15px;
    text-decoration: none;
    transition: all 0.3s;
}

.related-article-link:hover {
    transform: translateX(5px);
}

.related-article-image {
    width: 80px;
    height: 60px;
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
}

.related-article-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.related-article-content {
    flex: 1;
}

.related-article-content h4 {
    color: #031837;
    font-size: 0.95rem;
    margin-bottom: 5px;
    line-height: 1.4;
}

.related-article-date {
    color: #666;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    gap: 3px;
}

.related-article-date i {
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
    .article-content-wrapper {
        grid-template-columns: 1fr;
    }
    
    .article-sidebar {
        position: static;
    }
    
    .article-title {
        font-size: 2.2rem;
    }
}

@media (max-width: 768px) {
    .single-article {
        padding: 40px 0;
    }
    
    .article-title {
        font-size: 1.8rem;
    }
    
    .article-meta-top {
        gap: 10px;
    }
    
    .article-main {
        padding: 25px;
    }
    
    .post-navigation {
        flex-direction: column;
    }
    
    .post-nav {
        width: 100%;
    }
}

@media (max-width: 480px) {
    .article-title {
        font-size: 1.5rem;
    }
    
    .article-excerpt {
        font-size: 1rem;
    }
    
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