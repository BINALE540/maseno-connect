<div class="topbar d-flex align-items-center justify-content-between px-3 py-2">

    <!-- LEFT -->
    <div class="d-flex align-items-center gap-3">
        <button class="btn btn-warning btn-sm" id="toggleSidebar">
            <i class="fas fa-bars"></i>
        </button>

        <span class="text-white fw-bold fs-5">
            <i class="fas fa-utensils me-2"></i> Maseno Foods Hub
        </span>
 

    </div>

    <!-- RIGHT -->
    <div class="d-flex align-items-center gap-2">

        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') { ?>
            <a href="../admin/analytics.php" class="btn btn-outline-light btn-sm">
                <i class="fas fa-chart-line"></i>
            </a>
        <?php } ?>
               <span class="text-white small">
    Hi, <?= $_SESSION['name'] ?? 'User' ?>
</span>

        <a href="../auth/logout.php" class="btn btn-danger btn-sm">
            <i class="fas fa-sign-out-alt"></i>
        </a>

    </div>

</div>
