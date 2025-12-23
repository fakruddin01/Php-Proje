<?php
/**
 * Reusable Functions Library
 * Contains all common functions used throughout the application
 */

/**
 * Sanitize user input to prevent XSS attacks
 * @param string $data - Raw input data
 * @return string - Sanitized data
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Verify if user is logged in
 * Uses $_SESSION to check authentication
 * @return bool - True if logged in, false otherwise
 */
function verify_login() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Verify user role and permissions
 * @param array $allowed_roles - Array of allowed roles
 * @return bool - True if user has permission
 */
function verify_role($allowed_roles) {
    if (!verify_login()) {
        return false;
    }
    
    $user_role = $_SESSION['role'] ?? '';
    return in_array($user_role, $allowed_roles);
}

/**
 * Require login - redirect to login page if not authenticated
 * Uses $_SESSION verification
 */
function require_login() {
    if (!verify_login()) {
        $_SESSION['error_message'] = "يجب تسجيل الدخول أولاً";
        redirect('login.php');
        exit();
    }
}

/**
 * Require specific role - redirect if user doesn't have permission
 * @param array $allowed_roles - Array of allowed roles
 */
function require_role($allowed_roles) {
    require_login();
    
    if (!verify_role($allowed_roles)) {
        $_SESSION['error_message'] = "ليس لديك صلاحية للوصول إلى هذه الصفحة";
        redirect('index.php');
        exit();
    }
}

/**
 * Redirect to another page
 * @param string $page - Page to redirect to
 */
function redirect($page) {
    header("Location: " . BASE_URL . $page);
    exit();
}

/**
 * Get current user information from session
 * @return array - User data
 */
function get_user_info() {
    if (!verify_login()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email'],
        'role' => $_SESSION['role']
    ];
}

/**
 * Generate CSRF token for form protection
 * Stores token in $_SESSION
 * @return string - CSRF token
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token from form submission
 * Checks $_POST for token
 * @return bool - True if valid
 */
function verify_csrf_token() {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

/**
 * Display success or error messages
 * Uses $_SESSION to store messages
 */
function display_messages() {
    $output = '';
    
    if (isset($_SESSION['success_message'])) {
        $output .= '<div class="alert alert-success">' . sanitize_input($_SESSION['success_message']) . '</div>';
        unset($_SESSION['success_message']);
    }
    
    if (isset($_SESSION['error_message'])) {
        $output .= '<div class="alert alert-error">' . sanitize_input($_SESSION['error_message']) . '</div>';
        unset($_SESSION['error_message']);
    }
    
    return $output;
}

/**
 * Validate email format
 * @param string $email - Email to validate
 * @return bool - True if valid
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Hash password securely
 * @param string $password - Plain text password
 * @return string - Hashed password
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password against hash
 * @param string $password - Plain text password
 * @param string $hash - Hashed password
 * @return bool - True if match
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Format date in user-friendly format
 * @param string $date - Date string
 * @return string - Formatted date
 */
function format_date($date) {
    return date('d/m/Y H:i', strtotime($date));
}

/**
 * Check if user owns an event
 * @param PDO $pdo - Database connection
 * @param int $event_id - Event ID
 * @param int $user_id - User ID
 * @return bool - True if user owns event
 */
function user_owns_event($pdo, $event_id, $user_id) {
    $stmt = $pdo->prepare("SELECT organizer_id FROM events WHERE id = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();
    
    return $event && $event['organizer_id'] == $user_id;
}

/**
 * Check if user already has ticket for event
 * @param PDO $pdo - Database connection
 * @param int $event_id - Event ID
 * @param int $user_id - User ID
 * @return bool - True if user has ticket
 */
function user_has_ticket($pdo, $event_id, $user_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE event_id = ? AND user_id = ? AND status = 'active'");
    $stmt->execute([$event_id, $user_id]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Get available seats for event
 * @param PDO $pdo - Database connection
 * @param int $event_id - Event ID
 * @return int - Number of available seats
 */
function get_available_seats($pdo, $event_id) {
    $stmt = $pdo->prepare("
        SELECT e.max_participants - COUNT(t.id) as available
        FROM events e
        LEFT JOIN tickets t ON e.id = t.event_id AND t.status = 'active'
        WHERE e.id = ?
        GROUP BY e.id, e.max_participants
    ");
    $stmt->execute([$event_id]);
    $result = $stmt->fetch();
    
    return $result ? $result['available'] : 0;
}
?>
