<?php
// watch.php - TechInHausa Single Video Page
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Get video slug from URL (URL format: /video/slug)
$request_uri = $_SERVER['REQUEST_URI'];
$path_parts = explode('/', trim($request_uri, '/'));
$slug = end($path_parts);

// Alternative: If using query string, also check for slug parameter
if (empty($slug) || $slug === 'watch.php') {
    $slug = isset($_GET['slug']) ? sanitize($_GET['slug']) : '';
}

if (empty($slug)) {
    header("Location: " . SITE_URL . "/videos.php");
    exit();
}

// Get video details from database
$query = "SELECT v.*, c.name as category_name, c.slug as category_slug 
          FROM videos v 
          LEFT JOIN categories c ON v.category_id = c.id 
          WHERE v.slug = '$slug' AND v.is_published = 1 
          LIMIT 1";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    // Video not found, redirect to videos page
    header("Location: " . SITE_URL . "/videos.php");
    exit();
}

$video = mysqli_fetch_assoc($result);

// Update view count
mysqli_query($conn, "UPDATE videos SET views = views + 1 WHERE id = {$video['id']}");

// Get YouTube video ID from URL for embedding
$video_id = getYouTubeId($video['video_url']);

// Get related videos (same category, excluding current video)
$related_query = "SELECT * FROM videos 
                  WHERE category_id = {$video['category_id']} 
                  AND id != {$video['id']} 
                  AND is_published = 1 
                  ORDER BY 
                    CASE WHEN is_featured = 1 THEN 0 ELSE 1 END,
                    views DESC 
                  LIMIT 4";
$related_videos = mysqli_query($conn, $related_query);

// Get previous and next videos for navigation
$prev_query = "SELECT slug, title FROM videos 
               WHERE (published_at < '{$video['published_at']}' OR (published_at = '{$video['published_at']}' AND id < {$video['id']}))
               AND is_published = 1 
               ORDER BY published_at DESC, id DESC 
               LIMIT 1";
$prev_result = mysqli_query($conn, $prev_query);
$prev_video = mysqli_fetch_assoc($prev_result);

$next_query = "SELECT slug, title FROM videos 
               WHERE (published_at > '{$video['published_at']}' OR (published_at = '{$video['published_at']}' AND id > {$video['id']}))
               AND is_published = 1 
               ORDER BY published_at ASC, id ASC 
               LIMIT 1";
$next_result = mysqli_query($conn, $next_query);
$next_video = mysqli_fetch_assoc($next_result);

// Parse tags if they exist
$tags = !empty($video['tags']) ? explode(',', $video['tags']) : [];

$pageTitle = $video['title'] . " - TechInHausa";
$pageDesc = $video['excerpt'] ?? "Watch " . $video['title'] . " on TechInHausa";

include 'partials/header.php';
?>

<!-- Video Player Section -->
<section class="video-player-section">
    <div class="container">
        <div class="video-player-wrapper">
            <!-- Video Player -->
            <div class="video-player">
                <div class="video-container" id="videoContainer">
                    <?php if (!empty($video_id)): ?>
                        <!-- YouTube Player with play button overlay -->
                        <div id="player" class="youtube-player" data-video-id="<?= $video_id ?>"></div>
                        <div class="play-overlay" id="playOverlay" onclick="playVideo()">
                            <div class="play-button-large">
                                <i class="fas fa-play-circle"></i>
                            </div>
                            <div class="video-thumbnail-preview">
                                <img src="https://img.youtube.com/vi/<?= $video_id ?>/maxresdefault.jpg" 
                                     alt="<?= htmlspecialchars($video['title']) ?>"
                                     onerror="this.src='https://img.youtube.com/vi/<?= $video_id ?>/hqdefault.jpg'">
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="video-error">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p>Video information is invalid. Please try again later.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Video Info -->
            <div class="video-info-header">
                <h1 class="video-title"><?= htmlspecialchars($video['title']) ?></h1>
                
                <div class="video-stats">
                    <div class="video-meta">
                        <span class="meta-item">
                            <i class="fas fa-eye"></i> <?= number_format($video['views']) ?> views
                        </span>
                        <span class="meta-item">
                            <i class="fas fa-calendar-alt"></i> <?= formatDate($video['published_at'] ?? $video['created_at']) ?>
                        </span>
                        <?php if (!empty($video['video_duration'])): ?>
                            <span class="meta-item">
                                <i class="fas fa-clock"></i> <?= $video['video_duration'] ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="video-actions">
                        <button class="action-btn share-btn" onclick="shareVideo()" title="Share">
                            <i class="fas fa-share-alt"></i>
                        </button>
                        <button class="action-btn like-btn" onclick="likeVideo()" title="Like">
                            <i class="fas fa-thumbs-up"></i>
                        </button>
                    </div>
                </div>
                
                <div class="video-author">
                    <div class="author-info">
                        <div class="author-avatar">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <div class="author-details">
                            <span class="author-name"><?= htmlspecialchars($video['author'] ?? 'MalamIromba') ?></span>
                        </div>
                    </div>
                    
                    <?php if (!empty($video['category_name'])): ?>
                        <a href="videos.php?category=<?= $video['category_id'] ?>" class="video-category">
                            <i class="fas fa-folder"></i> <?= htmlspecialchars($video['category_name']) ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Video Content Section -->
