<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['restaurant_id'])) {
    header("Location: login.php");
    exit();
}

$restaurant_id = $_SESSION['restaurant_id'];
$product_id = intval($_GET['product_id'] ?? 0);

// Fetch restaurant info for navbar
$stmt = $conn->prepare("SELECT restaurant_name, address FROM restaurants WHERE id=?");
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$restaurant = $stmt->get_result()->fetch_assoc();
$stmt->close();

$restaurantName = $restaurant['restaurant_name'] ?? "My Restaurant";
$restaurantAddress = $restaurant['address'] ?? "";

// Fetch product info
$product = null;
if ($product_id > 0) {
    $stmt = $conn->prepare("
        SELECT p.id, p.product_name, COALESCE(SUM(b.quantity),0) AS total_stock
        FROM products p
        LEFT JOIN product_batches b ON b.product_id = p.id
        WHERE p.id=? AND p.restaurant_id=?
    ");
    $stmt->bind_param("ii", $product_id, $restaurant_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Add Batch
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_batch'])) {
    $quantity = floatval($_POST['quantity']);
    $expiry_date = $_POST['expiry_date'] ?: null;
    if ($quantity > 0) {
        $stmt = $conn->prepare("INSERT INTO product_batches (product_id, quantity, expiry_date) VALUES (?, ?, ?)");
        $stmt->bind_param("ids", $product_id, $quantity, $expiry_date);
        $stmt->execute();
        $stmt->close();
        $conn->query("UPDATE products SET quantity=(SELECT COALESCE(SUM(quantity),0) FROM product_batches WHERE product_id=$product_id) WHERE id=$product_id");
        header("Location: batches.php?product_id=$product_id&success=1");
        exit();
    }
}

// Delete Batch
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM product_batches WHERE id=$delete_id");
    $conn->query("UPDATE products SET quantity=(SELECT COALESCE(SUM(quantity),0) FROM product_batches WHERE product_id=$product_id) WHERE id=$product_id");
    header("Location: batches.php?product_id=$product_id&deleted=1");
    exit();
}

// Fetch batches
$batches = $conn->query("SELECT * FROM product_batches WHERE product_id=$product_id ORDER BY expiry_date ASC, id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Batches - <?= htmlspecialchars($product['product_name'] ?? '') ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { overflow-x: hidden; background:#f5f7fa; font-family:'Inter',sans-serif; }

/* Sidebar (same as orders.php) */
.sidebar {
    width: 180px;
    position: fixed;
    top: 56px;
    left: 0;
    height: 100%;
    background: #ffffff;
    border-right: 1px solid #e3e6f0;
    padding: 1rem;
    overflow-y: auto;
    transition: all 0.3s;
}
.sidebar.collapsed { margin-left: -180px; }
.sidebar ul.nav li a {
    color:#495057;
    text-decoration:none;
    display:block;
    padding:12px 20px;
    border-left:4px solid transparent;
    border-radius:0 8px 8px 0;
    margin:4px 0;
    font-weight:500;
    transition: all 0.2s;
}
.sidebar ul.nav li a:hover, .sidebar ul.nav li a.active {
    background: linear-gradient(90deg,#dbe4ff,#ffffff);
    border-left:4px solid #0d6efd;
    color:#0d6efd;
}

.main-content {
    margin-left: 180px;
    padding: 20px;
    padding-top: 70px;
    transition: margin-left 0.3s;
}
.main-content.expanded { margin-left: 0; }

.navbar { position: fixed; top: 0; width: 100%; z-index: 1000; }
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <button class="btn btn-primary me-2" id="sidebarToggle"><i class="bi bi-list"></i></button>
    <a class="navbar-brand"><?= htmlspecialchars($restaurantName) ?></a>
    <div class="ms-auto d-flex align-items-center">
      <span class="navbar-text me-3"><?= htmlspecialchars($restaurantAddress) ?></span>
      <a href="logout_restaurant.php" class="btn btn-outline-light btn-sm">Logout</a>
    </div>
  </div>
</nav>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <ul class="nav flex-column">
        <li class="nav-item"><a href="restaurant_dashboard.php"><i class="bi bi-house-door me-2"></i>Dashboard</a></li>
        <li class="nav-item"><a href="orders.php"><i class="bi bi-receipt me-2"></i>Orders</a></li>
        <li class="nav-item"><a href="products.php" class="active"><i class="bi bi-box-seam me-2"></i>Products</a></li>
        <li class="nav-item"><a href="categories.php"><i class="bi bi-tags me-2"></i>Categories</a></li>
        <li class="nav-item"><a href="cashiers.php"><i class="bi bi-people me-2"></i>Cashiers</a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content" id="mainContent">
    <div class="card shadow-sm">
        <div class="card-body">
            <h4>Batches - <?= htmlspecialchars($product['product_name'] ?? '') ?></h4>
            <a href="products.php" class="btn btn-secondary mb-3"><i class="bi bi-arrow-left"></i> Back</a>
            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addBatchModal"><i class="bi bi-plus-circle"></i> Add Batch</button>

            <table class="table table-bordered table-striped align-middle">
                <thead class="table-primary text-center">
                    <tr>
                        <th>#</th>
                        <th>Quantity</th>
                        <th>Expiry Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody class="text-center">
                    <?php $i=1; while($row=$batches->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= $row['quantity'] ?></td>
                        <td>
                            <?php if($row['expiry_date']): ?>
                                <?= htmlspecialchars($row['expiry_date']) ?>
                                <?php if(strtotime($row['expiry_date']) < time()): ?>
                                    <span class="badge bg-danger ms-1">Expired</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="?delete_id=<?= $row['id'] ?>&product_id=<?= $product_id ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this batch?')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Batch Modal -->
<div class="modal fade" id="addBatchModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Add Batch</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <label>Quantity</label>
            <input type="number" step="0.01" name="quantity" class="form-control" required>
            <label class="mt-2">Expiry Date</label>
            <input type="date" name="expiry_date" class="form-control">
        </div>
        <div class="modal-footer">
            <button type="submit" name="add_batch" class="btn btn-primary">Add Batch</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');
document.getElementById('sidebarToggle').addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('expanded');
});
</script>
</body>
</html>
