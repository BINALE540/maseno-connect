
<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

/* FETCH VENDORS */
$result = $conn->query("
SELECT 
    vendors.id,
    vendors.status,
    vendors.business_name,
    users.name,
    users.email,
    users.phone_number
FROM vendors
JOIN users ON vendors.user_id = users.id
");
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

/* CARD */
.glass-card {
    background:rgba(255,255,255,0.95);
    border-radius:15px;
}

/* TABLE */
.table-container {
    max-height:500px;
    overflow-y:auto;
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
    <a href="dashboard.php"><i class="fas fa-home"></i> Home</a>
    <a href="vendors.php"  class="active"><i class="fas fa-store"></i> Vendors</a>
    <a href="orders.php"><i class="fas fa-box"></i> Orders</a>
          <a href="users.php"><i class="fas fa-users"></i> Users</a>
    <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="content" id="content">

<?php include '../includes/header.php'; ?>

<div class="container">
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="text-white">Vendor Management</h3>

    <!-- ADD BUTTON -->
    <button class="btn btn-success" onclick="addVendor()">
        <i class="fas fa-plus"></i> Add Vendor
    </button>
</div>

<div class="glass-card p-3 shadow">

<div class="table-container">

<table class="table table-hover align-middle">
<thead class="table-dark">
<tr>
    <th>Business</th>
    <th>Owner</th>
    <th>Email</th>
    <th>Phone</th>
    <th>Status</th>
    <th>Action</th>
</tr>
</thead>

<tbody>

<?php while ($v = $result->fetch_assoc()) { ?>

<tr>

<td><b><?= htmlspecialchars($v['business_name']) ?></b></td>
<td><?= htmlspecialchars($v['name']) ?></td>
<td><?= htmlspecialchars($v['email']) ?></td>
<td><?= htmlspecialchars($v['phone_number']) ?></td>

<td>
<span class="badge 
<?= $v['status']=='approved' ? 'bg-success' :
   ($v['status']=='suspended' ? 'bg-danger' : 'bg-warning') ?>">
<?= ucfirst($v['status']) ?>
</span>
</td>

<td class="d-flex gap-1">

<button class="btn btn-sm btn-primary"
onclick="manageVendor(<?= $v['id'] ?>)">
<i class="fas fa-cogs"></i>
</button>

<button class="btn btn-sm btn-warning"
onclick="editVendor(
<?= $v['id'] ?>,
'<?= addslashes($v['name']) ?>',
'<?= addslashes($v['email']) ?>',
'<?= addslashes($v['phone_number']) ?>'
)">
<i class="fas fa-edit"></i>
</button>



<button class="btn btn-sm btn-danger"
onclick="deleteVendor(<?= $v['id'] ?>)">
<i class="fas fa-trash"></i>
</button>

</td>

</tr>

<?php } ?>

</tbody>
</table>

</div>
</div>

</div>








</div>
</div>
<script>
    // Sidebar toggle
$('#toggleSidebar').click(function(){
    $('#sidebar').toggleClass('collapsed');
    $('#content').toggleClass('expanded');
});

/* =========================
   ADD VENDOR
========================= */
function addVendor(){

    Swal.fire({
        title: 'Add Vendor',
        html: `
            <input id="name" class="swal2-input" placeholder="Full Name">
            <input id="email" class="swal2-input" placeholder="Email">
            <input id="phone" class="swal2-input" placeholder="Phone">
            <input id="password" type="password" class="swal2-input" placeholder="Password">
        `,
        showCancelButton: true,
        preConfirm: () => {
            return {
                name: document.getElementById('name').value,
                email: document.getElementById('email').value,
                phone: document.getElementById('phone').value,
                password: document.getElementById('password').value
            }
        }
    }).then((res)=>{

        if(res.isConfirmed){

            $.post('add_vendor.php', res.value, function(response){

                if(response === 'exists'){
                    Swal.fire('Error','Email already exists','error');
                    return;
                }

                Swal.fire('Vendor Added','','success')
                .then(()=>location.reload());

            });

        }

    });

}


/* =========================
   EDIT VENDOR
========================= */
function editVendor(id, name, email, phone){

    Swal.fire({
        title: 'Edit Vendor',
        html: `
            <input id="name" class="swal2-input" value="${name}">
            <input id="email" class="swal2-input" value="${email}">
            <input id="phone" class="swal2-input" value="${phone}">
        `,
        showCancelButton: true,
        preConfirm: () => {
            return {
                id: id,
                name: $('#name').val(),
                email: $('#email').val(),
                phone: $('#phone').val()
            }
        }
    }).then((res)=>{

        if(res.isConfirmed){

            $.post('update_vendor.php', res.value, function(){

                Swal.fire('Updated','','success')
                .then(()=>location.reload());

            });

        }

    });

}


/* =========================
   DELETE VENDOR
========================= */
function deleteVendor(id){

    Swal.fire({
        title: 'Delete Vendor?',
        text: 'This cannot be undone',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Delete'
    }).then((res)=>{

        if(res.isConfirmed){

            $.post('delete_vendor.php',{id:id},function(){

                Swal.fire('Deleted','','success')
                .then(()=>location.reload());

            });

        }

    });
}

/* =========================
   MANAGE STATUS
========================= */
function manageVendor(id){

    Swal.fire({
        title: 'Change Status',
        html: `
        <button class="btn btn-success w-100 mb-2" onclick="updateStatus(${id},'approved')">Approve</button>
        <button class="btn btn-danger w-100 mb-2" onclick="updateStatus(${id},'suspended')">Suspend</button>
        <button class="btn btn-warning w-100" onclick="updateStatus(${id},'pending')">Pending</button>
        `,
        showConfirmButton:false
    });

}

function updateStatus(id, status){

    $.post('update_vendor.php',{
        id:id,
        status:status
    },function(){

        Swal.fire('Updated','','success')
        .then(()=>location.reload());

    });

}
</script>
</body>
</html>
