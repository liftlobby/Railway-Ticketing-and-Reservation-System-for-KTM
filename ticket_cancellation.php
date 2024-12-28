<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Process ticket cancellation if ticket_id is provided
if (isset($_GET['ticket_id'])) {
    $ticket_id = $_GET['ticket_id'];
    
    try {
        $conn->begin_transaction();

        // Verify ticket belongs to user and is not already cancelled
        $check_sql = "SELECT t.*, s.available_seats, s.schedule_id, s.departure_time,
                            s.train_number, s.departure_station, s.arrival_station, s.price 
                     FROM tickets t 
                     JOIN schedules s ON t.schedule_id = s.schedule_id 
                     WHERE t.ticket_id = ? AND t.user_id = ? AND t.status != 'cancelled'";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $ticket_id, $user_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("Invalid ticket or already cancelled");
        }

        $ticket = $result->fetch_assoc();
        
        // Check if cancellation is within 24 hours of departure
        $departure_time = strtotime($ticket['departure_time']);
        $current_time = time();
        if (($departure_time - $current_time) < 24 * 60 * 60) {
            throw new Exception("Cancellation must be made at least 24 hours before departure");
        }

        if (isset($_POST['confirm_cancel'])) {
            // Update ticket status
            $update_ticket = $conn->prepare("UPDATE tickets SET status = 'cancelled' WHERE ticket_id = ?");
            $update_ticket->bind_param("i", $ticket_id);
            
            if (!$update_ticket->execute()) {
                throw new Exception("Error cancelling ticket");
            }

            // Increase available seats
            $update_seats = $conn->prepare("UPDATE schedules SET available_seats = available_seats + 1 WHERE schedule_id = ?");
            $update_seats->bind_param("i", $ticket['schedule_id']);
            
            if (!$update_seats->execute()) {
                throw new Exception("Error updating seat availability");
            }

            $conn->commit();
            $_SESSION['success_message'] = "Ticket cancelled successfully. Please visit any KTM station with your booking confirmation and valid ID to collect your refund.";
            header("Location: history.php");
            exit();
        }
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = $e->getMessage();
        if (isset($_POST['confirm_cancel'])) {
            header("Location: history.php");
            exit();
        }
    }
}

// Fetch user's active tickets
$tickets_sql = "SELECT t.*, s.train_number, s.departure_station, s.arrival_station, 
                       s.departure_time, s.arrival_time, s.price
                FROM tickets t
                JOIN schedules s ON t.schedule_id = s.schedule_id
                WHERE t.user_id = ? AND t.status != 'cancelled'
                AND s.departure_time > NOW()
                ORDER BY s.departure_time ASC";
