<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

/* FETCH USERS (ONLY ADMIN + STUDENT) */
$result = $conn->query("
SELECT id, name, email, phone_number, role
FROM users
WHERE role IN ('admin','student')
ORDER BY id DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Management</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

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
.glass-card{
    background:rgba(255,255,255,0.95);
    border-radius:15px;
}

/* TABLE */
.table-container{
    max-height:500px;
    overflow-y:auto;
}
</style>
</head>

<body>

<!-- SIDEBAR -->

<div class="sidebar" id="sidebar">
    <a href="dashboard.php"><i class="fas fa-home"></i> Home</a>
    <a href="vendors.php" ><i class="fas fa-store"></i> Vendors</a>
    <a href="orders.php"><i class="fas fa-box"></i> Orders</a>
          <a href="users.php"  class="active"><i class="fas fa-users"></i> Users</a>
    <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="content" id="content">

<?php include '../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="text-white">User Management</h3>

    <button class="btn btn-success" onclick="addUser()">
        <i class="fas fa-user-plus"></i> Add User
    </button>
</div>

<!-- FILTERS -->
<div class="glass-card p-3 mb-3">
    <div class="row g-2">
        <div class="col-md-6">
            <input type="text" id="search" class="form-control" placeholder="Search name/email...">
        </div>
        <div class="col-md-3">
            <select id="roleFilter" class="form-select">
                <option value="">All Roles</option>
                <option value="admin">Admin</option>
                <option value="student">Student</option>
            </select>
        </div>
    </div>
</div>

<div class="glass-card p-3 shadow">

<div class="table-container">

<table class="table table-hover align-middle">
<thead class="table-dark">
<tr>
    <th>Name</th>
    <th>Email</th>
    <th>Phone</th>
    <th>Role</th>
    <th>Action</th>
</tr>
</thead>

<tbody id="userTable">

<?php while ($u = $result->fetch_assoc()) { ?>

<tr data-role="<?= $u['role'] ?>">

<td><?= htmlspecialchars($u['name']) ?></td>
<td><?= htmlspecialchars($u['email']) ?></td>
<td><?= htmlspecialchars($u['phone_number']) ?></td>

<td>
<span class="badge <?= $u['role']=='admin'?'bg-danger':'bg-primary' ?>">
<?= ucfirst($u['role']) ?>
</span>
</td>

<td class="d-flex gap-1">

<button class="btn btn-sm btn-warning"
onclick="editUser(
<?= $u['id'] ?>,
'<?= addslashes($u['name']) ?>',
'<?= addslashes($u['email']) ?>',
'<?= addslashes($u['phone_number']) ?>',
'<?= $u['role'] ?>'
)">
<i class="fas fa-edit"></i>
</button>

<button class="btn btn-sm btn-danger"
onclick="deleteUser(<?= $u['id'] ?>)">
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

<script>
// Sidebar toggle
$('#toggleSidebar').click(function(){
    $('#sidebar').toggleClass('collapsed');
    $('#content').toggleClass('expanded');
});
/* =========================
   LIVE SEARCH + FILTER
========================= */
$('#search, #roleFilter').on('keyup change', function(){

    let search = $('#search').val().toLowerCase();
    let role = $('#roleFilter').val();

    $('#userTable tr').each(function(){

        let text = $(this).text().toLowerCase();
        let r = $(this).data('role');

        let show = true;

        if(search && !text.includes(search)) show=false;
        if(role && r !== role) show=false;

        $(this).toggle(show);
    });

});

/* =========================
   ADD USER
========================= */
function addUser(){

Swal.fire({
    title: 'Add User',
    html: `
        <input id="name" class="swal2-input" placeholder="Full Name">
        <input id="email" class="swal2-input" placeholder="Email">
        <input id="phone" class="swal2-input" placeholder="Phone">
        <input id="password" type="password" class="swal2-input" placeholder="Password">
        <select id="role" class="swal2-input">
            <option value="student">Student</option>
            <option value="admin">Admin</option>
        </select>
    `,
    showCancelButton:true,
    preConfirm:()=>{
        return {
            name:$('#name').val(),
            email:$('#email').val(),
            phone:$('#phone').val(),
            password:$('#password').val(),
            role:$('#role').val()
        }
    }
}).then(res=>{

    if(res.isConfirmed){

        $.post('add_user.php', res.value, function(response){

            if(response === 'exists'){
                Swal.fire('Error','Email already exists','error');
                return;
            }

            Swal.fire('User Added','','success')
            .then(()=>location.reload());

        });

    }

});

}

/* =========================
   EDIT USER
========================= */
function editUser(id,name,email,phone,role){

Swal.fire({
    title:'Edit User',
    html:`
        <input id="name" class="swal2-input" value="${name}">
        <input id="email" class="swal2-input" value="${email}">
        <input id="phone" class="swal2-input" value="${phone}">
        <select id="role" class="swal2-input">
            <option value="student" ${role=='student'?'selected':''}>Student</option>
            <option value="admin" ${role=='admin'?'selected':''}>Admin</option>
        </select>
    `,
    showCancelButton:true,
    preConfirm:()=>{
        return {
            id:id,
            name:$('#name').val(),
            email:$('#email').val(),
            phone:$('#phone').val(),
            role:$('#role').val()
        }
    }
}).then(res=>{

    if(res.isConfirmed){

        $.post('update_user.php', res.value, function(){
            Swal.fire('Updated','','success')
            .then(()=>location.reload());
        });

    }

});

}

/* =========================
   DELETE USER
========================= */
function deleteUser(id){

Swal.fire({
    title:'Delete user?',
    icon:'warning',
    showCancelButton:true
}).then(res=>{

    if(res.isConfirmed){

        $.post('delete_user.php',{id:id},function(){
            Swal.fire('Deleted','','success')
            .then(()=>location.reload());
        });

    }

});

}

</script>

</body>
</html>
