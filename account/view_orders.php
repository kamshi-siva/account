<?php
session_start();
require_once "config.php";

// 🔒 Protect page
if (!isset($_SESSION['restaurant_admin_id'])) {
    header("Location: restaurant_login.php");
    exit();
}

$restaurant_id = $_SESSION['restaurant_admin_id'];

// ✅ Filters
$filter_cashier = $_GET['cashier'] ?? '';
$filter_from = $_GET['from'] ?? '';
$filter_to = $_GET['to'] ?? '';

$where = "WHERE o.restaurant_id = ?";
$params = [$restaurant_id];
$types = "i";

if ($filter_cashier !== '') {
    $where .= " AND o.cashier_id = ?";
    $params[] = $filter_cashier;
    $types .= "i";
}

if ($filter_from !== '' && $filter_to !== '') {
    $where .= " AND DATE(o.created_at) BETWEEN ? AND ?";
    $params[] = $filter_from;
    $params[] = $filter_to;
    $types .= "ss";
}

// ✅ Get Orders with filters
$sql = "SELECT o.*, c.name AS cashier_name 
        FROM orders o
        JOIN cashiers c ON o.cashier_id = c.id
        $where
        ORDER BY o.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result();
$stmt->close();

// ✅ Get Cashiers for filter dropdown
$cashiers = $conn->query("SELECT id, name FROM cashiers WHERE restaurant_id = $restaurant_id");
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>View Orders</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">

  <h2>Orders History</h2>
  <a href="restaurant_dashboard.php" class="btn btn-secondary mb-3">⬅ Back to Dashboard</a>

  <!-- 🔍 Filter Form -->
  <form method="get" class="row g-3 mb-3">
    <div class="col-md-3">
      <label class="form-label">From</label>
      <input type="date" name="from" value="<?=htmlspecialchars($filter_from)?>" class="form-control">
    </div>
    <div class="col-md-3">
      <label class="form-label">To</label>
      <input type="date" name="to" value="<?=htmlspecialchars($filter_to)?>" class="form-control">
    </div>
    <div class="col-md-3">
      <label class="form-label">Cashier</label>
      <select name="cashier" class="form-select">
        <option value="">All</option>
        <?php while($c = $cashiers->fetch_assoc()): ?>
          <option value="<?=$c['id']?>" <?=($filter_cashier==$c['id'])?"selected":""?>><?=htmlspecialchars($c['name'])?></option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="col-md-3 align-self-end">
      <button class="btn btn-primary">Filter</button>
      <a href="view_orders.php" class="btn btn-secondary">Reset</a>
      <a href="export_orders_pdf.php?from=<?=$filter_from?>&to=<?=$filter_to?>&cashier=<?=$filter_cashier?>" class="btn btn-danger">Export PDF</a>
    </div>
  </form>

  <!-- Orders Table -->
  <div class="card shadow-sm">
    <div class="card-body">
      <table class="table table-bordered table-striped">
        <thead>
          <tr>
            <th>Order #</th>
            <th>Cashier</th>
            <th>Total (Rs)</th>
            <th>Payment Method</th>
            <th>Date</th>
            <th>Items</th>
          </tr>
        </thead>
        <tbody>
          <?php while($order = $orders->fetch_assoc()): ?>
          <tr>
            <td><?=htmlspecialchars($order['order_number'])?></td>
            <td><?=htmlspecialchars($order['cashier_name'])?></td>
            <td><?=number_format($order['total'],2)?></td>
            <td><?=$order['payment_method']?></td>
            <td><?=$order['created_at']?></td>
            <td>
              <button class="btn btn-sm btn-info" 
                      data-bs-toggle="collapse" 
                      data-bs-target="#items<?=$order['id']?>">View</button>
            </td>
          </tr>
          <tr class="collapse" id="items<?=$order['id']?>">
            <td colspan="6">
              <?php
                $items_sql = "SELECT * FROM order_items WHERE order_id = ?";
                $stmt2 = $conn->prepare($items_sql);
                $stmt2->bind_param("i", $order['id']);
                $stmt2->execute();
                $items = $stmt2->get_result();
                $stmt2->close();
              ?>
              <table class="table table-sm table-bordered">
                <thead>
                  <tr>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Price (Rs)</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while($item = $items->fetch_assoc()): ?>
                  <tr>
                    <td><?=htmlspecialchars($item['product_name'])?></td>
                    <td><?=$item['quantity']?></td>
                    <td><?=number_format($item['price'],2)?></td>
                  </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
