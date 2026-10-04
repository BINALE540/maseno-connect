<?php
require_once __DIR__ . '/token.php';

function stkPush($phone, $amount, $order_id) {

    if (!function_exists('getAccessToken')) {
        die("getAccessToken() NOT FOUND - token.php not loaded");
    }

    $access_token = getAccessToken();

    // Format phone
    $phone = preg_replace('/^0/', '254', $phone);

    if (empty($phone)) {
        return ["error" => "Phone number missing"];
    }

    $shortcode = "174379";

    // ✅ REPLACE WITH FULL PASSKEY
    $passkey = "bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919";

    $timestamp = date("YmdHis");
    $password = base64_encode($shortcode . $passkey . $timestamp);

    $url = "https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest";

    $payload = [
        "BusinessShortCode" => $shortcode,
        "Password" => $password,
        "Timestamp" => $timestamp,
        "TransactionType" => "CustomerPayBillOnline",
        "Amount" => (int)$amount,
        "PartyA" => $phone,
        "PartyB" => $shortcode,
        "PhoneNumber" => $phone,
        "CallBackURL" => "https://ryann-pseudoanatomic-melissia.ngrok-free.dev/maseno-connect/mpesa/callback.php",
        "AccountReference" => "ORDER_" . $order_id,
        "TransactionDesc" => "Food Payment"
    ];

    $curl = curl_init($url);

    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $access_token,
        "Content-Type: application/json"
    ]);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($curl);

    if (curl_errno($curl)) {
        return ["error" => curl_error($curl)];
    }

    return json_decode($response, true);
}