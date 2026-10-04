<?php
session_start();
include '../includes/db.php';
//cart.php
$cart = $_SESSION['cart'] ?? [];
$total = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Cart</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Bootstrap -->
<link href="../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="../fontawesome/css/all.min.css" rel="stylesheet">

<!-- JS -->
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

/* CART BOX */
.cart-box {
    background:#fff;
    border-radius:15px;
    padding:20px;
}

/* TABLE SCROLL (optional) */
.table-responsive {
    max-height:400px;
    overflow-y:auto;
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
    <a href="dashboard.php"><i class="fas fa-home"></i> Home</a>
    <a href="orders.php"><i class="fas fa-box"></i> Orders</a>
  
    <a href="cart.php" class="active"><i class="fas fa-shopping-cart"></i> Cart</a>
    <a href="#"><i class="fas fa-user"></i> <span class="text">Profile</span></a>
    <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<!-- CONTENT -->
<div class="content" id="content">

<!-- HEADER -->
<div class="mb-3">
    <?php include '../includes/header.php'; ?>
</div>

<div class="container py-4">

<h3 class="text-white mb-4">🛒 My Cart</h3>

<div class="cart-box shadow-sm">

<?php if (empty($cart)) { ?>

    <div class="text-center py-5">
        <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
        <h5>Cart is empty</h5>
    </div>

<?php } else { ?>

<div class="table-responsive">
<?php
$grouped = [];

foreach ($cart as $item) {

    $vendor_id = $item['vendor_id'] ?? 0;

    if (!isset($grouped[$vendor_id])) {
        $grouped[$vendor_id] = [
            'vendor_name' => 'Unknown',
            'items' => [],
            'total' => 0
        ];
    }

    // get vendor name once
    if ($grouped[$vendor_id]['vendor_name'] === 'Unknown' && $vendor_id) {
        $v = $conn->prepare("SELECT business_name FROM vendors WHERE id=?");
        $v->bind_param("i", $vendor_id);
        $v->execute();
        $res = $v->get_result()->fetch_assoc();
        $grouped[$vendor_id]['vendor_name'] = $res['business_name'] ?? 'Unknown';
    }

    $grouped[$vendor_id]['items'][] = $item;
    $grouped[$vendor_id]['total'] += $item['price'] * $item['quantity'];
}
?>

<?php foreach ($grouped as $vendor_id => $vendor): ?>

<div class="mb-4 p-3 rounded bg-light">

<!-- VENDOR HEADER -->
<h5 class="mb-3">
🏪 <?= htmlspecialchars($vendor['vendor_name']) ?>
</h5>

<table class="table align-middle">
<thead>
<tr>
<th>Item</th>
<th>Qty</th>
<th>Price</th>
<th>Total</th>
<th>Action</th>
</tr>
</thead>

<tbody>

<?php foreach ($vendor['items'] as $item): 
$subtotal = $item['price'] * $item['quantity'];
$total += $subtotal;
?>

<tr>

<td><?= htmlspecialchars($item['name']) ?></td>

<td><?= $item['quantity'] ?></td>

<td>KES <?= number_format($item['price'],2) ?></td>

<td>KES <?= number_format($subtotal,2) ?></td>

<td>
<button class="btn btn-danger btn-sm remove"
data-id="<?= $item['id'] ?>">
<i class="fas fa-trash"></i>
</button>
</td>

</tr>

<?php endforeach; ?>

</tbody>
</table>

<!-- VENDOR TOTAL -->
<div class="text-end">
<strong>
Vendor Total: KES <?= number_format($vendor['total'],2) ?>
</strong>
</div>

<!-- CHECKOUT PER VENDOR -->
<button class="btn btn-warning w-100 mt-2 checkout-vendor"
data-vendor="<?= $vendor_id ?>">
Checkout This Vendor
</button>

</div>

<?php endforeach; ?>


</div>

<hr>

<hr>

<h4 class="text-end">
Grand Total: <span class="text-success">
KES <?= number_format($total,2) ?>
</span>
</h4>


<!-- CHECKOUT 
<button class="btn btn-success w-100 mt-3 checkout">
<i class="fas fa-credit-card"></i> Proceed to Checkout(All Vendors)
</button>
-->
<?php } ?>

</div>

</div>
</div>

<!-- JS -->
<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>

<script>

/* REMOVE ITEM */
$('.remove').click(function(){
    let id = $(this).data('id');

    $.post('remove_from_cart.php', {id:id}, function(){
        Swal.fire({
            icon:'success',
            title:'Removed',
            timer:1000,
            showConfirmButton:false
        }).then(() => location.reload());
    });
});

/* CHECKOUT */
$('.checkout').click(function(){

    Swal.fire({
        title: 'Choose Payment Method',
        icon: 'question',
        showDenyButton: true,
        showCancelButton: true,
        confirmButtonText: 'MPESA',
        denyButtonText: 'Cash on Delivery'
    }).then((result) => {

        if (result.isConfirmed) {
            window.location = 'checkout_mpesa.php';

        } else if (result.isDenied) {
            window.location = 'checkout_vendor.php';
        }

    });

});

/* SIDEBAR TOGGLE (GLOBAL STYLE) */
$('#toggleSidebar').click(function(){
    $('#sidebar').toggleClass('collapsed');
    $('#content').toggleClass('expanded');
});



$('.checkout-vendor').click(function(){

    let vendor_id = $(this).data('vendor');

    Swal.fire({
        title: 'Choose Payment Method',
        text: 'Checkout this vendor only',
        icon: 'question',
        showDenyButton: true,
        showCancelButton: true,
        confirmButtonText: 'MPESA',
        denyButtonText: 'Cash on Delivery'
    }).then((result) => {

        if (result.isConfirmed) {
            // MPESA
            window.location = 'checkout_vendor.php?vendor_id=' 
                              + vendor_id + '&method=mpesa';

        } else if (result.isDenied) {
            // COD
            window.location = 'checkout_vendor.php?vendor_id=' 
                              + vendor_id + '&method=cod';
        }

    });

});


</script>

</body>
</html>
