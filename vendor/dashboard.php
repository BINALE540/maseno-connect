<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'vendor') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* ========================
   CHECK APPROVAL
======================== */
$stmt = $conn->prepare("SELECT id, status FROM vendors WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if (!$result || $result['status'] !== 'approved') {
    echo "<h2>Your account is pending admin approval.</h2>";
    exit();
}

$vendor_id = $result['id'];

/* ========================
   DASHBOARD STATS
======================== */

// Total Orders
$q1 = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE vendor_id=?");
$q1->bind_param("i", $vendor_id);
$q1->execute();
$totalOrders = $q1->get_result()->fetch_assoc()['total'] ?? 0;

// Pending Orders
$q2 = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE vendor_id=? AND status='Pending'");
$q2->bind_param("i", $vendor_id);
$q2->execute();
$pendingOrders = $q2->get_result()->fetch_assoc()['total'] ?? 0;

// Revenue
$q3 = $conn->prepare("SELECT SUM(total) as total FROM orders WHERE vendor_id=? AND payment_status='Paid'");
$q3->bind_param("i", $vendor_id);
$q3->execute();
$revenue = $q3->get_result()->fetch_assoc()['total'] ?? 0;

// Menu Count
$q4 = $conn->prepare("SELECT COUNT(*) as total FROM food_items WHERE vendor_id=?");
$q4->bind_param("i", $vendor_id);
$q4->execute();
$menuCount = $q4->get_result()->fetch_assoc()['total'] ?? 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Vendor Dashboard</title>

<link href="../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="../fontawesome/css/all.min.css" rel="stylesheet">
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

/* Cards */
.dashboard-card {
    background: rgba(255,255,255,0.95);
    border-radius: 18px;
    transition: 0.3s;
}
.dashboard-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.3);
}

/* Topbar */
.topbar {
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(10px);
    border-radius: 10px;
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
    <a href="dashboard.php" class="active"><i class="fas fa-home"></i> Home</a>
    <a href="menu.php"><i class="fas fa-utensils"></i> Menu</a>
    <a href="orders.php"><i class="fas fa-box"></i> Orders</a>
    <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<!-- CONTENT -->
<div class="content" id="content">

<div class="mb-4">
    <?php include '../includes/header.php'; ?>
</div>

<h4 class="text-white mb-4">Vendor Dashboard</h4>

<div class="row g-4">

    <!-- TOTAL ORDERS -->
    <div class="col-md-3">
        <div class="card dashboard-card shadow p-4 text-center">
            <i class="fas fa-box fa-2x text-primary mb-2"></i>
            <h6 class="text-muted">Total Orders</h6>
            <h3><?= $totalOrders ?></h3>
        </div>
    </div>

    <!-- PENDING -->
    <div class="col-md-3">
        <div class="card dashboard-card shadow p-4 text-center">
            <i class="fas fa-clock fa-2x text-warning mb-2"></i>
            <h6 class="text-muted">Pending Orders</h6>
            <h3><?= $pendingOrders ?></h3>
        </div>
    </div>

    <!-- REVENUE -->
    <div class="col-md-3">
        <div class="card dashboard-card shadow p-4 text-center">
            <i class="fas fa-money-bill-wave fa-2x text-success mb-2"></i>
            <h6 class="text-muted">Revenue</h6>
            <h3>KES <?= number_format($revenue,2) ?></h3>
        </div>
    </div>

    <!-- MENU COUNT -->
    <div class="col-md-3">
        <div class="card dashboard-card shadow p-4 text-center">
            <i class="fas fa-utensils fa-2x text-danger mb-2"></i>
            <h6 class="text-muted">Menu Items</h6>
            <h3><?= $menuCount ?></h3>
        </div>
    </div>

</div>

<!-- QUICK ACTIONS -->
<div class="row mt-5 g-4">

    <div class="col-md-6">
        <div class="card dashboard-card p-4 text-center">
            <h5>Manage Menu</h5>
            <p class="text-muted">Add or update food items</p>
            <a href="menu.php" class="btn btn-success w-100">
                <i class="fas fa-utensils"></i> Open Menu
            </a>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card dashboard-card p-4 text-center">
            <h5>View Orders</h5>
            <p class="text-muted">Track and process orders</p>
            <a href="orders.php" class="btn btn-warning w-100">
                <i class="fas fa-box"></i> View Orders
            </a>
        </div>
    </div>

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

// Welcome alert
document.addEventListener("DOMContentLoaded", function () {
    Swal.fire({
        title: "Welcome Vendor 👨‍🍳",
        text: "Manage your business efficiently",
        icon: "success",
        timer: 2000,
        showConfirmButton: false
    });
});
</script>

</body>
</html>
