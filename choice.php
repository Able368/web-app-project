<?php
// choice.php

require_once 'functions.php';
require_once 'db_connection.php';
check_session(); // Maintain the session [cite: 489]

$userData = get_user_data($pdo, $_SESSION['user_id']);
$username = $userData['username'] ?? 'N/A';
$email = $userData['email'] ?? 'N/A';

print_simple_header("Choice Page");
?>

<div class="content-container">
    <div class="description">
        <h2>ZamuraMedia Platform</h2>
        <p class="h5"><strong>Grow Your Social Medias:</strong> Boost your online presence with high-quality followers and engagement strategies.</p>
        <p class="h5"><strong>Earn Money as a Worker:</strong> Complete simple online tasks and get paid directly to your account.</p>
    </div>
    <div class="login-form" style="background-color: #34495e; color: white;">
        <h2 class="text-white">Welcome!</h2>
        <p><strong>Username:</strong> <?= htmlspecialchars($username); ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($email); ?></p>
        <p>Select an option below:</p>
        <a href="grow-social-media.php" class="btn btn-primary w-100 mb-3" style="background-color: #3498db; border: none;">Grow Your Social Media Platforms</a>
        <a href="platform-workers.php" class="btn btn-outline-light w-100">Earn Money as a Worker</a>
        
        <a href="login.php?logout=true" class="btn btn-sm btn-danger w-100 mt-4">Logout</a>
    </div>
</div>

<?php
print_simple_footer();
?>