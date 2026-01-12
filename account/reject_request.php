<?php
session_start();
require_once 'config.php';

if (!isset($_GET['id'])) die("Missing id");
$id = intval($_GET['id']);

// Mark rejected
$stmt = $conn->prepare("UPDATE restaurant_requests SET status = 'Rejected' WHERE id = ? AND status = 'Pending'");
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->close();

header("Location: super_requests.php");
exit;
