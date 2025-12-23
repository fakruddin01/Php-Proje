<?php
/**
 * Delete Event Page
 * Uses $_GET for event ID, verifies ownership or admin role
 */
require_once 'config.php';
require_login();

$current_user = get_user_info();

// Get event ID from $_GET
$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($event_id <= 0) {
    $_SESSION['error_message'] = "Invalid event ID";
    redirect('index.php');
}

// Fetch event
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    $_SESSION['error_message'] = "Event not found";
    redirect('index.php');
}

// Verify user owns the event or is admin
if (!user_owns_event($pdo, $event_id, $current_user['id']) && $current_user['role'] !== 'admin') {
    $_SESSION['error_message'] = "You don't have permission to delete this event";
    redirect('index.php');
}

// Delete event (CASCADE will also delete tickets)
$stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");

if ($stmt->execute([$event_id])) {
    $_SESSION['success_message'] = "Event deleted successfully";
} else {
    $_SESSION['error_message'] = "Failed to delete event";
}

redirect('index.php');
?>
