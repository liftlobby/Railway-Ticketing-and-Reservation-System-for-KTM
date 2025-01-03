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
$passenger_name = isset($_POST['passenger_name']) ? trim($_POST['passenger_name']) : null;

// Validate passenger name
if (empty($passenger_name)) {
    $_SESSION['error_message'] = "Passenger name is required.";
    header("Location: ticketing.php");
    exit();
}

// Validate ticket quantity
if ($ticket_quantity < 1 || $ticket_quantity > 4) {
    $_SESSION['error_message'] = "Invalid number of tickets.";
    header("Location: ticketing.php");
    exit();
}

// Set timezone
date_default_timezone_set('Asia/Kuala_Lumpur');

try {
    $conn->begin_transaction();

    // Check if train has already departed (5 minutes buffer for last-minute bookings)
    $buffer_time = date('Y-m-d H:i:s', strtotime('+5 minutes'));
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
        throw new Exception("This train has departed or not enough seats available.");
    }

    $schedule_data = $result->fetch_assoc();
    
    // Verify price matches database
    if (abs($schedule_data['price'] - $price) > 0.01) {
        throw new Exception("Invalid ticket price.");
    }

    // Get last used seat number
    $seat_sql = "SELECT MAX(CAST(SUBSTRING(seat_number, 2) AS UNSIGNED)) as last_seat 
                 FROM tickets 
                 WHERE schedule_id = ? 
                 AND status != 'cancelled'";
    
    $seat_stmt = $conn->prepare($seat_sql);
    $seat_stmt->bind_param("i", $schedule_id);
    $seat_stmt->execute();
    $seat_result = $seat_stmt->get_result();
    $last_seat_data = $seat_result->fetch_assoc();
    $last_seat_num = $last_seat_data['last_seat'] ?? 0;

    // Generate seat numbers
    $start_seat = $last_seat_num + 1;
    $end_seat = $start_seat + $ticket_quantity - 1;
    $seat_range = "A$start_seat-A$end_seat";

    // Generate QR code data - using a stable format
    $qr_data = json_encode([
        'ticket_id' => null, // Will be updated after insert
        'schedule_id' => $schedule_id,
        'seats' => $seat_range,
        'passenger' => $passenger_name
    ], JSON_UNESCAPED_SLASHES);
    
    // Insert single ticket for multiple seats
    $insert_sql = "INSERT INTO tickets (user_id, schedule_id, seat_number, num_seats, passenger_name, status, booking_date, payment_amount, qr_code) 
                   VALUES (?, ?, ?, ?, ?, 'pending', NOW(), ?, ?)";
    $total_amount = $price * $ticket_quantity;
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("issisds", $user_id, $schedule_id, $seat_range, $ticket_quantity, $passenger_name, $total_amount, $qr_data);
    
    if (!$insert_stmt->execute()) {
        throw new Exception("Failed to create ticket.");
    }
    $ticket_id = $conn->insert_id;

    // Update the QR code with the ticket ID
    $qr_data = json_encode([
        'ticket_id' => $ticket_id,
        'schedule_id' => $schedule_id,
        'seats' => $seat_range,
        'passenger' => $passenger_name
    ], JSON_UNESCAPED_SLASHES);

    // Update the ticket with the final QR code
    $update_qr_sql = "UPDATE tickets SET qr_code = ? WHERE ticket_id = ?";
    $update_qr_stmt = $conn->prepare($update_qr_sql);
    $update_qr_stmt->bind_param("si", $qr_data, $ticket_id);
    $update_qr_stmt->execute();

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
    $_SESSION['ticket_ids'] = [$ticket_id];
    $_SESSION['total_price'] = $total_amount;
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
