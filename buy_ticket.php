<?php
/**
 * Buy Ticket Page
 * Uses $_POST for event ID, verifies availability
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

// Fetch event details
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

// Verify event exists
if (!$event) {
    $_SESSION['error_message'] = "Event not found";
    redirect('index.php');
}

// Verify user is not the organizer
if ($event['organizer_id'] == $current_user['id']) {
    $_SESSION['error_message'] = "You cannot buy a ticket for your own event";
    redirect('event_details.php?id=' . $event_id);
}

// Verify event is not in the past
if (strtotime($event['event_date']) < time()) {
    $_SESSION['error_message'] = "Cannot register for past events";
    redirect('event_details.php?id=' . $event_id);
}

// Check if user already has a ticket
if (user_has_ticket($pdo, $event_id, $current_user['id'])) {
    $_SESSION['error_message'] = "You already have a ticket for this event";
    redirect('event_details.php?id=' . $event_id);
}

// Check if seats are available
$available_seats = get_available_seats($pdo, $event_id);
if ($available_seats <= 0) {
    $_SESSION['error_message'] = "Sorry, this event is fully booked";
    redirect('event_details.php?id=' . $event_id);
}

// Create ticket
try {
    $stmt = $pdo->prepare("INSERT INTO tickets (event_id, user_id, status) VALUES (?, ?, 'active')");
    
    if ($stmt->execute([$event_id, $current_user['id']])) {
        $_SESSION['success_message'] = "Ticket purchased successfully! You are registered for this event.";
    } else {
        $_SESSION['error_message'] = "Failed to purchase ticket. Please try again.";
    }
} catch (PDOException $e) {
    // Handle duplicate ticket error
    $_SESSION['error_message'] = "You already have a ticket for this event";
}

redirect('event_details.php?id=' . $event_id);
?>
