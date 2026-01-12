<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['restaurant_admin_id']) && !isset($_SESSION['cashier_id'])) {
    header("Location: index.php");
    exit();
}

$restaurant_id = $_SESSION['restaurant_id'] ?? 0;
if ($restaurant_id == 0) die("Restaurant ID missing in session.");

// Fetch restaurant info
$stmt = $conn->prepare("SELECT restaurant_name, address FROM restaurants WHERE id=?");
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$restaurant = $stmt->get_result()->fetch_assoc();
$stmt->close();

$restaurantName = $restaurant['restaurant_name'] ?? "My Restaurant";
$restaurantAddress = $restaurant['address'] ?? "";

// ✅ Add quantity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_qty'])) {
    $product_id = intval($_POST['product_id']);
    $add_qty = intval($_POST['quantity']);
    if ($add_qty > 0) {
        $stmt = $conn->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ? AND restaurant_id = ?");
        $stmt->bind_param("iii", $add_qty, $product_id, $restaurant_id);
        $stmt->execute();
        $stmt->close();
        $success_msg = "Quantity added successfully!";
    } else {
        $error_msg = "Please enter a valid quantity.";
    }
}

// ✅ Update per-product low stock limit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_limit'])) {
    $product_id = intval($_POST['product_id']);
    $custom_limit = intval($_POST['custom_limit']);
    if ($custom_limit >= 0) {
        $stmt = $conn->prepare("UPDATE products SET low_stock_limit = ? WHERE id = ? AND restaurant_id = ?");
        $stmt->bind_param("iii", $custom_limit, $product_id, $restaurant_id);
        $stmt->execute();
        $stmt->close();
        $success_msg = "Low stock limit updated!";
    } else {
        $error_msg = "Please enter a valid limit.";
    }
}

// ✅ Fetch all products (each with its low stock limit)
$stmt = $conn->prepare("SELECT id, product_name, quantity, IFNULL(low_stock_limit, 10) AS low_stock_limit FROM products WHERE restaurant_id = ? ORDER BY product_name ASC");
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$allProducts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>All Products - <?=htmlspecialchars($restaurantName)?></title>
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
.alert-low { color: red; font-weight: bold; }
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
        <li class="nav-item"><a href="out_of_stock.php"><i class="bi bi-exclamation-triangle me-2"></i>Low Stock</a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content" id="mainContent">
  <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="mb-0"><?= htmlspecialchars($restaurantName) ?> - All Products</h3>
      <a href="out_of_stock.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
  </div>

  <?php if (!empty($success_msg)): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
  <?php endif; ?>
  <?php if (!empty($error_msg)): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error_msg) ?></div>
  <?php endif; ?>

  <div class="table-responsive">
      <table class="table table-bordered table-striped align-middle">
          <thead class="table-dark text-center">
              <tr>
                  <th>#</th>
                  <th>Product Name</th>
                  <th>Quantity</th>
                  <th>Custom Low Stock Limit</th>
                  <th>Status</th>
                  <th>Action</th>
              </tr>
          </thead>
          <tbody class="text-center">
              <?php foreach ($allProducts as $i => $p): ?>
              <tr>
                  <td><?= $i + 1 ?></td>
                  <td><?= htmlspecialchars($p['product_name']) ?></td>
                  <td><?= htmlspecialchars($p['quantity']) ?></td>
                  <td>
                      <form method="POST" class="d-flex justify-content-center align-items-center gap-2">
                          <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                          <input type="number" name="custom_limit" value="<?= htmlspecialchars($p['low_stock_limit']) ?>" min="0" class="form-control form-control-sm" style="width: 80px;">
                          <button type="submit" name="update_limit" class="btn btn-sm btn-outline-primary"><i class="bi bi-save"></i></button>
                      </form>
                  </td>
                  <td>
                      <?php if ($p['quantity'] <= $p['low_stock_limit']): ?>
                          <span class="alert-low">Low Stock!</span>
                      <?php else: ?>
                          <span class="text-success">OK</span>
                      <?php endif; ?>
                  </td>
                  <td>
                      <button 
                          class="btn btn-sm btn-primary addQtyBtn"
                          data-bs-toggle="modal"
                          data-bs-target="#addQtyModal"
                          data-id="<?= $p['id'] ?>"
                          data-name="<?= htmlspecialchars($p['product_name']) ?>">
                          <i class="bi bi-plus-lg"></i> Add Quantity
                      </button>
                  </td>
              </tr>
              <?php endforeach; ?>
          </tbody>
      </table>
  </div>
</div>

<!-- Add Quantity Modal -->
<div class="modal fade" id="addQtyModal" tabindex="-1" aria-labelledby="addQtyModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title" id="addQtyModalLabel">Add Quantity</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="product_id" id="product_id">
        <div class="mb-3">
          <label class="form-label">Product Name</label>
          <input type="text" class="form-control" id="product_name" readonly>
        </div>
        <div class="mb-3">
          <label class="form-label">Add Quantity</label>
          <input type="number" class="form-control" name="quantity" min="1" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="add_qty" class="btn btn-primary">Add</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');
document.getElementById('sidebarToggle').addEventListener('click', ()=>{
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('expanded');
});

// Fill Add Quantity Modal
document.querySelectorAll('.addQtyBtn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('product_id').value = btn.getAttribute('data-id');
        document.getElementById('product_name').value = btn.getAttribute('data-name');
    });
});
</script>
</body>
</html>
