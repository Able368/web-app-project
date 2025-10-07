<?php
// FILE: functions.php - Consolidated and corrected version (With check_admin() and add_notification())

require_once 'db_connection.php';
// We'll assume $pdo is available globally from db_connection.php

session_start();

// --- Session and Auth Functions ---

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

function check_session() {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit;
    }
}

// NEW FUNCTION: Enforces Admin Access
function check_admin() {
    if (!is_admin()) {
        // Redirect non-admins to the regular dashboard
        header("Location: dashboard.php");
        exit;
    }
}

function get_user_data($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database Error in get_user_data: " . $e->getMessage());
        return null;
    }
}

function get_unread_notifications_count($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

// NEW FUNCTION: Logs a notification for a user
function add_notification($pdo, $userId, $message, $type = 'General') {
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $message, $type]);
        return true;
    } catch (PDOException $e) {
        error_log("Notification Error: " . $e->getMessage());
        return false;
    }
}


// --- HTML Component Functions ---

/** Generates the common HTML header for dashboard-style pages */
function print_dashboard_header($pdo, $title) {
    check_session();
    $userData = get_user_data($pdo, $_SESSION['user_id']);
    $username = $userData['username'] ?? 'User';
    $notification_count = get_unread_notifications_count($pdo, $_SESSION['user_id']);
    $notif_badge = $notification_count > 0 ? "<span class='badge bg-danger ms-1'>$notification_count</span>" : "";

    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>$title</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Styles to keep the sidebar fixed and header consistent */
        body { margin-top: 50px; } 
        .topbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            background-color: #2c3e50;
            color: white;
            padding: 10px 20px;
        }
        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 50px; 
            left: 0;
            background-color: #34495e;
            padding-top: 20px;
            overflow-y: auto;
            color: #fff;
        }
        .main-content {
            margin-left: 250px; 
            padding: 20px;
            min-height: calc(100vh - 50px);
            background: linear-gradient(to right, #bdc3c7, #2c3e50);
            color: #fff;
        }
        .sidebar a {
            color: #ecf0f1;
            text-decoration: none;
            padding: 10px 15px;
            display: block;
            border-bottom: 1px solid #4a627a;
        }
        .sidebar a:hover {
            background-color: #4a627a;
        }
    </style>
</head>
<body>
    <div class="topbar d-flex justify-content-between align-items-center">
        <div class="d-inline-flex align-items-center">
             <small class="me-3"><i class="far fa-clock me-2"></i>Mon - Fri : 09.00 AM - 09.00 PM</small>
             <small><i class="fa fa-envelope me-2"></i>abelniyonshuti3@gmail.com</small>
        </div>
        <div class="d-inline-flex align-items-center">
            <a class="text-white me-2" href="#"><i class="fab fa-facebook-f"></i></a>
            <a class="text-white me-2" href="#"><i class="fab fa-twitter"></i></a>
            <a class="text-white me-2" href="#"><i class="fab fa-linkedin-in"></i></a>
            <a class="text-white me-4" href="#"><i class="fab fa-instagram"></i></a>
            <h5 class="text-warning mb-0 me-3">Welcome, $username!</h5>
            <a href="login.php?logout=true" class="btn btn-sm btn-danger">Logout</a>
        </div>
    </div>
HTML;
}

