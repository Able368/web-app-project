<?php
// admin_recharge_approval.php

require_once 'functions.php';
require_once 'db_connection.php';

// --- ADMIN SESSION CHECK ---
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header("Location: admin_login.php");
    exit;
}

// --- Logic to Handle Approval/Rejection ---
$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['recharge_id'], $_POST['action'])) {
    $rechargeId = (int)$_POST['recharge_id'];
    $action = $_POST['action']; // 'approve' or 'reject'

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT user_id, amount, currency, is_approved FROM recharges WHERE id = ?");
        $stmt->execute([$rechargeId]);
        $recharge = $stmt->fetch();

        if ($recharge && $recharge['is_approved'] == 0) { // Check if still pending
            $isApproved = ($action == 'approve') ? 1 : 2; // 1=Approved, 2=Rejected
            $statusMsg = ($action == 'approve') ? 'Approved' : 'Rejected';

            // 1. Update recharge status
            $updateStmt = $pdo->prepare("UPDATE recharges SET is_approved = ?, admin_approved_at = NOW() WHERE id = ?");
            $updateStmt->execute([$isApproved, $rechargeId]);

            // 2. If approved, update user balance
            if ($action == 'approve') {
                $balanceField = ($recharge['currency'] == 'RWF') ? 'balance_rwf' : 'balance_usd';
                $balanceUpdateStmt = $pdo->prepare("UPDATE users SET $balanceField = $balanceField + ? WHERE id = ?");
                $balanceUpdateStmt->execute([$recharge['amount'], $recharge['user_id']]);
                $notificationMsg = "Your recharge of " . $recharge['amount'] . " " . $recharge['currency'] . " has been successfully approved and added to your balance.";
            } else {
                 $notificationMsg = "Your recharge request of " . $recharge['amount'] . " " . $recharge['currency'] . " was rejected. Please contact support.";
            }

            // 3. Notify user
            $notificationStmt = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'Recharge')");
            $notificationStmt->execute([$recharge['user_id'], $notificationMsg]);

            $message = "Recharge request #$rechargeId has been $statusMsg.";
            $messageType = 'success';
        } else {
            $message = "Recharge request is already processed or does not exist.";
            $messageType = 'danger';
        }

        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $message = "Database Error: Could not process request. " . $e->getMessage();
        $messageType = 'danger';
        error_log("Recharge Approval Error: " . $e->getMessage());
    }
}


// --- Fetch Pending Recharges ---
try {
    $stmt = $pdo->prepare("SELECT r.*, u.username FROM recharges r JOIN users u ON r.user_id = u.id WHERE r.is_approved = 0 ORDER BY r.request_date ASC");
    $stmt->execute();
    $pendingRecharges = $stmt->fetchAll();
} catch (PDOException $e) {
    $pendingRecharges = [];
    $message = "Error fetching pending recharges.";
    $messageType = 'danger';
}

// Reuse Admin HTML structure
require_once 'admin_dashboard.php'; 
?>

<main class="main-content">
    <div class="card p-4 shadow">
        <h1 class="text-dark">Approve Pending Recharges (Top-Ups)</h1>
        <p class="text-muted">Review payment proof and approve or reject user recharge requests.</p>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType; ?>"><?= htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if (count($pendingRecharges) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover mt-4">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Amount</th>
                            <th>Currency</th>
                            <th>User Contact</th>
                            <th>Proof</th>
                            <th>Requested At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingRecharges as $recharge): ?>
                            <tr>
                                <td><?= htmlspecialchars($recharge['id']); ?></td>
                                <td><?= htmlspecialchars($recharge['username']); ?></td>
                                <td><?= number_format($recharge['amount'], 2); ?></td>
                                <td><?= htmlspecialchars($recharge['currency']); ?></td>
                                <td><?= htmlspecialchars($recharge['phone_or_wallet']); ?></td>
                                <td><a href="<?= htmlspecialchars($recharge['proof_screenshot']); ?>" target="_blank" class="btn btn-sm btn-info">View Proof</a></td>
                                <td><?= date('Y-m-d H:i', strtotime($recharge['request_date'])); ?></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="recharge_id" value="<?= $recharge['id']; ?>">
                                        <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">Approve</button>
                                    </form>
                                    <form method="POST" class="d-inline ms-1">
                                        <input type="hidden" name="recharge_id" value="<?= $recharge['id']; ?>">
                                        <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger">Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info mt-4">No pending recharge requests at this time.</div>
        <?php endif; ?>
    </div>
</main>

<?php
// print_footer() is called inside admin_dashboard.php's content area
?>