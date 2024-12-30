<?php
session_start();
require_once '../config/database.php';

// Check if staff is logged in
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch all users
$sql = "SELECT * FROM users ORDER BY created_at DESC";
$users = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - KTM Railway System</title>
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
        .user-status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 500;
        }
        .user-status.active {
            background-color: #d4edda;
            color: #155724;
        }
        .user-status.inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        .user-status.suspended {
            background-color: #fff3cd;
            color: #856404;
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
                    <a class="nav-link" href="manage_tickets.php">
                        <i class='bx bx-ticket'></i> Manage Tickets
                    </a>
                    <a class="nav-link active" href="manage_users.php">
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
                    <h2>Manage Users</h2>
                    <div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class='bx bx-user-plus'></i> Add New User
                        </button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>User ID</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Status</th>
                                        <th>Registration Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($user = $users->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $user['user_id']; ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['no_phone']); ?></td>
                                        <td>
                                            <span class="user-status <?php echo $user['account_status']; ?>">
                                                <?php echo ucfirst($user['account_status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d M Y, h:i A', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#detailsModal<?php echo $user['user_id']; ?>">
                                                <i class='bx bx-info-circle'></i>
                                            </button>
                                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $user['user_id']; ?>">
                                                <i class='bx bx-edit'></i>
                                            </button>
                                            <?php if ($user['account_status'] === 'active'): ?>
                                            <button class="btn btn-sm btn-danger" onclick="suspendUser(<?php echo $user['user_id']; ?>)">
                                                <i class='bx bx-block'></i>
                                            </button>
                                            <?php else: ?>
                                            <button class="btn btn-sm btn-success" onclick="activateUser(<?php echo $user['user_id']; ?>)">
                                                <i class='bx bx-check'></i>
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <!-- User Details Modal -->
                                    <div class="modal fade" id="detailsModal<?php echo $user['user_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">User Details</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <h6>Basic Information</h6>
                                                        <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                                                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                                                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['no_phone']); ?></p>
                                                        <p><strong>Status:</strong> <?php echo ucfirst($user['account_status']); ?></p>
                                                    </div>
                                                    <div class="mb-3">
                                                        <h6>Additional Information</h6>
                                                        <p><strong>Registration Date:</strong> <?php echo date('d M Y, h:i A', strtotime($user['created_at'])); ?></p>
                                                        <p><strong>Last Login:</strong> <?php echo $user['last_login'] ? date('d M Y, h:i A', strtotime($user['last_login'])) : 'Never'; ?></p>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Edit User Modal -->
                                    <div class="modal fade" id="editModal<?php echo $user['user_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit User</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="update_user.php" method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label">Username</label>
                                                            <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Email</label>
                                                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Phone</label>
                                                            <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($user['no_phone']); ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">New Password (leave blank to keep current)</label>
                                                            <input type="password" class="form-control" name="new_password" minlength="8">
                                                            <small class="text-muted">Minimum 8 characters</small>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Confirm New Password</label>
                                                            <input type="password" class="form-control" name="confirm_password" minlength="8">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Account Status</label>
                                                            <select class="form-select" name="account_status" required>
                                                                <option value="active" <?php echo $user['account_status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                                                <option value="inactive" <?php echo $user['account_status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                                <option value="suspended" <?php echo $user['account_status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary">Save Changes</button>
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

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="user_actions.php" method="post">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Username</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label>Phone</label>
                            <input type="text" class="form-control" name="no_phone" required>
                        </div>
                        <div class="mb-3">
                            <label>Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function suspendUser(userId) {
            if (confirm('Are you sure you want to suspend this user?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'user_actions.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'suspend';
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'user_id';
                idInput.value = userId;
                
                form.appendChild(actionInput);
                form.appendChild(idInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function activateUser(userId) {
            if (confirm('Are you sure you want to activate this user?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'user_actions.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'activate';
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'user_id';
                idInput.value = userId;
                
                form.appendChild(actionInput);
                form.appendChild(idInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
