<?php
// paper.php - Single Research Paper Page
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Get paper ID or slug
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$slug = isset($_GET['slug']) ? sanitize($_GET['slug']) : '';

if ($id <= 0 && empty($slug)) {
    header('Location: research.php');
    exit();
}

// Build query based on ID or slug
if ($id > 0) {
    $where = "r.id = $id";
} else {
    $slug = mysqli_real_escape_string($conn, $slug);
    $where = "r.slug = '$slug'";
}

// Get paper details
$query = "
    SELECT r.*, c.name as category_name, c.slug as category_slug, c.icon as category_icon
    FROM research r
    LEFT JOIN categories c ON r.category_id = c.id
    WHERE $where AND r.is_published = 1
    LIMIT 1
";

$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) === 0) {
    header('Location: research.php');
    exit();
}

$paper = mysqli_fetch_assoc($result);

// Update view count
mysqli_query($conn, "UPDATE research SET views = views + 1 WHERE id = {$paper['id']}");

// Get related papers (same category)
$related_query = "
    SELECT id, title, slug, excerpt, featured_image, author, published_at
    FROM research
    WHERE category_id = {$paper['category_id']} 
    AND id != {$paper['id']}
    AND is_published = 1
    ORDER BY published_at DESC
    LIMIT 4
";
$related_result = mysqli_query($conn, $related_query);

// Get previous and next papers
$prev_query = "
    SELECT id, title, slug 
    FROM research 
    WHERE published_at < '{$paper['published_at']}' 
    AND is_published = 1 
    ORDER BY published_at DESC 
    LIMIT 1
";
$prev_result = mysqli_query($conn, $prev_query);
$prev_paper = mysqli_fetch_assoc($prev_result);

$next_query = "
    SELECT id, title, slug 
    FROM research 
    WHERE published_at > '{$paper['published_at']}' 
    AND is_published = 1 
    ORDER BY published_at ASC 
    LIMIT 1
";
$next_result = mysqli_query($conn, $next_query);
$next_paper = mysqli_fetch_assoc($next_result);

$pageTitle = $paper['title'] . " - TechInHausa Research";
$pageDesc = $paper['excerpt'] ?? $paper['abstract'] ?? substr(strip_tags($paper['content']), 0, 160);

include 'partials/header.php';
?>

