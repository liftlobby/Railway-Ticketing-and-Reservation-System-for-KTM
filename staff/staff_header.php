<?php
if (!isset($_SESSION['staff_id']) || !isset($_SESSION['staff_username'])) {
    header("Location: login.php");
    exit();
}
?>

<nav class="navbar">
    <div class="navbar-brand">
        <img src="../images/ktm_logo.png" alt="KTM Logo" class="navbar-logo">
        <span class="navbar-title">KTM Staff Portal</span>
    </div>
    <div class="navbar-menu">
        <a href="dashboard.php" class="navbar-item">
            <i class="fas fa-home"></i> Dashboard
        </a>
        <a href="manage_schedules.php" class="navbar-item">
            <i class="fas fa-calendar"></i> Schedules
        </a>
        <a href="manage_tickets.php" class="navbar-item">
            <i class="fas fa-ticket-alt"></i> Tickets
        </a>
        <a href="manage_users.php" class="navbar-item">
            <i class="fas fa-users"></i> Users
        </a>
        <a href="scan_qr.php" class="navbar-item">
            <i class="fas fa-qrcode"></i> Scan QR
        </a>
        <?php if ($_SESSION['staff_role'] === 'admin'): ?>
        <a href="manage_staff.php" class="navbar-item">
            <i class="fas fa-user-shield"></i> Staff
        </a>
        <?php endif; ?>
        <a href="logout.php" class="navbar-item">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</nav>

<style>
.navbar {
    background-color: #1a1a1a;
    padding: 1rem;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.navbar-brand {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.navbar-logo {
    height: 40px;
    width: auto;
}

.navbar-title {
    font-size: 1.5rem;
    font-weight: bold;
}

.navbar-menu {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.navbar-item {
    color: #ffffff;
    text-decoration: none;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    transition: background-color 0.3s;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.navbar-item:hover {
    background-color: #333333;
    color: white;
    text-decoration: none;
}

.navbar-item.active {
    background-color: #0056b3;
    color: white;
}

.navbar-item i {
    font-size: 1.1rem;
}

@media (max-width: 768px) {
    .navbar {
        flex-direction: column;
        padding: 0.5rem;
    }

    .navbar-menu {
        flex-wrap: wrap;
        justify-content: center;
        padding: 0.5rem;
        gap: 0.5rem;
    }

    .navbar-item {
        font-size: 0.9rem;
        padding: 0.4rem 0.8rem;
    }
}
</style>
