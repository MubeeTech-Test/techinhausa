<?php
// index.php - TechInHausa Homepage
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Verify database connection
if (!$conn) {
    die("Database connection failed. Please check your configuration.");
}

include __DIR__ . "/partials/header.php";

// ============================================
// URL MAPPING (as specified)
// ============================================
$pageMap = [
    'video' => 'watch.php',
    'blog' => 'post.php',
    'news' => 'article.php',
    'research' => 'paper.php',
    'creator' => 'item.php'
];

// ============================================
// HERO SLIDER - Only is_featured content
// ============================================
$featuredContent = [];

// Featured Videos
$videosQuery = mysqli_query($conn,
    "SELECT id, title, slug, 'video' as content_type, excerpt, featured_image, author, published_at, views
     FROM videos
     WHERE is_featured = 1 AND is_published = 1
     ORDER BY published_at DESC
     LIMIT 3"
);
if ($videosQuery && mysqli_num_rows($videosQuery) > 0) {
    while ($row = mysqli_fetch_assoc($videosQuery)) {
        $featuredContent[] = $row;
    }
}

// Featured Blog Posts
$blogsQuery = mysqli_query($conn,
    "SELECT id, title, slug, 'blog' as content_type, excerpt, featured_image, author, published_at, views
     FROM blog_posts
     WHERE is_featured = 1 AND is_published = 1
     ORDER BY published_at DESC
     LIMIT 3"
);
if ($blogsQuery && mysqli_num_rows($blogsQuery) > 0) {
    while ($row = mysqli_fetch_assoc($blogsQuery)) {
        $featuredContent[] = $row;
    }
}

// Featured News
$newsQuery = mysqli_query($conn,
    "SELECT id, title, slug, 'news' as content_type, excerpt, featured_image, author, published_at, views
     FROM news
     WHERE is_featured = 1 AND is_published = 1
     ORDER BY published_at DESC
     LIMIT 3"
);
if ($newsQuery && mysqli_num_rows($newsQuery) > 0) {
    while ($row = mysqli_fetch_assoc($newsQuery)) {
        $featuredContent[] = $row;
    }
}

// Featured Research
$researchQuery = mysqli_query($conn,
    "SELECT id, title, slug, 'research' as content_type, excerpt, featured_image, author, published_at, views
     FROM research
     WHERE is_featured = 1 AND is_published = 1
     ORDER BY published_at DESC
     LIMIT 3"
);
if ($researchQuery && mysqli_num_rows($researchQuery) > 0) {
    while ($row = mysqli_fetch_assoc($researchQuery)) {
        $featuredContent[] = $row;
    }
}

// Sort by published date (newest first)
if (!empty($featuredContent)) {
    usort($featuredContent, function($a, $b) {
        return strtotime($b['published_at']) - strtotime($a['published_at']);
    });
    $featuredContent = array_slice($featuredContent, 0, 5);
}

// ============================================
// VIDEOS SECTION - Latest & Popular
// ============================================
$latestVideos = mysqli_query($conn,
    "SELECT * FROM videos WHERE is_published = 1 ORDER BY published_at DESC LIMIT 4"
);

$popularVideos = mysqli_query($conn,
    "SELECT * FROM videos WHERE is_published = 1 ORDER BY views DESC LIMIT 4"
);

// ============================================
// FOUNDER SECTION - From founders table
// ============================================
$founderQuery = mysqli_query($conn,
    "SELECT * FROM founders
     WHERE is_active = 1
     ORDER BY display_order ASC
     LIMIT 1"
);

$founder = null;
if ($founderQuery && mysqli_num_rows($founderQuery) > 0) {
    $founderData = mysqli_fetch_assoc($founderQuery);
    $founder = [
        'name' => $founderData['name'],
        'title' => $founderData['title'] ?? null,
        'bio' => $founderData['bio'] ?? null,
        'image' => $founderData['image'] ?? null,
        'years_active' => $founderData['years_active'] ?? null,
        'projects' => $founderData['projects_count'] ?? null,
        'students' => $founderData['students_count'] ?? null,
        'email' => $founderData['email'] ?? null,
        'twitter' => $founderData['twitter'] ?? null,
        'linkedin' => $founderData['linkedin'] ?? null,
        'github' => $founderData['github'] ?? null
    ];
}

// ============================================
// MALAMIROMBA CONTENT - From creator table
// ============================================
$creatorContent = [];

$creatorQuery = "
    SELECT id, title, slug, content_type, excerpt, featured_image,
           video_url, video_duration, file_url, author, views,
           published_at, created_at
    FROM creator
    WHERE (author LIKE '%Ibrahim Zubairu%' OR author = 'MalamIromba')
      AND is_published = 1
    ORDER BY 
        CASE 
            WHEN is_featured = 1 THEN 0 
            ELSE 1 
        END,
        published_at DESC 
    LIMIT 6
";

$creatorResult = mysqli_query($conn, $creatorQuery);

if ($creatorResult && mysqli_num_rows($creatorResult) > 0) {
    while ($row = mysqli_fetch_assoc($creatorResult)) {
        $creatorContent[] = $row;
    }
}

