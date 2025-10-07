<?php
// admin_set_costs.php

require_once 'functions.php';
require_once 'db_connection.php';

// --- ADMIN SESSION CHECK ---
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header("Location: admin_login.php");
    exit;
}

// --- Logic to Handle Cost Management (Add/Update/Delete) ---
$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];

    try {
        if ($action == 'add' || $action == 'update') {
            $platform = $_POST['platform'];
            $service_type = $_POST['service_type'];
            $unit_count = (int)$_POST['unit_count'];
            $cost_rwf = (float)$_POST['cost_rwf'];
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            if ($action == 'add') {
                $stmt = $pdo->prepare("INSERT INTO services (platform, service_type, unit_count, cost_rwf, is_active) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$platform, $service_type, $unit_count, $cost_rwf, $is_active]);
                $message = "New service cost added successfully.";
            } elseif ($action == 'update' && isset($_POST['service_id'])) {
                $serviceId = (int)$_POST['service_id'];
                $stmt = $pdo->prepare("UPDATE services SET platform=?, service_type=?, unit_count=?, cost_rwf=?, is_active=? WHERE id=?");
                $stmt->execute([$platform, $service_type, $unit_count, $cost_rwf, $is_active, $serviceId]);
                $message = "Service #$serviceId updated successfully.";
            }
            $messageType = 'success';
        } elseif ($action == 'delete' && isset($_POST['service_id'])) {
            $serviceId = (int)$_POST['service_id'];
            $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
            $stmt->execute([$serviceId]);
            $message = "Service #$serviceId deleted.";
            $messageType = 'warning';
        }
    } catch (PDOException $e) {
        $message = "Database Error: Could not manage service costs. " . $e->getMessage();
        $messageType = 'danger';
        error_log("Service Cost Management Error: " . $e->getMessage());
    }
}

// --- Fetch All Service Costs ---
try {
    $stmt = $pdo->prepare("SELECT * FROM services ORDER BY platform, service_type");
    $stmt->execute();
    $allServices = $stmt->fetchAll();
} catch (PDOException $e) {
    $allServices = [];
    $message = (isset($message) ? $message . ' / ' : '') . "Error fetching services.";
    $messageType = 'danger';
}

// Reuse Admin HTML structure
require_once 'admin_dashboard.php'; 
?>

<main class="main-content">
    <div class="card p-4 shadow">
        <h1 class="text-dark">Set Service Costs</h1>
        <p class="text-muted">Manage the pricing, units, and platforms for all social media boosting services.</p>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType; ?>"><?= htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- Add New Service Form -->
        <div class="mb-5 p-4 border rounded bg-light">
            <h4 class="text-dark">Add New Service</h4>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="row g-3">
                    <div class="col-md-3">
                        <select name="platform" class="form-select" required>
                            <option value="">Select Platform</option>
                            <option value="YouTube">YouTube</option>
                            <option value="TikTok">TikTok</option>
                            <option value="Instagram">Instagram</option>
                            <option value="Twitter">Twitter</option>
                            <option value="Facebook">Facebook</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="service_type" class="form-control" placeholder="Service Name (e.g., Views, Subscribers)" required>
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="unit_count" class="form-control" placeholder="Unit Count (e.g., 1000)" required>
                    </div>
                    <div class="col-md-2">
                        <input type="number" step="0.01" name="cost_rwf" class="form-control" placeholder="Cost (RWF)" required>
                    </div>
                     <div class="col-md-2 d-flex align-items-center">
                        <div class="form-check form-switch me-3">
                            <input class="form-check-input" type="checkbox" role="switch" name="is_active" checked>
                            <label class="form-check-label text-dark">Active</label>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Add</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Current Services Table -->
        <h4 class="text-dark mt-5">Existing Service Costs</h4>
        <?php if (count($allServices) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover mt-3">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Platform</th>
                            <th>Type</th>
                            <th>Units</th>
                            <th>Cost (RWF)</th>
                            <th>Active</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allServices as $service): ?>
                            <tr>
                                <form method="POST">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="service_id" value="<?= $service['id']; ?>">
                                    <td><?= $service['id']; ?></td>
                                    <td><input type="text" name="platform" value="<?= htmlspecialchars($service['platform']); ?>" class="form-control form-control-sm w-100" required></td>
                                    <td><input type="text" name="service_type" value="<?= htmlspecialchars($service['service_type']); ?>" class="form-control form-control-sm w-100" required></td>
                                    <td><input type="number" name="unit_count" value="<?= htmlspecialchars($service['unit_count']); ?>" class="form-control form-control-sm w-100" required></td>
                                    <td><input type="number" step="0.01" name="cost_rwf" value="<?= htmlspecialchars($service['cost_rwf']); ?>" class="form-control form-control-sm w-100" required></td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" name="is_active" <?= $service['is_active'] ? 'checked' : ''; ?>>
                                        </div>
                                    </td>
                                    <td>
                                        <button type="submit" class="btn btn-sm btn-success me-1">Save</button>
                                        <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger">Delete</button>
                                    </td>
                                </form>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-warning mt-4">No services are currently defined.</div>
        <?php endif; ?>
    </div>
</main>