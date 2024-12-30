<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['schedule_id']) || !isset($_POST['ticket_quantity']) || !isset($_POST['price'])) {
    header("Location: ticketing.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$schedule_id = $_POST['schedule_id'];
$ticket_quantity = intval($_POST['ticket_quantity']);
$price = floatval($_POST['price']);
$total_price = $price * $ticket_quantity;

// Set timezone
date_default_timezone_set('Asia/Kuala_Lumpur');

// Validate ticket quantity
if ($ticket_quantity < 1 || $ticket_quantity > 4) {
    $_SESSION['error_message'] = "Invalid number of tickets.";
    header("Location: ticketing.php");
    exit();
}

try {
    $conn->begin_transaction();

    // Check if train has already departed or is closing soon (30 minutes buffer)
    $buffer_time = date('Y-m-d H:i:s', strtotime('+30 minutes'));
    $check_departure_sql = "SELECT departure_time, available_seats, price 
                           FROM schedules 
                           WHERE schedule_id = ? 
                           AND departure_time > ?
                           AND available_seats >= ?";
                           
    $check_stmt = $conn->prepare($check_departure_sql);
    $check_stmt->bind_param("isi", $schedule_id, $buffer_time, $ticket_quantity);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("This train has departed or is closing soon, or not enough seats available.");
    }

    $schedule_data = $result->fetch_assoc();
    
    // Verify price matches database
    if (abs($schedule_data['price'] - $price) > 0.01) {
        throw new Exception("Invalid ticket price.");
    }

    // Get last used seat number
    $seat_sql = "SELECT seat_number 
                 FROM tickets 
                 WHERE schedule_id = ? 
                 AND status != 'cancelled'
                 ORDER BY seat_number DESC 
                 LIMIT 1";
    
    $seat_stmt = $conn->prepare($seat_sql);
    $seat_stmt->bind_param("i", $schedule_id);
    $seat_stmt->execute();
    $seat_result = $seat_stmt->get_result();
    
    $last_seat_num = 0;
    if ($seat_result->num_rows > 0) {
        $last_seat = $seat_result->fetch_assoc()['seat_number'];
        $last_seat_num = intval(substr($last_seat, 1));
    }

    // Insert tickets
    $ticket_ids = array();
    $insert_sql = "INSERT INTO tickets (user_id, schedule_id, seat_number, status, booking_date, payment_amount) 
                   VALUES (?, ?, ?, 'pending', NOW(), ?)";
    $insert_stmt = $conn->prepare($insert_sql);

    for ($i = 1; $i <= $ticket_quantity; $i++) {
        $seat_number = 'A' . ($last_seat_num + $i);
        $insert_stmt->bind_param("issd", $user_id, $schedule_id, $seat_number, $price);
        
        if (!$insert_stmt->execute()) {
            throw new Exception("Failed to create ticket.");
        }
        $ticket_ids[] = $conn->insert_id;
    }

    // Update available seats with a final check
    $update_sql = "UPDATE schedules 
                   SET available_seats = available_seats - ? 
                   WHERE schedule_id = ? 
                   AND departure_time > ? 
                   AND available_seats >= ?";
    
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("iiis", $ticket_quantity, $schedule_id, $buffer_time, $ticket_quantity);
    
    if (!$update_stmt->execute() || $update_stmt->affected_rows === 0) {
        throw new Exception("Failed to update seat availability or train has departed.");
    }

    $conn->commit();
    
    // Store booking information in session
    $_SESSION['ticket_ids'] = $ticket_ids;
    $_SESSION['total_price'] = $total_price;
    $_SESSION['ticket_quantity'] = $ticket_quantity;
    
    // Redirect to payment page
    header("Location: payment.php");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = $e->getMessage();
    header("Location: ticketing.php");
    exit();
}
?>
