# Event Management System - PHP Homework Project

## 📋 Project Overview
A complete event management system built with PHP, MySQL, and modern web design. This project demonstrates proper use of:
- ✅ **require/include** - Centralized configuration and reusable components
- ✅ **$_GET** - Search, filtering, and parameter passing
- ✅ **$_POST** - Form submissions with CSRF protection
- ✅ **$_SESSION** - User authentication and state management
- ✅ **Verification systems** - Input validation, role-based access control, CSRF tokens

## 🎯 Features

### User Roles
1. **Admin** - Full system access, manage users and all events
2. **Organizer** - Create and manage own events, view participants
3. **Participant** - View events, register/cancel tickets

### Core Functionality
- 🔐 User authentication (login/register/logout)
- 📅 Event creation and management
- 🎫 Ticket purchasing and cancellation
- 👥 Participant list viewing (for organizers)
- 🔍 Event search functionality
- ⚙️ Admin panel for user management

## 🚀 Installation Instructions

### Prerequisites
- XAMPP, WAMP, or similar PHP development environment
- PHP 7.4 or higher
- MySQL 5.7 or higher

### Setup Steps

1. **Start your local server (XAMPP/WAMP)**
   - Start Apache and MySQL services

2. **Import the database**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database or use the SQL file:
   - Click "Import" and select `database.sql`
   - Or manually run the SQL file

3. **Configure database connection** (if needed)
   - Open `config.php`
   - Update database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'event_management');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

4. **Update base URL**
   - In `config.php`, update the BASE_URL if your folder name is different:
   ```php
   define('BASE_URL', 'http://localhost/your-folder-name/');
   ```

5. **Access the application**
   - Open browser and navigate to: `http://localhost/php%20ödev/`
   - You will be redirected to the login page

## 👤 Default User Accounts

| Role | Username | Password |
|------|----------|----------|
| Admin | admin | admin123 |
| Organizer | organizer1 | pass123 |
| Participant | user1 | pass123 |

## 📁 File Structure

```
php ödev/
├── config.php              # Centralized DB config (included in all pages)
├── functions.php           # Reusable functions library
├── database.sql            # Database schema and sample data
│
├── login.php              # Login (uses $_POST, $_SESSION)
├── register.php           # Registration (uses $_POST)
├── logout.php             # Logout (destroys $_SESSION)
│
├── header.php             # Common header (uses require)
├── footer.php             # Common footer
├── style.css              # Modern responsive styling
│
├── index.php              # Event listing (uses $_GET for search)
├── create_event.php       # Create event (uses $_POST)
├── edit_event.php         # Edit event (uses $_GET, $_POST)
├── delete_event.php       # Delete event (uses $_GET)
├── event_details.php      # Event details (uses $_GET)
│
├── buy_ticket.php         # Purchase ticket (uses $_POST)
├── cancel_ticket.php      # Cancel ticket (uses $_POST)
├── view_participants.php  # View participants (uses $_GET)
│
└── admin_users.php        # User management (uses $_GET, $_POST)
```

## 🔒 Security Features

### 1. CSRF Protection
Every form includes a CSRF token:
```php
<input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
```

### 2. SQL Injection Prevention
All queries use prepared statements with PDO:
```php
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
```

### 3. Input Sanitization
All user input is sanitized:
```php
$username = sanitize_input($_POST['username']);
```

### 4. Role-Based Access Control
Pages verify user permissions:
```php
require_role(['organizer', 'admin']);
```

## 📝 Key PHP Concepts Demonstrated

### Using require/include
Every page includes centralized configuration:
```php
require_once 'config.php';  // Includes functions.php automatically
include 'header.php';
include 'footer.php';
```

### Using $_GET
Search and filtering:
```php
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
```

Event ID passing:
```php
$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
```

### Using $_POST
Form submissions:
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username']);
    // Process form...
}
```

### Using $_SESSION
User authentication:
```php
$_SESSION['user_id'] = $user['id'];
$_SESSION['role'] = $user['role'];

// Check if logged in
if (verify_login()) {
    // User is authenticated
}
```

## 🧪 Testing the Application

### As Admin
1. Login with admin/admin123
2. View all events
3. Go to "Manage Users" to change user roles
4. Edit or delete any event

### As Organizer
1. Login with organizer1/pass123
2. Click "Create Event" to add new event
3. Edit your own events
4. View participant lists for your events

### As Participant
1. Login with user1/pass123
2. Browse available events
3. Purchase tickets for events
4. Cancel your tickets

## 🎨 Design Features
- Modern, responsive design
- Role-based color-coded badges
- Card-based layout
- Smooth animations and hover effects
- Mobile-friendly interface

## 📊 Database Schema

### users
- id (Primary Key)
- username (Unique)
- email (Unique)
- password (Hashed)
- role (admin/organizer/participant)
- created_at

### events
- id (Primary Key)
- organizer_id (Foreign Key → users)
- title
- description
- event_date
- location
- max_participants
- created_at

### tickets
- id (Primary Key)
- event_id (Foreign Key → events)
- user_id (Foreign Key → users)
- purchase_date
- status (active/cancelled)

## 📄 License
This is a homework project for educational purposes.

## 👨‍💻 Author
Created as PHP homework demonstrating proper use of require/include, $_GET, $_POST, $_SESSION, and verification systems.