$tickets_stmt = $conn->prepare($tickets_sql);
$tickets_stmt->bind_param("i", $user_id);
$tickets_stmt->execute();
$tickets_result = $tickets_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Cancellation - KTM Railway System</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-title {
            color: #003366;
            text-align: center;
            margin-bottom: 30px;
        }

        .policy-section {
            background: white;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .policy-section h2 {
            color: #003366;
            margin-bottom: 20px;
        }

        .policy-list {
            list-style-type: none;
            padding: 0;
        }

        .policy-list li {
            margin-bottom: 15px;
            padding-left: 25px;
            position: relative;
        }

        .policy-list li:before {
            content: "•";
            color: #003366;
            position: absolute;
            left: 0;
            font-weight: bold;
        }

        .important-note {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-top: 20px;
            border-radius: 4px;
        }

        .tickets-section {
            margin-top: 30px;
        }

        .ticket-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .ticket-details {
            flex: 1;
        }

        .ticket-actions {
            margin-left: 20px;
        }

        .btn {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
            border: none;
        }

        .btn-cancel {
            background-color: #dc3545;
            color: white;
        }

        .btn-cancel:hover {
            background-color: #c82333;
        }

        .no-tickets {
            text-align: center;
            padding: 30px;
            background: white;
            border-radius: 8px;
            margin-top: 20px;
        }

        .steps-section {
            margin-top: 30px;
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .steps-section h2 {
            color: #003366;
            margin-bottom: 20px;
        }

        .steps-list {
            list-style-type: none;
            padding: 0;
            counter-reset: steps;
        }

        .steps-list li {
            margin-bottom: 15px;
            padding-left: 35px;
            position: relative;
        }

        .steps-list li:before {
            counter-increment: steps;
            content: counter(steps);
            position: absolute;
            left: 0;
            width: 25px;
            height: 25px;
            background-color: #003366;
            color: #ffcc00;
            border-radius: 50%;
            text-align: center;
            line-height: 25px;
            font-weight: bold;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            border-radius: 8px;
            width: 400px;
            text-align: center;
        }

        .btn-group {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .close {
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #666;
        }
    </style>
</head>
<body>
    <?php require_once 'Head_and_Foot/header.php'; ?>

    <div class="container">
        <h1 class="page-title">Ticket Cancellation</h1>

        <!-- Policy Section -->
        <div class="policy-section">
            <h2>Cancellation and Refund Policy</h2>
            <ul class="policy-list">
                <li>Cancellation requests must be made at least 24 hours before departure time</li>
                <li>Service charges will apply for all refunds</li>
                <li>Refunds will be processed within 7 working days</li>
                <li>No refunds will be provided for missed departures</li>
            </ul>
            <div class="important-note">
                <strong>Important:</strong> Refunds can only be physically collected at any KTM station. 
                Please bring your booking confirmation and valid ID.
            </div>
        </div>

        <!-- Steps Section -->
        <div class="steps-section">
            <h2>Steps to Cancel Your Ticket</h2>
            <ul class="steps-list">
                <li>Select the ticket you wish to cancel from your active tickets below</li>
                <li>Review the ticket details and cancellation policy</li>
                <li>Confirm your cancellation request</li>
                <li>Visit any KTM station with your booking confirmation and valid ID to collect your refund</li>
            </ul>
        </div>

        <!-- Active Tickets Section -->
        <div class="tickets-section">
            <h2>Your Active Tickets</h2>
            <?php if ($tickets_result->num_rows > 0): ?>
                <?php while ($ticket = $tickets_result->fetch_assoc()): ?>
                    <div class="ticket-card">
                        <div class="ticket-details">
                            <h3>Train <?php echo htmlspecialchars($ticket['train_number']); ?></h3>
                            <p><strong>From:</strong> <?php echo htmlspecialchars($ticket['departure_station']); ?></p>
                            <p><strong>To:</strong> <?php echo htmlspecialchars($ticket['arrival_station']); ?></p>
                            <p><strong>Departure:</strong> <?php echo date('d M Y, h:i A', strtotime($ticket['departure_time'])); ?></p>
                            <p><strong>Seat:</strong> <?php echo htmlspecialchars($ticket['seat_number']); ?></p>
                            <p><strong>Price:</strong> RM <?php echo number_format($ticket['price'], 2); ?></p>
                        </div>
                        <div class="ticket-actions">
                            <?php if (strtotime($ticket['departure_time']) - time() >= 24 * 60 * 60): ?>
                                <button class="btn btn-cancel" onclick="showCancelModal(<?php echo $ticket['ticket_id']; ?>)">
                                    Cancel Ticket
                                </button>
                            <?php else: ?>
                                <span class="btn btn-secondary" style="cursor: not-allowed;">
                                    Cannot Cancel (< 24h)
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-tickets">
                    <p>You have no active tickets that can be cancelled.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Cancel Confirmation Modal -->
    <div id="cancelModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeCancelModal()">&times;</span>
            <h2>Confirm Cancellation</h2>
            <p>Are you sure you want to cancel this ticket? This action cannot be undone.</p>
            <form method="POST" id="cancelForm">
                <div class="btn-group">
                    <button type="button" class="btn btn-secondary" onclick="closeCancelModal()">No, Keep Ticket</button>
                    <button type="submit" name="confirm_cancel" class="btn btn-cancel">Yes, Cancel Ticket</button>
                </div>
            </form>
        </div>
    </div>

    <?php require_once 'Head_and_Foot/footer.php'; ?>

    <script>
        function showCancelModal(ticketId) {
            const modal = document.getElementById('cancelModal');
            const form = document.getElementById('cancelForm');
            form.action = 'ticket_cancellation.php?ticket_id=' + ticketId;
            modal.style.display = "block";
        }

        function closeCancelModal() {
            const modal = document.getElementById('cancelModal');
            modal.style.display = "none";
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('cancelModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>
