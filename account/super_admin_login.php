<?php
session_start();
require_once 'config.php';

$error = '';
$success = '';

if (isset($_GET['registered']) && $_GET['registered'] == 1) {
    $success = "Super Admin account created successfully! Please login.";
}

if (isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email == "" || $password == "") {
        $error = "Both fields are required!";
    } else {
        $stmt = $conn->prepare("SELECT * FROM super_admins WHERE email=? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        $admin = $res->fetch_assoc();
        $stmt->close();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['super_admin_id'] = $admin['id'];
            $_SESSION['super_admin_name'] = $admin['name'];

            header("Location: super_admin_panel.php");
            exit();
        } else {
            $error = "Invalid email or password!";
        }
    }
}
?>

<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Super Admin Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5 bg-white p-4 shadow rounded">
            <h3 class="text-center mb-4">Super Admin Login</h3>

            <?php if($success): ?>
                <div class="alert alert-success"><?=htmlspecialchars($success)?></div>
            <?php endif; ?>

            <?php if($error): ?>
                <div class="alert alert-danger"><?=htmlspecialchars($error)?></div>
            <?php endif; ?>

            <form method="post">
                <div class="mb-3">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
            </form>

            <p class="mt-3 text-center">
                Don't have an account? <a href="super_admin_register.php">Register here</a>
            </p>
        </div>
    </div>
</div>
</body>
</html>
