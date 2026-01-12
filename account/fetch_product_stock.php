<?php
session_start();
include "config.php";

$restaurant_id = $_SESSION['restaurant_id'] ?? 0;
if($restaurant_id==0){ echo json_encode([]); exit(); }

$res = $conn->query("
    SELECT p.id, p.product_name, IFNULL(SUM(b.quantity),0) AS total_qty
    FROM products p
    LEFT JOIN product_batches b ON p.id=b.product_id AND b.restaurant_id=$restaurant_id
    WHERE p.restaurant_id=$restaurant_id
    GROUP BY p.id
");

$products=[];
while($row = $res->fetch_assoc()){
    $products[] = $row;
}
echo json_encode($products);
