<?php
require_once __DIR__ . '/../config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .notification-badge {
            background: red;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
            position: relative;
            top: -10px;
            right: 5px;
        }
    </style>
</head>
<body>
    <header class="header">
        <a href="index.php">
            <img src="image/logo.png" alt="KTM Logo">
        </a>
        <nav>
            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Navigation for logged-in users -->
                <a href="schedules.php">Schedules</a>
                <a href="ticketing.php">Buy Tickets</a>
                <a href="history.php">Purchase History</a>
                <a href="ticket_cancellation.php">Cancel Ticket</a>
                <a href="report.php">Report Issue</a>
                <a href="about_us.php">About Us</a>
                <a href="notifications.php">
                    <i class="bi bi-bell"></i>
                    <?php
                        require_once __DIR__ . '/../includes/NotificationManager.php';
                        $notificationManager = new NotificationManager($conn);
                        $unreadCount = count($notificationManager->getUnreadNotifications($_SESSION['user_id']));
                        if ($unreadCount > 0):
                    ?>
                    <span class="notification-badge"><?php echo $unreadCount; ?></span>
                    <?php endif; ?>
                </a>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <!-- Navigation for guests -->
                <a href="schedules.php">Schedules</a>
                <a href="about_us.php">About Us</a>
                <a href="login.php">Login/Register</a>
            <?php endif; ?>
        </nav>
    </header>
    <!-- Main Content Container -->
    <div class="container mt-4">