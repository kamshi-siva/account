<?php
session_start();
include "config.php";

if (!isset($_SESSION['restaurant_admin_id']) && !isset($_SESSION['cashier_id'])) {
    header("Location: index.php");
    exit();
}

$restaurant_id = $_SESSION['restaurant_id'] ?? 0;
$product_id = intval($_GET['product_id'] ?? 0);

$batches = $conn->query("
    SELECT * 
    FROM product_batches
    WHERE product_id=$product_id AND restaurant_id=$restaurant_id
    ORDER BY expiry_date ASC
");
?>

<h3>Batches for Product ID <?= $product_id ?></h3>
<table class="table table-bordered">
<tr>
<th>Batch ID</th>
<th>Quantity</th>
<th>Expiry Date</th>
</tr>
<?php while($b = $batches->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($b['id']) ?></td>
<td><?= htmlspecialchars($b['quantity']) ?></td>
<td><?= htmlspecialchars($b['expiry_date']) ?></td>
</tr>
<?php endwhile; ?>
</table>
