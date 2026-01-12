<?php
session_start();
require_once "config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['phone'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, restaurant_name, password FROM restaurants WHERE phone=? AND status='Active' LIMIT 1");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $restaurant = $result->fetch_assoc();

        if (password_verify($password, $restaurant['password'])) {
            $_SESSION['restaurant_admin_id'] = $restaurant['id'];
            $_SESSION['restaurant_id'] = $restaurant['id'];
            $_SESSION['restaurant_name'] = $restaurant['restaurant_name'];

            header("Location: restaurant_dashboard.php");
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "No restaurant account found with that phone number.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Restaurant Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex justify-content-center align-items-center vh-100">
<div class="card p-4 shadow" style="width:350px;">
    <h4 class="text-center mb-3">Restaurant Login</h4>
    <?php if(isset($error)): ?>
        <div class="alert alert-danger"><?=$error?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label>Phone</label>
            <input type="text" name="phone" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Login</button>
    </form>
</div>
</body>
</html>
