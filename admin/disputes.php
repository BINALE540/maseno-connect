<?php
session_start();
include '../includes/db.php';

if ($_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$result = $conn->query("
SELECT orders.id, orders.total, orders.status, orders.dispute_status,
       orders.created_at,
       s.name AS student, vuser.name AS vendor
FROM orders
JOIN users s ON orders.student_id = s.id
JOIN vendors v ON orders.vendor_id = v.id
JOIN users vuser ON v.user_id = vuser.id
WHERE orders.dispute_status != 'none'
ORDER BY orders.created_at DESC
");
?>

<?php include '../includes/header.php'; ?>

<div class="container py-5">
  <h3 class="text-white mb-4">Order Disputes</h3>

  <div class="card shadow-lg border-0">
    <div class="card-body">

      <table class="table table-hover align-middle">
        <thead class="table-dark">
          <tr>
            <th>Student</th>
            <th>Vendor</th>
            <th>Total</th>
            <th>Status</th>
            <th>Dispute</th>
            <th>Action</th>
          </tr>
        </thead>

        <tbody>
        <?php while ($o = $result->fetch_assoc()) { ?>
          <tr>
            <td><?= $o['student'] ?></td>
            <td><?= $o['vendor'] ?></td>
            <td>KES <?= $o['total'] ?></td>
            <td><?= $o['status'] ?></td>
            <td>
              <span class="badge <?= $o['dispute_status']=='disputed'?'bg-danger':'bg-success' ?>">
                <?= ucfirst($o['dispute_status']) ?>
              </span>
            </td>
            <td>
              <?php if ($o['dispute_status']=='disputed') { ?>
                <a href="resolve_dispute.php?id=<?= $o['id'] ?>"
                   class="btn btn-success btn-sm">
                   Resolve
                </a>
              <?php } else { ?>
                Completed
              <?php } ?>
            </td>
          </tr>
        <?php } ?>
        </tbody>

      </table>

    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
