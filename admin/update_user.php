<?php
include '../includes/db.php';

$id = $_POST['id'];
$name = $_POST['name'];
$email = $_POST['email'];
$phone = $_POST['phone'];
$role = $_POST['role'];

$stmt = $conn->prepare("
UPDATE users SET name=?,email=?,phone_number=?,role=? WHERE id=?
");
$stmt->bind_param("ssssi",$name,$email,$phone,$role,$id);
$stmt->execute();

echo "updated";
