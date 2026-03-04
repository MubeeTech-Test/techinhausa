<?php
// admin/profile.php - Manage Admin Profile (User Account)
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";

// Require admin login
require_admin_login();

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

$message = '';
$error = '';

// Get current admin info
$current_user = getCurrentUser();

if (!$current_user || !isset($current_user['id'])) {
    die("User not found. Please log in again.");
}

$user_id = (int)$current_user['id']; // Cast to integer for safety

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $full_name = mysqli_real_escape_string($conn, trim($_POST['full_name']));
        $email = mysqli_real_escape_string($conn, trim($_POST['email']));
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } else {
            $update = "UPDATE users SET full_name = '$full_name', email = '$email' WHERE id = $user_id";
            
            if (mysqli_query($conn, $update)) {
                $_SESSION['admin_name'] = $full_name;
                $message = "Profile updated successfully.";
            } else {
                $error = "Error updating profile: " . mysqli_error($conn);
            }
        }
    }
    
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        $result = mysqli_query($conn, "SELECT password_hash FROM users WHERE id = $user_id");
        
        if (!$result) {
            $error = "Database error: " . mysqli_error($conn);
        } else {
            $user = mysqli_fetch_assoc($result);
            
            if (password_verify($current_password, $user['password_hash'])) {
                if ($new_password === $confirm_password) {
                    // Password strength validation
                    if (strlen($new_password) < 6) {
                        $error = "Password must be at least 6 characters long.";
                    } else {
                        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $update = "UPDATE users SET password_hash = '$password_hash' WHERE id = $user_id";
                        
                        if (mysqli_query($conn, $update)) {
                            $message = "Password changed successfully.";
                        } else {
                            $error = "Error changing password: " . mysqli_error($conn);
                        }
                    }
                } else {
                    $error = "New passwords do not match.";
                }
            } else {
                $error = "Current password is incorrect.";
            }
        }
    }
}

// Get user data
$query = "SELECT username, full_name, email, created_at, last_login FROM users WHERE id = $user_id";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Error fetching user data: " . mysqli_error($conn));
}

if (mysqli_num_rows($result) === 0) {
    die("User not found in database.");
}

$user = mysqli_fetch_assoc($result);
?>

<?php include __DIR__ . "/../partials/header.php"; ?>

<div class="admin-wrapper">
    <?php include __DIR__ . "/sidebar.php"; ?>
    
    <main class="admin-content">
        <div class="container-fluid">
            <h1 class="admin-title">My Profile</h1>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-user"></i> Profile Information</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" onsubmit="return validateProfileForm()">
                                <div class="form-group">
                                    <label>Username</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" readonly disabled>
                                </div>
                                
                                <div class="form-group">
                                    <label>Full Name <span class="text-danger">*</span></label>
                                    <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Email Address <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Member Since</label>
                                    <input type="text" class="form-control" value="<?php echo $user['created_at'] ? date('F j, Y', strtotime($user['created_at'])) : 'N/A'; ?>" readonly disabled>
                                </div>
                                
                                <div class="form-group">
                                    <label>Last Login</label>
                                    <input type="text" class="form-control" value="<?php echo $user['last_login'] ? date('F j, Y g:i a', strtotime($user['last_login'])) : 'Never'; ?>" readonly disabled>
                                </div>
                                
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Profile
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-key"></i> Change Password</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" onsubmit="return validatePasswordForm()">
                                <div class="form-group">
                                    <label>Current Password <span class="text-danger">*</span></label>
                                    <input type="password" name="current_password" id="current_password" class="form-control" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>New Password <span class="text-danger">*</span></label>
                                    <input type="password" name="new_password" id="new_password" class="form-control" required>
                                    <small class="text-muted">Minimum 6 characters</small>
                                </div>
                                
                                <div class="form-group">
                                    <label>Confirm New Password <span class="text-danger">*</span></label>
                                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                                </div>
                                
                                <button type="submit" name="change_password" class="btn btn-warning">
                                    <i class="fas fa-key"></i> Change Password
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Account Security Tips -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h3><i class="fas fa-shield-alt"></i> Security Tips</h3>
                        </div>
                        <div class="card-body">
                            <ul class="security-tips">
                                <li><i class="fas fa-check-circle text-success"></i> Use a strong password with letters, numbers, and symbols</li>
                                <li><i class="fas fa-check-circle text-success"></i> Never share your password with anyone</li>
                                <li><i class="fas fa-check-circle text-success"></i> Change your password regularly</li>
                                <li><i class="fas fa-check-circle text-success"></i> Log out when using shared computers</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<style>
