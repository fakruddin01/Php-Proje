<?php
/**
 * Registration Page
 * Uses $_POST for form submission with validation
 */
require_once 'config.php';

// If already logged in, redirect to dashboard
if (verify_login()) {
    redirect('index.php');
}

// Process registration form using $_POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token()) {
        $_SESSION['error_message'] = "Invalid request. Please try again.";
    } else {
        $username = sanitize_input($_POST['username'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $role = sanitize_input($_POST['role'] ?? 'participant');
        
        $errors = [];
        
        // Validation checks
        if (empty($username)) {
            $errors[] = "Username is required";
        } elseif (strlen($username) < 3) {
            $errors[] = "Username must be at least 3 characters";
        }
        
        if (empty($email)) {
            $errors[] = "Email is required";
        } elseif (!validate_email($email)) {
            $errors[] = "Invalid email format";
        }
        
        if (empty($password)) {
            $errors[] = "Password is required";
        } elseif (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters";
        }
        
        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match";
        }
        
        // Validate role
        $allowed_roles = ['participant', 'organizer'];
        if (!in_array($role, $allowed_roles)) {
            $role = 'participant';
        }
        
        // Check if username already exists
        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $errors[] = "Username already taken";
            }
        }
        
        // Check if email already exists
        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = "Email already registered";
            }
        }
        
        // If no errors, create user
        if (empty($errors)) {
            $hashed_password = hash_password($password);
            
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            
            if ($stmt->execute([$username, $email, $hashed_password, $role])) {
                $_SESSION['success_message'] = "Registration successful! Please login.";
                redirect('login.php');
            } else {
                $errors[] = "Registration failed. Please try again.";
            }
        }
        
        // Display errors
        if (!empty($errors)) {
            $_SESSION['error_message'] = implode("<br>", $errors);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Event Management System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-box">
            <h1>🎫 Event Management</h1>
            <h2>Create Account</h2>
            
            <?php echo display_messages(); ?>
            
            <!-- Registration form using $_POST -->
            <form method="POST" action="register.php">
                <!-- CSRF token -->
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <div class="form-group">
                    <label for="username">Username *</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        required 
                        minlength="3"
                        value="<?php echo isset($_POST['username']) ? sanitize_input($_POST['username']) : ''; ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        required
                        value="<?php echo isset($_POST['email']) ? sanitize_input($_POST['email']) : ''; ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required minlength="6">
                    <small>At least 6 characters</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <div class="form-group">
                    <label for="role">I want to *</label>
                    <select id="role" name="role" required>
                        <option value="participant" <?php echo (!isset($_POST['role']) || $_POST['role'] === 'participant') ? 'selected' : ''; ?>>
                            Participate in Events
                        </option>
                        <option value="organizer" <?php echo (isset($_POST['role']) && $_POST['role'] === 'organizer') ? 'selected' : ''; ?>>
                            Organize Events
                        </option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Register</button>
            </form>
            
            <p class="auth-footer">
                Already have an account? <a href="login.php">Login here</a>
            </p>
        </div>
    </div>
</body>
</html>
