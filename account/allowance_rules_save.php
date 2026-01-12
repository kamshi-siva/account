<?php
session_start();
require_once "config.php";

$restaurant_id = $_SESSION['restaurant_id'] ?? 0;
$cashier_id = intval($_POST['cashier_id'] ?? $_GET['id'] ?? 0);

if ($restaurant_id == 0 || $cashier_id == 0) {
    die("Invalid or missing cashier ID.");
}

// Get values safely
$default_allowance = floatval($_POST['default_allowance'] ?? 1000);
$year_end_bonus    = floatval($_POST['year_end_bonus'] ?? 5000);
$sales_threshold   = floatval($_POST['sales_threshold'] ?? 50000);
$extra_allowance   = floatval($_POST['extra_allowance'] ?? 2000);

// Check if an allowance rule already exists for this restaurant
$stmt = $conn->prepare("SELECT id FROM allowance_rules WHERE restaurant_id = ?");
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$result = $stmt->get_result();
$exists = $result->fetch_assoc();
$stmt->close();

if ($exists) {
    // Update existing record
    $stmt = $conn->prepare("
        UPDATE allowance_rules 
        SET default_allowance = ?, year_end_bonus = ?, sales_threshold = ?, extra_allowance = ?, updated_at = NOW() 
        WHERE restaurant_id = ?
    ");
    $stmt->bind_param("ddddi", $default_allowance, $year_end_bonus, $sales_threshold, $extra_allowance, $restaurant_id);
} else {
    // Insert new record
    $stmt = $conn->prepare("
        INSERT INTO allowance_rules (restaurant_id, default_allowance, year_end_bonus, sales_threshold, extra_allowance) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("idddd", $restaurant_id, $default_allowance, $year_end_bonus, $sales_threshold, $extra_allowance);
}


$stmt->execute();
$stmt->close();

// Redirect back with success
header("Location: cashier_salary.php?id=$cashier_id&success=1");
exit();
?>
