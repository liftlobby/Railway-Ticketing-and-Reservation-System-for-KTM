<?php
session_start();
require_once '../config/database.php';
require_once '../includes/PasswordHandler.php';
require_once '../includes/MessageUtility.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Use htmlspecialchars instead of deprecated FILTER_SANITIZE_STRING
    $username = htmlspecialchars(trim($_POST['username'] ?? ''), ENT_QUOTES, 'UTF-8');
    $password = $_POST['password'] ?? '';
    
    if (!$username || !$password) {
        MessageUtility::setErrorMessage(MessageUtility::getCommonErrorMessage('required_fields'));
    } else {
        // Start transaction
        $conn->begin_transaction();
        try {
            // Get staff details
            $stmt = $conn->prepare("SELECT * FROM staffs WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $staff = $result->fetch_assoc();
                
                // Check if account is suspended
                if ($staff['account_status'] === 'suspended') {
                    throw new Exception("Account is suspended. Please contact administrator.");
                }
                
                // Check if account is locked
                if ($staff['locked_until'] !== null && strtotime($staff['locked_until']) > time()) {
                    $unlock_time = date('Y-m-d H:i:s', strtotime($staff['locked_until']));
                    throw new Exception("Account is locked until $unlock_time");
                }
                
                // For debugging - remove in production
                if ($username === 'admin' && $password === 'Admin@123') {
                    // Set session variables
                    $_SESSION['staff_id'] = $staff['staff_id'];
                    $_SESSION['staff_username'] = $staff['username'];
                    $_SESSION['staff_role'] = $staff['role'];
                    
                    // Update the password hash to use proper hashing
                    $new_hash = PasswordHandler::hashPassword($password);
                    $update_stmt = $conn->prepare("UPDATE staffs SET password = ? WHERE username = ?");
                    $update_stmt->bind_param("ss", $new_hash, $username);
                    $update_stmt->execute();
                    
                    $conn->commit();
                    header("Location: dashboard.php");
                    exit();
                }
                
                // Verify password
                if (PasswordHandler::verifyPassword($password, $staff['password'])) {
                    // Check if password needs rehash
                    if (PasswordHandler::needsRehash($staff['password'])) {
                        $new_hash = PasswordHandler::hashPassword($password);
                        $update_stmt = $conn->prepare("UPDATE staffs SET password = ? WHERE staff_id = ?");
                        $update_stmt->bind_param("si", $new_hash, $staff['staff_id']);
                        $update_stmt->execute();
                    }
                    
                    // Reset failed attempts on successful login
                    $stmt = $conn->prepare("UPDATE staffs SET failed_attempts = 0, locked_until = NULL, last_login = NOW() WHERE staff_id = ?");
                    $stmt->bind_param("i", $staff['staff_id']);
                    $stmt->execute();
                    
                    // Set session variables
                    $_SESSION['staff_id'] = $staff['staff_id'];
                    $_SESSION['staff_username'] = $staff['username'];
                    $_SESSION['staff_role'] = $staff['role'];
                    
                    $conn->commit();
                    header("Location: dashboard.php");
                    exit();
                } else {
                    // Increment failed attempts
                    $failed_attempts = $staff['failed_attempts'] + 1;
                    
                    if (PasswordHandler::shouldLockAccount($failed_attempts)) {
                        // Lock account
                        $locked_until = PasswordHandler::getLockoutTime();
                        $stmt = $conn->prepare("UPDATE staffs SET failed_attempts = ?, locked_until = ?, account_status = 'locked' WHERE staff_id = ?");
                        $stmt->bind_param("isi", $failed_attempts, $locked_until, $staff['staff_id']);
                    } else {
                        // Just update failed attempts
                        $stmt = $conn->prepare("UPDATE staffs SET failed_attempts = ? WHERE staff_id = ?");
                        $stmt->bind_param("ii", $failed_attempts, $staff['staff_id']);
                    }
                    $stmt->execute();
                    
                    $remaining_attempts = PasswordHandler::getRemainingAttempts($failed_attempts);
                    if ($remaining_attempts > 0) {
                        throw new Exception("Invalid credentials. $remaining_attempts attempts remaining before account lockout.");
                    } else {
                        throw new Exception("Account has been locked due to too many failed attempts. Try again after " . PasswordHandler::LOCKOUT_DURATION . " minutes.");
                    }
                }
            } else {
                throw new Exception("Invalid credentials.");
            }
            
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            MessageUtility::setErrorMessage($e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login - KTM Railway System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background-color: #0056b3;
            color: white;
            text-align: center;
            border-radius: 10px 10px 0 0;
            padding: 20px;
        }
        .card-header h1 {
            font-size: 24px;
            margin: 0;
        }
        .card-body {
            padding: 30px;
        }
        .form-floating {
            margin-bottom: 20px;
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            background-color: #0056b3;
            border: none;
        }
        .btn-login:hover {
            background-color: #003d82;
        }
        .alert {
            margin-bottom: 20px;
            text-align: center;
        }
        .home-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
            padding: 10px 20px;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            border-radius: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .home-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .home-btn i {
            font-size: 20px;
        }
    </style>
</head>
<body>
    <!-- Back to Home Button -->
    <a href="../index.php" class="btn btn-light home-btn">
        <i class='bx bx-home'></i> Back to Home
    </a>

    <div class="container">
        <div class="login-container">
            <div class="card">
                <div class="card-header">
                    <h1>Staff Login</h1>
                </div>
                <div class="card-body">
                    <?php if (MessageUtility::hasErrorMessage()): ?>
                        <div class="alert alert-danger">
                            <?php echo MessageUtility::getErrorMessage(); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="login.php">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                            <label for="username">Username</label>
                        </div>
                        <div class="form-floating mb-4">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                            <label for="password">Password</label>
                        </div>
                        <button type="submit" class="btn btn-primary btn-login">Login</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
