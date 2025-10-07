<?php
// FILE: admin_settings.php - Admin interface for system-wide settings (Corrected)

include_once 'db_connection.php';
include_once 'functions.php';
// Assuming $pdo is available from db_connection.php

if (!is_admin()) {
    header("Location: dashboard.php");
    exit;
}

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $momo_pay_code = filter_input(INPUT_POST, 'momo_pay_code', FILTER_SANITIZE_STRING);
    $binance_wallet_address = filter_input(INPUT_POST, 'binance_wallet_address', FILTER_SANITIZE_STRING);
    $rwf_per_usd = filter_input(INPUT_POST, 'rwf_per_usd', FILTER_VALIDATE_FLOAT);

    if ($rwf_per_usd > 0) {
        try {
            // Check if settings row exists (assuming only one row, ID 1)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_settings WHERE id = 1");
            $stmt->execute();
            
            if ($stmt->fetchColumn() > 0) {
                // Update existing settings
                $stmt = $pdo->prepare("UPDATE admin_settings SET momo_pay_code = ?, binance_wallet_address = ?, rwf_per_usd = ? WHERE id = 1");
                $stmt->execute([$momo_pay_code, $binance_wallet_address, $rwf_per_usd]);
            } else {
                // Insert initial settings
                $stmt = $pdo->prepare("INSERT INTO admin_settings (id, momo_pay_code, binance_wallet_address, rwf_per_usd) VALUES (1, ?, ?, ?)");
                $stmt->execute([$momo_pay_code, $binance_wallet_address, $rwf_per_usd]);
            }
            
            $_SESSION['message'] = "System settings updated successfully.";
            $_SESSION['message_type'] = "success";
        } catch (Exception $e) {
            error_log("Settings Update Error: " . $e->getMessage());
            $_SESSION['message'] = "An error occurred during settings update.";
            $_SESSION['message_type'] = "danger";
        }
    } else {
        $_SESSION['message'] = "Exchange rate must be a valid positive number.";
        $_SESSION['message_type'] = "danger";
    }
    header("Location: admin_settings.php");
    exit;
}

// Fetch current settings
$stmt_settings = $pdo->query("SELECT * FROM admin_settings WHERE id = 1 LIMIT 1");
$settings = $stmt_settings->fetch(PDO::FETCH_ASSOC);

// FIX: Replace print_header() with the correct function print_dashboard_header() and call print_sidebar()
print_dashboard_header($pdo, "Admin System Settings");
print_sidebar();
?>
<div class="main-content">
    <main class="dashboard-content">
        <div class="container-fluid">
            <h2 class="mb-4">System Settings Configuration</h2>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?= $_SESSION['message_type']; ?>"><?= $_SESSION['message']; ?></div>
                <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
            <?php endif; ?>

            <div class="card shadow">
                <div class="card-header bg-primary text-white">Financial & Exchange Rate Settings</div>
                <div class="card-body">
                    <form method="POST" action="admin_settings.php">
                        <div class="mb-3">
                            <label for="rwf_per_usd" class="form-label">RWF per 1 USD Exchange Rate</label>
                            <input type="number" step="0.01" min="1" class="form-control" name="rwf_per_usd" 
                                    value="<?= htmlspecialchars($settings['rwf_per_usd'] ?? 1250.00); ?>" required>
                            <small class="form-text text-muted">Example: If 1 USD = 1250 RWF, enter 1250.</small>
                        </div>

                        <h5 class="mt-4">Payment Acceptance Details (for user deposits)</h5>
                        <div class="mb-3">
                            <label for="momo_pay_code" class="form-label">MoMo Pay Code (RWF Deposits)</label>
                            <input type="text" class="form-control" name="momo_pay_code" 
                                    value="<?= htmlspecialchars($settings['momo_pay_code'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="binance_wallet_address" class="form-label">Binance Wallet Address (USD Deposits)</label>
                            <input type="text" class="form-control" name="binance_wallet_address" 
                                    value="<?= htmlspecialchars($settings['binance_wallet_address'] ?? ''); ?>">
                        </div>
                        
                        <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Save System Settings</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>
<?php print_footer(); ?>