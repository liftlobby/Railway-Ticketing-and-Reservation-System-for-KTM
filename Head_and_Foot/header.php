<?php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
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
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <!-- Navigation for guests -->
                <a href="schedules.php">Schedules</a>
                <a href="QRCode.php">QR Onboard</a>
                <a href="about_us.php">About Us</a>
                <a href="login.php">Login/Register</a>
            <?php endif; ?>
        </nav>
    </header>
    <!-- Main Content Container -->
    <div class="container mt-4">