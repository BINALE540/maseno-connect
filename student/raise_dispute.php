<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$id = $_GET['id'];

$stmt = $conn->prepare("
UPDATE orders SET dispute_status = 'disputed'
WHERE id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: orders.php");
exit();
