<?php
// withdrawal.php

require_once 'functions.php';
check_session(); 

$userId = $_SESSION['user_id'];
$userData = get_user_data($pdo, $userId);
$message = '';
$minRwfWithdrawal = 5000; // Define minimum withdrawal limit

// --- Logic to Handle Withdrawal Request ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['amount'], $_POST['method'], $_POST['destination'])) {
    $amount = (float)$_POST['amount'];
    $method = $_POST['method']; // MoMoPay or USDT
    $destination = trim($_POST['destination']);
    $currency = ($method == 'USDT') ? 'USD' : 'RWF';

    // 1. Basic Validation
    if ($amount <= 0 || empty($destination)) {
        $message = "Invalid amount or destination provided.";
        $messageType = 'danger';
    } elseif ($currency == 'RWF' && $amount < $minRwfWithdrawal) {
        $message = "Minimum RWF withdrawal amount is " . number_format($minRwfWithdrawal) . " RWF.";
        $messageType = 'warning';
    } else {
        try {
            $pdo->beginTransaction();

            // 2. Check and Deduct Balance
            $balanceField = ($currency == 'RWF') ? 'balance_rwf' : 'balance_usd';
            $currentBalance = $userData[$balanceField] ?? 0;
            
            if ($currentBalance >= $amount) {
                // Deduct the requested amount
                $updateBalance = $pdo->prepare("UPDATE users SET $balanceField = $balanceField - ? WHERE id = ?");
                $updateBalance->execute([$amount, $userId]);

                // 3. Log Withdrawal Request (Assuming a 'withdrawals' table exists)
                // INSERT INTO withdrawals (user_id, amount, currency, method, destination, status) VALUES (?, ?, ?, ?, ?, 'Pending');
                
                $message = "Withdrawal request for **" . number_format($amount, 2) . " $currency** submitted successfully! Please wait for admin approval.";
                $messageType = 'success';
                
                // Refresh user data
                $userData = get_user_data($pdo, $userId);
            } else {
                $message = "Insufficient $currency balance to process this withdrawal.";
                $messageType = 'warning';
            }
            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $message = "Database Error: Could not process withdrawal request.";
            $messageType = 'danger';
        }
    }
}


// Reuse Dashboard HTML structure
print_dashboard_header($pdo, "Cashout Earnings");
print_sidebar(); 
?>

<main class="main-content">
    <div class="card p-4 shadow">
        <h1 class="text-dark">Withdraw Your Earnings (Cashout)</h1>
        <p class="text-muted">Minimum RWF Withdrawal: **<?= number_format($minRwfWithdrawal); ?> RWF**</p>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType; ?>"><?= htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="row g-4 mt-3">
            <div class="col-md-6">
                <div class="p-3 bg-success text-white rounded">
                    <h5>Available RWF Balance</h5>
                    <h2 class="display-4"><?= number_format($userData['balance_rwf'] ?? 0, 2); ?> RWF</h2>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 bg-warning text-dark rounded">
                    <h5>Available USD Balance</h5>
                    <h2 class="display-4">$<?= number_format($userData['balance_usd'] ?? 0, 2); ?></h2>
                </div>
            </div>
        </div>

        <h4 class="mt-5 text-dark">Submit Withdrawal Request</h4>
        <form method="POST" class="p-4 border rounded bg-light">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="amount" class="form-label text-dark">Amount to Withdraw</label>
                    <input type="number" step="0.01" class="form-control" id="amount" name="amount" placeholder="e.g., 6000 RWF or 10 USD" required>
                </div>
                <div class="col-md-6">
                    <label for="method" class="form-label text-dark">Withdrawal Method</label>
                    <select class="form-select" id="method" name="method" required>
                        <option value="">Select Method</option>
                        <option value="MoMoPay">MoMoPay (RWF)</option>
                        <option value="USDT">USDT (USD)</option>
                    </select>
                </div>
                <div class="col-12">
                    <label for="destination" class="form-label text-dark">Destination Number/Wallet Address</label>
                    <input type="text" class="form-control" id="destination" name="destination" placeholder="MoMo Number OR USDT Wallet Address" required>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-danger w-100 mt-3">Request Cashout</button>
                </div>
            </div>
        </form>
    </div>
</main>
<?php print_footer(); ?>