<?php
// referral.php

require_once 'functions.php';
check_session(); 

$userId = $_SESSION['user_id'];
$userData = get_user_data($pdo, $userId);
$referralCode = $userData['referral_code'] ?? 'N/A';
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$referralLink = $base_url . "/signup.php?ref=" . $referralCode;

// --- Fetch Referral Earnings and Counts (Mocked logic for a complete view) ---
try {
    // 1. Fetch total users referred (Mocked count for demonstration)
    $stmt = $pdo->prepare("SELECT COUNT(id) FROM users WHERE referred_by_id = ?");
    $stmt->execute([$userId]);
    $referredCount = $stmt->fetchColumn();
    
    // 2. Fetch recent referral earnings (Mocked data since 'referral_commissions' table doesn't exist)
    $recentEarnings = [
        ['date' => '2025-09-25', 'amount' => 1500, 'source' => 'User A (First Deposit)'],
        ['date' => '2025-09-20', 'amount' => 500, 'source' => 'User B (Order Commission)'],
    ];

} catch (PDOException $e) {
    $referredCount = 0;
    $recentEarnings = [];
    error_log("Referral Data Fetch Error: " . $e->getMessage());
}

// Reuse Dashboard HTML structure
print_dashboard_header($pdo, "Refer & Earn");
print_sidebar(); 
?>

<main class="main-content">
    <div class="card p-4 shadow">
        <h1 class="text-dark">Referral Program</h1>
        <p class="text-muted">Invite friends and earn a commission when they use ZamuraMedia!</p>
        
        <div class="row g-4 mt-3">
            <div class="col-md-6">
                <div class="p-4 rounded shadow bg-success text-white">
                    <h4 class="mb-3">Your Referral Code</h4>
                    <p class="display-6 fw-bold mb-0"><?= htmlspecialchars($referralCode); ?></p>
                </div>
            </div>
            <div class="col-md-6">
                 <div class="p-4 rounded shadow bg-warning text-dark">
                    <h4 class="mb-3">Total Referred Users</h4>
                    <p class="display-6 fw-bold mb-0"><?= number_format($referredCount); ?></p>
                </div>
            </div>
            
            <div class="col-12">
                <div class="p-4 border rounded bg-light text-dark">
                    <h4 class="mb-3">Shareable Link</h4>
                    <div class="input-group">
                        <input type="text" class="form-control" id="referralLink" value="<?= htmlspecialchars($referralLink); ?>" readonly>
                        <button class="btn btn-primary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('referralLink').value); alert('Link copied!');">Copy Link</button>
                    </div>
                    <small class="form-text text-muted">Send this link to friends to ensure you get credit for their registration.</small>
                </div>
            </div>
        </div>

        <h4 class="mt-5 text-dark">Recent Commission Earnings</h4>
         <?php if (count($recentEarnings) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover mt-3">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount (RWF)</th>
                            <th>Source</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentEarnings as $earning): ?>
                            <tr>
                                <td><?= htmlspecialchars($earning['date']); ?></td>
                                <td class="text-success fw-bold">+<?= number_format($earning['amount'], 2); ?></td>
                                <td><?= htmlspecialchars($earning['source']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info mt-4">No referral commissions earned yet.</div>
        <?php endif; ?>
    </div>
</main>
<?php print_footer(); ?>