// If no creator content, try videos and blog_posts by author
if (empty($creatorContent)) {
    $videosByAuthor = mysqli_query($conn,
        "SELECT id, title, slug, 'video' as content_type, excerpt, featured_image, published_at, views 
         FROM videos 
         WHERE author LIKE '%Ibrahim Zubairu%' AND is_published = 1 
         ORDER BY published_at DESC 
         LIMIT 3"
    );
    
    if ($videosByAuthor && mysqli_num_rows($videosByAuthor) > 0) {
        while ($row = mysqli_fetch_assoc($videosByAuthor)) {
            $creatorContent[] = $row;
        }
    }
    
    $blogsByAuthor = mysqli_query($conn,
        "SELECT id, title, slug, 'blog' as content_type, excerpt, featured_image, published_at, views 
         FROM blog_posts 
         WHERE author LIKE '%Ibrahim Zubairu%' AND is_published = 1 
         ORDER BY published_at DESC 
         LIMIT " . (6 - count($creatorContent))
    );
    
    if ($blogsByAuthor && mysqli_num_rows($blogsByAuthor) > 0) {
        while ($row = mysqli_fetch_assoc($blogsByAuthor)) {
            $creatorContent[] = $row;
        }
    }
}

// ============================================
// RESEARCH SECTION
// ============================================
$researchItems = mysqli_query($conn,
    "SELECT * FROM research WHERE is_published = 1 ORDER BY published_at DESC LIMIT 4"
);

// ============================================
// MEDIA FEATURES SECTION
// ============================================
$mediaFeatures = mysqli_query($conn,
    "SELECT * FROM media_features WHERE is_active = 1 ORDER BY is_featured DESC, feature_date DESC LIMIT 6"
);

// ============================================
// BLOG POSTS SECTION
// ============================================
$blogPosts = mysqli_query($conn,
    "SELECT * FROM blog_posts WHERE is_published = 1 ORDER BY published_at DESC LIMIT 4"
);

// ============================================
// SPONSORS SECTION
// ============================================
$sponsors = mysqli_query($conn,
    "SELECT * FROM sponsors WHERE is_active = 1 ORDER BY
     FIELD(sponsor_level, 'platinum', 'gold', 'silver', 'bronze'), display_order ASC LIMIT 8"
);
?>

<!-- HERO SLIDER SECTION - Only shows if there's featured content -->
<?php if (!empty($featuredContent)): ?>
<section class="hero-slider-section">
    <div class="hero-slider-container">
        <div class="hero-slider" id="heroSlider">
            <?php foreach ($featuredContent as $index => $slide): ?>
            <div class="hero-slide <?php echo $index === 0 ? 'active' : ''; ?>">
                <?php
                $targetPage = $pageMap[$slide['content_type']] ?? '#';
                ?>
                <a href="<?php echo SITE_URL; ?>/<?php echo $targetPage; ?>?slug=<?php echo $slide['slug']; ?>" class="hero-slide-link">
                    <div class="hero-image">
                        <img src="<?php echo getImageUrl($slide['featured_image'] ?? '', $slide['content_type']); ?>"
                             alt="<?php echo $slide['title']; ?>">
                        <div class="hero-overlay"></div>
                    </div>
                   
                    <div class="hero-content container">
                        <span class="hero-category">
                            <?php
                            $labels = [
                                'video' => 'VIDEO',
                                'blog' => 'BLOG',
                                'news' => 'NEWS',
                                'research' => 'RESEARCH'
                            ];
                            echo $labels[$slide['content_type']] ?? strtoupper($slide['content_type']);
                            ?>
                        </span>
                       
                        <h1 class="hero-title"><?php echo $slide['title']; ?></h1>
                       
                        <?php if (!empty($slide['excerpt'])): ?>
                        <p class="hero-excerpt"><?php echo truncateText($slide['excerpt'], 150); ?></p>
                        <?php endif; ?>
                       
                        <div class="hero-meta">
                            <span><i class="fas fa-user"></i> <?php echo $slide['author'] ?? 'MalamIromba'; ?></span>
                            <span><i class="fas fa-calendar-alt"></i> <?php echo formatDate($slide['published_at'] ?? ''); ?></span>
                        </div>
                       
                        <span class="hero-cta">Read Full <i class="fas fa-arrow-right"></i></span>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
       
        <div class="slider-nav">
            <button class="slider-prev" aria-label="Previous slide"><i class="fas fa-chevron-left"></i></button>
            <div class="slider-dots">
                <?php foreach ($featuredContent as $index => $slide): ?>
                <button class="dot <?php echo $index === 0 ? 'active' : ''; ?>" data-slide="<?php echo $index; ?>"></button>
                <?php endforeach; ?>
            </div>
            <button class="slider-next" aria-label="Next slide"><i class="fas fa-chevron-right"></i></button>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- VIDEOS SECTION - Latest & Popular -->
