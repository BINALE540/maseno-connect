<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

/* COUNTS */
$vendors = $conn->query("SELECT COUNT(*) AS c FROM vendors")->fetch_assoc()['c'];
$students = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='student'")->fetch_assoc()['c'];
$orders = $conn->query("SELECT COUNT(*) AS c FROM orders")->fetch_assoc()['c'];

/* ORDERS BY STATUS (FOR CHART) */
$statusData = [
    'Pending' => 0,
    'Preparing' => 0,
    'Delivered' => 0
];

$res = $conn->query("SELECT status, COUNT(*) as c FROM orders GROUP BY status");
while($row = $res->fetch_assoc()){
    $statusData[$row['status']] = $row['c'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="../fontawesome/css/all.min.css" rel="stylesheet">

<script src="../assets/bootstrap/js/jquery-3.6.0.min.js"></script>
<script src="../assets/bootstrap/js/chart.js"></script>
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

/* CARDS */
.dashboard-card {
    background:rgba(255,255,255,0.95);
    border-radius:15px;
    transition:0.3s;
}
.dashboard-card:hover {
    transform:translateY(-5px);
    box-shadow:0 10px 25px rgba(0,0,0,0.2);
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
    <a href="dashboard.php" class="active"><i class="fas fa-home"></i> Home</a>
    <a href="vendors.php"><i class="fas fa-store"></i> Vendors</a>
    <a href="orders.php"><i class="fas fa-box"></i> Orders</a>
          <a href="users.php"><i class="fas fa-users"></i> Users</a>
    <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<!-- CONTENT -->
<div class="content" id="content">

<!-- HEADER -->
<div class="mb-4">
    <?php include '../includes/header.php'; ?>
</div>

<h3 class="text-white mb-4">Admin Dashboard</h3>

<!-- STATS -->
<div class="row g-4">

    <div class="col-md-4">
        <div class="card dashboard-card text-center p-4">
            <i class="fas fa-store fa-2x text-primary mb-2"></i>
            <h5>Vendors</h5>
            <h2><?= $vendors ?></h2>
           
        </div>
    </div>

    <div class="col-md-4">
        <div class="card dashboard-card text-center p-4">
            <i class="fas fa-box fa-2x text-success mb-2"></i>
            <h5>Orders</h5>
            <h2><?= $orders ?></h2>
        
        </div>
    </div>

    <div class="col-md-4">
        <div class="card dashboard-card text-center p-4">
            <i class="fas fa-users fa-2x text-dark mb-2"></i>
            <h5>Students</h5>
            <h2><?= $students ?></h2>
        </div>
    </div>

</div>

<!-- CHART -->
<div class="row mt-5 justify-content-left">
    <div class="col-md-4 d-flex justify-content-center">

        <div class="card dashboard-card p-3 text-center"
             style="width:400px; height:400px;">

            <h6 class="mb-2">Orders Analytics</h6>

            <div style="flex:1; position:relative;">
                <canvas id="ordersChart"></canvas>
            </div>

        </div>

    </div>
</div>





</div>

<!-- JS -->
<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>

<script>
// SIDEBAR TOGGLE
$('#toggleSidebar').click(function(){
    $('#sidebar').toggleClass('collapsed');
    $('#content').toggleClass('expanded');
});

// SWEETALERT WELCOME
Swal.fire({
    title: 'Welcome Admin 👋',
    text: 'Monitor system activity in real-time',
    icon: 'info',
    timer: 2000,
    showConfirmButton: false
});

// CHART
const ctx = document.getElementById('ordersChart');

new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Pending','Preparing','Delivered'],
        datasets: [{
            data: [
                <?= $statusData['Pending'] ?>,
                <?= $statusData['Preparing'] ?>,
                <?= $statusData['Delivered'] ?>
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false, // 🔥 MUST BE FALSE

        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});


</script>

</body>
</html>
