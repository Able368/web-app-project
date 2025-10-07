<?php
// FILE: submit_task.php - Handles worker task submissions

session_start();
include_once 'db_connection.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $worker_id = $_SESSION['user_id'];
    $task_id = filter_input(INPUT_POST, 'task_id', FILTER_SANITIZE_NUMBER_INT);
    $submission_proof = filter_input(INPUT_POST, 'submission_proof', FILTER_SANITIZE_URL);

    if (!$task_id || !$submission_proof) {
        $_SESSION['message'] = "Invalid task submission details.";
        $_SESSION['message_type'] = "danger";
        header("Location: platform-workers.php");
        exit;
    }

    try {
        // 1. Check if the worker has already submitted this task (Optional, but good for anti-spam)
        $stmt = $pdo->prepare("SELECT id FROM worker_submissions WHERE worker_id = ? AND task_id = ? AND admin_status = 0");
        $stmt->execute([$worker_id, $task_id]);
        if ($stmt->fetch()) {
            $_SESSION['message'] = "You already have a pending submission for this task.";
            $_SESSION['message_type'] = "warning";
            header("Location: platform-workers.php");
            exit;
        }

        // 2. Insert the submission into the new 'worker_submissions' table
        $stmt = $pdo->prepare("INSERT INTO worker_submissions (worker_id, task_id, submission_proof, admin_status) 
                               VALUES (?, ?, ?, 0)"); // 0 = Pending Review
        $stmt->execute([$worker_id, $task_id, $submission_proof]);

        $_SESSION['message'] = "Task submitted successfully! Awaiting admin review for payment.";
        $_SESSION['message_type'] = "success";

    } catch (Exception $e) {
        error_log("Task Submission Error: " . $e->getMessage());
        $_SESSION['message'] = "An error occurred while submitting your task. Please try again later.";
        $_SESSION['message_type'] = "danger";
    }

    header("Location: platform-workers.php");
    exit;
} else {
    header("Location: platform-workers.php");
    exit;
}
?>







