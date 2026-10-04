<?php
session_start();
include '../includes/db.php';

if ($_SESSION['role'] != 'admin') {
    exit("Access denied");
}

$type = $_GET['type'] ?? 'csv';

/* Optional date filter */
$from = $_GET['from'] ?? null;
$to   = $_GET['to'] ?? null;

$where = "";
$params = [];

if ($from && $to) {
    $where = "WHERE DATE(orders.created_at) BETWEEN ? AND ?";
    $params = [$from, $to];
}

/* Fetch data */
$sql = "
SELECT 
  orders.id,
  u1.name AS student,
  u2.name AS vendor,
  orders.pickup_location,
  orders.total,
  orders.status,
  orders.created_at
FROM orders
JOIN users u1 ON orders.student_id = u1.id
JOIN vendors v ON orders.vendor_id = v.id
JOIN users u2 ON v.user_id = u2.id
$where
ORDER BY orders.created_at DESC
";

$stmt = $conn->prepare($sql);

if ($params) {
    $stmt->bind_param("ss", ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

/* CSV EXPORT */
if ($type == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=admin_orders.csv');

    $out = fopen('php://output', 'w');

    fputcsv($out, ['OrderID','Student','Vendor','Pickup','Total','Status','Date']);

    while ($row = $result->fetch_assoc()) {
        fputcsv($out, $row);
    }

    fclose($out);
    exit();
}

/* EXCEL EXPORT */
if ($type == 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=admin_orders.xls");

    echo "OrderID\tStudent\tVendor\tPickup\tTotal\tStatus\tDate\n";

    while ($row = $result->fetch_assoc()) {
        echo implode("\t", $row) . "\n";
    }

    exit();
}
