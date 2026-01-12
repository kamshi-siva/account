<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['restaurant_admin_id'])) {
    header("Location: index.php");
    exit();
}

$restaurant_id = $_SESSION['restaurant_id'];
$id = intval($_GET['id']);
$errors = [];
$success = '';

// Fetch cashier details including new fields
$stmt = $conn->prepare("SELECT name, phone, ic_no, address, id_image FROM cashiers WHERE id=? AND restaurant_id=?");
$stmt->bind_param("ii", $id, $restaurant_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if (!$row) {
    header("Location: cashiers.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $ic_no = trim($_POST['ic_no']);
    $address = trim($_POST['address']);
    $password = trim($_POST['password']);
    $id_image = $_FILES['id_image'] ?? null;

    if (!$name || !$phone) {
        $errors[] = "Name and phone are required";
    }

    // Check if phone is unique
    $stmt_check = $conn->prepare("SELECT id FROM cashiers WHERE phone=? AND restaurant_id=? AND id<>?");
    $stmt_check->bind_param("sii", $phone, $restaurant_id, $id);
    $stmt_check->execute();
    $stmt_check->store_result();
    if ($stmt_check->num_rows > 0) {
        $errors[] = "Phone number already exists";
    }

    // Handle file upload
    $uploaded_file = $row['id_image'];
    if ($id_image && $id_image['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($id_image['name'], PATHINFO_EXTENSION);
        $filename = 'cashier_'.$id.'_'.time().'.'.$ext;
        $target_dir = 'uploads/cashiers/';
        if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
        if (move_uploaded_file($id_image['tmp_name'], $target_dir.$filename)) {
            $uploaded_file = $filename;
        } else {
            $errors[] = "Failed to upload ID image";
        }
    }

    if (empty($errors)) {
        if ($password) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt_update = $conn->prepare("UPDATE cashiers SET name=?, phone=?, password=?, ic_no=?, address=?, id_image=? WHERE id=? AND restaurant_id=?");
            $stmt_update->bind_param("ssssssii", $name, $phone, $hashedPassword, $ic_no, $address, $uploaded_file, $id, $restaurant_id);
        } else {
            $stmt_update = $conn->prepare("UPDATE cashiers SET name=?, phone=?, ic_no=?, address=?, id_image=? WHERE id=? AND restaurant_id=?");
            $stmt_update->bind_param("sssssii", $name, $phone, $ic_no, $address, $uploaded_file, $id, $restaurant_id);
        }
        if ($stmt_update->execute()) {
            $success = "Cashier updated successfully!";
            $row['name'] = $name;
            $row['phone'] = $phone;
            $row['ic_no'] = $ic_no;
            $row['address'] = $address;
            $row['id_image'] = $uploaded_file;
        } else {
            $errors[] = "Database error: ".$stmt_update->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Cashier</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.img-thumb { width: 100px; height: 100px; object-fit: cover; border-radius: 5px; margin-top: 5px; }
</style>
</head>
<body>
<div class="container mt-5">
    <h3>Edit Cashier</h3>
    <?php if($errors): ?>
        <div class="alert alert-danger"><?= implode('<br>', $errors) ?></div>
    <?php endif; ?>
    <?php if($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($row['name']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($row['phone']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">IC / Passport No</label>
            <input type="text" name="ic_no" class="form-control" value="<?= htmlspecialchars($row['ic_no']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Address</label>
            <textarea name="address" class="form-control"><?= htmlspecialchars($row['address']) ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">ID / Passport / License Image</label>
            <input type="file" name="id_image" class="form-control">
            <?php if($row['id_image']): ?>
                <img src="uploads/cashiers/<?= htmlspecialchars($row['id_image']) ?>" class="img-thumb" alt="ID Image">
            <?php endif; ?>
        </div>
        <div class="mb-3">
            <label class="form-label">Password (leave blank to keep unchanged)</label>
            <input type="password" name="password" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary">Update Cashier</button>
        <a href="cashiers.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
</body>
</html>
