<?php
include '../includes/db.php';

$name = $_POST['name'];
$email = $_POST['email'];
$phone = $_POST['phone'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
$role = $_POST['role'];

/* CHECK EMAIL */
$check = $conn->prepare("SELECT id FROM users WHERE email=?");
$check->bind_param("s",$email);
$check->execute();

if($check->get_result()->num_rows > 0){
    echo "exists";
    exit;
}

$stmt = $conn->prepare("
INSERT INTO users(name,email,phone_number,password,role)
VALUES (?,?,?,?,?)
");
$stmt->bind_param("sssss",$name,$email,$phone,$password,$role);
$stmt->execute();

echo "success";
