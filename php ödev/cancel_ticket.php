<?php
/**
 * Cancel Ticket Page
 * Uses $_POST for event ID, verifies ticket ownership
 */
require_once 'config.php';
require_login();

$current_user = get_user_info();

// This page only accepts POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

// Verify CSRF token
if (!verify_csrf_token()) {
    $_SESSION['error_message'] = "Invalid request. Please try again.";
    redirect('index.php');
}

// Get event ID from $_POST
$event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;

if ($event_id <= 0) {
    $_SESSION['error_message'] = "Invalid event ID";
    redirect('index.php');
}

// Find user's ticket for this event
$stmt = $pdo->prepare("
    SELECT * FROM tickets 
    WHERE event_id = ? AND user_id = ? AND status = 'active'
    LIMIT 1
");
$stmt->execute([$event_id, $current_user['id']]);
$ticket = $stmt->fetch();

// Verify ticket exists
if (!$ticket) {
    $_SESSION['error_message'] = "You don't have an active ticket for this event";
    redirect('event_details.php?id=' . $event_id);
}

// Cancel ticket by updating status
$stmt = $pdo->prepare("UPDATE tickets SET status = 'cancelled' WHERE id = ?");

if ($stmt->execute([$ticket['id']])) {
    $_SESSION['success_message'] = "Ticket cancelled successfully";
} else {
    $_SESSION['error_message'] = "Failed to cancel ticket. Please try again.";
}

redirect('event_details.php?id=' . $event_id);
?>
