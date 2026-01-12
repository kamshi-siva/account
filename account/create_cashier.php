<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['restaurant_admin_id'])) {
    header("Location: index.php");
    exit();
}

// Safe check for restaurant_id
$restaurant_id = $_SESSION['restaurant_id'] ?? 0;
if (!$restaurant_id) {
    header("Location: index.php");
    exit();
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validate input
    if (!$name || !$phone || !$password) {
        $errors[] = "All fields are required";
    } else {
        // Check if phone already exists for this restaurant
        $stmt = $conn->prepare("SELECT id FROM cashiers WHERE phone=? AND restaurant_id=?");
        $stmt->bind_param("si", $phone, $restaurant_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Phone number already registered for this restaurant";
        }
        $stmt->close();
    }

    // If no errors, insert new cashier
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO cashiers (restaurant_id, name, phone, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $restaurant_id, $name, $phone, $hashedPassword);
        if ($stmt->execute()) {
            $success = "Cashier added successfully!";
        } else {
            $errors[] = "Database error: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Cashier</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h3>Add New Cashier</h3>

    <?php if($errors): ?>
        <div class="alert alert-danger"><?= implode('<br>', $errors) ?></div>
    <?php endif; ?>

    <?php if($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Add Cashier</button>
        <a href="cashiers.php" class="btn btn-secondary">Back</a>
    </form>
</div>
</body>
</html>
