<?php
// includes/functions.php
require_once __DIR__ . '/db.php';

// ============================================
// CORE HELPER FUNCTIONS
// ============================================

/**
 * Sanitize input to prevent XSS attacks
 */
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate URL-friendly slug from string
 */
function createSlug($string) {
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

/**
 * Get categories by type for navigation menus
 */
function getCategoriesByType($type) {
    global $conn;
    
    $type = mysqli_real_escape_string($conn, $type);
    $query = "SELECT * FROM categories WHERE type = '$type' ORDER BY name ASC";
    $result = mysqli_query($conn, $query);
    
    $categories = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $categories[] = $row;
        }
    }
    return $categories;
}

/**
 * Get image URL with proper path based on content type
 */
function getImageUrl($image, $type = 'content') {
    global $SITE_URL;
    
    if (empty($image)) {
        return $SITE_URL . '/assets/images/techinhausa-about.jpg';
    }
    
    if (filter_var($image, FILTER_VALIDATE_URL)) {
        return $image;
    }
    
    $image = ltrim($image, '/');
    
    // Map content types to upload folders
    $folders = [
        'video'    => 'videos',
        'blog'     => 'blog',
        'news'     => 'news',
        'research' => 'research',
        'media'    => 'media',
        'sponsor'  => 'sponsors',
        'creator'  => 'creator',
        'founder'  => 'founders',
        'content'  => 'content'
    ];
    
    $folder = $folders[$type] ?? 'content';
    
    // If image already includes uploads/ path
    if (strpos($image, 'uploads/') === 0) {
        return $SITE_URL . '/' . $image;
    }
    
    return $SITE_URL . '/uploads/' . $folder . '/' . $image;
}

/**
 * Format date in Hausa language
 */
function formatDateHausa($date) {
    if (empty($date)) return 'Ba kwanan wata ba';
    
    $timestamp = strtotime($date);
    if ($timestamp === false) return 'Ba kwanan wata ba';
    
    $months = [
        'Janairu', 'Fabrairu', 'Maris', 'Afrilu', 'Mayu', 'Yuni',
        'Yuli', 'Agusta', 'Satumba', 'Oktoba', 'Nuwamba', 'Disamba'
    ];
    
    $day = date('j', $timestamp);
    $month = $months[date('n', $timestamp) - 1];
    $year = date('Y', $timestamp);
    
    return "$day $month, $year";
}

/**
 * Format date in English (for admin/English UI)
 */
function formatDate($date) {
    if (empty($date)) return '';
    return date('M j, Y', strtotime($date));
}

/**
 * Truncate text to specified length
 */
function truncateText($text, $length = 100) {
    if (empty($text)) return '';
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
}

/**
 * Extract YouTube video ID from URL
 */
function getYouTubeId($url) {
    preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $url, $matches);
    return $matches[1] ?? '';
}

// ============================================
// CONTENT QUERY FUNCTIONS
// ============================================

/**
 * Get featured content for hero slider
 */