<?php if (($latestVideos && mysqli_num_rows($latestVideos) > 0) || ($popularVideos && mysqli_num_rows($popularVideos) > 0)): ?>
<section class="videos-section">
    <div class="container">
        <div class="section-header">
            <div>
                <h2 class="section-title">Featured Videos</h2>
                <p class="section-subtitle">Check out the latest and most popular tutorials</p>
            </div>
            <div class="section-tabs">
                <button class="tab-btn active" data-tab="latest-videos">Latest</button>
                <button class="tab-btn" data-tab="popular-videos">Popular</button>
            </div>
        </div>
       
        <div class="tab-content active" id="latest-videos">
            <div class="videos-grid">
                <?php if ($latestVideos && mysqli_num_rows($latestVideos) > 0): ?>
                    <?php while ($video = mysqli_fetch_assoc($latestVideos)): ?>
                        <div class="video-card">
                            <div class="video-thumbnail">
                                <a href="<?php echo SITE_URL; ?>/watch.php?slug=<?php echo $video['slug']; ?>">
                                    <img src="<?php echo getImageUrl($video['featured_image'] ?? '', 'video'); ?>"
                                         alt="<?php echo $video['title']; ?>">
                                    <?php if (!empty($video['video_duration'])): ?>
                                        <span class="video-duration"><?php echo $video['video_duration']; ?></span>
                                    <?php endif; ?>
                                    <span class="play-icon"><i class="fas fa-play-circle"></i></span>
                                </a>
                            </div>
                            <div class="video-info">
                                <h3><a href="<?php echo SITE_URL; ?>/watch.php?slug=<?php echo $video['slug']; ?>"><?php echo $video['title']; ?></a></h3>
                                <p><?php echo truncateText($video['excerpt'] ?? '', 80); ?></p>
                                <div class="video-meta">
                                    <span><i class="fas fa-eye"></i> <?php echo number_format($video['views'] ?? 0); ?></span>
                                    <span><i class="fas fa-user"></i> <?php echo $video['author'] ?? 'MalamIromba'; ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
       
        <div class="tab-content" id="popular-videos">
            <div class="videos-grid">
                <?php if ($popularVideos && mysqli_num_rows($popularVideos) > 0): ?>
                    <?php while ($video = mysqli_fetch_assoc($popularVideos)): ?>
                        <div class="video-card">
                            <div class="video-thumbnail">
                                <a href="<?php echo SITE_URL; ?>/watch.php?slug=<?php echo $video['slug']; ?>">
                                    <img src="<?php echo getImageUrl($video['featured_image'] ?? '', 'video'); ?>"
                                         alt="<?php echo $video['title']; ?>">
                                    <?php if (!empty($video['video_duration'])): ?>
                                        <span class="video-duration"><?php echo $video['video_duration']; ?></span>
                                    <?php endif; ?>
                                    <span class="play-icon"><i class="fas fa-play-circle"></i></span>
                                </a>
                            </div>
                            <div class="video-info">
                                <h3><a href="<?php echo SITE_URL; ?>/watch.php?slug=<?php echo $video['slug']; ?>"><?php echo $video['title']; ?></a></h3>
                                <p><?php echo truncateText($video['excerpt'] ?? '', 80); ?></p>
                                <div class="video-meta">
                                    <span><i class="fas fa-eye"></i> <?php echo number_format($video['views'] ?? 0); ?></span>
                                    <span><i class="fas fa-user"></i> <?php echo $video['author'] ?? 'MalamIromba'; ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
       
        <div class="section-footer">
            <a href="<?php echo SITE_URL; ?>/videos.php" class="view-all-btn">
                View All Videos <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- TECH IN HAUSA | HAUSA AI | MALAMIROMBA SECTION -->
<?php if (!empty($creatorContent)): ?>
<section class="featured-section malamIromba-section" style="background-color: #f8f9ff;">
    <div class="container">
        <div class="section-header center">
            <h2 class="section-title">Tech In Hausa | Hausa AI | MalamIromba</h2>
            <p class="section-subtitle">Personal Education Initiative by Ibrahim Zubairu</p>
        </div>
       
        <div class="content-grid">
            <?php foreach ($creatorContent as $item): ?>
                <?php
                $targetPage = $pageMap[$item['content_type']] ?? 'item.php';
                ?>
                <div class="content-card">
                    <div class="card-image">
                        <a href="<?php echo SITE_URL; ?>/<?php echo $targetPage; ?>?slug=<?php echo $item['slug']; ?>">
                            <img src="<?php echo getImageUrl($item['featured_image'] ?? '', 'creator'); ?>"
                                 alt="<?php echo $item['title']; ?>">
                            <?php if ($item['content_type'] === 'video'): ?>
                                <span class="play-icon-small"><i class="fas fa-play"></i></span>
                            <?php elseif (in_array($item['content_type'], ['publication', 'research']) && !empty($item['file_url'])): ?>
                                <span class="pdf-icon-small"><i class="fas fa-file-pdf"></i></span>
                            <?php endif; ?>
                           
                            <?php if (!empty($item['video_duration'])): ?>
                                <span class="video-duration"><?php echo $item['video_duration']; ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                    <div class="card-content">
                        <span class="content-badge <?php echo $item['content_type']; ?>">
                            <?php
                            $typeLabels = [
                                'video' => 'Video',
                                'blog' => 'Blog',
                                'publication' => 'Publication',
                                'research' => 'Research',
                                'tutorial' => 'Tutorial'
                            ];
                            echo $typeLabels[$item['content_type']] ?? ucfirst($item['content_type']);
                            ?>
                        </span>
                        <h3>
                            <a href="<?php echo SITE_URL; ?>/<?php echo $targetPage; ?>?slug=<?php echo $item['slug']; ?>">
                                <?php echo htmlspecialchars($item['title']); ?>
                            </a>
                        </h3>
                        <p><?php echo truncateText($item['excerpt'] ?? '', 100); ?></p>
                        <div class="card-meta">
                            <span><i class="fas fa-calendar"></i> <?php echo formatDate($item['published_at'] ?? $item['created_at']); ?></span>
                            <span><i class="fas fa-eye"></i> <?php echo number_format($item['views'] ?? 0); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
       
        <div class="section-footer">
            <a href="<?php echo SITE_URL; ?>/creator.php" class="view-all-btn">
                View All MalamIromba Content <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ABOUT US SECTION -->
