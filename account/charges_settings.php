<?php
session_start();
include "config.php";

$restaurant_id = $_SESSION['restaurant_id'];

$stmt = $conn->prepare("
  SELECT tax_rate, service_charge_rate, table_charge
  FROM restaurants
  WHERE id=?
");
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$r = $stmt->get_result()->fetch_assoc();
?>

<h3>Charges Settings</h3>

<form method="POST" action="update_charges.php">
    <label>Tax (%)</label>
    <input type="number" step="0.01" name="tax_rate"
           value="<?= $r['tax_rate'] ?>">

    <label>Service Charge (%)</label>
    <input type="number" step="0.01" name="service_charge_rate"
           value="<?= $r['service_charge_rate'] ?>">

    <label>Table Charge (Rs)</label>
    <input type="number" step="0.01" name="table_charge"
           value="<?= $r['table_charge'] ?>">

    <button type="submit">Save</button>
</form>
