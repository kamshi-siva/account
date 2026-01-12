<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['restaurant_admin_id']) && !isset($_SESSION['cashier_id'])) {
    echo json_encode(['success'=>false,'message'=>'Unauthorized']);
    exit();
}

$restaurant_id = $_SESSION['restaurant_id'] ?? 0;
$product_id = (int)($_POST['product_id'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 0);

if($restaurant_id==0 || $product_id==0 || $quantity<=0) {
    echo json_encode(['success'=>false,'message'=>'Invalid input']);
    exit();
}

// Update product quantity
$stmt = $conn->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ? AND restaurant_id = ?");
$stmt->bind_param("iii", $quantity, $product_id, $restaurant_id);
$stmt->execute();
$stmt->close();

// Get new quantity
$stmt = $conn->prepare("SELECT quantity FROM products WHERE id = ? AND restaurant_id = ?");
$stmt->bind_param("ii", $product_id, $restaurant_id);
$stmt->execute();
$stmt->bind_result($new_quantity);
$stmt->fetch();
$stmt->close();

// Get updated low stock count
$stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE restaurant_id = ? AND quantity <= 10");
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$stmt->bind_result($low_stock_count);
$stmt->fetch();
$stmt->close();

echo json_encode(['success'=>true,'new_quantity'=>$new_quantity,'low_stock_count'=>$low_stock_count]);
