<?php
// user_settings.php

require_once 'functions.php';
check_session(); 

$userId = $_SESSION['user_id'];
$userData = get_user_data($pdo, $userId);
$message = '';
$messageType = '';

// --- Logic to Handle Password Update ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_password') {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if (password_verify($currentPassword, $userData['password_hash'])) {
        if ($newPassword === $confirmPassword) {
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $stmt->execute([$newPasswordHash, $userId]);
                $message = "Password updated successfully.";
                $messageType = 'success';
            } catch (PDOException $e) {
                $message = "Database error during password update.";
                $messageType = 'danger';
            }
        } else {
            $message = "New password and confirmation password do not match.";
            $messageType = 'warning';
        }
    } else {
        $message = "Current password is incorrect.";
        $messageType = 'danger';
    }
}

// Reuse Dashboard HTML structure
print_dashboard_header($pdo, "My Profile & Settings");
print_sidebar(); 
?>

<main class="main-content">
    <div class="card p-4 shadow">
        <h1 class="text-dark">Account Settings</h1>
        <p class="text-muted">Manage your security and profile information.</p>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType; ?>"><?= htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="row g-4 mt-3">
            <div class="col-md-5">
                <div class="p-4 rounded shadow bg-primary text-white">
                    <h4 class="mb-3">Account Details</h4>
                    <p><strong>Username:</strong> <?= htmlspecialchars($userData['username']); ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($userData['email']); ?></p>
                    <p><strong>RWF Balance:</strong> <?= number_format($userData['balance_rwf'], 2); ?> RWF</p>
                    <p><strong>Member Since:</strong> <?= date('M d, Y', strtotime($userData['registration_date'])); ?></p>
                </div>
            </div>

            <div class="col-md-7">
                <div class="p-4 border rounded shadow bg-light text-dark">
                    <h4 class="mb-3">Change Password</h4>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_password">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-warning w-100">Update Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
<?php print_footer(); ?>