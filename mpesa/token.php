<?php

function getAccessToken() {

    $consumerKey = "hogE7ABqH8r5TBLdRLvuxSjAu8eDh3wbfxYR3eogxiGAbdsx";
    $consumerSecret = "JlMNa0NATgyihcAPBsCaixlqHH8HVwwIHiZv0NyOjH5mPxxchxZzFoOJk5FSzYaA";

    $credentials = base64_encode($consumerKey . ":" . $consumerSecret);

    $url = "https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials";

    $curl = curl_init();

curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

curl_setopt($curl, CURLOPT_HTTPHEADER, [
    "Authorization: Basic $credentials",
    "Content-Type: application/json"
]);

curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0");

curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($curl);

echo "<h3>RAW:</h3>";
var_dump($response);

echo "<h3>HTTP:</h3>";
var_dump(curl_getinfo($curl, CURLINFO_HTTP_CODE));

echo "<h3>ERROR:</h3>";
var_dump(curl_error($curl));

exit;
}