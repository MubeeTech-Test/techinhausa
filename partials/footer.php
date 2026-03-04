<?php
// partials/footer.php
// Get BASE_URL from config for dynamic URL resolution
require_once __DIR__ . "/../includes/config.php";
$BASE_URL = SITE_URL; // Use dynamic URL from config
?>
<!-- Footer -->
<footer class="main-footer">
    <div class="footer-container">

        <div class="footer-about">
            <!-- Stylish Logo Link -->
            <a href="<?= $BASE_URL ?>/index.php" class="footer-logo-link">
                <div class="footer-logo-card">
                    <img src="<?= $BASE_URL ?>/assets/images/techinhausa-logo.jpg" alt="TechInHausa Logo" class="footer-logo-img">
                    <div class="footer-logo-glow"></div>
                    <div class="footer-logo-corner corner-tl"></div>
                    <div class="footer-logo-corner corner-tr"></div>
                    <div class="footer-logo-corner corner-bl"></div>
                    <div class="footer-logo-corner corner-br"></div>
                </div>
            </a>
            
            <p>
                TechInHausa brings you the latest technology and AI news in the Hausa language. 
                We are the source of modern tech education for the Hausa community. We teach programming, 
                AI, and other tech fields in an easy-to-understand way.
            </p>
        </div>

        <div class="footer-links">
            <h4>Quick Links</h4>
            <ul>
                <li><a href="<?= $BASE_URL ?>/index.php"><i class="fas fa-chevron-right"></i> Home</a></li>
                <li><a href="<?= $BASE_URL ?>/about.php"><i class="fas fa-chevron-right"></i> About Us</a></li>
                <li><a href="<?= $BASE_URL ?>/videos/"><i class="fas fa-chevron-right"></i> Videos</a></li>
                <li><a href="<?= $BASE_URL ?>/blog/"><i class="fas fa-chevron-right"></i> Blog</a></li>
                <li><a href="<?= $BASE_URL ?>/news/"><i class="fas fa-chevron-right"></i> News</a></li>
                <li><a href="<?= $BASE_URL ?>/research/"><i class="fas fa-chevron-right"></i> Research</a></li>
                <li><a href="<?= $BASE_URL ?>/creator/"><i class="fas fa-chevron-right"></i> MalamIromba</a></li>
                <li><a href="<?= $BASE_URL ?>/contact.php"><i class="fas fa-chevron-right"></i> Contact Us</a></li>
            </ul>
        </div>

        <!-- Social Media Links -->
        <div class="footer-social">
            <h4>Follow Us</h4>
            <ul class="social-list">
                <li>
                    <a href="https://www.youtube.com/@techinHausa" target="_blank">
                        <i class="fab fa-youtube"></i> YouTube
                    </a>
                </li>
                <li>
                    <a href="https://www.facebook.com/techinHausa" target="_blank">
                        <i class="fab fa-facebook"></i> Facebook
                    </a>
                </li>
                <li>
                    <a href="https://www.instagram.com/techinHausa" target="_blank">
                        <i class="fab fa-instagram"></i> Instagram
                    </a>
                </li>
                <li>
                    <a href="https://twitter.com/techinHausa" target="_blank">
                        <i class="fab fa-twitter"></i> Twitter (X)
                    </a>
                </li>
                <li>
                    <a href="https://t.me/techinHausa" target="_blank">
                        <i class="fab fa-telegram"></i> Telegram
                    </a>
                </li>
                <li>
                    <a href="https://github.com/techinHausa" target="_blank">
                        <i class="fab fa-github"></i> GitHub
                    </a>
                </li>
                <li>
                    <a href="mailto:info@techinhausa.com.ng">
                        <i class="fas fa-envelope"></i> info@techinhausa.com.ng
                    </a>
                </li>
                <li>
                    <a href="tel:+2348000000000">
                        <i class="fas fa-phone"></i> +234 800 000 0000
                    </a>
                </li>
            </ul>
        </div>

    </div>

    <div class="footer-bottom">
        <p>
            &copy; <?php echo date('Y'); ?> TechInHausa. All rights reserved.
            <br>
            Developed by
            <a
                href="https://mubeetech.com.ng"
                target="_blank"
                rel="noopener noreferrer"
                class="footer-imprint"
            >
                MubeeTech
            </a>
        </p>
    </div>
</footer>

<style>
/* Footer Logo Styles - Matching Header Design */
.footer-logo-link {
    display: inline-block;
    text-decoration: none;
    margin-bottom: 20px;
}

.footer-logo-card {
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
}

.footer-logo-card:hover {
    transform: translateY(-4px);
    border-color: #D3C9FE;
    box-shadow: 0 15px 30px -5px rgba(211, 201, 254, 0.3);
}

.footer-logo-img {
    max-width: 100%;
    max-height: 60px;
    width: auto;
    height: auto;
    object-fit: contain;
    position: relative;
    z-index: 2;
    filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.3));
}

