<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['restaurant_admin_id'])) {
    header("Location: index.php");
    exit();
}

$restaurant_id = $_SESSION['restaurant_id'];

// ✅ Delete (Move to deleted_cashiers first)
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $cashier_id = (int)$_GET['delete'];

    // Fetch cashier data
    $stmt = $conn->prepare("SELECT * FROM cashiers WHERE id=? AND restaurant_id=?");
    $stmt->bind_param("ii", $cashier_id, $restaurant_id);
    $stmt->execute();
    $cashier = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($cashier) {
        // Move to deleted_cashiers table
        $stmt = $conn->prepare("INSERT INTO deleted_cashiers (cashier_id, restaurant_id, name, phone, ic_no, address, id_image)
                                VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "iisssss",
            $cashier['id'],
            $cashier['restaurant_id'],
            $cashier['name'],
            $cashier['phone'],
            $cashier['ic_no'],
            $cashier['address'],
            $cashier['id_image']
        );
        $stmt->execute();
        $stmt->close();

        // Delete from main table
        $stmt = $conn->prepare("DELETE FROM cashiers WHERE id=? AND restaurant_id=?");
        $stmt->bind_param("ii", $cashier_id, $restaurant_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: cashiers.php?deleted=1");
    exit();
}

// ✅ Fetch active cashiers
$stmt = $conn->prepare("SELECT id, name, phone, ic_no, address, id_image FROM cashiers WHERE restaurant_id=? ORDER BY id DESC");
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$cashiers = $stmt->get_result();
$stmt->close();

// ✅ Fetch restaurant info
$stmt2 = $conn->prepare("SELECT restaurant_name, address FROM restaurants WHERE id=?");
$stmt2->bind_param("i", $restaurant_id);
$stmt2->execute();
$restaurant = $stmt2->get_result()->fetch_assoc();
$restaurantName = $restaurant['restaurant_name'] ?? "My Restaurant";
$restaurantAddress = $restaurant['address'] ?? "";
$stmt2->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cashiers - <?= htmlspecialchars($restaurantName) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
body {
    overflow-x: hidden;
    font-family: 'Inter', sans-serif;
    background: #f5f7fa;
}
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
.sidebar.collapsed {
    margin-left: -180px;
}
.sidebar ul.nav li a {
    color: #495057;
    text-decoration: none;
    display: block;
    padding: 12px 20px;
    border-left: 4px solid transparent;
    border-radius: 0 8px 8px 0;
    margin: 4px 0;
    font-weight: 500;
    transition: all 0.2s;
}
.sidebar ul.nav li a:hover, .sidebar ul.nav li a.active {
    background: linear-gradient(90deg,#dbe4ff,#ffffff);
    border-left: 4px solid #0d6efd;
    color: #0d6efd;
}
.main-content {
    margin-left: 180px;
    padding: 20px;
    padding-top: 70px;
    transition: margin-left 0.3s;
}
.main-content.expanded {
    margin-left: 0;
}
.navbar {
    position: fixed;
    top: 0;
    width: 100%;
    z-index: 1000;
}
.table td, .table th {
    vertical-align: middle;
}
.img-thumb {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 5px;
}
.toast-container {
    position: fixed;
    top: 70px;
    right: 20px;
    z-index: 2000;
}
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
        <li class="nav-item"><a href="products.php"><i class="bi bi-box-seam me-2"></i>Products</a></li>
        <li class="nav-item"><a href="categories.php"><i class="bi bi-tags me-2"></i>Categories</a></li>
        <li class="nav-item"><a href="cashiers.php" class="active"><i class="bi bi-people me-2"></i>Cashiers</a></li>
        <li class="nav-item"><a href="deleted_cashiers.php"><i class="bi bi-trash3 me-2"></i>Deleted Cashiers</a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content" id="mainContent">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Manage Cashiers</h3>
        <a href="add_cashier.php" class="btn btn-success"><i class="bi bi-plus-circle"></i> Add New Cashier</a>
    </div>

    <table class="table table-bordered table-striped shadow-sm">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Phone</th>
                <th>IC No</th>
                <th>Address</th>
                <th>ID Image</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($cashiers->num_rows > 0): ?>
                <?php while($row = $cashiers->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['phone']) ?></td>
                        <td><?= htmlspecialchars($row['ic_no']) ?></td>
                        <td><?= htmlspecialchars($row['address']) ?></td>
                        <td>
                            <?php if($row['id_image']): ?>
                                <img src="uploads/cashiers/<?= htmlspecialchars($row['id_image']) ?>" class="img-thumb" alt="ID Image">
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="cashier_salary.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info text-white"><i class="bi bi-eye"></i> View</a>
                            <a href="edit_cashier.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil-square"></i> Edit</a>
                            <a href="cashiers.php?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to move this cashier to deleted list?')"><i class="bi bi-trash"></i> Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7" class="text-center">No cashiers found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Toast Notification -->
<div class="toast-container">
  <div id="deleteToast" class="toast align-items-center text-bg-success border-0" role="alert">
    <div class="d-flex">
      <div class="toast-body">Cashier moved to deleted list successfully!</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
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

// Show toast if deleted
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.has('deleted')) {
    const toastEl = document.getElementById('deleteToast');
    const toast = new bootstrap.Toast(toastEl);
    toast.show();
}
</script>
</body>
</html>
