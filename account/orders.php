<?php
session_start();
include "config.php";

if (!isset($_SESSION['restaurant_id'])) {
    header("Location: login.php");
    exit();
}

$restaurant_id = $_SESSION['restaurant_id'] ?? 0;

// Fetch restaurant info
$stmt = $conn->prepare("SELECT restaurant_name, address FROM restaurants WHERE id=?");
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$restaurant = $stmt->get_result()->fetch_assoc();
$restaurantName = $restaurant['restaurant_name'] ?? "My Restaurant";
$restaurantAddress = $restaurant['address'] ?? "";

// Orders filter options
$filter_options = [
    'today' => 'Today',
    'yesterday' => 'Yesterday',
    'week' => 'This Week',
    'last_week' => 'Last Week',
    'month' => 'This Month',
    'last_month' => 'Last Month',
    'year' => 'This Year',
    'last_year' => 'Last Year',
];

$filter = $_GET['filter'] ?? '';
$where = "o.restaurant_id = $restaurant_id";
switch($filter){
    case 'today': $where .= " AND DATE(o.created_at) = CURDATE()"; break;
    case 'yesterday': $where .= " AND DATE(o.created_at) = CURDATE() - INTERVAL 1 DAY"; break;
    case 'week': $where .= " AND YEARWEEK(o.created_at,1) = YEARWEEK(CURDATE(),1)"; break;
    case 'last_week': $where .= " AND YEARWEEK(o.created_at,1) = YEARWEEK(CURDATE(),1) - 1"; break;
    case 'month': $where .= " AND YEAR(o.created_at) = YEAR(CURDATE()) AND MONTH(o.created_at) = MONTH(CURDATE())"; break;
    case 'last_month': $where .= " AND YEAR(o.created_at) = YEAR(CURDATE() - INTERVAL 1 MONTH) AND MONTH(o.created_at) = MONTH(CURDATE() - INTERVAL 1 MONTH)"; break;
    case 'year': $where .= " AND YEAR(o.created_at) = YEAR(CURDATE())"; break;
    case 'last_year': $where .= " AND YEAR(o.created_at) = YEAR(CURDATE() - INTERVAL 1 YEAR)"; break;
}

// Fetch orders
$orders = $conn->query("
    SELECT o.*, c.name AS cashier_name
    FROM orders o
    LEFT JOIN cashiers c ON o.cashier_id = c.id
    WHERE $where
    ORDER BY o.created_at DESC
");

// API for order items
if(isset($_GET['order_items']) && is_numeric($_GET['order_items'])){
    $order_id = (int)$_GET['order_items'];
    $itemsRes = $conn->query("SELECT * FROM order_items WHERE order_id=$order_id");
    $items = [];
    while($row = $itemsRes->fetch_assoc()) $items[] = $row;
    echo json_encode($items);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Orders - <?=htmlspecialchars($restaurantName)?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { overflow-x: hidden; background:#f5f7fa; font-family:'Inter',sans-serif; }

/* Sidebar same as dashboard */
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

.order-row:hover { background-color:#f0f0f0; cursor:pointer; }
#printArea { display:none; }
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
        <li class="nav-item"><a href="orders.php" class="active"><i class="bi bi-receipt me-2"></i>Orders</a></li>
        <li class="nav-item"><a href="products.php"><i class="bi bi-box-seam me-2"></i>Products</a></li>
        <li class="nav-item"><a href="categories.php"><i class="bi bi-tags me-2"></i>Categories</a></li>
        <li class="nav-item"><a href="cashiers.php"><i class="bi bi-people me-2"></i>Cashiers</a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content" id="mainContent">

    <h3>Orders</h3>

    <!-- Filter -->
    <form method="get" class="mb-3 w-25">
        <select name="filter" class="form-select" onchange="this.form.submit()">
            <option value="">All Orders</option>
            <?php foreach($filter_options as $key=>$label): ?>
                <option value="<?=$key?>" <?=($filter==$key)?'selected':''?>><?=$label?></option>
            <?php endforeach; ?>
        </select>
    </form>

    <!-- Orders Table -->
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>#</th>
                <th>Order #</th>
                <th>Cashier</th>
                <th>Total (Rs)</th>
                <th>Payment</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php 
        if($orders && $orders->num_rows > 0):
            $i=1;
            while($o = $orders->fetch_assoc()):
        ?>
        <tr class="order-row">
            <td><?=$i++?></td>
            <td><?=htmlspecialchars($o['order_number'] ?? $o['id'])?></td>
            <td><?=htmlspecialchars($o['cashier_name'] ?? 'N/A')?></td>
            <td><?=number_format($o['total'],2)?></td>
            <td><?=htmlspecialchars($o['payment_method'])?></td>
            <td><?=date("d-m-Y H:i", strtotime($o['created_at']))?></td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="viewOrderItems(<?=$o['id']?>)">View Items</button>
                <button class="btn btn-sm btn-success" onclick="printOrder(<?=$o['id']?>)">Print</button>
            </td>
        </tr>
        <?php endwhile; else: ?>
        <tr><td colspan="7" class="text-center">No orders found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

</div>

<!-- Order Modal -->
<div class="modal fade" id="orderModal" tabindex="-1">
<div class="modal-dialog modal-lg">
<div class="modal-content">
<div class="modal-header">
<h5 class="modal-title">Order Details</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
<table class="table table-bordered">
<thead>
<tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th></tr>
</thead>
<tbody id="orderItemsBody"></tbody>
</table>
<div class="text-end fw-bold">Subtotal: Rs <span id="modalSubtotal">0.00</span></div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
</div>
</div>
</div>
</div>

<div id="printArea"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');
document.getElementById('sidebarToggle').addEventListener('click', ()=>{
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('expanded');
});

function viewOrderItems(orderId){
    fetch('?order_items='+orderId)
    .then(res=>res.json())
    .then(data=>{
        let tbody=document.getElementById('orderItemsBody'); tbody.innerHTML=''; let subtotal=0;
        data.forEach(item=>{
            let total=(parseFloat(item.price)*parseInt(item.quantity)).toFixed(2);
            subtotal += parseFloat(total);
            tbody.innerHTML += `<tr>
                <td>${item.product_name}</td>
                <td>${item.quantity}</td>
                <td>Rs ${parseFloat(item.price).toFixed(2)}</td>
                <td>Rs ${total}</td>
            </tr>`;
        });
        document.getElementById('modalSubtotal').innerText=subtotal.toFixed(2);
        new bootstrap.Modal(document.getElementById('orderModal')).show();
    });
}

function printOrder(orderId){
    fetch('?order_items='+orderId)
    .then(res=>res.json())
    .then(data=>{
        let printArea=document.getElementById('printArea'); 
        let html='<h3>Receipt</h3><table border="1" width="100%"><tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th></tr>'; 
        let subtotal=0;
        data.forEach(item=>{
            let total=(parseFloat(item.price)*parseInt(item.quantity)).toFixed(2);
            subtotal += parseFloat(total);
            html += `<tr>
                <td>${item.product_name}</td>
                <td>${item.quantity}</td>
                <td>Rs ${parseFloat(item.price).toFixed(2)}</td>
                <td>Rs ${total}</td>
            </tr>`;
        });
        html += `</table><div style="text-align:right;font-weight:bold;">Subtotal: Rs ${subtotal.toFixed(2)}</div>`;
        printArea.innerHTML=html; window.print();
    });
}
</script>
</body>
</html>