<section class="about-section">
    <div class="container">
        <div class="about-grid">
            <div class="about-image">
                <img src="<?php echo SITE_URL; ?>/assets/images/techinhausa-about.jpg"
                     alt="About TechInHausa">
            </div>
            <div class="about-content">
                <h2 class="section-title">About Us</h2>
                <p class="about-text">
                    TechInHausa is the source of technology and AI education in the Hausa language.
                    We bring you the latest lessons, videos, and news to help you understand the
                    world of technology in your native language.
                </p>
                <p class="about-text">
                    We were founded with the aim of bringing technological knowledge to everyone,
                    regardless of language. We believe that anyone can learn technology if it is
                    explained in the language they understand.
                </p>
                <a href="<?php echo SITE_URL; ?>/about.php" class="read-more-btn">
                    Read Our Full Story <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- FOUNDER'S SECTION - Only shows if founder data exists -->
<?php if ($founder && $founder['name']): ?>
<section class="founder-section" style="background-color: #f8f9ff;">
    <div class="container">
        <div class="founder-grid">
            <?php if (!empty($founder['image'])): ?>
            <div class="founder-image">
                <img src="<?php echo getImageUrl($founder['image'], 'founder'); ?>"
                     alt="<?php echo $founder['name']; ?>">
            </div>
            <?php endif; ?>
            <div class="founder-content">
                <span class="founder-label">Founder</span>
                <h2 class="section-title"><?php echo $founder['name']; ?></h2>
                
                <?php if (!empty($founder['title'])): ?>
                <h3 class="founder-title"><?php echo $founder['title']; ?></h3>
                <?php endif; ?>
                
                <?php if (!empty($founder['bio'])): ?>
                <p class="founder-bio"><?php echo $founder['bio']; ?></p>
                <?php endif; ?>
               
                <?php if (!empty($founder['years_active']) || !empty($founder['projects']) || !empty($founder['students'])): ?>
                <div class="founder-stats">
                    <?php if (!empty($founder['years_active'])): ?>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $founder['years_active']; ?></span>
                        <span class="stat-label">Years Active</span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($founder['projects'])): ?>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $founder['projects']; ?>+</span>
                        <span class="stat-label">Videos</span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($founder['students'])): ?>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $founder['students']; ?></span>
                        <span class="stat-label">Audience</span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
               
                <a href="<?php echo SITE_URL; ?>/creator.php" class="read-more-btn">
                    Read Full Story <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ACADEMIC PUBLICATIONS & RESEARCH SECTION -->
<?php if ($researchItems && mysqli_num_rows($researchItems) > 0): ?>
<section class="research-section">
    <div class="container">
        <div class="section-header">
            <div>
                <h2 class="section-title">Research & Publications</h2>
                <p class="section-subtitle">Latest research and academic publications</p>
            </div>
        </div>
       
        <div class="research-grid">
            <?php while ($item = mysqli_fetch_assoc($researchItems)): ?>
                <div class="research-card">
                    <div class="research-image">
                        <img src="<?php echo getImageUrl($item['featured_image'] ?? '', 'research'); ?>"
                             alt="<?php echo $item['title']; ?>">
                        <?php if (!empty($item['file_url'])): ?>
                            <span class="pdf-badge">PDF</span>
                        <?php endif; ?>
                    </div>
                    <div class="research-content">
                        <h3><a href="<?php echo SITE_URL; ?>/paper.php?slug=<?php echo $item['slug']; ?>"><?php echo $item['title']; ?></a></h3>
                        <p><?php echo truncateText($item['excerpt'] ?? $item['abstract'] ?? '', 100); ?></p>
                        <div class="research-meta">
                            <span><i class="fas fa-user"></i> <?php echo $item['author'] ?? 'MalamIromba'; ?></span>
                            <span><i class="fas fa-calendar"></i> <?php echo date('Y', strtotime($item['published_at'] ?? 'now')); ?></span>
                        </div>
                        <a href="<?php echo SITE_URL; ?>/paper.php?slug=<?php echo $item['slug']; ?>" class="read-more-link">
                            Read Full <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
       
        <div class="section-footer">
            <a href="<?php echo SITE_URL; ?>/research.php" class="view-all-btn">
                View All Research <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- MEDIA FEATURES & RECOGNITION SECTION -->
