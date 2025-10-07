<?php
// process_recharge.php

require_once 'functions.php';
check_session(); 

$userId = $_SESSION['user_id'];
$message = 'Recharge request submission failed.';
$messageType = 'danger';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['amount'], $_POST['currency'], $_POST['user_contact'])) {
    $amount = (float)$_POST['amount'];
    $currency = $_POST['currency'];
    $contact = trim($_POST['user_contact']);
    $proof_path = '';

    if (!in_array($currency, ['RWF', 'USD']) || $amount <= 0 || empty($contact)) {
        $message = "Invalid input or missing required fields.";
    } else {
        // --- File Upload Handling ---
        if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] == 0) {
            $file = $_FILES['screenshot'];
            $fileSize = $file['size']; // Already checked on client side, but re-check
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

            if ($fileSize > 512000) { // 500KB limit
                $message = "File size exceeds the 500kbits limit.";
            } elseif (!in_array($file['type'], $allowedTypes)) {
                $message = "Only JPG, PNG, and GIF images are allowed for proof.";
            } else {
                // Securely rename and move the file
                $uploadDir = 'uploads/recharge_proofs/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $fileName = $userId . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
                $proof_path = $uploadDir . $fileName;

                if (move_uploaded_file($file['tmp_name'], $proof_path)) {
                    // --- Database Insertion ---
                    try {
                        $stmt = $pdo->prepare("INSERT INTO recharges (user_id, amount, currency, phone_or_wallet, proof_screenshot, is_approved) VALUES (?, ?, ?, ?, ?, 0)");
                        $stmt->execute([$userId, $amount, $currency, $contact, $proof_path]);

                        $message = "Recharge request submitted successfully! Please wait for admin approval.";
                        $messageType = 'success';
                    } catch (PDOException $e) {
                        $message = "Database error: Could not submit request.";
                        error_log("Recharge Submission Error: " . $e->getMessage());
                    }
                } else {
                    $message = "File upload failed. Check folder permissions.";
                }
            }
        } else {
            $message = "Payment screenshot is required.";
        }
    }
}

// Redirect back to the dashboard with the status message
$_SESSION['status_message'] = $message;
$_SESSION['status_type'] = $messageType;
header("Location: grow-social-media.php");
exit;