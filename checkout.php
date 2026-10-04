<?php
session_start();
include 'includes/db.php';
include 'mpesa/stkpush.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$payment_method = $_POST['payment_method'] ?? 'COD';
$mpesa_phone = $_POST['mpesa_phone'] ?? null;
$student_phone = $_POST['student_phone'] ?? null;
$pickup_location = $_POST['pickup_location'] ?? null;

/* GET CART ITEMS */
$cart = $conn->query("
SELECT f.id, f.price, c.quantity, f.vendor_id 
FROM cart c
JOIN food_items f ON c.food_id = f.id
WHERE c.user_id = $user_id
");

if ($cart->num_rows == 0) {
    die("Cart is empty!");
}

$total = 0;
$vendor_id = null;

while ($item = $cart->fetch_assoc()) {
    $total += $item['price'] * $item['quantity'];

    if ($vendor_id === null) {
        $vendor_id = $item['vendor_id'];
    }
}

/* ✅ PUT IT RIGHT HERE */
if ($vendor_id === null) {
    die("ERROR: Vendor not found. Cart issue.");
}

/* THEN CONTINUE */
$stmt = $conn->prepare("
INSERT INTO orders 
(student_id, vendor_id, total, student_phone, pickup_location, payment_method, payment_status, mpesa_phone) 
VALUES (?, ?, ?, ?, ?, ?, 'Pending', ?)
");

$stmt->bind_param("iidssss", $user_id, $vendor_id, $total, $student_phone, $pickup_location, $payment_method, $mpesa_phone);
$stmt->execute();

$order_id = $stmt->insert_id;

/* INSERT ORDER ITEMS */
$cart = $conn->query("
SELECT f.id, f.price, c.quantity 
FROM cart c
JOIN food_items f ON c.food_id = f.id
WHERE c.user_id = $user_id
");

while ($item = $cart->fetch_assoc()) {
    $stmt = $conn->prepare("
    INSERT INTO order_items (order_id, food_id, quantity, price)
    VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("iiid", $order_id, $item['id'], $item['quantity'], $item['price']);
    $stmt->execute();
}

/* PAYMENT */
if ($payment_method === 'MPESA') {

    if (empty($mpesa_phone)) {
        die("Enter M-Pesa phone number");
    }

    $phone = $mpesa_phone;

    $response = stkPush($phone, $total, $order_id);

    if (isset($response['ResponseCode']) && $response['ResponseCode'] == "0") {

        $checkout_id = $response['CheckoutRequestID'];

        // ✅ SAVE TO ORDERS
        $stmt = $conn->prepare("
            UPDATE orders 
            SET checkout_request_id=? 
            WHERE id=?
        ");
        $stmt->bind_param("si", $checkout_id, $order_id);
        $stmt->execute();

        // ✅ INSERT INTO PAYMENTS (VERY IMPORTANT)
        $stmt = $conn->prepare("
            INSERT INTO payments (order_id, checkout_request_id, payment_status)
            VALUES (?, ?, 'Pending')
        ");
        $stmt->bind_param("is", $order_id, $checkout_id);
        $stmt->execute();

        echo "STK Push sent! Check your phone.";

    } else {
        echo "<pre>";
        print_r($response);
        echo "</pre>";
        exit();
    }
} else {
    echo "Order placed successfully (Cash on Pickup)";
}

/* CLEAR CART */
$conn->query("DELETE FROM cart WHERE user_id = $user_id");
?>