<?php if ($mediaFeatures && mysqli_num_rows($mediaFeatures) > 0): ?>
<section class="media-section" style="background-color: #f8f9ff;">
    <div class="container">
        <div class="section-header center">
            <h2 class="section-title">Media Features & Recognition</h2>
            <p class="section-subtitle">The initiative has been featured in prominent media outlets...</p>
        </div>
       
        <div class="media-grid">
            <?php while ($media = mysqli_fetch_assoc($mediaFeatures)): ?>
                <a href="<?php echo $media['article_url'] ?? '#'; ?>" target="_blank" class="media-card">
                    <div class="media-logo">
                        <?php if (!empty($media['outlet_logo'])): ?>
                            <img src="<?php echo getImageUrl($media['outlet_logo'], 'media'); ?>"
                                 alt="<?php echo $media['outlet_name']; ?>">
                        <?php else: ?>
                            <i class="fas fa-newspaper"></i>
                        <?php endif; ?>
                    </div>
                    <div class="media-content">
                        <h4><?php echo $media['title']; ?></h4>
                        <p class="media-outlet"><?php echo $media['outlet_name']; ?></p>
                        <?php if (!empty($media['feature_date'])): ?>
                            <span class="media-date"><?php echo date('M Y', strtotime($media['feature_date'])); ?></span>
                        <?php endif; ?>
                    </div>
                    <i class="fas fa-external-link-alt media-icon"></i>
                </a>
            <?php endwhile; ?>
        </div>
       
        <div class="section-footer">
            <a href="<?php echo SITE_URL; ?>/media.php" class="view-all-btn">
                View All Media Features <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- BLOG POSTS SECTION -->
<?php if ($blogPosts && mysqli_num_rows($blogPosts) > 0): ?>
<section class="blog-section">
    <div class="container">
        <div class="section-header">
            <div>
                <h2 class="section-title">Blog Posts</h2>
                <p class="section-subtitle">Latest articles and tutorials on tech and AI</p>
            </div>
        </div>
       
        <div class="blog-grid">
            <?php while ($blog = mysqli_fetch_assoc($blogPosts)): ?>
                <div class="blog-card">
                    <div class="blog-image">
                        <a href="<?php echo SITE_URL; ?>/post.php?slug=<?php echo $blog['slug']; ?>">
                            <img src="<?php echo getImageUrl($blog['featured_image'] ?? '', 'blog'); ?>"
                                 alt="<?php echo $blog['title']; ?>">
                        </a>
                    </div>
                    <div class="blog-content">
                        <h3><a href="<?php echo SITE_URL; ?>/post.php?slug=<?php echo $blog['slug']; ?>"><?php echo $blog['title']; ?></a></h3>
                        <p><?php echo truncateText($blog['excerpt'] ?? $blog['content'] ?? '', 120); ?></p>
                        <div class="blog-meta">
                            <span><i class="fas fa-user"></i> <?php echo $blog['author'] ?? 'MalamIromba'; ?></span>
                            <span><i class="fas fa-calendar"></i> <?php echo formatDate($blog['published_at'] ?? ''); ?></span>
                        </div>
                        <a href="<?php echo SITE_URL; ?>/post.php?slug=<?php echo $blog['slug']; ?>" class="read-more-link">
                            Read Full <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
       
        <div class="section-footer">
            <a href="<?php echo SITE_URL; ?>/blog.php" class="view-all-btn">
                View All Blog Posts <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- NEWSLETTER SECTION -->
<section class="newsletter-section" style="background: linear-gradient(135deg, #031837 0%, #0a2a4a 100%);">
    <div class="container">
        <div class="newsletter-content">
            <h2 class="newsletter-title">Subscribe to Our Newsletter</h2>
            <p class="newsletter-text">Receive the latest news, videos, and tutorials directly to your email.</p>
           
            <form id="newsletterForm" class="newsletter-form">
                <div class="form-group">
                    <input type="email" name="email" placeholder="Your email" required>
                    <button type="submit" class="newsletter-btn">
                        <span class="btn-text">Subscribe</span>
                        <span class="btn-loading" style="display: none;"><i class="fas fa-spinner fa-spin"></i></span>
                    </button>
                </div>
               
                <div class="form-check">
                    <input type="checkbox" id="consent" required>
                    <label for="consent">I agree to receive emails. I can unsubscribe at any time.</label>
                </div>
               
                <div id="newsletter-message" class="newsletter-message"></div>
            </form>
           
            <div class="newsletter-features">
                <span><i class="fas fa-check-circle"></i> New Videos</span>
                <span><i class="fas fa-check-circle"></i> Blog Posts</span>
                <span><i class="fas fa-check-circle"></i> Exclusive Research</span>
                <span><i class="fas fa-check-circle"></i> No Spam</span>
            </div>
        </div>
    </div>
