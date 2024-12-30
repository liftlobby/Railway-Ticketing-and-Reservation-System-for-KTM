<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and has transaction ID
if (!isset($_SESSION['user_id']) || !isset($_GET['transaction_id'])) {
    header("Location: ticketing.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$transaction_id = $_GET['transaction_id'];

try {
    // Fetch ticket details for display
    $sql = "SELECT t.*, s.train_number, s.departure_station, s.arrival_station, 
                   s.departure_time, s.arrival_time, s.price, p.payment_method,
                   p.transaction_id
            FROM tickets t
            JOIN schedules s ON t.schedule_id = s.schedule_id
            JOIN payments p ON t.ticket_id = p.ticket_id
            WHERE p.transaction_id = ?
            ORDER BY t.ticket_id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $transaction_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("No tickets found");
    }

    $tickets = $result->fetch_all(MYSQLI_ASSOC);
    $total_amount = array_sum(array_column($tickets, 'price'));

} catch (Exception $e) {
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .success-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        .success-icon {
            color: #28a745;
            font-size: 64px;
            margin-bottom: 20px;
        }
        .success-icon i {
            animation: scaleUp 0.5s ease-in-out;
        }
        @keyframes scaleUp {
            0% { transform: scale(0); }
            60% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        .transaction-info {
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            text-align: left;
        }
        .tickets-container {
            margin: 30px 0;
        }
        .ticket-card {
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            text-align: left;
            border: 1px solid #dee2e6;
            display: flex;
            gap: 20px;
            transition: transform 0.3s ease;
        }
        .ticket-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .ticket-details {
            flex: 1;
        }
        .ticket-details h3 {
            color: #0056b3;
            margin-bottom: 15px;
        }
        .ticket-details p {
            margin: 8px 0;
            color: #555;
        }
        .qr-code-container {
            text-align: center;
            padding: 10px;
            background: white;
            border-radius: 8px;
        }
        .qr-code {
            width: 120px;
            height: 120px;
            margin-bottom: 5px;
        }
        .total-amount {
            font-size: 1.5em;
            color: #0056b3;
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .payment-info {
            margin: 20px 0;
            padding: 15px;
            color: #666;
        }
        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s;
            font-weight: 500;
        }
        .btn-primary {
            background-color: #0056b3;
            color: white;
        }
        .btn-primary:hover {
            background-color: #003d82;
        }
    </style>
</head>
<body>
    <?php require_once 'Head_and_Foot/header.php'; ?>

    <div class="success-container">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h1>Payment Successful!</h1>
        <p>Your tickets have been confirmed and are now active.</p>

        <div class="transaction-info">
            <p><strong>Transaction ID:</strong> <?php echo htmlspecialchars($transaction_id); ?></p>
            <p><strong>Payment Method:</strong> <?php echo ucwords(str_replace('_', ' ', $tickets[0]['payment_method'])); ?></p>
            <p><strong>Date:</strong> <?php echo date('d M Y, h:i A'); ?></p>
        </div>

        <div class="tickets-container">
            <h2>Your Tickets</h2>
            <?php foreach ($tickets as $ticket): ?>
                <div class="ticket-card">
                    <div class="ticket-details">
                        <h3>Train <?php echo htmlspecialchars($ticket['train_number']); ?></h3>
                        <p><strong>From:</strong> <?php echo htmlspecialchars($ticket['departure_station']); ?></p>
                        <p><strong>To:</strong> <?php echo htmlspecialchars($ticket['arrival_station']); ?></p>
                        <p><strong>Departure:</strong> <?php echo date('d M Y, h:i A', strtotime($ticket['departure_time'])); ?></p>
                        <p><strong>Arrival:</strong> <?php echo date('d M Y, h:i A', strtotime($ticket['arrival_time'])); ?></p>
                        <?php if (isset($ticket['seat_number'])): ?>
                            <p><strong>Seat:</strong> <?php echo htmlspecialchars($ticket['seat_number']); ?></p>
                        <?php endif; ?>
                        <p><strong>Price:</strong> RM <?php echo number_format($ticket['price'], 2); ?></p>
                    </div>
                    <div class="qr-code-container">
                        <img src="generate_qr.php?ticket_id=<?php echo $ticket['ticket_id']; ?>" 
                             alt="Ticket QR Code" 
                             class="qr-code">
                            <br>
                        <small>Show at gate</small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="total-amount">
            Total Amount Paid: RM <?php echo number_format($total_amount, 2); ?>
        </div>

        <div class="payment-info">
            <p>A confirmation email has been sent to your registered email address.</p>
            <p>Please show your QR code(s) at the station gate for entry.</p>
        </div>

        <div class="btn-group">
            <a href="history.php" class="btn btn-primary">View All Tickets</a>
            <a href="ticketing.php" class="btn btn-primary">Book Another Ticket</a>
        </div>
    </div>

    <?php require_once 'Head_and_Foot/footer.php'; ?>
</body>
</html>
