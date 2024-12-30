<?php
session_start();
require_once 'config/database.php';
require_once 'includes/PasswordHandler.php';
require_once 'includes/MessageUtility.php';

// If user is already logged in, redirect to index
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize inputs
    $username = htmlspecialchars(trim($_POST['username'] ?? ''), ENT_QUOTES, 'UTF-8');
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        MessageUtility::setErrorMessage(MessageUtility::getCommonErrorMessage('required_fields'));
    } else {
        // Start transaction
        $conn->begin_transaction();
        try {
            // Get user details
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Check if account is suspended
                if ($user['account_status'] === 'suspended') {
                    throw new Exception("Account is suspended. Please contact administrator.");
                }
                
                // Check if account is locked
                if ($user['locked_until'] !== null && strtotime($user['locked_until']) > time()) {
                    $unlock_time = date('Y-m-d H:i:s', strtotime($user['locked_until']));
                    throw new Exception("Account is locked until $unlock_time");
                }
                
                // Verify password
                if (PasswordHandler::verifyPassword($password, $user['password'])) {
                    // Check if password needs rehash
                    if (PasswordHandler::needsRehash($user['password'])) {
                        $new_hash = PasswordHandler::hashPassword($password);
                        $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                        $update_stmt->bind_param("si", $new_hash, $user['user_id']);
                        $update_stmt->execute();
                    }
                    
                    // Reset failed attempts on successful login
                    $stmt = $conn->prepare("UPDATE users SET failed_attempts = 0, locked_until = NULL, last_login = NOW() WHERE user_id = ?");
                    $stmt->bind_param("i", $user['user_id']);
                    $stmt->execute();
                    
                    // Set session variables
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['last_activity'] = time();
                    $_SESSION['expire_time'] = 30 * 60; // 30 minutes
                    
                    $conn->commit();
                    header("Location: index.php");
                    exit();
                } else {
                    // Increment failed attempts
                    $failed_attempts = $user['failed_attempts'] + 1;
                    
                    if (PasswordHandler::shouldLockAccount($failed_attempts)) {
                        // Lock account
                        $locked_until = PasswordHandler::getLockoutTime();
                        $stmt = $conn->prepare("UPDATE users SET failed_attempts = ?, locked_until = ?, account_status = 'locked' WHERE user_id = ?");
                        $stmt->bind_param("isi", $failed_attempts, $locked_until, $user['user_id']);
                    } else {
                        // Just update failed attempts
                        $stmt = $conn->prepare("UPDATE users SET failed_attempts = ? WHERE user_id = ?");
                        $stmt->bind_param("ii", $failed_attempts, $user['user_id']);
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

// Check for session timeout
if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > $_SESSION['expire_time']) {
    session_unset();
    session_destroy();
    MessageUtility::setWarningMessage(MessageUtility::getCommonErrorMessage('session_expired'));
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - KTM Railway System</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 40px auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .login-container h2 {
            color: #003366;
            text-align: center;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #003366;
        }

        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .login-button {
            width: 100%;
            padding: 10px;
            background-color: #003366;
            color: #ffcc00;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        .login-button:hover {
            background-color: #ffcc00;
            color: #003366;
        }

        .error-message {
            color: #dc3545;
            text-align: center;
            margin-bottom: 15px;
        }

        .register-link {
            text-align: center;
            margin-top: 15px;
        }

        .register-link a {
            color: #003366;
            text-decoration: none;
        }

        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?php require_once 'Head_and_Foot/header.php'; ?>

    <div class="login-container">
        <h2>Login</h2>
        <?php echo MessageUtility::displayMessages(); ?>

        <form action="login.php" method="post" onsubmit="return validateForm()">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="login-button">Login</button>
        </form>

        <div class="register-link">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </div>

    <script>
    function validateForm() {
        var username = document.getElementById('username').value.trim();
        var password = document.getElementById('password').value;
        
        if (username === '') {
            alert('Please enter your username');
            return false;
        }
        
        if (password === '') {
            alert('Please enter your password');
            return false;
        }
        
        return true;
    }
    </script>

    <?php require_once 'Head_and_Foot/footer.php'; ?>
</body>
</html>