</section>

<!-- SPONSORS SECTION -->
<?php if ($sponsors && mysqli_num_rows($sponsors) > 0): ?>
<section class="sponsors-section">
    <div class="container">
        <div class="section-header center">
            <h2 class="section-title">Our Partners</h2>
            <p class="section-subtitle">Companies that support our mission</p>
        </div>
       
        <div class="sponsors-grid">
            <?php while ($sponsor = mysqli_fetch_assoc($sponsors)): ?>
                <a href="<?php echo $sponsor['website_url'] ?? '#'; ?>" target="_blank" class="sponsor-card">
                    <img src="<?php echo getImageUrl($sponsor['logo_url'] ?? '', 'sponsor'); ?>"
                         alt="<?php echo $sponsor['name']; ?>">
                    <?php if (!empty($sponsor['sponsor_level'])): ?>
                        <span class="sponsor-level level-<?php echo $sponsor['sponsor_level']; ?>">
                            <?php echo ucfirst($sponsor['sponsor_level']); ?>
                        </span>
                    <?php endif; ?>
                </a>
            <?php endwhile; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<style>
/* Hero Slider Styles - Keeping your existing CSS */
.hero-slider-section {
    position: relative;
    height: 600px;
    overflow: hidden;
}

.hero-slider {
    position: relative;
    height: 100%;
}

.hero-slide {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.5s ease;
}

.hero-slide.active {
    opacity: 1;
    visibility: visible;
}

.hero-image {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

.hero-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(to right, rgba(3,24,55,0.95), rgba(1,1,1,0.8));
}

.hero-content {
    position: relative;
    z-index: 2;
    max-width: 600px;
    margin: 0 auto;
    padding: 0 20px;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    color: white;
}

.hero-category {
    display: inline-block;
    background: #D3C9FE;
    color: #031837;
    padding: 5px 15px;
    border-radius: 30px;
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 20px;
    align-self: flex-start;
}

.hero-title {
    font-size: 3rem;
    margin-bottom: 20px;
    line-height: 1.2;
}

.hero-excerpt {
    font-size: 1.1rem;
    margin-bottom: 20px;
    opacity: 0.9;
}

.hero-meta {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
    font-size: 0.95rem;
}

.hero-meta i {
    margin-right: 5px;
    color: #D3C9FE;
}

.hero-cta {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 30px;
    background: #D3C9FE;
    color: #031837;
    border-radius: 50px;
    font-weight: 600;
    width: fit-content;
    transition: all 0.3s;
}

.hero-cta:hover {
    background: #b8a9fe;
    transform: translateY(-2px);
}

.slider-nav {
    position: absolute;
    bottom: 30px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    align-items: center;
    gap: 20px;
    z-index: 10;
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(10px);
    padding: 10px 20px;
    border-radius: 50px;
}

.slider-prev,
.slider-next {
    background: none;
    border: none;
    color: white;
    font-size: 1.2rem;
    cursor: pointer;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.3s;
}

.slider-prev:hover,
.slider-next:hover {
    background: rgba(255,255,255,0.2);
}

.slider-dots {
    display: flex;
    gap: 10px;
}

.dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: rgba(255,255,255,0.5);
    border: none;
    cursor: pointer;
    transition: all 0.3s;
}

.dot.active {
    background: #D3C9FE;
    transform: scale(1.2);
}

/* Section Styles - Keeping all your existing section styles */
.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 40px;
    flex-wrap: wrap;
    gap: 20px;
}

.section-header.center {
    text-align: center;
    justify-content: center;
    flex-direction: column;
}

.section-title {
    font-size: 2.2rem;
    color: #031837;
    margin-bottom: 10px;
    position: relative;
    padding-bottom: 15px;
}

.section-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 80px;
    height: 4px;
    background: #D3C9FE;
    border-radius: 2px;
}

.center .section-title::after {
    left: 50%;
    transform: translateX(-50%);
}

.section-subtitle {
    color: #666;
    font-size: 1.1rem;
}

.section-tabs {
    display: flex;
    gap: 10px;
    background: white;
    padding: 5px;
    border-radius: 50px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.tab-btn {
    padding: 10px 25px;
    border: none;
    background: none;
    border-radius: 50px;
    font-weight: 600;
    cursor: pointer;
    color: #666;
    transition: all 0.3s;
}

.tab-btn.active {
    background: #D3C9FE;
    color: #031837;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.videos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 30px;
}

.video-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    transition: transform 0.3s;
}

.video-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(211,201,254,0.3);
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
    transition: transform 0.3s;
}

.video-card:hover .video-thumbnail img {
    transform: scale(1.05);
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
}

.play-icon {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 3rem;
    color: white;
    opacity: 0.8;
    transition: opacity 0.3s;
}

.video-card:hover .play-icon {
    opacity: 1;
}

.video-info {
    padding: 20px;
}

.video-info h3 {
    margin-bottom: 10px;
    font-size: 1.1rem;
}

.video-info h3 a {
    color: #031837;
    text-decoration: none;
}

.video-info p {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 15px;
    line-height: 1.5;
}

