<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['restaurant_admin_id']) && !isset($_SESSION['cashier_id'])) {
    header("Location: index.php");
    exit();
}

$restaurant_id = $_SESSION['restaurant_id'] ?? 0;
if ($restaurant_id == 0) die("Restaurant ID missing in session.");

// Add Supplier
if(isset($_POST['add_supplier'])){
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $address = $_POST['address'];

    $stmt = $conn->prepare("INSERT INTO suppliers (restaurant_id, name, phone, email, address, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("issss", $restaurant_id, $name, $phone, $email, $address);
    $stmt->execute();
    header("Location: suppliers.php");
    exit();
}

// Edit Supplier
if(isset($_POST['edit_supplier'])){
    $id = (int)$_POST['supplier_id'];
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $address = $_POST['address'];

    $stmt = $conn->prepare("UPDATE suppliers SET name=?, phone=?, email=?, address=? WHERE id=? AND restaurant_id=?");
    $stmt->bind_param("ssssii", $name, $phone, $email, $address, $id, $restaurant_id);
    $stmt->execute();
    header("Location: suppliers.php");
    exit();
}

// Delete Supplier
if(isset($_GET['delete'])){
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM suppliers WHERE id=$id AND restaurant_id=$restaurant_id");
    header("Location: suppliers.php");
    exit();
}

// Search suppliers
$searchQuery = '';
if(isset($_GET['search'])){
    $searchQuery = $conn->real_escape_string($_GET['search']);
    $suppliers = $conn->query("SELECT * FROM suppliers WHERE restaurant_id=$restaurant_id AND name LIKE '%$searchQuery%' ORDER BY id DESC");
} else {
    $suppliers = $conn->query("SELECT * FROM suppliers WHERE restaurant_id=$restaurant_id ORDER BY id DESC");
}

// Fetch restaurant info
$stmt = $conn->prepare("SELECT restaurant_name, address FROM restaurants WHERE id = ?");
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$restaurant = $stmt->get_result()->fetch_assoc();
$stmt->close();

$restaurantName = $restaurant['restaurant_name'] ?? "My Restaurant";
$restaurantAddress = $restaurant['address'] ?? "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Suppliers - <?= htmlspecialchars($restaurantName) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { overflow-x: hidden; background:#f5f7fa; font-family:'Inter',sans-serif; }
.navbar { z-index: 1050; }
.sidebar {
    width: 180px;
    position: fixed;
    top: 56px;
    left: 0;
    bottom: 0;
    transition: all 0.3s;
    background: #ffffff;
    border-right: 1px solid #e3e6f0;
    z-index: 1040;
    padding: 1rem;
}
.sidebar.collapsed { margin-left: -180px; }
.sidebar ul.nav li a {
    color:#495057; text-decoration:none; display:block; padding:12px 20px;
    border-left:4px solid transparent; border-radius:0 8px 8px 0; margin:4px 0;
    font-weight:500; transition: all 0.2s;
}
.sidebar ul.nav li a:hover, .sidebar ul.nav li a.active {
    background: linear-gradient(90deg,#dbe4ff,#ffffff);
    border-left:4px solid #0d6efd; color:#0d6efd;
}
.main-content { 
    margin-left: 180px;
    margin-top: 56px; 
    transition: margin-left 0.3s;
}
.main-content.expanded { margin-left: 0; }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
  <div class="container-fluid">
    <button class="btn btn-primary me-2" id="sidebarToggle"><i class="bi bi-list"></i></button>
    <a class="navbar-brand" href="#"><?= htmlspecialchars($restaurantName) ?></a>
    <div class="ms-auto d-flex align-items-center">
      <span class="me-3 text-white"><?= htmlspecialchars($restaurantAddress) ?></span>
      <a href="logout_restaurant.php" class="btn btn-outline-light btn-sm">Logout</a>
    </div>
  </div>
</nav>

<div class="position-fixed h-100 sidebar" id="sidebar">
    <ul class="nav flex-column">
        <li class="nav-item mb-2"><a href="restaurant_dashboard.php" class="nav-link text-dark"><i class="bi bi-house-door me-2"></i>Dashboard</a></li>
        <li class="nav-item mb-2"><a href="orders.php" class="nav-link text-dark"><i class="bi bi-receipt me-2"></i>Orders</a></li>
        <li class="nav-item mb-2"><a href="profit_report.php" class="nav-link text-dark"><i class="bi bi-bar-chart-line me-2"></i>Profit Report</a></li>
        <li class="nav-item mb-2"><a href="products.php" class="nav-link text-dark"><i class="bi bi-box-seam me-2"></i>Products</a></li>
        <li class="nav-item mb-2"><a href="categories.php" class="nav-link text-dark"><i class="bi bi-tags me-2"></i>Categories</a></li>
        <li class="nav-item mb-2"><a href="cashiers.php" class="nav-link text-dark"><i class="bi bi-people me-2"></i>Cashiers</a></li>
        <li class="nav-item mb-2"><a href="suppliers.php" class="nav-link text-dark active"><i class="bi bi-truck me-2"></i>Suppliers</a></li>
    </ul>
</div>

<div class="main-content p-4" id="mainContent">

<h3>Suppliers</h3>

<!-- Search -->
<form method="GET" class="mb-3 d-flex">
    <input type="text" name="search" class="form-control me-2" placeholder="Search by name..." value="<?= htmlspecialchars($searchQuery) ?>">
    <button type="submit" class="btn btn-outline-primary">Search</button>
</form>

<!-- Add Supplier -->
<form method="POST" class="row g-2 mb-3">
  <div class="col-md-3"><input type="text" name="name" placeholder="Name" class="form-control" required></div>
  <div class="col-md-2"><input type="text" name="phone" placeholder="Phone" class="form-control"></div>
  <div class="col-md-3"><input type="email" name="email" placeholder="Email" class="form-control"></div>
  <div class="col-md-3"><input type="text" name="address" placeholder="Address" class="form-control"></div>
  <div class="col-md-1"><button type="submit" name="add_supplier" class="btn btn-success w-100">Add</button></div>
</form>

<!-- Supplier Table -->
<table class="table table-bordered table-striped">
<thead>
<tr><th>#</th><th>Name</th><th>Phone</th><th>Email</th><th>Address</th><th>Actions</th></tr>
</thead>
<tbody>
<?php
$i = 1;
while($s = $suppliers->fetch_assoc()):
?>
<tr>
<td><?=$i++?></td>
<td><?=htmlspecialchars($s['name'])?></td>
<td><?=htmlspecialchars($s['phone'])?></td>
<td><?=htmlspecialchars($s['email'])?></td>
<td><?=htmlspecialchars($s['address'])?></td>
<td>
    <button class="btn btn-sm btn-primary me-1" data-bs-toggle="modal" data-bs-target="#editModal<?=$s['id']?>">Edit</button>
    <a href="?delete=<?=$s['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</a>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal<?=$s['id']?>" tabindex="-1">
      <div class="modal-dialog">
        <form method="POST" class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Edit Supplier</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="supplier_id" value="<?=$s['id']?>">
            <div class="mb-2"><input type="text" name="name" class="form-control" value="<?=htmlspecialchars($s['name'])?>" required></div>
            <div class="mb-2"><input type="text" name="phone" class="form-control" value="<?=htmlspecialchars($s['phone'])?>"></div>
            <div class="mb-2"><input type="email" name="email" class="form-control" value="<?=htmlspecialchars($s['email'])?>"></div>
            <div class="mb-2"><input type="text" name="address" class="form-control" value="<?=htmlspecialchars($s['address'])?>"></div>
          </div>
          <div class="modal-footer">
            <button type="submit" name="edit_supplier" class="btn btn-success">Save Changes</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </form>
      </div>
    </div>

</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('sidebarToggle').addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('collapsed');
    document.getElementById('mainContent').classList.toggle('expanded');
});
</script>
</body>
</html>
