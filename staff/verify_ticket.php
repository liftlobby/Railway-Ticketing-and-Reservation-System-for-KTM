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
                       s.departure_time, s.arrival_time, s.platform_number, s.train_status,
                       p.payment_status, p.amount as payment_amount
                FROM tickets t
                JOIN users u ON t.user_id = u.user_id
                JOIN schedules s ON t.schedule_id = s.schedule_id
                LEFT JOIN payments p ON t.ticket_id = p.ticket_id
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
        
        // Check ticket status
        if ($ticket_data['status'] !== 'active') {
            echo json_encode([
                'success' => false, 
                'message' => 'Invalid ticket! Status: ' . ucfirst($ticket_data['status'])
            ]);
            exit();
        }

        // Check payment status
        if ($ticket_data['payment_status'] !== 'paid') {
            echo json_encode([
                'success' => false, 
                'message' => 'Payment not completed! Status: ' . ucfirst($ticket_data['payment_status'])
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
                'price' => $ticket_data['payment_amount']
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
        $sql = "SELECT t.*, p.payment_status 
                FROM tickets t
                LEFT JOIN payments p ON t.ticket_id = p.ticket_id
                WHERE t.ticket_id = ? AND t.status = 'active'";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $ticket_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Invalid or already used ticket");
        }

        $ticket_data = $result->fetch_assoc();
        
        // Check payment status
        if ($ticket_data['payment_status'] !== 'paid') {
            throw new Exception("Payment not completed for this ticket");
        }

        // Record the verification
        $verify_sql = "INSERT INTO ticket_verifications (ticket_id, staff_id, verification_time, status) 
                      VALUES (?, ?, NOW(), 'success')";
        $verify_stmt = $conn->prepare($verify_sql);
        $verify_stmt->bind_param("ii", $ticket_id, $staff_id);
        $verify_stmt->execute();
        
        // Update ticket status
        $update_sql = "UPDATE tickets SET 
                       status = 'onboard',
                       verified_by = ?,
                       verification_time = NOW(),
                       updated_at = NOW() 
                       WHERE ticket_id = ?";
        
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ii", $staff_id, $ticket_id);
        $update_stmt->execute();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Ticket verified successfully']);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
