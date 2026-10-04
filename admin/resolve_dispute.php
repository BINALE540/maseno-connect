<?php
session_start();
include '../includes/db.php';

if ($_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$id = $_GET['id'];

$stmt = $conn->prepare("
UPDATE orders 
SET dispute_status='resolved', admin_note='Reviewed and resolved by admin'
WHERE id=?
");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: disputes.php");
exit();