<section class="video-content-section">
    <div class="container">
        <div class="content-wrapper">
            <!-- Main Content -->
            <div class="main-content">
                <!-- Description -->
                <div class="video-description-card">
                    <h2><i class="fas fa-align-left"></i> Video Description</h2>
                    <div class="description-content">
                        <?php if (!empty($video['description'])): ?>
                            <p><?= nl2br(htmlspecialchars($video['description'])) ?></p>
                        <?php elseif (!empty($video['excerpt'])): ?>
                            <p><?= nl2br(htmlspecialchars($video['excerpt'])) ?></p>
                        <?php else: ?>
                            <p>No description available for this video.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Tags -->
                <?php if (!empty($tags)): ?>
                    <div class="video-tags-card">
                        <h2><i class="fas fa-tags"></i> Tags</h2>
                        <div class="tags-list">
                            <?php foreach ($tags as $tag): 
                                $tag = trim($tag);
                                if (!empty($tag)):
                            ?>
                                <a href="videos.php?search=<?= urlencode($tag) ?>" class="tag">
                                    #<?= htmlspecialchars($tag) ?>
                                </a>
                            <?php endif; endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <div class="video-sidebar">
                <!-- Next/Prev Navigation -->
                <?php if ($prev_video || $next_video): ?>
                <div class="sidebar-card video-navigation">
                    <h3><i class="fas fa-arrows-alt-h"></i> Navigation</h3>
                    <div class="nav-links">
                        <?php if ($prev_video): ?>
                            <a href="video/<?= $prev_video['slug'] ?>" class="nav-link prev">
                                <i class="fas fa-arrow-left"></i>
                                <div class="nav-text">
                                    <span class="nav-label">Previous</span>
                                    <span class="nav-title"><?= htmlspecialchars($prev_video['title']) ?></span>
                                </div>
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($next_video): ?>
                            <a href="video/<?= $next_video['slug'] ?>" class="nav-link next">
                                <div class="nav-text">
                                    <span class="nav-label">Next</span>
                                    <span class="nav-title"><?= htmlspecialchars($next_video['title']) ?></span>
                                </div>
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Related Videos -->
                <?php if (mysqli_num_rows($related_videos) > 0): ?>
                <div class="sidebar-card related-videos">
                    <h3><i class="fas fa-video"></i> Related Videos</h3>
                    <div class="related-list">
                        <?php while ($related = mysqli_fetch_assoc($related_videos)): 
                            $rel_video_id = getYouTubeId($related['video_url']);
                            $rel_thumbnail = !empty($related['featured_image']) 
                                ? getImageUrl($related['featured_image'], 'video') 
                                : "https://img.youtube.com/vi/{$rel_video_id}/mqdefault.jpg";
                        ?>
                            <a href="video/<?= $related['slug'] ?>" class="related-item">
                                <div class="related-thumbnail">
                                    <img src="<?= $rel_thumbnail ?>" 
                                         alt="<?= htmlspecialchars($related['title']) ?>"
                                         loading="lazy"
                                         onerror="this.src='<?= SITE_URL ?>/assets/images/video-placeholder.jpg'">
                                    <?php if (!empty($related['video_duration'])): ?>
                                        <span class="related-duration"><?= $related['video_duration'] ?></span>
                                    <?php endif; ?>
                                    <span class="related-play"><i class="fas fa-play"></i></span>
                                </div>
                                <div class="related-info">
                                    <h4 class="related-title"><?= htmlspecialchars($related['title']) ?></h4>
                                    <span class="related-views">
                                        <i class="fas fa-eye"></i> <?= number_format($related['views']) ?>
                                    </span>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- YouTube IFrame API -->
<script src="https://www.youtube.com/iframe_api"></script>

