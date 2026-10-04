<?php
session_start();
include '../includes/db.php';
//orders.php
// Auth check
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

$sql = "
SELECT id, total, status, dispute_status, pickup_location, payment_status, 
       payment_method, mpesa_receipt, mpesa_phone, created_at
FROM orders
WHERE student_id = ?
ORDER BY created_at DESC
";

$stmt = $conn->prepare($sql);
if (!$stmt) die("SQL ERROR: " . $conn->error);

$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Orders</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Bootstrap -->
<link href="../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="../fontawesome/css/all.min.css" rel="stylesheet">

<!-- SweetAlert -->
<script src="../assets/bootstrap/js/sweetalert2@11.js"></script>
<script src="../assets/bootstrap/js/jquery-3.6.0.min.js"></script>

<style>
body {
    background: url('../assets/images/food-bg.jpg') no-repeat center center fixed;
    background-size: cover;
    font-family: 'Poppins', sans-serif;
}

/* Overlay */
body::before {
    content: '';
    position: fixed;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.6);
    top: 0;
    left: 0;
    z-index: -1;
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




/* Content */
.content {
    margin-left: 250px;
    padding: 20px;
    transition: 0.3s;
}
.content.expanded { margin-left: 70px; }
/* Topbar */
.topbar {
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(10px);
    border-radius: 10px;
}

/* Cards */
.dashboard-card {
    background: rgba(255,255,255,0.95);
    border-radius: 15px;
}

/* Scroll table */
.table-container {
    max-height: 500px;
    overflow-y: auto;
}