function getFeaturedContent($limit = 5) {
    global $conn;
    
    $featuredItems = [];
    $limit = (int)$limit;
    
    // Get featured videos
    $videos = mysqli_query($conn, "
        SELECT id, title, slug, 'video' as content_type, excerpt, featured_image, author, published_at 
        FROM videos 
        WHERE is_featured = 1 AND is_published = 1 
        ORDER BY published_at DESC 
        LIMIT $limit
    ");
    
    if ($videos && mysqli_num_rows($videos) > 0) {
        while ($row = mysqli_fetch_assoc($videos)) {
            $featuredItems[] = $row;
        }
    }
    
    // Get featured blog posts
    $blogs = mysqli_query($conn, "
        SELECT id, title, slug, 'blog' as content_type, excerpt, featured_image, author, published_at 
        FROM blog_posts 
        WHERE is_featured = 1 AND is_published = 1 
        ORDER BY published_at DESC 
        LIMIT $limit
    ");
    
    if ($blogs && mysqli_num_rows($blogs) > 0) {
        while ($row = mysqli_fetch_assoc($blogs)) {
            $featuredItems[] = $row;
        }
    }
    
    // Get featured news
    $news = mysqli_query($conn, "
        SELECT id, title, slug, 'news' as content_type, excerpt, featured_image, author, published_at 
        FROM news 
        WHERE is_featured = 1 AND is_published = 1 
        ORDER BY published_at DESC 
        LIMIT $limit
    ");
    
    if ($news && mysqli_num_rows($news) > 0) {
        while ($row = mysqli_fetch_assoc($news)) {
            $featuredItems[] = $row;
        }
    }
    
    // Get featured research
    $research = mysqli_query($conn, "
        SELECT id, title, slug, 'research' as content_type, excerpt, featured_image, author, published_at 
        FROM research 
        WHERE is_featured = 1 AND is_published = 1 
        ORDER BY published_at DESC 
        LIMIT $limit
    ");
    
    if ($research && mysqli_num_rows($research) > 0) {
        while ($row = mysqli_fetch_assoc($research)) {
            $featuredItems[] = $row;
        }
    }
    
    // Sort by date (newest first)
    usort($featuredItems, function($a, $b) {
        return strtotime($b['published_at']) - strtotime($a['published_at']);
    });
    
    return array_slice($featuredItems, 0, $limit);
}

/**
 * Get videos by type (latest or popular)
 */
function getVideos($type = 'latest', $limit = 4) {
    global $conn;
    
    $limit = (int)$limit;
    
    if ($type === 'popular') {
        $query = "SELECT * FROM videos WHERE is_published = 1 ORDER BY views DESC LIMIT $limit";
    } else {
        $query = "SELECT * FROM videos WHERE is_published = 1 ORDER BY published_at DESC LIMIT $limit";
    }
    
    $result = mysqli_query($conn, $query);
    
    $items = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $items[] = $row;
        }
    }
    
    return $items;
}

/**
 * Get latest blog posts
 */
function getBlogPosts($limit = 4) {
    global $conn;
    
    $limit = (int)$limit;
    $query = "SELECT * FROM blog_posts WHERE is_published = 1 ORDER BY published_at DESC LIMIT $limit";
    $result = mysqli_query($conn, $query);
    
    $items = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $items[] = $row;
        }
    }
    
    return $items;
}

/**
 * Get latest news
 */
function getNews($limit = 4) {
    global $conn;
    
    $limit = (int)$limit;
    $query = "SELECT * FROM news WHERE is_published = 1 ORDER BY published_at DESC LIMIT $limit";
    $result = mysqli_query($conn, $query);
    
    $items = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $items[] = $row;
        }
    }
    
    return $items;
}

/**
 * Get latest research
 */
function getResearch($limit = 4) {
    global $conn;
    
    $limit = (int)$limit;
    $query = "SELECT * FROM research WHERE is_published = 1 ORDER BY published_at DESC LIMIT $limit";
    $result = mysqli_query($conn, $query);
    
    $items = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $items[] = $row;
        }
    }
    
    return $items;
}

/**
 * Get content by specific author
 */
function getContentByAuthor($author, $limit = 6) {
    global $conn;
    
    $author = mysqli_real_escape_string($conn, $author);
    $limit = (int)$limit;
    
    $allItems = [];
    
    // Get videos by author
    $videos = mysqli_query($conn, "
        SELECT id, title, slug, 'video' as content_type, excerpt, featured_image, author, published_at 
        FROM videos 
        WHERE author LIKE '%$author%' AND is_published = 1 
        ORDER BY published_at DESC 
        LIMIT $limit
    ");
    
    if ($videos && mysqli_num_rows($videos) > 0) {
        while ($row = mysqli_fetch_assoc($videos)) {
            $allItems[] = $row;
        }
    }
    
    // Get blog posts by author
    $blogs = mysqli_query($conn, "
        SELECT id, title, slug, 'blog' as content_type, excerpt, featured_image, author, published_at 
        FROM blog_posts 
        WHERE author LIKE '%$author%' AND is_published = 1 
        ORDER BY published_at DESC 
        LIMIT $limit
    ");
    
    if ($blogs && mysqli_num_rows($blogs) > 0) {
        while ($row = mysqli_fetch_assoc($blogs)) {
            $allItems[] = $row;
        }
    }
    
    // Sort by date
    usort($allItems, function($a, $b) {
        return strtotime($b['published_at']) - strtotime($a['published_at']);
    });
    
    return array_slice($allItems, 0, $limit);
}

/**
 * Get active media features
 */
function getMediaFeatures($limit = 6) {
    global $conn;
    
    $limit = (int)$limit;
    $query = "SELECT * FROM media_features WHERE is_active = 1 ORDER BY is_featured DESC, feature_date DESC, display_order ASC LIMIT $limit";
    $result = mysqli_query($conn, $query);
    
    $items = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $items[] = $row;
        }
    }
    
    return $items;
}

