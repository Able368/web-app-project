<?php
// FILE: grow-social-media.php

include_once 'db_connection.php';
include_once 'functions.php';

// Check if the user is logged in
if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}

// Get the user's current RWF balance
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT balance_rwf FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$current_balance_rwf = $user ? $user['balance_rwf'] : 0.00;

// Array of platforms for display
$platforms = ['YouTube', 'TikTok', 'Instagram', 'Twitter', 'Facebook'];

// --- FIX: Use the correct header function and call the sidebar ---
print_dashboard_header($pdo, "SMM Services Dashboard"); 
print_sidebar(); 
// --- END FIX ---
?>
<main class="main-content"> <div class="container-fluid">
        <h2 class="mb-4">Grow Your Social Media</h2>
        <p class="lead">Your Current Balance: <strong class="text-success"><?= number_format($current_balance_rwf, 0); ?> RWF</strong></p>
        
        <a href="recharge.php" class="btn btn-warning mb-4"><i class="fas fa-wallet"></i> Request Top-Up</a>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['message_type']; ?>"><?= $_SESSION['message']; ?></div>
            <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
        <?php endif; ?>

        <?php foreach ($platforms as $platform): ?>
            <div class="platform-section mb-5">
                <h3><?= htmlspecialchars($platform); ?> Services</h3>
                
                <?php
                // Fetch services ONLY for SMM from the new 'services' table
                $stmt = $pdo->prepare("SELECT * FROM services WHERE platform = ? AND is_active = 1 ORDER BY cost_rwf ASC");
                $stmt->execute([$platform]);
                $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <?php if ($services): ?>
                    <div class="row row-cols-1 row-cols-md-3 g-4">
                        <?php foreach ($services as $service): ?>
                            <div class="col">
                                <div class="card h-100 shadow-sm border-primary">
                                    <div class="card-body">
                                        <h5 class="card-title"><?= htmlspecialchars($service['service_type']); ?></h5>
                                        <p class="card-text">
                                            <strong>Amount:</strong> <?= number_format($service['unit_count'], 0); ?> Units<br>
                                            <strong>Price:</strong> <span class="text-danger"><?= number_format($service['cost_rwf'], 0); ?> RWF</span>
                                        </p>
                                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#orderModal<?= $service['id']; ?>">
                                            <i class="fas fa-shopping-cart"></i> Buy Now
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="modal fade" id="orderModal<?= $service['id']; ?>" tabindex="-1" aria-labelledby="orderModalLabel<?= $service['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="process_order.php">
                                            <div class="modal-header bg-primary text-white">
                                                <h5 class="modal-title" id="orderModalLabel<?= $service['id']; ?>">Confirm Purchase: <?= htmlspecialchars($service['platform'] . ' ' . $service['service_type']); ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body text-dark">
                                                <input type="hidden" name="service_id" value="<?= $service['id']; ?>">
                                                <input type="hidden" name="cost_rwf" value="<?= $service['cost_rwf']; ?>">
                                                <p>Cost: <strong><?= number_format($service['cost_rwf'], 0); ?> RWF</strong></p>
                                                <div class="mb-3">
                                                    <label for="target_link" class="form-label"><?= htmlspecialchars($platform); ?> Post/Page Link</label>
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
                    <div class="alert alert-warning mt-4">No <?= htmlspecialchars($platform); ?> services are currently available.</div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</main>
<?php print_footer(); ?>