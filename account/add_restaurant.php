<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['super_admin_id'])) {
    header("Location: index.php");
    exit();
}

$success = $error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $logoFile = $_FILES['logo']['name'];
    $logoTmp = $_FILES['logo']['tmp_name'];
    $uploadDir = "uploads/logos/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileExt = pathinfo($logoFile, PATHINFO_EXTENSION);
    $newFileName = uniqid("logo_") . "." . $fileExt;
    $targetFile = $uploadDir . $newFileName;

    if (move_uploaded_file($logoTmp, $targetFile)) {
        $stmt = $conn->prepare("INSERT INTO restaurants (restaurant_name, phone, address, logo, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $phone, $address, $newFileName, $password);

        if ($stmt->execute()) {
            $success = "Restaurant added successfully!";
        } else {
            $error = "Database error: " . $conn->error;
        }
    } else {
        $error = "Failed to upload logo.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Restaurant | Super Admin Panel</title>

<!-- Bootstrap & Font Awesome -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
body {
    font-family: 'Inter', sans-serif;
    background-color: #f5f7fa;
    overflow-x: hidden;
    margin: 0;
}

/* Sidebar */
.sidebar {
    width: 260px;
    background: #fff;
    color: #495057;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    padding-top: 30px;
    border-right: 1px solid #e3e6f0;
    transition: all 0.3s ease;
    z-index: 1000;
}
.sidebar a {
    color: #495057;
    text-decoration: none;
    display: block;
    padding: 15px 25px;
    border-left: 4px solid transparent;
    border-radius: 0 8px 8px 0;
    margin: 4px 0;
    font-weight: 500;
    transition: all 0.2s;
}
.sidebar a:hover, .sidebar a.active {
    background: linear-gradient(90deg,#dbe4ff,#ffffff);
    border-left: 4px solid #0d6efd;
    color: #0d6efd;
}
.sidebar h4 {
    text-align: center;
    margin-bottom: 30px;
    font-weight: 700;
    font-size: 1.3rem;
    color: #0d6efd;
}

/* Submenu */
.submenu {
    display: none;
    padding-left: 25px;
    border-left: 2px solid #e9ecef;
    margin-left: 10px;
}
.submenu a {
    font-size: 0.95rem;
    padding: 10px 25px;
}
.sidebar a.dropdown-toggle.active + .submenu {
    display: block;
}

/* Main content */
.main-content {
    margin-left: 260px;
    padding: 30px;
    transition: margin-left 0.3s ease;
}
.main-content.expanded {
    margin-left: 0;
}

/* Form box */
.container-box {
    background: #fff;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    margin-top: 40px;
}

/* Page title */
.page-title {
    font-weight: bold;
    font-size: 1.5rem;
    color: #0d6efd;
}

/* Form */
.form-label {
    font-weight: 500;
}
.btn-primary {
    border-radius: 8px;
    padding: 10px 18px;
}
.top-btn {
    float: right;
}

/* Toggle button */
.toggle-btn {
    position: fixed;
    top: 20px;
    left: 260px;
    font-size: 22px;
    cursor: pointer;
    color: #495057;
    z-index: 1100;
    transition: left 0.3s ease;
}
.sidebar.collapsed ~ .toggle-btn {
    left: 20px; 
}
.sidebar.collapsed {
    width: 0;
    padding: 0;
    overflow: hidden;
}
</style>
</head>
<body>

<?php include "super_admin_sidebar.php"; ?>

<div class="main-content" id="mainContent">
    <div class="container-box">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="page-title"><i class="fa-solid fa-utensils me-2"></i>Add Restaurant</h2>
            <a href="view_restaurants.php" class="btn btn-outline-primary top-btn">
                <i class="fa-solid fa-list me-1"></i> View Restaurants
            </a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($success) ?></div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Restaurant Name</label>
                <input type="text" name="name" class="form-control" placeholder="Enter restaurant name" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone" class="form-control" placeholder="Enter phone number" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control" rows="2" placeholder="Enter address" required></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Logo</label>
                <input type="file" name="logo" class="form-control" accept="image/*" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Set login password" required>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-plus me-2"></i> Add Restaurant
            </button>
        </form>
    </div>
</div>

<!-- Scripts -->
<script>
const sidebar = document.getElementById("sidebar");
const toggleBtn = document.createElement('i');
toggleBtn.className = "fa-solid fa-bars toggle-btn";
toggleBtn.id = "toggleSidebar";
document.body.appendChild(toggleBtn);

const mainContent = document.getElementById("mainContent");

toggleBtn.addEventListener("click", function() {
    sidebar.classList.toggle("collapsed");
    mainContent.classList.toggle("expanded");
});

// Dropdown toggle
const restaurantDropdown = document.getElementById("restaurantDropdown");
const restaurantMenu = document.getElementById("restaurantMenu");

restaurantDropdown.addEventListener("click", function() {
    restaurantMenu.style.display = restaurantMenu.style.display === "block" ? "none" : "block";
    this.classList.toggle("active");
});
</script>

</body>
</html>
