<?php
// contact.php - TechInHausa Contact Page
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = "Contact Us - TechInHausa";
$pageDesc = "Contact us for questions, suggestions, or collaboration. We'd love to hear your feedback.";

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message'] ?? '');
    
    // Validate required fields
    $errors = [];
    if (empty($name)) $errors[] = "Your name is required";
    if (empty($email)) $errors[] = "Your email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Your email is not valid";
    if (empty($subject)) $errors[] = "Message subject is required";
    if (empty($message)) $errors[] = "Message is required";
    
    if (empty($errors)) {
        // Get IP and user agent
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Insert into database
        $sql = "INSERT INTO contact_submissions (name, email, subject, message, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssss", $name, $email, $subject, $message, $ip_address, $user_agent);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = "Thank you! Your message has been sent. We will respond shortly.";
            
            // Optional: Send email notification to admin
            // mail_to_admin($subject, $message, $email, $name);
        } else {
            $error = "An error occurred. Please try again.";
            error_log("Contact form error: " . mysqli_error($conn));
        }
        mysqli_stmt_close($stmt);
    } else {
        $error = implode("<br>", $errors);
    }
}

include 'partials/header.php';
?>

<section class="contact-page-section">
    <div class="container">
        <div class="contact-header">
            <h1 class="page-title">Contact Us</h1>
            <p class="page-subtitle">We'd love to hear your feedback and questions</p>
        </div>
        
        <div class="contact-grid">
            <!-- Contact Information -->
            <div class="contact-info-card">
                <h2>Contact Information</h2>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="info-content">
                        <h3>Our Address</h3>
                        <p>No. 123, Tech Avenue,<br>Kano, Nigeria</p>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                    <div class="info-content">
                        <h3>Our Phone</h3>
                        <p>+234 800 000 0000</p>
                        <p>+234 800 000 0001</p>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="info-content">
                        <h3>Our Email</h3>
                        <p>info@techinhausa.com.ng</p>
                        <p>support@techinhausa.com.ng</p>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="info-content">
                        <h3>Working Hours</h3>
                        <p>Monday - Friday: 9:00 - 18:00</p>
                        <p>Saturday: 10:00 - 14:00</p>
                        <p>Sunday: Closed</p>
                    </div>
                </div>
                
                <div class="social-links-contact">
                    <h3>Follow Us on Social Media</h3>
                    <div class="social-icons">
                        <a href="https://facebook.com/techinhausa" target="_blank" class="social-icon facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://twitter.com/techinhausa" target="_blank" class="social-icon twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="https://instagram.com/techinhausa" target="_blank" class="social-icon instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="https://youtube.com/@techinhausa" target="_blank" class="social-icon youtube">
                            <i class="fab fa-youtube"></i>
                        </a>
                        <a href="https://linkedin.com/company/techinhausa" target="_blank" class="social-icon linkedin">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="https://t.me/techinhausa" target="_blank" class="social-icon telegram">
                            <i class="fab fa-telegram-plane"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Contact Form -->
            <div class="contact-form-card">
                <h2>Send Us a Message</h2>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?= $success ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="contact-form">
                    <div class="form-group">
                        <label for="name">
                            <i class="fas fa-user"></i> Your Name <span class="required">*</span>
                        </label>
                        <input type="text" id="name" name="name" 
                               value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>"
                               placeholder="Enter your name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i> Your Email <span class="required">*</span>
                        </label>
                        <input type="email" id="email" name="email" 
                               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                               placeholder="example@domain.com" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">
                            <i class="fas fa-heading"></i> Message Subject <span class="required">*</span>
                        </label>
                        <input type="text" id="subject" name="subject" 
                               value="<?= isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : '' ?>"
                               placeholder="What would you like to tell us?" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">
                            <i class="fas fa-comment"></i> Your Message <span class="required">*</span>
                        </label>
                        <textarea id="message" name="message" rows="6" 
                                  placeholder="Write your message here..." required><?= isset($_POST['message']) ? htmlspecialchars($_POST['message']) : '' ?></textarea>
                    </div>
                    
                    <div class="form-group form-check">
                        <input type="checkbox" id="consent" name="consent" required>
                        <label for="consent">
                            I agree to be contacted via email or phone regarding this message
                        </label>
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<style>
/* Contact Page Styles */
.contact-page-section {
    padding: 60px 0;
    background: linear-gradient(135deg, #f5f7fb 0%, #ffffff 100%);
}

.contact-header {
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

.contact-grid {
    display: grid;
    grid-template-columns: 1fr 1.5fr;
    gap: 40px;
    max-width: 1200px;
    margin: 0 auto;
}

/* Contact Info Card */
.contact-info-card {
    background: white;
    border-radius: 20px;
    padding: 40px 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border: 1px solid rgba(211, 201, 254, 0.3);
}

.contact-info-card h2 {
    color: #031837;
    font-size: 1.8rem;
    margin-bottom: 30px;
    position: relative;
    padding-bottom: 15px;
}

.contact-info-card h2::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 60px;
    height: 3px;
    background: #D3C9FE;
}

.info-item {
    display: flex;
    gap: 20px;
    margin-bottom: 30px;
    padding: 15px;
    border-radius: 12px;
    transition: all 0.3s;
}

.info-item:hover {
    background: rgba(211, 201, 254, 0.1);
    transform: translateY(-2px);
}

.info-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #031837, #0a2a4a);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.info-icon i {
    font-size: 1.5rem;
    color: #D3C9FE;
}

