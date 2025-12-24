<?php
/**
 * Common Header - Included on all pages
 * Uses require/include for config and displays navigation based on role
 */
if (!isset($pdo)) {
    require_once 'config.php';
}

// Get current user info from session
$current_user = get_user_info();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Event Management System'; ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php if (verify_login()): ?>
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <a href="index.php">🎫 Event Management</a>
            </div>
            
            <ul class="nav-menu">
                <li><a href="index.php" class="<?php echo $current_page === 'index.php' ? 'active' : ''; ?>">Events</a></li>
                
                <?php if (verify_role(['organizer', 'admin'])): ?>
                    <li><a href="create_event.php" class="<?php echo $current_page === 'create_event.php' ? 'active' : ''; ?>">Create Event</a></li>
                <?php endif; ?>
                
                <?php if (verify_role(['admin'])): ?>
                    <li><a href="admin_users.php" class="<?php echo $current_page === 'admin_users.php' ? 'active' : ''; ?>">Manage Users</a></li>
                <?php endif; ?>
            </ul>
            
            <div class="nav-user">
                <span class="user-info">
                    <span class="role-badge role-<?php echo $current_user['role']; ?>">
                        <?php echo ucfirst($current_user['role']); ?>
                    </span>
                    <?php echo sanitize_input($current_user['username']); ?>
                </span>
                <a href="logout.php" class="btn btn-sm btn-secondary">Logout</a>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <main class="main-content">
        <div class="container">
