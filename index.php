<?php
// index.php

require_once 'functions.php';

print_simple_header("Welcome to ZamuraMedia");
?>

<div class="content-container">
    <div class="description">
        <h2>Welcome to ZamuraMedia</h2>
        <p>ZamuraMedia is a platform dedicated to helping you grow your online presence. Join us and experience the best services tailored to your needs.</p>
        <p><strong>Grow Your Social Medias:</strong> Boost your online presence with high-quality followers and engagement strategies.</p>
        <p><strong>Earn Money as a Worker:</strong> Complete simple online tasks and get paid directly to your account.</p>
    </div>
    <div class="login-form">
        <h2>Get Started</h2>
        <p>Choose an option below to access your account or create a new one:</p>
        <a href="login.php" class="btn btn-primary w-100 mb-3" style="background-color: #3498db; border: none;">Login</a>
        <a href="signup.php" class="btn btn-outline-secondary w-100">Sign Up</a>
    </div>
</div>

<?php
print_simple_footer();
?>