<?php
session_start();
include '../includes/db.php';

$error = "";
$success = "";

/* HANDLE REGISTRATION */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role     = $_POST['role'];

if ($_POST['role'] == 'student' && empty($_POST['phone_number'])) {
    die("Phone number is required for students.");
}

    // Check if email exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $error = "Email already registered";
    } else {
        $phone_number = trim($_POST['phone_number']);

       $stmt = $conn->prepare(
      "INSERT INTO users (name, email, phone_number, password, role) 
       VALUES (?, ?, ?, ?, ?)"
);

        $stmt->bind_param("sssss", $name, $email, $phone_number, $password, $role);
        $stmt->execute();

        $user_id = $stmt->insert_id;

        // If vendor, create vendor profile
        if ($role === 'vendor') {

        $business_name = trim($_POST['business_name']);

        if (empty($business_name)) {
        $error = "Business name is required for vendors.";
        } else {

        $v = $conn->prepare(
            "INSERT INTO vendors (user_id, business_name, status) 
             VALUES (?, ?, 'pending')"
        );

        $v->bind_param("is", $user_id, $business_name);
        $v->execute();
    }
}

        $success = "Account created successfully. You can now login.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Maseno Foods Hub | Register</title>

  <link href="../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="../assets/bootstrap/css/all.min.css" rel="stylesheet">
<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/bootstrap/js/jquery-3.6.0.min.js"></script>
<script src="../assets/bootstrap/js/chart.js"></script>
<script src="../assets/bootstrap/js/html5-qrcode.min.js"></script>
<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/bootstrap/js/sweetalert2@11.js"></script>
<link href="../assets/bootstrap/css/css2.css" rel="stylesheet">

<style>
body {
    font-family: 'Poppins', sans-serif;
    background:
      linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)),
      url('../assets/images/food-bg.jpg') center/cover no-repeat;
    min-height: 100vh;
}

.register-card {
    background: #fff;
    border-radius: 12px;
    padding: 30px;
}
</style>
</head>

<body class="d-flex align-items-center justify-content-center">

<div class="container">
  <div class="row justify-content-center">

    <!-- LEFT INFO -->
    <div class="col-md-6 text-white mb-4 mb-md-0">
      <h1 class="fw-bold">Maseno Foods Hub</h1>
      <p class="mt-3">
        Join Maseno Foods Hub and enjoy easy food ordering
        from trusted campus vendors.
      </p>
      <p>
        Register as a student or vendor and start today.
      </p>
    </div>

    <!-- REGISTER CARD -->
    <div class="col-md-4">
      <div class="register-card shadow">

        <h4 class="text-center mb-3">Create Account</h4>

        <?php if ($error) { ?>
          <div class="alert alert-danger text-center">
            <?= $error ?>
          </div>
        <?php } ?>

        <?php if ($success) { ?>
          <div class="alert alert-success text-center">
            <?= $success ?>
            <br>
            <a href="login.php" class="fw-bold">Login here</a>
          </div>
        <?php } ?>

        <form method="POST">
          <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="name" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>

          <div class="mb-3">
            <label>Phone Number</label>
            <input type="text" name="phone_number" class="form-control" 
             placeholder="e.g. 0712345678" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Register As</label>
            <select name="role" id="roleSelect" class="form-select" required>
            <option value="">-- Select Role --</option>
            <option value="student">Student</option>
            <option value="vendor">Vendor</option>
            </select>
          </div>

          <div class="mb-3" id="businessField" style="display:none;">
            <label class="form-label">Hotel / Cafe Name</label>
            <input type="text" name="business_name" class="form-control">
          </div>

          <button class="btn btn-primary w-100">
            Create Account
          </button>
        </form>

        <div class="text-center mt-3">
          <small>
            Already have an account?
            <a href="login.php" class="fw-bold">Login</a>
          </small>
        </div>

      </div>
    </div>

  </div>
</div>
<script>
document.getElementById('roleSelect').addEventListener('change', function() {
    const businessField = document.getElementById('businessField');
    if (this.value === 'vendor') {
        businessField.style.display = 'block';
    } else {
        businessField.style.display = 'none';
    }
});
</script>
</body>
</html>
