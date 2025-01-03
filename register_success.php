<?php
session_start();

// If there's no success message, redirect to home
if (!isset($_SESSION['registration_success'])) {
    header("Location: index.php");
    exit();
}

// Get the username from session
$username = isset($_SESSION['registered_username']) ? $_SESSION['registered_username'] : '';

// Clear the success message and username from session
$success_message = $_SESSION['registration_success'];
unset($_SESSION['registration_success']);
unset($_SESSION['registered_username']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful - KTM Railway System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .success-container {
            max-width: 600px;
            margin: 60px auto;
            padding: 40px 20px;
            text-align: center;
        }
        .success-icon {
            font-size: 80px;
            color: #28a745;
            margin-bottom: 20px;
            animation: scaleIn 0.5s ease-in-out;
        }
        .success-title {
            color: #343a40;
            margin-bottom: 20px;
            font-size: 32px;
            animation: fadeInUp 0.5s ease-in-out 0.2s both;
        }
        .success-message {
            color: #6c757d;
            margin-bottom: 30px;
            animation: fadeInUp 0.5s ease-in-out 0.4s both;
        }
        .next-steps {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            animation: fadeInUp 0.5s ease-in-out 0.6s both;
        }
        .next-steps h3 {
            color: #495057;
            font-size: 20px;
            margin-bottom: 15px;
        }
        .next-steps ul {
            text-align: left;
            list-style-type: none;
            padding: 0;
        }
        .next-steps ul li {
            margin-bottom: 10px;
            padding-left: 30px;
            position: relative;
            color: #6c757d;
        }
        .next-steps ul li i {
            position: absolute;
            left: 0;
            top: 2px;
            color: #28a745;
        }
        .action-buttons {
            animation: fadeInUp 0.5s ease-in-out 0.8s both;
        }
        .action-button {
            background-color: #0056b3;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 0 10px;
        }
        .action-button:hover {
            background-color: #004494;
            color: white;
            transform: translateY(-2px);
        }
        .action-button.secondary {
            background-color: #6c757d;
        }
        .action-button.secondary:hover {
            background-color: #5a6268;
        }
        @keyframes scaleIn {
            from {
                transform: scale(0);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .countdown {
            font-size: 14px;
            color: #6c757d;
            margin-top: 20px;
            animation: fadeInUp 0.5s ease-in-out 1s both;
        }
    </style>
</head>
<body>
    <?php include 'Head_and_Foot/header.php'; ?>
    
    <div class="success-container">
        <i class="bx bx-check-circle success-icon"></i>
        <h1 class="success-title">Registration Successful!</h1>
        <p class="success-message"><?php echo htmlspecialchars($success_message); ?></p>
        
        <div class="next-steps">
            <h3>Next Steps</h3>
            <ul>
                <li><i class="bx bx-log-in"></i> Log in to your account</li>
                <li><i class="bx bx-user"></i> Complete your profile information</li>
                <li><i class="bx bx-train"></i> Start booking your train tickets</li>
                <li><i class="bx bx-bell"></i> Enable notifications for updates</li>
            </ul>
        </div>
        
        <div class="action-buttons">
            <a href="login.php" class="action-button">
                <i class="bx bx-log-in"></i>
                Login Now
            </a>
            <a href="index.php" class="action-button secondary">
                <i class="bx bx-home"></i>
                Go to Homepage
            </a>
        </div>
        
        <div class="countdown">
            Redirecting to login page in <span id="countdown">10</span> seconds...
        </div>
    </div>
    
    <?php include 'Head_and_Foot/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Countdown timer
        let timeLeft = 10;
        const countdownElement = document.getElementById('countdown');
        
        const countdownTimer = setInterval(() => {
            timeLeft--;
            countdownElement.textContent = timeLeft;
            
            if (timeLeft <= 0) {
                clearInterval(countdownTimer);
                window.location.href = 'login.php';
            }
        }, 1000);
    </script>
</body>
</html>
