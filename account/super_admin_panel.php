<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['super_admin_id'])) {
    header("Location: super_admin_login.php");
    exit();
}

$pendingCount = $conn->query("SELECT COUNT(*) AS total FROM restaurant_requests WHERE status='Pending'")->fetch_assoc()['total'] ?? 0;
$approvedCount = $conn->query("SELECT COUNT(*) AS total FROM restaurants WHERE status='Active'")->fetch_assoc()['total'] ?? 0;
$inactiveCount = $conn->query("SELECT COUNT(*) AS total FROM restaurants WHERE status='Inactive'")->fetch_assoc()['total'] ?? 0;

$requests = $conn->query("SELECT * FROM restaurant_requests WHERE status='Pending' ORDER BY created_at DESC");
$allRestaurants = $conn->query("SELECT * FROM restaurants ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Super Admin Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
body {
    font-family: 'Inter', sans-serif;
    background: #f5f7fa;
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

/* Submenu */
.submenu {
    display: none;
    padding-left: 25px;
    border-left: 2px solid #e9ecef;
    margin-left: 10px;
}
.submenu a {
    font-size: 0.95rem;
    padding: 10px 25px;
}
.sidebar a.dropdown-toggle.active + .submenu {
    display: block;
}

/* Main content */
.main-content {
    margin-left: 260px;
    padding: 30px;
    transition: margin-left 0.3s ease;
}
.main-content.expanded {
    margin-left: 0;
}

/* Stats cards */
.card-stat {
    border: none;
    border-radius: 16px;
    background: linear-gradient(135deg,#ffffff,#f8f9fa);
    box-shadow: 0 10px 20px rgba(0,0,0,0.05);
    transition: transform 0.3s, box-shadow 0.3s;
    cursor: pointer;
}
.card-stat:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.1);
}
.card-stat h3 {
    font-weight: 700;
    font-size: 2rem;
}
.card-stat i {
    font-size: 2.5rem;
}

/* Tables */
.table-container {
    background: #ffffff;
    padding: 20px;
    border-radius: 16px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.05);
    margin-top: 30px;
}

/* Toggle button */
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

/* Badge */
.badge-info {
    background-color: #0d6efd;
    color: #fff;
    font-size: 0.8rem;
    padding: 5px 10px;
    border-radius: 10px;
}
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <h4><i class="fa-solid fa-crown me-2"></i>Super Admin</h4>

    <a href="#" class="active"><i class="fa-solid fa-gauge-high me-2"></i> Dashboard</a>

    <a href="#requests"><i class="fa-solid fa-envelope-open-text me-2"></i> Pending Requests 
        <span class="badge badge-info float-end"><?=$pendingCount?></span>
    </a>

    <!-- Restaurant Dropdown -->
    <a href="javascript:void(0);" class="dropdown-toggle" id="restaurantDropdown">
        <i class="fa-solid fa-utensils me-2"></i> Restaurants 
        
    </a>
    <div class="submenu" id="restaurantMenu">
        <a href="add_restaurant.php"><i class="fa-solid fa-plus-circle me-2"></i> Add Restaurant</a>
        <a href="view_restaurants.php"><i class="fa-solid fa-list me-2"></i> View Restaurants</a>
    </div>

    <a href="super_admin_logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i> Logout</a>
</div>

<!-- Toggle button -->
<i class="fa-solid fa-bars toggle-btn" id="toggleSidebar"></i>

<!-- Main content -->
<div class="main-content" id="mainContent">
    <h2 class="mb-4">Welcome, <?=htmlspecialchars($_SESSION['super_admin_name'])?></h2>

    <!-- Stats Cards -->
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card card-stat text-center text-primary" onclick="location.href='#requests'">
                <div class="card-body">
                    <i class="fa-solid fa-clock me-2"></i>
                    <h3><?=$pendingCount?></h3>
                    <p class="fw-semibold">Pending Requests</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-stat text-center text-success" onclick="location.href='#restaurants'">
                <div class="card-body">
                    <i class="fa-solid fa-check-circle me-2"></i>
                    <h3><?=$approvedCount?></h3>
                    <p class="fw-semibold">Active Restaurants</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-stat text-center text-secondary" onclick="location.href='#restaurants'">
                <div class="card-body">
                    <i class="fa-solid fa-ban me-2"></i>
                    <h3><?=$inactiveCount?></h3>
                    <p class="fw-semibold">Inactive Restaurants</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Requests Table -->
    <div id="requests" class="table-container">
        <h4 class="mb-3">Pending Restaurant Requests</h4>
        <?php if($requests->num_rows > 0): ?>
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Restaurant Name</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($r = $requests->fetch_assoc()): ?>
                <tr>
                    <td><?=htmlspecialchars($r['restaurant_name'])?></td>
                    <td><?=htmlspecialchars($r['phone'])?></td>
                    <td><?=htmlspecialchars($r['address'])?></td>
                    <td>
                        <a href="approve_request.php?id=<?=$r['id']?>" class="btn btn-success btn-sm">Approve</a>
                        <a href="reject_request.php?id=<?=$r['id']?>" class="btn btn-danger btn-sm">Reject</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p class="text-muted">No pending requests.</p>
        <?php endif; ?>
    </div>

    <!-- Restaurants Table -->
    <div id="restaurants" class="table-container">
        <h4 class="mb-3">All Restaurants</h4>
        <?php if($allRestaurants->num_rows > 0): ?>
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Cashier</th>
                </tr>
            </thead>
            <tbody>
                <?php while($r = $allRestaurants->fetch_assoc()): ?>
                <tr>
                    <td><?=$r['id']?></td>
                    <td><?=$r['restaurant_code'] ?? '-'?></td>
                    <td><?=htmlspecialchars($r['restaurant_name'])?></td>
                    <td><?=htmlspecialchars($r['phone'])?></td>
                    <td><?=$r['status']?></td>
                    <td>
                        <a href="create_cashier.php?restaurant_id=<?=$r['id']?>" class="btn btn-primary btn-sm">
                            <i class="fa-solid fa-user-plus"></i> Create Cashier
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p class="text-muted">No restaurants found.</p>
        <?php endif; ?>
    </div>
</div>

<script>
const sidebar = document.getElementById("sidebar");
const mainContent = document.getElementById("mainContent");
const toggleBtn = document.getElementById("toggleSidebar");

// Sidebar toggle
toggleBtn.addEventListener("click", function() {
    sidebar.classList.toggle("collapsed");
    mainContent.classList.toggle("expanded");
});

// Dropdown toggle
const restaurantDropdown = document.getElementById("restaurantDropdown");
const restaurantMenu = document.getElementById("restaurantMenu");

restaurantDropdown.addEventListener("click", function() {
    restaurantMenu.style.display = restaurantMenu.style.display === "block" ? "none" : "block";
    this.classList.toggle("active");
});
</script>

</body>
</html>
