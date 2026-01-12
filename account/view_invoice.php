<?php
include "config.php";

$order_number = $_GET['order'] ?? '';

if (empty($order_number)) {
    die("<p class='text-danger text-center mt-5'>Invalid invoice link.</p>");
}

// Fetch order details
$stmt = $conn->prepare("
    SELECT o.id, o.order_number, o.total, o.payment_method, o.restaurant_id, 
           o.cashier_id, o.created_at, o.customer_phone,
           r.restaurant_name, r.address, r.phone AS restaurant_phone,
           c.name AS cashier_name
    FROM orders o
    LEFT JOIN restaurants r ON o.restaurant_id = r.id
    LEFT JOIN cashiers c ON o.cashier_id = c.id
    WHERE o.order_number = ?
");
$stmt->bind_param("s", $order_number);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("<p class='text-danger text-center mt-5'>Invoice not found.</p>");
}

$order = $result->fetch_assoc();
$stmt->close();

// Fetch order items
$itemStmt = $conn->prepare("SELECT product_name, quantity, price FROM order_items WHERE order_id = ?");
$itemStmt->bind_param("i", $order['id']);
$itemStmt->execute();
$items = $itemStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$itemStmt->close();

// Calculations
$subtotal = 0;
foreach ($items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$tax = round($subtotal * 0.05, 2);
$total = round($subtotal + $tax, 2);

// Format date & time
$date = date("d M Y", strtotime($order['created_at']));
$time = date("h:i A", strtotime($order['created_at']));
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Invoice - <?= htmlspecialchars($order['order_number']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { font-family: 'Courier New', monospace; background:#f8f9fa; }
.receipt { max-width: 350px; margin: 20px auto; background: #fff; padding: 15px; border: 1px dashed #333; }
.receipt h2 { font-size: 18px; margin: 0; }
.receipt p { margin: 0; font-size: 12px; }
.table th, .table td { font-size: 12px; padding: 4px; }
.table tfoot td { font-weight: bold; }
.divider { border-top: 1px dashed #333; margin: 6px 0; }
.print-btn { width:100%; margin-top:10px; }
@media print {
    .print-btn { display:none; }
    body { background:#fff; }
    .receipt { border:none; margin:0; padding:0; }
}
</style>
</head>
<body>

<div class="receipt">
    <div class="text-center mb-2">
        <h2 class="text-dark">🍴 <?= htmlspecialchars($order['restaurant_name'] ?? "My Restaurant") ?></h2>
        <p><?= htmlspecialchars($order['address'] ?? "Address not available") ?></p>
        <p>Tel: <?= htmlspecialchars($order['restaurant_phone'] ?? "-") ?></p>
        <p>Date: <?= htmlspecialchars($date) ?></p>
        <p>Time: <?= htmlspecialchars($time) ?></p>
        <p><strong>Invoice #:</strong> <?= htmlspecialchars($order['order_number']) ?></p>
        <div class="divider"></div>
    </div>

    <p><strong>Cashier:</strong> <?= htmlspecialchars($order['cashier_name'] ?? 'N/A') ?><br>
       <strong>Payment:</strong> <?= htmlspecialchars($order['payment_method']) ?></p>
    <div class="divider"></div>

    <table class="table table-borderless">
        <thead>
            <tr>
                <th>Item</th>
                <th class="text-center">Qty</th>
                <th class="text-end">Rate</th>
                <th class="text-end">Amt</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): 
                $lineTotal = $item['price'] * $item['quantity']; ?>
                <tr>
                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                    <td class="text-center"><?= $item['quantity'] ?></td>
                    <td class="text-end"><?= number_format($item['price'], 2) ?></td>
                    <td class="text-end"><?= number_format($lineTotal, 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr><td colspan="3" class="text-end">Subtotal</td><td class="text-end"><?= number_format($subtotal,2) ?></td></tr>
            <tr><td colspan="3" class="text-end">Tax (5%)</td><td class="text-end"><?= number_format($tax,2) ?></td></tr>
            <tr><td colspan="3" class="text-end fw-bold">Total</td><td class="text-end fw-bold"><?= number_format($total,2) ?></td></tr>
        </tfoot>
    </table>

    <button class="btn btn-dark btn-sm print-btn" onclick="window.print()">🖨 Print Invoice</button>

    <div class="text-center mt-3">
        <div class="divider"></div>
        <p class="small">Thank you for dining with us! 🙏</p>
    </div>
</div>

</body>
</html>
