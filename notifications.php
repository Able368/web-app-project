<?php
// notifications.php

require_once 'functions.php';
check_session(); 

// --- Logic to Fetch Notifications ---
$userId = $_SESSION['user_id'];
$message = '';

try {
    // 1. Fetch all notifications for the user
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll();

    // 2. Mark all unread notifications as read (optional, can be done on view)
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0")->execute([$userId]);

} catch (PDOException $e) {
    $notifications = [];
    $message = "Error fetching notifications.";
    error_log("Notification Fetch Error: " . $e->getMessage());
}

// Reuse Dashboard HTML structure
print_dashboard_header($pdo, "My Notifications");
print_sidebar(); 
?>

<main class="main-content">
    <div class="card p-4 shadow">
        <h1 class="text-dark">Notifications Center</h1>
        <p class="text-muted">Stay updated on your account activity, including recharge approvals and task payments.</p>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if (count($notifications) > 0): ?>
            <div class="list-group mt-4">
                <?php foreach ($notifications as $notification): ?>
                    <?php 
                        // Determine color based on notification type
                        $badgeClass = 'bg-secondary';
                        if ($notification['type'] == 'Recharge') $badgeClass = 'bg-success';
                        if ($notification['type'] == 'Order') $badgeClass = 'bg-primary';
                        if ($notification['type'] == 'System') $badgeClass = 'bg-warning text-dark';
                        
                        $itemClass = $notification['is_read'] == 0 ? 'list-group-item-light border-start border-5 border-primary' : 'list-group-item-secondary';
                    ?>
                    <div class="list-group-item <?= $itemClass; ?> d-flex justify-content-between align-items-start mb-2 rounded shadow-sm">
                        <div class="ms-2 me-auto">
                            <div class="fw-bold d-flex align-items-center">
                                <span class="badge <?= $badgeClass; ?> me-2"><?= htmlspecialchars($notification['type']); ?></span>
                                <?= htmlspecialchars($notification['message']); ?>
                            </div>
                            <small class="text-muted"><?= date('M d, Y H:i', strtotime($notification['created_at'])); ?></small>
                        </div>
                        <?php if ($notification['is_read'] == 0): ?>
                             <span class="badge bg-danger rounded-pill">NEW</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info mt-4">You have no notifications yet.</div>
        <?php endif; ?>
    </div>
</main>
<?php print_footer(); ?>