/** Generates the fixed sidebar menu, reflecting the new SMM/Worker/Admin structure. */
function print_sidebar() {
    global $pdo;
    
    // Determine the user's role
    $is_admin_user = is_admin();
    
    // Define all navigation links based on role
    $nav_links = [
        'dashboard.php' => ['icon' => 'fas fa-home', 'text' => 'Home Dashboard'],
        'choice.php'    => ['icon' => 'fas fa-exchange-alt', 'text' => 'Role Selector'],
    ];

    if (!$is_admin_user) {
        // User/Worker Links
        $nav_links['grow-social-media.php'] = ['icon' => 'fas fa-chart-line', 'text' => 'Buy SMM Services'];
        $nav_links['platform-workers.php'] = ['icon' => 'fas fa-hammer', 'text' => 'Earn Money (Tasks)'];
        $nav_links['recharge.php'] = ['icon' => 'fas fa-wallet', 'text' => 'Recharge Account'];
        $nav_links['withdraw.php'] = ['icon' => 'fas fa-money-bill-wave', 'text' => 'Withdraw Funds'];
        $nav_links['notifications.php'] = ['icon' => 'fas fa-bell', 'text' => 'Notifications'];
        $nav_links['help.php'] = ['icon' => 'fas fa-question-circle', 'text' => 'Help & Support'];
    } else {
        // Admin Links - UPDATED STRUCTURE
        $nav_links['admin_dashboard.php'] = ['icon' => 'fas fa-tachometer-alt', 'text' => 'Admin Overview'];
        $nav_links['admin_manage_recharges.php'] = ['icon' => 'fas fa-money-check-alt', 'text' => 'Manage Recharges'];
        $nav_links['admin_manage_submissions.php'] = ['icon' => 'fas fa-user-check', 'text' => 'Review Submissions'];
        $nav_links['admin_manage_orders.php'] = ['icon' => 'fas fa-shopping-basket', 'text' => 'Manage SMM Orders'];
        $nav_links['admin_manage_services.php'] = ['icon' => 'fas fa-cubes', 'text' => 'Manage SMM Prices'];
        $nav_links['admin_manage_tasks.php'] = ['icon' => 'fas fa-tasks', 'text' => 'Manage Worker Jobs'];
        $nav_links['admin_manage_users.php'] = ['icon' => 'fas fa-users', 'text' => 'Manage Users'];
        $nav_links['admin_settings.php'] = ['icon' => 'fas fa-cogs', 'text' => 'System Settings'];
        // Adding the link for the current file
        $nav_links['admin_manage_withdrawals.php'] = ['icon' => 'fas fa-credit-card', 'text' => 'Manage Withdrawals'];
        $nav_links['admin_add_video_tasks.php'] = ['icon' => 'fas fa-money-check-alt', 'text' => 'Add Video Tasks.php'];
    }

    // Start sidebar HTML
    echo '<aside class="sidebar"><ul class="list-unstyled">';
    
    // Loop through links
    foreach ($nav_links as $url => $data) {
        $active_class = (basename($_SERVER['PHP_SELF']) == $url) ? 'active' : '';
        $notif_badge_html = '';

        // Add notification badge only to the user's notifications link
        if (!$is_admin_user && $url == 'notifications.php') {
            $notification_count = get_unread_notifications_count($pdo, $_SESSION['user_id']);
            $notif_badge_html = $notification_count > 0 ? "<span class='badge bg-danger ms-1'>$notification_count</span>" : "";
        }

        echo "<li><a href='{$url}' class='{$active_class}'><i class='{$data['icon']} me-2'></i> {$data['text']} {$notif_badge_html}</a></li>";
    }

    echo '</ul>';
    echo '<a href="choice.php" class="btn btn-sm btn-outline-light w-75 mt-4 mx-auto d-block">← Back to Choices</a>';
    echo '</aside>';
}


/** Generates the common HTML footer. */
function print_footer() {
    echo <<<HTML
    <footer class="bg-dark text-white text-center py-3 mt-auto">
        <p>&copy; 2024 ZamuraMedia Platform. All rights reserved.</p>
        <p>Contact: abelniyonshuti3@gmail.com</p>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
</body>
</html>
HTML;
}

/** Generates the simple header for login/signup pages. */
function print_simple_header($title) {
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>$title</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
    <style>
        body {
            background: linear-gradient(to right, #bdc3c7, #2c3e50);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: 'Arial', sans-serif;
            margin: 0;
            color: #333;
        }
        .top-icons {
            background-color: #2c3e50;
            color: white;
            display: flex;
            justify-content: space-between;
            padding: 10px 20px;
            align-items: center;
        }
        .top-icons .social-links a {
            margin: 0 10px;
            color: white;
            font-size: 1.5rem;
        }
        .top-icons .social-links a:hover {
            color: #3498db;
        }
        .content-container {
             display: flex;
             width: 100%;
             max-width: 1200px;
             margin: 0 auto;
             flex-grow: 1;
             align-items: center;
             justify-content: center;
             padding: 20px;
        }
        .description, .login-form {
            flex: 1;
            padding: 20px;
            color: #fff;
        }
        .login-form, .signup-card {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.3);
            padding: 30px;
            max-width: 500px;
        }
    </style>
</head>
<body>
    <div class="top-icons">
        <div class="d-flex align-items-center">
            <a href="help.php" class="btn btn-sm btn-outline-light me-3">Help</a>
            <div class="social-links">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-linkedin-in"></i></a>
                <a href="#"><i class="fab fa-tiktok"></i></a>
            </div>
        </div>
        <div class="email-caption">
            <small>Email: abelniyonshuti3@gmail.com</small>
        </div>
    </div>
HTML;
}

function print_simple_footer() {
      echo <<<HTML
    <footer class="bg-dark text-white text-center py-3 mt-auto">
        <p>&copy; 2024 ZamuraMedia Platform. All rights reserved.</p>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
</body>
</html>
HTML;
}