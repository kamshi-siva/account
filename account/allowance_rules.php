<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['restaurant_admin_id'])) {
    header("Location: index.php");
    exit();
}

$restaurant_id = $_SESSION['restaurant_id'];
$errors = [];
$success = '';

// Add or Update Rule
if (isset($_POST['save_rule'])) {
    $id = intval($_POST['id']);
    $min_sales = floatval($_POST['min_sales']);
    $max_sales = floatval($_POST['max_sales']);
    $percentage = floatval($_POST['percentage']);
    $fixed_amount = floatval($_POST['fixed_amount']);

    if ($min_sales < 0 || $max_sales <= 0 || ($percentage <= 0 && $fixed_amount <= 0)) {
        $errors[] = "Please enter valid sales range and allowance.";
    }

    if (empty($errors)) {
        if ($id > 0) {
            // Update
            $stmt = $conn->prepare("UPDATE cashier_allowance_rules SET min_sales=?, max_sales=?, percentage=?, fixed_amount=? WHERE id=? AND restaurant_id=?");
            $stmt->bind_param("ddddii", $min_sales, $max_sales, $percentage, $fixed_amount, $id, $restaurant_id);
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO cashier_allowance_rules (restaurant_id, min_sales, max_sales, percentage, fixed_amount) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("idddd", $restaurant_id, $min_sales, $max_sales, $percentage, $fixed_amount);
        }

        if ($stmt->execute()) {
            $success = "Allowance rule saved successfully.";
        } else {
            $errors[] = "Database error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Delete Rule
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM cashier_allowance_rules WHERE id=? AND restaurant_id=?");
    $stmt->bind_param("ii", $id, $restaurant_id);
    $stmt->execute();
    $stmt->close();
    $success = "Allowance rule deleted.";
}

// Fetch rules
$rules = $conn->query("SELECT * FROM cashier_allowance_rules WHERE restaurant_id=$restaurant_id ORDER BY min_sales ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Allowance Rules</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">

<div class="container">
    <h3>Cashier Allowance Rules</h3>

    <?php if ($errors): ?>
        <div class="alert alert-danger"><?= implode('<br>', $errors) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <!-- Add/Edit Form -->
    <div class="card mb-4">
        <div class="card-header">Add / Edit Rule</div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="id" id="rule_id" value="0">
                <div class="row mb-2">
                    <div class="col">
                        <label>Min Sales</label>
                        <input type="number" name="min_sales" id="min_sales" step="0.01" class="form-control" required>
                    </div>
                    <div class="col">
                        <label>Max Sales</label>
                        <input type="number" name="max_sales" id="max_sales" step="0.01" class="form-control" required>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col">
                        <label>Percentage (%)</label>
                        <input type="number" name="percentage" id="percentage" step="0.01" class="form-control">
                    </div>
                    <div class="col">
                        <label>Fixed Amount</label>
                        <input type="number" name="fixed_amount" id="fixed_amount" step="0.01" class="form-control">
                    </div>
                </div>
                <button type="submit" name="save_rule" class="btn btn-primary">Save Rule</button>
            </form>
        </div>
    </div>

    <!-- Rules Table -->
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Min Sales</th>
                <th>Max Sales</th>
                <th>Percentage (%)</th>
                <th>Fixed Amount</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $rules->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['min_sales'] ?></td>
                    <td><?= $row['max_sales'] ?></td>
                    <td><?= $row['percentage'] ?></td>
                    <td><?= $row['fixed_amount'] ?></td>
                    <td>
                        <button class="btn btn-sm btn-warning" onclick="editRule(<?= $row['id'] ?>,<?= $row['min_sales'] ?>,<?= $row['max_sales'] ?>,<?= $row['percentage'] ?>,<?= $row['fixed_amount'] ?>)">Edit</button>
                        <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this rule?')">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script>
function editRule(id, min, max, perc, fixed) {
    document.getElementById('rule_id').value = id;
    document.getElementById('min_sales').value = min;
    document.getElementById('max_sales').value = max;
    document.getElementById('percentage').value = perc;
    document.getElementById('fixed_amount').value = fixed;
}
</script>
</body>
</html>
