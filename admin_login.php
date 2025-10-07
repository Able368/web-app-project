<?php
// admin_login.php

require_once 'functions.php'; // Includes db_connection.php and starts session
require_once 'db_connection.php';
$error = '';

// Check if admin is already logged in
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
    header("Location: admin_dashboard.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    try {
        // Query to check for user AND admin status (is_admin = 1)
        $stmt = $pdo->prepare("SELECT id, password_hash, is_admin FROM users WHERE (username = ? OR email = ?) AND is_admin = 1");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Login successful and user is an admin
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['is_admin'] = 1;
            
            header("Location: admin_dashboard.php");
            exit;
        } else {
            $error = "Invalid credentials or account is not an administrator.";
        }
    } catch (PDOException $e) {
        $error = "A database error occurred. Please try again.";
        error_log("Admin Login PDO Error: " . $e->getMessage());
    }
}

// Reusing simple header/footer for consistent styling
print_simple_header("Admin Login"); 
?>

<div class="content-container">
    <div class="description" style="color: #f39c12;">
        <h2>System Administrator Access</h2>
        <p>This panel allows management of user accounts, service prices, recharge approvals, and worker task validations.</p>
        <p>⚠️ **Restricted Access:** Only system administrators may log in here.</p>
    </div>
    <div class="login-form">
        <h2 class="text-center text-danger">Admin Login</h2>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" action="admin_login.php">
            <div class="mb-3">
                <label for="username" class="form-label">Username/Email</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-danger w-100">Login as Admin</button>
        </form>
        <p class="text-center mt-3"><a href="login.php">← Back to User Login</a></p>
    </div>
</div>

<?php
print_simple_footer();
?>

