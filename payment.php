<?php
session_start();
require_once 'config/database.php';
require_once 'includes/TokenManager.php';
require_once 'includes/NotificationManager.php';

// Check if user is logged in and has pending tickets
if (!isset($_SESSION['user_id']) || !isset($_SESSION['ticket_ids']) || !isset($_SESSION['total_price'])) {
    header("Location: ticketing.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$ticket_ids = $_SESSION['ticket_ids'];
$total_price = $_SESSION['total_price'];
$ticket_quantity = $_SESSION['ticket_quantity'];

// Fetch ticket details
$tickets = [];
if (!empty($ticket_ids)) {
    $placeholders = str_repeat('?,', count($ticket_ids) - 1) . '?';
    $sql = "SELECT t.*, s.train_number, s.departure_station, s.arrival_station, s.departure_time, s.arrival_time 
            FROM tickets t 
            JOIN schedules s ON t.schedule_id = s.schedule_id 
            WHERE t.ticket_id IN ($placeholders)";
    
    $stmt = $conn->prepare($sql);
    $types = str_repeat('i', count($ticket_ids));
    $stmt->bind_param($types, ...$ticket_ids);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // Parse seat range for display
        $seat_range = $row['seat_number'];
        if (strpos($seat_range, '-') !== false) {
            list($start, $end) = explode('-', $seat_range);
            $row['seat_display'] = "Seats $start to $end";
        } else {
            $row['seat_display'] = "Seat " . $seat_range;
        }
        $tickets[] = $row;
    }
}

// If payment is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_method'])) {
    try {
        $conn->begin_transaction();
        
        $payment_method = $_POST['payment_method'];
        $transaction_id = uniqid('PAY_', true);
        
        // Insert single payment record for the ticket
        $payment_sql = "INSERT INTO payments (ticket_id, payment_method, amount, payment_date, status, transaction_id) 
                       VALUES (?, ?, ?, NOW(), 'completed', ?)";
        $payment_stmt = $conn->prepare($payment_sql);
        
        // Process payment for the ticket
        $ticket_id = $ticket_ids[0]; // We now only have one ticket
        $payment_stmt->bind_param("isds", $ticket_id, $payment_method, $total_price, $transaction_id);
        if (!$payment_stmt->execute()) {
            throw new Exception("Failed to process payment.");
        }
        
        // Update ticket status
        $update_sql = "UPDATE tickets SET status = 'active', payment_status = 'paid' WHERE ticket_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $ticket_id);
        if (!$update_stmt->execute()) {
            throw new Exception("Failed to update ticket status.");
        }

        // Get schedule details for QR code and notification
        $schedule_sql = "SELECT s.* FROM tickets t 
                        JOIN schedules s ON t.schedule_id = s.schedule_id 
                        WHERE t.ticket_id = ?";
        $schedule_stmt = $conn->prepare($schedule_sql);
        $schedule_stmt->bind_param("i", $ticket_id);
        $schedule_stmt->execute();
        $schedule_data = $schedule_stmt->get_result()->fetch_assoc();

        // Get ticket details
        $ticket_sql = "SELECT * FROM tickets WHERE ticket_id = ?";
        $ticket_stmt = $conn->prepare($ticket_sql);
        $ticket_stmt->bind_param("i", $ticket_id);
        $ticket_stmt->execute();
        $ticket_data = $ticket_stmt->get_result()->fetch_assoc();

        // Create QR code token
        $tokenManager = new TokenManager($conn);
        $token = $tokenManager->generateSecureToken($ticket_id, $user_id);

        // Create QR code data
        $qrData = array(
            'token' => $token,
            'Ticket ID' => $ticket_id,
            'Train' => $schedule_data['train_number'],
            'From' => $schedule_data['departure_station'],
            'To' => $schedule_data['arrival_station'],
            'Departure' => date('d M Y, h:i A', strtotime($schedule_data['departure_time'])),
            'Arrival' => date('d M Y, h:i A', strtotime($schedule_data['arrival_time'])),
            'Seat' => $ticket_data['seat_number'],
            'Status' => 'active'
        );

        // Convert to JSON and store
        $qr_code = json_encode($qrData);

        // Update the QR code
        $update_qr_sql = "UPDATE tickets SET qr_code = ? WHERE ticket_id = ?";
        $update_qr_stmt = $conn->prepare($update_qr_sql);
        $update_qr_stmt->bind_param("si", $qr_code, $ticket_id);
        $update_qr_stmt->execute();

        // Send notification
        $notificationManager = new NotificationManager($conn);
        
        // Create detailed message
        $message = "Booking Confirmation\n\n";
        $message .= "Ticket Details:\n";
        $message .= "Train: " . $schedule_data['train_number'] . "\n";
        $message .= "From: " . $schedule_data['departure_station'] . "\n";
        $message .= "To: " . $schedule_data['arrival_station'] . "\n";
        $message .= "Departure: " . date('d M Y, h:i A', strtotime($schedule_data['departure_time'])) . "\n";
        $message .= "Passenger: " . $ticket_data['passenger_name'] . "\n";
        $message .= "Quantity: " . $ticket_data['num_seats'] . " ticket(s)\n";
        $message .= "Total Price: RM " . number_format($total_price, 2) . "\n";
        $message .= "Payment Method: " . ucfirst($payment_method) . "\n";
        $message .= "Transaction ID: " . $transaction_id . "\n\n";
        $message .= "Your e-ticket has been attached to this email. You can also view it in your purchase history.";
        
        // Send booking confirmation notification
        $notificationManager->sendTicketStatusNotification(
            $ticket_id,
            'booked',
            $message
        );

        // Update available seats
        $update_seats_sql = "UPDATE schedules 
                            SET available_seats = available_seats - ? 
                            WHERE schedule_id = ?";
        $update_seats_stmt = $conn->prepare($update_seats_sql);
        $update_seats_stmt->bind_param("ii", $ticket_data['num_seats'], $schedule_data['schedule_id']);
        if (!$update_seats_stmt->execute()) {
            throw new Exception("Failed to update seat availability.");
        }
        
        // Log the payment
        $log_sql = "INSERT INTO activity_logs (user_id, action, description, ip_address) 
                    VALUES (?, 'payment', ?, ?)";
        $description = "Payment completed for ticket #$ticket_id: " . $payment_method . " - " . $transaction_id;
        $log_stmt = $conn->prepare($log_sql);
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $log_stmt->bind_param("iss", $user_id, $description, $ip_address);
        $log_stmt->execute();

        $conn->commit();
        
        // Clear session variables
        unset($_SESSION['ticket_ids']);
        unset($_SESSION['total_price']);
        unset($_SESSION['ticket_quantity']);
        
        // Redirect to success page
        header("Location: payment_success.php?transaction_id=" . urlencode($transaction_id));
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - KTM Railway System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .payment-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        .section-title {
            color: #0056b3;
            margin-bottom: 20px;
            text-align: center;
        }
        .ticket-summary {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .ticket-details {
            border-bottom: 1px solid #dee2e6;
            padding: 15px 0;
        }
        .ticket-details:last-child {
            border-bottom: none;
        }
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .payment-method {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .payment-method:hover {
            border-color: #0056b3;
            transform: translateY(-2px);
        }
        .payment-method.selected {
            border-color: #0056b3;
            background-color: #f8f9fa;
        }
        .payment-method i {
            font-size: 2em;
            margin-bottom: 10px;
            color: #0056b3;
        }
        .total-amount {
            font-size: 1.5em;
            text-align: right;
            margin: 20px 0;
            color: #0056b3;
        }
        .btn-pay {
            background-color: #0056b3;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-size: 1.1em;
            margin-top: 20px;
        }
        .btn-pay:hover {
            background-color: #003d82;
        }
        .error-message {
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php require_once 'Head_and_Foot/header.php'; ?>

    <div class="payment-container">
        <h1 class="section-title">Complete Your Payment</h1>
        
        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="ticket-summary">
            <h2 class="section-title">Ticket Summary</h2>
            <?php foreach ($tickets as $ticket): ?>
                <div class="ticket-details">
                    <p><strong>Train Number:</strong> <?php echo isset($ticket['train_number']) ? htmlspecialchars($ticket['train_number']) : 'N/A'; ?></p>
                    <p><strong>From:</strong> <?php echo isset($ticket['departure_station']) ? htmlspecialchars($ticket['departure_station']) : 'N/A'; ?></p>
                    <p><strong>To:</strong> <?php echo isset($ticket['arrival_station']) ? htmlspecialchars($ticket['arrival_station']) : 'N/A'; ?></p>
                    <p><strong>Departure:</strong> <?php echo isset($ticket['departure_time']) ? date('d M Y, h:i A', strtotime($ticket['departure_time'])) : 'N/A'; ?></p>
                    <p><strong>Arrival:</strong> <?php echo isset($ticket['arrival_time']) ? date('d M Y, h:i A', strtotime($ticket['arrival_time'])) : 'N/A'; ?></p>
                    <?php if (isset($ticket['seat_display'])): ?>
                        <p><strong>Seat:</strong> <?php echo htmlspecialchars($ticket['seat_display']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <div class="total-amount">
                <p><strong>Total Amount:</strong> RM <?php echo number_format($total_price, 2); ?></p>
            </div>
        </div>

        <form method="POST" id="payment-form">
            <h2 class="section-title">Select Payment Method</h2>
            
            <div class="payment-methods">
                <div class="payment-method" data-method="credit_card">
                    <i class="fas fa-credit-card"></i>
                    <p>Credit Card</p>
                </div>
                <div class="payment-method" data-method="online_banking">
                    <i class="fas fa-university"></i>
                    <p>Online Banking</p>
                </div>
                <div class="payment-method" data-method="e_wallet">
                    <i class="fas fa-wallet"></i>
                    <p>E-Wallet</p>
                </div>
            </div>

            <input type="hidden" name="payment_method" id="payment_method">
            <button type="submit" class="btn-pay" disabled>Pay Now</button>
        </form>
    </div>

    <?php require_once 'Head_and_Foot/footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const paymentMethods = document.querySelectorAll('.payment-method');
            const paymentMethodInput = document.getElementById('payment_method');
            const payButton = document.querySelector('.btn-pay');
            
            paymentMethods.forEach(method => {
                method.addEventListener('click', function() {
                    // Remove selected class from all methods
                    paymentMethods.forEach(m => m.classList.remove('selected'));
                    
                    // Add selected class to clicked method
                    this.classList.add('selected');
                    
                    // Update hidden input value
                    paymentMethodInput.value = this.dataset.method;
                    
                    // Enable pay button
                    payButton.disabled = false;
                });
            });
        });
    </script>
</body>
</html>
