<?php
// services-youtube.php

require_once 'functions.php';
check_session(); 

$userId = $_SESSION['user_id'];
$userData = get_user_data($pdo, $userId);
$message = '';

// --- Logic to Handle Order Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['service_id'], $_POST['target_link'])) {
    $serviceId = (int)$_POST['service_id'];
    $targetLink = trim($_POST['target_link']);

    try {
        $pdo->beginTransaction();

        // 1. Fetch service details and price
        $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ? AND is_active = 1");
        $stmt->execute([$serviceId]);
        $service = $stmt->fetch();

        if ($service) {
            $costRWF = $service['cost_rwf'];
            
            // 2. Check user balance (assumes RWF is the purchase currency)
            if ($userData['balance_rwf'] >= $costRWF) {
                // 3. Deduct balance
                $updateBalance = $pdo->prepare("UPDATE users SET balance_rwf = balance_rwf - ? WHERE id = ?");
                $updateBalance->execute([$costRWF, $userId]);

                // 4. Create new order
                $insertOrder = $pdo->prepare("INSERT INTO orders (user_id, service_id, target_link, amount_paid, currency, status) VALUES (?, ?, ?, ?, 'RWF', 'Pending')");
                $insertOrder->execute([$userId, $serviceId, $targetLink, $costRWF]);

                $message = "Order placed successfully for {$service['service_type']}! Cost: " . number_format($costRWF, 2) . " RWF.";
                $messageType = 'success';
                
                // Refresh user data for display
                $userData = get_user_data($pdo, $userId); 
            } else {
                $message = "Insufficient RWF balance. Please recharge your account.";
                $messageType = 'warning';
            }
        } else {
            $message = "Selected service is invalid or inactive.";
            $messageType = 'danger';
        }
        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $message = "Database Error: Could not place order. " . $e->getMessage();
        $messageType = 'danger';
        error_log("Order Placement Error: " . $e->getMessage());
    }
}

// --- Fetch Available YouTube Services ---
try {
    $stmt = $pdo->prepare("SELECT * FROM services WHERE platform = 'YouTube' AND is_active = 1 ORDER BY cost_rwf ASC");
    $stmt->execute();
    $youtubeServices = $stmt->fetchAll();
} catch (PDOException $e) {
    $youtubeServices = [];
    $message = (isset($message) ? $message . ' / ' : '') . "Error fetching services.";
    $messageType = 'danger';
}

// Reuse Dashboard HTML structure
print_dashboard_header($pdo, "YouTube Services");
print_sidebar(); 
?>

<main class="main-content">
    <div class="card p-4 shadow">
        <h1 class="text-dark">YouTube Service Orders</h1>
        <p class="text-muted">Current RWF Balance: **<?= number_format($userData['balance_rwf'] ?? 0, 2); ?> RWF**</p>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType; ?>"><?= htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if (count($youtubeServices) > 0): ?>
            <div class="row g-4 mt-3">
                <?php foreach ($youtubeServices as $service): ?>
                    <div class="col-md-4">
                        <div class="p-4 rounded shadow-lg bg-info text-white">
                            <h4><?= htmlspecialchars($service['service_type']); ?></h4>
                            <p>Get **<?= number_format($service['unit_count']); ?>** units.</p>
                            <p class="h5">Cost: **<?= number_format($service['cost_rwf'], 0); ?> RWF**</p>
                            
                            <button type="button" class="btn btn-warning mt-2 w-100" data-bs-toggle="modal" data-bs-target="#orderModal-<?= $service['id']; ?>">
                                Place Order
                            </button>
                        </div>
                    </div>

                    <div class="modal fade" id="orderModal-<?= $service['id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <div class="modal-header bg-dark text-white">
                                        <h5 class="modal-title">Order: <?= htmlspecialchars($service['service_type']); ?></h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body text-dark">
                                        <input type="hidden" name="service_id" value="<?= $service['id']; ?>">
                                        <p>Cost: **<?= number_format($service['cost_rwf'], 0); ?> RWF**</p>
                                        <div class="mb-3">
                                            <label for="target_link" class="form-label">YouTube Video/Channel Link</label>
                                            <input type="url" class="form-control" name="target_link" required>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-success">Confirm Order</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-warning mt-4">No YouTube services are currently available.</div>
        <?php endif; ?>
    </div>
</main>
<?php print_footer(); ?>