.info-content {
    flex: 1;
}

.info-content h3 {
    color: #031837;
    font-size: 1.1rem;
    margin-bottom: 8px;
}

.info-content p {
    color: #666;
    line-height: 1.6;
    margin-bottom: 5px;
}

/* Social Links */
.social-links-contact {
    margin-top: 40px;
    padding-top: 30px;
    border-top: 1px solid rgba(211, 201, 254, 0.3);
}

.social-links-contact h3 {
    color: #031837;
    font-size: 1.2rem;
    margin-bottom: 20px;
}

.social-icons {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.social-icon {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.3s;
    font-size: 1.2rem;
}

.social-icon.facebook {
    background: #1877f2;
    color: white;
}

.social-icon.twitter {
    background: #1da1f2;
    color: white;
}

.social-icon.instagram {
    background: linear-gradient(45deg, #f09433, #d62976, #962fbf);
    color: white;
}

.social-icon.youtube {
    background: #ff0000;
    color: white;
}

.social-icon.linkedin {
    background: #0077b5;
    color: white;
}

.social-icon.telegram {
    background: #0088cc;
    color: white;
}

.social-icon:hover {
    transform: translateY(-5px) scale(1.1);
    box-shadow: 0 10px 20px rgba(0,0,0,0.2);
}

/* Contact Form Card */
.contact-form-card {
    background: white;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border: 1px solid rgba(211, 201, 254, 0.3);
}

.contact-form-card h2 {
    color: #031837;
    font-size: 1.8rem;
    margin-bottom: 30px;
    position: relative;
    padding-bottom: 15px;
}

.contact-form-card h2::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 60px;
    height: 3px;
    background: #D3C9FE;
}

/* Form Styles */
.contact-form .form-group {
    margin-bottom: 25px;
}

.contact-form label {
    display: block;
    margin-bottom: 8px;
    color: #333;
    font-weight: 500;
}

.contact-form label i {
    color: #D3C9FE;
    margin-right: 5px;
}

.contact-form .required {
    color: #ff4444;
    margin-left: 3px;
}

.contact-form input,
.contact-form textarea {
    width: 100%;
    padding: 15px;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    font-size: 1rem;
    transition: all 0.3s;
    font-family: 'Inter', sans-serif;
}

.contact-form input:focus,
.contact-form textarea:focus {
    outline: none;
    border-color: #D3C9FE;
    box-shadow: 0 0 0 4px rgba(211, 201, 254, 0.2);
}

.contact-form input::placeholder,
.contact-form textarea::placeholder {
    color: #999;
}

.form-check {
    display: flex;
    align-items: flex-start;
    gap: 10px;
}

.form-check input[type="checkbox"] {
    width: 20px;
    height: 20px;
    margin-top: 3px;
    accent-color: #031837;
}

.form-check label {
    flex: 1;
    font-size: 0.95rem;
    color: #666;
}

.submit-btn {
    width: 100%;
    padding: 16px;
    background: linear-gradient(135deg, #031837, #0a2a4a);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.submit-btn:hover {
    background: linear-gradient(135deg, #0a2a4a, #031837);
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(3, 24, 55, 0.3);
}

.submit-btn i {
    color: #D3C9FE;
}

/* Alert Messages */
.alert {
    padding: 15px 20px;
    border-radius: 10px;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-success {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.alert-error {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.alert i {
    font-size: 1.2rem;
}

/* Responsive */
@media (max-width: 992px) {
    .contact-grid {
        grid-template-columns: 1fr;
        gap: 30px;
    }
    
    .contact-info-card,
    .contact-form-card {
        padding: 30px;
    }
}

@media (max-width: 768px) {
    .contact-page-section {
        padding: 40px 0;
    }
    
    .page-title {
        font-size: 2rem;
    }
    
    .info-item {
        flex-direction: column;
        text-align: center;
    }
    
    .info-icon {
        margin: 0 auto;
    }
    
    .social-icons {
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .contact-info-card h2,
    .contact-form-card h2 {
        font-size: 1.5rem;
    }
    
    .submit-btn {
        padding: 14px;
        font-size: 1rem;
    }
    
    .form-check {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<?php include 'partials/footer.php'; ?>