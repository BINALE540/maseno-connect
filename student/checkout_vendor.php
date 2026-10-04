<?php
session_start();
include '../includes/db.php';

/* ========================
   SWEET ALERT
======================== */
function swal($type, $title, $text, $redirect = null) {
?>
<!DOCTYPE html>
<html>
<head>
<script src="../assets/bootstrap/js/sweetalert2@11.js"></script>
</head>
<body>
<script>
Swal.fire({
    icon: '<?= $type ?>',
    title: '<?= $title ?>',
    text: '<?= $text ?>'
}).then(() => {
    <?= $redirect ? "window.location='$redirect';" : "window.history.back();" ?>
});
</script>
</body>
</html>
<?php
exit;
}

/* ========================
   AUTH
======================== */
if (!isset($_SESSION['user_id'])) {
    swal('error','Login Required','Please login','../auth/login.php');
}

$user_id = $_SESSION['user_id'];

$location_json = $_POST['location_json'] ?? null;

/* ========================
   GET DATA
======================== */
$vendor_id = $_GET['vendor_id'] ?? 0;
$method    = $_GET['method'] ?? '';

if (!$vendor_id || !in_array($method, ['cod','mpesa'])) {
    swal('error','Error','Invalid request');
}

/* ========================
   CART FILTER
======================== */
$cart = $_SESSION['cart'] ?? [];
$items = array_filter($cart, fn($i) => $i['vendor_id'] == $vendor_id);

if (empty($items)) {
    swal('warning','Cart Empty','No items found','cart.php');
}

/* ========================
   TOTAL
======================== */
$total = 0;
foreach ($items as $item) {
    $total += $item['price'] * $item['quantity'];
}