<script>
// YouTube Player
var player;
var videoId = '<?= $video_id ?>';

function onYouTubeIframeAPIReady() {
    // Player will be created when play button is clicked
}

function playVideo() {
    // Hide overlay
    document.getElementById('playOverlay').classList.add('hidden');
    
    // Create player if it doesn't exist
    if (!player) {
        player = new YT.Player('player', {
            height: '100%',
            width: '100%',
            videoId: videoId,
            playerVars: {
                'autoplay': 1,
                'rel': 0,
                'modestbranding': 1
            },
            events: {
                'onReady': onPlayerReady,
                'onStateChange': onPlayerStateChange
            }
        });
    } else {
        player.playVideo();
    }
}

function onPlayerReady(event) {
    event.target.playVideo();
}

function onPlayerStateChange(event) {
    // Video ended
    if (event.data === YT.PlayerState.ENDED) {
        // Show overlay again
        document.getElementById('playOverlay').classList.remove('hidden');
    }
}

// Share functionality
function shareVideo() {
    if (navigator.share) {
        navigator.share({
            title: '<?= htmlspecialchars($video['title'], ENT_QUOTES) ?>',
            text: '<?= htmlspecialchars($video['excerpt'] ?? 'Watch this video on TechInHausa', ENT_QUOTES) ?>',
            url: window.location.href
        })
        .catch(() => {
            fallbackShare();
        });
    } else {
        fallbackShare();
    }
}

function fallbackShare() {
    // Copy link to clipboard
    navigator.clipboard.writeText(window.location.href).then(() => {
        showNotification('Link copied to clipboard!');
    }).catch(() => {
        showNotification('An error occurred. Please try again.');
    });
}

function likeVideo() {
    showNotification('Thank you for liking this video!');
}

function showNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}
</script>

<!-- Note: formatDate function needs to be added to functions.php if not exists -->
<?php
// Add this to functions.php if needed:
// function formatDate($date) {
//     return date('F j, Y', strtotime($date));
// }
?>

<style>
/* Copy all the styles from the previous watch.php */
/* Video Player Section */
.video-player-section {
    background: #031837;
    padding: 30px 0;
}

.video-player-wrapper {
    max-width: 1000px;
    margin: 0 auto;
}

.video-player {
    margin-bottom: 20px;
    position: relative;
}

.video-container {
    position: relative;
    width: 100%;
    aspect-ratio: 16/9;
    overflow: hidden;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    background: #000;
}

/* YouTube Player */
.youtube-player {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border: 0;
    z-index: 1;
}

/* Play Overlay */
.play-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    cursor: pointer;
    z-index: 2;
    transition: opacity 0.3s;
}

.play-overlay.hidden {
    opacity: 0;
    pointer-events: none;
}