<article class="research-single">
    <div class="container">
        <!-- Back to Research -->
        <div class="back-to-research">
            <a href="research.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to All Research
            </a>
        </div>
        
        <div class="research-single-wrapper">
            <!-- Main Content -->
            <div class="research-single-main">
                <!-- Paper Header -->
                <header class="paper-header">
                    <div class="paper-meta-top">
                        <?php if (!empty($paper['category_name'])): ?>
                            <a href="research.php?category=<?= $paper['category_id'] ?>" class="paper-category">
                                <?php if (!empty($paper['category_icon'])): ?>
                                    <i class="fas <?= $paper['category_icon'] ?>"></i>
                                <?php endif; ?>
                                <?= htmlspecialchars($paper['category_name']) ?>
                            </a>
                        <?php endif; ?>
                        
                        <span class="paper-date">
                            <i class="far fa-calendar-alt"></i> 
                            <?= formatDateHausa($paper['published_at']) ?>
                        </span>
                        
                        <span class="paper-author">
                            <i class="fas fa-user"></i> 
                            <?= htmlspecialchars($paper['author'] ?? 'MalamIromba') ?>
                        </span>
                        
                        <span class="paper-views">
                            <i class="fas fa-eye"></i> 
                            <?= number_format($paper['views'] + 1) ?> views
                        </span>
                        
                        <?php if ($paper['is_featured']): ?>
                            <span class="paper-featured">
                                <i class="fas fa-star"></i> Featured
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <h1 class="paper-title"><?= htmlspecialchars($paper['title']) ?></h1>
                    
                    <?php if (!empty($paper['excerpt'])): ?>
                        <div class="paper-excerpt">
                            <p><?= nl2br(htmlspecialchars($paper['excerpt'])) ?></p>
                        </div>
                    <?php endif; ?>
                </header>
                
                <!-- Featured Image -->
                <?php if (!empty($paper['featured_image'])): ?>
                    <div class="paper-featured-image">
                        <img src="<?= getImageUrl($paper['featured_image'], 'research') ?>" 
                             alt="<?= htmlspecialchars($paper['title']) ?>"
                             onerror="this.src='<?= SITE_URL ?>/assets/images/techinhausa-about.jpg'">
                    </div>
                <?php endif; ?>
                
                <!-- Abstract Section -->
                <?php if (!empty($paper['abstract'])): ?>
                    <div class="paper-abstract">
                        <h2><i class="fas fa-align-left"></i> Abstract</h2>
                        <div class="abstract-content">
                            <?= nl2br(htmlspecialchars($paper['abstract'])) ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Full Content -->
                <?php if (!empty($paper['content'])): ?>
                    <div class="paper-content">
                        <h2><i class="fas fa-book-open"></i> Full Research</h2>
                        <div class="content-body">
                            <?= nl2br(htmlspecialchars($paper['content'])) ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Tags -->
                <?php if (!empty($paper['tags'])): ?>
                    <div class="paper-tags">
                        <h3><i class="fas fa-tags"></i> Tags:</h3>
                        <div class="tags-list">
                            <?php 
                            $tags = explode(',', $paper['tags']);
                            foreach ($tags as $tag): 
                                $tag = trim($tag);
                                if (!empty($tag)):
                            ?>
                                <a href="research.php?tag=<?= urlencode($tag) ?>" class="tag">
                                    #<?= htmlspecialchars($tag) ?>
                                </a>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- PDF Download Section -->
                <?php if (!empty($paper['file_url'])): ?>
                    <div class="paper-download-section">
                        <div class="download-card">
                            <div class="download-icon">
                                <i class="fas fa-file-pdf"></i>
                            </div>
                            <div class="download-info">
                                <h3>Download Research Paper</h3>
                                <p>Get the full research paper as a PDF</p>
                            </div>
                            <a href="<?= $paper['file_url'] ?>" target="_blank" class="download-btn">
                                <i class="fas fa-download"></i> Download PDF
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Citation Information -->
                <div class="paper-citation">
                    <h3><i class="fas fa-quote-right"></i> How to Cite</h3>
                    <div class="citation-box">
                        <p><?= htmlspecialchars($paper['author'] ?? 'MalamIromba') ?>. (<?= date('Y', strtotime($paper['published_at'])) ?>). 
                           <?= htmlspecialchars($paper['title']) ?>. <em>TechInHausa Research</em>.</p>
                        <button class="copy-citation" onclick="copyCitation()">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                    </div>
                </div>
                
                <!-- Share Buttons -->
                <div class="paper-share">
                    <h3>Share this research:</h3>
                    <div class="share-buttons">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(SITE_URL . '/paper.php?id=' . $paper['id']) ?>" 
                           target="_blank" class="share-btn facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?= urlencode(SITE_URL . '/paper.php?id=' . $paper['id']) ?>&text=<?= urlencode($paper['title']) ?>" 
                           target="_blank" class="share-btn twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?= urlencode(SITE_URL . '/paper.php?id=' . $paper['id']) ?>&title=<?= urlencode($paper['title']) ?>" 
                           target="_blank" class="share-btn linkedin">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="https://wa.me/?text=<?= urlencode($paper['title'] . ' ' . SITE_URL . '/paper.php?id=' . $paper['id']) ?>" 
                           target="_blank" class="share-btn whatsapp">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                        <a href="https://t.me/share/url?url=<?= urlencode(SITE_URL . '/paper.php?id=' . $paper['id']) ?>&text=<?= urlencode($paper['title']) ?>" 
                           target="_blank" class="share-btn telegram">
                            <i class="fab fa-telegram-plane"></i>
                        </a>
                        <a href="mailto:?subject=<?= urlencode($paper['title']) ?>&body=<?= urlencode($paper['title'] . ' - ' . SITE_URL . '/paper.php?id=' . $paper['id']) ?>" 
                           class="share-btn email">
                            <i class="fas fa-envelope"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Paper Navigation -->
                <div class="paper-navigation">
                    <?php if ($prev_paper): ?>
                        <a href="paper.php?id=<?= $prev_paper['id'] ?>&slug=<?= $prev_paper['slug'] ?>" class="paper-nav prev">
                            <i class="fas fa-arrow-left"></i>
                            <div class="paper-nav-content">
                                <span>Previous Research</span>
                                <h4><?= htmlspecialchars($prev_paper['title']) ?></h4>
                            </div>
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($next_paper): ?>
                        <a href="paper.php?id=<?= $next_paper['id'] ?>&slug=<?= $next_paper['slug'] ?>" class="paper-nav next">
                            <div class="paper-nav-content">
                                <span>Next Research</span>
                                <h4><?= htmlspecialchars($next_paper['title']) ?></h4>
                            </div>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Sidebar -->
            <aside class="research-single-sidebar">
                <!-- Paper Info Widget -->
                <div class="sidebar-widget paper-info-widget">
                    <h3 class="widget-title">Paper Information</h3>
                    <ul class="paper-info-list">
                        <li>
                            <strong><i class="fas fa-user"></i> Author:</strong>
                            <span><?= htmlspecialchars($paper['author'] ?? 'MalamIromba') ?></span>
                        </li>
                        <li>
                            <strong><i class="far fa-calendar-alt"></i> Published:</strong>
                            <span><?= formatDateHausa($paper['published_at']) ?></span>
                        </li>
                        <?php if (!empty($paper['category_name'])): ?>
                        <li>
                            <strong><i class="fas fa-folder"></i> Category:</strong>
                            <span><?= htmlspecialchars($paper['category_name']) ?></span>
                        </li>
                        <?php endif; ?>
                        <li>
                            <strong><i class="fas fa-eye"></i> Views:</strong>
                            <span><?= number_format($paper['views'] + 1) ?></span>
                        </li>
                        <?php if (!empty($paper['file_url'])): ?>
                        <li>
                            <strong><i class="fas fa-file-pdf"></i> PDF:</strong>
                            <span><a href="<?= $paper['file_url'] ?>" target="_blank">Download</a></span>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <!-- Related Papers -->
                <?php if ($related_result && mysqli_num_rows($related_result) > 0): ?>
                    <div class="sidebar-widget related-widget">
                        <h3 class="widget-title">Related Research</h3>
                        <div class="related-papers-list">
                            <?php while ($related = mysqli_fetch_assoc($related_result)): ?>
                                <div class="related-paper-item">
                                    <a href="paper.php?id=<?= $related['id'] ?>&slug=<?= $related['slug'] ?>" class="related-paper-link">
                                        <?php if (!empty($related['featured_image'])): ?>
                                            <div class="related-paper-image">
                                                <img src="<?= getImageUrl($related['featured_image'], 'research') ?>" 
                                                     alt="<?= htmlspecialchars($related['title']) ?>">
                                            </div>
                                        <?php endif; ?>
                                        <div class="related-paper-content">
                                            <h4><?= htmlspecialchars($related['title']) ?></h4>
                                            <span class="related-paper-author">
                                                <i class="fas fa-user"></i> <?= htmlspecialchars($related['author'] ?? 'MalamIromba') ?>
                                            </span>
                                            <span class="related-paper-date">
                                                <i class="far fa-calendar-alt"></i> <?= date('Y', strtotime($related['published_at'])) ?>
                                            </span>
                                        </div>
                                    </a>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Categories Widget -->
                <?php
                $cats_query = mysqli_query($conn, "
                    SELECT c.name, c.slug, c.icon, COUNT(r.id) as count
                    FROM categories c
                    LEFT JOIN research r ON c.id = r.category_id AND r.is_published = 1
                    WHERE c.type = 'research'
                    GROUP BY c.id
                    ORDER BY count DESC
                    LIMIT 5
                ");
                ?>
                <?php if ($cats_query && mysqli_num_rows($cats_query) > 0): ?>
                    <div class="sidebar-widget">
                        <h3 class="widget-title">Popular Categories</h3>
                        <ul class="category-list">
                            <?php while ($cat = mysqli_fetch_assoc($cats_query)): ?>
                                <li>
                                    <a href="research.php?category=<?= $cat['slug'] ?>">
                                        <?php if (!empty($cat['icon'])): ?>
                                            <i class="fas <?= $cat['icon'] ?>"></i>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($cat['name']) ?>
                                        <span class="count">(<?= $cat['count'] ?>)</span>
                                    </a>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <!-- Newsletter Widget -->
                <div class="sidebar-widget newsletter-widget">
                    <h3 class="widget-title">Join Our Newsletter</h3>
                    <p>Get new research papers directly to your inbox</p>
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
/* Research Single Styles */
.research-single {
    padding: 60px 0;
    background: #f5f7fb;
}

/* Back Link */
.back-to-research {
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

/* Research Single Wrapper */
.research-single-wrapper {
    display: grid;
    grid-template-columns: 2.5fr 1fr;
    gap: 40px;
}

/* Main Content */
.research-single-main {
    background: white;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    border: 1px solid rgba(211, 201, 254, 0.2);
}

/* Paper Header */
.paper-header {
    margin-bottom: 40px;
}

.paper-meta-top {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 20px;
    font-size: 0.9rem;
}

.paper-category {
    background: #D3C9FE;
    color: #031837;
    padding: 5px 15px;
    border-radius: 20px;
    font-weight: 600;
    text-decoration: none;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.paper-category:hover {
    background: #b8a9fe;
}

.paper-date,
.paper-author,
.paper-views,
.paper-featured {
    color: #666;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.paper-date i,
.paper-author i,
.paper-views i,
.paper-featured i {
    color: #D3C9FE;
}

.paper-featured {
    background: rgba(211, 201, 254, 0.2);
    padding: 3px 10px;
    border-radius: 20px;
}

.paper-title {
    font-size: 2.5rem;
    color: #031837;
    margin-bottom: 20px;
    line-height: 1.3;
}

.paper-excerpt {
    background: #f8f9ff;
    padding: 25px;
    border-radius: 12px;
    border-left: 4px solid #D3C9FE;
    font-size: 1.1rem;
    color: #555;
    font-style: italic;
}

/* Featured Image */
.paper-featured-image {
    margin-bottom: 40px;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.paper-featured-image img {
    width: 100%;
    height: auto;
    display: block;
}

/* Abstract */
.paper-abstract {
    margin-bottom: 40px;
}

.paper-abstract h2 {
    color: #031837;
    font-size: 1.5rem;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.paper-abstract h2 i {
    color: #D3C9FE;
}

.abstract-content {
    background: #f8f9ff;
    padding: 30px;
    border-radius: 12px;
    line-height: 1.8;
    color: #333;
}

/* Full Content */
.paper-content {
    margin-bottom: 40px;
}

.paper-content h2 {
    color: #031837;
    font-size: 1.5rem;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.paper-content h2 i {
    color: #D3C9FE;
}

.content-body {
    line-height: 1.9;
    color: #333;
}

.content-body p {
    margin-bottom: 20px;
}

.content-body h3,
.content-body h4 {
    color: #031837;
    margin: 30px 0 15px;
}

.content-body ul,
.content-body ol {
    margin: 20px 0;
    padding-left: 30px;
}

.content-body li {
    margin-bottom: 10px;
}

.content-body blockquote {
    background: #f8f9ff;
    border-left: 4px solid #D3C9FE;
    padding: 20px 30px;
    font-style: italic;
    margin: 30px 0;
    border-radius: 0 10px 10px 0;
}

/* Tags */
.paper-tags {
    margin-bottom: 40px;
    padding: 20px 0;
    border-top: 1px solid #eee;
    border-bottom: 1px solid #eee;
}

.paper-tags h3 {
    color: #031837;
    font-size: 1rem;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.paper-tags h3 i {
    color: #D3C9FE;
}

/* Download Section */
.paper-download-section {
    margin-bottom: 40px;
}

.download-card {
    background: linear-gradient(135deg, #f8f9ff, #ffffff);
    border: 2px solid #D3C9FE;
    border-radius: 15px;
    padding: 30px;
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.download-icon {
    width: 70px;
    height: 70px;
    background: #dc3545;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.download-icon i {
    font-size: 2rem;
    color: white;
}

.download-info {
    flex: 1;
}

.download-info h3 {
    color: #031837;
    margin-bottom: 5px;
}

.download-info p {
    color: #666;
}

.download-btn {
    padding: 15px 30px;
    background: #dc3545;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    transition: all 0.3s;
}

.download-btn:hover {
    background: #c82333;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
}

/* Citation */
.paper-citation {
    margin-bottom: 40px;
}

.paper-citation h3 {
    color: #031837;
    font-size: 1.1rem;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.paper-citation h3 i {
    color: #D3C9FE;
}

.citation-box {
    background: #f0f4ff;
    padding: 20px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
    border: 1px dashed #D3C9FE;
}

.citation-box p {
    flex: 1;
    font-family: monospace;
    color: #333;
    margin: 0;
}

.copy-citation {
    padding: 8px 15px;
    background: #031837;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: all 0.3s;
}

.copy-citation:hover {
    background: #D3C9FE;
    color: #031837;
}

/* Share Section */
.paper-share {
    margin-bottom: 40px;
}

.paper-share h3 {
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
.share-btn.linkedin { background: #0077b5; }
.share-btn.whatsapp { background: #25d366; }
.share-btn.telegram { background: #0088cc; }
.share-btn.email { background: #666; }

/* Paper Navigation */
.paper-navigation {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    margin-top: 40px;
    padding-top: 30px;
    border-top: 2px solid #f0f0f0;
}

.paper-nav {
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

.paper-nav:hover {
    background: #D3C9FE;
    transform: translateY(-2px);
}

.paper-nav.prev {
    text-align: left;
}

.paper-nav.next {
    text-align: right;
    justify-content: flex-end;
}

.paper-nav i {
    color: #031837;
    font-size: 1.2rem;
}

.paper-nav-content span {
    color: #666;
    font-size: 0.8rem;
    display: block;
    margin-bottom: 5px;
}

.paper-nav-content h4 {
    color: #031837;
    font-size: 0.95rem;
    line-height: 1.4;
}

.paper-nav:hover .paper-nav-content h4 {
    color: #031837;
}

/* Sidebar */
.research-single-sidebar {
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

/* Paper Info Widget */
.paper-info-list {
    list-style: none;
}

.paper-info-list li {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
}

.paper-info-list li:last-child {
    border-bottom: none;
}

.paper-info-list strong {
    color: #031837;
    display: flex;
    align-items: center;
    gap: 5px;
}

.paper-info-list strong i {
    color: #D3C9FE;
}

.paper-info-list span {
    color: #666;
}

.paper-info-list a {
    color: #031837;
    text-decoration: none;
    font-weight: 600;
}

.paper-info-list a:hover {
    color: #D3C9FE;
}

/* Related Papers List */
.related-papers-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.related-paper-link {
    display: flex;
    gap: 12px;
    text-decoration: none;
    transition: all 0.3s;
}

.related-paper-link:hover {
    transform: translateX(5px);
}

.related-paper-image {
    width: 70px;
    height: 50px;
    border-radius: 6px;
    overflow: hidden;
    flex-shrink: 0;
}

.related-paper-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.related-paper-content {
    flex: 1;
}

.related-paper-content h4 {
    color: #031837;
    font-size: 0.9rem;
    margin-bottom: 5px;
    line-height: 1.4;
}

.related-paper-author,
.related-paper-date {
    color: #666;
    font-size: 0.75rem;
    display: inline-flex;
    align-items: center;
    gap: 3px;
    margin-right: 10px;
}

.related-paper-author i,
.related-paper-date i {
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
    .research-single-wrapper {
        grid-template-columns: 1fr;
    }
    
    .research-single-sidebar {
        position: static;
    }
    
    .paper-title {
        font-size: 2rem;
    }
}

@media (max-width: 768px) {
    .research-single-main {
        padding: 25px;
    }
    
    .paper-title {
        font-size: 1.8rem;
    }
    
    .paper-meta-top {
        flex-direction: column;
        gap: 10px;
    }
    
    .download-card {
        flex-direction: column;
        text-align: center;
    }
    
    .citation-box {
        flex-direction: column;
        text-align: center;
    }
    
    .paper-navigation {
        flex-direction: column;
    }
    
    .paper-nav {
        width: 100%;
    }
}

@media (max-width: 480px) {
    .paper-title {
        font-size: 1.5rem;
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
// Copy citation function
function copyCitation() {
    const citationText = document.querySelector('.citation-box p').innerText;
    navigator.clipboard.writeText(citationText).then(function() {
        alert('Citation copied!');
    }, function() {
        alert('An error occurred. Please try again.');
    });
}

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
                    body: JSON.stringify({email, source: 'research-single'})
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