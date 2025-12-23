<?php
/**
 * View Participants Page - Organizer/Admin Only
 * Uses $_GET for event ID, displays all participants
 */
$page_title = "Event Participants";
require_once 'config.php';
require_role(['organizer', 'admin']); // Only organizers and admins can view

$current_user = get_user_info();

// Get event ID from $_GET
$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

if ($event_id <= 0) {
    $_SESSION['error_message'] = "Invalid event ID";
    redirect('index.php');
}

// Fetch event details
$stmt = $pdo->prepare("SELECT e.*, u.username as organizer_name FROM events e JOIN users u ON e.organizer_id = u.id WHERE e.id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    $_SESSION['error_message'] = "Event not found";
    redirect('index.php');
}

// Verify user owns the event or is admin
if (!user_owns_event($pdo, $event_id, $current_user['id']) && $current_user['role'] !== 'admin') {
    $_SESSION['error_message'] = "You don't have permission to view participants for this event";
    redirect('index.php');
}

// Fetch all participants
$stmt = $pdo->prepare("
    SELECT t.*, u.username, u.email, t.purchase_date
    FROM tickets t
    JOIN users u ON t.user_id = u.id
    WHERE t.event_id = ? AND t.status = 'active'
    ORDER BY t.purchase_date ASC
");
$stmt->execute([$event_id]);
$participants = $stmt->fetchAll();

include 'header.php';
?>

<?php echo display_messages(); ?>

<div class="page-header">
    <h1 class="page-title">👥 Event Participants</h1>
    <a href="event_details.php?id=<?php echo $event_id; ?>" class="btn btn-secondary">← Back to Event</a>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><?php echo sanitize_input($event['title']); ?></h2>
        <p style="color: var(--text-secondary); margin-top: 0.5rem;">
            📅 <?php echo format_date($event['event_date']); ?> | 
            📍 <?php echo sanitize_input($event['location']); ?>
        </p>
    </div>
    
    <div style="margin-bottom: 1rem;">
        <p style="font-weight: 600; color: var(--text-primary);">
            Total Participants: <?php echo count($participants); ?> / <?php echo $event['max_participants']; ?>
        </p>
        <p style="color: var(--text-secondary);">
            Available Seats: <?php echo get_available_seats($pdo, $event_id); ?>
        </p>
    </div>
    
    <?php if (empty($participants)): ?>
        <div class="alert alert-warning">
            No participants have registered for this event yet.
        </div>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Registration Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($participants as $index => $participant): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo sanitize_input($participant['username']); ?></td>
                            <td><?php echo sanitize_input($participant['email']); ?></td>
                            <td><?php echo format_date($participant['purchase_date']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
