<?php
session_start();
include '../includes/db.php';

if ($_SESSION['role'] != 'admin') exit;

$id = $_POST['id'];

/* GET USER ID */
$stmt = $conn->prepare("SELECT user_id FROM vendors WHERE id=?");
$stmt->bind_param("i",$id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if (!$res) exit;

$user_id = $res['user_id'];

/* DELETE USER → CASCADE deletes vendor */
$stmt = $conn->prepare("DELETE FROM users WHERE id=?");
$stmt->bind_param("i",$user_id);
$stmt->execute();

echo "deleted";
