<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My History</title>
    <style>
        main {
            padding: 20px;
        }
        .trip-container {
            background: white;
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .trip-details {
            flex: 2;
        }
        .trip-details h3 {
            margin: 0;
            color: #333;
        }
        .trip-details p {
            margin: 5px 0;
            font-size: 14px;
            color: #666;
        }
        .status {
            flex: 1;
            text-align: right;
            font-weight: bold;
            color: #4CAF50;
        }

    </style>
</head>
<body>
    <?php include 'Head_and_Foot\header.php'; ?>

    <main>
        <h2>My Trips</h2>

        <div class="trip-container">
            <div class="trip-details">
                <h3>From: Johor Bahru Sentral</h3>
                <p>To: Kuala Lumpur Sentral</p>
                <p>Date: 9 December 2024, Monday</p>
            </div>
            <div class="status">Completed</div>
        </div>

        <div class="trip-container">
            <div class="trip-details">
                <h3>From: Ipoh</h3>
                <p>To: Butterworth</p>
                <p>Date: 22 December 2024, Monday</p>
            </div>
            <div class="status">Completed</div>
        </div>

        <div class="trip-container">
            <div class="trip-details">
                <h3>From: Seremban</h3>
                <p>To: Gemas</p>
                <p>Date: 31 December 2024, Monday</p>
            </div>
            <div class="status">Completed</div>
        </div>

        <div class="trip-container">
            <div class="trip-details">
                <h3>From: KL Sentral</h3>
                <p>To: Padang Besar</p>
                <p>Date: 5 January 2025, Sunday</p>
            </div>
            <div class="status">Pending</div>
        </div>
    </main>

    <?php include 'Head_and_Foot\footer.php'; ?>
</body>
</html>
