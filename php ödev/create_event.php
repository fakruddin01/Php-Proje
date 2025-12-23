<?php
/**
 * Create Event Page - Organizer/Admin only
 * Uses require for config, $_POST for form submission, role verification
 */
$page_title = "Create Event";
require_once 'config.php';
require_role(['organizer', 'admin']); // Verify user has permission

$current_user = get_user_info();

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
        } elseif (strtotime($event_date) < time()) {
            $errors[] = "Event date must be in the future";
        }
        
        if (empty($location)) {
            $errors[] = "Event location is required";
        }
        
        if ($max_participants < 1) {
            $errors[] = "Maximum participants must be at least 1";
        } elseif ($max_participants > 1000) {
            $errors[] = "Maximum participants cannot exceed 1000";
        }
        
        // If no errors, create event
        if (empty($errors)) {
            $stmt = $pdo->prepare("
                INSERT INTO events (organizer_id, title, description, event_date, location, max_participants)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            if ($stmt->execute([$current_user['id'], $title, $description, $event_date, $location, $max_participants])) {
                $_SESSION['success_message'] = "Event created successfully!";
                redirect('index.php');
            } else {
                $errors[] = "Failed to create event. Please try again.";
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
    <h1 class="page-title">Create New Event</h1>
</div>

<div class="card">
    <!-- Event creation form using $_POST -->
    <form method="POST" action="create_event.php">
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
                value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"
            >
        </div>
        
        <div class="form-group">
            <label for="description">Event Description *</label>
            <textarea 
                id="description" 
                name="description" 
                required
            ><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="event_date">Event Date & Time *</label>
            <input 
                type="datetime-local" 
                id="event_date" 
                name="event_date" 
                required
                min="<?php echo date('Y-m-d\TH:i'); ?>"
                value="<?php echo isset($_POST['event_date']) ? $_POST['event_date'] : ''; ?>"
            >
            <small>Event must be scheduled for a future date</small>
        </div>
        
        <div class="form-group">
            <label for="location">Location *</label>
            <input 
                type="text" 
                id="location" 
                name="location" 
                required
                value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>"
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
                value="<?php echo isset($_POST['max_participants']) ? (int)$_POST['max_participants'] : 50; ?>"
            >
            <small>Maximum number of people who can register (1-1000)</small>
        </div>
        
        <div style="display: flex; gap: 1rem;">
            <button type="submit" class="btn btn-primary">Create Event</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>
