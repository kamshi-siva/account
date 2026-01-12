<?php
session_start();
require_once 'config.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['restaurant_name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '') $errors[] = "Restaurant name is required.";
    if ($address === '') $errors[] = "Address is required.";
    if ($phone === '') $errors[] = "Phone number is required.";
    if ($password === '' || strlen($password) < 6) $errors[] = "Password required (min 6 chars).";

    $logoPath = null;
    if (!empty($_FILES['logo']['name'])) {
        $allowed = ['png','jpg','jpeg','gif','webp'];
        $fileName = basename($_FILES['logo']['name']);
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $errors[] = "Logo must be an image (png,jpg,jpeg,gif,webp).";
        } else {
            $targetDir = __DIR__ . '/uploads/logos/';
            if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
            $newFileName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            $targetFile = $targetDir . $newFileName;
            if (!move_uploaded_file($_FILES['logo']['tmp_name'], $targetFile)) {
                $errors[] = "Failed to upload logo.";
            } else {
                $logoPath = 'uploads/logos/' . $newFileName;
            }
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM restaurant_requests WHERE phone = ? UNION SELECT id FROM restaurants WHERE phone = ?");
        $stmt->bind_param('ss', $phone, $phone);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) $errors[] = "Phone number already registered.";
        $stmt->close();
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO restaurant_requests (logo, restaurant_name, address, phone, password, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
        $stmt->bind_param('sssss', $logoPath, $name, $address, $phone, $hash);
        if ($stmt->execute()) {
            $success = "✅ Registration submitted! Waiting for Super Admin approval.";
        } else {
            $errors[] = "Database error: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Restaurant Registration</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body {
  font-family: 'Inter', sans-serif;
  background: linear-gradient(135deg, #f9fafb, #e9ecef);
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
}
.card {
  border: none;
  border-radius: 15px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}
.btn-primary {
  background: #007bff;
  border: none;
  font-weight: 600;
  transition: all 0.3s ease;
}
.btn-primary:hover {
  background: #0056b3;
  transform: scale(1.03);
}
.logo-preview {
  width: 140px;
  height: 140px;
  border-radius: 50%;
  border: 3px dashed #ccc;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f8f9fa;
  overflow: hidden;
  margin: 0 auto 10px;
  cursor: pointer;
  transition: 0.3s;
}
.logo-preview:hover {
  border-color: #007bff;
}
.logo-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
</style>
</head>
<body>
<div class="container">
  <div class="col-md-6 mx-auto">
    <div class="card p-4">
      <h3 class="text-center mb-3"><i class="bi bi-shop text-primary"></i> Register Your Restaurant</h3>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
          <ul class="mb-0">
            <?php foreach($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert alert-success text-center fw-semibold"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data">
        <div class="text-center mb-3">
          <div id="logoPreview" class="logo-preview">
            <span class="text-muted small">Click to Upload logo</span>
          </div>
          <input type="file" id="logo" name="logo" class="d-none" accept="image/*">
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Restaurant Name</label>
          <input type="text" class="form-control" name="restaurant_name" required value="<?=isset($name)?htmlspecialchars($name):''?>">
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Address</label>
          <textarea class="form-control" name="address" rows="3" required><?=isset($address)?htmlspecialchars($address):''?></textarea>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Phone Number</label>
          <input type="text" class="form-control" name="phone" required value="<?=isset($phone)?htmlspecialchars($phone):''?>">
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Password</label>
          <input type="password" class="form-control" name="password" required placeholder="At least 6 characters">
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2">Submit Registration</button>
      </form>

      <div class="text-center mt-4">
        <small class="text-muted">Your request will be reviewed by the Super Admin. You’ll be notified upon approval.</small>
      </div>
    </div>
  </div>
</div>

<script>
const logoInput = document.getElementById('logo');
const logoPreview = document.getElementById('logoPreview');
logoPreview.addEventListener('click', () => logoInput.click());
logoInput.addEventListener('change', () => {
  const file = logoInput.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = e => logoPreview.innerHTML = `<img src="${e.target.result}" alt="Logo">`;
    reader.readAsDataURL(file);
  } else {
    logoPreview.innerHTML = `<span class="text-muted small">Click to Upload</span>`;
  }
});
</script>
</body>
</html>
