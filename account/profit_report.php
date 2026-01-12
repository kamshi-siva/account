<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['restaurant_admin_id']) && !isset($_SESSION['cashier_id'])) {
    header("Location: login.php");
    exit();
}

$restaurant_id = $_SESSION['restaurant_id'] ?? 0;

// Filter logic
$filter = $_GET['filter'] ?? 'this_month';
$custom_start = $_GET['start_date'] ?? date('Y-m-01');
$custom_end   = $_GET['end_date'] ?? date('Y-m-d');

$start_date = $end_date = date('Y-m-d');

switch($filter){
    case 'today':
        $start_date = $end_date = date('Y-m-d');
        break;
    case 'yesterday':
        $start_date = $end_date = date('Y-m-d', strtotime('-1 day'));
        break;
    case 'this_week':
        $start_date = date('Y-m-d', strtotime('monday this week'));
        $end_date   = date('Y-m-d', strtotime('sunday this week'));
        break;
    case 'last_week':
        $start_date = date('Y-m-d', strtotime('monday last week'));
        $end_date   = date('Y-m-d', strtotime('sunday last week'));
        break;
    case 'this_month':
        $start_date = date('Y-m-01');
        $end_date   = date('Y-m-t');
        break;
    case 'last_month':
        $start_date = date('Y-m-01', strtotime('first day of last month'));
        $end_date   = date('Y-m-t', strtotime('last day of last month'));
        break;
    case 'this_year':
        $start_date = date('Y-01-01');
        $end_date   = date('Y-12-31');
        break;
    case 'last_year':
        $start_date = date('Y-01-01', strtotime('last year'));
        $end_date   = date('Y-12-31', strtotime('last year'));
        break;
    case 'custom':
        $start_date = $custom_start;
        $end_date   = $custom_end;
        break;
}

