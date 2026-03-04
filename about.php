<?php
// about.php - TechInHausa About Page
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = "About Us - TechInHausa";
$pageDesc = "Learn about TechInHausa, our mission, vision, and the story of MalamIromba. We bring you tech and AI education in the Hausa language.";

include __DIR__ . "/partials/header.php";

// Get founder info from creator table
$founderQuery = mysqli_query($conn, 
    "SELECT * FROM creator 
     WHERE (author LIKE '%Ibrahim Zubairu%' OR author = 'MalamIromba') 
     AND content_type = 'profile' 
     AND is_published = 1
     LIMIT 1"
);

if ($founderQuery && mysqli_num_rows($founderQuery) > 0) {
    $founderData = mysqli_fetch_assoc($founderQuery);
    $founder = [
        'name' => $founderData['author'],
        'title' => 'MalamIromba',
        'bio' => $founderData['excerpt'] ?? $founderData['content'] ?? 'Founder of TechInHausa, passionate about making technology education accessible to Hausa-speaking communities.',
        'image' => $founderData['featured_image'] ?? 'founder.jpg',
        'years_active' => $founderData['years_active'] ?? 5,
        'projects' => $founderData['projects_count'] ?? 50,
        'students' => $founderData['students_count'] ?? '10,000+'
    ];
} else {
    // Default founder info if no profile found
    $founder = [
        'name' => 'Ibrahim Zubairu',
        'title' => 'MalamIromba',
        'bio' => 'Founder of TechInHausa, passionate about making technology education accessible to Hausa-speaking communities. With years of experience in software development and education, he has helped thousands of students learn programming and AI in their native language.',
        'image' => 'founder.jpg',
        'years_active' => 5,
        'projects' => 50,
        'students' => '10,000+'
    ];
}

// Get team members (if you have a team table)
$teamMembers = [];
$teamQuery = mysqli_query($conn, 
    "SELECT * FROM team_members 
     WHERE is_active = 1 
     ORDER BY display_order ASC 
     LIMIT 6"
);
if ($teamQuery && mysqli_num_rows($teamQuery) > 0) {
    while ($row = mysqli_fetch_assoc($teamQuery)) {
        $teamMembers[] = $row;
    }
}

// Get stats
$totalVideos = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM videos WHERE is_published = 1"))['count'] ?? 0;
$totalBlogs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM blog_posts WHERE is_published = 1"))['count'] ?? 0;
$totalNews = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM news WHERE is_published = 1"))['count'] ?? 0;
$totalResearch = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM research WHERE is_published = 1"))['count'] ?? 0;
$totalSubscribers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM subscribers WHERE is_active = 1"))['count'] ?? 0;
?>

