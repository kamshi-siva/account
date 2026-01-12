<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['restaurant_admin_id']) && !isset($_SESSION['cashier_id'])) {
    header("Location: index.php");
    exit();
}

$restaurant_id = $_SESSION['restaurant_id'] ?? 0;
if ($restaurant_id == 0) die("Restaurant ID missing in session.");

// --- Get Expiry Alert Days ---
$stmt = $conn->prepare("SELECT expiry_alert_days FROM settings WHERE restaurant_id=? LIMIT 1");
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$settings = $stmt->get_result()->fetch_assoc();
$expiryAlertDays = $settings['expiry_alert_days'] ?? 7;
$stmt->close();

// --- Fetch Restaurant Info ---
$stmt = $conn->prepare("SELECT restaurant_name, address FROM restaurants WHERE id=?");
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$restaurant = $stmt->get_result()->fetch_assoc();
$stmt->close();
$restaurantName = $restaurant['restaurant_name'] ?? "My Restaurant";
$restaurantAddress = $restaurant['address'] ?? "";

// --- Fetch Expired / Expiring Batches ---
$query = $conn->prepare("
    SELECT 
        p.id AS product_id,
        p.product_name,
        pb.id AS batch_id,
        pb.quantity,
        pb.expiry_date
    FROM product_batches pb
    INNER JOIN products p ON pb.product_id = p.id
    WHERE p.restaurant_id = ?
      AND pb.expiry_date IS NOT NULL
      AND pb.expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
      AND pb.expiry_date <> '0000-00-00'
    ORDER BY pb.expiry_date ASC
");
$query->bind_param("ii", $restaurant_id, $expiryAlertDays);
$query->execute();
$result = $query->get_result();
$batches = $result->fetch_all(MYSQLI_ASSOC);
$query->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($restaurantName) ?> | Expired Products</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { overflow-x:hidden; background:#f5f7fa; font-family:'Inter',sans-serif; }
.navbar { position: fixed; top:0; width:100%; z-index:1000; }

/* Sidebar */
.sidebar {
    width:180px;
    position:fixed;
    top:56px;
    left:0;
    height:100%;
    background:#fff;
    border-right:1px solid #e3e6f0;
    padding:1rem;
    overflow-y:auto;
    transition:all 0.3s;
}
.sidebar.collapsed { margin-left:-180px; }
.sidebar ul.nav li a {
    color:#495057;
    text-decoration:none;
    display:block;
    padding:12px 20px;
    border-left:4px solid transparent;
    border-radius:0 8px 8px 0;
    margin:4px 0;
    font-weight:500;
    transition:all 0.2s;
}
.sidebar ul.nav li a:hover, .sidebar ul.nav li a.active {
    background:linear-gradient(90deg,#dbe4ff,#ffffff);
    border-left:4px solid #0d6efd;
    color:#0d6efd;
}

/* Main content */
.main-content {
    margin-left:180px;
    padding:20px;
    padding-top:70px;
    transition:margin-left 0.3s;
}
.main-content.expanded { margin-left:0; }

.table thead th { background-color:#0d6efd; color:white; }
.expired { background-color:#f8d7da !important; }
.expiring-soon { background-color:#fff3cd !important; }
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
        <li class="nav-item"><a href="cashiers.php"><i class="bi bi-people me-2"></i>Cashiers</a></li>
        <li class="nav-item"><a href="expired_products.php" class="active"><i class="bi bi-hourglass-split me-2"></i>Expired</a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content" id="mainContent">
    <h4 class="mb-4"><i class="bi bi-hourglass-split text-primary me-2"></i>Expired & Expiring Products</h4>

    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Batches nearing expiry</h6>
            <span class="badge bg-primary"><?= count($batches) ?> Batches</span>
        </div>
        <div class="card-body table-responsive">
            <?php if (count($batches) > 0): ?>
            <table class="table table-bordered align-middle">
                <thead>
                    <tr class="text-center">
                        <th>#</th>
                        <th>Product Name</th>
                        <th>Batch ID</th>
                        <th>Quantity</th>
                        <th>Expiry Date</th>
                        <th>Status</th>
                        <th>Days Left</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $count = 1;
                    foreach ($batches as $row):
                        $expiry_date = $row['expiry_date'];
                        $days_diff = (strtotime($expiry_date) - strtotime(date('Y-m-d'))) / (60*60*24);
                        if ($days_diff < 0) {
                            $rowClass = "expired";
                            $status = "<span class='badge bg-dark'>Expired</span>";
                        } elseif ($days_diff <= $expiryAlertDays) {
                            $rowClass = "expiring-soon";
                            $status = "<span class='badge bg-warning text-dark'>Expiring Soon</span>";
                        } else {
                            $rowClass = "";
                            $status = "<span class='badge bg-success'>Valid</span>";
                        }
                    ?>
                    <tr class="text-center <?= $rowClass ?>">
                        <td><?= $count++ ?></td>
                        <td><?= htmlspecialchars($row['product_name']) ?></td>
                        <td>#<?= htmlspecialchars($row['batch_id']) ?></td>
                        <td><?= htmlspecialchars($row['quantity']) ?></td>
                        <td><?= htmlspecialchars($row['expiry_date']) ?></td>
                        <td><?= $status ?></td>
                        <td>
                            <?php
                            if ($days_diff < 0) echo abs($days_diff) . " days ago";
                            else echo round($days_diff) . " days left";
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div class="alert alert-success text-center mb-0">
                    ✅ All product batches are within valid expiry dates.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
