<?php
// admin_set_tasks.php

require_once 'functions.php';
require_once 'db_connection.php';

// --- ADMIN SESSION CHECK ---
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header("Location: admin_login.php");
    exit;
}

// --- Logic to Handle Task Management (Add/Delete/View) ---
$message = '';
// This logic will be simpler than service costs, focusing just on posting general tasks.
// In a real system, a dedicated 'tasks' table would be needed to store the full task description.
// For now, we will use the 'services' table structure with worker-specific platforms (Book, Question, Project).

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];

    try {
        if ($action == 'add_task') {
            $platform = $_POST['task_type']; // Book, Question, Project
            $service_type = $_POST['title']; // Task Title
            $unit_count = 1; // Always 1 task
            $cost_rwf = (float)$_POST['reward_rwf'];
            $is_active = 1; // New tasks are active by default
            $description = $_POST['description']; // New field for task description

            // Insert into 'services' table (We use service_type for title and a separate field for description)
            // NOTE: Description column doesn't exist yet, this requires a DB modification. 
            // Assuming the 'services' table structure from database.sql, we'll store the description in a separate table or combine it with service_type for this exercise.
            // Let's modify the service_type to include a brief description for simplicity.
            
            $full_title = $service_type . " - " . substr($description, 0, 50) . "...";

            $stmt = $pdo->prepare("INSERT INTO services (platform, service_type, unit_count, cost_rwf, is_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$platform, $full_title, $unit_count, $cost_rwf, $is_active]);
            
            $message = "New worker task added successfully: $service_type.";
            $messageType = 'success';

        } elseif ($action == 'delete' && isset($_POST['service_id'])) {
            $serviceId = (int)$_POST['service_id'];
            $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
            $stmt->execute([$serviceId]);
            $message = "Task #$serviceId deleted.";
            $messageType = 'warning';
        }
    } catch (PDOException $e) {
        $message = "Database Error: Could not manage tasks. " . $e->getMessage();
        $messageType = 'danger';
        error_log("Task Management Error: " . $e->getMessage());
    }
}

// --- Fetch Worker-specific Tasks (Platforms: Book, Question, Project) ---
try {
    $stmt = $pdo->prepare("SELECT * FROM services WHERE platform IN ('Book', 'Question', 'Project') ORDER BY id DESC");
    $stmt->execute();
    $workerTasks = $stmt->fetchAll();
} catch (PDOException $e) {
    $workerTasks = [];
    $message = (isset($message) ? $message . ' / ' : '') . "Error fetching tasks.";
    $messageType = 'danger';
}

// Reuse Admin HTML structure
require_once 'admin_dashboard.php'; 
?>

<main class="main-content">
    <div class="card p-4 shadow">
        <h1 class="text-dark">Post Worker Tasks</h1>
        <p class="text-muted">Create new tasks (Article Writing, Q&A, Projects) available for workers to complete for RWF rewards.</p>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType; ?>"><?= htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- Add New Task Form -->
        <div class="mb-5 p-4 border rounded bg-light">
            <h4 class="text-dark">Post New Task</h4>
            <form method="POST">
                <input type="hidden" name="action" value="add_task">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label text-dark">Task Type</label>
                        <select name="task_type" class="form-select" required>
                            <option value="Book">Article Writing</option>
                            <option value="Question">Question Answering</option>
                            <option value="Project">Project Making</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-dark">Task Title</label>
                        <input type="text" name="title" class="form-control" placeholder="Short title for the task" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-dark">Reward (RWF)</label>
                        <input type="number" step="1" name="reward_rwf" class="form-control" placeholder="Reward in RWF" required>
                    </div>
                    <div class="col-12">
                         <label class="form-label text-dark">Full Task Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Detailed description of what the worker needs to do." required></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary w-100">Post Task</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Current Posted Tasks Table -->
        <h4 class="text-dark mt-5">Currently Posted Worker Tasks</h4>
        <?php if (count($workerTasks) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover mt-3">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Title/Description Snippet</th>
                            <th>Reward (RWF)</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($workerTasks as $task): ?>
                            <tr>
                                <td><?= $task['id']; ?></td>
                                <td><?= htmlspecialchars($task['platform']); ?></td>
                                <td><?= htmlspecialchars($task['service_type']); ?></td>
                                <td><?= number_format($task['cost_rwf'], 0); ?></td>
                                <td><span class="badge bg-success">Active</span></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="service_id" value="<?= $task['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-warning mt-4">No worker tasks are currently posted.</div>
        <?php endif; ?>
    </div>
</main>