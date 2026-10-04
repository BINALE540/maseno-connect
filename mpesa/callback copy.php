<?php

$data = file_get_contents("php://input");
$logFile = "logs/stk_log.json";

// Save full response
file_put_contents($logFile, $data . PHP_EOL, FILE_APPEND);

$response = json_decode($data, true);

/*
Here you should:
1. Extract CheckoutRequestID
2. Match with your orders table
3. Update payment_status = 'Paid'
*/

http_response_code(200);
echo json_encode(["ResultCode" => 0, "ResultDesc" => "Accepted"]);