<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'vendor') {
    header("Location: ../auth/login.php");
    exit();
}

$vendor_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $order_id = intval($_POST['order_id']);
    $status = $_POST['status'];

    // Ensure order belongs to vendor
    $check = $conn->prepare("SELECT id FROM orders WHERE id=? AND vendor_id=?");
    $check->bind_param("ii", $order_id, $vendor_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows == 0) {
        die("Unauthorized");
    }

    $update = $conn->prepare("UPDATE orders SET status=? WHERE id=?");
    $update->bind_param("si", $status, $order_id);
    $update->execute();

    header("Location: order_details.php?id=".$order_id);
}
?>