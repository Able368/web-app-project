<?php
// admin_worker_approval.php

require_once 'functions.php';

// --- ADMIN SESSION CHECK ---
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header("Location: admin_login.php");
    exit;
}

// NOTE: This page requires a dedicated 'worker_submissions' table 
// to properly manage worker-submitted content, but since that wasn't in database.sql, 
// we'll mock the interface and assume pending submissions are listed.

// --- Logic to Handle Worker Submission Approval/Rejection (Mocked) ---
$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submission_id'], $_POST['action'])) {
    $submissionId = (int)$_POST['submission_id'];
    $action = $_POST['action'];

    // Mock logic for payment based on a fictional submission ID and worker ID
    $mockWorkerId = 101; 
    $mockRewardRwf = 500.00;

    try {
        $pdo->beginTransaction();
        
        if ($action == 'approve') {
            // Mock: Pay the worker and notify
            $balanceUpdateStmt = $pdo->prepare("UPDATE users SET balance_rwf = balance_rwf + ? WHERE id = ?");
            $balanceUpdateStmt->execute([$mockRewardRwf, $mockWorkerId]);
            
            $notificationMsg = "Your task submission (ID $submissionId) was approved! You earned " . number_format($mockRewardRwf, 2) . " RWF.";
            $notificationStmt = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'System')");
            $notificationStmt->execute([$mockWorkerId, $notificationMsg]);

            $message = "Submission #$submissionId approved. RWF " . number_format($mockRewardRwf, 2) . " paid to worker.";
            $messageType = 'success';

        } elseif ($action == 'reject') {
            // Mock: Notify rejection
            $notificationMsg = "Your task submission (ID $submissionId) was rejected. Please review the requirements.";
            $notificationStmt = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'System')");
            $notificationStmt->execute([$mockWorkerId, $notificationMsg]);

            $message = "Submission #$submissionId rejected. Worker notified.";
            $messageType = 'warning';
        }
        
        $pdo->commit();
    } catch (PDOException $e) {
         $pdo->rollBack();
         $message = "Database Error: Could not process worker submission.";
         $messageType = 'danger';
         error_log("Worker Approval Error: " . $e->getMessage());
    }
}

// --- Fetch Mock Pending Worker Submissions ---
// In a real system, this would query the 'worker_submissions' table where status='PendingAdmin'
$mockSubmissions = [
    ['id' => 123, 'worker_username' => 'Janine_W', 'task_type' => 'Article Writing', 'task_title' => 'Review of Latest Tech Gadgets', 'reward' => 800, 'submission_link' => '#'],
    ['id' => 124, 'worker_username' => 'Big_Boss', 'task_type' => 'Question Answering', 'task_title' => 'Fixing PHP PDO Error', 'reward' => 450, 'submission_link' => '#'],
    ['id' => 125, 'worker_username' => 'Cool_Dev', 'task_type' => 'Project Making', 'task_title' => 'Simple HTML Calculator', 'reward' => 2500, 'submission_link' => '#'],
];


// Reuse Admin HTML structure
require_once 'admin_dashboard.php'; 
?>

<main class="main-content">
    <div class="card p-4 shadow">
        <h1 class="text-dark">Approve Worker Submissions</h1>
        <p class="text-muted">Review articles, answers, and projects submitted by workers and approve payment upon successful completion.</p>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType; ?>"><?= htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if (count($mockSubmissions) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover mt-4">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Worker</th>
                            <th>Task Type</th>
                            <th>Task Title</th>
                            <th>Reward (RWF)</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mockSubmissions as $submission): ?>
                            <tr>
                                <td><?= $submission['id']; ?></td>
                                <td><?= htmlspecialchars($submission['worker_username']); ?></td>
                                <td><?= htmlspecialchars($submission['task_type']); ?></td>
                                <td><?= htmlspecialchars($submission['task_title']); ?></td>
                                <td><?= number_format($submission['reward'], 0); ?></td>
                                <td>
                                    <a href="<?= $submission['submission_link']; ?>" target="_blank" class="btn btn-sm btn-info me-1">View Submission</a>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="submission_id" value="<?= $submission['id']; ?>">
                                        <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">Approve & Pay</button>
                                    </form>
                                    <form method="POST" class="d-inline ms-1">
                                        <input type="hidden" name="submission_id" value="<?= $submission['id']; ?>">
                                        <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger">Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info mt-4">No pending worker submissions to review.</div>
        <?php endif; ?>
    </div>
</main>
