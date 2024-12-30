<?php
session_start();
require_once 'config/database.php';
require_once 'includes/PasswordUtility.php';
require_once 'includes/MessageUtility.php';

// If user is already logged in, redirect to index
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize inputs
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'no_phone', FILTER_SANITIZE_STRING);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    $errors = [];

    // Validate username
    if (!preg_match('/^[a-zA-Z0-9_]{4,20}$/', $username)) {
        $errors[] = MessageUtility::getCommonErrorMessage('username_invalid');
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = MessageUtility::getCommonErrorMessage('invalid_email');
    }

    // Validate phone number
    if (!preg_match('/^[0-9]{10,15}$/', $phone)) {
        $errors[] = MessageUtility::getCommonErrorMessage('invalid_phone');
    }

    // Validate password match
    if ($password !== $confirm_password) {
        $errors[] = MessageUtility::getCommonErrorMessage('passwords_not_match');
    }

    // Validate password strength
    $password_errors = PasswordUtility::validatePasswordStrength($password);
    $errors = array_merge($errors, $password_errors);

    if (empty($errors)) {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            MessageUtility::setErrorMessage(MessageUtility::getCommonErrorMessage('username_exists'));
            header("Location: register.php");
            exit();
        }

        // Check if email already exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            MessageUtility::setErrorMessage(MessageUtility::getCommonErrorMessage('email_exists'));
            header("Location: register.php");
            exit();
        }

        try {
            // Hash password
            $hashed_password = PasswordUtility::hashPassword($password);

            // Begin transaction
            $conn->begin_transaction();

            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (username, email, no_phone, password, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssss", $username, $email, $phone, $hashed_password);

            if ($stmt->execute()) {
                // Log the registration
                $user_id = $conn->insert_id;
                $logStmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) VALUES (?, 'registration', 'New user registration', ?, ?)");
                $logStmt->bind_param("iss", $user_id, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
                $logStmt->execute();

                // Commit transaction
                $conn->commit();

                MessageUtility::setSuccessMessage("Registration successful! Please login with your credentials.");
                header("Location: login.php");
                exit();
            } else {
                throw new Exception("Registration failed");
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            MessageUtility::setErrorMessage(MessageUtility::getCommonErrorMessage('server_error'));
        }
    } else {
        foreach ($errors as $error) {
            MessageUtility::setErrorMessage($error);
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Registration Page</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        background: url('image/komuter1.jpg');
        background-size: cover;
        margin: 0;
        padding: 0;
        display: grid;
        height: 100vh;
    }

    .container {
        background-color: rgba(255, 255, 255, 0.9); 
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        width: 90%;
        max-width: 400px;
        text-align: center;
        justify-self: center;    
        align-self: center;
    }

    .error-message {
        color: #dc3545;
        margin-bottom: 15px;
        text-align: left;
        padding: 10px;
        background-color: #ffe6e6;
        border-radius: 4px;
    }

    .password-requirements {
        text-align: left;
        margin: 10px 0;
        font-size: 0.9em;
        color: #666;
    }

    h2.center {
        text-align: center;
        color: #333333;
    }

    label {
        display: block;
        margin-bottom: 8px;
        font-weight: bold;
        color: #333333;
    }

    input[type="text"],
    input[type="email"],
    input[type="password"],
    input[type="tel"] {
        width: calc(100% - 20px);
        padding: 10px;
        margin-bottom: 20px;
        border: 1px solid #cccccc; 
        border-radius: 5px;
    }

    input[type="submit"] {
        background-color: #007bff; 
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        width: 100%;
    }

    input[type="submit"]:hover {
        background-color: #0056b3;
    }
    </style>
    <script>
    function validateForm() {
        var username = document.getElementById('username').value.trim();
        var email = document.getElementById('email').value.trim();
        var phone = document.getElementById('no_phone').value.trim();
        var password = document.getElementById('password').value;
        var confirmPassword = document.getElementById('confirm_password').value;
        
        // Username validation
        if (!/^[a-zA-Z0-9_]{4,20}$/.test(username)) {
            alert('Username must be 4-20 characters and can only contain letters, numbers, and underscores');
            return false;
        }
        
        // Email validation
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            alert('Please enter a valid email address');
            return false;
        }
        
        // Phone validation
        if (!/^[0-9]{10,15}$/.test(phone)) {
            alert('Please enter a valid phone number (10-15 digits)');
            return false;
        }
        
        // Password validation
        if (password.length < 8) {
            alert('Password must be at least 8 characters long');
            return false;
        }
        
        if (!/[A-Z]/.test(password)) {
            alert('Password must contain at least one uppercase letter');
            return false;
        }
        
        if (!/[a-z]/.test(password)) {
            alert('Password must contain at least one lowercase letter');
            return false;
        }
        
        if (!/[0-9]/.test(password)) {
            alert('Password must contain at least one number');
            return false;
        }
        
        if (!/[^A-Za-z0-9]/.test(password)) {
            alert('Password must contain at least one special character');
            return false;
        }
        
        if (password !== confirmPassword) {
            alert('Passwords do not match');
            return false;
        }
        
        return true;
    }
    </script>
</head>
<body>
    <?php include 'Head_and_Foot\header.php'; ?>
    <div class="container">
        <h2 class="center">Registration Form</h2>
        <?php echo MessageUtility::displayMessages(); ?>
        
        <form action="register.php" method="post" onsubmit="return validateForm()">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" 
                   pattern="[a-zA-Z0-9_]{4,20}" 
                   title="4-20 characters, letters, numbers and underscore only" 
                   value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" 
                   required>
            
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" 
                   value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" 
                   required>
            
            <label for="no_phone">Phone Number:</label>
            <input type="tel" id="no_phone" name="no_phone" 
                   pattern="[0-9]{10,15}" 
                   title="Phone number (10-15 digits)" 
                   value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>" 
                   required>
            
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            
            <label for="confirm_password">Confirm Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
            
            <div class="password-requirements">
                Password must contain:
                <ul>
                    <li>At least 8 characters</li>
                    <li>At least one uppercase letter</li>
                    <li>At least one lowercase letter</li>
                    <li>At least one number</li>
                    <li>At least one special character</li>
                </ul>
            </div>
            
            <input type="submit" value="Register">
        </form>
        
        <div class="login-link" style="text-align: center; margin-top: 15px;">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>
    <?php include 'Head_and_Foot\footer.php'; ?>
</body>
</html>