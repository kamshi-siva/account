<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['restaurant_id'])) {
    header("Location: login.php");
    exit();
}

$restaurant_id = $_SESSION['restaurant_id'];

// Fetch restaurant info
$stmt = $conn->prepare("SELECT restaurant_name, address FROM restaurants WHERE id=?");
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$restaurant = $stmt->get_result()->fetch_assoc();
$restaurantName = $restaurant['restaurant_name'] ?? "My Restaurant";
$restaurantAddress = $restaurant['address'] ?? "";

// Messages
$success = $error = "";

// --- Update Expiry Alert Days ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_alert'])) {
    $days = intval($_POST['expiry_alert_days']);
    $stmt = $conn->prepare("UPDATE settings SET expiry_alert_days=? WHERE restaurant_id=?");
    $stmt->bind_param("ii", $days, $restaurant_id);
    if ($stmt->execute()) $success = "Expiry alert days updated successfully.";
    else $error = "Failed to update expiry alert days.";
}

// --- Add Product ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = trim($_POST['product_name']);
    $category_id = intval($_POST['category_id']);
    $price = floatval($_POST['price']);
    $cost_price = floatval($_POST['cost_price']);
    $quantity = floatval($_POST['quantity']);
    $unit = $_POST['unit'] ?? '';
    $barcode = $_POST['barcode'] ?? '';
    $expiry_date = $_POST['expiry_date'] ?? null;

    // Check if product exists
    $stmt_check = $conn->prepare("SELECT id FROM products WHERE restaurant_id=? AND product_name=? LIMIT 1");
    $stmt_check->bind_param("is", $restaurant_id, $name);
    $stmt_check->execute();
    $existing = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    if ($existing) {
        $product_id = $existing['id'];
        if ($quantity > 0) {
            $batch_stmt = $conn->prepare("INSERT INTO product_batches (product_id, quantity, expiry_date) VALUES (?, ?, ?)");
            $batch_stmt->bind_param("ids", $product_id, $quantity, $expiry_date);
            $batch_stmt->execute();
            $batch_stmt->close();
        }
        $conn->query("UPDATE products SET quantity=(SELECT COALESCE(SUM(quantity),0) FROM product_batches WHERE product_id=$product_id) WHERE id=$product_id");
        $success = "Batch added to existing product successfully.";
    } else {
        $stmt = $conn->prepare("INSERT INTO products (restaurant_id, product_name, category_id, price, cost_price, quantity, unit, barcode) VALUES (?, ?, ?, ?, ?, 0, ?, ?)");
        $stmt->bind_param("isiddss", $restaurant_id, $name, $category_id, $price, $cost_price, $unit, $barcode);
        if ($stmt->execute()) {
            $product_id = $stmt->insert_id;
            if ($quantity > 0) {
                $batch_stmt = $conn->prepare("INSERT INTO product_batches (product_id, quantity, expiry_date) VALUES (?, ?, ?)");
                $batch_stmt->bind_param("ids", $product_id, $quantity, $expiry_date);
                $batch_stmt->execute();
                $batch_stmt->close();
                $conn->query("UPDATE products SET quantity=(SELECT COALESCE(SUM(quantity),0) FROM product_batches WHERE product_id=$product_id) WHERE id=$product_id");
            }
            $success = "Product added successfully and now visible in POS.";
        } else $error = "Failed to add product.";
        $stmt->close();
    }
}

// --- Edit Product ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
    $id = intval($_POST['edit_id']);
    $name = trim($_POST['edit_name']);
    $category_id = intval($_POST['edit_category']);
    $price = floatval($_POST['edit_price']);
    $cost_price = floatval($_POST['edit_cost_price']);
    $unit = $_POST['edit_unit'] ?? '';
    $barcode = $_POST['edit_barcode'] ?? '';
    $expiry_date = $_POST['edit_expiry_date'] ?? null;

    $stmt = $conn->prepare("UPDATE products SET product_name=?, category_id=?, price=?, cost_price=?, unit=?, barcode=? WHERE id=? AND restaurant_id=?");
    $stmt->bind_param("siddssii", $name, $category_id, $price, $cost_price, $unit, $barcode, $id, $restaurant_id);
    if ($stmt->execute()) $success = "Product updated successfully.";
    else $error = "Failed to update product.";
    $stmt->close();

    if ($expiry_date) {
        $conn->query("UPDATE product_batches SET expiry_date='$expiry_date' WHERE product_id=$id ORDER BY expiry_date ASC LIMIT 1");
    }
    $conn->query("UPDATE products SET quantity=(SELECT COALESCE(SUM(quantity),0) FROM product_batches WHERE product_id=$id) WHERE id=$id");
}

