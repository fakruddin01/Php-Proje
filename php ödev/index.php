<?php
/**
 * Main Dashboard - Event Listing Page
 * Uses require for config, $_SESSION for auth, $_GET for filtering
 */
$page_title = "Events Dashboard";
require_once 'config.php';
require_login(); // Verify user is logged in using $_SESSION

$current_user = get_user_info();

// Get filter from $_GET
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

// Build query based on user role
$query = "SELECT e.*, u.username as organizer_name,
          (SELECT COUNT(*) FROM tickets WHERE event_id = e.id AND status = 'active') as ticket_count
          FROM events e
          JOIN users u ON e.organizer_id = u.id";

$params = [];

// Add search filter if provided via $_GET
if (!empty($search)) {
    $query .= " WHERE (e.title LIKE ? OR e.description LIKE ? OR e.location LIKE ?)";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param];
}

$query .= " ORDER BY e.event_date ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$events = $stmt->fetchAll();

include 'header.php';
?>

<?php echo display_messages(); ?>

<div class="page-header">
    <h1 class="page-title">📅 Events</h1>
    <?php if (verify_role(['organizer', 'admin'])): ?>
        <a href="create_event.php" class="btn btn-primary">+ Create New Event</a>
    <?php endif; ?>
</div>

<!-- Search form using $_GET -->
<div class="search-box">
    <form method="GET" action="index.php">
        <input 
            type="text" 
            name="search" 
            class="search-input" 
            placeholder="🔍 Search events by title, description, or location..."
            value="<?php echo htmlspecialchars($search); ?>"
        >
    </form>
</div>

<?php if (empty($events)): ?>
    <div class="card">
        <p style="text-align: center; color: var(--text-secondary); padding: 2rem;">
            <?php echo !empty($search) ? "No events found matching your search." : "No events available yet."; ?>
        </p>
    </div>
<?php else: ?>
    <div class="events-grid">
        <?php foreach ($events as $event): ?>
            <?php
                $available_seats = get_available_seats($pdo, $event['id']);
                $is_organizer = $current_user['id'] == $event['organizer_id'];
                $is_admin = $current_user['role'] === 'admin';
                $has_ticket = user_has_ticket($pdo, $event['id'], $current_user['id']);
                $is_past = strtotime($event['event_date']) < time();
            ?>
            <div class="event-card">
                <h3 class="event-title"><?php echo sanitize_input($event['title']); ?></h3>
                
                <div class="event-meta">
                    <div>📅 <?php echo format_date($event['event_date']); ?></div>
                    <div>📍 <?php echo sanitize_input($event['location']); ?></div>
                    <div>👤 Organized by: <?php echo sanitize_input($event['organizer_name']); ?></div>
                    <div>
                        🎫 <?php echo $event['ticket_count']; ?> / <?php echo $event['max_participants']; ?> participants
                        <?php if ($available_seats > 0 && !$is_past): ?>
                            <span class="badge badge-success"><?php echo $available_seats; ?> seats left</span>
                        <?php elseif (!$is_past): ?>
                            <span class="badge badge-danger">Full</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <p class="event-description">
                    <?php echo nl2br(sanitize_input(substr($event['description'], 0, 150))); ?>
                    <?php if (strlen($event['description']) > 150) echo '...'; ?>
                </p>
                
                <div class="event-actions">
                    <a href="event_details.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-secondary">View Details</a>
                    
                    <?php if ($is_organizer || $is_admin): ?>
                        <a href="edit_event.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                        <a href="view_participants.php?event_id=<?php echo $event['id']; ?>" class="btn btn-sm btn-success">View Participants</a>
                    <?php endif; ?>
                    
                    <?php if (!$is_organizer && !$is_past): ?>
                        <?php if ($has_ticket): ?>
                            <span class="badge badge-success">✓ Registered</span>
                        <?php elseif ($available_seats > 0): ?>
                            <form method="POST" action="buy_ticket.php" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-success">Buy Ticket</button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include 'footer.php'; ?>
