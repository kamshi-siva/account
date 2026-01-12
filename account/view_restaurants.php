<?php 
session_start();
require_once "config.php";

if (!isset($_SESSION['super_admin_id'])) {
    header("Location: super_admin_login.php");
    exit();
}

$pendingCount = $conn->query("SELECT COUNT(*) AS c FROM restaurant_requests WHERE status='Pending'")->fetch_assoc()['c'] ?? 0;
$activeCount  = $conn->query("SELECT COUNT(*) AS c FROM restaurants WHERE status='Active'")->fetch_assoc()['c'] ?? 0;
$inactiveCount = $conn->query("SELECT COUNT(*) AS c FROM restaurants WHERE status='Inactive'")->fetch_assoc()['c'] ?? 0;

$statusFilter = $_GET['status'] ?? 'all';
switch ($statusFilter) {
    case 'pending':
        $query = $conn->query("SELECT id, restaurant_name, phone, status, created_at, logo FROM restaurant_requests WHERE status='Pending' ORDER BY created_at DESC");
        break;
    case 'active':
        $query = $conn->query("SELECT id, restaurant_name, phone, status, created_at, logo FROM restaurants WHERE status='Active' ORDER BY created_at DESC");
        break;
    case 'inactive':
        $query = $conn->query("SELECT id, restaurant_name, phone, status, created_at, logo FROM restaurants WHERE status='Inactive' ORDER BY created_at DESC");
        break;
    default:
        $query = $conn->query("SELECT id, restaurant_name, phone, status, created_at, logo FROM restaurants ORDER BY created_at DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Restaurants | Super Admin Panel</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
body {
    font-family: 'Inter', sans-serif;
    background: #f5f7fa;
    margin: 0;
    overflow-x: hidden;
}
.sidebar {
    width: 260px;
    background: #ffffff;
    color: #495057;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    padding-top: 30px;
    border-right: 1px solid #e3e6f0;
    transition: all 0.3s ease;
    z-index: 1000;
}
.sidebar a {
    color: #495057;
    text-decoration: none;
    display: block;
    padding: 15px 25px;
    border-left: 4px solid transparent;
    border-radius: 0 8px 8px 0;
    margin: 4px 0;
    font-weight: 500;
    transition: all 0.2s;
}
.sidebar a:hover, .sidebar a.active {
    background: linear-gradient(90deg,#dbe4ff,#ffffff);
    border-left: 4px solid #0d6efd;
    color: #0d6efd;
}
.sidebar h4 {
    text-align: center;
    margin-bottom: 30px;
    font-weight: 700;
    font-size: 1.3rem;
    color: #0d6efd;
}
.main-content {
    margin-left: 260px;
    padding: 30px;
    transition: margin-left 0.3s ease;
}
.main-content.expanded {
    margin-left: 0;
}
.table-container {
    background: #ffffff;
    padding: 20px;
    border-radius: 16px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.05);
    margin-top: 20px;
}
.logo-thumb {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 10px;
    border: 1px solid #ddd;
}
.status-btns .btn {
    border-radius: 50px;
}
.toggle-btn {
    position: fixed;
    top: 20px;
    left: 260px;
    font-size: 22px;
    cursor: pointer;
    color: #495057;
    z-index: 1100;
    transition: left 0.3s ease;
}
.sidebar.collapsed ~ .toggle-btn {
    left: 20px;
}
.sidebar.collapsed {
    width: 0;
    padding: 0;
    overflow: hidden;
}
</style>
</head>
<body>

<?php include "super_admin_sidebar.php"; ?>

<div class="main-content" id="mainContent">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="fw-bold text-primary">All Restaurants</h2>
        <a href="add_restaurant.php" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Add New Restaurant
        </a>
    </div>

    <div class="status-btns d-flex gap-2 mb-3">
        <a href="?status=pending" class="btn <?= $statusFilter=='pending'?'btn-warning':'btn-outline-warning' ?>">
            <i class="fa-solid fa-hourglass-half"></i> Pending (<?= $pendingCount ?>)
        </a>
        <a href="?status=active" class="btn <?= $statusFilter=='active'?'btn-success':'btn-outline-success' ?>">
            <i class="fa-solid fa-circle-check"></i> Active (<?= $activeCount ?>)
        </a>
        <a href="?status=inactive" class="btn <?= $statusFilter=='inactive'?'btn-secondary':'btn-outline-secondary' ?>">
            <i class="fa-solid fa-circle-xmark"></i> Inactive (<?= $inactiveCount ?>)
        </a>
        <a href="?status=all" class="btn <?= $statusFilter=='all'?'btn-primary':'btn-outline-primary' ?>">
            <i class="fa-solid fa-list"></i> All
        </a>
    </div>

    <div class="table-container">
        <?php if ($query->num_rows > 0): ?>
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Logo</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $query->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td>
                        <?php if (!empty($row['logo'])): ?>
                            <img src="uploads/logos/<?= htmlspecialchars($row['logo']) ?>" class="logo-thumb">
                        <?php else: ?>
                            <img src="uploads/default-logo.png" class="logo-thumb">
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['restaurant_name']) ?></td>
                    <td><?= htmlspecialchars($row['phone']) ?></td>
                    <td>
                        <span class="badge bg-<?= $row['status']=='Active'?'success':($row['status']=='Pending'?'warning':'secondary') ?>">
                            <?= htmlspecialchars($row['status']) ?>
                        </span>
                    </td>
                    <td><?= date('Y-m-d', strtotime($row['created_at'])) ?></td>
                    <td>
                        <?php if ($row['status'] == 'Pending'): ?>
                            <a href="approve_restaurant.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-success"><i class="fa-solid fa-check"></i></a>
                            <a href="reject_restaurant.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger"><i class="fa-solid fa-xmark"></i></a>
                        <?php else: ?>
                            <a href="edit_restaurant.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning"><i class="fa-solid fa-pen-to-square"></i></a>
                            <a href="delete_restaurant.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this restaurant?');"><i class="fa-solid fa-trash"></i></a>
                            <a href="create_cashier.php?restaurant_id=<?= $row['id'] ?>" class="btn btn-sm btn-primary"><i class="fa-solid fa-user-plus"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p class="text-muted">No restaurants found for this category.</p>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const sidebar = document.getElementById("sidebar");
    const mainContent = document.getElementById("mainContent");
    const toggleBtn = document.createElement('i');
    toggleBtn.className = "fa-solid fa-bars toggle-btn";
    document.body.appendChild(toggleBtn);

    toggleBtn.addEventListener("click", function() {
        sidebar.classList.toggle("collapsed");
        mainContent.classList.toggle("expanded");
    });
});
</script>

</body>
</html>
