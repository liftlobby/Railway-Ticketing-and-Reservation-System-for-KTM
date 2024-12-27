<?php
session_start();
require_once '../config/database.php';

// Check if staff is logged in
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

// Handle schedule creation
if (isset($_POST['create_schedule'])) {
    $train_number = $_POST['train_number'];
    $departure_station = $_POST['departure_station'];
    $arrival_station = $_POST['arrival_station'];
    $departure_time = $_POST['departure_time'];
    $arrival_time = $_POST['arrival_time'];
    $price = $_POST['price'];
    $available_seats = $_POST['available_seats'];
    
    $stmt = $conn->prepare("INSERT INTO schedules (train_number, departure_station, arrival_station, departure_time, arrival_time, price, available_seats) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssii", $train_number, $departure_station, $arrival_station, $departure_time, $arrival_time, $price, $available_seats);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Schedule created successfully!";
    } else {
        $_SESSION['error_message'] = "Error creating schedule: " . $conn->error;
    }
    header("Location: manage_schedules.php");
    exit();
}

// Handle schedule deletion
if (isset($_POST['delete_schedule'])) {
    $schedule_id = $_POST['schedule_id'];
    $stmt = $conn->prepare("DELETE FROM schedules WHERE schedule_id = ?");
    $stmt->bind_param("i", $schedule_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Schedule deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Error deleting schedule: " . $conn->error;
    }
    header("Location: manage_schedules.php");
    exit();
}

// Handle schedule update
if (isset($_POST['update_schedule'])) {
    $schedule_id = $_POST['schedule_id'];
    $train_number = $_POST['train_number'];
    $departure_station = $_POST['departure_station'];
    $arrival_station = $_POST['arrival_station'];
    $departure_time = $_POST['departure_time'];
    $arrival_time = $_POST['arrival_time'];
    $price = $_POST['price'];
    $available_seats = $_POST['available_seats'];
    
    $stmt = $conn->prepare("UPDATE schedules SET train_number = ?, departure_station = ?, arrival_station = ?, departure_time = ?, arrival_time = ?, price = ?, available_seats = ? WHERE schedule_id = ?");
    $stmt->bind_param("sssssiii", $train_number, $departure_station, $arrival_station, $departure_time, $arrival_time, $price, $available_seats, $schedule_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Schedule updated successfully!";
    } else {
        $_SESSION['error_message'] = "Error updating schedule: " . $conn->error;
    }
    header("Location: manage_schedules.php");
    exit();
}

// Fetch all schedules
$schedules = $conn->query("SELECT * FROM schedules ORDER BY departure_time");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Schedules - KTM Railway System</title>
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
                    <a class="nav-link active" href="manage_schedules.php">
                        <i class='bx bx-time-five'></i> Manage Schedules
                    </a>
                    <a class="nav-link" href="manage_tickets.php">
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
                <h2>Manage Schedules</h2>

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
                
                <!-- Add Schedule Button -->
                <button class="btn btn-primary mt-3 mb-3" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
                    <i class='bx bx-plus'></i> Add New Schedule
                </button>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Train Number</th>
                                        <th>Departure Station</th>
                                        <th>Arrival Station</th>
                                        <th>Departure Time</th>
                                        <th>Arrival Time</th>
                                        <th>Price (RM)</th>
                                        <th>Available Seats</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($schedule = $schedules->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($schedule['train_number']); ?></td>
                                        <td><?php echo htmlspecialchars($schedule['departure_station']); ?></td>
                                        <td><?php echo htmlspecialchars($schedule['arrival_station']); ?></td>
                                        <td><?php echo date('d M Y, h:i A', strtotime($schedule['departure_time'])); ?></td>
                                        <td><?php echo date('d M Y, h:i A', strtotime($schedule['arrival_time'])); ?></td>
                                        <td><?php echo number_format($schedule['price'], 2); ?></td>
                                        <td><?php echo $schedule['available_seats']; ?></td>
                                        <td>
                                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" 
                                                    data-bs-target="#editModal<?php echo $schedule['schedule_id']; ?>">
                                                Edit
                                            </button>
                                            <form action="" method="POST" class="d-inline" 
                                                  onsubmit="return confirm('Are you sure you want to delete this schedule?');">
                                                <input type="hidden" name="schedule_id" value="<?php echo $schedule['schedule_id']; ?>">
                                                <button type="submit" name="delete_schedule" class="btn btn-danger btn-sm">Delete</button>
                                            </form>
                                        </td>
                                    </tr>

                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editModal<?php echo $schedule['schedule_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Schedule</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="" method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="schedule_id" value="<?php echo $schedule['schedule_id']; ?>">
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Train Number</label>
                                                            <input type="text" name="train_number" class="form-control" 
                                                                   value="<?php echo htmlspecialchars($schedule['train_number']); ?>" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Departure Station</label>
                                                            <input type="text" name="departure_station" class="form-control" 
                                                                   value="<?php echo htmlspecialchars($schedule['departure_station']); ?>" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Arrival Station</label>
                                                            <input type="text" name="arrival_station" class="form-control" 
                                                                   value="<?php echo htmlspecialchars($schedule['arrival_station']); ?>" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Departure Time</label>
                                                            <input type="datetime-local" name="departure_time" class="form-control" 
                                                                   value="<?php echo date('Y-m-d\TH:i', strtotime($schedule['departure_time'])); ?>" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Arrival Time</label>
                                                            <input type="datetime-local" name="arrival_time" class="form-control" 
                                                                   value="<?php echo date('Y-m-d\TH:i', strtotime($schedule['arrival_time'])); ?>" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Price (RM)</label>
                                                            <input type="number" step="0.01" name="price" class="form-control" 
                                                                   value="<?php echo $schedule['price']; ?>" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Available Seats</label>
                                                            <input type="number" name="available_seats" class="form-control" 
                                                                   value="<?php echo $schedule['available_seats']; ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" name="update_schedule" class="btn btn-primary">Save Changes</button>
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

    <!-- Add Schedule Modal -->
    <div class="modal fade" id="addScheduleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Schedule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Train Number</label>
                            <input type="text" name="train_number" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Departure Station</label>
                            <input type="text" name="departure_station" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Arrival Station</label>
                            <input type="text" name="arrival_station" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Departure Time</label>
                            <input type="datetime-local" name="departure_time" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Arrival Time</label>
                            <input type="datetime-local" name="arrival_time" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Price (RM)</label>
                            <input type="number" step="0.01" name="price" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Available Seats</label>
                            <input type="number" name="available_seats" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="create_schedule" class="btn btn-primary">Create Schedule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
