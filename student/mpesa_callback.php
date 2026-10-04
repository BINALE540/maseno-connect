<?php
include '../includes/db.php';

// ========================
// READ RAW JSON FROM SAFARICOM
// ========================
$data = file_get_contents("php://input");

// OPTIONAL: LOG CALLBACK (VERY IMPORTANT)
file_put_contents("mpesa_log.txt", $data . PHP_EOL, FILE_APPEND);

// Decode JSON
$response = json_decode($data, true);

// Safety check
if (!isset($response['Body']['stkCallback'])) {
    http_response_code(400);
    exit;
}

$callback = $response['Body']['stkCallback'];

$checkout_id = $callback['CheckoutRequestID'] ?? '';
$result_code = $callback['ResultCode'] ?? 1;
$result_desc = $callback['ResultDesc'] ?? '';

// ========================
// HANDLE SUCCESS
// ========================
if ($result_code == 0) {

    $metadata = $callback['CallbackMetadata']['Item'] ?? [];

    $receipt = '';
    $amount  = 0;
    $phone   = '';

    foreach ($metadata as $item) {

        if ($item['Name'] == 'MpesaReceiptNumber') {
            $receipt = $item['Value'];
        }

        if ($item['Name'] == 'Amount') {
            $amount = $item['Value'];
        }

        if ($item['Name'] == 'PhoneNumber') {
            $phone = $item['Value'];
        }
    }

    // ========================
    // UPDATE PAYMENTS TABLE
    // ========================
    $stmt = $conn->prepare("
        UPDATE payments 
        SET payment_status='Paid',
            mpesa_receipt=?,
            result_code=0,
            result_desc=?,
            phone=?,
            amount=?
        WHERE checkout_request_id=?
    ");

    $stmt->bind_param("sssds", $receipt, $result_desc, $phone, $amount, $checkout_id);
    $stmt->execute();

    // ========================
    // UPDATE ORDERS TABLE
    // ========================
    $stmt = $conn->prepare("
        UPDATE orders 
        SET payment_status='Paid',
            mpesa_receipt=?
        WHERE checkout_request_id=?
    ");

    $stmt->bind_param("ss", $receipt, $checkout_id);
    $stmt->execute();

} else {

    // ========================
    // HANDLE FAILURE
    // ========================
    $stmt = $conn->prepare("
        UPDATE payments 
        SET payment_status='Failed',
            result_code=?,
            result_desc=?
        WHERE checkout_request_id=?
    ");

    $stmt->bind_param("iss", $result_code, $result_desc, $checkout_id);
    $stmt->execute();

    // UPDATE ORDER TOO
    $stmt = $conn->prepare("
        UPDATE orders 
        SET payment_status='Failed'
        WHERE checkout_request_id=?
    ");

    $stmt->bind_param("s", $checkout_id);
    $stmt->execute();
}

// ========================
// RESPONSE TO SAFARICOM
// ========================
echo json_encode([
    "ResultCode" => 0,
    "ResultDesc" => "Accepted"
]);
