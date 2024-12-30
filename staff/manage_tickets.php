<?php
session_start();
require_once '../config/database.php';

// Check if staff is logged in
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch all tickets with user details
$sql = "SELECT t.*, u.username, s.train_number, s.departure_station, s.arrival_station, 
        s.departure_time, s.arrival_time, s.price 
        FROM tickets t 
        JOIN users u ON t.user_id = u.user_id 
        JOIN schedules s ON t.schedule_id = s.schedule_id 
        ORDER BY t.booking_date DESC";
$tickets = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Tickets - KTM Railway System</title>
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
        .ticket-status.expired {
            background-color: #e2e3e5;
            color: #383d41;
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
                    <a class="nav-link" href="dashboard.php">
                        <i class='bx bxs-dashboard'></i> Dashboard
                    </a>
                    <a class="nav-link" href="manage_schedules.php">
                        <i class='bx bx-time-five'></i> Manage Schedules
                    </a>
                    <a class="nav-link active" href="manage_tickets.php">
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
                    <h2>Manage Tickets</h2>
                    <div>
                        <button class="btn btn-primary me-2" onclick="window.location.href='scan_qr.php'">
                            <i class='bx bx-qr-scan'></i> Scan QR Code
                        </button>
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#verifyTicketModal">
                            <i class='bx bx-check-circle'></i> Verify Ticket
                        </button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Ticket ID</th>
                                        <th>User</th>
                                        <th>Train</th>
                                        <th>Route</th>
                                        <th>Departure</th>
                                        <th>Status</th>
                                        <th>Booking Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($ticket = $tickets->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $ticket['ticket_id']; ?></td>
                                        <td><?php echo htmlspecialchars($ticket['username']); ?></td>
                                        <td><?php echo htmlspecialchars($ticket['train_number']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($ticket['departure_station']); ?> →
                                            <?php echo htmlspecialchars($ticket['arrival_station']); ?>
                                        </td>
                                        <td><?php echo date('d M Y, h:i A', strtotime($ticket['departure_time'])); ?></td>
                                        <td>
                                            <span class="ticket-status <?php echo $ticket['status']; ?>">
                                                <?php echo ucfirst($ticket['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d M Y, h:i A', strtotime($ticket['booking_date'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#detailsModal<?php echo $ticket['ticket_id']; ?>">
                                                <i class='bx bx-info-circle'></i>
                                            </button>
                                            <?php if ($ticket['status'] === 'active'): ?>
                                            <button class="btn btn-sm btn-success" onclick="verifyTicket(<?php echo $ticket['ticket_id']; ?>)">
                                                <i class='bx bx-check'></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="cancelTicket(<?php echo $ticket['ticket_id']; ?>)">
                                                <i class='bx bx-x'></i>
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <!-- Ticket Details Modal -->
                                    <div class="modal fade" id="detailsModal<?php echo $ticket['ticket_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Ticket Details</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <h6>Passenger Information</h6>
                                                        <p><strong>Username:</strong> <?php echo htmlspecialchars($ticket['username']); ?></p>
                                                    </div>
                                                    <div class="mb-3">
                                                        <h6>Journey Details</h6>
                                                        <p><strong>Train:</strong> <?php echo htmlspecialchars($ticket['train_number']); ?></p>
                                                        <p><strong>From:</strong> <?php echo htmlspecialchars($ticket['departure_station']); ?></p>
                                                        <p><strong>To:</strong> <?php echo htmlspecialchars($ticket['arrival_station']); ?></p>
                                                        <p><strong>Departure:</strong> <?php echo date('d M Y, h:i A', strtotime($ticket['departure_time'])); ?></p>
                                                        <p><strong>Arrival:</strong> <?php echo date('d M Y, h:i A', strtotime($ticket['arrival_time'])); ?></p>
                                                    </div>
                                                    <div class="mb-3">
                                                        <h6>Ticket Information</h6>
                                                        <p><strong>Price:</strong> RM <?php echo number_format($ticket['price'], 2); ?></p>
                                                        <p><strong>Status:</strong> <?php echo ucfirst($ticket['status']); ?></p>
                                                        <p><strong>Booking Date:</strong> <?php echo date('d M Y, h:i A', strtotime($ticket['booking_date'])); ?></p>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Verify Ticket Modal -->
    <div class="modal fade" id="verifyTicketModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Verify Ticket</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="ticket_actions.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="verify">
                        <div class="mb-3">
                            <label>Ticket ID</label>
                            <input type="text" class="form-control" name="ticket_id" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success">Verify Ticket</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function verifyTicket(ticketId) {
            if (confirm('Are you sure you want to verify this ticket?')) {
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
                idInput.value = ticketId;
                
                form.appendChild(actionInput);
                form.appendChild(idInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function cancelTicket(ticketId) {
            if (confirm('Are you sure you want to cancel this ticket?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'ticket_actions.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'cancel';
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'ticket_id';
                idInput.value = ticketId;
                
                form.appendChild(actionInput);
                form.appendChild(idInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
