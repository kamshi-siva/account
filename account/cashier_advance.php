<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['restaurant_admin_id'])) {
    header("Location: index.php");
    exit();
}

$restaurant_id = $_SESSION['restaurant_id'];
$cashier_id = intval($_GET['id']);

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount']);
    $reason = trim($_POST['reason']);
    $date = $_POST['given_date'];

    if ($amount <= 0 || !$date) {
        $errors[] = "Amount and Date are required.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO cashier_advance (cashier_id, restaurant_id, amount, reason, given_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iidss", $cashier_id, $restaurant_id, $amount, $reason, $date);
        if ($stmt->execute()) {
            $success = "Advance of Rs. " . number_format($amount, 2) . " recorded successfully.";
        } else {
            $errors[] = "Database error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch all advances
$stmt2 = $conn->prepare("SELECT amount, reason, given_date FROM cashier_advance WHERE cashier_id=? ORDER BY given_date DESC");
$stmt2->bind_param("i", $cashier_id);
$stmt2->execute();
$advances = $stmt2->get_result();
$stmt2->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Advance Salary</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <a href="cashier_salary.php?id=<?= $cashier_id ?>" class="btn btn-secondary mb-3">Back</a>

  <h4 class="mb-3">Advance Payments</h4>

  <?php if ($errors): ?><div class="alert alert-danger"><?= implode('<br>', $errors) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

  <form method="POST" class="card p-3 shadow-sm mb-4">
    <div class="mb-3">
      <label class="form-label">Amount</label>
      <input type="number" step="0.01" name="amount" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Reason (optional)</label>
      <input type="text" name="reason" class="form-control">
    </div>
    <div class="mb-3">
      <label class="form-label">Date</label>
      <input type="date" name="given_date" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary">Save Advance</button>
  </form>

  <div class="card shadow-sm">
    <div class="card-header bg-dark text-white">Advance History</div>
    <div class="card-body p-0">
      <table class="table table-bordered mb-0">
        <thead class="table-light">
          <tr>
            <th>Date</th>
            <th>Amount</th>
            <th>Reason</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($advances->num_rows > 0): ?>
            <?php while($row = $advances->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($row['given_date']) ?></td>
                <td><?= number_format($row['amount'], 2) ?></td>
                <td><?= htmlspecialchars($row['reason'] ?? '-') ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="3" class="text-center">No advance records found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>
