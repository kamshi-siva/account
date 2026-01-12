<?php
session_start();
require_once "config.php";

if ($_SESSION['role'] != 'admin') {
    header("Location: dashboard.php");
    exit();
}

$restaurant_id = $_SESSION['restaurant_id'];
$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to'] ?? date('Y-m-d');

// Total orders & sales
$sales = $conn->query("
SELECT COUNT(*) AS total_orders, SUM(total) AS total_sales
FROM orders
WHERE restaurant_id=$restaurant_id AND created_at BETWEEN '$from' AND '$to'
")->fetch_assoc();

// Low stock
$low_stock = $conn->query("
SELECT product_name, quantity
FROM products
WHERE restaurant_id=$restaurant_id AND quantity <= (SELECT low_stock_limit FROM settings WHERE restaurant_id=$restaurant_id)
");

// Expiring products
$expiring = $conn->query("
SELECT p.product_name, MIN(b.expiry_date) AS nearest_expiry
FROM product_batches b
JOIN products p ON p.id = b.product_id
WHERE p.restaurant_id=$restaurant_id
GROUP BY p.id
HAVING DATEDIFF(nearest_expiry, CURDATE()) <= (SELECT expiry_alert_days FROM settings WHERE restaurant_id=$restaurant_id)
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Reports</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h3>Branch Reports (<?=htmlspecialchars($from)?> to <?=htmlspecialchars($to)?>)</h3>
    <form method="GET" class="row g-3 mb-3">
        <div class="col-auto"><input type="date" name="from" class="form-control" value="<?=htmlspecialchars($from)?>"></div>
        <div class="col-auto"><input type="date" name="to" class="form-control" value="<?=htmlspecialchars($to)?>"></div>
        <div class="col-auto"><button class="btn btn-primary">Filter</button></div>
    </form>

    <p>Total Orders: <?=$sales['total_orders']?> | Total Sales: <?=number_format($sales['total_sales'],2)?></p>

    <h5>Low Stock Products</h5>
    <table class="table table-bordered">
        <thead><tr><th>#</th><th>Product</th><th>Quantity</th></tr></thead>
        <tbody>
        <?php $i=1; while($row=$low_stock->fetch_assoc()): ?>
            <tr>
                <td><?=$i++?></td>
                <td><?=htmlspecialchars($row['product_name'])?></td>
                <td><?=$row['quantity']?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <h5>Expiring Products</h5>
    <table class="table table-bordered">
        <thead><tr><th>#</th><th>Product</th><th>Expiry Date</th></tr></thead>
        <tbody>
        <?php $i=1; while($row=$expiring->fetch_assoc()): ?>
            <tr>
                <td><?=$i++?></td>
                <td><?=htmlspecialchars($row['product_name'])?></td>
                <td><?=$row['nearest_expiry']?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
