<?php
// FILE: dashboard.php - Main User Dashboard

include_once 'db_connection.php';
include_once 'functions.php';

if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// 1. Fetch User Balances and Info
$stmt = $pdo->prepare("SELECT balance_rwf, balance_usd, username FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Ensure $user has been fetched and update the variables
$current_rwf = $user['balance_rwf'] ?? 0.00;
$current_usd = $user['balance_usd'] ?? 0.00;
// We now explicitly get the username from the database result for use below
$current_username = $user['username'] ?? 'User'; 

// 2. Fetch Recent SMM Orders (Buyer Activity)
$stmt_orders = $pdo->prepare("
    SELECT o.status, o.amount_paid, o.target_link, s.platform, s.service_type
    FROM orders o 
    JOIN services s ON o.service_id = s.id
    WHERE o.user_id = ?
    ORDER BY o.order_date DESC 
    LIMIT 5
");
$stmt_orders->execute([$user_id]);
$recent_orders = $stmt_orders->fetchAll(PDO::FETCH_ASSOC);

// 3. Fetch Recent Worker Submissions (Earner Activity)
$stmt_submissions = $pdo->prepare("
    SELECT ws.admin_status, ws.submission_date, t.title, t.payment_rwf 
    FROM worker_submissions ws 
    JOIN tasks t ON ws.task_id = t.id
    WHERE ws.worker_id = ?
    ORDER BY ws.submission_date DESC 
    LIMIT 5
");
$stmt_submissions->execute([$user_id]);
$recent_submissions = $stmt_submissions->fetchAll(PDO::FETCH_ASSOC);

// FIX 1: Use the correct header function and $pdo object
print_dashboard_header($pdo, "User Dashboard");

// FIX 2: Call print_sidebar() to display the navigation sidebar
print_sidebar(); 
?>
<main class="main-content">
    <div class="container-fluid">
        <h2 class="mb-4">Welcome Back, <?= htmlspecialchars($current_username); ?>!</h2>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['message_type']; ?>"><?= $_SESSION['message']; ?></div>
            <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
        <?php endif; ?>

        <div class="row g-4 mb-5">
            <div class="col-md-6">
                <div class="card bg-success text-white shadow h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-wallet me-2"></i> RWF Balance</h5>
                        <p class="card-text fs-2"><?= number_format($current_rwf, 0); ?> RWF</p>
                        <a href="recharge.php" class="btn btn-light btn-sm mt-2"><i class="fas fa-plus-circle"></i> Recharge</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-primary text-white shadow h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-dollar-sign me-2"></i> USD Balance</h5>
                        <p class="card-text fs-2">$<?= number_format($current_usd, 2); ?></p>
                        <a href="withdraw.php" class="btn btn-light btn-sm mt-2"><i class="fas fa-arrow-down"></i> Withdraw</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="card shadow h-100">
                    <div class="card-header bg-danger text-white">
                        <i class="fas fa-shopping-cart me-2"></i> Recent SMM Orders (Buyer)
                    </div>
                    <ul class="list-group list-group-flush">
                        <?php if ($recent_orders): ?>
                            <?php foreach ($recent_orders as $order): ?>
                                <li class="list-group-item">
                                    **<?= htmlspecialchars($order['platform']); ?> <?= htmlspecialchars($order['service_type']); ?>**<br>
                                    <small>Link: <a href="<?= htmlspecialchars($order['target_link']); ?>" target="_blank"><?= substr(htmlspecialchars($order['target_link']), 0, 30); ?>...</a></small><br>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($order['status']); ?></span> - 
                                    <small class="text-danger"><?= number_format($order['amount_paid'], 0); ?> RWF</small>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="list-group-item text-muted">No SMM orders placed yet.</li>
                        <?php endif; ?>
                    </ul>
                    <div class="card-footer"><a href="grow-social-media.php" class="btn btn-link">View All Orders</a></div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card shadow h-100">
                    <div class="card-header bg-info text-white">
                        <i class="fas fa-tasks me-2"></i> Recent Task Submissions (Worker)
                    </div>
                    <ul class="list-group list-group-flush">
                        <?php if ($recent_submissions): ?>
                            <?php foreach ($recent_submissions as $submission): ?>
                                <?php
                                $status_text = match((int)$submission['admin_status']) {
                                    0 => ['Pending', 'warning'],
                                    1 => ['Approved (Paid)', 'success'],
                                    2 => ['Rejected', 'danger'],
                                    default => ['Unknown', 'secondary'],
                                };
                                ?>
                                <li class="list-group-item">
                                    **<?= htmlspecialchars($submission['title']); ?>**<br>
                                    <span class="badge bg-<?= $status_text[1]; ?>"><?= $status_text[0]; ?></span> - 
                                    <small class="text-success">+<?= number_format($submission['payment_rwf'], 0); ?> RWF</small>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="list-group-item text-muted">No tasks submitted yet.</li>
                        <?php endif; ?>
                    </ul>
                    <div class="card-footer"><a href="platform-workers.php" class="btn btn-link">View All Tasks</a></div>
                </div>
            </div>
        </div>

    </div>
</main>
<?php print_footer(); ?>