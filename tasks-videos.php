<?php
// FILE: tasks-videos.php - SMM/Video/Follow/Like Tasks (UPDATED)

include_once 'db_connection.php';
include_once 'functions.php';

global $pdo;

// Safely start the session directly (replaces the redundant check_session() function)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// 1. Fetch user's current balance
$stmt = $pdo->prepare("SELECT balance_rwf FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$user_balance = $user['balance_rwf'] ?? 0.00;

// 2. Process Task Submission (Marks task as Pending Review)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'complete_video') {
    $task_id = (int)$_POST['task_id'];

    // Check if user has already submitted this task (Pending or Approved)
    $stmt = $pdo->prepare("SELECT id FROM worker_submissions WHERE worker_id = ? AND task_id = ? AND admin_status IN (0, 1)");
    $stmt->execute([$user_id, $task_id]);
    
    if ($stmt->fetch()) {
        $_SESSION['message'] = "You've already submitted this task or it's been approved. You can only earn once.";
        $_SESSION['message_type'] = 'warning';
    } else {
        // Log the submission (Status 0 = Pending Review)
        try {
            // Note: Proof is currently a fixed string, but could be a screenshot URL in a more advanced system
            $proof = "SMM task claimed/completed by worker. Admin must verify via the link.";
            $stmt = $pdo->prepare("INSERT INTO worker_submissions (worker_id, task_id, submission_proof, admin_status) VALUES (?, ?, ?, 0)");
            $stmt->execute([$user_id, $task_id, $proof]);
            
            $_SESSION['message'] = "Task submitted! The admin will verify your follow/view/like and credit your account.";
            $_SESSION['message_type'] = 'success';
            
        } catch (PDOException $e) {
            error_log("SMM Task Submission Error: " . $e->getMessage());
            $_SESSION['message'] = "Database error during submission.";
            $_SESSION['message_type'] = 'danger';
        }
    }
    header("Location: tasks-videos.php");
    exit;
}

// 3. Fetch SMM Tasks (UPDATED TO INCLUDE JOIN and broadened WHERE clause)
$stmt = $pdo->prepare("
    SELECT 
        t.*, 
        ws.admin_status,
        smm.platform,       -- Fetched from the new details table
        smm.target_url      -- Fetched from the new details table
    FROM tasks t
    INNER JOIN smm_video_details smm ON t.id = smm.task_id  -- Only include tasks that have video details
    LEFT JOIN worker_submissions ws ON t.id = ws.task_id AND ws.worker_id = ?
    WHERE t.status = 'Active' 
    -- Broadened task_type criteria to include all possible video/SMM task types
    AND t.task_type IN ('Subscribe', 'View', 'Like', 'Comment', 'Follow', '4', 'Video Engagement') 
    ORDER BY t.payment_rwf DESC
");
$stmt->execute([$user_id]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper function to get YouTube embed ID
function get_youtube_embed_id($url) {
    if (strpos($url, 'youtube.com/watch') !== false) {
        parse_str(parse_url($url, PHP_URL_QUERY), $vars);
        return $vars['v'] ?? null;
    } elseif (strpos($url, 'youtu.be') !== false) {
        return basename(parse_url($url, PHP_URL_PATH));
    }
    return null;
}

print_dashboard_header($pdo, "Earn by SMM/Video Tasks");
print_sidebar();
?>
<div class="main-content">
    <div class="container-fluid">
        <h2 class="mb-4">🔗 SMM Tasks: Watch, Follow, Like, Subscribe</h2>
        <p class="lead">Your Earnings Balance: <strong class="text-success"><?= number_format($user_balance, 0); ?> RWF</strong></p>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['message_type']; ?>"><?= $_SESSION['message']; ?></div>
            <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
        <?php endif; ?>

        <div class="row task-list mt-4">
            <?php if ($tasks): ?>
                <?php foreach ($tasks as $task): 
                    $task_id = $task['id'];
                    $status_int = $task['admin_status'] ?? -1; 
                    // target_url and platform are now fetched from the JOIN
                    $url = htmlspecialchars($task['target_url']);
                    $platform = htmlspecialchars($task['platform']); 
                    
                    // Logic to display status
                    $status_info = match($status_int) {
                        0 => ['Pending Review', 'warning', 'Awaiting Admin Verification'],
                        1 => ['Paid (Completed)', 'success', 'You have been credited.'],
                        2 => ['Rejected', 'danger', 'Your submission was rejected.'],
                        default => ['Available', 'primary', 'Click to mark as completed after action.'],
                    };
                ?>
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow-sm border-<?= $status_info[1]; ?> h-100">
                            <div class="card-header bg-<?= $status_info[1]; ?> text-white">
                                <h5><?= htmlspecialchars($task['title']); ?> (Reward: <?= number_format($task['payment_rwf'], 0); ?> RWF)</h5>
                            </div>
                            <div class="card-body text-dark">
                                
                                <p class="card-text">
                                    **Platform:** <span class="badge bg-info"><?= $platform; ?></span> | 
                                    **Action:** <span class="badge bg-secondary"><?= htmlspecialchars($task['task_type']); ?></span>
                                </p>

                                <?php $youtube_id = get_youtube_embed_id($url); ?>
                                <?php if ($youtube_id && $platform == 'YouTube'): ?>
                                    <div class="ratio ratio-16x9 mb-3">
                                        <iframe src="https://www.youtube.com/embed/<?= $youtube_id; ?>" title="YouTube video" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-light text-center">
                                        <p class="mb-1">Click the link below to perform the **<?= $task['task_type']; ?>** action:</p>
                                        <a href="<?= $url; ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary mt-2">
                                            <i class="fas fa-external-link-alt"></i> Go to <?= $platform; ?> Link
                                        </a>
                                    </div>
                                <?php endif; ?>
                                
                                <p class="mt-2">
                                    **Your Status:** <span class="badge bg-<?= $status_info[1]; ?>"><?= $status_info[0]; ?></span>
                                </p>

                                <?php if ($status_int < 0 || $status_int == 2): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="task_id" value="<?= $task_id; ?>">
                                        <input type="hidden" name="action" value="complete_video">
                                        <!-- NOTE: We use a custom message box instead of confirm() in real web apps for better UX, but keeping the PHP confirm() check for immediate function. -->
                                        <button type="submit" class="btn btn-success btn-sm mt-2" onclick="return confirm('WARNING: Confirm you have successfully completed the action (viewed/subscribed/followed/liked). You can only earn from this task ONCE.');">
                                            <i class="fas fa-check-circle"></i> Submit & Claim <?= number_format($task['payment_rwf'], 0); ?> RWF
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-secondary btn-sm mt-2" disabled><?= $status_info[2]; ?></button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info">No active SMM/Video tasks are currently available. Check back soon!</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php print_footer(); ?>
