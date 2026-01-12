<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['restaurant_admin_id'])) {
    header("Location: index.php");
    exit();
}

$restaurant_id = $_SESSION['restaurant_id'];

$success = $error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $limit = intval($_POST['low_stock_limit']);
    if ($limit > 0) {
        $stmt = $conn->prepare("
            INSERT INTO settings (restaurant_id, low_stock_limit)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE low_stock_limit = VALUES(low_stock_limit)
        ");
        $stmt->bind_param("ii", $restaurant_id, $limit);
        $stmt->execute();
        $success = "Low stock alert limit updated successfully!";
    } else {
        $error = "Please enter a valid number.";
    }
}

$stmt = $conn->prepare("SELECT low_stock_limit FROM settings WHERE restaurant_id=?");
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$currentLimit = $result['low_stock_limit'] ?? 10;
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Settings</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
  <div class="card shadow">
    <div class="card-header bg-primary text-white"><h4>Stock Alert Settings</h4></div>
    <div class="card-body">
      <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

      <form method="POST">
        <div class="mb-3">
          <label class="form-label">Low Stock Alert Limit</label>
          <input type="number" name="low_stock_limit" class="form-control" value="<?= $currentLimit ?>" min="1" required>
          <div class="form-text">Products below or equal to this quantity will trigger an alert.</div>
        </div>
        <button type="submit" class="btn btn-success">Save</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>
