<?php
include 'config.php';

function generatePassword() {
    global $BusinessShortCode, $Passkey;

    $timestamp = date("YmdHis");

    $password = base64_encode($BusinessShortCode . $Passkey . $timestamp);

    return [
        "password" => $password,
        "timestamp" => $timestamp
    ];
}