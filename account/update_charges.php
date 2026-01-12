<?php
session_start();
include "config.php";

/* 🔐 Restaurant admin login check */
if (!isset($_SESSION['restaurant_id'])) {
    die("Access Denied");
}

$restaurant_id = (int)$_SESSION['restaurant_id'];

$tax     = floatval($_POST['tax_rate'] ?? 0);
$service = floatval($_POST['service_charge_rate'] ?? 0);
$table   = floatval($_POST['table_charge'] ?? 0);

$stmt = $conn->prepare("
    UPDATE restaurants
    SET tax_rate = ?, service_charge_rate = ?, table_charge = ?
    WHERE id = ?
");
$stmt->bind_param("dddi", $tax, $service, $table, $restaurant_id);
$stmt->execute();
$stmt->close();

header("Location: charges_settings.php?saved=1");
exit;
