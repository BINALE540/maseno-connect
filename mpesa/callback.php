<?php
include '../includes/db.php';

/* ================= GET RAW JSON ================= */
$data = file_get_contents("php://input");
$logFile = "logs/stk_log.json";

/* SAVE FULL RESPONSE */
file_put_contents($logFile, $data . PHP_EOL, FILE_APPEND);

$response = json_decode($data, true);

/* ================= VALIDATE ================= */
if (!isset($response['Body']['stkCallback'])) {
    http_response_code(200);
    exit;
}

$callback = $response['Body']['stkCallback'];

$resultCode = $callback['ResultCode'];
$resultDesc = $callback['ResultDesc'];
$checkoutID = $callback['CheckoutRequestID'];

/* ================= SUCCESS ================= */
if ($resultCode == 0) {

    $items = $callback['CallbackMetadata']['Item'];

    $amount = 0;
    $receipt = '';
    $phone = '';

    foreach ($items as $item) {
        if ($item['Name'] == 'Amount') {
            $amount = $item['Value'];
        }
        if ($item['Name'] == 'MpesaReceiptNumber') {
            $receipt = $item['Value'];
        }
        if ($item['Name'] == 'PhoneNumber') {
            $phone = $item['Value'];
        }
    }

    /* ===== UPDATE ORDERS ===== */
    $stmt = $conn->prepare("
        UPDATE orders 
        SET payment_status = 'Paid', mpesa_receipt = ? 
        WHERE checkout_request_id = ?
    ");
    $stmt->bind_param("ss", $receipt, $checkoutID);
    $stmt->execute();
    $stmt->close();

    /* ===== UPDATE PAYMENTS ===== */
    $stmt = $conn->prepare("
        UPDATE payments 
        SET 
            payment_status = 'Paid',
            mpesa_receipt = ?,
            phone = ?,
            amount = ?,
            result_code = 0,
            result_desc = 'Success'
        WHERE checkout_request_id = ?
    ");
    $stmt->bind_param("ssds", $receipt, $phone, $amount, $checkoutID);
    $stmt->execute();
    $stmt->close();

} else {

    /* ================= FAILED / CANCELLED ================= */

    $stmt = $conn->prepare("
        UPDATE payments 
        SET 
            payment_status = 'Failed',
            result_code = ?,
            result_desc = ?
        WHERE checkout_request_id = ?
    ");
    $stmt->bind_param("iss", $resultCode, $resultDesc, $checkoutID);
    $stmt->execute();
    $stmt->close();
}

/* ================= RESPONSE TO SAFARICOM ================= */
http_response_code(200);
echo json_encode([
    "ResultCode" => 0,
    "ResultDesc" => "Accepted"
]);