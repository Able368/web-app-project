<?php
// login.php

require_once 'functions.php';
require_once 'db_connection.php';
$error = '';

// Handle Logout [cite: 489]
if (isset($_GET['logout']) && $_GET['logout'] == 'true') {
    session_destroy();
    session_start();
    $error = "You have been logged out successfully.";
}

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: choice.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT id, password_hash, is_admin FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Login successful. Maintain session 
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['is_admin'] = $user['is_admin'];
            
            header("Location: choice.php");
            exit;
        } else {
            $error = "Invalid username/email or password.";
        }
    } catch (PDOException $e) {
        $error = "A database error occurred during login. Please try again.";
        error_log("Login PDO Error: " . $e->getMessage());
    }
}

print_simple_header("Login Page");
?>

<div class="content-container">
    <div class="description">
        <h2>ZamuraMedia Platform</h2>
        <p><strong>Grow Your Social Medias:</strong> Boost your online presence with high-quality subscribers.</p>
        <p><strong>Earn Money as a Worker:</strong> Engage with content, complete tasks, and earn money from the comfort of your home.</p>
    </div>
    <div class="login-form">
        <h2>Login</h2>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" action="login.php">
            <div class="mb-3">
                <label for="username" class="form-label">Username/Email</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100" style="background-color: #3498db;">Login</button>
        </form>
        <p class="text-center mt-3"><a href="signup.php">Don't have an account? Sign Up</a></p>
    </div>
</div>

<?php
print_simple_footer();
?>