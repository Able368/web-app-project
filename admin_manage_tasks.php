<?php
// FILE: admin_manage_tasks.php - Admin interface for posting/editing worker tasks

include_once 'db_connection.php';
include_once 'functions.php';
// Assuming $pdo is available from db_connection.php

if (!is_admin()) {
    header("Location: dashboard.php");
    exit;
}

$task_types = ['Book', 'Question', 'Project', 'Video Engagement'];

// Handle Task Addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    // Sanitizing and validating inputs
    $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $payment_rwf = filter_input(INPUT_POST, 'payment_rwf', FILTER_VALIDATE_FLOAT);
    $task_type = filter_input(INPUT_POST, 'task_type', FILTER_SANITIZE_STRING);

    if ($title && $description && $payment_rwf !== false && $payment_rwf > 0 && in_array($task_type, $task_types)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO tasks (title, description, payment_rwf, task_type, status) VALUES (?, ?, ?, ?, 'Active')");
            $stmt->execute([$title, $description, $payment_rwf, $task_type]);
            $_SESSION['message'] = "New task '{$title}' added successfully.";
            $_SESSION['message_type'] = "success";
        } catch (PDOException $e) {
            error_log("Task Add Error: " . $e->getMessage());
            $_SESSION['message'] = "An error occurred while adding the task.";
            $_SESSION['message_type'] = "danger";
        }
    } else {
        $_SESSION['message'] = "Invalid task details provided. Ensure Title, Description, Payout, and Type are valid.";
        $_SESSION['message_type'] = "danger";
    }
    header("Location: admin_manage_tasks.php");
    exit;
}

// Fetch all existing tasks
$stmt_tasks = $pdo->query("SELECT * FROM tasks ORDER BY created_at DESC");
$tasks = $stmt_tasks->fetchAll(PDO::FETCH_ASSOC);

// FIX: Replace print_header() with the correctly named function print_dashboard_header($pdo, $title)
print_dashboard_header($pdo, "Admin Task Management");
print_sidebar(); 
?>

<div class="main-content">
    <main class="dashboard-content">
        <div class="container-fluid">
            <h2 class="mb-4">Manage Worker Tasks</h2>
            <p class="lead">Add and review the jobs available for users to earn money.</p>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?= $_SESSION['message_type']; ?>"><?= $_SESSION['message']; ?></div>
                <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
            <?php endif; ?>

            <button class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#addTaskModal">
                <i class="fas fa-plus"></i> Add New Task
            </button>

            <div class="card shadow">
                <div class="card-header">Existing Tasks</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Payout (RWF)</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tasks as $task): ?>
                                <tr>
                                    <td><?= $task['id']; ?></td>
                                    <td><?= htmlspecialchars($task['title']); ?></td>
                                    <td><?= htmlspecialchars($task['task_type']); ?></td>
                                    <td><?= number_format($task['payment_rwf'], 0); ?></td>
                                    <td><span class="badge bg-<?= $task['status'] == 'Active' ? 'success' : 'secondary'; ?>"><?= $task['status']; ?></span></td>
                                    <td>
                                        <button class="btn btn-sm btn-info">Edit</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<div class="modal fade" id="addTaskModal" tabindex="-1" aria-labelledby="addTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="admin_manage_tasks.php">
                <input type="hidden" name="action" value="add">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addTaskModalLabel">Add New Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-dark">
                    <div class="mb-3">
                        <label for="task_type" class="form-label">Task Type</label>
                        <select class="form-control" name="task_type" required>
                            <?php foreach ($task_types as $type): ?>
                                <option value="<?= $type; ?>"><?= $type; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="title" class="form-label">Task Title</label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description (Full instructions)</label>
                        <textarea class="form-control" name="description" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="payment_rwf" class="form-label">Worker Payout (RWF)</label>
                        <input type="number" step="1" min="1" class="form-control" name="payment_rwf" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success">Save Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php print_footer(); ?>