<style>
    /* About Page Specific Styles */
    .about-hero {
        background: linear-gradient(135deg, #031837 0%, #0a2a4a 100%);
        padding: 80px 0;
        text-align: center;
        color: white;
        position: relative;
        overflow: hidden;
    }

    .about-hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: repeating-linear-gradient(
            45deg,
            rgba(211, 201, 254, 0.05) 0px,
            rgba(211, 201, 254, 0.05) 2px,
            transparent 2px,
            transparent 8px
        );
        pointer-events: none;
    }

    .about-hero h1 {
        font-size: 3.5rem;
        margin-bottom: 20px;
        background: linear-gradient(135deg, #fff 0%, #D3C9FE 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .about-hero p {
        font-size: 1.2rem;
        max-width: 800px;
        margin: 0 auto;
        opacity: 0.9;
    }

    .about-section {
        padding: 80px 0;
    }

    .about-section:nth-child(even) {
        background-color: #f8f9ff;
    }

    .about-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .about-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 60px;
        align-items: center;
    }

    .about-content h2 {
        font-size: 2.5rem;
        color: #031837;
        margin-bottom: 20px;
        position: relative;
        padding-bottom: 15px;
    }

    .about-content h2::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 80px;
        height: 4px;
        background: #D3C9FE;
        border-radius: 2px;
    }

    .about-content p {
        font-size: 1.1rem;
        line-height: 1.8;
        color: #444;
        margin-bottom: 20px;
    }

    .about-image {
        position: relative;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 20px 40px rgba(3, 24, 55, 0.2);
    }

    .about-image img {
        width: 100%;
        height: auto;
        display: block;
        transition: transform 0.5s;
    }

    .about-image:hover img {
        transform: scale(1.05);
    }

    .about-image::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(211, 201, 254, 0.2) 0%, transparent 100%);
        pointer-events: none;
    }

    /* Stats Section */
    .stats-section {
        background: linear-gradient(135deg, #031837 0%, #0a2a4a 100%);
        padding: 60px 0;
        color: white;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 30px;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .stat-card {
        text-align: center;
        padding: 30px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(211, 201, 254, 0.2);
        border-radius: 15px;
        backdrop-filter: blur(10px);
        transition: transform 0.3s;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        border-color: #D3C9FE;
    }

    .stat-icon {
        font-size: 2.5rem;
        color: #D3C9FE;
        margin-bottom: 15px;
    }

    .stat-number {
        font-size: 2.5rem;
        font-weight: 700;
        color: white;
        margin-bottom: 5px;
    }

    .stat-label {
        font-size: 1rem;
        opacity: 0.8;
    }

    /* Mission & Vision */
    .mission-vision-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 30px;
        margin-top: 40px;
    }

    .mission-card,
    .vision-card {
        padding: 40px;
        background: white;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        text-align: center;
        border: 1px solid rgba(211, 201, 254, 0.2);
        transition: all 0.3s;
    }

    .mission-card:hover,
    .vision-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px rgba(211, 201, 254, 0.2);
        border-color: #D3C9FE;
    }

    .card-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #031837 0%, #0a2a4a 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
    }

    .card-icon i {
        font-size: 2.5rem;
        color: #D3C9FE;
    }

    .mission-card h3,
    .vision-card h3 {
        font-size: 1.8rem;
        color: #031837;
        margin-bottom: 15px;
    }

    .mission-card p,
    .vision-card p {
        font-size: 1rem;
        line-height: 1.8;
        color: #666;
    }

    /* Founder Section */
    .founder-detailed {
        padding: 80px 0;
        background: white;
    }

    .founder-grid {
        display: grid;
        grid-template-columns: 1fr 1.5fr;
        gap: 50px;
        align-items: center;
    }

    .founder-image {
        position: relative;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 20px 40px rgba(3, 24, 55, 0.2);
    }

    .founder-image img {
        width: 100%;
        height: auto;
        display: block;
    }

    .founder-image::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(211, 201, 254, 0.2) 0%, transparent 100%);
        pointer-events: none;
    }

    .founder-content {
        padding: 20px;
    }

    .founder-label {
        color: #D3C9FE;
        font-size: 1rem;
        text-transform: uppercase;
        letter-spacing: 2px;
        margin-bottom: 10px;
        display: block;
    }

    .founder-name {
        font-size: 2.5rem;
        color: #031837;
        margin-bottom: 5px;
    }

    .founder-title {
        font-size: 1.2rem;
        color: #666;
        margin-bottom: 20px;
    }

    .founder-bio {
        font-size: 1.1rem;
        line-height: 1.8;
        color: #444;
        margin-bottom: 30px;
    }

    .founder-stats {
        display: flex;
        gap: 40px;
        margin-bottom: 30px;
    }

    .stat-item {
        text-align: center;
    }

    .stat-item .number {
        display: block;
        font-size: 2rem;
        font-weight: 700;
        color: #031837;
    }

    .stat-item .label {
        color: #666;
        font-size: 0.9rem;
    }

    .social-links {
        display: flex;
        gap: 15px;
    }

    .social-link {
        width: 45px;
        height: 45px;
        background: rgba(211, 201, 254, 0.1);
        border: 1px solid rgba(211, 201, 254, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #031837;
        font-size: 1.2rem;
        transition: all 0.3s;
        text-decoration: none;
    }

    .social-link:hover {
        background: #D3C9FE;
        color: #031837;
        transform: translateY(-3px);
    }

    /* Team Section */
    .team-section {
        padding: 80px 0;
        background: #f8f9ff;
    }

    .team-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 30px;
        margin-top: 40px;
    }

    .team-card {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        transition: all 0.3s;
        text-align: center;
        border: 1px solid rgba(211, 201, 254, 0.2);
    }

    .team-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(211, 201, 254, 0.3);
        border-color: #D3C9FE;
    }

    .team-image {
        width: 100%;
        height: 280px;
        overflow: hidden;
    }

    .team-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s;
    }

    .team-card:hover .team-image img {
        transform: scale(1.05);
    }

    .team-info {
        padding: 20px;
    }

    .team-info h3 {
        font-size: 1.3rem;
        color: #031837;
        margin-bottom: 5px;
    }

    .team-position {
        color: #D3C9FE;
        font-weight: 600;
        margin-bottom: 15px;
    }

    .team-bio {
        color: #666;
        font-size: 0.9rem;
        line-height: 1.6;
        margin-bottom: 15px;
    }

    .team-social {
        display: flex;
        justify-content: center;
        gap: 10px;
    }

    .team-social a {
        width: 35px;
        height: 35px;
        background: rgba(211, 201, 254, 0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #031837;
        transition: all 0.3s;
        text-decoration: none;
    }

    .team-social a:hover {
        background: #D3C9FE;
        transform: translateY(-2px);
    }

    /* Timeline */
    .timeline-section {
        padding: 80px 0;
        background: white;
    }

    .timeline {
        position: relative;
        max-width: 800px;
        margin: 40px auto 0;
    }

    .timeline::before {
        content: '';
        position: absolute;
        top: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 2px;
        height: 100%;
        background: linear-gradient(180deg, #D3C9FE 0%, transparent 100%);
    }

    .timeline-item {
        position: relative;
        margin-bottom: 50px;
    }

    .timeline-dot {
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        width: 20px;
        height: 20px;
        background: #D3C9FE;
        border: 4px solid #031837;
        border-radius: 50%;
        z-index: 2;
    }

    .timeline-content {
        position: relative;
        width: calc(50% - 40px);
        padding: 20px;
        background: #f8f9ff;
        border-radius: 10px;
        border: 1px solid rgba(211, 201, 254, 0.2);
    }

    .timeline-item:nth-child(odd) .timeline-content {
        left: 0;
    }

    .timeline-item:nth-child(even) .timeline-content {
        left: 50%;
        margin-left: 40px;
    }

    .timeline-year {
        display: inline-block;
        padding: 5px 15px;
        background: #031837;
        color: white;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
        margin-bottom: 10px;
    }

    .timeline-title {
        font-size: 1.3rem;
        color: #031837;
        margin-bottom: 10px;
    }

    .timeline-text {
        color: #666;
        line-height: 1.6;
    }

    /* Values Section */
    .values-section {
        padding: 80px 0;
        background: #f8f9ff;
    }

    .values-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 30px;
        margin-top: 40px;
    }

    .value-card {
        padding: 30px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        text-align: center;
        border: 1px solid rgba(211, 201, 254, 0.2);
        transition: all 0.3s;
    }

    .value-card:hover {
        transform: translateY(-5px);
        border-color: #D3C9FE;
    }

    .value-icon {
        width: 70px;
        height: 70px;
        background: linear-gradient(135deg, #031837 0%, #0a2a4a 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
    }

    .value-icon i {
        font-size: 2rem;
        color: #D3C9FE;
    }

    .value-card h3 {
        font-size: 1.3rem;
        color: #031837;
        margin-bottom: 10px;
    }

    .value-card p {
        color: #666;
        line-height: 1.6;
    }

    /* CTA Section */
    .about-cta {
        padding: 60px 0;
        background: linear-gradient(135deg, #031837 0%, #0a2a4a 100%);
        text-align: center;
        color: white;
    }

    .about-cta h2 {
        font-size: 2.5rem;
        margin-bottom: 20px;
    }

    .about-cta p {
        font-size: 1.2rem;
        margin-bottom: 30px;
        opacity: 0.9;
    }

    .cta-buttons {
        display: flex;
        gap: 20px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .cta-btn {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 15px 35px;
        border-radius: 50px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s;
    }

    .cta-primary {
        background: #D3C9FE;
        color: #031837;
    }

    .cta-primary:hover {
        background: #b8a9fe;
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(211, 201, 254, 0.3);
    }

    .cta-secondary {
        background: transparent;
        color: white;
        border: 2px solid rgba(211, 201, 254, 0.3);
    }

    .cta-secondary:hover {
        border-color: #D3C9FE;
        transform: translateY(-2px);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .about-hero h1 {
            font-size: 2.5rem;
        }

        .about-grid,
        .founder-grid,
        .mission-vision-grid {
            grid-template-columns: 1fr;
            gap: 30px;
        }

        .timeline::before {
            left: 30px;
        }

        .timeline-dot {
            left: 30px;
        }

        .timeline-content {
            width: calc(100% - 80px);
            left: 80px !important;
            margin-left: 0 !important;
        }

        .founder-stats {
            flex-wrap: wrap;
            justify-content: center;
        }

        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }

        .cta-buttons {
            flex-direction: column;
        }

        .cta-btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<!-- About Hero Section -->
<section class="about-hero">
    <div class="about-container">
        <h1>About Us</h1>
        <p>TechInHausa is the source of technology and AI education in the Hausa language. We bring you the latest tutorials, videos, and news to help you understand the tech world in your native language.</p>
    </div>
</section>

<!-- Stats Section -->
<section class="stats-section">
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-video"></i>
            </div>
            <div class="stat-number"><?php echo $totalVideos; ?></div>
            <div class="stat-label">Videos</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-blog"></i>
            </div>
            <div class="stat-number"><?php echo $totalBlogs; ?></div>
            <div class="stat-label">Blog Posts</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-newspaper"></i>
            </div>
            <div class="stat-number"><?php echo $totalNews; ?></div>
            <div class="stat-label">News</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-flask"></i>
            </div>
            <div class="stat-number"><?php echo $totalResearch; ?></div>
            <div class="stat-label">Research</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-number"><?php echo $totalSubscribers; ?></div>
            <div class="stat-label">Subscribers</div>
        </div>
    </div>
</section>

<!-- Our Story Section -->
<section class="about-section">
    <div class="about-container">
        <div class="about-grid">
            <div class="about-content">
                <h2>Our Story</h2>
                <p>TechInHausa was founded in 2020 by Ibrahim Zubairu (MalamIromba), with the aim of bringing technology and AI education to the Hausa-speaking community in their native language. We started with a few videos on computer programming, but have now grown into a comprehensive educational platform offering tutorials, blog posts, news, and research in various technology fields.</p>
                <p>We believe that language should not be a barrier to technology education. That's why we are committed to translating and explaining tech concepts in an easy-to-understand way for anyone willing to learn.</p>
            </div>
            <div class="about-image">
                <img src="<?= SITE_URL ?>/assets/images/about-story.jpg" alt="TechInHausa Story" onerror="this.src='<?= SITE_URL ?>/assets/images/techinhausa-about.jpg'">
            </div>
        </div>
    </div>
</section>

<!-- Mission & Vision -->
<section class="about-section">
    <div class="about-container">
        <div class="mission-vision-grid">
            <div class="mission-card">
                <div class="card-icon">
                    <i class="fas fa-bullseye"></i>
                </div>
                <h3>Our Mission</h3>
                <p>To bring technology and AI education to every Hausa speaker, regardless of their status. We want to see Hausa people leading the technological transformation in Nigeria and the world at large.</p>
            </div>
            <div class="vision-card">
                <div class="card-icon">
                    <i class="fas fa-eye"></i>
                </div>
                <h3>Our Vision</h3>
                <p>To become the leading technology and AI education hub in Northern Nigeria, producing skilled tech professionals who will transform our communities through the use of technology.</p>
            </div>
        </div>
    </div>
</section>

<!-- Founder Detailed Section -->
<section class="founder-detailed">
    <div class="about-container">
        <div class="founder-grid">
            <div class="founder-image">
                <img src="<?= getImageUrl($founder['image'], 'creator') ?>" alt="<?= $founder['name'] ?>" onerror="this.src='<?= SITE_URL ?>/assets/images/techinhausa-about.jpg'">
            </div>
            <div class="founder-content">
                <span class="founder-label">Founder</span>
                <h2 class="founder-name"><?= $founder['name'] ?></h2>
                <h3 class="founder-title"><?= $founder['title'] ?></h3>
                <p class="founder-bio"><?= $founder['bio'] ?></p>
                
                <div class="founder-stats">
                    <div class="stat-item">
                        <span class="number"><?= $founder['years_active'] ?></span>
                        <span class="label">Years Active</span>
                    </div>
                    <div class="stat-item">
                        <span class="number"><?= $founder['projects'] ?>+</span>
                        <span class="label">Projects</span>
                    </div>
                    <div class="stat-item">
                        <span class="number"><?= $founder['students'] ?></span>
                        <span class="label">Students</span>
                    </div>
                </div>
                
                <div class="social-links">
                    <a href="#" class="social-link" target="_blank"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-link" target="_blank"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" class="social-link" target="_blank"><i class="fab fa-github"></i></a>
                    <a href="#" class="social-link" target="_blank"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Timeline Section -->
<section class="timeline-section">
    <div class="about-container">
        <h2 style="text-align: center; font-size: 2.5rem; color: #031837; margin-bottom: 20px;">Our Journey</h2>
        <p style="text-align: center; color: #666; max-width: 700px; margin: 0 auto 40px;">From our beginnings to today, here is the story of TechInHausa in brief.</p>
        
        <div class="timeline">
            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <span class="timeline-year">2020</span>
                    <h3 class="timeline-title">TechInHausa Founded</h3>
                    <p class="timeline-text">Ibrahim Zubairu founded TechInHausa with the aim of bringing tech education in Hausa. We started with a few videos on YouTube.</p>
                </div>
            </div>
            
            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <span class="timeline-year">2021</span>
                    <h3 class="timeline-title">Blog Launched</h3>
                    <p class="timeline-text">We launched our blog to provide written tutorials and news for readers.</p>
                </div>
            </div>
            
            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <span class="timeline-year">2022</span>
                    <h3 class="timeline-title">Expansion to News and Research</h3>
                    <p class="timeline-text">We started publishing tech news and academic research to provide comprehensive information to the community.</p>
                </div>
            </div>
            
            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <span class="timeline-year">2023</span>
                    <h3 class="timeline-title">Media Recognition</h3>
                    <p class="timeline-text">TechInHausa was featured in major media outlets such as TechCrunch, BBC Hausa, and Nigerian newspapers.</p>
                </div>
            </div>
            
            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <span class="timeline-year">2024</span>
                    <h3 class="timeline-title">New Website Launch</h3>
                    <p class="timeline-text">We launched this new website to provide a better experience for our users.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Our Values -->
<section class="values-section">
    <div class="about-container">
        <h2 style="text-align: center; font-size: 2.5rem; color: #031837; margin-bottom: 20px;">Our Values</h2>
        <p style="text-align: center; color: #666; max-width: 700px; margin: 0 auto 40px;">These are the principles we operate by at TechInHausa.</p>
        
        <div class="values-grid">
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <h3>Love for Learning</h3>
                <p>We love education and learning, and we want to share this love with our community.</p>
            </div>
            
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-hand-holding-heart"></i>
                </div>
                <h3>Accessibility</h3>
                <p>We provide our lessons for free to ensure that everyone can access education without any barriers.</p>
            </div>
            
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3>Quality</h3>
                <p>We provide quality education that will help learners understand and apply what they learn.</p>
            </div>
            
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>Community</h3>
                <p>We are building a strong community of learners who can help each other and grow together.</p>
            </div>
        </div>
    </div>
</section>

<!-- Team Section (if you have team members) -->
<?php if (!empty($teamMembers)): ?>
<section class="team-section">
    <div class="about-container">
        <h2 style="text-align: center; font-size: 2.5rem; color: #031837; margin-bottom: 20px;">Our Team</h2>
        <p style="text-align: center; color: #666; max-width: 700px; margin: 0 auto 40px;">The talented people working to bring you tech education in Hausa.</p>
        
        <div class="team-grid">
            <?php foreach ($teamMembers as $member): ?>
            <div class="team-card">
                <div class="team-image">
                    <img src="<?= getImageUrl($member['image'] ?? '', 'team') ?>" alt="<?= $member['name'] ?>">
                </div>
                <div class="team-info">
                    <h3><?= $member['name'] ?></h3>
                    <div class="team-position"><?= $member['position'] ?></div>
                    <p class="team-bio"><?= truncateText($member['bio'] ?? '', 100) ?></p>
                    <div class="team-social">
                        <?php if (!empty($member['twitter'])): ?>
                        <a href="<?= $member['twitter'] ?>" target="_blank"><i class="fab fa-twitter"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($member['linkedin'])): ?>
                        <a href="<?= $member['linkedin'] ?>" target="_blank"><i class="fab fa-linkedin-in"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($member['github'])): ?>
                        <a href="<?= $member['github'] ?>" target="_blank"><i class="fab fa-github"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- CTA Section -->
<section class="about-cta">
    <div class="about-container">
        <h2>Join Our Community</h2>
        <p>Become one of the thousands of people learning from us every day.</p>
        <div class="cta-buttons">
            <a href="<?= SITE_URL ?>/videos/" class="cta-btn cta-primary">
                <i class="fas fa-play-circle"></i> Watch Videos
            </a>
            <a href="<?= SITE_URL ?>/contact.php" class="cta-btn cta-secondary">
                <i class="fas fa-envelope"></i> Contact Us
            </a>
        </div>
    </div>
</section>

<?php include __DIR__ . "/partials/footer.php"; ?>