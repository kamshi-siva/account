<?php
session_start();
require_once "config.php";

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($phone === '' || $password === '') {
        $errors[] = "Both phone and password are required.";
    } else {
        $stmt = $conn->prepare("
            SELECT c.id, c.name, c.password, c.restaurant_id, r.restaurant_name
            FROM cashiers c
            JOIN restaurants r ON c.restaurant_id = r.id
            WHERE c.phone = ? AND r.status = 'Active'
            LIMIT 1
        ");
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        $res = $stmt->get_result();
        $cashier = $res->fetch_assoc();
        $stmt->close();

        if ($cashier && password_verify($password, $cashier['password'])) {
            $_SESSION['cashier_id'] = $cashier['id'];
            $_SESSION['cashier_name'] = $cashier['name'];
            $_SESSION['restaurant_id'] = $cashier['restaurant_id'];
            $_SESSION['restaurant_name'] = $cashier['restaurant_name'];

            header("Location: pos.php");
            exit();
        } else {
            $errors[] = "Invalid phone number or password.";
        }
    }
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Cashier Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="card shadow-sm">
        <div class="card-body">
          <h3 class="mb-3 text-center">Cashier Login</h3>

          <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
              <ul>
                <?php foreach($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?>
              </ul>
            </div>
          <?php endif; ?>

          <form method="post">
            <div class="mb-3">
              <label class="form-label">Phone Number</label>
              <input type="text" name="phone" class="form-control" value="<?=htmlspecialchars($_POST['phone'] ?? '')?>" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Password</label>
              <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
          </form>

        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
