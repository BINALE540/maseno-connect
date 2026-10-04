<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    exit("Unauthorized");
}

$student_id = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    exit("Order not specified");
}

$order_id = intval($_GET['id']);

/* ORDER */
$stmt = $conn->prepare("SELECT * FROM orders WHERE id=? AND student_id=?");
$stmt->bind_param("ii", $order_id, $student_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) exit("Order not found");

/* ITEMS */
$item_stmt = $conn->prepare("
SELECT oi.quantity, oi.price, f.name
FROM order_items oi
JOIN food_items f ON oi.food_id = f.id
WHERE oi.order_id = ?
");
$item_stmt->bind_param("i", $order_id);
$item_stmt->execute();
$items = $item_stmt->get_result();
?>

<div style="text-align:left">

<h5>Order #<?= $order['id'] ?></h5>

<?php
$pickup = $order['pickup_location'] ?? '';

$name = "Unspecified";
$lat = null;
$lng = null;

if (!empty($pickup)) {
    $decoded = json_decode($pickup, true);

    if (json_last_error() === JSON_ERROR_NONE) {
        $name = $decoded['name'] ?? "Unspecified";
        $lat  = $decoded['lat'] ?? null;
        $lng  = $decoded['lng'] ?? null;
    } else {
        $name = $pickup; // fallback for old records
    }
}
?>

<p><strong>Pickup Location:</strong> <?= htmlspecialchars($name) ?></p>

<p>
<strong>Coordinates:</strong><br>
Latitude: <?= $lat ?? 'Unspecified' ?><br>
Longitude: <?= $lng ?? 'Unspecified' ?>
</p>

<p><strong>Date:</strong> <?= $order['created_at'] ?></p>

<hr>

<table class="table table-sm">
<thead>
<tr>
<th>Item</th>
<th>Qty</th>
<th>Price</th>
<th>Total</th>
</tr>
</thead>

<tbody>
<?php while ($item = $items->fetch_assoc()) { ?>
<tr>
<td><?= htmlspecialchars($item['name'] ?? '') ?></td>
<td><?= $item['quantity'] ?></td>
<td>KES <?= $item['price'] ?></td>
<td>KES <?= $item['price'] * $item['quantity'] ?></td>
</tr>
<?php } ?>
</tbody>
</table>

<h6 class="mt-3">Total: <strong>KES <?= $order['total'] ?></strong></h6>

</div>
