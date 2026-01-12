<?php
session_start();
require_once "config.php";

// 🔒 Protect page
if (!isset($_SESSION['restaurant_admin_id'])) {
    header("Location: restaurant_login.php");
    exit();
}

$restaurant_id = $_SESSION['restaurant_admin_id'];
$errors = [];
$success = "";

// ✅ Add Product
if (isset($_POST['add_product'])) {
    $product_name = trim($_POST['product_name']);
    $price = $_POST['price'];

    if ($product_name === "" || $price === "") {
        $errors[] = "All fields are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO products (restaurant_id, product_name, price) VALUES (?,?,?)");
        $stmt->bind_param("isd", $restaurant_id, $product_name, $price);
        if ($stmt->execute()) {
            $success = "Product added successfully!";
        } else {
            $errors[] = "Error: " . $conn->error;
        }
        $stmt->close();
    }
}

// ✅ Delete Product
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $conn->query("DELETE FROM products WHERE id = $delete_id AND restaurant_id = $restaurant_id");
    $success = "Product deleted successfully!";
}

// ✅ Get Products
$result = $conn->query("SELECT * FROM products WHERE restaurant_id = $restaurant_id ORDER BY created_at DESC");
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Manage Products</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">

  <h2>Manage Products/Menu</h2>
  <a href="restaurant_dashboard.php" class="btn btn-secondary mb-3">⬅ Back to Dashboard</a>

  <?php if ($success): ?>
    <div class="alert alert-success"><?=htmlspecialchars($success)?></div>
  <?php endif; ?>
  <?php if ($errors): ?>
    <div class="alert alert-danger">
      <ul><?php foreach($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul>
    </div>
  <?php endif; ?>

  <!-- Add Product Form -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <h5>Add New Product</h5>
      <form method="post">
        <div class="mb-3">
          <label class="form-label">Product Name</label>
          <input type="text" name="product_name" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Price (Rs)</label>
          <input type="number" step="0.01" name="price" class="form-control" required>
        </div>
        <button type="submit" name="add_product" class="btn btn-success">Add Product</button>
      </form>
    </div>
  </div>

  <!-- List Products -->
  <div class="card shadow-sm">
    <div class="card-body">
      <h5>Products List</h5>
      <table class="table table-bordered table-striped">
        <thead>
          <tr>
            <th>ID</th>
            <th>Product Name</th>
            <th>Price (Rs)</th>
            <th>Status</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?=$row['id']?></td>
            <td><?=htmlspecialchars($row['product_name'])?></td>
            <td><?=number_format($row['price'],2)?></td>
            <td><?=$row['status']?></td>
            <td><?=$row['created_at']?></td>
            <td>
              <a href="?delete=<?=$row['id']?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this product?')">Delete</a>
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
