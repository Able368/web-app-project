<?php
// FILE: platform-workers.php

include_once 'db_connection.php';
include_once 'functions.php';

// Check if the user is logged in
if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_balance = 0.00; // Worker balance is tracked in 'balance_rwf'

// Fetch user's current balance
$stmt = $pdo->prepare("SELECT balance_rwf FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if ($user) {
    $user_balance = $user['balance_rwf'];
}

// Fetch active tasks from the new 'tasks' table
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE status = 'Active' ORDER BY payment_rwf DESC");
$stmt->execute();
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch the worker's pending/approved submissions for tracking
$stmt = $pdo->prepare("SELECT ws.task_id, ws.admin_status, t.title FROM worker_submissions ws JOIN tasks t ON ws.task_id = t.id WHERE ws.worker_id = ? ORDER BY ws.submission_date DESC");
$stmt->execute([$user_id]);
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
$submission_status_map = [];
foreach ($submissions as $sub) {
    $submission_status_map[$sub['task_id']] = $sub['admin_status'];
}

// --- FIX START: Use the correct header function and include sidebar ---
print_dashboard_header($pdo, "Worker Task Panel");
print_sidebar();
// --- FIX END ---
?>
<main class="main-content"> <div class="container-fluid">
        <h2 class="mb-4">Available Tasks for Workers</h2>
        <p class="lead">Your Earnings Balance: <strong class="text-success"><?= number_format($user_balance, 0); ?> RWF</strong></p>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['message_type']; ?>"><?= $_SESSION['message']; ?></div>
            <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
        <?php endif; ?>

        <div class="task-list mt-4">
            <?php if ($tasks): ?>
                <?php foreach ($tasks as $task): 
                    $task_id = $task['id'];
                    $status_int = $submission_status_map[$task_id] ?? -1;
                    
                    // NOTE: Using a match expression requires PHP 8.0 or higher. 
                    // Assuming you have PHP 8.0+, this is fine. If not, use an if/else block.
                    $status_text = match($status_int) {
                        0 => ['Pending Review', 'warning'],
                        1 => ['Approved (Paid)', 'success'],
                        2 => ['Rejected', 'danger'],
                        default => ['Available', 'primary'],
                    };
                ?>
                    <div class="card mb-3 shadow-sm border-<?= $status_text[1]; ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($task['title']); ?> (<?= htmlspecialchars($task['task_type']); ?>)</h5>
                            <p class="card-text">
                                **Reward:** <span class="text-success fw-bold"><?= number_format($task['payment_rwf'], 0); ?> RWF</span><br>
                                **Description:** <?= nl2br(htmlspecialchars($task['description'])); ?>
                            </p>
                            
                            <p class="mt-2">
                                **Your Status:** <span class="badge bg-<?= $status_text[1]; ?>"><?= $status_text[0]; ?></span>
                            </p>

                            <?php if ($status_int < 0 || $status_int == 2): ?>
                                <button class="btn btn-primary btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#submitModal<?= $task_id; ?>">
                                    <i class="fas fa-check-circle"></i> Submit Proof
                                </button>
                            <?php elseif ($status_int == 0): ?>
                                <button class="btn btn-secondary btn-sm mt-2" disabled>Awaiting Admin Review</button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="modal fade" id="submitModal<?= $task_id; ?>" tabindex="-1" aria-labelledby="submitModalLabel<?= $task_id; ?>" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" action="submit_task.php">
                                    <div class="modal-header bg-primary text-white">
                                        <h5 class="modal-title" id="submitModalLabel<?= $task_id; ?>">Submit Proof for: <?= htmlspecialchars($task['title']); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body text-dark">
                                        <input type="hidden" name="task_id" value="<?= $task_id; ?>">
                                        <p>You will earn <strong><?= number_format($task['payment_rwf'], 0); ?> RWF</strong> upon approval.</p>
                                        <div class="mb-3">
                                            <label for="submission_proof" class="form-label">Link to Proof (e.g., Google Drive, Screenshot URL)</label>
                                            <input type="url" class="form-control" name="submission_proof" required>
                                        </div>
                                        <p class="text-muted small">Ensure the link is accessible by the admin.</p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-success">Submit Task</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info">No active tasks are currently available. Check back soon!</div>
            <?php endif; ?>
        </div>
    </div>
    <a href="tasks-videos.php" class="btn btn-primary w-100 mb-3" style="background-color: #3498db; border: none;">Watch Videos and Earn</a>
    <a href="tasks-article.php" class="btn btn-primary w-100 mb-3" style="background-color: #3498db; border: none;">Writing books</a>
    <a href="tasks-projects.php" class="btn btn-primary w-100 mb-3" style="background-color: #3498db; border: none;">Make projects</a>
    <a href="tasks-questions.php" class="btn btn-primary w-100 mb-3" style="background-color: #3498db; border: none;">Answer Questions</a>
    
</main>
<?php print_footer(); ?>