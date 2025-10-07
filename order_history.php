<?php
// order_history.php

require_once 'functions.php';
check_session(); 

$userId = $_SESSION['user_id'];
$message = '';

// --- Fetch User's Order History ---
try {
    $stmt = $pdo->prepare("SELECT o.*, s.platform, s.service_type FROM orders o JOIN services s ON o.service_id = s.id WHERE o.user_id = ? ORDER BY o.order_date DESC");
    $stmt->execute([$userId]);
    $orderHistory = $stmt->fetchAll();
} catch (PDOException $e) {
    $orderHistory = [];
    $message = "Error fetching order history.";
    error_log("Order History Fetch Error: " . $e->getMessage());
}

// Reuse Dashboard HTML structure
print_dashboard_header($pdo, "My Order History");
print_sidebar(); 
?>

<main class="main-content">
    <div class="card p-4 shadow">
        <h1 class="text-dark">Service Order History</h1>
        <p class="text-muted">A record of all your social media growth service purchases.</p>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if (count($orderHistory) > 0): ?>
            <div class="table-responsive mt-4">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Platform</th>
                            <th>Service Type</th>
                            <th>Target Link</th>
                            <th>Amount Paid</th>
                            <th>Order Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orderHistory as $order): ?>
                            <tr>
                                <td>#<?= htmlspecialchars($order['id']); ?></td>
                                <td><?= htmlspecialchars($order['platform']); ?></td>
                                <td><?= htmlspecialchars($order['service_type']); ?></td>
                                <td><a href="<?= htmlspecialchars($order['target_link']); ?>" target="_blank" class="text-decoration-none">View Link</a></td>
                                <td><?= number_format($order['amount_paid'], 2); ?> <?= htmlspecialchars($order['currency']); ?></td>
                                <td><?= date('Y-m-d H:i', strtotime($order['order_date'])); ?></td>
                                <td><span class="badge bg-<?= $order['status'] == 'Completed' ? 'success' : ($order['status'] == 'Failed' ? 'danger' : 'warning'); ?>"><?= htmlspecialchars($order['status']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info mt-4">You have not placed any service orders yet.</div>
        <?php endif; ?>
    </div>
</main>
<?php print_footer(); ?>