<?php
// create_admin.php - TEMPORARY SCRIPT FOR ADMIN SETUP
require_once 'db_connection.php'; // Make sure this connects to your database

// **1. CHOOSE A STRONG PASSWORD**
$plain_password = "YourSecurePasswordHere"; 
$admin_username = "sysadmin";
$admin_email = "admin@yourdomain.com";

// **2. CREATE THE SECURE HASH** (Uses the highly secure bcrypt algorithm by default)
$password_hash = password_hash($plain_password, PASSWORD_DEFAULT); 

// **3. INSERT INTO DATABASE**
try {
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, is_admin) VALUES (?, ?, ?, 1)");
    $stmt->execute([$admin_username, $admin_email, $password_hash]);

    echo "Admin user **" . htmlspecialchars($admin_username) . "** created successfully!<br>";
    echo "Password Hash: " . htmlspecialchars($password_hash);
} catch (PDOException $e) {
    // Handle case where username/email already exists or other error
    echo "Error creating admin user: " . $e->getMessage();
}
?>