// Add cashier salaries as expenses automatically
$salary_stmt = $conn->prepare("
    SELECT cs.id, cs.total_salary AS salary, cs.month, cs.year 
    FROM cashier_salary cs
    WHERE cs.restaurant_id=? AND cs.status='Paid' AND STR_TO_DATE(CONCAT(cs.year,'-',cs.month,'-01'), '%Y-%M-%d') BETWEEN ? AND ?
");
$salary_stmt->bind_param("iss", $restaurant_id, $start_date, $end_date);
$salary_stmt->execute();
$salary_result = $salary_stmt->get_result();

$total_salary_expense = 0;
while($row = $salary_result->fetch_assoc()){
    $total_salary_expense += $row['salary'];
}
$salary_stmt->close();

// Sales & profit calculation
$sql = "
SELECT 
    oi.product_name,
    SUM(oi.quantity) AS total_qty,
    SUM(oi.price * oi.quantity) AS total_sales,
    SUM(p.cost_price * oi.quantity) AS total_cost,
    (SUM(oi.price * oi.quantity) - SUM(p.cost_price * oi.quantity)) AS profit
FROM order_items oi
JOIN orders o ON oi.order_id = o.id
JOIN products p ON oi.product_name = p.product_name
WHERE o.restaurant_id = ?
  AND DATE(o.created_at) BETWEEN ? AND ?
GROUP BY oi.product_name
ORDER BY profit DESC;
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $restaurant_id, $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

// Fetch other expenses for same period
$exp_sql = "SELECT * FROM expenses WHERE restaurant_id=? AND date BETWEEN ? AND ? ORDER BY date DESC";
$exp_stmt = $conn->prepare($exp_sql);
$exp_stmt->bind_param("iss", $restaurant_id, $start_date, $end_date);
$exp_stmt->execute();
$expenses = $exp_stmt->get_result();

// Calculate total expenses (including salaries)
$total_expenses = $total_salary_expense;
while ($row = $expenses->fetch_assoc()) {
    $total_expenses += $row['amount'];
}
$expenses->data_seek(0);

// Restaurant info
$stmt2 = $conn->prepare("SELECT restaurant_name, address FROM restaurants WHERE id=?");
$stmt2->bind_param("i", $restaurant_id);
$stmt2->execute();
$restaurant = $stmt2->get_result()->fetch_assoc();
$restaurantName = $restaurant['restaurant_name'] ?? "My Restaurant";
$restaurantAddress = $restaurant['address'] ?? "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profit Report - <?=htmlspecialchars($restaurantName)?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { overflow-x: hidden; background:#f5f7fa; font-family:'Inter',sans-serif; }
.sidebar { width: 180px; position: fixed; top: 56px; left: 0; height: 100%; background: #fff; border-right: 1px solid #e3e6f0; padding: 1rem; overflow-y: auto; transition: all 0.3s; }
.sidebar.collapsed { margin-left: -180px; }
.sidebar ul.nav li a { color:#495057; text-decoration:none; display:block; padding:12px 20px; border-left:4px solid transparent; border-radius:0 8px 8px 0; margin:4px 0; font-weight:500; transition: all 0.2s; }
.sidebar ul.nav li a:hover, .sidebar ul.nav li a.active { background: linear-gradient(90deg,#dbe4ff,#ffffff); border-left:4px solid #0d6efd; color:#0d6efd; }
.main-content { margin-left: 180px; padding: 20px; padding-top: 70px; transition: margin-left 0.3s; }
.main-content.expanded { margin-left: 0; }
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
        <li class="nav-item"><a href="categories.php"><i class="bi bi-tags me-2"></i>Categories</a></li>
        <li class="nav-item"><a href="cashiers.php"><i class="bi bi-people me-2"></i>Cashiers</a></li>
        <li class="nav-item"><a href="profit_report.php" class="active"><i class="bi bi-bar-chart-line me-2"></i>Profit Report</a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content" id="mainContent">
<h3>Profit & Expense Report</h3>

<!-- Filter Dropdown -->
<form method="get" class="row g-2 mb-4 align-items-end">
  <div class="col-md-4">
    <label>Filter Period</label>
    <select name="filter" class="form-select" onchange="this.form.submit()">
      <?php
      $filters = ['today'=>'Today','yesterday'=>'Yesterday','this_week'=>'This Week','last_week'=>'Last Week','this_month'=>'This Month','last_month'=>'Last Month','this_year'=>'This Year','last_year'=>'Last Year','custom'=>'Custom'];
      foreach($filters as $key=>$label){
          $selected = ($filter==$key) ? 'selected' : '';
          echo "<option value='$key' $selected>$label</option>";
      }
      ?>
    </select>
  </div>
  <div class="col-md-3" id="custom_start_div" style="display:<?=($filter=='custom')?'block':'none'?>">
    <label>Start Date</label>
    <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($custom_start) ?>">
  </div>
  <div class="col-md-3" id="custom_end_div" style="display:<?=($filter=='custom')?'block':'none'?>">
    <label>End Date</label>
    <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($custom_end) ?>">
  </div>
  <div class="col-md-2">
    <button class="btn btn-primary w-100">Filter</button>
  </div>
</form>

<!-- Profit Table -->
<h5 class="mt-4">Sales & Profit</h5>
<table class="table table-bordered table-striped">
<thead class="table-light">
  <tr>
    <th>Product</th>
    <th>Qty Sold</th>
    <th>Total Sales (Rs)</th>
    <th>Total Cost (Rs)</th>
    <th>Profit (Rs)</th>
  </tr>
</thead>
<tbody>
<?php
$grandSales = $grandCost = $grandProfit = 0;
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $grandSales += $row['total_sales'];
        $grandCost += $row['total_cost'];
        $grandProfit += $row['profit'];
        echo "<tr>
                <td>{$row['product_name']}</td>
                <td>{$row['total_qty']}</td>
                <td>" . number_format($row['total_sales'], 2) . "</td>
                <td>" . number_format($row['total_cost'], 2) . "</td>
                <td class='fw-bold text-success'>" . number_format($row['profit'], 2) . "</td>
              </tr>";
    }
    echo "<tr class='table-dark fw-bold'>
            <td colspan='2'>TOTAL</td>
            <td>" . number_format($grandSales, 2) . "</td>
            <td>" . number_format($grandCost, 2) . "</td>
            <td>" . number_format($grandProfit, 2) . "</td>
          </tr>";
} else {
    echo "<tr><td colspan='5' class='text-center text-muted'>No sales data found for this period.</td></tr>";
}
?>
</tbody>
</table>

<!-- Expenses Section -->
<h5 class="mt-5">Expenses (Including Salaries)</h5>
<form method="POST" class="row g-2 mb-3">
  <div class="col-md-4"><input type="text" name="expense_name" class="form-control" placeholder="Expense Name" required></div>
  <div class="col-md-3"><input type="number" step="0.01" name="amount" class="form-control" placeholder="Amount (Rs)" required></div>
  <div class="col-md-3"><input type="date" name="date" class="form-control" value="<?=date('Y-m-d')?>" required></div>
  <div class="col-md-2"><button name="add_expense" class="btn btn-success w-100">Add</button></div>
</form>

<table class="table table-bordered">
<thead class="table-light">
  <tr><th>Date</th><th>Expense Name</th><th>Amount (Rs)</th></tr>
</thead>
<tbody>
<?php
if ($expenses->num_rows > 0) {
    while ($row = $expenses->fetch_assoc()) {
        echo "<tr><td>{$row['date']}</td><td>{$row['expense_name']}</td><td>" . number_format($row['amount'],2) . "</td></tr>";
    }
    echo "<tr class='table-warning fw-bold'><td colspan='2'>Total Expenses</td><td>" . number_format($total_expenses,2) . "</td></tr>";
} else {
    echo "<tr><td colspan='3' class='text-center text-muted'>No expenses found for this period.</td></tr>";
}
?>
<tr class="table-info fw-bold"><td colspan="2">Total Salaries</td><td><?= number_format($total_salary_expense,2) ?></td></tr>
</tbody>
</table>

<!-- Net Profit -->
<h4 class="mt-4">Net Profit: 
<span class="text-primary fw-bold">
<?= number_format($grandProfit - $total_expenses, 2) ?> Rs
</span></h4>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');
document.getElementById('sidebarToggle').addEventListener('click', ()=>{
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('expanded');
});

// Show custom date inputs
const filterSelect = document.querySelector('select[name="filter"]');
const customStartDiv = document.getElementById('custom_start_div');
const customEndDiv = document.getElementById('custom_end_div');
filterSelect.addEventListener('change', function(){
    if(this.value=='custom'){
        customStartDiv.style.display='block';
        customEndDiv.style.display='block';
    } else {
        customStartDiv.style.display='none';
        customEndDiv.style.display='none';
    }
});
</script>
</body>
</html>
