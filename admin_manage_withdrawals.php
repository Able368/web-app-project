<?php
// FILE: admin_manage_withdrawals.php (Error Handled for Undefined Array Keys)

include_once 'db_connection.php'; // Assuming db_connection.php makes $pdo global
include_once 'functions.php';

global $pdo; // Make sure $pdo is available here

check_session(); 
check_admin(); 

$message = '';
$messageType = '';

// --- Logic to Handle Approval/Rejection ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'], $_POST['withdrawal_id'])) {
    $withdrawalId = (int)$_POST['withdrawal_id'];
    $action = $_POST['action']; // 'approve' or 'reject'

    try {
        $pdo->beginTransaction();

        // 1. Fetch the withdrawal details and lock the row
        $stmt = $pdo->prepare("SELECT w.*, u.username, u.id AS user_id FROM withdrawals w JOIN users u ON w.user_id = u.id WHERE w.id = ? FOR UPDATE");
        $stmt->execute([$withdrawalId]);
        $withdrawal = $stmt->fetch();

        if ($withdrawal && $withdrawal['status'] == 'Pending') {
            $userId = $withdrawal['user_id'];
            $amount = $withdrawal['amount'];
            $currency = $withdrawal['currency'];
            $balanceField = ($currency == 'RWF') ? 'balance_rwf' : 'balance_usd';

            if ($action == 'approve') {
                // Mark withdrawal as Approved
                $updateWithdrawal = $pdo->prepare("UPDATE withdrawals SET status = 'Approved' WHERE id = ?");
                $updateWithdrawal->execute([$withdrawalId]);

                // Log notification for the user
                $notifyMsg = "Your withdrawal request of **" . number_format($amount, 2) . " $currency** has been successfully approved and processed.";
                add_notification($pdo, $userId, $notifyMsg, 'Withdrawal');

                $message = "Withdrawal #$withdrawalId for {$withdrawal['username']} Approved successfully.";
                $messageType = 'success';

            } elseif ($action == 'reject') {
                // 1. Mark withdrawal as Rejected
                $updateWithdrawal = $pdo->prepare("UPDATE withdrawals SET status = 'Rejected' WHERE id = ?");
                $updateWithdrawal->execute([$withdrawalId]);

                // 2. Return the funds to the user's balance (since it was deducted upon request)
                $returnFunds = $pdo->prepare("UPDATE users SET $balanceField = $balanceField + ? WHERE id = ?");
                $returnFunds->execute([$amount, $userId]);
                
                // Log notification for the user
                $notifyMsg = "Your withdrawal request of **" . number_format($amount, 2) . " $currency** has been rejected. The funds have been returned to your account balance.";
                add_notification($pdo, $userId, $notifyMsg, 'Withdrawal');

                $message = "Withdrawal #$withdrawalId for {$withdrawal['username']} Rejected. Funds returned to user.";
                $messageType = 'warning';
            }
        } else {
            $message = "Invalid withdrawal request or status is not pending.";
            $messageType = 'danger';
        }

        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Admin Withdrawal Error: " . $e->getMessage());
        $message = "Database Error: Could not process withdrawal action.";
        $messageType = 'danger';
    }
}

// --- Fetch all Withdrawal Requests (Pending, Approved, Rejected) ---
try {
    $stmt = $pdo->prepare("SELECT w.*, u.username, u.email FROM withdrawals w JOIN users u ON w.user_id = u.id ORDER BY w.request_date DESC");
    $stmt->execute();
    $withdrawals = $stmt->fetchAll();
} catch (PDOException $e) {
    $withdrawals = [];
    $message = (isset($message) ? $message . ' / ' : '') . "Error fetching withdrawal requests.";
    $messageType = 'danger';
}

print_dashboard_header($pdo, "Manage Withdrawals");

print_sidebar(); 
?>

<div class="main-content">
    <main class="admin-content">
        <div class="card p-4 shadow">
            <h1 class="text-dark">Manage Withdrawal Requests</h1>
            <p class="text-muted">Review and process cashout requests from users.</p>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= $messageType; ?>"><?= htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if (count($withdrawals) > 0): ?>
                <div class="table-responsive mt-4">
                    <table class="table table-striped table-hover align-middle">
                        <thead>
                            <tr class="table-dark">
                                <th>ID</th>
                                <th>User (Email)</th>
                                <th>Amount</th>
                                <th>Method/Destination</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($withdrawals as $w): ?>
                                <tr>
                                    <td>#<?= htmlspecialchars($w['id'] ?? ''); ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($w['username'] ?? 'N/A'); ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($w['email'] ?? 'N/A'); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= ($w['currency'] ?? '') == 'RWF' ? 'success' : 'warning'; ?> p-2">
                                            <?= number_format($w['amount'] ?? 0, 2); ?> <?= htmlspecialchars($w['currency'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($w['method'] ?? 'N/A'); ?></strong><br>
                                        <small><?= htmlspecialchars($w['destination'] ?? 'N/A'); ?></small>
                                    </td>
                                    <td><?= date('Y-m-d H:i', strtotime($w['request_date'] ?? 'now')); ?></td>
                                    <td>
                                        <?php 
                                             // FIX: Use null coalescing operator (??) to prevent Undefined array key warnings
                                             $currentStatus = $w['status'] ?? 'Error'; 
                                             $statusClass = 'warning';

                                             if ($currentStatus == 'Approved') $statusClass = 'success';
                                             if ($currentStatus == 'Rejected') $statusClass = 'danger';
                                             if ($currentStatus == 'Error') $statusClass = 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $statusClass; ?>"><?= htmlspecialchars($currentStatus); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($currentStatus == 'Pending'): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="withdrawal_id" value="<?= $w['id']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn btn-sm btn-success me-2" onclick="return confirm('Are you sure you want to APPROVE this withdrawal?');">
                                                    Approve
                                                </button>
                                            </form>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="withdrawal_id" value="<?= $w['id']; ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to REJECT this withdrawal? Funds will be returned to the user.');">
                                                    Reject
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-secondary" disabled>Processed</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info mt-4">No withdrawal requests found.</div>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php 
print_footer(); 
?>