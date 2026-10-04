<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include '../includes/db.php';

$error = "";

/* LOGIN LOGIC */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare(
        "SELECT id, name, password, role FROM users WHERE email = ?"
    );
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role']    = $user['role'];
            $_SESSION['name']    = $user['name'];

            if ($user['role'] === 'admin') {
                header("Location: ../admin/dashboard.php");
            } elseif ($user['role'] === 'vendor') {
                header("Location: ../vendor/dashboard.php");
            } else {
                header("Location: ../student/dashboard.php");
            }
            exit();
        } else {
            $error = "Invalid email or password";
        }
    } else {
        $error = "Invalid email or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Maseno Foods Hub</title>

<link href="../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;600&family=Pacifico&display=swap" rel="stylesheet">

<style>
body {
    font-family: 'Poppins', sans-serif;
    background:
      linear-gradient(rgba(0,0,0,0.8), rgba(0,0,0,0.8)),
      url('../assets/images/food-bg.jpg') center/cover no-repeat;
}

/* NAVBAR */
.navbar {
    padding: 15px;
}

/* HERO */
.hero {
    color: white;
    padding: 120px 0 40px;
}

.hero h1 {
    font-size: 42px;
}

.hero p {
    color: #ccc;
}

/* BUTTONS */
.btn-custom {
    border-radius: 30px;
    padding: 10px 25px;
}

.btn-register {
    background: #ff5722;
    color: white;
}

/* VENDORS */
.vendor-section {
    background: #fff;
    padding: 60px 0;
}

.vendor-title {
    font-family: 'Pacifico', cursive;
    font-size: 22px;
}

.palmers { color: #28a745; }
.silver { color: #6c757d; }
.oxygen { color: #ff5722; }

.food-card {
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 15px;
    transition: 0.3s;
    cursor: pointer;
}

.food-card img {
    height: 160px;
    width: 100%;
    object-fit: cover;
}

.food-card:hover {
    transform: scale(1.05);
}

.price {
    color: #28a745;
    font-weight: bold;
}

/* LOGIN */
.login-card {
    background: #fff;
    border-radius: 15px;
    padding: 30px;
}
</style>
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar navbar-dark bg-dark fixed-top">
  <div class="container">
    <span class="navbar-brand fw-bold">Maseno Foods Hub</span>

    <div>
      <a href="#login" class="btn btn-warning btn-custom me-2">Login</a>
      <a href="register.php" class="btn btn-register btn-custom">Register</a>
    </div>
  </div>
</nav>

<!-- HERO -->
<section class="hero text-center">
  <div class="container">
    <h1 class="fw-bold">🍔 Maseno Foods Hub</h1>
    <p>Fast • Reliable • Affordable Campus Meals</p>

    <div class="mt-4">
      <a href="#login" class="btn btn-primary btn-custom me-2">Order Now</a>
      <a href="register.php" class="btn btn-register btn-custom">Create Account</a>
    </div>
  </div>
</section>

<!-- VENDORS -->
<section class="vendor-section text-center">
  <div class="container">
    <h2 class="mb-5 fw-bold">🔥 Our Top Vendors</h2>

    <div class="row">

      <!-- PALMERS -->
      <div class="col-md-4">
        <h4 class="vendor-title palmers">Palmers Cafe</h4>

        <a href="#login">
          <div class="card food-card shadow">
            <img src="../assets/images/ugali-meat.jpg">
            <div class="card-body">
              <p>Ugali & Beef</p>
            </div>
          </div>
        </a>

        <a href="#login">
          <div class="card food-card shadow">
            <img src="../assets/images/tea-snacks.jpg">
            <div class="card-body">
              <p>Tea & Snacks</p>
            </div>
          </div>
        </a>
      </div>

      <!-- SILVER -->
      <div class="col-md-4">
        <h4 class="vendor-title silver">Silver Spoon</h4>

        <a href="#login">
          <div class="card food-card shadow">
            <img src="../assets/images/pilau.jpg">
            <div class="card-body">
              <p>Pilau</p>
            </div>
          </div>
        </a>

        <a href="#login">
          <div class="card food-card shadow">
            <img src="../assets/images/chicken.jpg">
            <div class="card-body">
              <p>Chicken Fry</p>
            </div>
          </div>
        </a>
      </div>

      <!-- OXYGEN -->
      <div class="col-md-4">
        <h4 class="vendor-title oxygen">Oxygen Restaurant</h4>

        <a href="#login">
          <div class="card food-card shadow">
            <img src="../assets/images/burger.jpg">
            <div class="card-body">
              <p>Burger & Fries</p>
            </div>
          </div>
        </a>

        <a href="#login">
          <div class="card food-card shadow">
            <img src="../assets/images/soda.jpg">
            <div class="card-body">
              <p>Soft Drink</p>
            </div>
          </div>
        </a>
      </div>

    </div>
  </div>
</section>

<!-- LOGIN -->
<section id="login" class="py-5">
  <div class="container d-flex justify-content-center">
    <div class="col-md-4">
      <div class="login-card shadow">

        <h4 class="text-center mb-3">Login to Order</h4>

        <?php if ($error) { ?>
          <div class="alert alert-danger text-center">
            <?= $error ?>
          </div>
        <?php } ?>

        <form method="POST">
          <input class="form-control mb-3" type="email" name="email" placeholder="Email" required>
          <input class="form-control mb-3" type="password" name="password" placeholder="Password" required>
          <button class="btn btn-primary w-100">Login</button>
        </form>

        <p class="text-center mt-3">
          New student?
          <a href="register.php">Create account</a>
        </p>

      </div>
    </div>
  </div>
</section>

</body>
</html>