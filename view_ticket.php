<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if ticket_id is provided
if (!isset($_GET['ticket_id'])) {
    header("Location: history.php");
    exit();
}

$ticket_id = $_GET['ticket_id'];

// Fetch ticket details
$sql = "SELECT t.*, s.train_number, s.departure_station, s.arrival_station, 
               s.departure_time, s.arrival_time, s.price,
               p.payment_date, p.payment_method
        FROM tickets t
        JOIN schedules s ON t.schedule_id = s.schedule_id
        LEFT JOIN payments p ON t.ticket_id = p.ticket_id
        WHERE t.ticket_id = ? AND t.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $ticket_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: history.php");
    exit();
}

$ticket = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Ticket - KTM Railway System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }

        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }

        .ticket-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }

        .ticket-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px dashed #ddd;
        }

        .ticket-header h1 {
            color: #0078D7;
            margin: 0;
            font-size: 24px;
        }

        .ticket-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .detail-group {
            margin-bottom: 15px;
        }

        .detail-group label {
            display: block;
            color: #666;
            font-size: 0.9em;
            margin-bottom: 5px;
        }

        .detail-group .value {
            font-size: 1.1em;
            font-weight: bold;
            color: #333;
        }

        .ticket-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px dashed #ddd;
        }

        .qr-code {
            text-align: center;
            margin: 20px 0;
        }

        .qr-code img {
            max-width: 150px;
        }

        .ticket-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
        }

        .btn-primary {
            background-color: #0078D7;
            color: white;
            border: none;
        }

        .btn-secondary {
            background-color: #f8f9fa;
            color: #333;
            border: 1px solid #ddd;
        }

        .ticket-status {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9em;
        }

        .status-paid {
            background-color: #d4edda;
            color: #155724;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
    </style>
</head>
<body>
    <?php require_once 'Head_and_Foot/header.php'; ?>

    <div class="container">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION['success_message'];
                unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>

        <div class="ticket-container">
            <div class="ticket-status <?php echo $ticket['payment_status'] === 'paid' ? 'status-paid' : 'status-pending'; ?>">
                <?php echo ucfirst($ticket['payment_status']); ?>
            </div>

            <div class="ticket-header">
                <h1>KTM Railway Ticket</h1>
                <p>Ticket #<?php echo str_pad($ticket['ticket_id'], 6, '0', STR_PAD_LEFT); ?></p>
            </div>

            <div class="ticket-details">
                <div class="detail-group">
                    <label>Train Number</label>
                    <div class="value"><?php echo htmlspecialchars($ticket['train_number']); ?></div>
                </div>

                <div class="detail-group">
                    <label>Seat Number</label>
                    <div class="value"><?php echo htmlspecialchars($ticket['seat_number']); ?></div>
                </div>

                <div class="detail-group">
                    <label>From</label>
                    <div class="value"><?php echo htmlspecialchars($ticket['departure_station']); ?></div>
                </div>

                <div class="detail-group">
                    <label>To</label>
                    <div class="value"><?php echo htmlspecialchars($ticket['arrival_station']); ?></div>
                </div>

                <div class="detail-group">
                    <label>Departure</label>
                    <div class="value"><?php echo date('d M Y, h:i A', strtotime($ticket['departure_time'])); ?></div>
                </div>

                <div class="detail-group">
                    <label>Arrival</label>
                    <div class="value"><?php echo date('d M Y, h:i A', strtotime($ticket['arrival_time'])); ?></div>
                </div>

                <div class="detail-group">
                    <label>Amount Paid</label>
                    <div class="value">RM <?php echo number_format($ticket['payment_amount'], 2); ?></div>
                </div>

                <div class="detail-group">
                    <label>Payment Method</label>
                    <div class="value"><?php echo ucfirst($ticket['payment_method']); ?></div>
                </div>
            </div>

            <div class="qr-code">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo urlencode('TICKET-' . $ticket['ticket_id']); ?>" alt="Ticket QR Code">
            </div>

            <div class="ticket-footer">
                <p><small>Please show this ticket and valid ID during boarding</small></p>
                <div class="ticket-actions">
                    <a href="javascript:window.print()" class="btn btn-primary">
                        <i class="fas fa-print"></i> Print Ticket
                    </a>
                    <a href="history.php" class="btn btn-secondary">
                        <i class="fas fa-history"></i> View History
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php require_once 'Head_and_Foot/footer.php'; ?>
</body>
</html>
