<?php
/**
 * Admin User Management Page - Admin Only
 * Uses $_GET for search, $_POST for role updates
 */
$page_title = "User Management";
require_once 'config.php';
require_role(['admin']); // Only admins can access

// Handle role update using $_POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Verify CSRF token
    if (!verify_csrf_token()) {
        $_SESSION['error_message'] = "Invalid request. Please try again.";
    } else {
        $action = $_POST['action'];
        $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        
        if ($action === 'update_role' && $user_id > 0) {
            $new_role = sanitize_input($_POST['role'] ?? '');
            
            // Validate role
            $allowed_roles = ['admin', 'organizer', 'participant'];
            if (in_array($new_role, $allowed_roles)) {
                $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                
                if ($stmt->execute([$new_role, $user_id])) {
                    $_SESSION['success_message'] = "User role updated successfully";
                } else {
                    $_SESSION['error_message'] = "Failed to update user role";
                }
            } else {
                $_SESSION['error_message'] = "Invalid role selected";
            }
        } elseif ($action === 'delete_user' && $user_id > 0) {
            // Prevent deleting yourself
            if ($user_id == $_SESSION['user_id']) {
                $_SESSION['error_message'] = "You cannot delete your own account";
            } else {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                
                if ($stmt->execute([$user_id])) {
                    $_SESSION['success_message'] = "User deleted successfully";
                } else {
                    $_SESSION['error_message'] = "Failed to delete user";
                }
            }
        }
        
        redirect('admin_users.php');
    }
}

// Get search parameter from $_GET
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

// Build query
$query = "SELECT * FROM users WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (username LIKE ? OR email LIKE ?)";
    $search_param = "%$search%";
    $params = [$search_param, $search_param];
}

$query .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

include 'header.php';
?>

<?php echo display_messages(); ?>

<div class="page-header">
    <h1 class="page-title">👥 User Management</h1>
</div>

<!-- Search form using $_GET -->
<div class="search-box">
    <form method="GET" action="admin_users.php">
        <input 
            type="text" 
            name="search" 
            class="search-input" 
            placeholder="🔍 Search users by username or email..."
            value="<?php echo htmlspecialchars($search); ?>"
        >
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">All Users (<?php echo count($users); ?>)</h2>
    </div>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo sanitize_input($user['username']); ?></td>
                        <td><?php echo sanitize_input($user['email']); ?></td>
                        <td>
                            <span class="role-badge role-<?php echo $user['role']; ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </td>
                        <td><?php echo format_date($user['created_at']); ?></td>
                        <td>
                            <!-- Role update form using $_POST -->
                            <form method="POST" action="admin_users.php" style="display: inline-block; margin-right: 0.5rem;">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <input type="hidden" name="action" value="update_role">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                
                                <select name="role" onchange="this.form.submit()" 
                                        style="padding: 0.25rem 0.5rem; border-radius: 0.25rem; border: 1px solid var(--border-color);">
                                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    <option value="organizer" <?php echo $user['role'] === 'organizer' ? 'selected' : ''; ?>>Organizer</option>
                                    <option value="participant" <?php echo $user['role'] === 'participant' ? 'selected' : ''; ?>>Participant</option>
                                </select>
                            </form>
                            
                            <!-- Delete user form using $_POST -->
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <form method="POST" action="admin_users.php" style="display: inline-block;" 
                                      onsubmit="return confirm('Are you sure you want to delete this user? This will also delete all their events and tickets.')">
                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            <?php else: ?>
                                <span style="color: var(--text-secondary); font-size: 0.875rem;">(You)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Statistics -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-top: 2rem;">
    <?php
        $stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
        $role_counts = $stmt->fetchAll();
    ?>
    
    <?php foreach ($role_counts as $role_stat): ?>
        <div class="card">
            <h3 style="margin-bottom: 0.5rem; color: var(--text-primary);">
                <?php echo ucfirst($role_stat['role']); ?>s
            </h3>
            <p style="font-size: 2rem; font-weight: bold; color: var(--primary-color);">
                <?php echo $role_stat['count']; ?>
            </p>
        </div>
    <?php endforeach; ?>
</div>

<?php include 'footer.php'; ?>