/* Footer Logo Glow Effect */
.footer-logo-glow {
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

.footer-logo-card:hover .footer-logo-glow {
    opacity: 1;
}

/* Footer Logo Corner Accents */
.footer-logo-corner {
    position: absolute;
    width: 10px;
    height: 10px;
    border: 2px solid transparent;
    z-index: 3;
    opacity: 0.5;
    transition: all 0.3s;
}

.footer-logo-card:hover .footer-logo-corner {
    opacity: 1;
    border-color: #D3C9FE;
}

.footer-logo-corner.corner-tl {
    top: -3px;
    left: -3px;
    border-top: 2px solid #D3C9FE;
    border-left: 2px solid #D3C9FE;
    border-radius: 12px 0 0 0;
}

.footer-logo-corner.corner-tr {
    top: -3px;
    right: -3px;
    border-top: 2px solid #D3C9FE;
    border-right: 2px solid #D3C9FE;
    border-radius: 0 12px 0 0;
}

.footer-logo-corner.corner-bl {
    bottom: -3px;
    left: -3px;
    border-bottom: 2px solid #D3C9FE;
    border-left: 2px solid #D3C9FE;
    border-radius: 0 0 0 12px;
}

.footer-logo-corner.corner-br {
    bottom: -3px;
    right: -3px;
    border-bottom: 2px solid #D3C9FE;
    border-right: 2px solid #D3C9FE;
    border-radius: 0 0 12px 0;
}

/* Footer imprint link (Developer credit) */
.footer-imprint {
    color: var(--lavender);          /* Soft lavender */
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    position: relative;
    display: inline-block;
}

.footer-imprint:hover {
    color: var(--lavender-dark);     /* Darker lavender on hover */
    transform: translateY(-2px);
    text-shadow: 0 2px 10px rgba(211, 201, 254, 0.3);
}

/* Footer specific styles to match TechInHausa theme */
.main-footer {
    background-color: var(--navy-dark);
    color: var(--white);
    padding: 60px 10% 20px;
    border-top: 1px solid rgba(211, 201, 254, 0.1);
    position: relative;
    overflow: hidden;
}

.main-footer::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--lavender), var(--navy), var(--lavender));
    opacity: 0.3;
}

.footer-container {
    display: flex;
    justify-content: space-between;
    gap: 50px;
    flex-wrap: wrap;
    align-items: flex-start;
    margin-bottom: 40px;
    position: relative;
    z-index: 2;
}

.footer-about {
    max-width: 350px;
}

.footer-about p {
    color: rgba(255, 255, 255, 0.7);
    line-height: 1.8;
    margin-bottom: 25px;
    font-size: 0.95rem;
}

.footer-links h4,
.footer-social h4 {
    margin-bottom: 25px;
    color: var(--lavender);
    font-size: 1.2rem;
    font-weight: 600;
    position: relative;
    padding-bottom: 10px;
}

.footer-links h4::after,
.footer-social h4::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 40px;
    height: 2px;
    background: var(--lavender);
    border-radius: 2px;
}

.footer-links ul,
.social-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-links li {
    margin-bottom: 12px;
}

.footer-links a {
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    font-size: 0.95rem;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.footer-links a:hover {
    color: var(--lavender);
    transform: translateX(8px);
}

.footer-links i {
    color: var(--lavender);
    font-size: 0.8rem;
    transition: transform 0.3s;
}

.footer-links a:hover i {
    transform: translateX(3px);
}

/* Social Links */
.social-list li {
    margin-bottom: 15px;
}

.social-list a {
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 12px;
    transition: all 0.3s;
}

.social-list i {
    font-size: 1.4rem;
    color: var(--lavender);
    transition: all 0.3s;
    width: 24px;
    text-align: center;
}

.social-list a:hover {
    color: var(--lavender);
    transform: translateX(5px);
}

.social-list a:hover i {
    color: var(--lavender-dark);
    transform: scale(1.1);
}

/* Footer Bottom */
.footer-bottom {
    text-align: center;
    border-top: 1px solid rgba(211, 201, 254, 0.1);
    padding-top: 25px;
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.5);
    position: relative;
    z-index: 2;
}

.footer-bottom p {
    line-height: 1.8;
}

.footer-bottom i {
    animation: heartbeat 1.5s ease infinite;
}

@keyframes heartbeat {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

/* Responsive Footer */
@media (max-width: 768px) {
    .main-footer {
        padding: 50px 6% 20px;
    }

    .footer-container {
        flex-direction: column;
        gap: 40px;
    }

    .footer-about {
        max-width: 100%;
    }

    .footer-links h4::after,
    .footer-social h4::after {
        width: 60px;
    }
    
    .footer-logo-card {
        width: 160px;
        height: 60px;
    }
    
    .footer-logo-img {
        max-height: 50px;
    }
}

@media (max-width: 480px) {
    .footer-logo-card {
        width: 140px;
        height: 55px;
    }
    
    .footer-logo-img {
        max-height: 45px;
    }
    
    .footer-links a,
    .social-list a {
        font-size: 0.9rem;
    }

    .social-list i {
        font-size: 1.2rem;
    }

    .footer-bottom {
        font-size: 0.8rem;
    }
}

/* Optional: Add a subtle tech pattern background */
.main-footer::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image: 
        radial-gradient(circle at 10% 20%, rgba(211, 201, 254, 0.03) 0%, transparent 20%),
        radial-gradient(circle at 90% 80%, rgba(211, 201, 254, 0.03) 0%, transparent 20%);
    pointer-events: none;
    z-index: 1;
}
</style>

</body>
</html>