<?php
session_start();
require_once '../config/database.php';

// Check if staff is logged in
if (!isset($_SESSION['staff_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if (!isset($_GET['ticket_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Ticket ID not provided']);
    exit();
}

$ticket_id = $_GET['ticket_id'];

// Get ticket details with user, schedule, and payment information
$sql = "SELECT t.*, u.username, s.train_number, s.departure_station, s.arrival_station, 
        s.departure_time, s.arrival_time, s.platform_number, s.train_status, s.available_seats,
        p.payment_status, p.amount as payment_amount, p.payment_method, p.transaction_id,
        r.refund_id, r.amount as refund_amount, r.status as refund_status 
        FROM tickets t 
        JOIN users u ON t.user_id = u.user_id 
        JOIN schedules s ON t.schedule_id = s.schedule_id 
        LEFT JOIN payments p ON t.ticket_id = p.ticket_id 
        LEFT JOIN refunds r ON t.ticket_id = r.ticket_id 
        WHERE t.ticket_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Ticket not found']);
    exit();
}

$ticket = $result->fetch_assoc();

// Format dates for display
$ticket['departure_time'] = date('Y-m-d H:i:s', strtotime($ticket['departure_time']));
$ticket['arrival_time'] = date('Y-m-d H:i:s', strtotime($ticket['arrival_time']));
$ticket['booking_date'] = date('Y-m-d H:i:s', strtotime($ticket['booking_date']));
if ($ticket['created_at']) $ticket['created_at'] = date('Y-m-d H:i:s', strtotime($ticket['created_at']));
if ($ticket['updated_at']) $ticket['updated_at'] = date('Y-m-d H:i:s', strtotime($ticket['updated_at']));

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'ticket' => $ticket
]);
