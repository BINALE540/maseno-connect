<?php
session_start();
include '../includes/db.php';

/* ========================
   AUTH
======================== */
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'vendor') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* ========================
   GET VENDOR
======================== */
$stmt = $conn->prepare("SELECT id, status FROM vendors WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$res) {
    die("Vendor not found");
}

$vendor_id = $res['id'];

if ($res['status'] != 'approved') {
    die("Account pending approval");
}

/* ========================
   ADD FOOD
======================== */
if (isset($_POST['add_food'])) {

    $name = trim($_POST['name']);
    $price = $_POST['price'];
    $image = null;

    if (!empty($_FILES['image']['name'])) {
        $image = time() . '_' . $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], "../uploads/foods/" . $image);
    }

    $stmt = $conn->prepare("INSERT INTO food_items (vendor_id, name, price, image) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isds", $vendor_id, $name, $price, $image);
    $stmt->execute();

    $_SESSION['toast'] = ['type'=>'success','msg'=>'Food added successfully'];
    header("Location: menu.php");
    exit();
}

/* ========================
   DELETE FOOD
======================== */
if (isset($_GET['delete'])) {

    $id = (int)$_GET['delete'];

    try {

        $stmt = $conn->prepare("
            DELETE FROM food_items 
            WHERE id=? AND vendor_id=?
        ");
        $stmt->bind_param("ii", $id, $vendor_id);
        $stmt->execute();

        $_SESSION['toast'] = [
            'type'=>'success',
            'msg'=>'Item deleted successfully'
        ];

    } catch (mysqli_sql_exception $e) {

        $_SESSION['toast'] = [
            'type'=>'error',
            'msg'=>'Cannot delete item. It has existing orders.'
        ];
    }

    header("Location: menu.php");
    exit();
}


/* ========================
   UPDATE FOOD (AJAX)
======================== */
if (isset($_POST['update_id'])) {

    $id = $_POST['update_id'];
    $name = trim($_POST['name']);
    $price = $_POST['price'];

    $stmt = $conn->prepare("SELECT image FROM food_items WHERE id=? AND vendor_id=?");
    $stmt->bind_param("ii", $id, $vendor_id);
    $stmt->execute();
    $old = $stmt->get_result()->fetch_assoc();

    $image = $old['image'];

    if (!empty($_FILES['image']['name'])) {

        $newImage = time() . '_' . $_FILES['image']['name'];

        if (move_uploaded_file($_FILES['image']['tmp_name'], "../uploads/foods/" . $newImage)) {

            if (!empty($image) && file_exists("../uploads/foods/" . $image)) {
                unlink("../uploads/foods/" . $image);
            }

            $image = $newImage;
        }
    }

    $stmt = $conn->prepare("
        UPDATE food_items 
        SET name=?, price=?, image=? 
        WHERE id=? AND vendor_id=?
    ");
    $stmt->bind_param("sdsii", $name, $price, $image, $id, $vendor_id);
    $stmt->execute();

    echo "OK";
    exit();
}

/* ========================
   FETCH MENU
======================== */
$stmt = $conn->prepare("SELECT * FROM food_items WHERE vendor_id=?");
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title>Menu</title>

<link href="../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="../fontawesome/css/all.min.css" rel="stylesheet">
<script src="../assets/bootstrap/js/jquery-3.6.0.min.js"></script>
<script src="../assets/bootstrap/js/sweetalert2@11.js"></script>

<style>
body {
    background: url('../assets/images/food-bg.jpg') no-repeat center center fixed;
    background-size: cover;
    font-family: 'Poppins', sans-serif;
}

/* Overlay */
body::before {
    content: '';
    position: fixed;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.6);
    top: 0;
    left: 0;
    z-index: -1;
}


/* SIDEBAR */
.sidebar {
    height:100vh;
    width:250px;
    position:fixed;
    background:#111;
    padding-top:20px;
    transition:0.3s;
}
.sidebar.collapsed { width:70px; }

.sidebar a {
    display:block;
    padding:15px;
    color:#fff;
    text-decoration:none;
}
.sidebar a:hover,
.sidebar a.active {
    background:#ffc107;
    color:#000;
}




/* Content */
.content {
    margin-left: 250px;
    padding: 20px;
    transition: 0.3s;
}
.content.expanded { margin-left: 70px; }
/* Topbar */
.topbar {
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(10px);
    border-radius: 10px;
}


.card-custom {
    background:#fff;
    border-radius:15px;
}

.preview {
    height:60px;
}
</style>
</head>

<body>

<div class="sidebar" id="sidebar">
    <a href="dashboard.php"><i class="fas fa-home"></i>Home</a>
    <a href="menu.php" class="active"> <i class="fas fa-utensils"></i> Menu</a>
    <a href="orders.php"><i class="fas fa-box"></i> Orders</a>
    <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="content" id="content">

<?php include '../includes/header.php'; ?>

<div class="container">

<h4 class="text-white">Menu</h4>

<!-- ADD -->
<div class="card card-custom p-4 mb-4 shadow">
<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="add_food" value="1">

<div class="row g-2">
<div class="col-md-4">
<input type="text" name="name" class="form-control" placeholder="Food Name" required>
</div>

<div class="col-md-3">
<input type="number" step="0.01" name="price" class="form-control" placeholder="Price" required>
</div>

<div class="col-md-3">
<input type="file" name="image" class="form-control" accept="image/*" onchange="previewImage(event)">
</div>

<div class="col-md-2">
<button class="btn btn-success w-100">Add</button>
</div>
</div>

<img id="preview" class="preview mt-3 d-none">
</form>
</div>

<!-- LIST -->
<div class="card card-custom p-3">
<table class="table table-bordered">
<tr>
<th>Image</th>
<th>Name</th>
<th>Price</th>
<th>Action</th>
</tr>

<?php while($row=$result->fetch_assoc()){ ?>
<tr>
<td>
<?php if($row['image']){ ?>
<img src="../uploads/foods/<?= $row['image'] ?>" class="preview">
<?php } ?>
</td>

<td><?= htmlspecialchars($row['name']) ?></td>
<td><?= number_format($row['price'],2) ?></td>

<td>
<button class="btn btn-warning btn-sm"
onclick="editFood(<?= $row['id'] ?>,'<?= htmlspecialchars($row['name'],ENT_QUOTES) ?>','<?= $row['price'] ?>')">
<i class="fas fa-edit"></i>
</button>

<button class="btn btn-danger btn-sm"
onclick="deleteFood(<?= $row['id'] ?>)">
<i class="fas fa-trash"></i>
</button>
</td>
</tr>
<?php } ?>
</table>
</div>

</div>
</div>

<!-- TOAST SYSTEM -->
<?php if(!empty($_SESSION['toast'])){ ?>
<script>
document.addEventListener("DOMContentLoaded", function(){
    toast("<?= $_SESSION['toast']['type'] ?>","<?= $_SESSION['toast']['msg'] ?>");
});
</script>
<?php unset($_SESSION['toast']); } ?>

<script>
// Sidebar toggle
$('#toggleSidebar').click(function(){
    $('#sidebar').toggleClass('collapsed');
    $('#content').toggleClass('expanded');
});
function toast(type,msg){
    Swal.fire({
        toast:true,
        position:'top-end',
        icon:type,
        title:msg,
        showConfirmButton:false,
        timer:3000,
        background:'#1e1e1e',
        color:'#fff'
    });
}

function previewImage(e){
    let img=document.getElementById('preview');
    img.src=URL.createObjectURL(e.target.files[0]);
    img.classList.remove('d-none');
}

/* DELETE */
function deleteFood(id){
    Swal.fire({
        title:'Delete item?',
        icon:'warning',
        showCancelButton:true
    }).then(res=>{
        if(res.isConfirmed){
            window.location='menu.php?delete='+id;
        }
    });
}

/* EDIT */
function editFood(id,name,price){

Swal.fire({
    title:'Edit Food',
    html:`
        <input id="name" class="swal2-input" value="${name}">
        <input id="price" class="swal2-input" value="${price}">
        <input id="image" type="file" class="swal2-file">
    `,
    confirmButtonText:'Update',
    preConfirm: async () => {

        let fd = new FormData();
        fd.append('update_id', id);
        fd.append('name', document.getElementById('name').value);
        fd.append('price', document.getElementById('price').value);

        let file = document.getElementById('image').files[0];
        if(file) fd.append('image', file);

        let res = await fetch('menu.php',{method:'POST',body:fd});

        if(!res.ok){
            toast('error','Update failed');
            return;
        }

        toast('success','Updated successfully');
        setTimeout(()=>location.reload(),1000);
    }
});

}
</script>

</body>
</html>