.admin-wrapper {
    display: flex;
    min-height: 100vh;
}

.admin-content {
    flex: 1;
    padding: 30px;
    background: #f8f9fa;
}

.admin-title {
    color: #031837;
    margin-bottom: 30px;
    font-size: 2rem;
    font-weight: 600;
}

.card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    margin-bottom: 30px;
    border: 1px solid rgba(211, 201, 254, 0.2);
    overflow: hidden;
}

.card-header {
    padding: 20px 25px;
    border-bottom: 1px solid #eee;
    background: linear-gradient(to right, #f8f9ff, white);
}

.card-header h3 {
    margin: 0;
    color: #031837;
    font-size: 1.3rem;
    font-weight: 600;
}

.card-header h3 i {
    color: #D3C9FE;
    margin-right: 10px;
}

.card-body {
    padding: 25px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #333;
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #eef2f6;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s;
}

.form-control:focus {
    outline: none;
    border-color: #D3C9FE;
    box-shadow: 0 0 0 3px rgba(211, 201, 254, 0.2);
}

.form-control[readonly] {
    background: #f8f9fa;
    border-color: #eef2f6;
    color: #666;
    cursor: not-allowed;
}

.text-danger {
    color: #dc3545;
}

.text-muted {
    color: #6c757d;
    font-size: 0.85rem;
    margin-top: 5px;
    display: block;
}

.btn {
    padding: 12px 25px;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: #031837;
    color: white;
}

.btn-primary:hover {
    background: #0a2a4a;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(3,24,55,0.2);
}

.btn-warning {
    background: #ffc107;
    color: #000;
}

.btn-warning:hover {
    background: #e0a800;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255,193,7,0.3);
}

.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-weight: 500;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.security-tips {
    list-style: none;
    padding: 0;
    margin: 0;
}

.security-tips li {
    padding: 10px 0;
    border-bottom: 1px solid #eee;
    display: flex;
    align-items: center;
    gap: 10px;
}

.security-tips li:last-child {
    border-bottom: none;
}

.security-tips i {
    font-size: 1.1rem;
}

.row {
    display: flex;
    flex-wrap: wrap;
    gap: 30px;
}

.col-md-6 {
    flex: 1 1 calc(50% - 15px);
    min-width: 300px;
}

@media (max-width: 768px) {
    .admin-wrapper {
        flex-direction: column;
    }
    
    .row {
        flex-direction: column;
    }
    
    .col-md-6 {
        width: 100%;
    }
    
    .admin-content {
        padding: 20px;
    }
}
</style>

<script>
function validateProfileForm() {
    const fullName = document.querySelector('input[name="full_name"]').value.trim();
    const email = document.querySelector('input[name="email"]').value.trim();
    
    if (fullName === '') {
        alert('Please enter your full name.');
        return false;
    }
    
    if (email === '') {
        alert('Please enter your email address.');
        return false;
    }
    
    // Basic email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        alert('Please enter a valid email address.');
        return false;
    }
    
    return true;
}

function validatePasswordForm() {
    const currentPassword = document.getElementById('current_password').value;
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (currentPassword === '') {
        alert('Please enter your current password.');
        return false;
    }
    
    if (newPassword === '') {
        alert('Please enter a new password.');
        return false;
    }
    
    if (newPassword.length < 6) {
        alert('New password must be at least 6 characters long.');
        return false;
    }
    
    if (newPassword !== confirmPassword) {
        alert('New passwords do not match.');
        return false;
    }
    
    return true;
}
</script>

<?php include __DIR__ . "/../partials/footer.php"; ?>