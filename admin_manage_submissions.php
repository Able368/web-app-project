<?php
// FILE: admin_manage_submissions.php - Admin interface for approving worker submissions

include_once 'db_connection.php';
include_once 'functions.php';

// Assume $pdo is available from db_connection.php and functions.php includes it.

if (!is_admin()) {
    header("Location: dashboard.php");
    exit;
}

// Handle Approval/Rejection Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submission_id'])) {
    $submission_id = filter_input(INPUT_POST, 'submission_id', FILTER_SANITIZE_NUMBER_INT);
    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING); // 'approve' or 'reject'
    
    try {
        $pdo->beginTransaction();

        // 1. Fetch submission details and lock row
        $stmt = $pdo->prepare("SELECT ws.worker_id, t.payment_rwf, ws.admin_status, t.title 
                               FROM worker_submissions ws
                               JOIN tasks t ON ws.task_id = t.id
                               WHERE ws.id = ? AND ws.admin_status = 0 FOR UPDATE");
        $stmt->execute([$submission_id]);
        $submission = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$submission) {
            $pdo->rollBack();
            $_SESSION['message'] = "Submission not found or already processed.";
            $_SESSION['message_type'] = "warning";
            header("Location: admin_manage_submissions.php");
            exit;
        }

        $worker_id = $submission['worker_id'];
        $payment_rwf = $submission['payment_rwf'];
        $task_title = $submission['title'];

        if ($action === 'approve') {
            // 2. Approve submission (admin_status = 1)
            $stmt = $pdo->prepare("UPDATE worker_submissions SET admin_status = 1, admin_approved_at = NOW() WHERE id = ?");
            $stmt->execute([$submission_id]);

            // 3. Credit worker's balance
            $stmt = $pdo->prepare("UPDATE users SET balance_rwf = balance_rwf + ? WHERE id = ?");
            $stmt->execute([$payment_rwf, $worker_id]);

            // 4. Notify worker
            $message = "Your submission for the task '{$task_title}' was **APPROVED** and you have been credited " . number_format($payment_rwf) . " RWF.";
            $stmt_notif = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'System')");
            $stmt_notif->execute([$worker_id, $message]);

            $_SESSION['message'] = "Worker payment of " . number_format($payment_rwf) . " RWF approved and credited.";
            $_SESSION['message_type'] = "success";

        } elseif ($action === 'reject') {
            // 2. Reject submission (admin_status = 2)
            $stmt = $pdo->prepare("UPDATE worker_submissions SET admin_status = 2 WHERE id = ?");
            $stmt->execute([$submission_id]);
            
            // 3. Notify worker of rejection
            $message = "Your submission for the task '{$task_title}' was **REJECTED**. Please review the requirements.";
            $stmt_notif = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'System')");
            $stmt_notif->execute([$worker_id, $message]);

            $_SESSION['message'] = "Submission rejected for task '{$task_title}'.";
            $_SESSION['message_type'] = "warning";
        }

        $pdo->commit();

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Submission Approval Error: " . $e->getMessage());
        $_SESSION['message'] = "An error occurred during processing.";
        $_SESSION['message_type'] = "danger";
    }

    header("Location: admin_manage_submissions.php");
    exit;
}

// Fetch all pending submissions
$stmt_submissions = $pdo->query("
    SELECT ws.id, ws.submission_proof, ws.submission_date, u.username, t.title, t.payment_rwf 
    FROM worker_submissions ws
    JOIN users u ON ws.worker_id = u.id
    JOIN tasks t ON ws.task_id = t.id
    WHERE ws.admin_status = 0 
    ORDER BY ws.submission_date ASC
");
$pending_submissions = $stmt_submissions->fetchAll(PDO::FETCH_ASSOC);

// --- START OF FIX ---
// Replace the undefined print_header() with the correct print_dashboard_header() and print_sidebar().
print_dashboard_header($pdo, "Admin Submission Review");
print_sidebar();
?>
<div class="main-content">
    <main class="dashboard-content">
        <div class="container-fluid">
            <h2 class="mb-4">Review Worker Submissions</h2>
            <p class="lead">Approve or reject pending proofs to credit workers' accounts.</p>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?= $_SESSION['message_type']; ?>"><?= $_SESSION['message']; ?></div>
                <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
            <?php endif; ?>

            <?php if ($pending_submissions): ?>
                <div class="row row-cols-1 row-cols-md-2 g-4">
                    <?php foreach ($pending_submissions as $sub): ?>
                        <div class="col">
                            <div class="card shadow-sm border-info h-100">
                                <div class="card-body">
                                    <h5 class="card-title text-info"><?= htmlspecialchars($sub['title']); ?></h5>
                                    <p class="card-text">
                                        **Worker:** <?= htmlspecialchars($sub['username']); ?><br>
                                        **Payout:** <span class="text-success fw-bold"><?= number_format($sub['payment_rwf'], 0); ?> RWF</span><br>
                                        **Submitted:** <?= date('Y-m-d H:i', strtotime($sub['submission_date'])); ?><br>
                                    </p>
                                    <a href="<?= htmlspecialchars($sub['submission_proof']); ?>" target="_blank" class="btn btn-outline-info btn-sm mb-3">
                                        <i class="fas fa-external-link-alt"></i> View Proof
                                    </a>

                                    <form method="POST" action="admin_manage_submissions.php" class="d-inline">
                                        <input type="hidden" name="submission_id" value="<?= $sub['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-success btn-sm">
                                            <i class="fas fa-check"></i> Approve & Pay
                                        </button>
                                    </form>
                                    <form method="POST" action="admin_manage_submissions.php" class="d-inline ms-2">
                                        <input type="hidden" name="submission_id" value="<?= $sub['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">No worker submissions are currently awaiting review.</div>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php print_footer(); ?>