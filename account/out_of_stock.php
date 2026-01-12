<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['restaurant_admin_id']) && !isset($_SESSION['cashier_id'])) {
    header("Location: index.php");
    exit();
}

$restaurant_id = $_SESSION['restaurant_id'] ?? 0;
if ($restaurant_id == 0) die("Restaurant ID missing.");

// 🏪 Fetch restaurant info
$stmt = $conn->prepare("SELECT restaurant_name, address FROM restaurants WHERE id=?");
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$restaurant = $stmt->get_result()->fetch_assoc();
$restaurantName = $restaurant['restaurant_name'] ?? "My Restaurant";
$restaurantAddress = $restaurant['address'] ?? "";
$stmt->close();

// 📦 Fetch products whose quantity is less than or equal to their custom low_stock_limit
$stmt = $conn->prepare("
    SELECT id, product_name, quantity, low_stock_limit
    FROM products
    WHERE restaurant_id = ? AND quantity <= low_stock_limit
    ORDER BY quantity ASC
");
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$lowStockProducts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Low Stock Products - <?=htmlspecialchars($restaurantName)?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { overflow-x: hidden; background:#f5f7fa; font-family:'Inter',sans-serif; }
.sidebar {
    width: 180px; position: fixed; top: 56px; left: 0;
    height: 100%; background: #ffffff; border-right:1px solid #e3e6f0;
    padding: 1rem; overflow-y:auto; transition: all 0.3s;
}
.sidebar.collapsed { margin-left: -180px; }
.sidebar ul.nav li a {
    color:#495057; text-decoration:none; display:block;
    padding:12px 20px; border-left:4px solid transparent; border-radius:0 8px 8px 0;
    margin:4px 0; font-weight:500; transition: all 0.2s;
}
.sidebar ul.nav li a:hover, .sidebar ul.nav li a.active {
    background: linear-gradient(90deg,#dbe4ff,#ffffff);
    border-left:4px solid #0d6efd; color:#0d6efd;
}
.main-content { margin-left: 180px; padding: 20px; padding-top: 70px; transition: margin-left 0.3s; }
.main-content.expanded { margin-left: 0; }
.navbar { position: fixed; top: 0; width: 100%; z-index: 1000; }
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <button class="btn btn-primary me-2" id="sidebarToggle"><i class="bi bi-list"></i></button>
    <a class="navbar-brand"><?=htmlspecialchars($restaurantName)?></a>
    <div class="ms-auto d-flex align-items-center">
      <span class="navbar-text me-3"><?=htmlspecialchars($restaurantAddress)?></span>
      <a href="logout_restaurant.php" class="btn btn-outline-light btn-sm">Logout</a>
    </div>
  </div>
</nav>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <ul class="nav flex-column">
        <li class="nav-item"><a href="restaurant_dashboard.php"><i class="bi bi-house-door me-2"></i>Dashboard</a></li>
        <li class="nav-item"><a href="orders.php"><i class="bi bi-receipt me-2"></i>Orders</a></li>
        <li class="nav-item"><a href="products.php"><i class="bi bi-box-seam me-2"></i>Products</a></li>
        <li class="nav-item"><a href="categories.php"><i class="bi bi-tags me-2"></i>Categories</a></li>
        <li class="nav-item"><a href="cashiers.php"><i class="bi bi-people me-2"></i>Cashiers</a></li>
        <li class="nav-item"><a href="out_of_stock.php" class="active"><i class="bi bi-exclamation-triangle me-2"></i>Low Stock</a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content" id="mainContent">
  <div class="d-flex justify-content-between align-items-center mb-3">
      <h3><?= htmlspecialchars($restaurantName) ?> - Low Stock Products</h3>
      <a href="all_products.php" class="btn btn-primary"><i class="bi bi-eye"></i> View All</a>
  </div>

  <?php if (count($lowStockProducts) > 0): ?>
  <div class="table-responsive">
    <table class="table table-bordered table-striped">
      <thead class="table-dark text-center">
        <tr>
          <th>#</th>
          <th>Product Name</th>
          <th>Quantity</th>
          <th>Low Stock Limit</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody class="text-center">
        <?php foreach ($lowStockProducts as $i => $p): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= htmlspecialchars($p['product_name']) ?></td>
          <td><?= htmlspecialchars($p['quantity']) ?></td>
          <td><?= htmlspecialchars($p['low_stock_limit']) ?></td>
          <td><span class="text-danger fw-bold">Low Stock!</span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="alert alert-success">All products are above their low stock limit 🎉</div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');
document.getElementById('sidebarToggle').addEventListener('click', ()=>{
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('expanded');
});
</script>
</body>
</html>
