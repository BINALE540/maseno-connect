<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'vendor') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* GET VENDOR */
$stmt = $conn->prepare("SELECT id FROM vendors WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($vendor_id);
$stmt->fetch();
$stmt->close();

/* ORDERS */
$sql = "
SELECT 
    orders.id,
    orders.total,
    orders.status,
    orders.payment_method,
    orders.pickup_location,
    orders.created_at,
    users.name AS student_name,
    users.phone_number AS student_phone
FROM orders
JOIN users ON orders.student_id = users.id
WHERE orders.vendor_id = ?
ORDER BY orders.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>

<title>Vendor Orders</title>

<link href="../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="../fontawesome/css/all.min.css" rel="stylesheet">

<script src="../assets/bootstrap/js/jquery-3.6.0.min.js"></script>
<script src="../assets/bootstrap/js/sweetalert2@11.js"></script>

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

/* TABLE SCROLL */
.table-container {
    max-height:500px;
    overflow-y:auto;
    background:rgba(255,255,255,0.95);
    border-radius:10px;
    padding:10px;
}
</style>

</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
    <a href="dashboard.php" ><i class="fas fa-home"></i> Home</a>
    <a href="menu.php"><i class="fas fa-utensils"></i> Menu</a>
    <a href="orders.php" class="active"><i class="fas fa-box"></i> Orders</a>
    <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<!-- CONTENT -->
<div class="content" id="content">

<div class="mb-4">
    <?php include '../includes/header.php'; ?>
</div>


<!-- EXPORT -->
<div class="mb-3 d-flex gap-2">

    <a href="export_orders.php?type=csv"
       class="btn btn-success btn-sm"
       onclick="showExportToast()">
       Export CSV
    </a>

    <a href="export_orders.php?type=excel"
       class="btn btn-warning btn-sm"
       onclick="showExportToast()">
       Export Excel
    </a>

</div>

<h4 class="text-white mb-3">Customer Orders</h4>


<div class="row mb-3 g-2">

    <!-- STATUS FILTER -->
    <div class="col-md-3">
        <select id="statusFilter" class="form-select">
            <option value="">All Status</option>
            <option value="Pending">Pending</option>
            <option value="Preparing">Preparing</option>
            <option value="Delivered">Delivered</option>
        </select>
    </div>

    <!-- PAYMENT FILTER -->
    <div class="col-md-3">
        <select id="paymentFilter" class="form-select">
            <option value="">All Payments</option>
            <option value="COD">COD</option>
            <option value="MPESA">MPESA</option>
        </select>
    </div>

</div>

<div class="table-container shadow">

<table class="table table-hover align-middle">
<thead class="table-light">
<tr>
    <th>Student</th>
    <th>Phone</th>
    <th>Pickup</th>
    <th>Total</th>
    <th>Status</th>
    <th>Date</th>
    <th>Action</th>
</tr>

</thead>

<tbody>
<?php while ($row = $result->fetch_assoc()) { ?>
<tr 
data-status="<?= $row['status'] ?>"
data-payment="<?= $row['payment_method'] ?>">

<td><?= htmlspecialchars($row['student_name'] ?? '') ?></td>
<td><?= htmlspecialchars($row['student_phone'] ?? '') ?></td>

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


<td><b>KES <?= number_format($row['total'],2) ?></b></td>

<td>
<select class="form-select form-select-sm status-change"
        data-id="<?= $row['id'] ?>">

    <option value="Pending" <?= $row['status']=='Pending'?'selected':'' ?>>Pending</option>
    <option value="Preparing" <?= $row['status']=='Preparing'?'selected':'' ?>>Preparing</option>
    <option value="Delivered" <?= $row['status']=='Delivered'?'selected':'' ?>>Delivered</option>

</select>
</td>

<td><?= $row['created_at'] ?></td>

<td>
<button class="btn btn-sm btn-info mb-1"
onclick="viewOrder(<?= $row['id'] ?>)">
View
</button>

</td>

</tr>
<?php } ?>

</tbody>
</table>

</div>

</div>
<script src="../assets/bootstrap/js/jquery-3.6.0.min.js"></script>
<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>

<script>
// Sidebar toggle
$('#toggleSidebar').click(function(){
    $('#sidebar').toggleClass('collapsed');
    $('#content').toggleClass('expanded');
});

/* ========================
   TOAST
======================== */
function toast(type, msg){
    Swal.fire({
        toast:true,
        position:'top-end',
        icon:type,
        title:msg,
        showConfirmButton:false,
        timer:2000,
        background:'#1e1e1e',
        color:'#fff'
    });
}

/* ========================
   AUTO STATUS UPDATE
======================== */
$(document).on('change', '.status-change', function(){

    let order_id = $(this).data('id');
    let status = $(this).val();

    $.ajax({
        url: 'update_order.php',
        type: 'POST',
        data: {
            order_id: order_id,
            status: status
        },
        success: function(res){
            toast('success','Order updated');
        },
        error: function(){
            toast('error','Update failed');
        }
    });

});

/* ========================
   EXPORT LOADING
======================== */
function showExportToast(){
    Swal.fire({
        title: 'Preparing export...',
        text: 'Please wait',
        timer: 300,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
}


function viewOrder(id) {

    fetch('get_order_details.php?id=' + id)
    .then(res => res.json())
    .then(data => {

        if (data.error) {
            toast('error', data.error);
            return;
        }

        let o = data.order;
        let items = data.items;

        
        // Parse pickup location JSON safely
let pickupName = "Unspecified";
let lat = null;
let lng = null;

try {
    let parsed = JSON.parse(o.pickup_location);

    if (parsed && typeof parsed === "object") {
        pickupName = parsed.name || "Unspecified";
        lat = parsed.lat ?? null;
        lng = parsed.lng ?? null;
    }

} catch (e) {
    // fallback if it's not JSON (old data)
    if (o.pickup_location) {
        pickupName = o.pickup_location;
    }
}


        let html = `
<div style="text-align:left">

<p><b>Student:</b> ${o.name}</p>
<p><b>Phone:</b> ${o.phone_number}</p>

<p><b>Pickup Location:</b> ${pickupName}</p>

<p>
<b>Coordinates:</b><br>
Latitude: ${lat ?? 'Unspecified'}<br>
Longitude: ${lng ?? 'Unspecified'}
</p>

<p><b>Status:</b> ${o.status}</p>
<p><b>Total:</b> KES ${o.total}</p>

<hr>

<h5>Items</h5>
<table style="width:100%;font-size:13px">
<tr>
    <th>Item</th>
    <th>Qty</th>
    <th>Price</th>
</tr>
`;


        items.forEach(i => {
            html += `
            <tr>
                <td>${i.name}</td>
                <td>${i.quantity}</td>
                <td>${i.price}</td>
            </tr>`;
        });

        html += `</table></div>`;

        Swal.fire({
            title: 'Order Details',
            html: html,
            width: 600,
            background: 'rgba(0,0,0,0.85)',
            color: '#fff',
            confirmButtonColor: '#ffc107'
        });

    });

}


/* FILTER */
function applyFilters(){
let status = $('#statusFilter').val().toLowerCase();
let payment = $('#paymentFilter').val().toLowerCase();

$('tbody tr').each(function(){

let s = ($(this).data('status')||'').toLowerCase();
let p = ($(this).data('payment')||'').toLowerCase();

let show = true;

if(status && s !== status) show=false;
if(payment && p !== payment) show=false;

$(this).toggle(show);

});
}

$('#statusFilter,#paymentFilter').on('change',applyFilters);
</script>

</body>
</html>