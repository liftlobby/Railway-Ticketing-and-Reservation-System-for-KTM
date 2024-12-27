<?php
session_start();
require_once '../config/database.php';

// Check if staff is logged in
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

$staff_role = $_SESSION['staff_role'];
$staff_id = $_SESSION['staff_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - KTM Railway System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <style>
        .sidebar {
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding: 20px;
            background-color: #343a40;
            color: white;
        }
        .content {
            margin-left: 250px;
            padding: 20px;
        }
        .nav-link {
            color: white;
            margin: 10px 0;
        }
        .nav-link:hover {
            color: #17a2b8;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar">
                <h3 class="mb-4">Staff Dashboard</h3>
                <nav class="nav flex-column">
                    <a class="nav-link active" href="dashboard.php">
                        <i class='bx bxs-dashboard'></i> Dashboard
                    </a>
                    <a class="nav-link" href="manage_schedules.php">
                        <i class='bx bx-time-five'></i> Manage Schedules
                    </a>
                    <a class="nav-link" href="manage_tickets.php">
                        <i class='bx bx-ticket'></i> Manage Tickets
                    </a>
                    <a class="nav-link" href="manage_users.php">
                        <i class='bx bx-user'></i> Manage Users
                    </a>
                    <?php if ($staff_role === 'admin'): ?>
                    <a class="nav-link" href="manage_staffs.php">
                        <i class='bx bx-group'></i> Manage Staff
                    </a>
                    <?php endif; ?>
                    <a class="nav-link" href="logout.php">
                        <i class='bx bx-log-out'></i> Logout
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 content">
                <h2>Welcome, <?php echo htmlspecialchars($_SESSION['staff_username']); ?></h2>
                
                <div class="row mt-4">
                    <!-- Statistics Cards -->
                    <div class="col-md-3 mb-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Users</h5>
                                <?php
                                $result = $conn->query("SELECT COUNT(*) as count FROM users");
                                $users_count = $result->fetch_assoc()['count'];
                                ?>
                                <p class="card-text h2"><?php echo $users_count; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Active Tickets</h5>
                                <?php
                                $result = $conn->query("SELECT COUNT(*) as count FROM tickets WHERE status = 'active'");
                                $tickets_count = $result->fetch_assoc()['count'];
                                ?>
                                <p class="card-text h2"><?php echo $tickets_count; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-4">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Today's Schedules</h5>
                                <?php
                                $result = $conn->query("SELECT COUNT(*) as count FROM schedules WHERE DATE(departure_time) = CURDATE()");
                                $schedules_count = $result->fetch_assoc()['count'];
                                ?>
                                <p class="card-text h2"><?php echo $schedules_count; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-4">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Staff</h5>
                                <?php
                                $result = $conn->query("SELECT COUNT(*) as count FROM staffs");
                                $staff_count = $result->fetch_assoc()['count'];
                                ?>
                                <p class="card-text h2"><?php echo $staff_count; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5>Recent Ticket Bookings</h5>
                            </div>
                            <div class="card-body">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Ticket ID</th>
                                            <th>User</th>
                                            <th>Schedule</th>
                                            <th>Booking Date</th>
                                            <th>Status</th>
                                            <th>Payment Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = "SELECT t.*, u.username, s.departure_station, s.arrival_station 
                                                FROM tickets t 
                                                JOIN users u ON t.user_id = u.user_id 
                                                JOIN schedules s ON t.schedule_id = s.schedule_id 
                                                ORDER BY t.booking_date DESC LIMIT 5";
                                        $result = $conn->query($sql);
                                        while ($row = $result->fetch_assoc()):
                                        ?>
                                        <tr>
                                            <td><?php echo $row['ticket_id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                                            <td><?php echo htmlspecialchars($row['departure_station']) . ' → ' . htmlspecialchars($row['arrival_station']); ?></td>
                                            <td><?php echo $row['booking_date']; ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $row['status'] === 'active' ? 'success' : ($row['status'] === 'cancelled' ? 'danger' : 'secondary'); ?>">
                                                    <?php echo ucfirst($row['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $row['payment_status'] === 'paid' ? 'success' : ($row['payment_status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                                    <?php echo ucfirst($row['payment_status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
