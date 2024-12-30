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

// Set timezone to match your server's timezone
date_default_timezone_set('Asia/Kuala_Lumpur');

// Add buffer time (e.g., 30 minutes before departure)
$buffer_time = date('Y-m-d H:i:s', strtotime('+30 minutes'));
$current_time = date('Y-m-d H:i:s');

// Fetch only available schedules
$sql = "SELECT s.*, 
               COALESCE(s.available_seats, 50) as available_seats,
               (COALESCE(s.available_seats, 50) > 0) as is_available,
               CASE 
                   WHEN s.departure_time <= DATE_ADD(NOW(), INTERVAL 30 MINUTE) THEN 'closing'
                   ELSE 'available'
               END as booking_status
        FROM schedules s 
        WHERE s.departure_time > NOW()
        AND COALESCE(s.available_seats, 50) > 0
        ORDER BY s.departure_time ASC";

$result = $conn->query($sql);
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
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }

        .schedule-grid {
            display: grid;
            grid-template-columns: minmax(350px, 600px);
            gap: 20px;
            margin-top: 20px;
            justify-content: center;
        }

        .schedule-card {
            background: white;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 20px;
            transition: transform 0.2s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .schedule-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .seats-available {
            color: #28a745;
        }

        .seats-closing {
            color: #ff6b6b;
            animation: blink 1s infinite;
        }

        @keyframes blink {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        .book-button {
            background: #003366;
            color: #ffcc00;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 100%;
            margin-top: 10px;
        }

        .book-button:hover {
            background: #002244;
        }

        .book-button.closing {
            background: #ff6b6b;
        }

        .train-info {
            margin: 10px 0;
            color: #666;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.9em;
            font-weight: bold;
        }

        .status-badge.closing {
            background: #ff6b6b;
            color: white;
            animation: blink 1s infinite;
        }

        .status-badge.available {
            background: #28a745;
            color: white;
        }

        .ticket-quantity {
            margin: 15px 0;
        }

        .ticket-quantity label {
            display: block;
            margin-bottom: 5px;
            color: #003366;
        }

        .ticket-quantity select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 5px;
        }

        .total-price {
            font-size: 1.1em;
            color: #003366;
            font-weight: bold;
            margin-top: 10px;
        }

        .page-title {
            color: #003366;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2em;
        }

        .no-schedules {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            color: #666;
        }
    </style>
</head>
<body>
    <?php require_once 'Head_and_Foot/header.php'; ?>

    <div class="schedule-container">
        <h1 class="page-title">Available Train Tickets</h1>

        <?php if ($result->num_rows == 0): ?>
            <div class="no-schedules">
                <p>No available trains at the moment.</p>
                <p>Please check back later for new schedules.</p>
            </div>
        <?php else: ?>
            <div class="schedule-grid">
                <?php while ($schedule = $result->fetch_assoc()): ?>
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
                            <div class="schedule-price">
                                <strong>Price:</strong> RM <?php echo number_format($schedule['price'], 2); ?>
                            </div>
                            <div class="seats-info <?php echo $schedule['booking_status'] == 'closing' ? 'seats-closing' : 'seats-available'; ?>">
                                <i class="fas fa-chair"></i>
                                <?php echo $schedule['available_seats']; ?> seats available
                                <?php if ($schedule['booking_status'] == 'closing'): ?>
                                    <span class="status-badge closing">Closing Soon</span>
                                <?php else: ?>
                                    <span class="status-badge available">Available</span>
                                <?php endif; ?>
                            </div>
                            <form action="process_booking.php" method="POST">
                                <input type="hidden" name="schedule_id" value="<?php echo $schedule['schedule_id']; ?>">
                                <input type="hidden" name="price" value="<?php echo $schedule['price']; ?>">
                                <div class="ticket-quantity">
                                    <label for="ticket_quantity_<?php echo $schedule['schedule_id']; ?>">
                                        Number of Tickets:
                                    </label>
                                    <select name="ticket_quantity" 
                                            id="ticket_quantity_<?php echo $schedule['schedule_id']; ?>"
                                            onchange="updateTotalPrice(this, <?php echo $schedule['price']; ?>)">
                                        <?php for ($i = 1; $i <= min(5, $schedule['available_seats']); $i++): ?>
                                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <div class="total-price" id="total_price_<?php echo $schedule['schedule_id']; ?>">
                                        Total: RM <?php echo number_format($schedule['price'], 2); ?>
                                    </div>
                                </div>
                                <button type="submit" 
                                        class="book-button <?php echo $schedule['booking_status'] == 'closing' ? 'closing' : ''; ?>">
                                    Book Now
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php require_once 'Head_and_Foot/footer.php'; ?>

    <script>
    function updateTotalPrice(select, basePrice) {
        const quantity = select.value;
        const totalPrice = (quantity * basePrice).toFixed(2);
        const scheduleId = select.id.split('_')[2];
        document.getElementById(`total_price_${scheduleId}`).innerHTML = `Total: RM ${totalPrice}`;
    }
    </script>
</body>
</html>