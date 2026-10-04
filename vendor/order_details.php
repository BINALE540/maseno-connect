<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'vendor') {
    header("Location: ../auth/login.php");
    exit();
}

/* Get vendor ID from vendors table */
$user_id = $_SESSION['user_id'];

$v = $conn->prepare("SELECT id FROM vendors WHERE user_id=?");
$v->bind_param("i",$user_id);
$v->execute();
$v->bind_result($vendor_id);
$v->fetch();
$v->close();

/* Check order id */
if (!isset($_GET['id'])) {
    die("Order not specified");
}

$order_id = intval($_GET['id']);

/* FETCH ORDER INFO */
$sql = "
SELECT 
o.*,
u.name AS student_name,
u.phone_number AS student_phone
FROM orders o
JOIN users u ON o.student_id = u.id
WHERE o.id = ? AND o.vendor_id = ?
";

$stmt = $conn->prepare($sql) or die($conn->error);
$stmt->bind_param("ii",$order_id,$vendor_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die("Order not found");
}

/* FETCH ORDER ITEMS */
$item_sql = "
SELECT 
oi.quantity,
oi.price,
f.name
FROM order_items oi
JOIN food_items f ON oi.food_id = f.id
WHERE oi.order_id = ?
";

$item_stmt = $conn->prepare($item_sql) or die($conn->error);
$item_stmt->bind_param("i",$order_id);
$item_stmt->execute();
$items = $item_stmt->get_result();
?>

<?php include '../includes/header.php'; ?>

<div class="container py-5">

<h3 class="page-title mb-4">Order #<?= $order['id'] ?></h3>

<div class="row g-4">

<!-- ORDER INFO -->
<div class="col-md-6">
<div class="card shadow-sm">
<div class="card-body">

<h5 class="mb-3">Order Info</h5>

<p><strong>Student:</strong> <?= htmlspecialchars($order['student_name']) ?></p>

<p><strong>Phone:</strong> <?= htmlspecialchars($order['student_phone']) ?></p>

<p><strong>Pickup:</strong> <?= htmlspecialchars($order['pickup_location']) ?></p>

<p><strong>Date:</strong> <?= $order['created_at'] ?></p>

<p>
<strong>Status:</strong>
<span class="badge bg-info">
<?= htmlspecialchars($order['status']) ?>
</span>
</p>

<p>
<strong>Payment:</strong>
<span class="badge 
<?= $order['payment_status']=='Paid' ? 'bg-success' :
($order['payment_status']=='Pending' ? 'bg-warning' : 'bg-secondary') ?>">
<?= htmlspecialchars($order['payment_status']) ?>
</span>
</p>

<h4 class="mt-3">
Total: KES <?= number_format($order['total'],2) ?>
</h4>

</div>
</div>

<!-- UPDATE STATUS -->
<div class="card shadow-sm mt-4">
<div class="card-body">

<h5 class="mb-3">Update Status</h5>

<form method="POST" action="update_order_status.php">

<input type="hidden" name="order_id" value="<?= $order['id'] ?>">

<select name="status" class="form-select mb-3">

<option value="Pending" <?= $order['status']=='Pending'?'selected':'' ?>>Pending</option>

<option value="Preparing" <?= $order['status']=='Preparing'?'selected':'' ?>>Preparing</option>

<option value="Ready" <?= $order['status']=='Ready'?'selected':'' ?>>Ready</option>

<option value="Delivered" <?= $order['status']=='Delivered'?'selected':'' ?>>Delivered</option>

</select>

<button class="btn btn-success w-100">
Update Status
</button>

</form>

</div>
</div>

</div>

<!-- ORDER ITEMS -->
<div class="col-md-6">
<div class="card shadow-sm">
<div class="card-body">

<h5 class="mb-3">Items</h5>

<table class="table">
<thead>
<tr>
<th>Item</th>
<th>Qty</th>
<th>Price</th>
<th>Total</th>
</tr>
</thead>

<tbody>

<?php while($item = $items->fetch_assoc()) { ?>

<tr>

<td><?= htmlspecialchars($item['name']) ?></td>

<td><?= $item['quantity'] ?></td>

<td>KES <?= number_format($item['price'],2) ?></td>

<td>KES <?= number_format($item['price']*$item['quantity'],2) ?></td>

</tr>

<?php } ?>

</tbody>
</table>

</div>
</div>
</div>

</div>

<a href="orders.php" class="btn btn-secondary mt-4">
← Back to Orders
</a>

</div>

<?php include '../includes/footer.php'; ?>