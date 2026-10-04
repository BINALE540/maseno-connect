<?php
session_start();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$food_id = intval($_POST['food_id'] ?? 0);
$quantity = intval($_POST['quantity'] ?? 1);

if ($food_id <= 0) {
    http_response_code(400);
    exit("Invalid item");
}

/* If item already exists → increase qty */
if (isset($_SESSION['cart'][$food_id])) {
    $_SESSION['cart'][$food_id]['quantity'] += $quantity;
} else {
    include '../includes/db.php';
$stmt = $conn->prepare("SELECT id, name, price, vendor_id FROM food_items WHERE id=?");
    $stmt->bind_param("i", $food_id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();

    if (!$item) {
        http_response_code(404);
        exit("Item not found");
    }

    $_SESSION['cart'][$food_id] = [
        'id' => $item['id'],
        'name' => $item['name'],
        'price' => $item['price'],
        'vendor_id' => $item['vendor_id'],
        'quantity' => $quantity
    ];
}

/* flag to auto-open cart */
$_SESSION['open_cart'] = true;

echo "success";
