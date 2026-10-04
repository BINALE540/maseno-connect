<?php
session_start();
include '../includes/db.php';

if ($_SESSION['role'] != 'vendor') {
    exit("Access denied");
}

$user_id = $_SESSION['user_id'];

/* get vendor id */
$stmt = $conn->prepare("SELECT id FROM vendors WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($vendor_id);
$stmt->fetch();
$stmt->close();

$type = $_GET['type'] ?? 'csv';

/* fetch orders */
$sql = "
SELECT 
  orders.id,
  users.name AS student,
  orders.pickup_location,
  orders.total,
  orders.status,
  orders.created_at
FROM orders
JOIN users ON orders.student_id = users.id
WHERE orders.vendor_id = ?
ORDER BY orders.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$result = $stmt->get_result();

/* CSV */
if ($type == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=vendor_orders.csv');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['OrderID','Student','Pickup','Total','Status','Date']);

    while ($row = $result->fetch_assoc()) {
        fputcsv($out, $row);
    }

    fclose($out);
    exit();
}

/* Excel */
if ($type == 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=vendor_orders.xls");

    echo "OrderID\tStudent\tPickup\tTotal\tStatus\tDate\n";

    while ($row = $result->fetch_assoc()) {
        echo implode("\t", $row) . "\n";
    }

    exit();
}
