<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['schedule_id'])) {
    header("Location: ticketing.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$schedule_id = $_POST['schedule_id'];

try {
    $conn->begin_transaction();

    // Check if seats are still available
    $check_sql = "SELECT train_number, departure_station, arrival_station, 
                         departure_time, arrival_time, price, available_seats 
                  FROM schedules 
                  WHERE schedule_id = ? AND available_seats > 0";
    
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $schedule_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("No seats available for this schedule.");
    }

    $schedule = $result->fetch_assoc();

    // Get current seat numbers for this schedule
    $seat_sql = "SELECT seat_number 
                 FROM tickets 
                 WHERE schedule_id = ? 
                 AND status != 'cancelled'
                 ORDER BY seat_number DESC";
    
    $seat_stmt = $conn->prepare($seat_sql);
    $seat_stmt->bind_param("i", $schedule_id);
    $seat_stmt->execute();
    $seat_result = $seat_stmt->get_result();
    
    $taken_seats = array();
    while ($row = $seat_result->fetch_assoc()) {
        $taken_seats[] = $row['seat_number'];
    }

    $conn->commit();

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = $e->getMessage();
    header("Location: ticketing.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - KTM Railway System</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .payment-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .ticket-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .ticket-summary h2 {
            color: #003366;
            margin-bottom: 15px;
        }

        .ticket-detail {
            margin: 10px 0;
            padding: 5px 0;
            border-bottom: 1px solid #dee2e6;
        }

        .ticket-detail:last-child {
            border-bottom: none;
        }

        .seat-selection {
            margin: 20px 0;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .seat-selection h3 {
            color: #003366;
            margin-bottom: 15px;
        }

        .seat-input {
            margin: 10px 0;
        }

        .seat-input label {
            display: block;
            margin-bottom: 5px;
            color: #666;
        }

        .seat-input select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
        }

        .total-price {
            font-size: 1.2em;
            color: #003366;
            margin: 20px 0;
            padding: 10px;
            background: #e9ecef;
            border-radius: 4px;
            text-align: right;
        }

        .payment-button {
            display: inline-block;
            padding: 12px 24px;
            background: #003366;
            color: #ffcc00;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .payment-button:hover {
            background: #002244;
        }

        .payment-options {
            margin-top: 30px;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php require_once 'Head_and_Foot/header.php'; ?>

    <div class="payment-container">
        <h1>Complete Your Booking</h1>

        <div class="ticket-summary">
            <h2>Journey Details</h2>
            <div class="ticket-detail">
                <strong>Train:</strong> <?php echo htmlspecialchars($schedule['train_number']); ?>
            </div>
            <div class="ticket-detail">
                <strong>From:</strong> <?php echo htmlspecialchars($schedule['departure_station']); ?>
            </div>
            <div class="ticket-detail">
                <strong>To:</strong> <?php echo htmlspecialchars($schedule['arrival_station']); ?>
            </div>
            <div class="ticket-detail">
                <strong>Departure:</strong> <?php echo date('d M Y, h:i A', strtotime($schedule['departure_time'])); ?>
            </div>
            <div class="ticket-detail">
                <strong>Arrival:</strong> <?php echo date('d M Y, h:i A', strtotime($schedule['arrival_time'])); ?>
            </div>
            <div class="ticket-detail">
                <strong>Price per ticket:</strong> RM <?php echo number_format($schedule['price'], 2); ?>
            </div>
        </div>

        <form action="process_booking.php" method="POST">
            <input type="hidden" name="schedule_id" value="<?php echo $schedule_id; ?>">
            <input type="hidden" name="price" value="<?php echo $schedule['price']; ?>">
            
            <div class="seat-selection">
                <h3>Select Number of Tickets</h3>
                <div class="seat-input">
                    <label>Number of Tickets (Maximum 4):</label>
                    <select name="ticket_quantity" id="ticket_quantity" onchange="updateTotal()">
                        <?php
                        $max_tickets = min(4, $schedule['available_seats']);
                        for ($i = 1; $i <= $max_tickets; $i++) {
                            echo "<option value='$i'>$i ticket" . ($i > 1 ? 's' : '') . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="total-price">
                    Total Price: RM <span id="total_amount"><?php echo number_format($schedule['price'], 2); ?></span>
                </div>
            </div>

            <div class="payment-options">
                <button type="submit" class="payment-button">Proceed to Payment</button>
            </div>
        </form>
    </div>

    <script>
        function updateTotal() {
            const quantity = document.getElementById('ticket_quantity').value;
            const pricePerTicket = <?php echo $schedule['price']; ?>;
            const total = quantity * pricePerTicket;
            document.getElementById('total_amount').textContent = total.toFixed(2);
        }
    </script>

    <?php require_once 'Head_and_Foot/footer.php'; ?>
</body>
</html>
