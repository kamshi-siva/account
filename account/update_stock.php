<?php
session_start();
include "config.php";

if (!isset($_SESSION['cashier_id'])) {
    http_response_code(403);
    echo json_encode(['status'=>'error','msg'=>'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['cart'])) {
    echo json_encode(['status'=>'error','msg'=>'Cart is empty']);
    exit();
}

$restaurant_id = $_SESSION['restaurant_id'];
$cart = $data['cart'];

$conn->begin_transaction();

try {
    foreach ($cart as $item) {
        $product_name = $item['name'];
        $qty_to_deduct = intval($item['qty']);
        
        // Deduct from batches with nearest expiry first
        $stmt = $conn->prepare("SELECT id, quantity FROM product_batches WHERE product_id=(SELECT id FROM products WHERE product_name=? AND restaurant_id=?) AND restaurant_id=? AND quantity>0 ORDER BY expiry_date ASC");
        $stmt->bind_param("sii", $product_name, $restaurant_id, $restaurant_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $remaining = $qty_to_deduct;
        while($row = $result->fetch_assoc() && $remaining > 0){
            $batch_id = $row['id'];
            $batch_qty = $row['quantity'];
            if($batch_qty >= $remaining){
                $new_qty = $batch_qty - $remaining;
                $conn->query("UPDATE product_batches SET quantity=$new_qty WHERE id=$batch_id");
                $remaining = 0;
            } else {
                $conn->query("UPDATE product_batches SET quantity=0 WHERE id=$batch_id");
                $remaining -= $batch_qty;
            }
        }
    }
    $conn->commit();
    echo json_encode(['status'=>'success']);
} catch(Exception $e){
    $conn->rollback();
    echo json_encode(['status'=>'error','msg'=>$e->getMessage()]);
}
?>
