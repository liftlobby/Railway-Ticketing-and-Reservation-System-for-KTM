<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "railway_system";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username='$username' AND password='$password'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $username;
        header("Location: home.html");
        exit();
    } else {
        $error_message = "Invalid username or password, try <a href='login.php'>login</a> again.";
    }

    $conn->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login Page</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        background: 'image/komuter1.jpg';
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

    h2.center {
        text-align: center;
        color: #333333;}

    label {
        display: block;
        margin-bottom: 8px;
        font-weight: bold;
        color: #333333; }

    input[type="text"],
    input[type="email"],
    input[type="password"] {
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

</head>
<body>
    <?php include 'Head_and_Foot\header.php'; ?>
    <div class="container">
        <h2 class="center">Login Form</h2>
        <form action="login.php" method="post">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required><br><br>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required><br><br>
            <input type="submit" value="Login">
        </form>
        <p>Register <a href="register.php">here</a> if you have not.</p>
        <?php
        if (isset($error_message)) {
            echo "<p style='color:red;'>$error_message</p>";
        }
        ?>
        <br>
        <a href="index.php">HOME</a>
    </div>
    <?php include 'Head_and_Foot\footer.php'; ?>
</body>
</html>