<?php
if (!isset($_SESSION)) {
    session_start();
}

// Get the current page name
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="col-md-2 sidebar">
    <h3 class="mb-4">Staff Dashboard</h3>
    <nav class="nav flex-column">
        <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
            <i class='bx bxs-dashboard'></i> Dashboard
        </a>
        <a class="nav-link <?php echo $current_page === 'manage_schedules.php' ? 'active' : ''; ?>" href="manage_schedules.php">
            <i class='bx bx-time-five'></i> Manage Schedules
        </a>
        <a class="nav-link <?php echo $current_page === 'manage_tickets.php' ? 'active' : ''; ?>" href="manage_tickets.php">
            <i class='bx bx-ticket'></i> Manage Tickets
        </a>
        <a class="nav-link <?php echo $current_page === 'manage_users.php' ? 'active' : ''; ?>" href="manage_users.php">
            <i class='bx bx-user'></i> Manage Users
        </a>
        <a class="nav-link <?php echo $current_page === 'scan_qr.php' ? 'active' : ''; ?>" href="scan_qr.php">
            <i class='bx bx-qr-scan'></i> Scan QR
        </a>
        <?php if (isset($_SESSION['staff_role']) && $_SESSION['staff_role'] === 'admin'): ?>
        <a class="nav-link <?php echo $current_page === 'manage_staff.php' ? 'active' : ''; ?>" href="manage_staff.php">
            <i class='bx bx-group'></i> Manage Staff
        </a>
        <?php endif; ?>
        <a class="nav-link" href="logout.php">
            <i class='bx bx-log-out'></i> Logout
        </a>
    </nav>
</div>
