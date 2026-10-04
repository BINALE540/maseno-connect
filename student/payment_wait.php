<?php
$order_id = $_GET['order_id'] ?? 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Processing Payment</title>

    <link href="../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="../assets/bootstrap/js/jquery-3.6.0.min.js"></script>
    <script src="../assets/bootstrap/js/sweetalert2@11.js"></script>

    <style>
        body {
            background:#f8f9fa;
            display:flex;
            justify-content:center;
            align-items:center;
            height:100vh;
        }
        .card {
            text-align:center;
            padding:30px;
            border-radius:15px;
        }
    </style>
</head>

<body>

<div class="card shadow">
    <h4>📱 Waiting for Payment</h4>
    <p id="statusText">Check your phone and enter M-Pesa PIN</p>

    <div class="spinner-border text-success mt-3"></div>
</div>

<script>

let order_id = <?= $order_id ?>;
let attempts = 0;

function checkPayment() {

    attempts++;

    if (attempts > 20) {
        Swal.fire({
            icon: 'warning',
            title: 'Timeout',
            text: 'Payment not confirmed. Check orders page.'
        }).then(() => {
            window.location = 'orders.php';
        });
        return;
    }

    $.get('check_payment_status.php', {order_id: order_id}, function(res){

        let data = JSON.parse(res);

        if (data.payment_status === 'Paid') {

            Swal.fire({
                icon: 'success',
                title: 'Payment Confirmed',
                text: 'Your order is being prepared',
                timer: 2000,
                showConfirmButton: false
            });

            setTimeout(() => {
                window.location = 'orders.php';
            }, 2000);

        } else if (data.payment_status === 'Failed') {

            Swal.fire({
                icon: 'error',
                title: 'Payment Failed',
                text: 'Please try again'
            }).then(() => {
                window.location = 'cart.php';
            });

        }

    });
}

// RUN EVERY 3 SECONDS
setInterval(checkPayment, 3000);

</script>

</body>
</html>
