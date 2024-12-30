<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Set timezone
date_default_timezone_set('Asia/Kuala_Lumpur');
$current_time = date('Y-m-d H:i:s');

// Fetch user's tickets with schedule details
$sql = "SELECT t.*, s.train_number, s.departure_station, s.arrival_station, 
               s.departure_time, s.arrival_time, s.price,
               CASE 
                   WHEN s.departure_time <= NOW() THEN 'departed'
                   WHEN t.status = 'cancelled' THEN 'cancelled'
                   ELSE 'active'
               END as ticket_status
        FROM tickets t
        JOIN schedules s ON t.schedule_id = s.schedule_id
        WHERE t.user_id = ?
        ORDER BY s.departure_time DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$active_tickets = [];
$past_tickets = [];

while ($ticket = $result->fetch_assoc()) {
    if ($ticket['ticket_status'] === 'active') {
        $active_tickets[] = $ticket;
    } else {
        $past_tickets[] = $ticket;
    }
}
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
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .tickets-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 20px;
        }

        .tickets-column {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .column-header {
            color: #003366;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ffcc00;
        }

        .ticket-container {
            background: #f8f9fa;
            margin-bottom: 15px;
            padding: 20px;
            border-radius: 8px;
            position: relative;
            display: flex;
            gap: 20px;
            transition: transform 0.2s;
        }

        .ticket-container:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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

        .status-departed {
            background-color: #e9ecef;
            color: #495057;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .ticket-details {
            flex: 1;
        }

        .ticket-qr {
            text-align: center;
            padding: 10px;
            background: white;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: center;
        }

        .qr-code {
            width: 120px;
            height: 120px;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .qr-code:hover {
            transform: scale(1.05);
        }

        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s;
            font-weight: 500;
            display: inline-block;
            text-align: center;
        }

        .btn-view-qr {
            background-color: #0056b3;
            color: white;
            margin-bottom: 5px;
        }

        .btn-download {
            background-color: #28a745;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
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
            background: #f8f9fa;
            border-radius: 8px;
            color: #666;
        }

        @media (max-width: 768px) {
            .tickets-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php require_once 'Head_and_Foot/header.php'; ?>

    <div class="container">
        <h1 class="page-title">My Ticket History</h1>

        <div class="tickets-grid">
            <!-- Active Tickets Column -->
            <div class="tickets-column">
                <h2 class="column-header">Active Tickets</h2>
                <?php if (empty($active_tickets)): ?>
                    <div class="no-tickets">
                        <p>No active tickets</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($active_tickets as $ticket): ?>
                        <div class="ticket-container">
                            <div class="ticket-status status-active">Active</div>
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
                            <div class="ticket-qr">
                                <img src="generate_qr.php?ticket_id=<?php echo $ticket['ticket_id']; ?>" 
                                     alt="Ticket QR Code" 
                                     class="qr-code"
                                     onclick="showQRModal(<?php echo $ticket['ticket_id']; ?>)">
                                <button class="btn btn-view-qr" onclick="showQRModal(<?php echo $ticket['ticket_id']; ?>)">
                                    View QR Code
                                </button>
                                <a href="download_ticket.php?ticket_id=<?php echo $ticket['ticket_id']; ?>" class="btn btn-download">
                                    Download Ticket
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Past Tickets Column -->
            <div class="tickets-column">
                <h2 class="column-header">Past & Cancelled Tickets</h2>
                <?php if (empty($past_tickets)): ?>
                    <div class="no-tickets">
                        <p>No past tickets</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($past_tickets as $ticket): ?>
                        <div class="ticket-container">
                            <div class="ticket-status <?php 
                                echo $ticket['ticket_status'] === 'cancelled' ? 'status-cancelled' : 'status-departed';
                            ?>">
                                <?php echo ucfirst($ticket['ticket_status']); ?>
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
                            <div class="ticket-qr">
                                <img src="generate_qr.php?ticket_id=<?php echo $ticket['ticket_id']; ?>" 
                                     alt="Ticket QR Code" 
                                     class="qr-code"
                                     onclick="showQRModal(<?php echo $ticket['ticket_id']; ?>)">
                                <button class="btn btn-view-qr" onclick="showQRModal(<?php echo $ticket['ticket_id']; ?>)">
                                    View QR Code
                                </button>
                                <a href="download_ticket.php?ticket_id=<?php echo $ticket['ticket_id']; ?>" class="btn btn-download">
                                    Download Ticket
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- QR Code Modal -->
    <div id="qrModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeQRModal()">&times;</span>
            <img id="modalQRCode" src="" alt="QR Code" style="width: 100%; height: auto;">
        </div>
    </div>

    <?php require_once 'Head_and_Foot/footer.php'; ?>

    <script>
    function showQRModal(ticketId) {
        const modal = document.getElementById('qrModal');
        const modalImg = document.getElementById('modalQRCode');
        modal.style.display = "block";
        modalImg.src = `generate_qr.php?ticket_id=${ticketId}`;
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