.video-meta {
    display: flex;
    gap: 15px;
    font-size: 0.85rem;
    color: #888;
}

.video-meta i {
    color: #D3C9FE;
    margin-right: 5px;
}

/* About Section */
.about-grid,
.founder-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: center;
}

.about-image img,
.founder-image img {
    width: 100%;
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
}

.about-text {
    margin-bottom: 20px;
    line-height: 1.8;
    color: #444;
}

/* Founder Section */
.founder-label {
    color: #D3C9FE;
    font-size: 1rem;
    text-transform: uppercase;
    letter-spacing: 2px;
    margin-bottom: 10px;
    display: block;
}

.founder-title {
    color: #666;
    font-size: 1.2rem;
    margin-bottom: 20px;
}

.founder-bio {
    line-height: 1.8;
    color: #444;
    margin-bottom: 30px;
}

.founder-stats {
    display: flex;
    gap: 40px;
    margin-bottom: 30px;
}

.stat-number {
    display: block;
    font-size: 2rem;
    font-weight: 700;
    color: #031837;
}

.stat-label {
    color: #666;
    font-size: 0.9rem;
}

/* Research Grid */
.research-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 30px;
}

.research-card {
    display: flex;
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
}

.research-image {
    position: relative;
    width: 120px;
    min-width: 120px;
    height: 150px;
}

.research-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.pdf-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #dc2626;
    color: white;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 600;
}

.research-content {
    padding: 20px;
}

.research-content h3 {
    font-size: 1rem;
    margin-bottom: 8px;
}

.research-content h3 a {
    color: #031837;
    text-decoration: none;
}

.research-content p {
    color: #666;
    font-size: 0.85rem;
    margin-bottom: 10px;
    line-height: 1.5;
}

.research-meta {
    display: flex;
    gap: 15px;
    font-size: 0.8rem;
    color: #888;
    margin-bottom: 10px;
}

.research-meta i {
    color: #D3C9FE;
    margin-right: 5px;
}

.read-more-link {
    color: #031837;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.read-more-link:hover {
    color: #D3C9FE;
}

/* Media Grid */
.media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.media-card {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: white;
    border-radius: 12px;
    text-decoration: none;
    transition: all 0.3s;
    border: 1px solid #eee;
}

.media-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(211,201,254,0.3);
    border-color: #D3C9FE;
}

.media-logo {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f5f5f5;
    border-radius: 10px;
    overflow: hidden;
}

.media-logo img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.media-logo i {
    font-size: 1.5rem;
    color: #D3C9FE;
}

.media-content {
    flex: 1;
}

.media-content h4 {
    color: #031837;
    font-size: 0.95rem;
    margin-bottom: 3px;
}

.media-outlet {
    color: #666;
    font-size: 0.8rem;
}

.media-date {
    color: #999;
    font-size: 0.7rem;
}

.media-icon {
    color: #D3C9FE;
    opacity: 0;
    transform: translateX(-10px);
    transition: all 0.3s;
}

.media-card:hover .media-icon {
    opacity: 1;
    transform: translateX(0);
}

/* Blog Grid */
.blog-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 30px;
}

.blog-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
}

.blog-image {
    aspect-ratio: 16/9;
    overflow: hidden;
}

.blog-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.blog-card:hover .blog-image img {
    transform: scale(1.05);
}

.blog-content {
    padding: 20px;
}

.blog-content h3 {
    margin-bottom: 10px;
    font-size: 1.1rem;
}

.blog-content h3 a {
    color: #031837;
    text-decoration: none;
}

.blog-content p {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 15px;
    line-height: 1.6;
}

.blog-meta {
    display: flex;
    gap: 20px;
    font-size: 0.85rem;
    color: #888;
    margin-bottom: 15px;
}

.blog-meta i {
    color: #D3C9FE;
    margin-right: 5px;
}

/* Newsletter Section */
.newsletter-content {
    max-width: 600px;
    margin: 0 auto;
    text-align: center;
    color: white;
    padding: 60px 0;
}

.newsletter-title {
    font-size: 2.2rem;
    margin-bottom: 20px;
}

.newsletter-text {
    font-size: 1.1rem;
    margin-bottom: 30px;
    opacity: 0.9;
}

.newsletter-form .form-group {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.newsletter-form input[type="email"] {
    flex: 1;
    padding: 15px 20px;
    border: none;
    border-radius: 50px;
    font-size: 1rem;
}

.newsletter-btn {
    padding: 15px 40px;
    background: #D3C9FE;
    border: none;
    border-radius: 50px;
    color: #031837;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.newsletter-btn:hover {
    background: #b8a9fe;
    transform: translateY(-2px);
}

.form-check {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    font-size: 0.9rem;
}

.form-check input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: #D3C9FE;
}

.newsletter-features {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    justify-content: center;
    margin-top: 30px;
}

.newsletter-features span {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.9rem;
}

.newsletter-features i {
    color: #D3C9FE;
}

/* Sponsors Grid */
.sponsors-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 30px;
    align-items: center;
}

.sponsor-card {
    position: relative;
    display: block;
    padding: 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    transition: all 0.3s;
    text-align: center;
    border: 1px solid #eee;
}

