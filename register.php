<!DOCTYPE html>
<html>
<head>
    <title>Registration Page</title>
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
        <h2 class="center">Registration Form</h2>
        <form action="authenticate.php" method="post">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required><br><br>
            <label for="email">Email:</label>
            <input type="text" id="email" name="email" required><br><br>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required><br><br>
            <input type="submit" value="Submit">
        </form>
    </div>
    <?php include 'Head_and_Foot\footer.php'; ?>
 
</body>
</html>