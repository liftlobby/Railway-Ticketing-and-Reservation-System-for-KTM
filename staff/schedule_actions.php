<?php
session_start();
require_once '../config/database.php';
require_once '../includes/MessageUtility.php';
require_once '../includes/NotificationManager.php';

// Check if staff is logged in
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $train_number = $_POST['train_number'];
                $departure_station = $_POST['departure_station'];
                $arrival_station = $_POST['arrival_station'];
                $departure_time = $_POST['departure_time'];
                $arrival_time = $_POST['arrival_time'];
                $price = $_POST['price'];
                $platform_number = $_POST['platform_number'] ?? null;
                $available_seats = $_POST['available_seats'] ?? 100;

                $stmt = $conn->prepare("INSERT INTO schedules (train_number, departure_station, arrival_station, departure_time, arrival_time, platform_number, train_status, price, available_seats) VALUES (?, ?, ?, ?, ?, ?, 'on_time', ?, ?)");
                $stmt->bind_param("sssssidi", $train_number, $departure_station, $arrival_station, $departure_time, $arrival_time, $platform_number, $price, $available_seats);

                if ($stmt->execute()) {
                    MessageUtility::setSuccessMessage("Schedule added successfully!");
                } else {
                    MessageUtility::setErrorMessage("Error adding schedule: " . $conn->error);
                }
                break;

            case 'edit':
                $schedule_id = $_POST['schedule_id'];
                $train_number = $_POST['train_number'];
                $departure_station = $_POST['departure_station'];
                $arrival_station = $_POST['arrival_station'];
                $departure_time = $_POST['departure_time'];
                $arrival_time = $_POST['arrival_time'];
                $price = $_POST['price'];
                $platform_number = $_POST['platform_number'] ?? null;
                $train_status = $_POST['train_status'];
                $available_seats = $_POST['available_seats'] ?? 100;

                // Get the old schedule details to check what changed
                $old_schedule_sql = "SELECT * FROM schedules WHERE schedule_id = ?";
                $old_schedule_stmt = $conn->prepare($old_schedule_sql);
                $old_schedule_stmt->bind_param("i", $schedule_id);
                $old_schedule_stmt->execute();
                $old_schedule = $old_schedule_stmt->get_result()->fetch_assoc();

                $stmt = $conn->prepare("UPDATE schedules SET train_number = ?, departure_station = ?, arrival_station = ?, departure_time = ?, arrival_time = ?, platform_number = ?, train_status = ?, price = ?, available_seats = ? WHERE schedule_id = ?");
                $stmt->bind_param("sssssisidi", $train_number, $departure_station, $arrival_station, $departure_time, $arrival_time, $platform_number, $train_status, $price, $available_seats, $schedule_id);

                if ($stmt->execute()) {
                    // Initialize NotificationManager
                    $notificationManager = new NotificationManager($conn);

                    // Get affected tickets
                    $tickets_sql = "SELECT t.*, u.user_id 
                                  FROM tickets t 
                                  JOIN users u ON t.user_id = u.user_id 
                                  WHERE t.schedule_id = ? AND t.status = 'active'";
                    $tickets_stmt = $conn->prepare($tickets_sql);
                    $tickets_stmt->bind_param("i", $schedule_id);
                    $tickets_stmt->execute();
                    $affected_tickets = $tickets_stmt->get_result();

                    // Check what changed and notify affected passengers
                    if ($old_schedule['train_status'] !== $train_status) {
                        // Train status changed (delayed/cancelled)
                        $status_message = "Train Status Update\n\n";
                        $status_message .= "Train: {$train_number}\n";
                        $status_message .= "From: {$departure_station}\n";
                        $status_message .= "To: {$arrival_station}\n";
                        $status_message .= "New Status: " . ucfirst($train_status) . "\n";
                        
                        if ($train_status === 'delayed') {
                            $status_message .= "New Departure: " . date('d M Y, h:i A', strtotime($departure_time)) . "\n";
                            $status_message .= "New Arrival: " . date('d M Y, h:i A', strtotime($arrival_time)) . "\n";
                        }
                        
                        while ($ticket = $affected_tickets->fetch_assoc()) {
                            $notificationManager->sendTicketStatusNotification(
                                $ticket['ticket_id'],
                                $train_status,
                                $status_message
                            );
                        }
                        $affected_tickets->data_seek(0); // Reset result pointer
                    }

                    if ($old_schedule['platform_number'] !== $platform_number) {
                        // Platform changed
                        $platform_message = "Platform Change Notice\n\n";
                        $platform_message .= "Train: {$train_number}\n";
                        $platform_message .= "From: {$departure_station}\n";
                        $platform_message .= "To: {$arrival_station}\n";
                        $platform_message .= "New Platform: {$platform_number}\n";
                        $platform_message .= "Departure: " . date('d M Y, h:i A', strtotime($departure_time)) . "\n";
                        
                        while ($ticket = $affected_tickets->fetch_assoc()) {
                            $notificationManager->sendTicketStatusNotification(
                                $ticket['ticket_id'],
                                'platform_change',
                                $platform_message
                            );
                        }
                    }

                    MessageUtility::setSuccessMessage("Schedule updated successfully!");
                } else {
                    MessageUtility::setErrorMessage("Error updating schedule: " . $conn->error);
                }
                break;

            case 'delete':
                $schedule_id = $_POST['schedule_id'];

                // First check if there are any active tickets for this schedule
                $stmt = $conn->prepare("SELECT COUNT(*) as ticket_count FROM tickets WHERE schedule_id = ? AND status = 'active'");
                $stmt->bind_param("i", $schedule_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();

                if ($row['ticket_count'] > 0) {
                    MessageUtility::setErrorMessage("Cannot delete schedule: There are active tickets for this schedule.");
                } else {
                    $stmt = $conn->prepare("DELETE FROM schedules WHERE schedule_id = ?");
                    $stmt->bind_param("i", $schedule_id);

                    if ($stmt->execute()) {
                        MessageUtility::setSuccessMessage("Schedule deleted successfully!");
                    } else {
                        MessageUtility::setErrorMessage("Error deleting schedule: " . $conn->error);
                    }
                }
                break;

            case 'update_status':
                $schedule_id = $_POST['schedule_id'];
                $train_status = $_POST['train_status'];

                $stmt = $conn->prepare("UPDATE schedules SET train_status = ? WHERE schedule_id = ?");
                $stmt->bind_param("si", $train_status, $schedule_id);

                if ($stmt->execute()) {
                    MessageUtility::setSuccessMessage("Schedule status updated successfully!");
                } else {
                    MessageUtility::setErrorMessage("Error updating schedule status: " . $conn->error);
                }
                break;
        }
    }
}

header("Location: manage_schedules.php");
exit();
