<?php
session_start();
include "config.php";

if (!isset($_SESSION['restaurant_admin_id']) && !isset($_SESSION['cashier_id'])) {
    header("Location: login.php");
    exit();
}

$restaurant_id = $_SESSION['restaurant_id'] ?? 0;

// Fetch products and suppliers for the form
$products = $conn->query("SELECT * FROM products WHERE restaurant_id=$restaurant_id ORDER BY product_name ASC");
$suppliers = $conn->query("SELECT * FROM suppliers WHERE restaurant_id=$restaurant_id ORDER BY name ASC");

// Add Stock
if(isset($_POST['add_stock'])){
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    $cost_price = $_POST['cost_price'];
    $supplier_id = $_POST['supplier_id'] ?: NULL;
    $purchase_date = $_POST['purchase_date'];
    $expiry_date = $_POST['expiry_date'] ?: NULL;

    $stmt = $conn->prepare("INSERT INTO product_stock (product_id, quantity, cost_price, supplier_id, purchase_date, expiry_date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("idisss", $product_id, $quantity, $cost_price, $supplier_id, $purchase_date, $expiry_date);
    $stmt->execute();

    // Update product quantity
    $conn->query("UPDATE products SET quantity = quantity + $quantity WHERE id=$product_id");

    header("Location: product_stock.php");
    exit();
}

// Fetch current stock
$current_stock = $conn->query("
    SELECT ps.*, p.product_name, s.name AS supplier_name 
    FROM product_stock ps 
    JOIN products p ON ps.product_id = p.id
    LEFT JOIN suppliers s ON ps.supplier_id = s.id
    WHERE ps.expiry_date IS NULL OR ps.expiry_date >= CURDATE()
    ORDER BY ps.created_at DESC
");

// Fetch expired stock
$expired_stock = $conn->query("
    SELECT ps.*, p.product_name, s.name AS supplier_name 
    FROM product_stock ps 
    JOIN products p ON ps.product_id = p.id
    LEFT JOIN suppliers s ON ps.supplier_id = s.id
    WHERE ps.expiry_date < CURDATE()
    ORDER BY ps.expiry_date ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Product Stock</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { background:#f5f7fa; font-family:'Inter',sans-serif; }
.sidebar { width:180px; position:fixed; top:56px; left:0; height:100%; background:#fff; border-right:1px solid #e3e6f0; padding:1rem; overflow-y:auto; transition:0.3s; }
.sidebar.collapsed { margin-left:-180px; }
.sidebar ul.nav li a { color:#495057; text-decoration:none; display:block; padding:12px 20px; border-left:4px solid transparent; border-radius:0 8px 8px 0; margin:4px 0; font-weight:500; transition:0.2s; }
.sidebar ul.nav li a:hover, .sidebar ul.nav li a.active { background:linear-gradient(90deg,#dbe4ff,#fff); border-left:4px solid #0d6efd; color:#0d6efd; }
.main-content { margin-left:180px; padding:20px; padding-top:70px; transition:0.3s; }
.main-content.expanded { margin-left:0; }
.navbar { position:fixed; top:0; width:100%; z-index:1000; }
.expired { background:#f8d7da; }
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <button class="btn btn-primary me-2" id="sidebarToggle"><i class="bi bi-list"></i></button>
    <a class="navbar-brand">POS / ERP</a>
    <div class="ms-auto">
      <a href="logout_restaurant.php" class="btn btn-outline-light btn-sm">Logout</a>
    </div>
  </div>
</nav>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
  <ul class="nav flex-column">
    <li class="nav-item"><a href="restaurant_dashboard.php"><i class="bi bi-house-door me-2"></i>Dashboard</a></li>
    <li class="nav-item"><a href="products.php"><i class="bi bi-box-seam me-2"></i>Products</a></li>
    <li class="nav-item"><a href="product_stock.php" class="active"><i class="bi bi-stack me-2"></i>Stock</a></li>
    <li class="nav-item"><a href="suppliers.php"><i class="bi bi-truck me-2"></i>Suppliers</a></li>
  </ul>
</div>

<!-- Main Content -->
<div class="main-content" id="mainContent">
<h3>Product Stock</h3>

<!-- Add Stock Form -->
<form method="POST" class="row g-2 mb-4">
  <div class="col-md-3">
    <select name="product_id" class="form-control" required>
      <option value="">Select Product</option>
      <?php while($p=$products->fetch_assoc()): ?>
      <option value="<?=$p['id']?>"><?=htmlspecialchars($p['product_name'])?></option>
      <?php endwhile; ?>
    </select>
  </div>
  <div class="col-md-2">
    <input type="number" name="quantity" placeholder="Quantity" class="form-control" required>
  </div>
  <div class="col-md-2">
    <input type="number" step="0.01" name="cost_price" placeholder="Cost Price" class="form-control" required>
  </div>
  <div class="col-md-2">
    <select name="supplier_id" class="form-control">
      <option value="">Select Supplier</option>
      <?php while($s=$suppliers->fetch_assoc()): ?>
      <option value="<?=$s['id']?>"><?=htmlspecialchars($s['name'])?></option>
      <?php endwhile; ?>
    </select>
  </div>
  <div class="col-md-2">
    <input type="date" name="purchase_date" class="form-control" placeholder="Purchase Date" required>
  </div>
  <div class="col-md-2">
    <input type="date" name="expiry_date" class="form-control" placeholder="Expiry Date">
  </div>
  <div class="col-md-1">
    <button type="submit" name="add_stock" class="btn btn-success w-100">Add</button>
  </div>
</form>

<!-- Current Stock -->
<h5>Current Stock</h5>
<table class="table table-bordered table-striped">
<thead class="table-light">
<tr>
  <th>Product</th>
  <th>Qty</th>
  <th>Cost Price</th>
  <th>Supplier</th>
  <th>Purchase Date</th>
  <th>Expiry Date</th>
</tr>
</thead>
<tbody>
<?php if($current_stock && $current_stock->num_rows > 0):
  while($row=$current_stock->fetch_assoc()): ?>
<tr>
  <td><?=htmlspecialchars($row['product_name'])?></td>
  <td><?=$row['quantity']?></td>
  <td><?=number_format($row['cost_price'],2)?></td>
  <td><?=htmlspecialchars($row['supplier_name'] ?? '-')?></td>
  <td><?=$row['purchase_date']?></td>
  <td><?=$row['expiry_date'] ?? '-'?></td>
</tr>
<?php endwhile; else: ?>
<tr><td colspan="6" class="text-center">No current stock</td></tr>
<?php endif; ?>
</tbody>
</table>

<!-- Expired Stock -->
<h5 class="text-danger">Expired Stock</h5>
<table class="table table-bordered table-striped">
<thead class="table-light">
<tr>
  <th>Product</th>
  <th>Qty</th>
  <th>Cost Price</th>
  <th>Supplier</th>
  <th>Purchase Date</th>
  <th>Expiry Date</th>
</tr>
</thead>
<tbody>
<?php if($expired_stock && $expired_stock->num_rows > 0):
  while($row=$expired_stock->fetch_assoc()): ?>
<tr class="expired">
  <td><?=htmlspecialchars($row['product_name'])?></td>
  <td><?=$row['quantity']?></td>
  <td><?=number_format($row['cost_price'],2)?></td>
  <td><?=htmlspecialchars($row['supplier_name'] ?? '-')?></td>
  <td><?=$row['purchase_date']?></td>
  <td><?=$row['expiry_date']?></td>
</tr>
<?php endwhile; else: ?>
<tr><td colspan="6" class="text-center">No expired stock</td></tr>
<?php endif; ?>
</tbody>
</table>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');
document.getElementById('sidebarToggle').addEventListener('click', ()=> {
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('expanded');
});
</script>
</body>
</html>
