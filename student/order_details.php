<?php
session_start();
include '../includes/db.php';
//order_details.php
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    die("Order not specified");
}

$order_id = intval($_GET['id']);

/* FETCH ORDER */
$sql = "
SELECT *
FROM orders
WHERE id = ? AND student_id = ?
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("ORDER SQL ERROR: " . $conn->error);
}

$stmt->bind_param("ii", $order_id, $student_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die("Order not found");
}

/* FETCH ITEMS */
/* Fetch order items */
$item_sql = "
SELECT oi.quantity, oi.price, f.name
FROM order_items oi
JOIN food_items f ON oi.food_id = f.id
WHERE oi.order_id = ?
";

$item_stmt = $conn->prepare($item_sql);

if (!$item_stmt) {
    die("ITEM SQL ERROR: " . $conn->error);
}

$item_stmt->bind_param("i", $order_id);
$item_stmt->execute();
$items = $item_stmt->get_result();
?>

<?php include '../includes/header.php'; ?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Bootstrap -->
<link href="../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="../fontawesome/css/all.min.css" rel="stylesheet">

<!-- SweetAlert -->
<script src="../assets/bootstrap/js/sweetalert2@11.js"></script>
<script src="../assets/bootstrap/js/jquery-3.6.0.min.js"></script>

<div class="container py-5">

  <h3 class="page-title mb-4">Order #<?= $order['id'] ?></h3>

  <div class="row g-4">

    <!-- Order Summary -->
    <div class="col-md-6">
      <div class="card shadow-sm">
        <div class="card-body">

          <h5 class="mb-3">Order Summary</h5>

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

          <?php
            $status = $order['status'];
            $badge = 'bg-secondary';
            if ($status == 'Preparing') $badge = 'bg-primary';
            elseif ($status == 'Delivered') $badge = 'bg-success';
          ?>

          <p>
            <strong>Status:</strong>
            <span class="badge <?= $badge ?>">
              <?= $status ?>
            </span>
          </p>

          <p>
            <strong>Payment:</strong>
            <span class="badge 
              <?= $order['payment_status']=='Paid' ? 'bg-success' : 
                 ($order['payment_status']=='Pending' ? 'bg-warning' : 'bg-secondary') ?>">
              <?= $order['payment_status'] ?>
            </span>
          </p>

          <h4 class="mt-3">Total: KES <?= $order['total'] ?></h4>

        </div>
      </div>
    </div>

    <!-- Items -->
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
              <?php while ($item = $items->fetch_assoc()) { ?>
              <tr>
                <td><?= htmlspecialchars($item['name']) ?></td>
                <td><?= $item['quantity'] ?></td>
                <td>KES <?= $item['price'] ?></td>
                <td>KES <?= $item['price'] * $item['quantity'] ?></td>
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