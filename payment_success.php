<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['ticket_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$ticket_id = $_SESSION['ticket_id'];

try {
    $conn->begin_transaction();

    // Get ticket and schedule information
    $ticket_sql = "SELECT t.*, s.schedule_id, s.available_seats 
                   FROM tickets t 
                   JOIN schedules s ON t.schedule_id = s.schedule_id 
                   WHERE t.ticket_id = ? AND t.user_id = ?";
    $ticket_stmt = $conn->prepare($ticket_sql);
    $ticket_stmt->bind_param("ii", $ticket_id, $user_id);
    $ticket_stmt->execute();
    $ticket_result = $ticket_stmt->get_result();

    if ($ticket_result->num_rows === 0) {
        throw new Exception("Invalid ticket");
    }

    $ticket = $ticket_result->fetch_assoc();

    // Update ticket status to paid
    $update_ticket = $conn->prepare("UPDATE tickets SET status = 'active' WHERE ticket_id = ?");
    $update_ticket->bind_param("i", $ticket_id);
    
    if (!$update_ticket->execute()) {
        throw new Exception("Error updating ticket status");
    }

    // Decrease available seats
    $update_seats = $conn->prepare("UPDATE schedules SET available_seats = available_seats - 1 
                                   WHERE schedule_id = ? AND available_seats > 0");
    $update_seats->bind_param("i", $ticket['schedule_id']);
    
    if (!$update_seats->execute() || $update_seats->affected_rows === 0) {
        throw new Exception("Error updating seat availability");
    }

    $conn->commit();
    unset($_SESSION['ticket_id']); // Clear the ticket ID from session

    // Fetch updated ticket details for display
    $sql = "SELECT t.*, s.train_number, s.departure_station, s.arrival_station, 
                   s.departure_time, s.arrival_time, s.price
            FROM tickets t
            JOIN schedules s ON t.schedule_id = s.schedule_id
            WHERE t.ticket_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $ticket_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $ticket_details = $result->fetch_assoc();

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = $e->getMessage();
    header("Location: history.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success - KTM Railway System</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .success-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }

        .success-icon {
            color: #28a745;
            font-size: 48px;
            margin-bottom: 20px;
        }

        .ticket-details {
            margin: 30px auto;
            max-width: 500px;
            text-align: left;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .ticket-details p {
            margin: 10px 0;
            padding: 5px 0;
            border-bottom: 1px solid #dee2e6;
        }

        .ticket-details p:last-child {
            border-bottom: none;
        }

        .btn-group {
            margin-top: 30px;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 0 10px;
            border-radius: 4px;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .btn-primary {
            background-color: #003366;
            color: #ffcc00;
        }

        .btn-primary:hover {
            background-color: #002244;
        }

        .qr-code {
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <?php require_once 'Head_and_Foot/header.php'; ?>

    <div class="container">
        <div class="success-container">
            <div class="success-icon">✓</div>
            <h1>Payment Successful!</h1>
            <p>Your ticket has been confirmed and is now active.</p>

            <div class="ticket-details">
                <h2>Ticket Details</h2>
                <p><strong>Train:</strong> <?php echo htmlspecialchars($ticket_details['train_number']); ?></p>
                <p><strong>From:</strong> <?php echo htmlspecialchars($ticket_details['departure_station']); ?></p>
                <p><strong>To:</strong> <?php echo htmlspecialchars($ticket_details['arrival_station']); ?></p>
                <p><strong>Departure:</strong> <?php echo date('d M Y, h:i A', strtotime($ticket_details['departure_time'])); ?></p>
                <p><strong>Arrival:</strong> <?php echo date('d M Y, h:i A', strtotime($ticket_details['arrival_time'])); ?></p>
                <p><strong>Seat:</strong> <?php echo htmlspecialchars($ticket_details['seat_number']); ?></p>
                <p><strong>Price:</strong> RM <?php echo number_format($ticket_details['price'], 2); ?></p>
            </div>

            <div class="qr-code">
                <img src="generate_qr.php?ticket_id=<?php echo $ticket_id; ?>" 
                     alt="Ticket QR Code" 
                     style="width: 200px; height: 200px;">
                <p>Show this QR code at the station gate</p>
            </div>

            <div class="btn-group">
                <a href="history.php" class="btn btn-primary">View All Tickets</a>
                <a href="ticketing.php" class="btn btn-primary">Book Another Ticket</a>
            </div>
        </div>
    </div>

    <?php require_once 'Head_and_Foot/footer.php'; ?>
</body>
</html>
