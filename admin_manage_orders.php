<?php
// FILE: admin_manage_orders.php - Admin interface for managing SMM orders (UPDATED)

include_once 'db_connection.php';
include_once 'functions.php';

global $pdo;

if (!is_admin()) {
    header("Location: dashboard.php");
    exit;
}

// Handle Order Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $order_id = filter_input(INPUT_POST, 'order_id', FILTER_SANITIZE_NUMBER_INT);
    $new_status = filter_input(INPUT_POST, 'new_status', FILTER_SANITIZE_STRING); 
    
    if (in_array($new_status, ['In Progress', 'Completed', 'Failed'])) {
        try {
            $pdo->beginTransaction();

            // 1. Fetch order details, including service info, for update and notification
            $stmt_order = $pdo->prepare("
                SELECT o.user_id, o.amount_paid, o.target_link, s.service_type
                FROM orders o 
                JOIN services s ON o.service_id = s.id
                WHERE o.id = ? FOR UPDATE
            ");
            $stmt_order->execute([$order_id]);
            $order = $stmt_order->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                throw new Exception("Order not found.");
            }
            
            // 2. Update the order status in the 'orders' table
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $order_id]);

            $order_message = "Order #{$order_id} status updated to **{$new_status}**.";
            $order_message_type = 'info';

            // --- CORE LOGIC: Create Worker Task when status moves to 'In Progress' ---
            if ($new_status === 'In Progress' && $order['service_type'] == 'YouTube View') {
                // Determine the reward for the workers (e.g., 5 RWF per view)
                // NOTE: The exact payment logic (payment_rwf, quantity) must be defined based on your service pricing.
                // Assuming a fixed rate for the worker task here for demonstration.
                $worker_reward_rwf = 5; 
                $task_title = "Watch & View: {$order['target_link']}";
                $task_description = "Please watch the full video at the target link. Earning: {$worker_reward_rwf} RWF.";

                // Check if a task linked to this order already exists
                $stmt_check = $pdo->prepare("SELECT id FROM tasks WHERE linked_order_id = ?");
                $stmt_check->execute([$order_id]);
                
                if (!$stmt_check->fetch()) {
                    // Create the new task
                    $stmt_task = $pdo->prepare("
                        INSERT INTO tasks (title, description, task_type, payment_rwf, status, target_url, linked_order_id) 
                        VALUES (?, ?, 'Video View', ?, 'Active', ?, ?)
                    ");
                    $stmt_task->execute([
                        $task_title, 
                        $task_description, 
                        $worker_reward_rwf, 
                        $order['target_link'], 
                        $order_id
                    ]);
                    $order_message .= " **A worker task has been created for views/subs.**";
                }
            }
            // --- END CORE LOGIC ---

            // 3. Handle 'Failed' status: refund the user (Existing logic maintained)
            if ($new_status === 'Failed') {
                $refund_amount = $order['amount_paid'];
                
                // Refund the balance
                $stmt_refund = $pdo->prepare("UPDATE users SET balance_rwf = balance_rwf + ? WHERE id = ?");
                $stmt_refund->execute([$refund_amount, $order['user_id']]);
                
                // Notify the user
                $message = "Your SMM Order #{$order_id} has **FAILED**. The amount of " . number_format($refund_amount) . " RWF has been refunded to your RWF balance.";
                add_notification($pdo, $order['user_id'], $message, 'Order');

                $order_message = "Order #{$order_id} marked as **FAILED** and **refunded** the user.";
                $order_message_type = "warning";
            } else {
                 // Notify the user of status change (In Progress or Completed)
                $message = "Your SMM Order #{$order_id} status has been updated to **{$new_status}**.";
                add_notification($pdo, $order['user_id'], $message, 'Order');

                $order_message_type = $new_status === 'Completed' ? 'success' : 'info';
            }


            $pdo->commit();
            $_SESSION['message'] = $order_message;
            $_SESSION['message_type'] = $order_message_type;

        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Order Management Error: " . $e->getMessage());
            $_SESSION['message'] = "An error occurred during order processing: " . $e->getMessage();
            $_SESSION['message_type'] = "danger";
        }
    }
    header("Location: admin_manage_orders.php");
    exit;
}

// Fetch all pending and in-progress orders
$stmt_orders = $pdo->query("
    SELECT o.id, u.username, s.platform, s.service_type, o.target_link, o.amount_paid, o.status, o.order_date
    FROM orders o 
    JOIN users u ON o.user_id = u.id
    JOIN services s ON o.service_id = s.id
    WHERE o.status IN ('Pending', 'In Progress')
    ORDER BY o.order_date ASC
");
$pending_orders = $stmt_orders->fetchAll(PDO::FETCH_ASSOC);

// --- START OF FIX ---
print_dashboard_header($pdo, "Admin SMM Order Management");
print_sidebar();
?>
<div class="main-content">
    <main class="dashboard-content">
        <div class="container-fluid">
            <h2 class="mb-4">Manage SMM Orders</h2>
            <p class="lead">Review and update the status of services purchased by users.</p>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?= $_SESSION['message_type']; ?>"><?= $_SESSION['message']; ?></div>
                <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
            <?php endif; ?>

            <div class="card shadow">
                <div class="card-header">Pending & In-Progress Orders</div>
                <div class="card-body">
                    <?php if ($pending_orders): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Service</th>
                                        <th>Link</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_orders as $order): ?>
                                    <tr>
                                        <td><?= $order['id']; ?></td>
                                        <td><?= htmlspecialchars($order['username']); ?></td>
                                        <td><?= htmlspecialchars($order['platform']) . ' ' . htmlspecialchars($order['service_type']); ?></td>
                                        <td><a href="<?= htmlspecialchars($order['target_link']); ?>" target="_blank" class="btn btn-sm btn-link">View Link</a></td>
                                        <td><?= number_format($order['amount_paid'], 0); ?> RWF</td>
                                        <td><span class="badge bg-<?= $order['status'] == 'Pending' ? 'danger' : 'warning'; ?>"><?= $order['status']; ?></span></td>
                                        <td>
                                            <form method="POST" action="admin_manage_orders.php" class="d-inline">
                                                <input type="hidden" name="order_id" value="<?= $order['id']; ?>">
                                                <select name="new_status" class="form-select form-select-sm d-inline w-auto me-1">
                                                    <option value="In Progress" <?= $order['status'] == 'In Progress' ? 'selected' : ''; ?>>Start</option>
                                                    <option value="Completed">Complete</option>
                                                    <option value="Failed">Fail & Refund</option>
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-primary">Update</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No pending or in-progress SMM orders at this time.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>
<?php print_footer(); ?>