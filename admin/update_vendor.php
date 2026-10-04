<?php
session_start();
include '../includes/db.php';

if ($_SESSION['role'] != 'admin') exit;

$id     = $_POST['id'] ?? null;
$status = $_POST['status'] ?? null;
$name   = $_POST['name'] ?? null;
$email  = $_POST['email'] ?? null;
$phone  = $_POST['phone'] ?? null;

/* GET USER ID */
$stmt = $conn->prepare("SELECT user_id FROM vendors WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if (!$res) exit;

$user_id = $res['user_id'];

/* UPDATE STATUS */
if ($status) {
    $stmt = $conn->prepare("UPDATE vendors SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
}

/* UPDATE USER DETAILS */
if ($name || $email || $phone) {

    $stmt = $conn->prepare("
        UPDATE users 
        SET name=?, email=?, phone_number=? 
        WHERE id=?
    ");
    $stmt->bind_param("sssi", $name, $email, $phone, $user_id);
    $stmt->execute();
}

echo "success";
