<?php
session_start();
include '../includes/db.php';

if ($_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

if (isset($_GET['approve'])) {
    $id = $_GET['approve'];
    $conn->query("UPDATE vendors SET status='approved' WHERE id=$id");
}

$result = $conn->query(
    "SELECT vendors.id, users.name, vendors.shop_name, vendors.status
     FROM vendors JOIN users ON vendors.user_id = users.id"
);
?>

<h2>Approve Vendors</h2>

<table border="1" cellpadding="5">
<tr>
    <th>Vendor</th>
    <th>Shop</th>
    <th>Status</th>
    <th>Action</th>
</tr>

<?php while ($row = $result->fetch_assoc()) { ?>
<tr>
    <td><?= $row['name'] ?></td>
    <td><?= $row['shop_name'] ?></td>
    <td><?= $row['status'] ?></td>
    <td>
        <?php if ($row['status'] == 'pending') { ?>
            <a href="?approve=<?= $row['id'] ?>">Approve</a>
        <?php } ?>
    </td>
</tr>
<?php } ?>
</table>
