<?php
/**
 * Logout Page
 * Destroys user session and redirects to login
 */
require_once 'config.php';

// Verify user is logged in before logging out
if (verify_login()) {
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy the session
    session_destroy();
    
    // Start new session for message
    session_start();
    $_SESSION['success_message'] = "You have been logged out successfully";
}

// Redirect to login page
redirect('login.php');
?>
