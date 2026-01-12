<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['super_admin_id'])) {
    header("Location: super_admin_login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $restaurant_id = $_POST['restaurant_id'];
    $name = $_POST['customer_name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];

    $stmt = $conn->prepare("INSERT INTO customers (restaurant_id, customer_name, phone, email) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $restaurant_id, $name, $phone, $email);
    if ($stmt->execute()) {
        header("Location: super_admin_panel.php#customers");
        exit();
    } else {
        $error = "Error adding customer!";
    }
}
$restaurants = $conn->query("SELECT id, restaurant_name FROM restaurants WHERE status='Active'");
?>
<!DOCTYPE html>
<html>
<head>
<title>Add Customer</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
    <h3>Add Customer</h3>
    <?php if(isset($error)): ?><div class="alert alert-danger"><?=$error?></div><?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Restaurant</label>
            <select name="restaurant_id" class="form-select" required>
                <option value="">Select Restaurant</option>
                <?php while($r = $restaurants->fetch_assoc()): ?>
                <option value="<?=$r['id']?>"><?=$r['restaurant_name']?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Customer Name</label>
            <input type="text" name="customer_name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary">Save</button>
        <a href="super_admin_panel.php#customers" class="btn btn-secondary">Back</a>
    </form>
</div>
</body>
</html>
