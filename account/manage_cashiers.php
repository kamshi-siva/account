<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['restaurant_admin_id'])) {
    header("Location: restaurant_login.php");
    exit();
}

$restaurant_id = $_SESSION['restaurant_admin_id'];
$errors = [];
$success = "";

if (isset($_POST['add_cashier'])) {
    $name = trim($_POST['name']);
    $cashier_id = trim($_POST['cashier_id']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    if ($name === "" || $cashier_id === "" || $_POST['password'] === "") {
        $errors[] = "All fields are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO cashiers (restaurant_id, cashier_id, name, password, status) VALUES (?,?,?,?, 'Active')");
        $stmt->bind_param("isss", $restaurant_id, $cashier_id, $name, $password);
        if ($stmt->execute()) {
            $success = "Cashier added successfully!";
        } else {
            $errors[] = "Error: " . $conn->error;
        }
        $stmt->close();
    }
}

if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $conn->query("DELETE FROM cashiers WHERE id = $delete_id AND restaurant_id = $restaurant_id");
    $success = "Cashier deleted successfully!";
}

$result = $conn->query("SELECT * FROM cashiers WHERE restaurant_id = $restaurant_id ORDER BY created_at DESC");
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Manage Cashiers</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">

  <h2>Manage Cashiers</h2>
  <a href="restaurant_dashboard.php" class="btn btn-secondary mb-3">⬅ Back to Dashboard</a>

  <?php if ($success): ?>
    <div class="alert alert-success"><?=htmlspecialchars($success)?></div>
  <?php endif; ?>
  <?php if ($errors): ?>
    <div class="alert alert-danger">
      <ul><?php foreach($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul>
    </div>
  <?php endif; ?>

  <!-- Add Cashier Form -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <h5>Add New Cashier</h5>
      <form method="post">
        <div class="mb-3">
          <label class="form-label">Cashier Name</label>
          <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Cashier ID</label>
          <input type="text" name="cashier_id" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" name="add_cashier" class="btn btn-primary">Add Cashier</button>
      </form>
    </div>
  </div>

  <!-- List Cashiers -->
  <div class="card shadow-sm">
    <div class="card-body">
      <h5>Cashiers List</h5>
      <table class="table table-bordered table-striped">
        <thead>
          <tr>
            <th>ID</th>
            <th>Cashier ID</th>
            <th>Name</th>
            <th>Status</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?=$row['id']?></td>
            <td><?=htmlspecialchars($row['cashier_id'])?></td>
            <td><?=htmlspecialchars($row['name'])?></td>
            <td><?=$row['status']?></td>
            <td><?=$row['created_at']?></td>
            <td>
              <a href="?delete=<?=$row['id']?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this cashier?')">Delete</a>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
</body>
</html>
