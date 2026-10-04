<?php
include '../includes/db.php';

$order_id = $_GET['order_id'] ?? 0;

$stmt = $conn->prepare("
SELECT payment_status 
FROM orders 
WHERE id=?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();

$result = $stmt->get_result()->fetch_assoc();

echo json_encode($result);
