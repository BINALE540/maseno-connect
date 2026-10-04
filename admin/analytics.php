<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    exit();
}

/* =========================
   AJAX REAL-TIME DATA
========================= */
if(isset($_GET['ajax'])){

    $kpis = [
        "orders" => $conn->query("SELECT COUNT(*) c FROM orders")->fetch_assoc()['c'],
        "revenue" => $conn->query("SELECT SUM(total) s FROM orders")->fetch_assoc()['s'] ?? 0
    ];

    echo json_encode($kpis);
    exit();
}

/* KPIs */
$total_orders = $conn->query("SELECT COUNT(*) c FROM orders")->fetch_assoc()['c'];
$total_revenue = $conn->query("SELECT SUM(total) s FROM orders")->fetch_assoc()['s'] ?? 0;
$pending = $conn->query("SELECT COUNT(*) c FROM orders WHERE status='Pending'")->fetch_assoc()['c'];
$preparing = $conn->query("SELECT COUNT(*) c FROM orders WHERE status='Preparing'")->fetch_assoc()['c'];
$delivered = $conn->query("SELECT COUNT(*) c FROM orders WHERE status='Delivered'")->fetch_assoc()['c'];

/* STATUS */
$statusData = [$pending,$preparing,$delivered];

/* DAILY REVENUE */
$dailyRevenue = $conn->query("
SELECT DATE(created_at) d, SUM(total) t
FROM orders
GROUP BY d
ORDER BY d ASC
LIMIT 7
");

$rev_dates=[]; $rev_values=[];
while($r=$dailyRevenue->fetch_assoc()){
    $rev_dates[]=$r['d'];
    $rev_values[]=$r['t'];
}

/* MONTHLY REVENUE */
$monthly = $conn->query("
SELECT DATE_FORMAT(created_at,'%Y-%m') m, SUM(total) t
FROM orders
GROUP BY m
ORDER BY m ASC
LIMIT 6
");

$months=[]; $month_values=[];
while($m=$monthly->fetch_assoc()){
    $months[]=$m['m'];
    $month_values[]=$m['t'];
}

/* VENDOR PERFORMANCE */
$vendorChart = $conn->query("
SELECT v.business_name, SUM(o.total) t
FROM orders o
JOIN vendors v ON o.vendor_id=v.id
GROUP BY v.id
ORDER BY t DESC
LIMIT 5
");

$v_names=[]; $v_totals=[];
while($v=$vendorChart->fetch_assoc()){
    $v_names[]=$v['business_name'];
    $v_totals[]=$v['t'];
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Analytics</title>

<link href="../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="../fontawesome/css/all.min.css" rel="stylesheet">

<script src="../assets/bootstrap/js/jquery-3.6.0.min.js"></script>
<script src="../assets/bootstrap/js/chart.js"></script>

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
.card{border-radius:15px;}
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
    <a href="dashboard.php"><i class="fas fa-home"></i> Home</a>
    <a href="vendors.php" ><i class="fas fa-store"></i> Vendors</a>
    <a href="orders.php"><i class="fas fa-box"></i> Orders</a>
          <a href="users.php"><i class="fas fa-users"></i> Users</a>
    <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="content" id="content">
<?php include '../includes/header.php'; ?>

<h3 class="text-white mb-4">Analytics</h3>

<div class="row g-3 mb-4">

<div class="col-md-3">
<div class="card text-center p-3">
<h6>Total Orders</h6>
<h2 id="orders"><?= $total_orders ?></h2>
</div>
</div>

<div class="col-md-3">
<div class="card text-center p-3">
<h6>Revenue</h6>
<h2 id="revenue">KES <?= number_format($total_revenue) ?></h2>
</div>
</div>

</div>

<div class="row g-4">

<!-- STATUS -->
<div class="col-md-4">
<div class="card p-3">
<h6>Status</h6>
<canvas id="statusChart"></canvas>
</div>
</div>

<!-- REVENUE -->
<div class="col-md-8">
<div class="card p-3">
<h6>
Revenue 
<button class="btn btn-sm btn-outline-dark" onclick="switchRevenue('daily')">Daily</button>
<button class="btn btn-sm btn-outline-dark" onclick="switchRevenue('monthly')">Monthly</button>
</h6>
<canvas id="revenueChart"></canvas>
</div>
</div>

</div>

<div class="row mt-4">

<!-- VENDOR PERFORMANCE -->
<div class="col-md-12">
<div class="card p-3">
<h6>Top Vendors</h6>
<canvas id="vendorChart"></canvas>
</div>
</div>

</div>

</div>

<script>
    // Sidebar toggle
$('#toggleSidebar').click(function(){
    $('#sidebar').toggleClass('collapsed');
    $('#content').toggleClass('expanded');
});

/* STATUS PIE */
new Chart(document.getElementById('statusChart'),{
type:'pie',
data:{
labels:['Pending','Preparing','Delivered'],
datasets:[{data:<?= json_encode($statusData) ?>}]
}
});

/* REVENUE CHART */
let revenueChart = new Chart(document.getElementById('revenueChart'),{
type:'line',
data:{
labels:<?= json_encode($rev_dates) ?>,
datasets:[{label:'Revenue',data:<?= json_encode($rev_values) ?>}]
}
});

/* SWITCH DAILY/MONTHLY */
function switchRevenue(type){

if(type=='daily'){
revenueChart.data.labels = <?= json_encode($rev_dates) ?>;
revenueChart.data.datasets[0].data = <?= json_encode($rev_values) ?>;
}else{
revenueChart.data.labels = <?= json_encode($months) ?>;
revenueChart.data.datasets[0].data = <?= json_encode($month_values) ?>;
}

revenueChart.update();
}

/* VENDOR BAR */
new Chart(document.getElementById('vendorChart'),{
type:'bar',
data:{
labels:<?= json_encode($v_names) ?>,
datasets:[{label:'Revenue',data:<?= json_encode($v_totals) ?>}]
}
});

/* REAL-TIME REFRESH */
setInterval(()=>{
$.get('analytics.php?ajax=1',function(data){
let d = JSON.parse(data);
$('#orders').text(d.orders);
$('#revenue').text('KES '+Number(d.revenue).toLocaleString());
});
},10000);

</script>

</body>
</html>
