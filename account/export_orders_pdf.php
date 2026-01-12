<?php
session_start();
require_once "config.php";
require("fpdf/fpdf.php");

if (!isset($_SESSION['restaurant_admin_id'])) {
    die("Unauthorized");
}

$restaurant_id = $_SESSION['restaurant_admin_id'];

$filter_cashier = $_GET['cashier'] ?? '';
$filter_from = $_GET['from'] ?? '';
$filter_to = $_GET['to'] ?? '';

$where = "WHERE o.restaurant_id = ?";
$params = [$restaurant_id];
$types = "i";

if ($filter_cashier !== '') {
    $where .= " AND o.cashier_id = ?";
    $params[] = $filter_cashier;
    $types .= "i";
}
if ($filter_from !== '' && $filter_to !== '') {
    $where .= " AND DATE(o.created_at) BETWEEN ? AND ?";
    $params[] = $filter_from;
    $params[] = $filter_to;
    $types .= "ss";
}

$sql = "SELECT o.*, c.name AS cashier_name 
        FROM orders o
        JOIN cashiers c ON o.cashier_id = c.id
        $where
        ORDER BY o.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result();
$stmt->close();


$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont("Arial","B",14);
$pdf->Cell(190,10,"Orders Report",0,1,"C");

$pdf->SetFont("Arial","",12);
$pdf->Cell(190,10,"Date: ".date("Y-m-d H:i:s"),0,1,"C");

$pdf->Ln(5);

$pdf->SetFont("Arial","B",10);
$pdf->Cell(40,10,"Order #",1);
$pdf->Cell(40,10,"Cashier",1);
$pdf->Cell(40,10,"Total (Rs)",1);
$pdf->Cell(35,10,"Payment",1);
$pdf->Cell(35,10,"Date",1);
$pdf->Ln();

$pdf->SetFont("Arial","",10);

while($o = $orders->fetch_assoc()) {
    $pdf->Cell(40,10,$o['order_number'],1);
    $pdf->Cell(40,10,$o['cashier_name'],1);
    $pdf->Cell(40,10,number_format($o['total'],2),1);
    $pdf->Cell(35,10,$o['payment_method'],1);
    $pdf->Cell(35,10,substr($o['created_at'],0,10),1);
    $pdf->Ln();
}

$pdf->Output("D","orders_report.pdf");
