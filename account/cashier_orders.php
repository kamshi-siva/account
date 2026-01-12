<?php
session_start();
require_once "config.php";

if ($_SESSION['role'] != 'cashier') {
    header("Location: dashboard.php");
    exit();
}

$cashier_id = $_SESSION['cashier_id'];
$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to'] ?? date('Y-m-d');

$query = "
SELECT o.id, o.order_number, o.total, o.payment_method, o.created_at
FROM orders o
WHERE o.cashier_id = $cashier_id AND created_at BETWEEN '$from' AND '$to'
ORDER BY created_at DESC
";

$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Orders</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
<h3>My Order History (<?=htmlspecialchars($from)?> to <?=htmlspecialchars($to)?>)</h3>
<form method="GET" class="row g-3 mb-3">
    <div class="col-auto"><input type="date" name="from" class="form-control" value="<?=htmlspecialchars($from)?>"></div>
    <div class="col-auto"><input type="date" name="to" class="form-control" value="<?=htmlspecialchars($to)?>"></div>
    <div class="col-auto"><button class="btn btn-primary">Filter</button></div>
</form>

<table class="table table-bordered table-striped">
    <thead class="table-primary">
        <tr>
            <th>#</th>
            <th>Order Number</th>
            <th>Total</th>
            <th>Payment Method</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        <?php $i=1; while($row=$result->fetch_assoc()): ?>
        <tr>
            <td><?=$i++?></td>
            <td><?=htmlspecialchars($row['order_number'])?></td>
            <td><?=number_format($row['total'],2)?></td>
            <td><?=htmlspecialchars($row['payment_method'])?></td>
            <td><?=$row['created_at']?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
</div>
</body>
</html>
