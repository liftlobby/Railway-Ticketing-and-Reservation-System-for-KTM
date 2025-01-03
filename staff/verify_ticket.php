<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if staff is logged in
if (!isset($_SESSION['staff_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$staff_id = $_SESSION['staff_id'];

// Handle GET request for ticket details
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ticket_id'])) {
    $ticket_id = $_GET['ticket_id'];
    
    try {
        // Get ticket details
        $sql = "SELECT t.*, u.username, s.train_number, s.departure_station, s.arrival_station, 
                       s.departure_time, s.arrival_time, s.platform_number, s.train_status
                FROM tickets t
                JOIN users u ON t.user_id = u.user_id
                JOIN schedules s ON t.schedule_id = s.schedule_id
                WHERE t.ticket_id = ?";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $ticket_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Ticket not found']);
            exit();
        }

        $ticket_data = $result->fetch_assoc();
        
        // Format seat information for display
        $seat_info = $ticket_data['seat_number'];
        if (strpos($seat_info, '-') !== false) {
            list($start, $end) = explode('-', $seat_info);
            $ticket_data['seat_display'] = "Seats $start to $end";
        } else {
            $ticket_data['seat_display'] = "Seat " . $seat_info;
        }
        
        // Check ticket status
        if ($ticket_data['status'] === 'cancelled') {
            echo json_encode([
                'success' => false, 
                'message' => 'This ticket has been cancelled'
            ]);
            exit();
        }

        if ($ticket_data['status'] === 'used') {
            echo json_encode([
                'success' => false, 
                'message' => 'This ticket has already been used'
            ]);
            exit();
        }

        // Check if departure time has passed
        $departure_time = strtotime($ticket_data['departure_time']);
        $current_time = time();
        $time_difference = $departure_time - $current_time;

        // If departure time was more than 3 hours ago
        if ($time_difference < -10800) {
            echo json_encode([
                'success' => false,
                'message' => 'This ticket has expired. Departure time was ' . date('d M Y, h:i A', $departure_time)
            ]);
            exit();
        }

        // If trying to use ticket more than 3 hours before departure
        if ($time_difference > 10800) {
            echo json_encode([
                'success' => false,
                'message' => 'This ticket cannot be used yet. Departure time is ' . date('d M Y, h:i A', $departure_time)
            ]);
            exit();
        }

        // Return ticket details
        echo json_encode([
            'success' => true,
            'ticket' => [
                'ticket_id' => $ticket_data['ticket_id'],
                'username' => $ticket_data['username'],
                'train_number' => $ticket_data['train_number'],
                'departure_station' => $ticket_data['departure_station'],
                'arrival_station' => $ticket_data['arrival_station'],
                'departure_time' => $ticket_data['departure_time'],
                'arrival_time' => $ticket_data['arrival_time'],
                'platform_number' => $ticket_data['platform_number'],
                'train_status' => $ticket_data['train_status'],
                'status' => $ticket_data['status'],
                'seat_display' => $ticket_data['seat_display'],
                'num_seats' => $ticket_data['num_seats'] ?? 1
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

// Handle POST request for ticket verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ticket_id'])) {
    $ticket_id = $_POST['ticket_id'];
    
    // Start transaction
    $conn->begin_transaction();
    try {
        // Get ticket details
        $sql = "SELECT t.*, s.departure_time 
                FROM tickets t
                JOIN schedules s ON t.schedule_id = s.schedule_id
                WHERE t.ticket_id = ? FOR UPDATE";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $ticket_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Ticket not found');
        }

        $ticket_data = $result->fetch_assoc();
        
        // Validate ticket status
        if ($ticket_data['status'] !== 'active') {
            throw new Exception('Invalid ticket status: ' . ucfirst($ticket_data['status']));
        }

        // Validate time window
        $departure_time = strtotime($ticket_data['departure_time']);
        $current_time = time();
        $time_difference = $departure_time - $current_time;

        if ($time_difference < -10800) {
            throw new Exception('Ticket has expired');
        }

        if ($time_difference > 10800) {
            throw new Exception('Too early to use this ticket');
        }

        // Update ticket status
        $update_sql = "UPDATE tickets SET status = 'used', verified_by = ?, verified_at = NOW() WHERE ticket_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ii", $staff_id, $ticket_id);
        
        if (!$update_stmt->execute()) {
            throw new Exception('Failed to update ticket status');
        }

        // Log the verification
        $log_sql = "INSERT INTO ticket_verifications (ticket_id, staff_id, verification_time) VALUES (?, ?, NOW())";
        $log_stmt = $conn->prepare($log_sql);
        $log_stmt->bind_param("ii", $ticket_id, $staff_id);
        
        if (!$log_stmt->execute()) {
            throw new Exception('Failed to log verification');
        }

        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Ticket verified successfully'
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
?>
