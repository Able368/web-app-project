<?php
// FILE: admin_dashboard.php - Major updates for new tables

include_once 'db_connection.php';
include_once 'functions.php';

// Check if user is admin
if (!is_admin()) {
    header("Location: dashboard.php");
    exit;
}

// 1. Get counts for dashboard summary
// NOTE: This assumes $pdo is available globally after including db_connection.php and functions.php (which requires it).
// Orders (SMM Sales)
$stmt_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending'");
$pending_orders_count = $stmt_orders->fetchColumn();

// Worker Submissions (Tasks)
$stmt_submissions = $pdo->query("SELECT COUNT(*) FROM worker_submissions WHERE admin_status = 0");
$pending_submissions_count = $stmt_submissions->fetchColumn();

// Recharges
$stmt_recharges = $pdo->query("SELECT COUNT(*) FROM recharges WHERE is_approved = 0");
$pending_recharges_count = $stmt_recharges->fetchColumn();

// --- START: FIX FOR print_header() ERROR ---
// The functions file defines print_dashboard_header, not print_header.
// We also need to include the sidebar to complete the dashboard layout.

print_dashboard_header($pdo, "Admin Control Panel"); // Correct function call
print_sidebar(); // Add the sidebar to complete the dashboard layout
?>
<div class="main-content">
    <main class="dashboard-content">
        <div class="container-fluid">
            <h2 class="mb-4">Admin Control Panel</h2>
            <p class="lead">Welcome, Administrator.</p>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?= $_SESSION['message_type']; ?>"><?= $_SESSION['message']; ?></div>
                <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card text-white bg-warning h-100 shadow">
                        <div class="card-body">
                            <h5 class="card-title">Pending Recharges</h5>
                            <p class="card-text fs-1"><?= $pending_recharges_count; ?></p>
                            <a href="admin_manage_recharges.php" class="btn btn-light btn-sm">Review Now <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card text-white bg-danger h-100 shadow">
                        <div class="card-body">
                            <h5 class="card-title">New SMM Orders</h5>
                            <p class="card-text fs-1"><?= $pending_orders_count; ?></p>
                            <a href="admin_manage_orders.php" class="btn btn-light btn-sm">Manage Orders <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card text-white bg-info h-100 shadow">
                        <div class="card-body">
                            <h5 class="card-title">Worker Submissions</h5>
                            <p class="card-text fs-1"><?= $pending_submissions_count; ?></p>
                            <a href="admin_manage_submissions.php" class="btn btn-light btn-sm">Review Submissions <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                </div>
            </div>

            <h3 class="mt-5">System Management</h3>
            <div class="list-group">
                <a href="admin_manage_services.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-cubes me-2"></i> Manage SMM Services (Prices for Buyers)
                </a>
                <a href="admin_manage_tasks.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-tasks me-2"></i> Manage Worker Tasks (Jobs and Payouts)
                </a>
                <a href="admin_manage_users.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-users me-2"></i> Manage All Users (Balances, Status)
                </a>
                <a href="admin_settings.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-cogs me-2"></i> System Settings (Exchange Rate, Wallets)
                </a>
            </div>

        </div>
    </main>
</div>
<?php print_footer(); ?>