/* Scrollbar */
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-thumb {
    background: #ffc107;
    border-radius: 10px;
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
    <a href="dashboard.php"><i class="fas fa-home"></i> Home</a>
    <a href="orders.php" class="active"><i class="fas fa-box"></i> Orders</a>
    <a href="cart.php"><i class="fas fa-shopping-cart"></i> Cart</a>
    <a href="#"><i class="fas fa-user"></i> Profile</a>
    <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<!-- CONTENT -->
<div class="content" id="content">

<?php include '../includes/header.php'; ?>

<h4 class="text-white mb-4">My Orders</h4>

<!-- FILTERS -->
<div class="card dashboard-card p-3 mb-4">
    <div class="row g-2">
        <div class="col-md-4">
            <input type="date" id="filterDate" class="form-control">
        </div>

        <div class="col-md-4">
            <select id="filterStatus" class="form-control">
                <option value="">All Status</option>
                <option>Pending</option>
                <option>Preparing</option>
                <option>Delivered</option>
            </select>
        </div>

        <div class="col-md-4">
            <button class="btn btn-warning w-100" id="clearFilters">
                <i class="fas fa-times"></i> Clear Filters
            </button>
        </div>
    </div>
</div>

<!-- TABLE -->
<div class="card dashboard-card shadow-sm">
<div class="card-body p-0">
<div class="table-container">

<table class="table align-middle mb-0" id="ordersTable">

<thead class="table-light">
<tr>
<th>Total</th>
<th>Status</th>
<th>Payment</th>
<th>Dispute</th>
<th>Pickup</th>
<th>Date</th>
<th>Action</th>
</tr>
</thead>

<tbody>

<?php while ($row = $result->fetch_assoc()) { 

$status = $row['status'] ?? 'Pending';
$badge = 'bg-secondary';
if ($status == 'Preparing') $badge = 'bg-primary';
elseif ($status == 'Delivered') $badge = 'bg-success';

$dispute = $row['dispute_status'] ?? 'none';
?>

<tr 
data-status="<?= $status ?>" 
data-date="<?= date('Y-m-d', strtotime($row['created_at'])) ?>"
>

<td>KES <?= htmlspecialchars($row['total']) ?></td>

<td>
<span class="badge <?= $badge ?>">
<?= htmlspecialchars($status) ?>
</span>
</td>

<td>
<strong><?= htmlspecialchars($row['payment_method']) ?></strong><br>

<?php if ($row['payment_method'] == 'MPESA') { ?>
<small class="text-success">
Receipt: <?= htmlspecialchars($row['mpesa_receipt'] ?? '') ?>
</small><br>
<small>Phone: <?= htmlspecialchars($row['mpesa_phone'] ?? '') ?></small>
<?php } ?>

<br>
<span class="badge <?= $row['payment_status'] == 'Paid' ? 'bg-success' : 'bg-warning' ?>">
<?= htmlspecialchars($row['payment_status'] ?? '') ?>
</span>
</td>

<td>
<?php if ($dispute == 'none') { ?>
<span class="badge bg-secondary">None</span>
<?php } elseif ($dispute == 'disputed') { ?>
<span class="badge bg-danger">Disputed</span>
<?php } else { ?>
<span class="badge bg-success">Resolved</span>
<?php } ?>
</td>

<td>
<?php
$pickup = $row['pickup_location'] ?? '';

$name = "Unspecified";

if (!empty($pickup)) {
    $decoded = json_decode($pickup, true);

    if (json_last_error() === JSON_ERROR_NONE && isset($decoded['name'])) {
        $name = $decoded['name'];
    } elseif (!empty($pickup)) {
        $name = $pickup; // fallback (old data)
    }
}
?>

<span class="badge bg-info">
<?= htmlspecialchars($name) ?>
</span>
</td>


<td><?= htmlspecialchars($row['created_at'] ?? '') ?></td>

<td>
<button 
 class="btn btn-primary btn-sm view-order" 
 data-id="<?= $row['id'] ?>">
 <i class="fas fa-eye"></i> View
</button>


<?php if ($status == 'Delivered' && $dispute == 'none') { ?>
<a href="raise_dispute.php?id=<?= $row['id'] ?>"
class="btn btn-warning btn-sm dispute-btn">Dispute</a>
<?php } ?>
</td>

</tr>

<?php } ?>

</tbody>
</table>

</div>
</div>
</div>

</div>

<!-- JS -->
<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>

<script>
// Sidebar toggle
$('#toggleSidebar').click(function() {
    $('#sidebar').toggleClass('collapsed');
    $('#content').toggleClass('expanded');
});

// FILTERS
function filterTable() {
    let status = $('#filterStatus').val();
    let date = $('#filterDate').val();

    $('#ordersTable tbody tr').each(function() {
        let rowStatus = $(this).data('status');
        let rowDate = $(this).data('date');

        let show = true;

        if (status && rowStatus !== status) show = false;
        if (date && rowDate !== date) show = false;

        $(this).toggle(show);
    });
}

$('#filterStatus, #filterDate').on('change', filterTable);

$('#clearFilters').click(function(){
    $('#filterStatus').val('');
    $('#filterDate').val('');
    filterTable();

    Swal.fire({
        icon: 'success',
        title: 'Filters cleared',
        timer: 1200,
        showConfirmButton: false
    });
});

// Dispute confirm
$('.dispute-btn').click(function(e){
    e.preventDefault();
    let link = this.href;

    Swal.fire({
        title: 'Raise dispute?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes'
    }).then((result) => {
        if (result.isConfirmed) window.location = link;
    });
});

// Auto refresh
setInterval(() => {
    location.reload();
}, 30000);

$('.view-order').click(function () {
    let orderId = $(this).data('id');

    Swal.fire({
        title: 'Order Details',
        html: '<div id="orderContent">Loading...</div>',
        width: '700px',
        background: 'rgba(0,0,0,0.8)',
        color: '#fff',
        showConfirmButton: false,
        didOpen: () => {

            $.ajax({
                url: 'order_details_ajax.php',
                method: 'GET',
                data: { id: orderId },
                success: function (data) {
                    $('#orderContent').html(data);
                },
                error: function () {
                    $('#orderContent').html('<p class="text-danger">Failed to load order</p>');
                }
            });

        }
    });
});

</script>

</body>
</html>