// --- Delete Product ---
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM product_batches WHERE product_id=$delete_id");
    $conn->query("DELETE FROM products WHERE id=$delete_id AND restaurant_id=$restaurant_id");
    $success = "Product deleted successfully.";
}

// --- Fetch Expiry Alert Days ---
$stmt = $conn->prepare("SELECT expiry_alert_days FROM settings WHERE restaurant_id=? LIMIT 1");
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$settings = $stmt->get_result()->fetch_assoc();
$expiry_alert_days = $settings['expiry_alert_days'] ?? 7;
$stmt->close();

// --- Fetch Categories ---
$categories = $conn->query("SELECT id, name FROM categories WHERE restaurant_id=$restaurant_id ORDER BY name ASC");

// --- Fetch Products ---
$query = "
SELECT p.*, c.name AS category_name,
       (SELECT MIN(expiry_date) FROM product_batches WHERE product_id=p.id) AS nearest_expiry
FROM products p
LEFT JOIN categories c ON p.category_id=c.id
WHERE p.restaurant_id=?
ORDER BY p.id DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Products - <?= htmlspecialchars($restaurantName) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { overflow-x:hidden; background:#f5f7fa; font-family:'Inter',sans-serif; }
.sidebar { width:180px; position:fixed; top:56px; left:0; height:100%; background:#fff; border-right:1px solid #e3e6f0; padding:1rem; overflow-y:auto; transition:0.3s; }
.sidebar.collapsed { margin-left:-180px; }
.sidebar ul.nav li a { color:#495057; text-decoration:none; display:block; padding:12px 20px; border-left:4px solid transparent; border-radius:0 8px 8px 0; margin:4px 0; font-weight:500; transition:0.2s; }
.sidebar ul.nav li a:hover, .sidebar ul.nav li a.active { background:linear-gradient(90deg,#dbe4ff,#ffffff); border-left:4px solid #0d6efd; color:#0d6efd; }
.main-content { margin-left:180px; padding:20px; padding-top:70px; transition:0.3s; }
.main-content.expanded { margin-left:0; }
.navbar { position:fixed; top:0; width:100%; z-index:1000; }
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <button class="btn btn-primary me-2" id="sidebarToggle"><i class="bi bi-list"></i></button>
    <a class="navbar-brand"><?= htmlspecialchars($restaurantName) ?></a>
    <div class="ms-auto d-flex align-items-center">
      <span class="navbar-text me-3"><?= htmlspecialchars($restaurantAddress) ?></span>
      <a href="logout_restaurant.php" class="btn btn-outline-light btn-sm">Logout</a>
    </div>
  </div>
</nav>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <ul class="nav flex-column">
        <li class="nav-item"><a href="restaurant_dashboard.php"><i class="bi bi-house-door me-2"></i>Dashboard</a></li>
        <li class="nav-item"><a href="orders.php"><i class="bi bi-receipt me-2"></i>Orders</a></li>
        <li class="nav-item"><a href="products.php" class="active"><i class="bi bi-box-seam me-2"></i>Products</a></li>
        <li class="nav-item"><a href="categories.php"><i class="bi bi-tags me-2"></i>Categories</a></li>
        <li class="nav-item"><a href="cashiers.php"><i class="bi bi-people me-2"></i>Cashiers</a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content" id="mainContent">
    <h3>Products</h3>
    <?php if($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

    <!-- Expiry Alert Days -->
    <form method="POST" class="mb-3 w-25">
        <label>Expiry Alert Days</label>
        <input type="number" name="expiry_alert_days" value="<?= $expiry_alert_days ?>" class="form-control" min="1">
        <button type="submit" name="update_alert" class="btn btn-primary mt-2">Save</button>
    </form>

    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addProductModal">
        <i class="bi bi-plus-circle"></i> Add Product
    </button>

    <!-- Products Table -->
    <table class="table table-bordered table-striped align-middle">
        <thead class="table-primary text-center">
            <tr>
                <th>#</th><th>Product</th><th>Category</th><th>Price</th><th>Cost Price</th>
                <th>Quantity</th><th>Status</th><th>Unit</th><th>Barcode</th><th>Action</th>
            </tr>
        </thead>
        <tbody class="text-center">
        <?php $i=1; while($row=$result->fetch_assoc()): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($row['product_name']) ?></td>
                <td><?= htmlspecialchars($row['category_name'] ?? '-') ?></td>
                <td><?= number_format($row['price'],2) ?></td>
                <td><?= number_format($row['cost_price'],2) ?></td>
                <td><?= $row['quantity'] ?></td>
                <td>
                    <?php
                    $expiry_date = $row['nearest_expiry'];
                    if ($expiry_date) {
                        $days_diff = (strtotime($expiry_date) - strtotime(date('Y-m-d'))) / (60*60*24);
                        if ($days_diff < 0) echo '<span class="badge bg-dark">Expired</span>';
                        elseif ($days_diff <= $expiry_alert_days) echo '<span class="badge bg-warning">Expiring Soon</span>';
                        else echo '<span class="badge bg-success">OK</span>';
                    } else echo '<span class="badge bg-success">OK</span>';
                    ?>
                </td>
                <td><?= htmlspecialchars($row['unit'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['barcode'] ?? '-') ?></td>
                <td>
                    <a href="batches.php?product_id=<?= $row['id'] ?>" class="btn btn-primary btn-sm"><i class="bi bi-box-seam"></i> Batch</a>
                    <button class="btn btn-warning btn-sm editBtn"
                        data-id="<?= $row['id'] ?>"
                        data-name="<?= htmlspecialchars($row['product_name']) ?>"
                        data-price="<?= $row['price'] ?>"
                        data-cost="<?= $row['cost_price'] ?>"
                        data-category="<?= $row['category_id'] ?>"
                        data-unit="<?= htmlspecialchars($row['unit']) ?>"
                        data-barcode="<?= htmlspecialchars($row['barcode']) ?>"
                        data-expiry="<?= $row['nearest_expiry'] ?>"
                    ><i class="bi bi-pencil-square"></i></button>
                    <a href="?delete_id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this product?')"><i class="bi bi-trash"></i></a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
  <div class="modal-dialog">
  <form method="POST" class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title">Add Product</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
        <label>Product Name</label>
        <input type="text" name="product_name" class="form-control" required>
        <label>Category</label>
        <select name="category_id" class="form-control">
            <option value="">Select</option>
            <?php $categories->data_seek(0); while($cat=$categories->fetch_assoc()): ?>
                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
            <?php endwhile; ?>
        </select>
        <label>Price</label>
        <input type="number" step="0.01" name="price" class="form-control" required>
        <label>Cost Price</label>
        <input type="number" step="0.01" name="cost_price" class="form-control" required>
        <label>Quantity</label>
        <input type="number" step="0.01" name="quantity" class="form-control">
        <label>Expiry Date</label>
        <input type="date" name="expiry_date" class="form-control">
        <label>Unit</label>
        <input type="text" name="unit" class="form-control">
        <label>Barcode</label>
        <input type="text" name="barcode" class="form-control">
    </div>
    <div class="modal-footer">
        <button type="submit" name="add_product" class="btn btn-primary">Add</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
    </div>
  </form>
  </div>
</div>

<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1">
  <div class="modal-dialog">
  <form method="POST" class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title">Edit Product</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
        <input type="hidden" name="edit_id" id="edit_id">
        <label>Product Name</label>
        <input type="text" name="edit_name" id="edit_name" class="form-control" required>
        <label>Category</label>
        <select name="edit_category" id="edit_category" class="form-control"></select>
        <label>Price</label>
        <input type="number" step="0.01" name="edit_price" id="edit_price" class="form-control" required>
        <label>Cost Price</label>
        <input type="number" step="0.01" name="edit_cost_price" id="edit_cost_price" class="form-control" required>
        <label>Unit</label>
        <input type="text" name="edit_unit" id="edit_unit" class="form-control">
        <label>Barcode</label>
        <input type="text" name="edit_barcode" id="edit_barcode" class="form-control">
        <label>Expiry Date</label>
        <input type="date" name="edit_expiry_date" id="edit_expiry_date" class="form-control">
    </div>
    <div class="modal-footer">
        <button type="submit" name="edit_product" class="btn btn-warning">Update</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
    </div>
  </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Sidebar toggle
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');
document.getElementById('sidebarToggle').addEventListener('click',()=>{
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('expanded');
});

// Edit Product Modal
document.querySelectorAll('.editBtn').forEach(btn=>{
    btn.addEventListener('click',()=>{
        document.getElementById('edit_id').value = btn.dataset.id;
        document.getElementById('edit_name').value = btn.dataset.name;
        document.getElementById('edit_price').value = btn.dataset.price;
        document.getElementById('edit_cost_price').value = btn.dataset.cost;
        document.getElementById('edit_unit').value = btn.dataset.unit;
        document.getElementById('edit_barcode').value = btn.dataset.barcode;
        document.getElementById('edit_expiry_date').value = btn.dataset.expiry;

        // Populate category dropdown
        const categorySelect = document.getElementById('edit_category');
        categorySelect.innerHTML = '';
        <?php
        $categories->data_seek(0);
        while($cat = $categories->fetch_assoc()){
            echo "categorySelect.innerHTML += `<option value='{$cat['id']}'>".addslashes($cat['name'])."</option>`;\n";
        }
        ?>
        categorySelect.value = btn.dataset.category;

        // Show modal
        const editModal = new bootstrap.Modal(document.getElementById('editProductModal'));
        editModal.show();
    });
});
</script>
</body>
</html>
