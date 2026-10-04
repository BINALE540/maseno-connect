<?php
session_start();
include '../includes/db.php';

if ($_SESSION['role'] != 'admin') exit;

$name     = trim($_POST['name']);
$email    = trim($_POST['email']);
$phone    = trim($_POST['phone']);
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

/* CHECK DUPLICATE EMAIL */
$check = $conn->prepare("SELECT id FROM users WHERE email=?");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo "exists";
    exit;
}

/* START TRANSACTION */
$conn->begin_transaction();

try {

    /* INSERT USER */
    $stmt = $conn->prepare("
        INSERT INTO users (name,email,phone_number,password,role)
        VALUES (?,?,?,?, 'vendor')
    ");
    $stmt->bind_param("ssss", $name, $email, $phone, $password);
    $stmt->execute();

    $user_id = $stmt->insert_id;

    /* INSERT VENDOR */
    $stmt2 = $conn->prepare("
        INSERT INTO vendors (user_id,business_name,status)
        VALUES (?, ?, 'pending')
    ");
    $stmt2->bind_param("is", $user_id, $name);
    $stmt2->execute();

    $conn->commit();

    echo "success";

} catch (Exception $e) {
    $conn->rollback();
    echo "error";
}

