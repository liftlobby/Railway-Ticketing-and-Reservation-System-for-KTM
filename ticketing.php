<!-- Ticketing & Reservation Page -->
<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Debug information
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fetch available schedules with seat information
$sql = "SELECT s.*, 
               COALESCE(s.available_seats, 50) as available_seats,
               (COALESCE(s.available_seats, 50) > 0) as is_available
        FROM schedules s 
        WHERE s.departure_time > NOW() 
        ORDER BY s.departure_time ASC";

$result = $conn->query($sql);
$schedules = [];
while ($row = $result->fetch_assoc()) {
    $schedules[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTM Ticketing & Reservation</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .schedule-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }

        .schedule-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .schedule-info {
            flex: 1;
        }

        .schedule-time {
            font-size: 1.2em;
            color: #003366;
            margin-bottom: 10px;
        }

        .schedule-stations {
            color: #666;
            margin-bottom: 10px;
        }

        .schedule-price {
            font-weight: bold;
            color: #003366;
        }

        .seats-info {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }

        .seats-available {
            color: #28a745;
        }

        .seats-limited {
            color: #ffc107;
        }

        .seats-unavailable {
            color: #dc3545;
        }

        .book-button {
            background: #003366;
            color: #ffcc00;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .book-button:hover {
            background: #002244;
        }

        .book-button:disabled {
            background: #cccccc;
            cursor: not-allowed;
        }

        .train-info {
            margin: 10px 0;
            color: #666;
        }
    </style>
</head>
<body>
    <?php require_once 'Head_and_Foot/header.php'; ?>

    <div class="schedule-container">
        <h1>Available Train Schedules</h1>
        
        <?php if (empty($schedules)): ?>
            <p>No schedules available at the moment.</p>
        <?php else: ?>
            <?php foreach ($schedules as $schedule): ?>
                <div class="schedule-card">
                    <div class="schedule-info">
                        <div class="schedule-time">
                            <strong>Departure:</strong> <?php echo date('d M Y, h:i A', strtotime($schedule['departure_time'])); ?><br>
                            <strong>Arrival:</strong> <?php echo date('d M Y, h:i A', strtotime($schedule['arrival_time'])); ?>
                        </div>
                        
                        <div class="schedule-stations">
                            <strong>From:</strong> <?php echo htmlspecialchars($schedule['departure_station']); ?><br>
                            <strong>To:</strong> <?php echo htmlspecialchars($schedule['arrival_station']); ?>
                        </div>
                        
                        <div class="train-info">
                            <strong>Train:</strong> <?php echo htmlspecialchars($schedule['train_number']); ?>
                        </div>
                        
                        <div class="seats-info">
                            <?php if ($schedule['available_seats'] > 10): ?>
                                <span class="seats-available">
                                    <?php echo $schedule['available_seats']; ?> seats available
                                </span>
                            <?php elseif ($schedule['available_seats'] > 0): ?>
                                <span class="seats-limited">
                                    Only <?php echo $schedule['available_seats']; ?> seats left!
                                </span>
                            <?php else: ?>
                                <span class="seats-unavailable">Fully Booked</span>
                            <?php endif; ?>
                        </div>

                        <div class="schedule-price">
                            Price: RM <?php echo number_format($schedule['price'], 2); ?>
                        </div>
                    </div>

                    <form action="payment.php" method="POST">
                        <input type="hidden" name="schedule_id" value="<?php echo $schedule['schedule_id']; ?>">
                        <button type="submit" 
                                class="book-button" 
                                <?php echo $schedule['available_seats'] <= 0 ? 'disabled' : ''; ?>>
                            <?php echo $schedule['available_seats'] > 0 ? 'Book Now' : 'Sold Out'; ?>
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php require_once 'Head_and_Foot/footer.php'; ?>
</body>
</html>