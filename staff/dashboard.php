<?php
session_start();
require_once '../config/database.php';

// Check if staff is logged in
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

// Get total tickets
$sql = "SELECT COUNT(*) as total_tickets FROM tickets";
$result = $conn->query($sql);
$total_tickets = $result->fetch_assoc()['total_tickets'];

// Get active tickets
$sql = "SELECT COUNT(*) as active_tickets FROM tickets WHERE status = 'active'";
$result = $conn->query($sql);
$active_tickets = $result->fetch_assoc()['active_tickets'];

// Get total users
$sql = "SELECT COUNT(*) as total_users FROM users";
$result = $conn->query($sql);
$total_users = $result->fetch_assoc()['total_users'];

// Get today's schedules
$sql = "SELECT COUNT(*) as today_schedules FROM schedules WHERE DATE(departure_time) = CURDATE()";
$result = $conn->query($sql);
$today_schedules = $result->fetch_assoc()['today_schedules'];

// Get recent tickets
$sql = "SELECT t.*, u.username, s.train_number, s.departure_station, s.arrival_station, s.departure_time 
        FROM tickets t 
        JOIN users u ON t.user_id = u.user_id 
        JOIN schedules s ON t.schedule_id = s.schedule_id 
        ORDER BY t.booking_date DESC LIMIT 5";
