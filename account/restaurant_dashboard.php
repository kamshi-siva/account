<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['restaurant_admin_id']) && !isset($_SESSION['cashier_id'])) {
    header("Location: index.php");
    exit();
}

$restaurant_id = $_SESSION['restaurant_id'] ?? 0;
if ($restaurant_id == 0) die("Restaurant ID missing in session.");

$stmt = $conn->prepare("SELECT restaurant_name, address FROM restaurants WHERE id = ?");
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$restaurant = $stmt->get_result()->fetch_assoc();
$stmt->close();

$restaurantName = $restaurant['restaurant_name'] ?? "My Restaurant";
$restaurantAddress = $restaurant['address'] ?? "";

$totalOrders = $conn->query("SELECT COUNT(*) AS total FROM orders WHERE restaurant_id = $restaurant_id")->fetch_assoc()['total'] ?? 0;
$totalProducts = $conn->query("SELECT COUNT(*) AS total FROM products WHERE restaurant_id = $restaurant_id")->fetch_assoc()['total'] ?? 0;
$totalCategories = $conn->query("SELECT COUNT(*) AS total FROM categories WHERE restaurant_id = $restaurant_id")->fetch_assoc()['total'] ?? 0;
$totalCashiers = $conn->query("SELECT COUNT(*) AS total FROM cashiers WHERE restaurant_id = $restaurant_id")->fetch_assoc()['total'] ?? 0;
$totalSuppliers = $conn->query("SELECT COUNT(*) AS total FROM suppliers WHERE restaurant_id = $restaurant_id")->fetch_assoc()['total'] ?? 0;

// ✅ Expired / Near Expiry Product Count
$expiryAlertDays = 7; 

$expiredCountQuery = $conn->prepare("
    SELECT COUNT(*) AS total 
    FROM products 
    WHERE restaurant_id = ? 
      AND expiry_date IS NOT NULL 
      AND expiry_date <> '0000-00-00'
      AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
");
$expiredCountQuery->bind_param("ii", $restaurant_id, $expiryAlertDays);
$expiredCountQuery->execute();
$expiredCount = $expiredCountQuery->get_result()->fetch_assoc()['total'] ?? 0;
$expiredCountQuery->close();

// ✅ Accurate counting with product_batches if exists
if ($conn->query("SHOW TABLES LIKE 'product_batches'")->num_rows > 0) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM product_batches pb
        INNER JOIN products p ON pb.product_id = p.id
        WHERE p.restaurant_id = ?
          AND pb.expiry_date IS NOT NULL
          AND pb.expiry_date <> '0000-00-00'
          AND pb.expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
    ");
    $stmt->bind_param("ii", $restaurant_id, $expiryAlertDays);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $expiredCount = $row['total'] ?? $expiredCount;
    $stmt->close();
}

$popularDishQuery = $conn->query("
    SELECT oi.product_name, SUM(oi.quantity) AS total_sold
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE o.restaurant_id = $restaurant_id
    GROUP BY oi.product_name
    ORDER BY total_sold DESC
    LIMIT 1
");
$popularDish = ($popularDishQuery && $popularDishQuery->num_rows > 0) ? $popularDishQuery->fetch_assoc() : null;

$lowStockQuery = $conn->prepare("SELECT id, product_name, quantity FROM products WHERE restaurant_id = ? AND quantity <= 10 ORDER BY quantity ASC");
$lowStockQuery->bind_param("i", $restaurant_id);
$lowStockQuery->execute();
$lowStockProducts = $lowStockQuery->get_result()->fetch_all(MYSQLI_ASSOC);
$lowStockQuery->close();

$lowStockCount = count($lowStockProducts);

function getChartData($conn, $interval, $groupBy, $restaurant_id) {
    $query = "
        SELECT $groupBy AS period,
               (SUM(o.total) - SUM(oi.quantity * oi.price)) AS profit,
               SUM(oi.quantity * oi.price) AS expense
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.restaurant_id = $restaurant_id
          AND o.created_at >= DATE_SUB(NOW(), INTERVAL $interval)
        GROUP BY $groupBy
        ORDER BY $groupBy ASC
    ";
    $res = $conn->query($query);
    $labels = $profit = $expense = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $labels[] = $row['period'];
            $profit[] = (float)($row['profit'] ?? 0);
            $expense[] = (float)($row['expense'] ?? 0);
        }
    }
    return ['labels' => $labels, 'profit' => $profit, 'expense' => $expense];
}

