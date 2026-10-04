<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$vendors = $conn->query("
SELECT id, business_name
FROM vendors
WHERE status='approved'
");

// Pending Orders Count
$pendingOrdersQuery = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM orders 
    WHERE student_id = ? AND status = 'Pending'
");
$pendingOrdersQuery->bind_param("i", $_SESSION['user_id']);
$pendingOrdersQuery->execute();
$pendingOrders = $pendingOrdersQuery->get_result()->fetch_assoc()['total'] ?? 0;


// Pending Payments Sum
$pendingPaymentsQuery = $conn->prepare("
    SELECT SUM(total) as total 
    FROM orders 
    WHERE student_id = ? AND payment_status = 'Pending'
");
$pendingPaymentsQuery->bind_param("i", $_SESSION['user_id']);
$pendingPaymentsQuery->execute();
$pendingPayments = $pendingPaymentsQuery->get_result()->fetch_assoc()['total'] ?? 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Dashboard</title>

<!-- Bootstrap -->
<link href="../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="../fontawesome/css/all.min.css" rel="stylesheet">

<!-- Font Awesome -->
<link href="../assets/bootstrap/css/all.min.css" rel="stylesheet">

<!-- SweetAlert -->
<script src="../assets/bootstrap/js/sweetalert2@11.js"></script>

<!-- Custom CSS -->
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

/* Sidebar */
.sidebar {
    height: 100vh;
    width: 250px;
    position: fixed;
    background: #111;
    transition: 0.3s;
    padding-top: 20px;
}

.sidebar.collapsed {
    width: 70px;
}

.sidebar a {
    padding: 15px;
    display: block;
    color: #fff;
    text-decoration: none;
    transition: 0.2s;
}

.sidebar a:hover {
    background: #ffc107;
    color: #000;
}

.sidebar i {
    margin-right: 10px;
}

/* Content */
.content {
    margin-left: 250px;
    padding: 20px;
    transition: 0.3s;
}

.content.expanded {
    margin-left: 70px;
}

/* Cards */
.dashboard-card {
    background: rgba(255,255,255,0.95);
    border-radius: 15px;
}

.vendor-card {
    transition: 0.3s;
}
.vendor-card:hover {
    transform: scale(1.05);
}
.topbar {
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(10px);
    border-radius: 10px;
}
.dashboard-card {
    border-radius: 18px;
    transition: 0.3s;
}

.dashboard-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
}


</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
    <a href="#" class="active"><i class="fas fa-home"></i> <span class="text">Home</span></a>
    <a href="orders.php"><i class="fas fa-box"></i> <span class="text">Orders</span></a>
    <a href="cart.php"><i class="fas fa-shopping-cart"></i> Cart</a>
    <a href="#"><i class="fas fa-user"></i> <span class="text">Profile</span></a>
    <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> <span class="text">Logout</span></a>
</div>

<!-- CONTENT -->

<div class="content" id="content">
<div class="mb-4">
    <?php include '../includes/header.php'; ?>
</div>




<div class="row g-4">

    <!-- Pending Orders -->
    <div class="col-md-6">
        <div class="card dashboard-card shadow p-4 text-center">
            <div class="mb-2">
                <i class="fas fa-clock fa-2x text-warning"></i>
            </div>

            <h5 class="text-muted">Pending Orders</h5>

            <h2 class="fw-bold text-dark">
                <?= $pendingOrders ?>
            </h2>

        </div>
    </div>

    <!-- Pending Payments -->
    <div class="col-md-6">
        <div class="card dashboard-card shadow p-4 text-center">
            <div class="mb-2">
                <i class="fas fa-money-bill-wave fa-2x text-danger"></i>
            </div>

            <h5 class="text-muted">Pending Payments</h5>

            <h2 class="fw-bold text-dark">
                KES <?= number_format($pendingPayments, 2) ?>
            </h2>


        </div>
    </div>

</div>


<!-- VENDORS -->
<h4 class="text-white mt-5 mb-3 text-center">Available Vendors</h4>

<div class="row">
<?php while($row = $vendors->fetch_assoc()) { ?>
    <div class="col-md-4 mb-4">
        <div class="card vendor-card shadow text-center p-3">
            <h5><?= htmlspecialchars($row['business_name']) ?></h5>

            <a href="menu.php?vendor_id=<?= $row['id'] ?>" 
               class="btn btn-warning btn-sm">
               <i class="fas fa-utensils"></i> View Menu
            </a>
        </div>
    </div>
<?php } ?>
</div>

</div>

<!-- JS -->
<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/bootstrap/js/jquery-3.6.0.min.js"></script>

<script>
// SIDEBAR TOGGLE
$('#toggleSidebar').click(function() {
    $('#sidebar').toggleClass('collapsed');
    $('#content').toggleClass('expanded');
});

// SWEETALERT WELCOME
document.addEventListener("DOMContentLoaded", function () {
    Swal.fire({
        title: "Welcome!",
        text: "Browse meals from vendors 🍔",
        icon: "success",
        timer: 2000,
        showConfirmButton: false
    });
});
</script>

</body>
</html>
