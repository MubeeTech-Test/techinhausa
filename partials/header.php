<?php
// partials/header.php
// Get BASE_URL from config for dynamic URL resolution
require_once __DIR__ . "/../includes/config.php";
$BASE_URL = SITE_URL; // Use dynamic URL from config
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechInHausa – Technology & AI in Hausa Language</title>

    <link rel="stylesheet" href="<?= $BASE_URL ?>/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    
    <style>
        /* ===== PREMIUM DARK THEME HEADER ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fb;
        }

        /* Premium Header Container */
        .premium-header {
            background-color: #031837;
            background-image: 
                repeating-linear-gradient(
                    45deg,
                    rgba(211, 201, 254, 0.02) 0px,
                    rgba(211, 201, 254, 0.02) 2px,
                    transparent 2px,
                    transparent 8px
                );
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5);
        }

        /* Animated glow orbs */
        .premium-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 80%;
            height: 200%;
            background: radial-gradient(circle, rgba(211, 201, 254, 0.1) 0%, transparent 70%);
            animation: orbFloat 15s ease-in-out infinite;
            pointer-events: none;
        }

        .premium-header::after {
            content: '';
            position: absolute;
            bottom: -50%;
            left: -20%;
            width: 80%;
            height: 200%;
            background: radial-gradient(circle, rgba(211, 201, 254, 0.08) 0%, transparent 70%);
            animation: orbFloat 20s ease-in-out infinite reverse;
            pointer-events: none;
        }

        @keyframes orbFloat {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(5%, 5%) scale(1.1); }
        }

        .premium-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px 15px 30px;
            position: relative;
            z-index: 2;
        }

        /* Top Navigation Bar */
        .premium-navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 50px;
        }

        /* Left side empty for balance */
        .navbar-left {
            width: 45px;
        }

        /* Stylish Centered Logo */
        .premium-logo {
            position: relative;
            flex: 0 1 auto;
        }

        .premium-logo a {
            display: block;
            text-decoration: none;
        }

        .logo-card {
            position: relative;
            width: 180px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(211, 201, 254, 0.2);
            border-radius: 16px;
            overflow: visible;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            padding: 3px;
            backdrop-filter: blur(5px);
            box-shadow: 0 10px 20px -10px rgba(0, 0, 0, 0.5);
            margin: 0 auto;
        }

        .logo-card:hover {
            transform: translateY(-4px);
            border-color: #D3C9FE;
            box-shadow: 0 15px 30px -5px rgba(211, 201, 254, 0.3);
        }

        .logo-card img {
            max-width: 100%;
            max-height: 60px;
            width: auto;
            height: auto;
            object-fit: contain;
            position: relative;
            z-index: 2;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.3));
        }

        /* Logo Glow Effect */
        .logo-glow {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 30% 50%, rgba(211, 201, 254, 0.2) 0%, transparent 70%);
            border-radius: 16px;
            z-index: 1;
            opacity: 0;
            transition: opacity 0.4s;
        }

        .logo-card:hover .logo-glow {
            opacity: 1;
        }

        /* Corner Accents */
        .logo-corner {
            position: absolute;
            width: 10px;
            height: 10px;
            border: 2px solid transparent;
            z-index: 3;
            opacity: 0.5;
            transition: all 0.3s;
        }

        .logo-card:hover .logo-corner {
            opacity: 1;
            border-color: #D3C9FE;
        }

        .corner-tl {
            top: -3px;
            left: -3px;
            border-top: 2px solid #D3C9FE;
            border-left: 2px solid #D3C9FE;
            border-radius: 12px 0 0 0;
        }

        .corner-tr {
            top: -3px;
            right: -3px;
            border-top: 2px solid #D3C9FE;
            border-right: 2px solid #D3C9FE;
            border-radius: 0 12px 0 0;
        }

        .corner-bl {
            bottom: -3px;
            left: -3px;
            border-bottom: 2px solid #D3C9FE;
            border-left: 2px solid #D3C9FE;
            border-radius: 0 0 0 12px;
        }

        .corner-br {
            bottom: -3px;
            right: -3px;
            border-bottom: 2px solid #D3C9FE;
            border-right: 2px solid #D3C9FE;
            border-radius: 0 0 12px 0;
        }

        /* Right Side Actions */
        .premium-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .premium-search {
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(211, 201, 254, 0.2);
            border-radius: 12px;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s;
        }

        .premium-search:hover {
            background: rgba(211, 201, 254, 0.1);
            border-color: #D3C9FE;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(211, 201, 254, 0.2);
        }

        .premium-search i {
            color: #D3C9FE;
        }

        .premium-cta {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 25px;
            background: linear-gradient(135deg, #D3C9FE, #b8a9fe);
            border-radius: 12px;
            color: #031837;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(5px);
        }

        .premium-cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(211, 201, 254, 0.4);
        }

        .premium-cta i {
            color: #031837;
        }

        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            width: 45px;
            height: 45px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(211, 201, 254, 0.2);
            border-radius: 12px;
            color: white;
            font-size: 1.3rem;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .mobile-menu-toggle:hover {
            background: rgba(211, 201, 254, 0.1);
            border-color: #D3C9FE;
        }

        /* Hero Content */
        .premium-hero {
            text-align: center;
            margin-bottom: 0px;
        }

        /* Rounded Glowing Badge */
        .hero-badge {
            display: inline-block;
            padding: 8px 20px;
            background: rgba(211, 201, 254, 0.1);
            border: 1px solid rgba(211, 201, 254, 0.3);
            border-radius: 50px;
            color: #D3C9FE;
            font-weight: 600;
            font-size: 0.9rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 20px;
            backdrop-filter: blur(5px);
            box-shadow: 0 0 20px rgba(211, 201, 254, 0.3);
            animation: badgePulse 2s ease-in-out infinite;
        }

        @keyframes badgePulse {
            0%, 100% { box-shadow: 0 0 20px rgba(211, 201, 254, 0.3); }
            50% { box-shadow: 0 0 30px rgba(211, 201, 254, 0.6); }
        }

        /* Gradient Title */
        .hero-title {
            font-size: 4rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #fff 0%, #D3C9FE 50%, #b8a9fe 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 5px 20px rgba(211, 201, 254, 0.3);
            animation: titleFloat 3s ease-in-out infinite;
        }

        @keyframes titleFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        .hero-subtitle {
            color: rgba(255, 255, 255, 0.7);
            font-size: 1.2rem;
            max-width: 600px;
            margin: 0 auto;
            text-align: center;
            line-height: 1.6;
        }

        /* Navigation Cards Section */
        .nav-cards-section {
            margin-top: 20px;
        }

        .nav-cards-grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
            max-width: 1000px;
            margin: 0 auto;
        }

        /* Premium Navigation Cards */
        .nav-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 140px;
            height: 100px;
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(211, 201, 254, 0.15);
            border-radius: 18px;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        /* Card Glow Effect */
        .nav-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(211, 201, 254, 0.1),
                transparent
            );
            transition: left 0.5s;
        }

        .nav-card:hover::before {
            left: 100%;
        }

        /* Card Icon */
        .nav-card i {
            font-size: 2rem;
            color: #D3C9FE;
            margin-bottom: 8px;
            transition: all 0.3s;
            filter: drop-shadow(0 4px 8px rgba(211, 201, 254, 0.3));
        }

        /* Card Label */
        .nav-card span {
            color: white;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        /* Hover Effects */
        .nav-card:hover {
            transform: translateY(-6px);
            border-color: #D3C9FE;
            box-shadow: 
                0 15px 30px -10px rgba(211, 201, 254, 0.3),
                inset 0 1px 1px rgba(255, 255, 255, 0.1);
            background: rgba(211, 201, 254, 0.05);
        }

        .nav-card:hover i {
            transform: scale(1.1);
            color: #fff;
        }

        .nav-card:hover span {
            color: #D3C9FE;
        }

        /* Active Card State */
        .nav-card.active {
            background: rgba(211, 201, 254, 0.1);
            border-color: #D3C9FE;
            box-shadow: 0 10px 25px -5px rgba(211, 201, 254, 0.4);
        }

        .nav-card.active i {
            color: #fff;
        }

        .nav-card.active span {
            color: #D3C9FE;
        }

        /* Search Bar (Hidden by default) */
        .search-bar {
            background: #021c3f;
            padding: 20px;
            display: none;
            border-top: 1px solid rgba(211, 201, 254, 0.15);
            position: relative;
            z-index: 10;
        }

        .search-bar.active {
            display: block;
        }

        .search-bar .container {
            max-width: 600px;
            margin: 0 auto;
        }

        .search-bar form {
            display: flex;
            gap: 10px;
        }

        .search-bar input {
            flex: 1;
            padding: 15px 20px;
            border: 1px solid rgba(211, 201, 254, 0.2);
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .search-bar input:focus {
            outline: none;
            border-color: #D3C9FE;
            box-shadow: 0 0 0 3px rgba(211, 201, 254, 0.1);
        }

        .search-bar button {
            padding: 15px 30px;
            background: linear-gradient(135deg, #D3C9FE, #b8a9fe);
            border: none;
            border-radius: 12px;
            color: #031837;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .search-bar button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(211, 201, 254, 0.3);
        }

        /* Mobile Navigation Menu (Hidden by default) */
        .mobile-nav-menu {
            display: none;
            background: #021c3f;
            border-top: 1px solid rgba(211, 201, 254, 0.15);
            padding: 20px;
        }

        .mobile-nav-menu.active {
            display: block;
        }

        .mobile-nav-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .mobile-nav-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 15px 5px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(211, 201, 254, 0.1);
            border-radius: 14px;
            text-decoration: none;
            transition: all 0.3s;
        }

        .mobile-nav-card i {
            font-size: 1.3rem;
            color: #D3C9FE;
            margin-bottom: 5px;
        }

        .mobile-nav-card span {
            color: white;
            font-size: 0.8rem;
            font-weight: 500;
            text-align: center;
        }

        .mobile-nav-card:hover {
            background: rgba(211, 201, 254, 0.1);
            border-color: #D3C9FE;
            transform: translateY(-2px);
        }

        .mobile-cta {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 15px;
            padding: 12px;
            background: linear-gradient(135deg, #D3C9FE, #b8a9fe);
            border-radius: 12px;
            color: #031837;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .mobile-cta i {
            color: #031837;
        }

        .mobile-cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(211, 201, 254, 0.3);
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .premium-navbar {
                margin-bottom: 30px;
            }

            .hero-title {
                font-size: 3rem;
            }

            .premium-cta {
                display: none;
            }

            .mobile-menu-toggle {
                display: flex;
            }

            .navbar-left {
                width: 45px;
            }

            .nav-cards-section {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .premium-container {
                padding: 20px 15px 40px;
            }

            .hero-title {
                font-size: 2.5rem;
            }

            .hero-subtitle {
                font-size: 1rem;
            }

            .hero-badge {
                font-size: 0.8rem;
                padding: 6px 15px;
            }

            .logo-card {
                width: 160px;
                height: 60px;
            }

            .logo-card img {
                max-height: 50px;
            }

            .mobile-nav-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 576px) {
            .hero-title {
                font-size: 2rem;
            }

            .logo-card {
                width: 140px;
                height: 55px;
            }

            .logo-card img {
                max-height: 45px;
            }

            .navbar-left {
                width: 40px;
            }

            .premium-search {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }

            .mobile-menu-toggle {
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
            }

            .mobile-nav-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 360px) {
            .hero-title {
                font-size: 1.6rem;
            }

            .logo-card {
                width: 120px;
                height: 50px;
            }

            .logo-card img {
                max-height: 40px;
            }

            .navbar-left {
                width: 35px;
            }
        }
    </style>
</head>
<body>

<!-- Premium Dark Theme Header -->
<header class="premium-header">
    <div class="premium-container">
        <!-- Top Navigation Bar -->
        <div class="premium-navbar">
            <!-- Left empty space for balance -->
            <div class="navbar-left"></div>

            <!-- Centered Stylish Logo -->
            <div class="premium-logo">
                <a href="<?= $BASE_URL ?>/index.php">
                    <div class="logo-card">
                        <img src="<?= $BASE_URL ?>/assets/images/techinhausa-logo.jpg" alt="TechInHausa Logo">
                        <div class="logo-glow"></div>
                        <div class="logo-corner corner-tl"></div>
                        <div class="logo-corner corner-tr"></div>
                        <div class="logo-corner corner-bl"></div>
                        <div class="logo-corner corner-br"></div>
                    </div>
                </a>
            </div>

            <!-- Right Side Actions -->
            <div class="premium-actions">
                <button class="premium-search" id="searchToggle" aria-label="Search">
                    <i class="fas fa-search"></i>
                </button>
                <div class="mobile-menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </div>
            </div>
        </div>

        <!-- Hero Content -->
        <div class="premium-hero">
            <p class="hero-subtitle">
                Learn modern technology and Artificial Intelligence in the Hausa language
            </p>
        </div>

        <!-- Navigation Cards Section (Desktop Only) -->
        <div class="nav-cards-section">
            <div class="nav-cards-grid">
                <a href="<?= $BASE_URL ?>/index.php" class="nav-card <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
                <a href="<?= $BASE_URL ?>/videos.php" class="nav-card <?= strpos($_SERVER['PHP_SELF'], 'videos') !== false ? 'active' : '' ?>">
                    <i class="fas fa-play-circle"></i>
                    <span>Videos</span>
                </a>
                <a href="<?= $BASE_URL ?>/news.php" class="nav-card <?= strpos($_SERVER['PHP_SELF'], 'news') !== false ? 'active' : '' ?>">
                    <i class="fas fa-newspaper"></i>
                    <span>News</span>
                </a>
                <a href="<?= $BASE_URL ?>/research.php" class="nav-card <?= strpos($_SERVER['PHP_SELF'], 'research') !== false ? 'active' : '' ?>">
                    <i class="fas fa-flask"></i>
                    <span>Research</span>
                </a>
                <a href="<?= $BASE_URL ?>/creator.php" class="nav-card <?= strpos($_SERVER['PHP_SELF'], 'creator') !== false ? 'active' : '' ?>">
                    <i class="fas fa-user-graduate"></i>
                    <span>MalamIromba</span>
                </a>
                <a href="<?= $BASE_URL ?>/blog.php" class="nav-card <?= strpos($_SERVER['PHP_SELF'], 'blog') !== false ? 'active' : '' ?>">
                    <i class="fas fa-blog"></i>
                    <span>Blog</span>
                </a>
                <a href="<?= $BASE_URL ?>/contact.php" class="nav-card <?= basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : '' ?>">
                    <i class="fas fa-envelope"></i>
                    <span>Contact</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation Menu (Hidden by default, appears when toggle is clicked) -->
    <div class="mobile-nav-menu" id="mobileNavMenu">
        <div class="mobile-nav-grid">
            <a href="<?= $BASE_URL ?>/index.php" class="mobile-nav-card">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
            <a href="<?= $BASE_URL ?>/videos.php" class="mobile-nav-card">
                <i class="fas fa-play-circle"></i>
                <span>Videos</span>
            </a>
            <a href="<?= $BASE_URL ?>/news.php" class="mobile-nav-card">
                <i class="fas fa-newspaper"></i>
                <span>News</span>
            </a>
            <a href="<?= $BASE_URL ?>/research.php" class="mobile-nav-card">
                <i class="fas fa-flask"></i>
                <span>Research</span>
            </a>
            <a href="<?= $BASE_URL ?>/creator.php" class="mobile-nav-card">
                <i class="fas fa-user-graduate"></i>
                <span>MalamIromba</span>
            </a>
            <a href="<?= $BASE_URL ?>/blog.php" class="mobile-nav-card">
                <i class="fas fa-blog"></i>
                <span>Blog</span>
            </a>
            <a href="<?= $BASE_URL ?>/contact.php" class="mobile-nav-card">
                <i class="fas fa-envelope"></i>
                <span>Contact</span>
            </a>
        </div>
        <a href="<?= $BASE_URL ?>/contact.php" class="mobile-cta">
            <i class="fas fa-paper-plane"></i> Contact Us
        </a>
    </div>

    <!-- Search Bar (Hidden by default) -->
    <div class="search-bar" id="searchBar">
        <div class="container">
            <form action="<?= $BASE_URL ?>/search.php" method="GET">
                <input type="text" name="q" placeholder="Search videos, blog, news..." autocomplete="off">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </div>
</header>

<!-- JavaScript for Interactivity -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menuToggle');
    const mobileNav = document.getElementById('mobileNavMenu');
    const searchToggle = document.getElementById('searchToggle');
    const searchBar = document.getElementById('searchBar');

    // Mobile menu toggle
    if (menuToggle && mobileNav) {
        menuToggle.addEventListener('click', function() {
            mobileNav.classList.toggle('active');
            const icon = this.querySelector('i');
            if (mobileNav.classList.contains('active')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });
    }

    // Search toggle
    if (searchToggle && searchBar) {
        searchToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            searchBar.classList.toggle('active');
            if (searchBar.classList.contains('active')) {
                searchBar.querySelector('input').focus();
            }
        });

        // Close search when clicking outside
        document.addEventListener('click', function(event) {
            if (!searchToggle.contains(event.target) && !searchBar.contains(event.target)) {
                searchBar.classList.remove('active');
            }
        });
    }

    // Close mobile menu when clicking outside
    document.addEventListener('click', function(event) {
        if (mobileNav && mobileNav.classList.contains('active')) {
            if (!mobileNav.contains(event.target) && !menuToggle.contains(event.target)) {
                mobileNav.classList.remove('active');
                menuToggle.querySelector('i').classList.remove('fa-times');
                menuToggle.querySelector('i').classList.add('fa-bars');
            }
        }
    });

    // Prevent menu from closing when clicking inside
    if (mobileNav) {
        mobileNav.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
});
</script>