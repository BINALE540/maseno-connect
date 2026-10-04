<?php
include 'config.php';
//access_token.php
function getAccessToken() {
    global $consumerKey, $consumerSecret, $base_url;

    $credentials = base64_encode($consumerKey . ":" . $consumerSecret);

    $ch = curl_init($base_url . "/oauth/v1/generate?grant_type=client_credentials");
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Basic $credentials"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $data = json_decode($response);

    return $data->access_token;
}