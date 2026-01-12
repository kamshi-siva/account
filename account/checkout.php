<?php
session_start();
include "config.php";

if (!isset($_SESSION['cashier_id'])) {
    header("Location: login.php");
    exit();
}

$cashier_id    = $_SESSION['cashier_id'];
$cashier_name  = $_SESSION['cashier_name'] ?? 'Cashier';
$restaurant_id = $_SESSION['restaurant_id'];

$date = date("d M Y");
$time = date("h:i A");

// Default restaurant info
$restaurant_name    = "My POS Restaurant";
$restaurant_address = "123 Main Street, City";
$restaurant_phone   = "011-2345678";

// Fetch restaurant info
if ($restaurant_id) {
    $resStmt = $conn->prepare("SELECT restaurant_name, address, phone FROM restaurants WHERE id = ?");
    $resStmt->bind_param("i", $restaurant_id);
    $resStmt->execute();
    $resStmt->bind_result($rName, $rAddress, $rPhone);
    if ($resStmt->fetch()) {
        $restaurant_name    = $rName;
        $restaurant_address = $rAddress;
        $restaurant_phone   = $rPhone;
    }
    $resStmt->close();
}
// Default charges
$tax_rate = 0;
$service_charge_rate = 0;
$table_charge = 0;

// Fetch charges from restaurants table
if ($restaurant_id) {
    $chargeStmt = $conn->prepare("
        SELECT tax_rate, service_charge_rate, table_charge
        FROM restaurants
        WHERE id = ?
    ");
    $chargeStmt->bind_param("i", $restaurant_id);
    $chargeStmt->execute();
    $chargeStmt->bind_result($tax_rate, $service_charge_rate, $table_charge);
    $chargeStmt->fetch();
    $chargeStmt->close();
}

// Handle checkout POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['cartData'])) {

    $cart = json_decode($_POST['cartData'], true);
    if (!is_array($cart) || count($cart) === 0) {
        die("<p class='text-danger'>Cart is empty or invalid data.</p>");
    }

    $order_number   = "ORD" . time() . rand(100, 999);
    $payment_method = $_POST['payment_method'] ?? 'Cash';
    $paid_amount    = floatval($_POST['paid_amount'] ?? 0);
    $customer_phone = trim($_POST['customer_phone'] ?? '');

    // Calculate totals
    $subtotal = 0;
    foreach ($cart as $item) {
        $price = floatval($item['price'] ?? 0);
        $qty   = intval($item['qty'] ?? 0);
        $itemTotal = $price * $qty;
        if (!empty($item['extras'])) {
            foreach ($item['extras'] as $ex) {
                $extraPrice = floatval($ex['price'] ?? 0);
                $extraQty   = intval($ex['qty'] ?? 1);
                $itemTotal += $extraPrice * $extraQty;
            }
        }
        $subtotal += $itemTotal;
    }

    $tax_amount     = round($subtotal * ($tax_rate / 100), 2);
$service_amount = round($subtotal * ($service_charge_rate / 100), 2);
$table_amount   = round($table_charge, 2);

$total = round($subtotal + $tax_amount + $service_amount + $table_amount, 2);
$balance = round($paid_amount - $total, 2);


    // Insert order
    $stmt = $conn->prepare("INSERT INTO orders (restaurant_id, cashier_id, order_number, total, payment_method, customer_phone) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisdss", $restaurant_id, $cashier_id, $order_number, $total, $payment_method, $customer_phone);
    $stmt->execute();
    $order_id = $stmt->insert_id;
    $stmt->close();

    // Insert items + update stock
    $itemStmt = $conn->prepare("INSERT INTO order_items (order_id, product_name, quantity, price) VALUES (?, ?, ?, ?)");
    $updateStockStmt = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE restaurant_id = ? AND product_name = ?");

    foreach ($cart as $item) {
        $name = $item['name'];
        $qty  = intval($item['qty']);
        $price = floatval($item['price']);

        $itemStmt->bind_param("isid", $order_id, $name, $qty, $price);
        $itemStmt->execute();

        if (!empty($item['extras'])) {
            foreach ($item['extras'] as $ex) {
                $extraName = "→ " . $ex['name'];
                $extraQty  = intval($ex['qty']);
                $extraPrice = floatval($ex['price']);
                $itemStmt->bind_param("isid", $order_id, $extraName, $extraQty, $extraPrice);
                $itemStmt->execute();
            }
        }

        $updateStockStmt->bind_param("iis", $qty, $restaurant_id, $name);
        $updateStockStmt->execute();
    }

    $itemStmt->close();
    $updateStockStmt->close();

} else {
    die("<p class='text-danger'>Invalid request.</p>");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>POS Receipt</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { font-family: 'Courier New', monospace; background:#f8f9fa; }
.receipt { max-width: 350px; margin: 20px auto; background: #fff; padding: 15px; border: 1px dashed #333; }
.receipt h2 { font-size: 18px; margin: 0; }
.receipt p { margin: 0; font-size: 12px; }
.table th, .table td { font-size: 12px; padding: 4px; }
.table tfoot td { font-weight: bold; }
.divider { border-top: 1px dashed #333; margin: 6px 0; }
.print-btn, .whatsapp-btn, .back-btn, .sms-btn { width:100%; margin-top:8px; font-weight:bold; }
.whatsapp-btn { background:#25D366; color:white; border:none; }
.sms-btn { background:#ffc107; color:#000; border:none; }
@media print {
    .print-btn, .whatsapp-btn, .back-btn, .sms-btn { display:none; }
    body { background:#fff; }
    .receipt { border:none; margin:0; padding:0; }
}
</style>
</head>
<body>

<div class="receipt">
    <div class="text-center mb-2">
        <h2 class="text-dark">🍴 <?= htmlspecialchars($restaurant_name) ?></h2>
        <p><?= htmlspecialchars($restaurant_address) ?></p>
        <p>Tel: <?= htmlspecialchars($restaurant_phone) ?></p>
        <p>Date: <?= htmlspecialchars($date) ?></p>
        <p>Time: <span id="currentTime"><?= htmlspecialchars($time) ?></span></p>
        <p><strong>Invoice #:</strong> <?= htmlspecialchars($order_number) ?></p>
        <div class="divider"></div>
    </div>

<?php
if (!empty($cart)) {
    echo "<p><strong>Cashier:</strong> ".htmlspecialchars($cashier_name)."<br>
          <strong>Payment:</strong> ".htmlspecialchars($payment_method)."<br>
          <strong>Customer:</strong> ".htmlspecialchars($customer_phone)."</p>
          <div class='divider'></div>";

    echo "<table class='table table-borderless'>
            <thead>
                <tr>
                    <th>Item</th>
                    <th class='text-center'>Qty</th>
                    <th class='text-end'>Rate</th>
                    <th class='text-end'>Amt</th>
                </tr>
            </thead>
            <tbody>";

    foreach ($cart as $item) {
        $name = htmlspecialchars($item['name'], ENT_QUOTES);
        $price = floatval($item['price']);
        $qty   = intval($item['qty']);
        $lineTotal = round($price * $qty, 2);

        echo "<tr>
                <td>{$name}</td>
                <td class='text-center'>{$qty}</td>
                <td class='text-end'>".number_format($price,2)."</td>
                <td class='text-end'>".number_format($lineTotal,2)."</td>
              </tr>";

        if (!empty($item['extras'])) {
            foreach ($item['extras'] as $ex) {
                $extraName  = htmlspecialchars($ex['name']);
                $extraQty   = intval($ex['qty']);
                $extraPrice = floatval($ex['price']);
                echo "<tr>
                        <td class='ps-3 text-muted'>→ {$extraName}</td>
                        <td class='text-center'>{$extraQty}</td>
                        <td class='text-end'>".number_format($extraPrice,2)."</td>
                        <td class='text-end'>".number_format($extraPrice * $extraQty,2)."</td>
                      </tr>";
            }
        }
    }

    echo "</tbody>
          <tfoot>
            <tr><td colspan='3' class='text-end'>Subtotal</td><td class='text-end'>".number_format($subtotal,2)."</td></tr>
            <tr><td colspan='3' class='text-end'>Tax (5%)</td><td class='text-end'>".number_format($tax,2)."</td></tr>
            <tr><td colspan='3' class='text-end fw-bold'>Total</td><td class='text-end fw-bold'>".number_format($total,2)."</td></tr>
            <tr><td colspan='3' class='text-end'>Paid</td><td class='text-end'>".number_format($paid_amount,2)."</td></tr>
            <tr><td colspan='3' class='text-end'>Change</td><td class='text-end'>".number_format($balance,2)."</td></tr>
          </tfoot>
          </table>";

    echo "<button class='btn btn-dark btn-sm print-btn' onclick='window.print()'>🖨 Print Receipt</button>";

    if (!empty($customer_phone)) {
        echo "<button id='sendWhatsApp' class='btn whatsapp-btn btn-sm'>📱 Send Invoice via WhatsApp</button>";
        echo "<button id='sendSMS' class='btn sms-btn btn-sm'>📩 Send Invoice via SMS</button>";
    }

    echo "<a href='pos.php' class='btn btn-primary btn-sm back-btn'>🔙 Back to POS</a>";
} else {
    echo "<p class='text-danger'>No cart data received.</p>";
}
?>

<div class="text-center mt-3">
    <div class="divider"></div>
    <p class="small">Thank you! Visit Again 🙏</p>
</div>
</div>

<script>
function updateTime() {
    const now = new Date();
    const options = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };
    document.getElementById('currentTime').textContent = now.toLocaleTimeString('en-US', options);
}
updateTime();
setInterval(updateTime, 1000);

document.addEventListener("DOMContentLoaded", () => {
    // WhatsApp button
    const waBtn = document.getElementById("sendWhatsApp");
    if (waBtn) {
        waBtn.addEventListener("click", () => {
            let phone = "<?= $customer_phone ?>";
            const message = `Thank you for your purchase at <?= addslashes($restaurant_name) ?>! 🧾
Invoice No: <?= $order_number ?>
Total: Rs. <?= $total ?>
View your receipt here: https://yourdomain.com/view_invoice.php?order=<?= $order_number ?>`;
            if (phone.trim() !== "") {
                const waLink = "https://wa.me/" + encodeURIComponent(phone) + "?text=" + encodeURIComponent(message);
                window.open(waLink, "_blank");
            } else alert("Customer phone number not available!");
        });
    }

    // SMS button
    const smsBtn = document.getElementById("sendSMS");
    if (smsBtn) {
        smsBtn.addEventListener("click", () => {
            let phone = "<?= $customer_phone ?>".trim();
            const message = `Thank you for your purchase at <?= addslashes($restaurant_name) ?>! 🧾
Invoice No: <?= $order_number ?>
Total: Rs. <?= $total ?>
View your receipt here: https://yourdomain.com/view_invoice.php?order=<?= $order_number ?>`;

            // Normalize phone to +94 format
            phone = phone.replace(/\D/g, "");
            if (phone.startsWith("0")) phone = "+94" + phone.substring(1);
            else if (!phone.startsWith("+94")) phone = "+94" + phone;

            if (phone !== "") {
                fetch("send_sms.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ phone: phone, message: message })
                })
                .then(res => res.json())
                .then(data => alert(data.status || "SMS sent successfully!"))
                .catch(err => alert("Error sending SMS: " + err));
            } else alert("Customer phone number not available!");
        });
    }
});
</script>

</body>
</html>
