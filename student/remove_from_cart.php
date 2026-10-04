<?php
session_start();

$id = intval($_POST['id']);

if (isset($_SESSION['cart'][$id])) {
    unset($_SESSION['cart'][$id]);
}

echo "removed";
