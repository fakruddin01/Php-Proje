<?php
/**
 * Edit Event Page
 * Uses $_GET for event ID, $_POST for form submission
 * Verifies ownership or admin role
 */
$page_title = "Edit Event";
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
    $_SESSION['error_message'] = "You don't have permission to edit this event";
    redirect('index.php');
}

// Process form submission using $_POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token()) {
        $_SESSION['error_message'] = "Invalid request. Please try again.";
    } else {
        $title = sanitize_input($_POST['title'] ?? '');
        $description = sanitize_input($_POST['description'] ?? '');
        $event_date = $_POST['event_date'] ?? '';
        $location = sanitize_input($_POST['location'] ?? '');
        $max_participants = (int)($_POST['max_participants'] ?? 50);
        
        $errors = [];
        
        // Validation
        if (empty($title)) {
            $errors[] = "Event title is required";
        } elseif (strlen($title) < 5) {
            $errors[] = "Event title must be at least 5 characters";
        }
        
        if (empty($description)) {
            $errors[] = "Event description is required";
        }
        
        if (empty($event_date)) {
            $errors[] = "Event date is required";
        }
        
        if (empty($location)) {
            $errors[] = "Event location is required";
        }
        
        // Check that max_participants is not less than current participants
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE event_id = ? AND status = 'active'");
        $stmt->execute([$event_id]);
        $current_participants = $stmt->fetchColumn();
        
        if ($max_participants < $current_participants) {
            $errors[] = "Maximum participants cannot be less than current participants ($current_participants)";
        }
        
        // If no errors, update event
        if (empty($errors)) {
            $stmt = $pdo->prepare("
                UPDATE events 
                SET title = ?, description = ?, event_date = ?, location = ?, max_participants = ?
                WHERE id = ?
            ");
            
            if ($stmt->execute([$title, $description, $event_date, $location, $max_participants, $event_id])) {
                $_SESSION['success_message'] = "Event updated successfully!";
                redirect('event_details.php?id=' . $event_id);
            } else {
                $errors[] = "Failed to update event. Please try again.";
            }
        }
        
        // Display errors
        if (!empty($errors)) {
            $_SESSION['error_message'] = implode("<br>", $errors);
        }
    }
}

include 'header.php';
?>

<?php echo display_messages(); ?>

<div class="page-header">
    <h1 class="page-title">Edit Event</h1>
    <a href="event_details.php?id=<?php echo $event_id; ?>" class="btn btn-secondary">← Back</a>
</div>

<div class="card">
    <!-- Edit event form using $_POST -->
    <form method="POST" action="edit_event.php?id=<?php echo $event_id; ?>">
        <!-- CSRF protection -->
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        
        <div class="form-group">
            <label for="title">Event Title *</label>
            <input 
                type="text" 
                id="title" 
                name="title" 
                required 
                minlength="5"
                value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : htmlspecialchars($event['title']); ?>"
            >
        </div>
        
        <div class="form-group">
            <label for="description">Event Description *</label>
            <textarea 
                id="description" 
                name="description" 
                required
            ><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : htmlspecialchars($event['description']); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="event_date">Event Date & Time *</label>
            <input 
                type="datetime-local" 
                id="event_date" 
                name="event_date" 
                required
                value="<?php echo isset($_POST['event_date']) ? $_POST['event_date'] : date('Y-m-d\TH:i', strtotime($event['event_date'])); ?>"
            >
        </div>
        
        <div class="form-group">
            <label for="location">Location *</label>
            <input 
                type="text" 
                id="location" 
                name="location" 
                required
                value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : htmlspecialchars($event['location']); ?>"
            >
        </div>
        
        <div class="form-group">
            <label for="max_participants">Maximum Participants *</label>
            <input 
                type="number" 
                id="max_participants" 
                name="max_participants" 
                required 
                min="1" 
                max="1000"
                value="<?php echo isset($_POST['max_participants']) ? (int)$_POST['max_participants'] : (int)$event['max_participants']; ?>"
            >
            <?php
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE event_id = ? AND status = 'active'");
                $stmt->execute([$event_id]);
                $current_participants = $stmt->fetchColumn();
            ?>
            <small>Current participants: <?php echo $current_participants; ?></small>
        </div>
        
        <div style="display: flex; gap: 1rem;">
            <button type="submit" class="btn btn-primary">Update Event</button>
            <a href="event_details.php?id=<?php echo $event_id; ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>