$weeklyChart  = getChartData($conn, '7 DAY', 'WEEK(o.created_at)', $restaurant_id);
$monthlyChart = getChartData($conn, '30 DAY', 'MONTH(o.created_at)', $restaurant_id);
$yearlyChart  = getChartData($conn, '365 DAY', 'YEAR(o.created_at)', $restaurant_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($restaurantName) ?> Dashboard</title>
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
.hover-scale:hover { transform: scale(1.05); transition:0.3s ease-in-out; cursor:pointer; }
.toast-container { position: fixed; top: 70px; right: 20px; z-index: 1055; }
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
        <li class="nav-item mb-2"><a href="restaurant_dashboard.php" class="nav-link text-dark active"><i class="bi bi-house-door me-2"></i>Dashboard</a></li>
        <li class="nav-item mb-2"><a href="orders.php" class="nav-link text-dark"><i class="bi bi-receipt me-2"></i>Orders</a></li>
        <li class="nav-item mb-2"><a href="profit_report.php" class="nav-link text-dark"><i class="bi bi-bar-chart-line me-2"></i>Profit Report</a></li>
        <li class="nav-item mb-2"><a href="products.php" class="nav-link text-dark"><i class="bi bi-box-seam me-2"></i>Products</a></li>
        <li class="nav-item mb-2"><a href="categories.php" class="nav-link text-dark"><i class="bi bi-tags me-2"></i>Categories</a></li>
        <li class="nav-item mb-2"><a href="cashiers.php" class="nav-link text-dark"><i class="bi bi-people me-2"></i>Cashiers</a></li>
        <li class="nav-item mb-2"><a href="suppliers.php" class="nav-link text-dark"><i class="bi bi-truck me-2"></i>Suppliers</a></li>
    </ul>
</div>

<div class="main-content p-4" id="mainContent">
    <div class="row g-3 mt-3">
        <?php
        $cards = [
            ['icon'=>'bi-receipt','color'=>'primary','count'=>$totalOrders,'label'=>'Orders','link'=>'orders.php'],
            ['icon'=>'bi-box-seam','color'=>'success','count'=>$totalProducts,'label'=>'Products','link'=>'products.php'],
            ['icon'=>'bi-tags','color'=>'warning','count'=>$totalCategories,'label'=>'Categories','link'=>'categories.php'],
            ['icon'=>'bi-people','color'=>'danger','count'=>$totalCashiers,'label'=>'Cashiers','link'=>'cashiers.php'],
            ['icon'=>'bi-hourglass-split','color'=>'secondary','count'=>$expiredCount,'label'=>'Expired Products','link'=>'expired_products.php'],
            ['icon'=>'bi-truck','color'=>'info','count'=>$totalSuppliers,'label'=>'Suppliers','link'=>'suppliers.php'],
            ['icon'  => 'bi-sliders','color' => 'dark','count' => '⚙️','label' => 'Charges Settings','link'  => 'charges_settings.php'],

        ];
        foreach ($cards as $c): ?>
        <div class="col-12 col-sm-6 col-md-3">
            <a href="<?= $c['link'] ?>" class="text-decoration-none">
                <div class="card p-3 text-center shadow-sm hover-scale h-100">
                    <i class="bi <?= $c['icon'] ?> fs-3 text-<?= $c['color'] ?>"></i>
                    <h5 class="mt-2 mb-0"><?= $c['count'] ?></h5>
                    <small class="text-muted"><?= $c['label'] ?></small>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-3 mt-3">
        <div class="col-12 col-md-6">
            <div class="card p-3 shadow-sm text-center hover-scale h-100">
                <i class="bi bi-star fs-3 text-warning"></i>
                <h5 class="mt-2 mb-0"><?= $popularDish ? htmlspecialchars($popularDish['product_name']) : 'No sales yet' ?></h5>
                <small class="text-muted">Most Popular Dish</small>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <a href="out_of_stock.php" class="text-decoration-none">
            <div class="card p-3 shadow-sm text-center hover-scale h-100 bg-light border-danger">
                <i class="bi bi-exclamation-triangle fs-3 text-danger"></i>
                <h5 class="mt-2 mb-0"><?= $lowStockCount ?></h5>
                <small class="text-muted">Low Stock Products</small>
            </div>
            </a>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-lg-12">
            <div class="card p-4 shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Profit & Expenses</h5>
                    <select id="chartPeriod" class="form-select w-auto">
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="yearly">Yearly</option>
                    </select>
                </div>
                <canvas id="profitChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ✅ Clickable Toast Notifications -->
<div class="toast-container">
    <?php if($lowStockCount > 0): ?>
    <div class="toast align-items-center text-bg-warning border-0 show mb-2" role="alert" style="cursor:pointer;" onclick="window.location.href='out_of_stock.php'">
        <div class="d-flex">
            <div class="toast-body">
                ⚠️ You have <?= $lowStockCount ?> low-stock products!
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
    <?php endif; ?>

    <?php if($expiredCount > 0): ?>
    <div class="toast align-items-center text-bg-danger border-0 show" role="alert" style="cursor:pointer;" onclick="window.location.href='expired_products.php'">
        <div class="d-flex">
            <div class="toast-body">
                ⏰ <?= $expiredCount ?> products are expired or expiring soon!
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const chartData = {
    weekly: <?= json_encode($weeklyChart) ?>,
    monthly: <?= json_encode($monthlyChart) ?>,
    yearly: <?= json_encode($yearlyChart) ?>
};

const ctx = document.getElementById('profitChart').getContext('2d');
let profitChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: chartData.weekly.labels,
        datasets: [
            { label: 'Profit', data: chartData.weekly.profit, borderColor: 'green', backgroundColor: 'rgba(0,128,0,0.2)', tension: 0.3 },
            { label: 'Expenses', data: chartData.weekly.expense, borderColor: 'red', backgroundColor: 'rgba(255,0,0,0.2)', tension: 0.3 }
        ]
    },
    options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true } } }
});

document.getElementById('chartPeriod').addEventListener('change', function() {
    const period = this.value;
    profitChart.data.labels = chartData[period].labels;
    profitChart.data.datasets[0].data = chartData[period].profit;
    profitChart.data.datasets[1].data = chartData[period].expense;
    profitChart.update();
});

document.getElementById('sidebarToggle').addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('collapsed');
    document.getElementById('mainContent').classList.toggle('expanded');
});
</script>
</body>
</html>
