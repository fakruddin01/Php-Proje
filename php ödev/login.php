<?php
/**
 * Login Page
 * Uses $_POST for form submission and $_SESSION for authentication
 */
require_once 'config.php';

// If already logged in, redirect to dashboard
if (verify_login()) {
    redirect('index.php');
}

// Process login form submission using $_POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token()) {
        $_SESSION['error_message'] = "Invalid request. Please try again.";
    } else {
        $username = sanitize_input($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Validate inputs
        if (empty($username) || empty($password)) {
            $_SESSION['error_message'] = "Please fill in all fields";
        } else {
            // Check user credentials in database
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            // Verify password
            if ($user && verify_password($password, $user['password'])) {
                // Set session variables - using $_SESSION
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                $_SESSION['success_message'] = "Welcome back, " . $user['username'] . "!";
                redirect('index.php');
            } else {
                $_SESSION['error_message'] = "Invalid username or password";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Event Management System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-box">
            <h1>🎫 Event Management</h1>
            <h2>Login</h2>
            
            <?php echo display_messages(); ?>
            
            <!-- Login form using $_POST -->
            <form method="POST" action="login.php">
                <!-- CSRF token for security -->
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        required 
                        autofocus
                        value="<?php echo isset($_POST['username']) ? sanitize_input($_POST['username']) : ''; ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>
            
            <p class="auth-footer">
                Don't have an account? <a href="register.php">Register here</a>
            </p>
            
            <div class="demo-credentials">
                <h4>Demo Credentials:</h4>
                <p><strong>Admin:</strong> admin / admin123</p>
                <p><strong>Organizer:</strong> organizer1 / pass123</p>
                <p><strong>Participant:</strong> user1 / pass123</p>
            </div>
        </div>
    </div>
</body>
</html>
