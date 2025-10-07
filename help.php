<?php
// help.php

require_once 'functions.php';

print_simple_header("Help & Support");
?>

<div class="content-container flex-column align-items-center">
    <div class="card p-5 w-75 bg-light text-dark shadow">
        <h2 class="text-center mb-4">System Help & User Guidance</h2>
        <h4 class="mt-3">System Description</h4>
        <p>ZamuraMedia is a dual-purpose platform for **growing social media** and **earning money as a worker**.</p>

        <h4 class="mt-4">How to Login or Signup</h4>
        <p><strong>1. Signup:</strong> Click the **Sign Up** button on the home page. Fill in the required fields. Note that the password must be strong (min 8 chars, Capital, small, special character) and your Full Name must only contain letters.</p>
        
        <p><strong>2. Login:</strong> Click the **Login** button. Enter your Username/Email and Password. You will be directed to the **Choice Page** to select your desired path.</p>
        
        <p class="mt-4 text-center">For technical issues, please use the **Support** link in the side menu once logged in.</p>
    </div>
</div>

<?php
print_simple_footer();
?>