.sponsor-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(211,201,254,0.3);
    border-color: #D3C9FE;
}

.sponsor-card img {
    max-width: 100%;
    max-height: 60px;
    width: auto;
    height: auto;
    object-fit: contain;
}

.sponsor-level {
    position: absolute;
    top: 10px;
    right: 10px;
    font-size: 0.7rem;
    padding: 2px 8px;
    border-radius: 20px;
    font-weight: 600;
}

.level-platinum { background: #e5e4e2; color: #333; }
.level-gold { background: #ffd700; color: #333; }

/* Section Footer */
.section-footer {
    text-align: center;
    margin-top: 40px;
}

.view-all-btn {
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

.view-all-btn:hover {
    background: #D3C9FE;
    color: #031837;
    transform: translateY(-2px);
}

.read-more-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 30px;
    background: #031837;
    color: white;
    text-decoration: none;
    border-radius: 50px;
    font-weight: 600;
    transition: all 0.3s;
}

.read-more-btn:hover {
    background: #0a2a4a;
    transform: translateY(-2px);
}

.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 40px;
    color: #666;
    background: #f5f5f5;
    border-radius: 12px;
}

/* Responsive */
@media (max-width: 768px) {
    .hero-title {
        font-size: 2rem;
    }
    
    .about-grid,
    .founder-grid {
        grid-template-columns: 1fr;
        gap: 30px;
    }
    
    .research-grid {
        grid-template-columns: 1fr;
    }
    
    .founder-stats {
        justify-content: space-around;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .section-tabs {
        width: 100%;
    }
    
    .tab-btn {
        flex: 1;
        text-align: center;
    }
}

@media (max-width: 480px) {
    .hero-title {
        font-size: 1.5rem;
    }
    
    .slider-nav {
        padding: 5px 10px;
    }
    
    .dot {
        width: 8px;
        height: 8px;
    }
    
    .newsletter-form .form-group {
        flex-direction: column;
    }
    
    .newsletter-btn {
        width: 100%;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Hero Slider
    const slides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.dot');
    const prevBtn = document.querySelector('.slider-prev');
    const nextBtn = document.querySelector('.slider-next');
    let currentSlide = 0;
    let slideInterval;
    
    function showSlide(index) {
        slides.forEach(s => s.classList.remove('active'));
        dots.forEach(d => d.classList.remove('active'));
        slides[index].classList.add('active');
        dots[index].classList.add('active');
        currentSlide = index;
    }
    
    function nextSlide() {
        let next = currentSlide + 1;
        if (next >= slides.length) next = 0;
        showSlide(next);
    }
    
    function prevSlide() {
        let prev = currentSlide - 1;
        if (prev < 0) prev = slides.length - 1;
        showSlide(prev);
    }
    
    if (slides.length > 1) {
        slideInterval = setInterval(nextSlide, 5000);
        
        prevBtn?.addEventListener('click', () => {
            prevSlide();
            clearInterval(slideInterval);
            slideInterval = setInterval(nextSlide, 5000);
        });
        
        nextBtn?.addEventListener('click', () => {
            nextSlide();
            clearInterval(slideInterval);
            slideInterval = setInterval(nextSlide, 5000);
        });
        
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                showSlide(index);
                clearInterval(slideInterval);
                slideInterval = setInterval(nextSlide, 5000);
            });
        });
    }
    
    // Tab Switching
    const tabBtns = document.querySelectorAll('.tab-btn');
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tabId = this.dataset.tab;
            tabBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
        });
    });
    
    // Newsletter Form
    const newsletterForm = document.getElementById('newsletterForm');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const email = this.querySelector('input[type="email"]').value;
            const consent = document.getElementById('consent').checked;
            const submitBtn = this.querySelector('.newsletter-btn');
            const btnText = submitBtn.querySelector('.btn-text');
            const btnLoading = submitBtn.querySelector('.btn-loading');
            const messageDiv = document.getElementById('newsletter-message');
            
            if (!consent) {
                messageDiv.innerHTML = '<div class="message-error">Please agree before subscribing.</div>';
                messageDiv.style.display = 'block';
                return;
            }
            
            btnText.style.display = 'none';
            btnLoading.style.display = 'inline-block';
            submitBtn.disabled = true;
            
            try {
                const response = await fetch('subscribe.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({email})
                });
                
                const result = await response.json();
                messageDiv.innerHTML = result.success 
                    ? '<div class="message-success">Thank you! Your email has been added.</div>'
                    : '<div class="message-error">' + (result.message || 'An error occurred.') + '</div>';
                
                if (result.success) this.reset();
            } catch (error) {
                messageDiv.innerHTML = '<div class="message-error">An error occurred. Please try again.</div>';
            }
            
            messageDiv.style.display = 'block';
            btnText.style.display = 'inline-block';
            btnLoading.style.display = 'none';
            submitBtn.disabled = false;
            
            setTimeout(() => messageDiv.style.display = 'none', 5000);
        });
    }
});
</script>

<?php include __DIR__ . "/partials/footer.php"; ?>