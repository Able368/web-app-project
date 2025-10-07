<?php
// FILE: process_order.php - Handles SMM service purchase

session_start();
include_once 'db_connection.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $service_id = filter_input(INPUT_POST, 'service_id', FILTER_SANITIZE_NUMBER_INT);
    $cost_rwf = filter_input(INPUT_POST, 'cost_rwf', FILTER_VALIDATE_FLOAT);
    $target_link = filter_input(INPUT_POST, 'target_link', FILTER_SANITIZE_URL);

    if (!$service_id || $cost_rwf === false || !$target_link) {
        $_SESSION['message'] = "Invalid order details.";
        $_SESSION['message_type'] = "danger";
        header("Location: grow-social-media.php");
        exit;
    }

    try {
        // Start a transaction for safe financial operations
        $pdo->beginTransaction();

        // 1. Fetch user's current balance and lock the row
        $stmt = $pdo->prepare("SELECT balance_rwf FROM users WHERE id = ? FOR UPDATE");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || $user['balance_rwf'] < $cost_rwf) {
            $pdo->rollBack();
            $_SESSION['message'] = "Insufficient balance. Please top up your account.";
            $_SESSION['message_type'] = "danger";
            header("Location: grow-social-media.php");
            exit;
        }

        // 2. Deduct the cost from the user's balance
        $new_balance = $user['balance_rwf'] - $cost_rwf;
        $stmt = $pdo->prepare("UPDATE users SET balance_rwf = ? WHERE id = ?");
        $stmt->execute([$new_balance, $user_id]);

        // 3. Record the order in the new 'orders' table
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, service_id, target_link, amount_paid, currency, status) 
                               VALUES (?, ?, ?, ?, 'RWF', 'Pending')");
        $stmt->execute([$user_id, $service_id, $target_link, $cost_rwf]);
        
        // 4. Record a notification (Optional, but good practice)
        $order_id = $pdo->lastInsertId();
        $message = "Your SMM Order #{$order_id} has been placed for {$cost_rwf} RWF.";
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'Order')");
        $stmt->execute([$user_id, $message]);

        // Commit transaction
        $pdo->commit();

        $_SESSION['message'] = "Order placed successfully! {$cost_rwf} RWF deducted. You will be notified when the service starts.";
        $_SESSION['message_type'] = "success";

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Order Processing Error: " . $e->getMessage());
        $_SESSION['message'] = "An error occurred while processing your order. Please try again later.";
        $_SESSION['message_type'] = "danger";
    }

    header("Location: grow-social-media.php");
    exit;
} else {
    header("Location: grow-social-media.php");
    exit;
}
?>