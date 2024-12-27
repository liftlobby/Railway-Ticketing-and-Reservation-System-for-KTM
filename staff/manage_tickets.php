<?php
session_start();
require_once '../config/database.php';

// Check if staff is logged in
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

// Handle ticket edit
if (isset($_POST['edit_ticket'])) {
    $ticket_id = $_POST['ticket_id'];
    $schedule_id = $_POST['schedule_id'];
    $seat_number = $_POST['seat_number'];
    $status = $_POST['status'];
    $payment_status = $_POST['payment_status'];
    $payment_amount = $_POST['payment_amount'];
    
    $stmt = $conn->prepare("UPDATE tickets SET 
        schedule_id = ?, 
        seat_number = ?, 
        status = ?, 
        payment_status = ?, 
        payment_amount = ?,
        updated_at = NOW()
        WHERE ticket_id = ?");
    $stmt->bind_param("isssdi", $schedule_id, $seat_number, $status, $payment_status, $payment_amount, $ticket_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Ticket updated successfully!";
    } else {
        $_SESSION['error_message'] = "Error updating ticket: " . $conn->error;
    }
    header("Location: manage_tickets.php");
    exit();
}

// Handle ticket deletion
if (isset($_POST['delete_ticket'])) {
    $ticket_id = $_POST['ticket_id'];
    $stmt = $conn->prepare("DELETE FROM tickets WHERE ticket_id = ?");
    $stmt->bind_param("i", $ticket_id);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Ticket deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Error deleting ticket: " . $conn->error;
    }
    header("Location: manage_tickets.php");
    exit();
}

// Fetch all schedules for dropdown
$schedules_sql = "SELECT schedule_id, train_number, departure_station, arrival_station, 
                         departure_time, arrival_time, price 
                  FROM schedules 
                  ORDER BY departure_time ASC";
$schedules = $conn->query($schedules_sql);
$schedule_options = [];
while ($schedule = $schedules->fetch_assoc()) {
    $schedule_options[$schedule['schedule_id']] = sprintf(
        "Train %s: %s to %s (%s)",
        $schedule['train_number'],
        $schedule['departure_station'],
        $schedule['arrival_station'],
        date('d M Y, h:i A', strtotime($schedule['departure_time']))
    );
}

// Fetch all tickets with related information
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
        .alert {
            margin-top: 20px;
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
                    <?php if ($_SESSION['staff_role'] === 'admin'): ?>
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
                <h2>Manage Tickets</h2>
                
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success">
                        <?php 
                        echo $_SESSION['success_message']; 
                        unset($_SESSION['success_message']);
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger">
                        <?php 
                        echo $_SESSION['error_message']; 
                        unset($_SESSION['error_message']);
                        ?>
                    </div>
                <?php endif; ?>

                <div class="card mt-4">
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
                                        <th>Seat</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Payment</th>
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
                                        <td><?php echo htmlspecialchars($ticket['seat_number']); ?></td>
                                        <td>RM <?php echo number_format($ticket['payment_amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($ticket['status']); ?></td>
                                        <td><?php echo htmlspecialchars($ticket['payment_status']); ?></td>
                                        <td>
                                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" 
                                                    data-bs-target="#editModal<?php echo $ticket['ticket_id']; ?>">
                                                Edit
                                            </button>
                                            <form action="" method="POST" class="d-inline" 
                                                  onsubmit="return confirm('Are you sure you want to delete this ticket?');">
                                                <input type="hidden" name="ticket_id" value="<?php echo $ticket['ticket_id']; ?>">
                                                <button type="submit" name="delete_ticket" class="btn btn-danger btn-sm">Delete</button>
                                            </form>
                                        </td>
                                    </tr>

                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editModal<?php echo $ticket['ticket_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Ticket #<?php echo $ticket['ticket_id']; ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="" method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['ticket_id']; ?>">
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Schedule</label>
                                                            <select name="schedule_id" class="form-select" required>
                                                                <?php foreach ($schedule_options as $id => $text): ?>
                                                                    <option value="<?php echo $id; ?>" 
                                                                            <?php echo ($id == $ticket['schedule_id']) ? 'selected' : ''; ?>>
                                                                        <?php echo htmlspecialchars($text); ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Seat Number</label>
                                                            <input type="text" name="seat_number" class="form-control" 
                                                                   value="<?php echo htmlspecialchars($ticket['seat_number']); ?>" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Status</label>
                                                            <select name="status" class="form-select" required>
                                                                <option value="active" <?php echo ($ticket['status'] == 'active') ? 'selected' : ''; ?>>
                                                                    Active
                                                                </option>
                                                                <option value="cancelled" <?php echo ($ticket['status'] == 'cancelled') ? 'selected' : ''; ?>>
                                                                    Cancelled
                                                                </option>
                                                                <option value="completed" <?php echo ($ticket['status'] == 'completed') ? 'selected' : ''; ?>>
                                                                    Completed
                                                                </option>
                                                            </select>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Payment Status</label>
                                                            <select name="payment_status" class="form-select" required>
                                                                <option value="paid" <?php echo ($ticket['payment_status'] == 'paid') ? 'selected' : ''; ?>>
                                                                    Paid
                                                                </option>
                                                                <option value="pending" <?php echo ($ticket['payment_status'] == 'pending') ? 'selected' : ''; ?>>
                                                                    Pending
                                                                </option>
                                                                <option value="refunded" <?php echo ($ticket['payment_status'] == 'refunded') ? 'selected' : ''; ?>>
                                                                    Refunded
                                                                </option>
                                                            </select>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Payment Amount (RM)</label>
                                                            <input type="number" step="0.01" name="payment_amount" class="form-control" 
                                                                   value="<?php echo $ticket['payment_amount']; ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" name="edit_ticket" class="btn btn-primary">Save Changes</button>
                                                    </div>
                                                </form>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
