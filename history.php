<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user's tickets with schedule details
$sql = "SELECT t.*, s.train_number, s.departure_station, s.arrival_station, 
               s.departure_time, s.arrival_time, s.price
        FROM tickets t
        JOIN schedules s ON t.schedule_id = s.schedule_id
        WHERE t.user_id = ?
        ORDER BY t.booking_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Ticket History - KTM Railway System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .ticket-container {
            background: white;
            margin-bottom: 15px;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
            display: flex;
            gap: 20px;
        }

        .ticket-status {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
        }

        .status-active {
            background-color: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .ticket-details {
            flex: 1;
        }

        .ticket-qr {
            width: 150px;
            text-align: center;
        }

        .qr-code {
            width: 150px;
            height: 150px;
            margin-bottom: 10px;
        }

        .ticket-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            position: absolute;
            bottom: 20px;
            right: 20px;
        }

        .btn {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.3s;
            font-size: 14px;
        }

        .btn-cancel {
            background-color: #dc3545;
            color: white;
            border: none;
            width: auto;
            min-width: 100px;
        }

        .btn-view-qr {
            background-color: #003366;
            color: #ffcc00;
            padding: 6px 12px;
            font-size: 14px;
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
            width: 300px;
            text-align: center;
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

        .page-title {
            color: #003366;
            margin-bottom: 20px;
            text-align: center;
        }

        .no-tickets {
            text-align: center;
            padding: 20px;
            background: white;
            border-radius: 8px;
            margin-top: 20px;
        }
        
    </style>
</head>
<body>
    <?php require_once 'Head_and_Foot/header.php'; ?>

    <div class="container">
        <h1 class="page-title">My Ticket History</h1>

        <?php if ($result->num_rows > 0): ?>
            <?php while ($ticket = $result->fetch_assoc()): ?>
                <div class="ticket-container">
                    <div class="ticket-status <?php 
                        echo $ticket['status'] === 'cancelled' ? 'status-cancelled' : 'status-active';
                    ?>">
                        <?php echo $ticket['status'] === 'cancelled' ? 'Cancelled' : 'Active'; ?>
                    </div>

                    <div class="ticket-details">
                        <h3>Train <?php echo htmlspecialchars($ticket['train_number']); ?></h3>
                        <p><strong>From:</strong> <?php echo htmlspecialchars($ticket['departure_station']); ?></p>
                        <p><strong>To:</strong> <?php echo htmlspecialchars($ticket['arrival_station']); ?></p>
                        <p><strong>Departure:</strong> <?php echo date('d M Y, h:i A', strtotime($ticket['departure_time'])); ?></p>
                        <p><strong>Arrival:</strong> <?php echo date('d M Y, h:i A', strtotime($ticket['arrival_time'])); ?></p>
                        <p><strong>Seat:</strong> <?php echo htmlspecialchars($ticket['seat_number']); ?></p>
                        <p><strong>Price:</strong> RM <?php echo number_format($ticket['price'], 2); ?></p>
                        <p><strong>Booking Date:</strong> <?php echo date('d M Y, h:i A', strtotime($ticket['booking_date'])); ?></p>
                    </div>

                    <?php if ($ticket['status'] !== 'cancelled'): ?>
                    <div class="ticket-qr">
                        <img src="generate_qr.php?ticket_id=<?php echo $ticket['ticket_id']; ?>" 
                             alt="Ticket QR Code" 
                             class="qr-code"
                             style="width: 150px; height: 150px;"
                             onclick="showQRModal(<?php echo $ticket['ticket_id']; ?>)">
                        <button class="btn btn-view-qr" onclick="showQRModal(<?php echo $ticket['ticket_id']; ?>)">
                            View QR Code
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-tickets">
                <p>You haven't booked any tickets yet.</p>
                <a href="ticketing.php" class="btn btn-view-qr">Book a Ticket</a>
            </div>
        <?php endif; ?>

    <!-- QR Code Modal -->
    <div id="qrModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeQRModal()">&times;</span>
            <h2>Ticket QR Code</h2>
            <img id="modalQRCode" src="" alt="Ticket QR Code" style="width: 300px; height: 300px;">
            <p>Show this QR code at the station gate</p>
        </div>
    </div>
</div>
    <?php require_once 'Head_and_Foot/footer.php'; ?>

    <script>
        function showQRModal(ticketId) {
            const modal = document.getElementById('qrModal');
            const qrImage = document.getElementById('modalQRCode');
            qrImage.src = 'generate_qr.php?ticket_id=' + ticketId;
            modal.style.display = "block";
        }

        function closeQRModal() {
            const modal = document.getElementById('qrModal');
            modal.style.display = "none";
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('qrModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>