$recent_tickets = $conn->query($sql);
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
            width: 250px;
        }
        .content {
            margin-left: 250px;
            padding: 20px;
        }
        .nav-link {
            color: white;
            margin: 10px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .nav-link:hover {
            color: #17a2b8;
        }
        .nav-link.active {
            background-color: #0056b3;
            color: white;
        }
        .stats-card {
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .stats-card i {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .ticket-status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 500;
        }
        .ticket-status.active {
            background-color: #d4edda;
            color: #155724;
        }
        .ticket-status.used {
            background-color: #cce5ff;
            color: #004085;
        }
        .ticket-status.cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        #qr-reader {
            width: 100%;
            max-width: 300px;
            margin: 0 auto;
        }
        .scan-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .scan-result {
            background: white;
            padding: 20px;
            border-radius: 10px;
            max-width: 500px;
            width: 90%;
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
                    <a class="nav-link" href="scan_qr.php">
                        <i class='bx bx-qr-scan'></i> Scan QR
                    </a>
                    <?php if ($_SESSION['staff_role'] === 'admin'): ?>
                    <a class="nav-link" href="manage_staff.php">
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class='bx bxs-dashboard' style="font-size: 2.5rem; color: #0056b3;"></i>
                        </div>
                        <div>
                            <h2 class="mb-0">Dashboard</h2>
                            <small class="text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['staff_username']); ?></small>
                        </div>
                    </div>
                    <div>
                        <a href="scan_qr.php" class="btn btn-primary me-2">
                            <i class='bx bx-qr-scan'></i> Full Screen Scanner
                        </a>
                        <a href="manage_tickets.php" class="btn btn-info">
                            <i class='bx bx-ticket'></i> View All Tickets
                        </a>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="stats-card bg-primary text-white">
                            <i class='bx bx-ticket'></i>
                            <h3><?php echo $total_tickets; ?></h3>
                            <p class="mb-0">Total Tickets</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card bg-success text-white">
                            <i class='bx bx-check-circle'></i>
                            <h3><?php echo $active_tickets; ?></h3>
                            <p class="mb-0">Active Tickets</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card bg-info text-white">
                            <i class='bx bx-user'></i>
                            <h3><?php echo $total_users; ?></h3>
                            <p class="mb-0">Total Users</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card bg-warning text-white">
                            <i class='bx bx-time-five'></i>
                            <h3><?php echo $today_schedules; ?></h3>
                            <p class="mb-0">Today's Schedules</p>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <!-- Quick Scan Section -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Quick Scan</h5>
                                <div>
                                    <button class="btn btn-secondary btn-sm me-2" onclick="switchCamera()">
                                        <i class='bx bx-refresh'></i> Switch Camera
                                    </button>
                                    <button class="btn btn-primary btn-sm" onclick="startScanner()">
                                        <i class='bx bx-refresh'></i> Restart Scanner
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="qr-reader"></div>
                                <div class="alert alert-info mt-3">
                                    <i class='bx bx-info-circle'></i>
                                    Position the QR code within the scanner frame
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Tickets -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Recent Tickets</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Ticket ID</th>
                                                <th>User</th>
                                                <th>Train</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($ticket = $recent_tickets->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $ticket['ticket_id']; ?></td>
                                                <td><?php echo htmlspecialchars($ticket['username']); ?></td>
                                                <td><?php echo htmlspecialchars($ticket['train_number']); ?></td>
                                                <td>
                                                    <span class="ticket-status <?php echo $ticket['status']; ?>">
                                                        <?php echo ucfirst($ticket['status']); ?>
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
    </div>

    <!-- Scan Result Overlay -->
    <div class="scan-overlay" id="scanOverlay">
        <div class="scan-result">
            <h4>Ticket Details</h4>
            <div id="ticketDetails"></div>
            <div class="mt-3 d-flex justify-content-between">
                <button class="btn btn-secondary" onclick="closeScanResult()">Close</button>
                <button class="btn btn-success" id="verifyButton" onclick="verifyTicket()">Verify Ticket</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
        let html5QrcodeScanner;
        let currentTicketId = null;
        let currentCamera = 'environment'; // Default to back camera

        function startScanner() {
            if (html5QrcodeScanner) {
                html5QrcodeScanner.clear();
            }

            html5QrcodeScanner = new Html5QrcodeScanner(
                "qr-reader", 
                { 
                    fps: 10,
                    qrbox: {width: 200, height: 200},
                    aspectRatio: 1.0,
                    facingMode: currentCamera
                }
            );

            html5QrcodeScanner.render(onScanSuccess, onScanFailure);
        }

        function switchCamera() {
            currentCamera = currentCamera === 'environment' ? 'user' : 'environment';
            startScanner();
        }

        function onScanSuccess(decodedText, decodedResult) {
            try {
                const ticketData = JSON.parse(decodedText);
                if (ticketData.ticket_id) {
                    // Stop scanning temporarily
                    if (html5QrcodeScanner) {
                        html5QrcodeScanner.pause();
                    }

                    // Fetch ticket details
                    fetch('get_ticket_details.php?ticket_id=' + ticketData.ticket_id)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                currentTicketId = ticketData.ticket_id;
                                displayTicketDetails(data.ticket);
                            } else {
                                alert('Error: ' + data.message);
                                if (html5QrcodeScanner) {
                                    html5QrcodeScanner.resume();
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Error fetching ticket details');
                            if (html5QrcodeScanner) {
                                html5QrcodeScanner.resume();
                            }
                        });
                }
            } catch (e) {
                console.error('Invalid QR code format:', e);
            }
        }

        function onScanFailure(error) {
            // Handle scan failure silently
        }

        function displayTicketDetails(ticket) {
            const statusClass = ticket.status === 'active' ? 'text-success' : 
                              ticket.status === 'used' ? 'text-primary' : 
                              'text-danger';

            const details = `
                <div class="mb-3">
                    <h6>Passenger Information</h6>
                    <p><strong>Name:</strong> ${ticket.username}</p>
                </div>
                <div class="mb-3">
                    <h6>Journey Details</h6>
                    <p><strong>Train:</strong> ${ticket.train_number}</p>
                    <p><strong>From:</strong> ${ticket.departure_station}</p>
                    <p><strong>To:</strong> ${ticket.arrival_station}</p>
                    <p><strong>Departure:</strong> ${new Date(ticket.departure_time).toLocaleString()}</p>
                </div>
                <div class="mb-3">
                    <h6>Ticket Status</h6>
                    <p><strong>Status:</strong> <span class="${statusClass}">${ticket.status.toUpperCase()}</span></p>
                </div>
            `;

            document.getElementById('ticketDetails').innerHTML = details;
            document.getElementById('verifyButton').style.display = ticket.status === 'active' ? 'block' : 'none';
            document.getElementById('scanOverlay').style.display = 'flex';
        }

        function closeScanResult() {
            document.getElementById('scanOverlay').style.display = 'none';
            currentTicketId = null;
            if (html5QrcodeScanner) {
                html5QrcodeScanner.resume();
            }
        }

        function verifyTicket() {
            if (!currentTicketId) return;

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'ticket_actions.php';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'verify';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'ticket_id';
            idInput.value = currentTicketId;
            
            form.appendChild(actionInput);
            form.appendChild(idInput);
            document.body.appendChild(form);
            form.submit();
        }

        // Start scanner when page loads
        window.onload = startScanner;
    </script>
</body>
</html>
