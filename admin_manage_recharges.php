<?php
// FILE: admin_manage_recharges.php - Admin interface for approving recharges

include_once 'db_connection.php';
include_once 'functions.php';

if (!is_admin()) {
    header("Location: dashboard.php");
    exit;
}

// Handle Approval/Rejection Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recharge_id'])) {
    $recharge_id = filter_input(INPUT_POST, 'recharge_id', FILTER_SANITIZE_NUMBER_INT);
    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING); // 'approve' or 'reject'
    
    try {
        // Assume $pdo is available from db_connection.php
        $pdo->beginTransaction();

        // 1. Fetch submission details and lock row
        $stmt = $pdo->prepare("SELECT user_id, amount, currency FROM recharges WHERE id = ? AND is_approved = 0 FOR UPDATE");
        $stmt->execute([$recharge_id]);
        $recharge = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$recharge) {
            $pdo->rollBack();
            $_SESSION['message'] = "Recharge request not found or already processed.";
            $_SESSION['message_type'] = "warning";
            header("Location: admin_manage_recharges.php");
            exit;
        }

        $user_id = $recharge['user_id'];
        $amount = $recharge['amount'];
        $currency = $recharge['currency'];
        $balance_column = $currency === 'RWF' ? 'balance_rwf' : 'balance_usd';

        if ($action === 'approve') {
            // 2. Approve submission (is_approved = 1)
            $stmt = $pdo->prepare("UPDATE recharges SET is_approved = 1, admin_approved_at = NOW() WHERE id = ?");
            $stmt->execute([$recharge_id]);

            // 3. Credit user's balance based on currency
            $stmt = $pdo->prepare("UPDATE users SET {$balance_column} = {$balance_column} + ? WHERE id = ?");
            $stmt->execute([$amount, $user_id]);

            // 4. Notify user
            $message = "Your account recharge of " . number_format($amount) . " {$currency} was **APPROVED** and credited to your balance.";
            $stmt_notif = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'Recharge')");
            $stmt_notif->execute([$user_id, $message]);

            $_SESSION['message'] = "Recharge request approved. " . number_format($amount) . " {$currency} credited to user ID {$user_id}.";
            $_SESSION['message_type'] = "success";

        } elseif ($action === 'reject') {
            // 2. Reject submission (is_approved = 2)
            $stmt = $pdo->prepare("UPDATE recharges SET is_approved = 2 WHERE id = ?");
            $stmt->execute([$recharge_id]);
            
            // 3. Notify user of rejection
            $message = "Your recharge request of " . number_format($amount) . " {$currency} was **REJECTED**. Please check your proof.";
            $stmt_notif = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'Recharge')");
            $stmt_notif->execute([$user_id, $message]);

            $_SESSION['message'] = "Recharge request rejected for user ID {$user_id}.";
            $_SESSION['message_type'] = "danger";
        }

        $pdo->commit();

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Recharge Approval Error: " . $e->getMessage());
        $_SESSION['message'] = "An error occurred during processing.";
        $_SESSION['message_type'] = "danger";
    }

    header("Location: admin_manage_recharges.php");
    exit;
}

// Fetch all pending submissions
$stmt_recharges = $pdo->query("
    SELECT r.id, r.amount, r.currency, r.phone_or_wallet, r.proof_screenshot, r.request_date, u.username
    FROM recharges r
    JOIN users u ON r.user_id = u.id
    WHERE r.is_approved = 0 
    ORDER BY r.request_date ASC
");
$pending_recharges = $stmt_recharges->fetchAll(PDO::FETCH_ASSOC);

// --- START OF FIX ---
// The function is print_dashboard_header, not print_header. We also need print_sidebar.
print_dashboard_header($pdo, "Admin Recharge Review");
print_sidebar();
?>
<div class="main-content">
    <main class="dashboard-content">
        <div class="container-fluid">
            <h2 class="mb-4">Review Pending Recharges</h2>
            <p class="lead">Approve or reject top-up requests and credit user accounts.</p>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?= $_SESSION['message_type']; ?>"><?= $_SESSION['message']; ?></div>
                <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
            <?php endif; ?>

            <?php if ($pending_recharges): ?>
                <div class="row row-cols-1 row-cols-md-2 g-4">
                    <?php foreach ($pending_recharges as $rec): ?>
                        <div class="col">
                            <div class="card shadow-sm border-warning h-100">
                                <div class="card-body">
                                    <h5 class="card-title text-warning">Recharge Request: <?= number_format($rec['amount']); ?> <?= $rec['currency']; ?></h5>
                                    <p class="card-text">
                                        **User:** <?= htmlspecialchars($rec['username']); ?><br>
                                        **Sender Info:** <?= htmlspecialchars($rec['phone_or_wallet']); ?><br>
                                        **Requested:** <?= date('Y-m-d H:i', strtotime($rec['request_date'])); ?><br>
                                    </p>
                                    <a href="<?= htmlspecialchars($rec['proof_screenshot']); ?>" target="_blank" class="btn btn-outline-warning btn-sm mb-3" disabled>
                                        <i class="fas fa-image"></i> View Proof (Disabled Placeholder)
                                    </a>

                                    <form method="POST" action="admin_manage_recharges.php" class="d-inline">
                                        <input type="hidden" name="recharge_id" value="<?= $rec['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-success btn-sm">
                                            <i class="fas fa-check"></i> Approve & Credit
                                        </button>
                                    </form>
                                    <form method="POST" action="admin_manage_recharges.php" class="d-inline ms-2">
                                        <input type="hidden" name="recharge_id" value="<?= $rec['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">No pending recharge requests.</div>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php print_footer(); ?>