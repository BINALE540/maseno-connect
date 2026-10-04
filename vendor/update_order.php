<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'vendor') {
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $order_id = intval($_POST['order_id']);
    $status   = $_POST['status'];

    /* STEP 1: GET PAYMENT METHOD */
    $stmt = $conn->prepare("SELECT payment_method FROM orders WHERE id=?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if (!$result) {
        echo "error";
        exit;
    }

    $payment_method = $result['payment_method'];

    /* STEP 2: DETERMINE PAYMENT STATUS */
    $payment_status = null;

    if ($payment_method == 'COD' && $status == 'Delivered') {
        $payment_status = 'Paid';
    }

    /* STEP 3: UPDATE ORDER */
    if ($payment_status) {

        // Update BOTH status and payment_status
        $update = $conn->prepare("
            UPDATE orders 
            SET status=?, payment_status=? 
            WHERE id=?
        ");
        $update->bind_param("ssi", $status, $payment_status, $order_id);

    } else {

        // Update ONLY status
        $update = $conn->prepare("
            UPDATE orders 
            SET status=? 
            WHERE id=?
        ");
        $update->bind_param("si", $status, $order_id);
    }

    if ($update->execute()) {
        echo "success";
    } else {
        echo "error";
    }
}
?>
