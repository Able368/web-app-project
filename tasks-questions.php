<?php
// FILE: tasks-questions.php - Answer Questions/Surveys

include_once 'db_connection.php';
include_once 'functions.php';

global $pdo;

check_session();
$user_id = $_SESSION['user_id'];

// 1. Fetch user's current balance (for display)
$stmt = $pdo->prepare("SELECT balance_rwf FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$user_balance = $user['balance_rwf'] ?? 0.00;

// 2. Fetch Q&A Tasks
$stmt = $pdo->prepare("
    SELECT t.*, ws.admin_status
    FROM tasks t
    LEFT JOIN worker_submissions ws ON t.id = ws.task_id AND ws.worker_id = ?
    WHERE t.status = 'Active' AND t.task_type IN ('Survey', 'Q&A', 'Micro Task') 
    ORDER BY t.payment_rwf DESC
");
$stmt->execute([$user_id]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

print_dashboard_header($pdo, "Earn by Answering Questions");
print_sidebar();
?>
<div class="main-content">
    <div class="container-fluid">
        <h2 class="mb-4">❓ Quick Q&A and Survey Tasks</h2>
        <p class="lead">Your Earnings Balance: <strong class="text-success"><?= number_format($user_balance, 0); ?> RWF</strong></p>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['message_type']; ?>"><?= $_SESSION['message']; ?></div>
            <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
        <?php endif; ?>

        <div class="task-list mt-4">
            <?php if ($tasks): ?>
                <?php foreach ($tasks as $task): 
                    $task_id = $task['id'];
                    $status_int = $task['admin_status'] ?? -1; 
                    
                    $status_info = match($status_int) {
                        0 => ['Pending Review', 'warning', 'Awaiting Admin Review'],
                        1 => ['Completed', 'success', 'You were paid for this task.'],
                        2 => ['Rejected', 'danger', 'You can submit again.'],
                        default => ['Available', 'primary', 'Click to answer the question.'],
                    };
                ?>
                    <div class="card mb-3 shadow-sm border-<?= $status_info[1]; ?>">
                        <div class="card-body text-dark">
                            <h5 class="card-title"><?= htmlspecialchars($task['title']); ?> (<?= htmlspecialchars($task['task_type']); ?>)</h5>
                            <p class="card-text">
                                **Reward:** <span class="text-success fw-bold"><?= number_format($task['payment_rwf'], 0); ?> RWF</span><br>
                                **Task:** <?= nl2br(htmlspecialchars($task['description'])); ?>
                            </p>
                            
                            <p class="mt-2">
                                **Your Status:** <span class="badge bg-<?= $status_info[1]; ?>"><?= $status_info[0]; ?></span>
                            </p>

                            <?php if ($status_int < 1): ?>
                                <button class="btn btn-primary btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#submitModal<?= $task_id; ?>">
                                    <i class="fas fa-comment-dots"></i> Submit Answer
                                </button>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-sm mt-2" disabled><?= $status_info[2]; ?></button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="modal fade" id="submitModal<?= $task_id; ?>" tabindex="-1" aria-labelledby="submitModalLabel<?= $task_id; ?>" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" action="submit_task.php">
                                    <div class="modal-header bg-primary text-white">
                                        <h5 class="modal-title">Submit Answer for: <?= htmlspecialchars($task['title']); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body text-dark">
                                        <input type="hidden" name="task_id" value="<?= $task_id; ?>">
                                        <p>Provide your answer or proof of completion below.</p>
                                        <div class="mb-3">
                                            <label for="submission_proof" class="form-label">Your Answer/Proof Link</label>
                                            <textarea class="form-control" name="submission_proof" rows="4" required></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-success">Submit Answer</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info">No active Q&A tasks are currently available.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php print_footer(); ?>