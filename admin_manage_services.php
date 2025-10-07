<?php
// FILE: admin_manage_services.php - Admin interface for SMM service pricing (Buyers)

include_once 'db_connection.php';
include_once 'functions.php';

// Assuming $pdo is available from db_connection.php

if (!is_admin()) {
    header("Location: dashboard.php");
    exit;
}

$platforms = ['YouTube', 'TikTok', 'Instagram', 'Twitter', 'Facebook'];

// Handle Service Addition/Update (Simplified CRUD)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $platform = filter_input(INPUT_POST, 'platform', FILTER_SANITIZE_STRING);
    $service_type = filter_input(INPUT_POST, 'service_type', FILTER_SANITIZE_STRING);
    // Use FILTER_VALIDATE_INT to ensure it's a valid integer
    $unit_count = filter_input(INPUT_POST, 'unit_count', FILTER_VALIDATE_INT);
    // Use FILTER_VALIDATE_FLOAT to ensure it's a valid float (currency)
    $cost_rwf = filter_input(INPUT_POST, 'cost_rwf', FILTER_VALIDATE_FLOAT);

    if (in_array($platform, $platforms) && $service_type && $unit_count !== false && $unit_count > 0 && $cost_rwf !== false && $cost_rwf > 0) {
        try {
            // Using prepared statement for insertion
            $stmt = $pdo->prepare("INSERT INTO services (platform, service_type, unit_count, cost_rwf, is_active) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$platform, $service_type, $unit_count, $cost_rwf]);
            $_SESSION['message'] = "New SMM service added successfully.";
            $_SESSION['message_type'] = "success";
        } catch (PDOException $e) {
            error_log("Service Add Error: " . $e->getMessage());
            $_SESSION['message'] = "An error occurred while adding the service.";
            $_SESSION['message_type'] = "danger";
        }
    } else {
        $_SESSION['message'] = "Invalid service details provided. Check all fields.";
        $_SESSION['message_type'] = "danger";
    }
    header("Location: admin_manage_services.php");
    exit;
}

// Fetch all existing SMM services
$stmt_services = $pdo->query("SELECT * FROM services ORDER BY platform, unit_count ASC");
$services = $stmt_services->fetchAll(PDO::FETCH_ASSOC);

// --- FIX FOR FATAL ERROR: START ---

// 1. Call the correct function: print_dashboard_header()
print_dashboard_header($pdo, "Admin SMM Service Management");

// 2. Add the sidebar
print_sidebar();
?>
<div class="main-content">
    <main class="dashboard-content">
        <div class="container-fluid">
            <h2 class="mb-4">Manage SMM Services (Buyer Pricing)</h2>
            <p class="lead">Add and review social media packages that users can purchase.</p>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?= $_SESSION['message_type']; ?>"><?= $_SESSION['message']; ?></div>
                <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
            <?php endif; ?>

            <button class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                <i class="fas fa-plus"></i> Add New SMM Service
            </button>

            <div class="card shadow">
                <div class="card-header">Existing SMM Services</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Platform</th>
                                    <th>Service Type</th>
                                    <th>Units</th>
                                    <th>Price (RWF)</th>
                                    <th>Active</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($services as $service): ?>
                                <tr>
                                    <td><?= htmlspecialchars($service['platform']); ?></td>
                                    <td><?= htmlspecialchars($service['service_type']); ?></td>
                                    <td><?= number_format($service['unit_count'], 0); ?></td>
                                    <td><?= number_format($service['cost_rwf'], 0); ?></td>
                                    <td><span class="badge bg-<?= $service['is_active'] ? 'success' : 'secondary'; ?>"><?= $service['is_active'] ? 'Yes' : 'No'; ?></span></td>
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
<div class="modal fade" id="addServiceModal" tabindex="-1" aria-labelledby="addServiceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="admin_manage_services.php">
                <input type="hidden" name="action" value="add">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addServiceModalLabel">Add New SMM Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-dark">
                    <div class="mb-3">
                        <label for="platform" class="form-label">Platform</label>
                        <select class="form-control" name="platform" required>
                            <?php foreach ($platforms as $plat): ?>
                                <option value="<?= $plat; ?>"><?= $plat; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="service_type" class="form-label">Service Type (e.g., Followers, Views, Likes)</label>
                        <input type="text" class="form-control" name="service_type" required>
                    </div>
                    <div class="mb-3">
                        <label for="unit_count" class="form-label">Number of Units (e.g., 1000)</label>
                        <input type="number" step="1" min="1" class="form-control" name="unit_count" required>
                    </div>
                    <div class="mb-3">
                        <label for="cost_rwf" class="form-label">Price User Pays (RWF)</label>
                        <input type="number" step="10" min="1" class="form-control" name="cost_rwf" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success">Save Service</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php print_footer(); ?>