/* ========================
   POST (MPESA)
======================== */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    /* ========================
       HANDLE COD FIRST (SAFE)
    ======================== */
    if ($method == 'cod') {

        // CREATE ORDER
        $stmt = $conn->prepare("
        INSERT INTO orders (student_id, vendor_id, total, status, payment_method, payment_status, pickup_location)
        VALUES (?, ?, ?, 'Pending', 'cod', 'Pending' , ?)
        ");
        $stmt->bind_param("iids", $user_id, $vendor_id, $total, $location_json);
        $stmt->execute();

        $order_id = $stmt->insert_id;

        // INSERT ITEMS
        foreach ($items as $item) {
            $food_id = $item['food_id'] ?? $item['id'];

            $stmt = $conn->prepare("
            INSERT INTO order_items (order_id, food_id, quantity, price)
            VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("iiid", $order_id, $food_id, $item['quantity'], $item['price']);
            $stmt->execute();
        }

        // PAYMENT
        $stmt = $conn->prepare("
        INSERT INTO payments (order_id, payment_method, payment_status, amount)
        VALUES (?, 'COD', 'Unpaid', ?)
        ");
        $stmt->bind_param("id", $order_id, $total);
        $stmt->execute();

        // CLEAR CART
        $_SESSION['cart'] = array_filter($cart, fn($i) => $i['vendor_id'] != $vendor_id);

        swal('success','Order Placed','Cash on Delivery','orders.php');
    }

    /* ========================
       HANDLE MPESA (VALIDATE FIRST)
    ======================== */
    if ($method == 'mpesa') {

        $phone = $_POST['phone'] ?? '';

        // 🚫 STOP EARLY (NO DB INSERT YET)
        if (!preg_match('/^2547\d{8}$/', $phone)) {
            swal('error','Invalid Phone','Use format: 2547XXXXXXXX');
        }

        /* TOKEN */
        $consumerKey = "heTX7EKbypGR9n0iEUBs41QDzUxrEzheiJ2p8J8vQMi5KASQ";
        $consumerSecret = "MLYlAPGyZp6o1aInurpSqkIImxRn5pSluWZSAnXNoVUkZ6eMEYf7nQHAhSQlroqJ";

        $credentials = base64_encode($consumerKey.":".$consumerSecret);

        $ch = curl_init("https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Basic $credentials"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = json_decode(curl_exec($ch));
        $access_token = $response->access_token ?? null;

        // 🚫 STOP IF TOKEN FAILS
        if (!$access_token) {
            swal('error','M-Pesa Error','Failed to get token');
        }

        /* ========================
           NOW SAFE → CREATE ORDER
        ======================== */
$stmt = $conn->prepare("
INSERT INTO orders (student_id, vendor_id, total, status, payment_method, payment_status, pickup_location)
VALUES (?, ?, ?, 'Pending', 'mpesa', 'Pending', ?)
");
$stmt->bind_param("iids", $user_id, $vendor_id, $total, $location_json);

        $stmt->execute();

        $order_id = $stmt->insert_id;

        // INSERT ITEMS
        foreach ($items as $item) {
            $food_id = $item['food_id'] ?? $item['id'];

            $stmt = $conn->prepare("
            INSERT INTO order_items (order_id, food_id, quantity, price)
            VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("iiid", $order_id, $food_id, $item['quantity'], $item['price']);
            $stmt->execute();
        }

        /* STK */
        date_default_timezone_set('Africa/Nairobi');
        $timestamp = date("YmdHis");

        $shortcode = "174379";
        $passkey   = "bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919";

        $password = base64_encode($shortcode.$passkey.$timestamp);

        $stk = [
            "BusinessShortCode"=>$shortcode,
            "Password"=>$password,
            "Timestamp"=>$timestamp,
            "TransactionType"=>"CustomerPayBillOnline",
            "Amount"=>$total,
            "PartyA"=>$phone,
            "PartyB"=>$shortcode,
            "PhoneNumber"=>$phone,
            "CallBackURL"=>"https://ryann-pseudoanatomic-melissia.ngrok-free.dev/maseno-connect/student/mpesa_callback.php",
            "AccountReference"=>"Order-$order_id",
            "TransactionDesc"=>"Food Payment"
        ];

        $ch = curl_init("https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $access_token",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($stk));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = json_decode(curl_exec($ch), true);

        // 🚫 STOP IF STK FAILS
        if (!isset($response['CheckoutRequestID'])) {
            swal('error','Payment Error','STK Push Failed');
        }

        $checkout_id = $response['CheckoutRequestID'];

        /* SAVE PAYMENT */
$payment_method = 'MPESA';
$payment_status = 'Pending';

$stmt = $conn->prepare("
    INSERT INTO payments 
    (order_id, payment_method, payment_status, phone, amount, checkout_request_id)
    VALUES (?, ?, ?, ?, ?, ?)
");

// Types: 
// i = integer ($order_id)
// s = string  ($payment_method)
// s = string  ($payment_status)
// s = string  ($phone)
// d = double  ($total)
// s = string  ($checkout_id)
$stmt->bind_param("isssds", $order_id, $payment_method, $payment_status, $phone, $total, $checkout_id);
$stmt->execute();

        /* UPDATE ORDER */
        $stmt = $conn->prepare("
        UPDATE orders SET checkout_request_id=?, mpesa_phone=? WHERE id=?
        ");
        $stmt->bind_param("ssi", $checkout_id, $phone, $order_id);
        $stmt->execute();

        swal('info','STK Sent','Check your phone',"payment_wait.php?order_id=".$order_id);
    }
}


?>

<!DOCTYPE html>
<html>
<head>
<title>M-Pesa Payment</title>

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

<body class="bg-light">
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


<div class="container py-5">
    <div class="card p-4 shadow">
        <div class="mb-3">
    <strong><i class="fas fa-location"></i> Live Location Details:</strong>
    <div id="locationDisplay">Detecting location...<br><small> <b><i>For Live Feeds Location kindly ensure Internet Connection is Enaabled and Location Access Allowed</i></b></small></div>
</div>

 <form method="POST">

<input type="hidden" name="location_json" id="location_json">

<?php if ($method == 'mpesa'): ?>
<input type="text" name="phone" class="form-control mb-3" placeholder="2547XXXXXXXX" required>
<?php endif; ?>

<button id="payBtn" class="btn btn-success w-100" >
    Pay KES <?= number_format($total,2) ?>
</button>

</form>

    </div>
</div>
</div>
<script>/* SIDEBAR TOGGLE (GLOBAL STYLE) */
$('#toggleSidebar').click(function(){
    $('#sidebar').toggleClass('collapsed');
    $('#content').toggleClass('expanded');
});
</script>

<script>
function fetchLocation() {
    if (!navigator.geolocation) {
        document.getElementById("locationDisplay").innerHTML = "Geolocation not supported";
        return;
    }

navigator.geolocation.getCurrentPosition(
    async function(position) {
        console.log("SUCCESS:", position);

        let lat = position.coords.latitude;
        let lng = position.coords.longitude;

        let locationName = "Resolving location...";

        document.getElementById("locationDisplay").innerHTML =
            `<i class="fas fa-location-arrow"></i> Latitudes: ${lat} <br> <i class="fas fa-location-arrow"></i> Longitudes: ${lng} <br> ${locationName}`;

        try {
            let res = await fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`);

            if (!res.ok) throw new Error("Network error");

            let data = await res.json();
            locationName = data.display_name || "Unknown location";

        } catch (e) {
            locationName = "⚠️ No internet. Unable to resolve location name";
        }

        document.getElementById("locationDisplay").innerHTML =
            `<i class="fas fa-location-arrow"></i> Latitudes: ${lat} <br> <i class="fas fa-location-arrow"></i> Longitudes: ${lng} <br> <i class="fas fa-tags"></i>:${locationName}`;

        let locationJSON = JSON.stringify({ lat, lng, name: locationName });
        document.getElementById("location_json").value = locationJSON;

    },
    function(error) {
        console.error("ERROR:", error);

        document.getElementById("locationDisplay").innerHTML =
            `❌ Location error: ${error.message}`;
    }
);

}

window.onload = fetchLocation;

</script>

</body>
</html>
