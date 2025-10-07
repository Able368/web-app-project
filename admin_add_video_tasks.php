<?php
// FILE: admin_add_video_tasks.php - Admin interface for adding SMM Worker Tasks

include_once 'db_connection.php';
include_once 'functions.php';

global $pdo;

if (!is_admin()) {
    header("Location: dashboard.php");
    exit;
}

// Handle Task Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['platform'])) {
    $platform = filter_input(INPUT_POST, 'platform', FILTER_SANITIZE_STRING);
    $service_type = filter_input(INPUT_POST, 'service_type', FILTER_SANITIZE_STRING);
    $target_link = filter_input(INPUT_POST, 'target_link', FILTER_SANITIZE_URL);
    // Use FILTER_VALIDATE_FLOAT for correct payment processing
    $payment_rwf = filter_input(INPUT_POST, 'payment_rwf', FILTER_VALIDATE_FLOAT); 

    $title = "{$platform} {$service_type} Task";
    $description = "Please perform the required action on the link/ID provided to earn {$payment_rwf} RWF.";
    $task_type = $service_type; // Use service type as the task type

    // Check if the filtered payment is a valid number greater than zero
    if ($payment_rwf > 0 && !empty($target_link)) {
        
        $payment_rwf_db = (float)$payment_rwf; 
        
        // Start a transaction to ensure both inserts succeed or fail together
        $pdo->beginTransaction();
        
        try {
            // 1. Insert into the general 'tasks' table
            $stmt = $pdo->prepare("
                INSERT INTO tasks (title, description, task_type, payment_rwf, status) 
                VALUES (?, ?, ?, ?, 'Active')
            ");
            $stmt->execute([
                $title, 
                $description, 
                $task_type, 
                $payment_rwf_db
            ]);

            // Get the ID of the new task
            $task_id = $pdo->lastInsertId();

            // 2. Insert video-specific details into the new table
            $stmt_details = $pdo->prepare("
                INSERT INTO smm_video_details (task_id, platform, target_url)
                VALUES (?, ?, ?)
            ");
            $stmt_details->execute([
                $task_id,
                $platform, 
                $target_link
            ]);

            // Commit the transaction
            $pdo->commit();

            $_SESSION['message'] = "New **{$platform}** task (**{$service_type}**) created successfully!";
            $_SESSION['message_type'] = "success";
        } catch (PDOException $e) {
            // Rollback on error
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Task Creation Error: " . $e->getMessage());
            $_SESSION['message'] = "Database error during task creation. Check error logs. Error details: " . $e->getMessage();
            $_SESSION['message_type'] = "danger";
        }
    } else {
        $_SESSION['message'] = "Invalid payment amount or missing target link.";
        $_SESSION['message_type'] = "danger";
    }
    header("Location: admin_add_video_tasks.php");
    exit;
}

print_dashboard_header($pdo, "Admin Add Worker Tasks");
print_sidebar();
?>
<div class="main-content">
    <main class="dashboard-content">
        <div class="container-fluid">
            <h2 class="mb-4">➕ Create New SMM/Worker Task</h2>
            <p class="lead">Manually add tasks for workers to earn by watching, subscribing, or following.</p>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?= $_SESSION['message_type']; ?>"><?= $_SESSION['message']; ?></div>
                <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
            <?php endif; ?>

            <div class="card shadow">
                <div class="card-header bg-primary text-white">Task Details</div>
                <div class="card-body">
                    <form method="POST" action="admin_add_video_tasks.php">
                        
                        <div class="mb-3">
                            <label for="platform" class="form-label">Platform</label>
                            <select name="platform" id="platform" class="form-select" required>
                                <option value="" disabled selected>Select Platform</option>
                                <option value="YouTube">YouTube</option>
                                <option value="TikTok">TikTok</option>
                                <option value="Instagram">Instagram</option>
                                <option value="Facebook">Facebook</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="service_type" class="form-label">Service/Task Type</label>
                            <select name="service_type" id="service_type" class="form-select" required>
                                <option value="" disabled selected>Select Service Type</option>
                                <option value="Subscribe">Subscribe/Follow</option>
                                <option value="View">Views/Watchtime</option>
                                <option value="Like">Likes</option>
                                <option value="Comment">Comment</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="target_link" class="form-label">Target Link (Channel/Account URL or Video/Post URL)</label>
                            <input type="url" class="form-control" id="target_link" name="target_link" placeholder="e.g., https://youtube.com/channel/..." required>
                            <small class="form-text text-muted">Use the full link. This will be embedded or linked for the worker.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="payment_rwf" class="form-label">Reward per Worker (RWF)</label>
                            <input type="number" step="0.01" class="form-control" id="payment_rwf" name="payment_rwf" min="1" required>
                        </div>

                        <button type="submit" class="btn btn-success"><i class="fas fa-plus-circle"></i> Add Task for Workers</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>
<?php print_footer(); ?>