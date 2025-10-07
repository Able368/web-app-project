<?php
// FILE: recharge.php - User submits a top-up request

include_once 'db_connection.php';
include_once 'functions.php';

if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle Recharge Request Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $currency = filter_input(INPUT_POST, 'currency', FILTER_SANITIZE_STRING);
    $phone_or_wallet = filter_input(INPUT_POST, 'phone_or_wallet', FILTER_SANITIZE_STRING);
    // Note: Proof file upload logic should be implemented here (omitted for brevity)
    $proof_screenshot = "proof/upload_" . time() . ".jpg"; // Placeholder path

    if ($amount > 0 && ($currency == 'RWF' || $currency == 'USD') && $phone_or_wallet) {
        try {
            $stmt = $pdo->prepare("INSERT INTO recharges (user_id, amount, currency, phone_or_wallet, proof_screenshot, is_approved) 
                                     VALUES (?, ?, ?, ?, ?, 0)"); // 0 = Pending
            $stmt->execute([$user_id, $amount, $currency, $phone_or_wallet, $proof_screenshot]);

            $_SESSION['message'] = "Your recharge request of " . number_format($amount) . " {$currency} has been submitted for admin review. You will be notified when it is approved.";
            $_SESSION['message_type'] = "success";
        } catch (Exception $e) {
            error_log("Recharge Submission Error: " . $e->getMessage());
            $_SESSION['message'] = "An error occurred. Please try again.";
            $_SESSION['message_type'] = "danger";
        }
    } else {
        $_SESSION['message'] = "Please provide valid amount and payment details.";
        $_SESSION['message_type'] = "danger";
    }
    header("Location: recharge.php");
    exit;
}

// Fetch admin payment details for display
$stmt_settings = $pdo->query("SELECT momo_pay_code, binance_wallet_address FROM admin_settings LIMIT 1");
$settings = $stmt_settings->fetch(PDO::FETCH_ASSOC);

// FIX 1: Replaced print_header() with the correct function print_dashboard_header($pdo, $title)
print_dashboard_header($pdo, "Recharge Account");

// FIX 2: Call print_sidebar() to display the navigation sidebar
print_sidebar(); 
?>
<main class="main-content"> <div class="container-fluid">
        <h2 class="mb-4">Request Account Top-Up</h2>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['message_type']; ?>"><?= $_SESSION['message']; ?></div>
            <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
        <?php endif; ?>

        <div class="card shadow">
            <div class="card-body">
                <p class="lead">Please make a payment to one of the following methods, then submit your request below with the proof.</p>
                
                <div class="alert alert-info">
                    **RWF Payment (MoMoPay):** <?= htmlspecialchars($settings['momo_pay_code'] ?? 'N/A'); ?><br>
                    **USD Payment (Binance):** <?= htmlspecialchars($settings['binance_wallet_address'] ?? 'N/A'); ?>
                </div>

                <form method="POST" action="recharge.php" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="amount" class="form-label">Amount</label>
                            <input type="number" step="1" min="1" class="form-control" name="amount" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="currency" class="form-label">Currency</label>
                            <select class="form-control" name="currency" required>
                                <option value="RWF">RWF (Rwanda Franc)</option>
                                <option value="USD">USD (US Dollar)</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="phone_or_wallet" class="form-label">Your Phone Number or Sender Wallet ID (for verification)</label>
                        <input type="text" class="form-control" name="phone_or_wallet" required>
                    </div>
                    <div class="mb-3">
                        <label for="proof_screenshot" class="form-label">Upload Payment Proof Screenshot</label>
                        <input type="file" class="form-control" name="proof_screenshot" disabled>
                        <small class="text-danger">File upload functionality placeholder (requires server setup).</small>
                    </div>
                    <button type="submit" class="btn btn-success"><i class="fas fa-upload"></i> Submit Request</button>
                </form>
            </div>
        </div>
    </div>
</main>
<?php print_footer(); ?>