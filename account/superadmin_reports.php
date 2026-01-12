<?php
session_start();
require_once "config.php";

// Only Super Admin
if ($_SESSION['role'] != 'super_admin') {
    header("Location: dashboard.php");
    exit();
}

// Date filter
$from = $_GET['from'] ?? date('Y-m-01'); // start of month
$to   = $_GET['to'] ?? date('Y-m-d');    // today

$query = "
SELECT r.restaurant_name,
       COUNT(o.id) AS total_orders,
       SUM(o.total) AS total_sales,
       SUM(CASE WHEN p.quantity <= (SELECT low_stock_limit FROM settings WHERE restaurant_id=r.id) THEN 1 ELSE 0 END) AS low_stock_count
FROM restaurants r
LEFT JOIN orders o ON o.restaurant_id = r.id AND o.created_at BETWEEN '$from' AND '$to'
LEFT JOIN products p ON p.restaurant_id = r.id
GROUP BY r.id
ORDER BY total_sales DESC
";

$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Super Admin Reports</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h3>Super Admin Reports (<?=htmlspecialchars($from)?> to <?=htmlspecialchars($to)?>)</h3>
    <form method="GET" class="row g-3 mb-3">
        <div class="col-auto"><input type="date" name="from" class="form-control" value="<?=htmlspecialchars($from)?>"></div>
        <div class="col-auto"><input type="date" name="to" class="form-control" value="<?=htmlspecialchars($to)?>"></div>
        <div class="col-auto"><button class="btn btn-primary">Filter</button></div>
    </form>
    <table class="table table-bordered table-striped">
        <thead class="table-primary">
            <tr>
                <th>#</th>
                <th>Branch Name</th>
                <th>Total Orders</th>
                <th>Total Sales</th>
                <th>Low Stock Products</th>
            </tr>
        </thead>
        <tbody>
            <?php $i=1; while($row=$result->fetch_assoc()): ?>
            <tr>
                <td><?=$i++?></td>
                <td><?=htmlspecialchars($row['restaurant_name'])?></td>
                <td><?=$row['total_orders']?></td>
                <td><?=number_format($row['total_sales'],2)?></td>
                <td><?=$row['low_stock_count']?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
