<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['restaurant_admin_id'])) {
    header("Location: index.php");
    exit();
}

$restaurant_id = $_SESSION['restaurant_id'] ?? 0;
if (!$restaurant_id) {
    header("Location: index.php");
    exit();
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $ic_no = trim($_POST['ic_no'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $image = $_FILES['id_image'] ?? null;

    if (!$name || !$phone || !$password || !$ic_no || !$address) {
        $errors[] = "All fields are required";
    } else {
        $stmt = $conn->prepare("SELECT id FROM cashiers WHERE phone=? AND restaurant_id=?");
        $stmt->bind_param("si", $phone, $restaurant_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Phone number already registered for this restaurant";
        }
        $stmt->close();
    }

    $image_path = null;
    if ($image && $image['tmp_name']) {
        $allowed = ['jpg','jpeg','png','pdf'];
        $ext = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
        if(!in_array($ext, $allowed)) {
            $errors[] = "Invalid file type. Allowed: jpg, jpeg, png, pdf";
        } else {
            $image_path = "uploads/cashiers/" . uniqid() . "." . $ext;
            if(!move_uploaded_file($image['tmp_name'], $image_path)) {
                $errors[] = "Failed to upload file";
            }
        }
    }

    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO cashiers (restaurant_id, name, phone, password, ic_no, address, id_image) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssss", $restaurant_id, $name, $phone, $hashedPassword, $ic_no, $address, $image_path);
        if ($stmt->execute()) {
            $success = "Cashier added successfully!";
        } else {
            $errors[] = "Database error: " . $stmt->error;
        }
        $stmt->close();
    }
}

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
<title>Add Cashier - <?=htmlspecialchars($restaurantName)?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { overflow-x: hidden; background:#f5f7fa; font-family:'Inter',sans-serif; }
.sidebar { width: 180px; position: fixed; top: 56px; left: 0; height: 100%; background: #fff; border-right: 1px solid #e3e6f0; padding:1rem; overflow-y:auto; transition: all 0.3s; }
.sidebar.collapsed { margin-left: -180px; }
.sidebar ul.nav li a { color:#495057; text-decoration:none; display:block; padding:12px 20px; border-left:4px solid transparent; border-radius:0 8px 8px 0; margin:4px 0; font-weight:500; transition: all 0.2s; }
.sidebar ul.nav li a:hover, .sidebar ul.nav li a.active { background: linear-gradient(90deg,#dbe4ff,#ffffff); border-left:4px solid #0d6efd; color:#0d6efd; }
.main-content { margin-left: 180px; padding: 20px; padding-top: 70px; transition: margin-left 0.3s; }
.main-content.expanded { margin-left: 0; }
.navbar { position: fixed; top:0; width:100%; z-index:1000; }
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
        <li class="nav-item"><a href="create_cashier.php" class="active"><i class="bi bi-people me-2"></i>Cashiers</a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content" id="mainContent">
    <h3>Add New Cashier</h3>

    <?php if($errors): ?>
        <div class="alert alert-danger"><?= implode('<br>', $errors) ?></div>
    <?php endif; ?>
    <?php if($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST" class="row g-2 mt-3" enctype="multipart/form-data">
        <div class="col-md-4">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">IC Number</label>
            <input type="text" name="ic_no" class="form-control" value="<?= htmlspecialchars($_POST['ic_no'] ?? '') ?>" required>
        </div>
        <div class="col-md-8">
            <label class="form-label">Address</label>
            <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($_POST['address'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">ID / Passport / License Image</label>
            <input type="file" name="id_image" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
        </div>
        <div class="col-12 mt-3">
            <button type="submit" class="btn btn-success">Add Cashier</button>
            <a href="cashiers.php" class="btn btn-secondary">Back</a>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');
document.getElementById('sidebarToggle').addEventListener('click', function(){
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('expanded');
});
</script>
</body>
</html>
