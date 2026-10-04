<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_GET['vendor_id'])) {
    die("Vendor not specified");
}

$vendor_id = intval($_GET['vendor_id']);

$v = $conn->prepare("SELECT business_name FROM vendors WHERE id=?");
$v->bind_param("i",$vendor_id);
$v->execute();
$vendor = $v->get_result()->fetch_assoc();

$stmt = $conn->prepare("
SELECT id,name,price,image
FROM food_items
WHERE vendor_id=?
");
$stmt->bind_param("i",$vendor_id);
$stmt->execute();
$foods = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Menu</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

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

/* Food Card */
.food-card {
    background:#fff;
    border-radius:18px;
    padding:15px;
    text-align:center;
    transition:0.3s;
}
.food-card:hover {
    transform:translateY(-5px);
    box-shadow:0 10px 25px rgba(0,0,0,0.2);
}

.food-img {
    height:160px;
    object-fit:cover;
    border-radius:12px;
}

/* Quantity input */
.qty-box {
    display:flex;
    justify-content:center;
    align-items:center;
    gap:10px;
}
.qty-box input {
    width:60px;
    text-align:center;
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
    <a href="dashboard.php"><i class="fas fa-home"></i> Home</a>
    <a href="orders.php"><i class="fas fa-box"></i> Orders</a>
      <a href="cart.php"><i class="fas fa-shopping-cart"></i> Cart</a>
    <a href="#" class="active"><i class="fas fa-utensils"></i> Menu</a>
    <a href="#"><i class="fas fa-user"></i> <span class="text">Profile</span></a>
    <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<!-- CONTENT -->
<div class="content" id="content">

<?php include '../includes/header.php'; ?>

<h4 class="text-white mb-4">
🍽️ Menu — <?= htmlspecialchars($vendor['business_name']) ?>
</h4>

<div class="row g-4">

<?php while($row=$foods->fetch_assoc()) { ?>

<div class="col-md-4">

<div class="food-card">

<!-- IMAGE -->
<img src="<?= $row['image'] ? '../uploads/foods/'.$row['image'] : '../assets/images/food-bg.jpg' ?>"
     class="img-fluid food-img mb-3">

<h5><?= htmlspecialchars($row['name']) ?></h5>

<p class="fw-bold text-primary">
KES <?= number_format($row['price'],2) ?>
</p>

<!-- QUANTITY -->
<div class="qty-box mb-2">
    <button class="btn btn-sm btn-outline-secondary minus">-</button>
    <input type="number" class="form-control qty" value="1" min="1">
    <button class="btn btn-sm btn-outline-secondary plus">+</button>
</div>

<!-- ADD TO CART -->
<button 
 class="btn btn-warning w-100 add-cart"
 data-id="<?= $row['id'] ?>"
>
<i class="fas fa-cart-plus"></i> Add to Cart
</button>

</div>

</div>

<?php } ?>

</div>

</div>

<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>

<script>
// Sidebar toggle
$('#toggleSidebar').click(function() {
    $('#sidebar').toggleClass('collapsed');
    $('#content').toggleClass('expanded');
});

// Quantity buttons
$('.plus').click(function(){
    let input = $(this).siblings('.qty');
    input.val(parseInt(input.val()) + 1);
});

$('.minus').click(function(){
    let input = $(this).siblings('.qty');
    let val = parseInt(input.val());
    if(val > 1) input.val(val - 1);
});
$('.add-cart').click(function(){

    let btn = $(this);
    let foodId = btn.data('id');
    let qty = btn.closest('.food-card').find('.qty').val();

    $.post('add_to_cart.php', {
        food_id: foodId,
        quantity: qty
    }, function(){

Swal.fire({
    icon: 'success',
    title: 'Added to cart',
    showCancelButton: true,
    confirmButtonText: 'Go to Cart',
    cancelButtonText: 'Continue Shopping'
}).then((result) => {
    if (result.isConfirmed) {
        window.location.href = 'cart.php';
    }
});


    }).fail(function(){

        Swal.fire('Error', 'Failed to add to cart', 'error');

    });

});


</script>

</body>
</html>
