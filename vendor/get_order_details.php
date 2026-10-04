<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'vendor') {
    exit;
}

$user_id = $_SESSION['user_id'];

/* vendor id */
$v = $conn->prepare("SELECT id FROM vendors WHERE user_id=?");
$v->bind_param("i", $user_id);
$v->execute();
$v->bind_result($vendor_id);
$v->fetch();
$v->close();

$order_id = intval($_GET['id']);

/* ORDER */
$sql = "
SELECT o.*, u.name, u.phone_number
FROM orders o
JOIN users u ON u.id = o.student_id
WHERE o.id=? AND o.vendor_id=?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $order_id, $vendor_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    echo json_encode(['error' => 'Order not found']);
    exit;
}

/* ITEMS */
$item_sql = "
SELECT oi.quantity, oi.price, f.name
FROM order_items oi
JOIN food_items f ON f.id = oi.food_id
WHERE oi.order_id=?
";

$item_stmt = $conn->prepare($item_sql);
$item_stmt->bind_param("i", $order_id);
$item_stmt->execute();
$items = $item_stmt->get_result();

$data_items = [];
while ($i = $items->fetch_assoc()) {
    $data_items[] = $i;
}

echo json_encode([
    "order" => $order,
    "items" => $data_items
]);
