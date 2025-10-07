<?php
// signup.php

// Ensure these files exist and are correctly linked
require_once 'functions.php';
require_once 'db_connection.php';

$error = '';
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $referred_by = trim($_POST['referred_by'] ?? '');
    
    // Generate unique referral code (ensures data safety on empty fields)
    $referral_code = strtoupper(substr(hash('sha256', $username . time()), 0, 8));

    // --- Validation Checks ---
    if ($password !== $confirm_password) {
        $error = "Password and Confirm Password are not the same.";
    }
    if (empty($error) && strlen($password) < 8) {
        $error = "Password must contain at least 8 characters.";
    }
    // Check for Capital letters, small letters, and special characters
    if (empty($error) && (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[^a-zA-Z\d]/', $password))) {
        $error = "Password must contain Capital letters, small letters, and special characters.";
    }
    if (empty($error) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email should contain “@” and be a valid format.";
    }
    // Full Name check (letters and spaces only)
    if (empty($error) && !preg_match('/^[a-zA-Z\s]+$/', $full_name)) {
        $error = "Full Name should contain only letters.";
    }

    // --- Database Checks and Insertion ---
    if (empty($error)) {
        try {
            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                $error = "Username or Email already exists.";
            } else {
                // Hash password and insert user
                $password_hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, full_name, referred_by, referral_code) 
                                       VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$username, $email, $password_hash, $full_name, $referred_by, $referral_code]);
                
                $success_message = "Signup successful! You can now log in.";
                // Clear form inputs after success
                unset($_POST['username'], $_POST['email'], $_POST['full_name'], $_POST['referred_by']); 
            }
        } catch (PDOException $e) {
            // This is the critical block where the error was reported
            $error = "A database error occurred. Please try again.";
            error_log("Signup PDO Error: " . $e->getMessage());
        }
    }
}

// Ensure the functions exist in functions.php before calling them
print_simple_header("Signup Page");
?>

<div class="content-container">
    <div class="row w-100 justify-content-center">
        <div class="col-md-5 d-flex flex-column align-items-center justify-content-center text-center text-white">
            <h1 class="mb-4">ZamuraMedia Platform!</h1>
            <p class="h5">Our platform allows you to: (Grow Social Media, Earn Money, Refer Friends)</p>
        </div>

        <div class="col-md-5">
            <div class="signup-card">
                <h2 class="text-center">Sign Up</h2>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success_message); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="signup.php">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?= htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="referred_by" class="form-label">Referred By (Optional Code)</label>
                        <input type="text" class="form-control" id="referred_by" name="referred_by" value="<?= htmlspecialchars($_POST['referred_by'] ?? ''); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary w-100" style="background-color: #6c63ff;">Sign Up</button>
                    <p class="text-center mt-3"><a href="login.php">Already have an account? Login</a></p>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
print_simple_footer();
?>