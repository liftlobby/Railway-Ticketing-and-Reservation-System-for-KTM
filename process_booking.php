<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['schedule_id']) || !isset($_POST['ticket_quantity'])) {
    header("Location: ticketing.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$schedule_id = $_POST['schedule_id'];
$ticket_quantity = intval($_POST['ticket_quantity']);
$price = floatval($_POST['price']);

// Validate ticket quantity
if ($ticket_quantity < 1 || $ticket_quantity > 4) {
    $_SESSION['error_message'] = "Invalid number of tickets.";
    header("Location: ticketing.php");
    exit();
}

try {
    $conn->begin_transaction();

    // Check if enough seats are available
    $check_sql = "SELECT available_seats FROM schedules WHERE schedule_id = ? AND available_seats >= ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $schedule_id, $ticket_quantity);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Not enough seats available.");
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
    $insert_sql = "INSERT INTO tickets (user_id, schedule_id, seat_number, status, booking_date) 
                   VALUES (?, ?, ?, 'pending', NOW())";
    $insert_stmt = $conn->prepare($insert_sql);

    for ($i = 1; $i <= $ticket_quantity; $i++) {
        $seat_number = 'A' . ($last_seat_num + $i);
        $insert_stmt->bind_param("iis", $user_id, $schedule_id, $seat_number);
        
        if (!$insert_stmt->execute()) {
            throw new Exception("Failed to create ticket.");
        }
        $ticket_ids[] = $conn->insert_id;
    }

    // Update available seats
    $update_sql = "UPDATE schedules 
                   SET available_seats = available_seats - ? 
                   WHERE schedule_id = ? AND available_seats >= ?";
    
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("iii", $ticket_quantity, $schedule_id, $ticket_quantity);
    
    if (!$update_stmt->execute() || $update_stmt->affected_rows === 0) {
        throw new Exception("Failed to update seat availability.");
    }

    $conn->commit();
    
    // Store ticket IDs in session
    $_SESSION['ticket_ids'] = $ticket_ids;
    header("Location: payment_success.php");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = $e->getMessage();
    header("Location: ticketing.php");
    exit();
}
?>