.video-thumbnail-preview {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

.video-thumbnail-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.play-button-large {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 3;
    font-size: 5rem;
    color: white;
    opacity: 0.9;
    transition: all 0.3s;
    text-shadow: 0 5px 15px rgba(0,0,0,0.5);
}

.play-overlay:hover .play-button-large {
    transform: translate(-50%, -50%) scale(1.1);
    opacity: 1;
    color: #D3C9FE;
}

.video-error {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: #f5f5f5;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border-radius: 15px;
}

.video-error i {
    font-size: 3rem;
    color: #dc3545;
    margin-bottom: 15px;
}

/* Video Info Header */
.video-info-header {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.video-info-header .video-title {
    font-size: 1.8rem;
    color: #031837;
    margin-bottom: 15px;
    line-height: 1.3;
}

.video-stats {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    padding-bottom: 20px;
    border-bottom: 1px solid rgba(211, 201, 254, 0.3);
    margin-bottom: 20px;
}

.video-meta {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.meta-item {
    color: #666;
    font-size: 0.95rem;
}

.meta-item i {
    color: #D3C9FE;
    margin-right: 5px;
}

.video-actions {
    display: flex;
    gap: 10px;
}

.action-btn {
    width: 45px;
    height: 45px;
    background: #f0f4ff;
    border: none;
    border-radius: 50%;
    color: #031837;
    font-size: 1.1rem;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.action-btn:hover {
    background: #031837;
    color: white;
    transform: translateY(-2px);
}

.action-btn:hover i {
    color: #D3C9FE;
}

.video-author {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.author-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.author-avatar i {
    font-size: 3rem;
    color: #D3C9FE;
}

.author-details {
    display: flex;
    flex-direction: column;
}

.author-name {
    font-weight: 600;
    color: #031837;
    font-size: 1.1rem;
}

.video-category {
    padding: 8px 20px;
    background: #D3C9FE;
    color: #031837;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.video-category:hover {
    background: #b8a9fe;
    transform: translateY(-2px);
}

/* Video Content Section */
.video-content-section {
    padding: 40px 0;
    background: #f5f7fb;
}

.content-wrapper {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
}

/* Main Content Cards */
.main-content {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.video-description-card,
.video-tags-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    border: 1px solid rgba(211, 201, 254, 0.2);
}

.video-description-card h2,
.video-tags-card h2 {
    font-size: 1.3rem;
    color: #031837;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.video-description-card h2 i,
.video-tags-card h2 i {
    color: #D3C9FE;
}

.description-content {
    color: #444;
    line-height: 1.8;
}

.tags-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.tag {
    padding: 6px 15px;
    background: #f0f4ff;
    color: #031837;
    text-decoration: none;
    border-radius: 20px;
    font-size: 0.9rem;
    transition: all 0.3s;
}

.tag:hover {
    background: #031837;
    color: white;
}

/* Sidebar Cards */
.sidebar-card {
    background: white;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    border: 1px solid rgba(211, 201, 254, 0.2);
    margin-bottom: 25px;
}

.sidebar-card h3 {
    font-size: 1.1rem;
    color: #031837;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.sidebar-card h3 i {
    color: #D3C9FE;
}

/* Video Navigation */
.nav-links {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 10px;
    text-decoration: none;
    transition: all 0.3s;
}

.nav-link:hover {
    background: #031837;
    transform: translateY(-2px);
}

.nav-link:hover .nav-label,
.nav-link:hover .nav-title {
    color: white;
}

.nav-link:hover i {
    color: #D3C9FE;
}

.nav-link i {
    color: #031837;
    font-size: 1.2rem;
    transition: color 0.3s;
}

.nav-text {
    flex: 1;
}

.nav-label {
    font-size: 0.75rem;
    color: #888;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.nav-title {
    font-size: 0.9rem;
    color: #031837;
    font-weight: 500;
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 150px;
}

.nav-link.next {
    justify-content: flex-end;
}

/* Related Videos */
.related-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.related-item {
    display: flex;
    gap: 12px;
    text-decoration: none;
    transition: all 0.3s;
    padding: 8px;
    border-radius: 10px;
}

.related-item:hover {
    background: #f8f9fa;
    transform: translateX(5px);
}

.related-thumbnail {
    position: relative;
    width: 120px;
    height: 68px;
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
}

.related-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.related-item:hover .related-thumbnail img {
    transform: scale(1.1);
}

.related-duration {
    position: absolute;
    bottom: 3px;
    right: 3px;
    background: rgba(0,0,0,0.8);
    color: white;
    padding: 2px 5px;
    border-radius: 3px;
    font-size: 0.65rem;
}

.related-play {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    opacity: 0;
    transition: opacity 0.3s;
    font-size: 1.8rem;
    text-shadow: 0 2px 8px rgba(0,0,0,0.5);
}

.related-item:hover .related-play {
    opacity: 1;
}

.related-info {
    flex: 1;
    min-width: 0;
}

.related-title {
    font-size: 0.95rem;
    color: #031837;
    margin-bottom: 5px;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.related-views {
    font-size: 0.75rem;
    color: #888;
}

.related-views i {
    color: #D3C9FE;
    margin-right: 3px;
}

/* Notification */
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #031837;
    color: white;
    padding: 12px 24px;
    border-radius: 8px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.2);
    animation: slideIn 0.3s;
    z-index: 1000;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Responsive */
@media (max-width: 992px) {
    .content-wrapper {
        grid-template-columns: 1fr;
    }
    
    .video-info-header .video-title {
        font-size: 1.5rem;
    }
}

@media (max-width: 768px) {
    .video-stats {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .video-author {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .video-category {
        align-self: flex-start;
    }
    
    .video-player-section {
        padding: 20px 0;
    }
    
    .play-button-large {
        font-size: 3.5rem;
    }
}

@media (max-width: 480px) {
    .video-meta {
        flex-direction: column;
        gap: 8px;
    }
    
    .video-actions {
        width: 100%;
        justify-content: center;
    }
    
    .related-item {
        flex-direction: column;
    }
    
    .related-thumbnail {
        width: 100%;
        height: auto;
        aspect-ratio: 16/9;
    }
    
    .play-button-large {
        font-size: 3rem;
    }
}
</style>

<?php include 'partials/footer.php'; ?>