<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Problem</title>
    <style>
        main {
            padding: 20px;
        }
        .form-container {
            background: white;
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 0 auto;
        }
        .form-container h2 {
            margin-bottom: 20px;
            color: #333;
        }
        .form-container label {
            display: block;
            margin: 10px 0 5px;
            font-size: 14px;
        }
        .form-container input,
        .form-container textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 14px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .form-container textarea {
            resize: vertical;
            min-height: 100px;
        }
        .form-container button {
            background-color: #003366; /* Dark blue button */
            color: white; /* White text */
            border: none;
            padding: 10px 20px;
            font-size: 14px;
            cursor: pointer;
            border-radius: 5px;
        }
        .form-container button:hover {
            background-color: #002244; /* Darker blue on hover */
        }
    </style>
</head>
<body>
        <?php include 'Head_and_Foot\header.php'; ?>
    <main>
        <div class="form-container">
            <h2>Report a Problem</h2>
            <form action="#" method="POST">
                <label for="name">Your Name:</label>
                <input type="text" id="name" name="name" required>

                <label for="email">Your Email:</label>
                <input type="email" id="email" name="email" required>

                <label for="problem">Problem Description:</label>
                <textarea id="problem" name="problem" required></textarea>

                <label for="ticket-number">Ticket Number (if applicable):</label>
                <input type="text" id="ticket-number" name="ticket-number">

                <button type="submit">Submit Report</button>
            </form>
        </div>
    </main>

    <?php include 'Head_and_Foot\footer.php'; ?>
</body>
</html>
