<?php
session_start();
include '../includes/db.php';

/* ================= AUTH ================= */
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: menu.php");
    exit();
}

$student_id = $_SESSION['user_id'];

/* ================= INPUT ================= */
$food_id = (int)($_POST['food_id'] ?? 0);
$vendor_id = (int)($_POST['vendor_id'] ?? 0);
$pickup_location = trim($_POST['pickup_location'] ?? '');
$payment_method = $_POST['payment_method'] ?? '';
$qty = 1;

if (!$food_id || !$vendor_id || !$pickup_location || !$payment_method) {
    die("Invalid order data.");
}

/* ================= GET PRICE ================= */
$stmt = $conn->prepare("SELECT price FROM food_items WHERE id = ?");
$stmt->bind_param("i", $food_id);
$stmt->execute();
$stmt->bind_result($price);
$stmt->fetch();
$stmt->close();

$total = $price * $qty;

/* ================= M-PESA CONFIG ================= */
$consumerKey = "heTX7EKbypGR9n0iEUBs41QDzUxrEzheiJ2p8J8vQMi5KASQ";
$consumerSecret = "MLYlAPGyZp6o1aInurpSqkIImxRn5pSluWZSAnXNoVUkZ6eMEYf7nQHAhSQlroqJ";
$BusinessShortCode = "174379";
$Passkey = "bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919";
$CallBackURL = "https://ryann-pseudoanatomic-melissia.ngrok-free.dev/maseno-connect/mpesa/callback.php";

$base_url = "https://sandbox.safaricom.co.ke";

/* ================= DEFAULT ================= */
$mpesa_phone = NULL;
$checkoutRequestID = NULL;
$payment_status = 'Pending';

/* ================= M-PESA ================= */
if ($payment_method === 'MPESA') {

    if (empty($_POST['mpesa_phone'])) {
        die("M-Pesa phone required");
    }

    $phone = preg_replace('/^0/', '254', $_POST['mpesa_phone']);
    $amount = $total;

    /* ===== ACCESS TOKEN ===== */
    $credentials = base64_encode($consumerKey . ":" . $consumerSecret);

    $ch = curl_init($base_url . "/oauth/v1/generate?grant_type=client_credentials");
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Basic $credentials"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    // 🔥 DEBUG RESPONSE
    file_put_contents("../mpesa_logs/token_log.json", $response . "\n", FILE_APPEND);

    if (!$response) {
        die("Curl Error: " . curl_error($ch));
    }

    $tokenData = json_decode($response);

    if (!isset($tokenData->access_token)) {
        die("Access Token Error: " . $response);
    }

    $access_token = $tokenData->access_token;

    /* ===== PASSWORD ===== */
    $timestamp = date("YmdHis");
    $password = base64_encode($BusinessShortCode . $Passkey . $timestamp);

    /* ===== STK PUSH ===== */
    $stk_url = $base_url . "/mpesa/stkpush/v1/processrequest";

    $stkData = [
        "BusinessShortCode" => $BusinessShortCode,
        "Password" => $password,
        "Timestamp" => $timestamp,
        "TransactionType" => "CustomerPayBillOnline",
        "Amount" => $amount,
        "PartyA" => $phone,
        "PartyB" => $BusinessShortCode,
        "PhoneNumber" => $phone,
        "CallBackURL" => $CallBackURL,
        "AccountReference" => "MasenoConnect",
        "TransactionDesc" => "Food Payment"
    ];

    $ch = curl_init($stk_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer $access_token"
    ]);

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($stkData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $stkResponse = curl_exec($ch);

    file_put_contents("../mpesa_logs/stk_response.json", $stkResponse . "\n", FILE_APPEND);

    $stkResult = json_decode($stkResponse, true);

    if (isset($stkResult['CheckoutRequestID'])) {
        $checkoutRequestID = $stkResult['CheckoutRequestID'];
        $mpesa_phone = $phone;
    } else {
        die("STK Error: " . $stkResponse);
    }
}



/* ================= SAVE ORDER ================= */
$stmt = $conn->prepare("
INSERT INTO orders 
(student_id, vendor_id, total, status, pickup_location, payment_method, mpesa_phone, checkout_request_id, payment_status)
VALUES (?, ?, ?, 'Pending', ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "iidsssss",
    $student_id,
    $vendor_id,
    $total,
    $pickup_location,
    $payment_method,
    $mpesa_phone,
    $checkoutRequestID,
    $payment_status
);

$stmt->execute();

$order_id = $stmt->insert_id;
$stmt->close();

/* ================= SAVE ITEM ================= */
$stmt = $conn->prepare("
INSERT INTO order_items (order_id, food_id, quantity, price)
VALUES (?, ?, ?, ?)
");

$stmt->bind_param("iiid", $order_id, $food_id, $qty, $price);
$stmt->execute();
$stmt->close();

/* SAVE PAYMENT RECORD */
$stmt = $conn->prepare("
INSERT INTO payments 
(order_id, payment_method, payment_status, checkout_request_id, phone, amount)
VALUES (?, ?, 'Unpaid', ?, ?, ?)
");

$stmt->bind_param(
    "isssd",
    $order_id,
    $payment_method,
    $checkoutRequestID,
    $mpesa_phone,
    $total
);

$stmt->execute();
$stmt->close();

/* THEN redirect */
header("Location: orders.php");
exit();

?>