<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['super_admin_id'])) {
    header("Location: super_admin_login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("Missing request ID.");
}

$request_id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM restaurant_requests WHERE id=? AND status='Pending' LIMIT 1");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$res = $stmt->get_result();
$request = $res->fetch_assoc();
$stmt->close();

if (!$request) {
    die("Request not found or already processed.");
}

$original_phone = $request['phone'];
$phone = $original_phone;
$suffix = 1;

while (true) {
    $stmt_check = $conn->prepare("SELECT id FROM restaurants WHERE phone=? LIMIT 1");
    $stmt_check->bind_param("s", $phone);
    $stmt_check->execute();
    $stmt_check->store_result();
    $exists = $stmt_check->num_rows > 0;
    $stmt_check->close();

    if (!$exists) break; 
    $phone = $original_phone . '-' . $suffix;
    $suffix++;
}

$ins = $conn->prepare("INSERT INTO restaurants (restaurant_name, address, phone, logo, password, status) VALUES (?, ?, ?, ?, ?, 'Active')");
$ins->bind_param(
    "sssss",
    $request['restaurant_name'],
    $request['address'],
    $phone,
    $request['logo'],
    $request['password']
);

if (!$ins->execute()) {
    die("DB error while creating restaurant: " . $ins->error);
}

$new_restaurant_id = $ins->insert_id;
$ins->close();

$restaurant_code = 'REST-' . str_pad($new_restaurant_id, 4, '0', STR_PAD_LEFT);

$upd = $conn->prepare("UPDATE restaurants SET restaurant_code=? WHERE id=?");
$upd->bind_param("si", $restaurant_code, $new_restaurant_id);
$upd->execute();
$upd->close();

$upd_req = $conn->prepare("UPDATE restaurant_requests SET status='Approved' WHERE id=?");
$upd_req->bind_param("i", $request_id);
$upd_req->execute();
$upd_req->close();
?>

<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Request Approved</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="card p-4">
    <h4>Request Approved</h4>
    <p>Restaurant <strong><?=htmlspecialchars($request['restaurant_name'])?></strong> has been created successfully.</p>
    <p><strong>Restaurant Code:</strong> <code><?=$restaurant_code?></code></p>
    <p><strong>Assigned Phone:</strong> <code><?=$phone?></code></p>
    <p>Share this code with the restaurant admin — they can use their phone & password to log in. Super Admin can also create cashier IDs for this restaurant.</p>

    <a class="btn btn-primary" href="super_admin_panel.php">Back to Super Admin Panel</a>
    <a class="btn btn-success" href="create_cashier.php?restaurant_id=<?=$new_restaurant_id?>">Create Cashier for this Restaurant</a>
</div>
</body>
</html>
