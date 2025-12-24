<?php
/**
 * Event Details Page
 * Uses $_GET to retrieve event ID, displays full event information
 */
$page_title = "Event Details";
require_once 'config.php';
require_login();

// Get event ID from $_GET
$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($event_id <= 0) {
    $_SESSION['error_message'] = "Invalid event ID";
    redirect('index.php');
}

// Fetch event details
$stmt = $pdo->prepare("
    SELECT e.*, u.username as organizer_name, u.email as organizer_email
    FROM events e
    JOIN users u ON e.organizer_id = u.id
    WHERE e.id = ?
");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

// Verify event exists
if (!$event) {
    $_SESSION['error_message'] = "Event not found";
    redirect('index.php');
}

$current_user = get_user_info();
$available_seats = get_available_seats($pdo, $event_id);
$has_ticket = user_has_ticket($pdo, $event_id, $current_user['id']);
$is_organizer = $current_user['id'] == $event['organizer_id'];
$is_admin = $current_user['role'] === 'admin';
$is_past = strtotime($event['event_date']) < time();

// Get participant count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE event_id = ? AND status = 'active'");
$stmt->execute([$event_id]);
$participant_count = $stmt->fetchColumn();

include 'header.php';
?>

<?php echo display_messages(); ?>

<div class="page-header">
    <h1 class="page-title">Event Details</h1>
    <a href="index.php" class="btn btn-secondary">← Back to Events</a>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><?php echo sanitize_input($event['title']); ?></h2>
    </div>
    
    <div style="display: grid; gap: 1.5rem;">
        <div>
            <h3 style="margin-bottom: 0.5rem; color: var(--text-primary);">📅 Date & Time</h3>
            <p style="color: var(--text-secondary);"><?php echo format_date($event['event_date']); ?></p>
            <?php if ($is_past): ?>
                <span class="badge badge-danger">Event has passed</span>
            <?php endif; ?>
        </div>
        
        <div>
            <h3 style="margin-bottom: 0.5rem; color: var(--text-primary);">📍 Location</h3>
            <p style="color: var(--text-secondary);"><?php echo sanitize_input($event['location']); ?></p>
        </div>
        
        <div>
            <h3 style="margin-bottom: 0.5rem; color: var(--text-primary);">👤 Organizer</h3>
            <p style="color: var(--text-secondary);"><?php echo sanitize_input($event['organizer_name']); ?></p>
        </div>
        
        <div>
            <h3 style="margin-bottom: 0.5rem; color: var(--text-primary);">📝 Description</h3>
            <p style="color: var(--text-secondary); line-height: 1.6;"><?php echo nl2br(sanitize_input($event['description'])); ?></p>
        </div>
        
        <div>
            <h3 style="margin-bottom: 0.5rem; color: var(--text-primary);">🎫 Participation</h3>
            <p style="color: var(--text-secondary);">
                <?php echo $participant_count; ?> / <?php echo $event['max_participants']; ?> participants registered
            </p>
            <?php if ($available_seats > 0 && !$is_past): ?>
                <span class="badge badge-success"><?php echo $available_seats; ?> seats available</span>
            <?php elseif (!$is_past): ?>
                <span class="badge badge-danger">Event is full</span>
            <?php endif; ?>
        </div>
        
        <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 1rem;">
            <?php if ($is_organizer || $is_admin): ?>
                <a href="edit_event.php?id=<?php echo $event_id; ?>" class="btn btn-primary">Edit Event</a>
                <a href="view_participants.php?event_id=<?php echo $event_id; ?>" class="btn btn-success">View Participants</a>
                <a href="delete_event.php?id=<?php echo $event_id; ?>" 
                   class="btn btn-danger" 
                   onclick="return confirm('Are you sure you want to delete this event? This action cannot be undone.')">
                   Delete Event
                </a>
            <?php endif; ?>
            
            <?php if (!$is_organizer && !$is_past): ?>
                <?php if ($has_ticket): ?>
                    <span class="badge badge-success" style="padding: 0.75rem 1.5rem; font-size: 1rem;">✓ You are registered for this event</span>
                    <form method="POST" action="cancel_ticket.php">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to cancel your ticket?')">
                            Cancel My Ticket
                        </button>
                    </form>
                <?php elseif ($available_seats > 0): ?>
                    <form method="POST" action="buy_ticket.php">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                        <button type="submit" class="btn btn-success">Register for this Event</button>
                    </form>
                <?php else: ?>
                    <p style="color: var(--error-color); font-weight: 500;">This event is fully booked</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