/**
 * Get active sponsors sorted by level
 */
function getSponsors($limit = 8) {
    global $conn;
    
    $limit = (int)$limit;
    $query = "SELECT * FROM sponsors WHERE is_active = 1 ORDER BY FIELD(sponsor_level, 'platinum', 'gold', 'silver', 'bronze'), display_order ASC LIMIT $limit";
    $result = mysqli_query($conn, $query);
    
    $items = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $items[] = $row;
        }
    }
    
    return $items;
}

// ============================================
// STATIC CONTENT FUNCTIONS (Fallbacks)
// ============================================

/**
 * Get default hero slides (fallback)
 */
function getDefaultHeroSlides() {
    global $SITE_URL;
    
    return [
        [
            'id' => 0,
            'title' => 'TechInHausa - Learn Technology in Hausa',
            'slug' => '#',
            'content_type' => 'default',
            'excerpt' => 'Come and learn modern technology and AI in your native language.',
            'featured_image' => '/assets/images/hero-tech-1.jpg',
            'author' => 'MalamIromba',
            'published_at' => date('Y-m-d H:i:s')
        ],
        [
            'id' => 1,
            'title' => 'Hausa AI - Understanding Artificial Intelligence',
            'slug' => '#',
            'content_type' => 'default',
            'excerpt' => 'Learn how AI works and how you can use it in your daily life.',
            'featured_image' => '/assets/images/hero-ai-2.jpg',
            'author' => 'MalamIromba',
            'published_at' => date('Y-m-d H:i:s')
        ],
        [
            'id' => 2,
            'title' => 'Computer Programming for Beginners',
            'slug' => '#',
            'content_type' => 'default',
            'excerpt' => 'Start learning computer programming from the basics.',
            'featured_image' => '/assets/images/hero-coding-3.jpg',
            'author' => 'MalamIromba',
            'published_at' => date('Y-m-d H:i:s')
        ]
    ];
}

/**
 * Get about page content (static fallback)
 */
function getAboutContent() {
    return [
        'title' => 'TechInHausa | Hausa AI | MalamIromba',
        'subtitle' => 'Personal Education Initiative by Ibrahim Zubairu',
        'description' => 'TechInHausa is a pioneering initiative dedicated to making technology and AI education accessible to Hausa-speaking communities. Founded by Ibrahim Zubairu (MalamIromba), we provide high-quality tech content in the Hausa language, breaking down complex concepts into easy-to-understand lessons.',
        'mission' => 'To democratize tech education and empower Hausa speakers with the skills needed to thrive in the digital economy.',
        'vision' => 'A future where language is no barrier to accessing world-class technology education.',
        'image' => '/assets/images/about/ibrahim-zubairu.jpg',
        'years_active' => 2,
        'students_reached' => '50,000+',
        'content_items' => '200+',
        'partners' => '15+'
    ];
}

// ============================================
// UTILITY FUNCTIONS
// ============================================

/**
 * Safe redirect function
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Check if string starts with a given substring
 */
function startsWith($haystack, $needle) {
    return substr($haystack, 0, strlen($needle)) === $needle;
}

/**
 * Check if string ends with a given substring
 */
function endsWith($haystack, $needle) {
    $length = strlen($needle);
    if ($length == 0) return true;
    return substr($haystack, -$length) === $needle;
}

/**
 * Generate random string (for filenames, etc.)
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}
?>