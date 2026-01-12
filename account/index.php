<?php
session_start();
require_once "config.php";

$errors = [];

if (isset($_POST['login'])) {
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];

    if ($phone === "" || $password === "") {
        $errors[] = "All fields are required.";
    } else {
        $stmt = $conn->prepare("SELECT id, restaurant_name, phone, password, status FROM restaurants WHERE phone=? AND status='Active' LIMIT 1");
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        $restaurant = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($restaurant && password_verify($password, $restaurant['password'])) {
            $_SESSION['user_id'] = $restaurant['id'];
            $_SESSION['role'] = 'restaurant_admin';
            $_SESSION['restaurant_id'] = $restaurant['id'];
            $_SESSION['restaurant_name'] = $restaurant['restaurant_name'];

            header("Location: restaurant_dashboard.php");
            exit();
        } else {
            $stmt = $conn->prepare("SELECT id, name, phone, password, restaurant_id FROM cashiers WHERE phone=? LIMIT 1");
            $stmt->bind_param("s", $phone);
            $stmt->execute();
            $cashier = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($cashier && password_verify($password, $cashier['password'])) {
                $_SESSION['user_id'] = $cashier['id'];
                $_SESSION['role'] = 'cashier';
                $_SESSION['restaurant_id'] = $cashier['restaurant_id'];
                $_SESSION['cashier_name'] = $cashier['name'];

                header("Location: pos_frontend.php");
                exit();
            } else {
                $errors[] = "Invalid phone or password, or account not approved.";
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>POS System Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #f7f7f7; }
.login-card {
    max-width: 400px;
    margin: 80px auto;
    padding: 30px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 0 15px rgba(0,0,0,0.1);
}
.logo {
    display: block;
    margin: 0 auto 20px;
    width: 120px;
}
</style>
</head>
<body>

<div class="login-card">
    <img src="assets/logo.png" alt="Logo" class="logo">

    <h3 class="text-center mb-4">Restaurant POS Login</h3>

    <?php if ($errors): ?>
    <div class="alert alert-danger">
        <ul>
        <?php foreach($errors as $e) echo "<li>$e</li>"; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label for="phone" class="form-label">Phone Number</label>
            <input type="text" name="phone" id="phone" class="form-control" placeholder="Enter phone number" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" name="password" id="password" class="form-control" placeholder="Enter password" required>
        </div>
        <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
    </form>

    <p class="text-center mt-3">
        <a href="register.php">New Restaurant? Register Here</a>
    </p>
</div>

</body>
</html>
