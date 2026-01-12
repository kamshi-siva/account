<?php
session_start();
require_once "config.php";
require("fpdf/fpdf.php");

if (!isset($_SESSION['restaurant_admin_id'])) {
    die("Unauthorized");
}

$restaurant_id = $_SESSION['restaurant_admin_id'];
$type = $_GET['type'] ?? 'daily';

$restaurant = $conn->query("SELECT name, address, phone FROM restaurants WHERE id=$restaurant_id")->fetch_assoc();

if ($type === "monthly") {
    $title = "Monthly Sales Report (" . date("F Y") . ")";
    $dateCondition = "MONTH(created_at)=" . date("m") . " AND YEAR(created_at)=" . date("Y");
} else {
    $title = "Daily Sales Report (" . date("Y-m-d") . ")";
    $dateCondition = "DATE(created_at)='" . date("Y-m-d") . "'";
}

$sql = "SELECT o.*, c.name AS cashier_name 
        FROM orders o
        JOIN cashiers c ON o.cashier_id = c.id
        WHERE o.restaurant_id=? AND $dateCondition
        ORDER BY o.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$orders = $stmt->get_result();
$stmt->close();

$totalOrders = $orders->num_rows;
$totalSales = 0;
foreach ($orders as $o) {
    $totalSales += $o['total'];
}
$orders->data_seek(0); 

$pdf = new FPDF();
$pdf->AddPage();

$pdf->SetFont("Arial","B",16);
$pdf->Cell(190,10,$restaurant['name'],0,1,"C");
$pdf->SetFont("Arial","",12);
$pdf->Cell(190,8,$restaurant['address'],0,1,"C");
$pdf->Cell(190,8,"Phone: ".$restaurant['phone'],0,1,"C");
$pdf->Ln(5);

$pdf->SetFont("Arial","B",14);
$pdf->Cell(190,10,$title,0,1,"C");
$pdf->Ln(5);

$pdf->SetFont("Arial","B",12);
$pdf->Cell(95,10,"Total Orders: ".$totalOrders,0,0,"L");
$pdf->Cell(95,10,"Total Sales: Rs ".number_format($totalSales,2),0,1,"R");
$pdf->Ln(5);

$pdf->SetFont("Arial","B",10);
$pdf->Cell(30,10,"Order #",1);
$pdf->Cell(40,10,"Cashier",1);
$pdf->Cell(35,10,"Payment",1);
$pdf->Cell(35,10,"Total (Rs)",1);
$pdf->Cell(50,10,"Date",1);
$pdf->Ln();

$pdf->SetFont("Arial","",10);
foreach ($orders as $o) {
    $pdf->Cell(30,10,$o['order_number'],1);
    $pdf->Cell(40,10,$o['cashier_name'],1);
    $pdf->Cell(35,10,$o['payment_method'],1);
    $pdf->Cell(35,10,number_format($o['total'],2),1);
    $pdf->Cell(50,10,$o['created_at'],1);
    $pdf->Ln();
}

$filename = strtolower(str_replace(" ","_",$title)).".pdf";
$pdf->Output("D",$filename);
