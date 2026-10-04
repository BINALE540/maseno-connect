<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$result = $conn->query("
SELECT 
    orders.id,
    orders.total,
    orders.status,
    orders.pickup_location,
    orders.created_at,
    orders.payment_method,
    u1.name AS student,
    u1.phone_number AS student_phone,
    u2.name AS vendor
FROM orders
JOIN users u1 ON orders.student_id = u1.id
JOIN vendors v ON orders.vendor_id = v.id
JOIN users u2 ON v.user_id = u2.id
ORDER BY orders.created_at DESC
");

if (!$result) {
    die("SQL ERROR: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>All Orders</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="../fontawesome/css/all.min.css" rel="stylesheet">

<script src="../assets/bootstrap/js/jquery-3.6.0.min.js"></script>
<script src="../assets/bootstrap/js/sweetalert2@11.js"></script>

<style>
/* BACKGROUND */
body {
    background: url('../assets/images/food-bg.jpg') no-repeat center center fixed;
    background-size: cover;
}
body::before {
    content:'';
    position:fixed;
    width:100%; height:100%;
    background:rgba(0,0,0,0.65);
    z-index:-1;
}

/* SIDEBAR */
.sidebar {
    height:100vh;
    width:250px;
    position:fixed;
    background:#111;
    padding-top:20px;
    transition:0.3s;
}
.sidebar.collapsed { width:70px; }

.sidebar a {
    display:block;
    padding:15px;
    color:#fff;
    text-decoration:none;
}
.sidebar a:hover,
.sidebar a.active {
    background:#ffc107;
    color:#000;
}

/* CONTENT */
.content {
    margin-left:250px;
    padding:20px;
    transition:0.3s;
}
.content.expanded { margin-left:70px; }

/* CARD */
.glass-card {
    background:rgba(255,255,255,0.95);
    border-radius:15px;
}

/* TABLE SCROLL */
.table-container {
    max-height:500px;
    overflow-y:auto;
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
    <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="vendors.php"><i class="fas fa-store"></i> Vendors</a>
    <a href="orders.php" class="active"><i class="fas fa-box"></i> Orders</a>
        <a href="users.php"><i class="fas fa-users"></i> Users</a>
    <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<!-- CONTENT -->
<div class="content" id="content">

<?php include '../includes/header.php'; ?>

<h3 class="text-white mb-4"> All Orders</h3>

<!-- FILTERS -->
<div class="row g-2 mb-3">

    <!-- SEARCH -->
    <div class="col-md-4">
        <input type="text" id="search" class="form-control"
        placeholder="Search student, vendor or phone...">
    </div>

    <!-- STATUS -->
    <div class="col-md-3">
        <select id="statusFilter" class="form-select">
            <option value="">All Status</option>
            <option value="Pending">Pending</option>
            <option value="Preparing">Preparing</option>
            <option value="Delivered">Delivered</option>
        </select>
    </div>

    <!-- EXPORT -->
    <div class="col-md-5 text-end">
        <a href="export_orders.php?type=csv"
           class="btn btn-success btn-sm"
           onclick="exportToast()">
           <i class="fas fa-file-csv"></i> CSV
        </a>

        <a href="export_orders.php?type=excel"
           class="btn btn-warning btn-sm"
           onclick="exportToast()">
           <i class="fas fa-file-excel"></i> Excel
        </a>
    </div>

</div>

<!-- TABLE -->
<div class="glass-card p-3 shadow">

<div class="table-container">

<table class="table table-hover align-middle">
<thead class="table-dark">
<tr>
  <th>Student</th>
  <th>Phone</th>
  <th>Vendor</th>
  <th>Pickup</th>
  <th>Total</th>
  <th>Payment</th>
  <th>Status</th>
  <th>Date</th>
</tr>
</thead>

<tbody>

<?php while ($o = $result->fetch_assoc()) { ?>

<tr 
data-status="<?= $o['status'] ?>"
data-text="<?= strtolower($o['student'].' '.$o['vendor'].' '.$o['student_phone'] ?? '') ?>"
>

<td><?= htmlspecialchars($o['student'] ?? '') ?></td>

<td>
<span class="badge bg-secondary">
<?= htmlspecialchars($o['student_phone'] ?? '') ?>
</span>
</td>

<td>
<span class="badge bg-primary">
<?= htmlspecialchars($o['vendor'] ?? '') ?>
</span>
</td>

<td>
<?php
$pickup = $o['pickup_location'] ?? '';

$name = "Unspecified";

if (!empty($pickup)) {
    $decoded = json_decode($pickup, true);

    if (json_last_error() === JSON_ERROR_NONE && isset($decoded['name'])) {
        $name = $decoded['name'];
    } elseif (!empty($pickup)) {
        $name = $pickup; // fallback for old records
    }
}
?>

<span class="badge bg-info text-dark">
<?= htmlspecialchars($name) ?>
</span>
</td>



<td><b>KES <?= number_format($o['total'],2) ?></b></td>

<td>
<span class="badge <?= $o['payment_method']=='MPESA' ? 'bg-success' : 'bg-warning text-dark' ?>">
<?= $o['payment_method'] ?>
</span>
</td>

<td>
<span class="badge bg-dark">
<?= $o['status'] ?>
</span>
</td>

<td><?= $o['created_at'] ?></td>

</tr>

<?php } ?>

</tbody>
</table>

</div>
</div>

</div>

<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>

<script>

/* SIDEBAR */
$('#toggleSidebar').click(function(){
    $('#sidebar').toggleClass('collapsed');
    $('#content').toggleClass('expanded');
});

/* EXPORT LOADING */
function exportToast(){
    Swal.fire({
        title:'Preparing export...',
        timer:800,
        showConfirmButton:false,
        didOpen:()=>Swal.showLoading()
    });
}

/* FILTER + SEARCH */
function filterTable(){

    let search = $('#search').val().toLowerCase();
    let status = $('#statusFilter').val().toLowerCase();

    $('tbody tr').each(function(){

        let text = $(this).data('text');
        let s = ($(this).data('status')||'').toLowerCase();

        let show = true;

        if(search && !text.includes(search)) show = false;
        if(status && s !== status) show = false;

        $(this).toggle(show);

    });
}

$('#search, #statusFilter').on('keyup change', filterTable);

</script>

</body>
</html>
