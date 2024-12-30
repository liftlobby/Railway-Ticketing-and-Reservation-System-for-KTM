<?php
session_start();
require_once '../config/database.php';
require_once '../includes/PasswordPolicy.php';
require_once '../includes/MessageUtility.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['staff_id']) || $_SESSION['staff_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        try {
            // Start transaction
            $conn->begin_transaction();

            // Check for duplicate username
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM staffs WHERE username = ?");
            $stmt->bind_param("s", $_POST['username']);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['count'] > 0) {
                throw new Exception("Username already exists. Please choose a different username.");
            }

            // Check for duplicate email
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM staffs WHERE email = ?");
            $stmt->bind_param("s", $_POST['email']);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['count'] > 0) {
                throw new Exception("Email address already registered. Please use a different email.");
            }

            // Generate a secure password
            $password = PasswordPolicy::generateSecurePassword();
            $hashed_password = PasswordPolicy::hashPassword($password);

            // Insert new staff
            $stmt = $conn->prepare("INSERT INTO staffs (username, password, email, role, account_status) VALUES (?, ?, ?, ?, 'active')");
            $stmt->bind_param("ssss", 
                $_POST['username'],
                $hashed_password,
                $_POST['email'],
                $_POST['role']
            );
            $stmt->execute();

            $conn->commit();
            $_SESSION['success'] = "Staff added successfully! Initial password: " . $password;
            
            // TODO: Send email with credentials
            // For now, we'll just show the password in the success message
            
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = $e->getMessage();
        }
    } elseif ($action === 'edit') {
        try {
            $conn->begin_transaction();

            // Check for duplicate username except current staff
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM staffs WHERE username = ? AND staff_id != ?");
            $stmt->bind_param("si", $_POST['username'], $_POST['staff_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['count'] > 0) {
                throw new Exception("Username already exists. Please choose a different username.");
            }

            // Check for duplicate email except current staff
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM staffs WHERE email = ? AND staff_id != ?");
            $stmt->bind_param("si", $_POST['email'], $_POST['staff_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['count'] > 0) {
                throw new Exception("Email address already registered. Please use a different email.");
            }

            $stmt = $conn->prepare("UPDATE staffs SET username = ?, email = ?, role = ? WHERE staff_id = ?");
            $stmt->bind_param("sssi", 
                $_POST['username'],
                $_POST['email'],
                $_POST['role'],
                $_POST['staff_id']
            );
            $stmt->execute();

            $conn->commit();
            $_SESSION['success'] = "Staff updated successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = $e->getMessage();
        }
    } elseif ($action === 'reset_password') {
        try {
            $conn->begin_transaction();

            // Generate new password
            $new_password = PasswordPolicy::generateSecurePassword();
            $hashed_password = PasswordPolicy::hashPassword($new_password);

            $stmt = $conn->prepare("UPDATE staffs SET password = ? WHERE staff_id = ?");
            $stmt->bind_param("si", $hashed_password, $_POST['staff_id']);
            $stmt->execute();

            $conn->commit();
            $_SESSION['success'] = "Password reset successfully! New password: " . $new_password;
            
            // TODO: Send email with new password
            // For now, we'll just show it in the success message
            
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = $e->getMessage();
        }
    } elseif ($action === 'toggle_status') {
        try {
            // Check if trying to suspend own account
            if ($_POST['staff_id'] == $_SESSION['staff_id']) {
                throw new Exception("You cannot suspend your own account.");
            }

            $new_status = $_POST['current_status'] === 'active' ? 'suspended' : 'active';
            $stmt = $conn->prepare("UPDATE staffs SET account_status = ? WHERE staff_id = ?");
            $stmt->bind_param("si", $new_status, $_POST['staff_id']);
            $stmt->execute();
            
            $status_message = $new_status === 'active' ? 'activated' : 'suspended';
            $_SESSION['success'] = "Staff account {$status_message} successfully!";
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
    }
}

// Fetch all staff members
$stmt = $conn->prepare("SELECT staff_id, username, email, role, account_status, created_at, last_login FROM staffs ORDER BY username");
$stmt->execute();
$result = $stmt->get_result();
$staffs = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Staff - KTM Railway System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <style>
        body {
            overflow-x: hidden;
        }
        .sidebar {
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding: 20px;
            background-color: #343a40;
            color: white;
            width: 250px;
            z-index: 1000;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
            width: calc(100% - 250px);
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
            border-radius: 5px;
            padding: 8px 15px;
        }
        .staff-status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 500;
        }
        .staff-status.active {
            background-color: #d4edda;
            color: #155724;
        }
        .staff-status.suspended {
            background-color: #f8d7da;
            color: #721c24;
        }
        .staff-status.locked {
            background-color: #fff3cd;
            color: #856404;
        }
        .table th {
            background-color: #f8f9fa;
        }
        .action-buttons .btn {
            margin: 0 2px;
        }
    </style>
</head>
<body>
    <div class="container-fluid p-0">
        <div class="row g-0">
            <!-- Include the sidebar -->
            <?php include 'sidebar.php'; ?>

            <!-- Main Content -->
            <div class="main-content">
                <div class="container">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Manage Staff</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStaffModal">
                            <i class='bx bx-user-plus'></i> Add New Staff
                        </button>
                    </div>

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php 
                            echo $_SESSION['success'];
                            unset($_SESSION['success']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php 
                            echo $_SESSION['error'];
                            unset($_SESSION['error']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Last Login</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($staffs as $staff): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($staff['username']); ?></td>
                                            <td><?php echo htmlspecialchars($staff['email']); ?></td>
                                            <td><span class="badge bg-info"><?php echo ucfirst($staff['role']); ?></span></td>
                                            <td>
                                                <span class="staff-status <?php echo $staff['account_status']; ?>">
                                                    <?php echo ucfirst($staff['account_status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $staff['last_login'] ? date('d M Y, h:i A', strtotime($staff['last_login'])) : 'Never'; ?></td>
                                            <td class="action-buttons">
                                                <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewStaffModal<?php echo $staff['staff_id']; ?>">
                                                    <i class='bx bx-info-circle'></i>
                                                </button>
                                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editStaffModal<?php echo $staff['staff_id']; ?>">
                                                    <i class='bx bx-edit'></i>
                                                </button>
                                                <button class="btn btn-sm btn-secondary" onclick="resetPassword(<?php echo $staff['staff_id']; ?>)">
                                                    <i class='bx bx-key'></i>
                                                </button>
                                                <?php if ($staff['staff_id'] != $_SESSION['staff_id']): ?>
                                                    <?php if ($staff['account_status'] === 'active'): ?>
                                                        <button class="btn btn-sm btn-danger" onclick="toggleStatus(<?php echo $staff['staff_id']; ?>, 'active')">
                                                            <i class='bx bx-block'></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm btn-success" onclick="toggleStatus(<?php echo $staff['staff_id']; ?>, 'suspended')">
                                                            <i class='bx bx-check'></i>
                                                        </button>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                        </tr>

                                        <!-- View Staff Modal -->
                                        <div class="modal fade" id="viewStaffModal<?php echo $staff['staff_id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Staff Details</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <h6>Basic Information</h6>
                                                            <p><strong>Username:</strong> <?php echo htmlspecialchars($staff['username']); ?></p>
                                                            <p><strong>Email:</strong> <?php echo htmlspecialchars($staff['email']); ?></p>
                                                            <p><strong>Role:</strong> <?php echo ucfirst($staff['role']); ?></p>
                                                            <p><strong>Status:</strong> <?php echo ucfirst($staff['account_status']); ?></p>
                                                        </div>
                                                        <div class="mb-3">
                                                            <h6>Additional Information</h6>
                                                            <p><strong>Created:</strong> <?php echo date('d M Y, h:i A', strtotime($staff['created_at'])); ?></p>
                                                            <p><strong>Last Login:</strong> <?php echo $staff['last_login'] ? date('d M Y, h:i A', strtotime($staff['last_login'])) : 'Never'; ?></p>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Edit Staff Modal -->
                                        <div class="modal fade" id="editStaffModal<?php echo $staff['staff_id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Staff</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form action="manage_staff.php" method="POST">
                                                        <input type="hidden" name="action" value="edit">
                                                        <input type="hidden" name="staff_id" value="<?php echo $staff['staff_id']; ?>">
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label">Username</label>
                                                                <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($staff['username']); ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Email</label>
                                                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($staff['email']); ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Role</label>
                                                                <select class="form-select" name="role" required>
                                                                    <option value="admin" <?php echo $staff['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                                    <option value="staff" <?php echo $staff['role'] === 'staff' ? 'selected' : ''; ?>>Staff</option>
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
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Staff Modal -->
    <div class="modal fade" id="addStaffModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Staff</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="manage_staff.php" method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" required>
                                <option value="admin">Admin</option>
                                <option value="staff" selected>Staff</option>
                            </select>
                        </div>
                        <div class="alert alert-info">
                            <i class='bx bx-info-circle'></i>
                            A secure password will be generated automatically and displayed after staff creation.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Staff</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function resetPassword(staffId) {
            if (confirm('Are you sure you want to reset this staff member\'s password?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'manage_staff.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'reset_password';
                
                const staffInput = document.createElement('input');
                staffInput.type = 'hidden';
                staffInput.name = 'staff_id';
                staffInput.value = staffId;
                
                form.appendChild(actionInput);
                form.appendChild(staffInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function toggleStatus(staffId, currentStatus) {
            if (confirm('Are you sure you want to ' + (currentStatus === 'active' ? 'suspend' : 'activate') + ' this staff member?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'manage_staff.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'toggle_status';
                
                const staffInput = document.createElement('input');
                staffInput.type = 'hidden';
                staffInput.name = 'staff_id';
                staffInput.value = staffId;
                
                const statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'current_status';
                statusInput.value = currentStatus;
                
                form.appendChild(actionInput);
                form.appendChild(staffInput);
                form.appendChild(statusInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
