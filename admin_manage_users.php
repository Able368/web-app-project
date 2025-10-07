<?php
// FILE: admin_manage_users.php - Admin interface for managing users and balances

include_once 'db_connection.php';
include_once 'functions.php';
// Assuming $pdo is available from db_connection.php

if (!is_admin()) {
    header("Location: dashboard.php");
    exit;
}

// Handle Balance Update Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_balance') {
    $target_user_id = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
    // Ensure both balances are treated as floats
    $new_rwf_balance = filter_input(INPUT_POST, 'balance_rwf', FILTER_VALIDATE_FLOAT);
    $new_usd_balance = filter_input(INPUT_POST, 'balance_usd', FILTER_VALIDATE_FLOAT);

    if ($target_user_id && $new_rwf_balance !== false && $new_usd_balance !== false) {
        try {
            // Update balances in the users table
            $stmt = $pdo->prepare("UPDATE users SET balance_rwf = ?, balance_usd = ? WHERE id = ?");
            $stmt->execute([$new_rwf_balance, $new_usd_balance, $target_user_id]);

            $_SESSION['message'] = "User ID {$target_user_id}'s balances have been manually updated.";
            $_SESSION['message_type'] = "success";
        } catch (Exception $e) {
            error_log("Admin Balance Update Error: " . $e->getMessage());
            $_SESSION['message'] = "An error occurred during balance update.";
            $_SESSION['message_type'] = "danger";
        }
    } else {
        $_SESSION['message'] = "Invalid balance values or user ID.";
        $_SESSION['message_type'] = "danger";
    }

    header("Location: admin_manage_users.php");
    exit;
}

// Fetch all users
$stmt_users = $pdo->query("SELECT id, username, full_name, email, balance_rwf, balance_usd, is_admin FROM users ORDER BY id DESC");
$all_users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

// FIX: Replace print_header() with the correct function print_dashboard_header() and call print_sidebar()
print_dashboard_header($pdo, "Admin User Management");
print_sidebar();
?>
<div class="main-content">
    <main class="dashboard-content">
        <div class="container-fluid">
            <h2 class="mb-4">Manage All Users & Balances</h2>
            <p class="lead">View user details, update account status, and manually adjust balances.</p>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?= $_SESSION['message_type']; ?>"><?= $_SESSION['message']; ?></div>
                <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
            <?php endif; ?>

            <div class="card shadow">
                <div class="card-header">All System Users</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>RWF Balance</th>
                                    <th>USD Balance</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_users as $user): ?>
                                <tr>
                                    <td><?= $user['id']; ?></td>
                                    <td><?= htmlspecialchars($user['username']); ?></td>
                                    <td><?= htmlspecialchars($user['email']); ?></td>
                                    <td class="text-success fw-bold"><?= number_format($user['balance_rwf'], 0); ?> RWF</td>
                                    <td class="text-primary fw-bold">$<?= number_format($user['balance_usd'], 2); ?></td>
                                    <td>
                                        <?php if ($user['is_admin'] == 0): // Prevent editing self or other admins easily ?>
                                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editBalanceModal<?= $user['id']; ?>">
                                                <i class="fas fa-edit"></i> Edit Balance
                                            </button>
                                            <?php else: ?>
                                            <span class="badge bg-danger">ADMIN</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                                <div class="modal fade" id="editBalanceModal<?= $user['id']; ?>" tabindex="-1" aria-labelledby="editBalanceModalLabel<?= $user['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST" action="admin_manage_users.php">
                                                <input type="hidden" name="action" value="update_balance">
                                                <input type="hidden" name="user_id" value="<?= $user['id']; ?>">
                                                <div class="modal-header bg-warning text-dark">
                                                    <h5 class="modal-title" id="editBalanceModalLabel<?= $user['id']; ?>">Adjust Balances for <?= htmlspecialchars($user['username']); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body text-dark">
                                                    <p>Current RWF: <?= number_format($user['balance_rwf'], 0); ?> RWF</p>
                                                    <p>Current USD: $<?= number_format($user['balance_usd'], 2); ?></p>
                                                    <div class="mb-3">
                                                        <label for="balance_rwf" class="form-label">New RWF Balance</label>
                                                        <input type="number" step="1" class="form-control" name="balance_rwf" value="<?= htmlspecialchars($user['balance_rwf']); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="balance_usd" class="form-label">New USD Balance</label>
                                                        <input type="number" step="0.01" class="form-control" name="balance_usd" value="<?= htmlspecialchars($user['balance_usd']); ?>" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-warning">Save Changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<?php print_footer(); ?>