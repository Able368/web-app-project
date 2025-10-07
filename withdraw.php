<?php
// FILE: withdraw.php - User submits a withdrawal request

include_once 'db_connection.php';
include_once 'functions.php';

if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_balance = 0.00;

// Fetch current user balances
$stmt = $pdo->prepare("SELECT balance_rwf, balance_usd FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle Withdrawal Request Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $currency = filter_input(INPUT_POST, 'currency', FILTER_SANITIZE_STRING);
    $target_wallet = filter_input(INPUT_POST, 'target_wallet', FILTER_SANITIZE_STRING);
    $balance_column = $currency === 'RWF' ? 'balance_rwf' : 'balance_usd';
    $current_user_balance = $user[$balance_column] ?? 0.00;

    if ($amount > 0 && $target_wallet) {
        // Simple check to ensure amount is positive. More rigorous checks (like minimum withdrawal) should be added.
        if ($amount > $current_user_balance) {
            $_SESSION['message'] = "Insufficient balance in your {$currency} account. Available: " . number_format($current_user_balance, $currency === 'USD' ? 2 : 0) . " {$currency}";
            $_SESSION['message_type'] = "danger";
        } else {
            try {
                $pdo->beginTransaction();

                // 1. Deduct the withdrawal amount from the user's balance
                // Using a parameterized query for the column name is impossible here, so we must validate the column name string:
                if ($balance_column !== 'balance_rwf' && $balance_column !== 'balance_usd') {
                    throw new Exception("Invalid currency column selected.");
                }
                
                $stmt_deduct = $pdo->prepare("UPDATE users SET {$balance_column} = {$balance_column} - ? WHERE id = ?");
                $stmt_deduct->execute([$amount, $user_id]);

                // 2. Record the withdrawal request in the new 'withdrawals' table (status 0=Pending)
                $stmt_record = $pdo->prepare("INSERT INTO withdrawals (user_id, amount, currency, target_wallet, is_approved) 
                                                 VALUES (?, ?, ?, ?, 0)");
                $stmt_record->execute([$user_id, $amount, $currency, $target_wallet]);

                $pdo->commit();
                $_SESSION['message'] = "Withdrawal request of " . number_format($amount, $currency === 'USD' ? 2 : 0) . " {$currency} submitted. Funds will be sent after admin review.";
                $_SESSION['message_type'] = "success";

            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Withdrawal Error: " . $e->getMessage());
                $_SESSION['message'] = "An error occurred during withdrawal processing.";
                $_SESSION['message_type'] = "danger";
            }
        }
    } else {
        $_SESSION['message'] = "Please provide a valid amount and wallet address.";
        $_SESSION['message_type'] = "danger";
    }
    header("Location: withdraw.php");
    exit;
}

// FIX 1: Replaced print_header() with the correct function print_dashboard_header($pdo, $title)
print_dashboard_header($pdo, "Withdraw Funds");

// FIX 2: Call print_sidebar() to display the navigation sidebar
print_sidebar(); 
?>
<main class="main-content"> <div class="container-fluid">
        <h2 class="mb-4">Withdraw Your Earnings</h2>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['message_type']; ?>"><?= $_SESSION['message']; ?></div>
            <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="alert alert-success">Available RWF: **<?= number_format($user['balance_rwf'] ?? 0, 0); ?> RWF**</div>
            </div>
            <div class="col-md-6">
                <div class="alert alert-primary">Available USD: **$<?= number_format($user['balance_usd'] ?? 0, 2); ?>**</div>
            </div>
        </div>

        <div class="card shadow">
            <div class="card-body">
                <form method="POST" action="withdraw.php">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="amount" class="form-label">Amount to Withdraw</label>
                            <input type="number" step="0.01" min="1" class="form-control" name="amount" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="currency" class="form-label">Currency</label>
                            <select class="form-control" name="currency" required>
                                <option value="RWF">RWF (Mobile Money/Bank)</option>
                                <option value="USD">USD (Binance/Crypto)</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="target_wallet" class="form-label">Target Wallet/Address (MoMo Number or Binance Wallet)</label>
                        <input type="text" class="form-control" name="target_wallet" required>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Submit Withdrawal Request</button>
                </form>
            </div>
        </div>
    </div>
</main>
<?php print_footer(); ?>