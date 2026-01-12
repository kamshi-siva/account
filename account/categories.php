<?php
session_start();
include "config.php";

if (!isset($_SESSION['restaurant_id'])) {
    header("Location: login.php");
    exit();
}

$restaurant_id = $_SESSION['restaurant_id'];

// Add Category
if(isset($_POST['add_category'])){
    $name = $_POST['name'];
    $stmt = $conn->prepare("INSERT INTO categories (restaurant_id, name, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("is", $restaurant_id, $name);
    $stmt->execute();
    header("Location: categories.php");
    exit();
}

// Delete Category
if(isset($_GET['delete'])){
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM categories WHERE id=$id AND restaurant_id=$restaurant_id");
    header("Location: categories.php");
    exit();
}

// Edit Category
if(isset($_POST['edit_category'])){
    $id = $_POST['id'];
    $name = $_POST['name'];
    $stmt = $conn->prepare("UPDATE categories SET name=? WHERE id=? AND restaurant_id=?");
    $stmt->bind_param("sii", $name, $id, $restaurant_id);
    $stmt->execute();
    header("Location: categories.php");
    exit();
}

// Fetch categories
$categories = $conn->query("SELECT * FROM categories WHERE restaurant_id=$restaurant_id ORDER BY id DESC");

// Fetch restaurant info for navbar
$stmt = $conn->prepare("SELECT restaurant_name, address FROM restaurants WHERE id=?");
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$restaurant = $stmt->get_result()->fetch_assoc();
$restaurantName = $restaurant['restaurant_name'] ?? "My Restaurant";
$restaurantAddress = $restaurant['address'] ?? "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Categories - <?=htmlspecialchars($restaurantName)?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { overflow-x: hidden; background:#f5f7fa; font-family:'Inter',sans-serif; }

/* Sidebar */
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

/* Main content */
.main-content {
    margin-left: 180px;
    padding: 20px;
    padding-top: 70px;
    transition: margin-left 0.3s;
}
.main-content.expanded { margin-left: 0; }

/* Navbar */
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
        <li class="nav-item"><a href="categories.php" class="active"><i class="bi bi-tags me-2"></i>Categories</a></li>
        <li class="nav-item"><a href="cashiers.php"><i class="bi bi-people me-2"></i>Cashiers</a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content" id="mainContent">
    <h3>Categories</h3>

    <form method="POST" class="row g-2 mb-3">
        <div class="col-md-8">
            <input type="text" name="name" class="form-control" placeholder="Category Name" required>
        </div>
        <div class="col-md-4">
            <button type="submit" name="add_category" class="btn btn-success w-100">Add Category</button>
        </div>
    </form>

    <table class="table table-bordered">
    <thead>
        <tr>
            <th>#</th>
            <th>Name</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php if($categories && $categories->num_rows > 0):
        $i=1;
        while($c = $categories->fetch_assoc()): ?>
    <tr>
        <td><?=$i++?></td>
        <td><?=htmlspecialchars($c['name'])?></td>
        <td>
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editCatModal<?=$c['id']?>">Edit</button>
            <a href="?delete=<?=$c['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this category?')">Delete</a>
        </td>
    </tr>

    <!-- Edit Modal -->
    <div class="modal fade" id="editCatModal<?=$c['id']?>" tabindex="-1">
    <div class="modal-dialog">
    <div class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title">Edit Category</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <form method="POST">
    <div class="modal-body">
        <input type="hidden" name="id" value="<?=$c['id']?>">
        <div class="mb-2">
            <label>Name</label>
            <input type="text" name="name" class="form-control" value="<?=htmlspecialchars($c['name'])?>" required>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="edit_category" class="btn btn-success">Save Changes</button>
    </div>
    </form>
    </div>
    </div>
    </div>

    <?php endwhile; else: ?>
    <tr><td colspan="3" class="text-center">No categories yet.</td></tr>
    <?php endif; ?>
    </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Sidebar toggle
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');
document.getElementById('sidebarToggle').addEventListener('click', function(){
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('expanded');
});
</script>
</body>
</html>
