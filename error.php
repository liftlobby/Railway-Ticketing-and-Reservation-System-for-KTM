<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTM Help Center - Error</title>
    <style>
        main {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .error-container {
            background: white;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .error-container h2 {
            color: #cc0000; /* Red */
            font-size: 24px;
            margin-bottom: 10px;
        }
        .error-container p {
            color: #333;
            font-size: 16px;
            margin-bottom: 20px;
        }
        .error-container a {
            color: #003366; /* Dark blue */
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php include 'Head_and_Foot\header.php'; ?>

    <main>
        <div class="error-container">
            <h2>Error: Unable to Process Request</h2>
            <p>We encountered an issue while processing your request. Please try again later or contact our support team if the problem persists.</p>
            <p><a href="index.php">Return to Home</a></p>
        </div>
    </main>

    <?php include 'Head_and_Foot\footer.php'; ?>
</body>
</html>
