<?php
// Include PHPMailer files
require_once __DIR__ . '/../lib/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../lib/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../lib/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class NotificationManager {
    private $conn;
    private $mailer;

    public function __construct($conn) {
        if (!$conn) {
            require_once __DIR__ . '/../config/database.php';
        }
        $this->conn = $conn ?? $GLOBALS['conn'];
        $this->initializeMailer();
    }

    private function initializeMailer() {
        $this->mailer = new PHPMailer(true);
        $this->mailer->isSMTP();
        $this->mailer->Host = 'smtp.gmail.com';
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = 'kaizen20020222@gmail.com';
        $this->mailer->Password = 'xkrh kblr dhvt ihcp';
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = 587;
        $this->mailer->setFrom('kaizen20020222@gmail.com', 'KTM Railway System');
    }

    private function getTicketDetails($ticketId) {
        $sql = "SELECT t.*, s.train_number, s.departure_station, s.arrival_station, 
                       s.departure_time, s.arrival_time, s.platform_number,
                       u.email, u.username as user_name
                FROM tickets t
                JOIN schedules s ON t.schedule_id = s.schedule_id
                JOIN users u ON t.user_id = u.user_id
                WHERE t.ticket_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $ticketId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    private function getEmailTemplate($type, $ticketDetails) {
        $template = [
            'subject' => '',
            'message' => ''
        ];

        switch ($type) {
            case 'booked':
                $template['subject'] = "KTM Ticket Booking Confirmation - {$ticketDetails['train_number']}";
                $template['message'] = "Dear {$ticketDetails['user_name']},\n\n";
                $template['message'] .= "Thank you for booking with KTM Railway System. Your ticket has been confirmed!\n\n";
                $template['message'] .= "Booking Details:\n";
                $template['message'] .= "Ticket ID: {$ticketDetails['ticket_id']}\n";
                $template['message'] .= "Train Number: {$ticketDetails['train_number']}\n";
                $template['message'] .= "From: {$ticketDetails['departure_station']}\n";
                $template['message'] .= "To: {$ticketDetails['arrival_station']}\n";
                $template['message'] .= "Departure: " . date('d M Y, h:i A', strtotime($ticketDetails['departure_time'])) . "\n";
                $template['message'] .= "Arrival: " . date('d M Y, h:i A', strtotime($ticketDetails['arrival_time'])) . "\n";
                $template['message'] .= "Platform: {$ticketDetails['platform_number']}\n";
                $template['message'] .= "Passenger Name: {$ticketDetails['passenger_name']}\n";
                $template['message'] .= "Seat(s): {$ticketDetails['seat_number']}\n";
                $template['message'] .= "Number of Tickets: {$ticketDetails['num_seats']}\n";
                $template['message'] .= "Total Amount: RM " . number_format($ticketDetails['payment_amount'], 2) . "\n\n";
                $template['message'] .= "Your e-ticket has been attached to this email. Please present this at the station.\n\n";
                $template['message'] .= "Important Notes:\n";
                $template['message'] .= "- Please arrive at least 30 minutes before departure\n";
                $template['message'] .= "- Keep this ticket safe and present it during inspection\n";
                break;

            case 'cancelled':
                $template['subject'] = "KTM Ticket Cancellation Confirmation - {$ticketDetails['train_number']}";
                $template['message'] = "Dear {$ticketDetails['user_name']},\n\n";
                $template['message'] .= "Your ticket has been successfully cancelled.\n\n";
                $template['message'] .= "Cancelled Ticket Details:\n";
                $template['message'] .= "Ticket ID: {$ticketDetails['ticket_id']}\n";
                $template['message'] .= "Train Number: {$ticketDetails['train_number']}\n";
                $template['message'] .= "From: {$ticketDetails['departure_station']}\n";
                $template['message'] .= "To: {$ticketDetails['arrival_station']}\n";
                $template['message'] .= "Original Departure: " . date('d M Y, h:i A', strtotime($ticketDetails['departure_time'])) . "\n";
                $template['message'] .= "Refund Amount: RM " . number_format($ticketDetails['payment_amount'], 2) . "\n\n";
                $template['message'] .= "Your refund will be processed within 3-5 business days.\n";
                break;

            case 'delayed':
                $template['subject'] = "Important: Train Delay Notice - {$ticketDetails['train_number']}";
                $template['message'] = "Dear {$ticketDetails['user_name']},\n\n";
                $template['message'] .= "We regret to inform you that your train has been delayed.\n\n";
                $template['message'] .= "Affected Train Details:\n";
                $template['message'] .= "Train Number: {$ticketDetails['train_number']}\n";
                $template['message'] .= "From: {$ticketDetails['departure_station']}\n";
                $template['message'] .= "To: {$ticketDetails['arrival_station']}\n";
                $template['message'] .= "Original Departure: " . date('d M Y, h:i A', strtotime($ticketDetails['departure_time'])) . "\n";
                $template['message'] .= "New Departure: " . date('d M Y, h:i A', strtotime($ticketDetails['new_departure_time'])) . "\n\n";
                $template['message'] .= "We apologize for any inconvenience caused.\n";
                break;

            case 'platform_change':
                $template['subject'] = "Platform Change Notice - {$ticketDetails['train_number']}";
                $template['message'] = "Dear {$ticketDetails['user_name']},\n\n";
                $template['message'] .= "Please note that there has been a platform change for your train.\n\n";
                $template['message'] .= "Train Details:\n";
                $template['message'] .= "Train Number: {$ticketDetails['train_number']}\n";
                $template['message'] .= "From: {$ticketDetails['departure_station']}\n";
                $template['message'] .= "To: {$ticketDetails['arrival_station']}\n";
                $template['message'] .= "Departure: " . date('d M Y, h:i A', strtotime($ticketDetails['departure_time'])) . "\n";
                $template['message'] .= "New Platform: {$ticketDetails['platform_number']}\n\n";
                $template['message'] .= "Please proceed to the new platform.\n";
                break;
        }

        $template['message'] .= "\nFor any assistance, please contact our customer service or visit the nearest KTM counter.\n\n";
        $template['message'] .= "Thank you for choosing KTM Railway System.\n";
        $template['message'] .= "This is an automated message, please do not reply.";

        return $template;
    }

    public function sendTicketStatusNotification($ticketId, $type, $customMessage = null) {
        try {
            $ticketDetails = $this->getTicketDetails($ticketId);
            if (!$ticketDetails) {
                error_log("Error: Ticket details not found for ID: $ticketId");
                return false;
            }

            // Get email template
            $template = $this->getEmailTemplate($type, $ticketDetails);
            
            // Use custom message if provided
            $message = $customMessage ?? $template['message'];

            // Create notification in database
            $this->createNotification($ticketDetails['user_id'], $type, $message, $ticketId);

            // Send email
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($ticketDetails['email']);
            $this->mailer->isHTML(false);
            $this->mailer->Subject = $template['subject'];
            $this->mailer->Body = $message;

            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Error sending notification: " . $e->getMessage());
            return false;
        }
    }

    public function createNotification($userId, $type, $message, $relatedId = null) {
        try {
            if (!$this->conn) {
                return false;
            }
            
            $query = "INSERT INTO notifications (user_id, type, message, related_id, created_at, is_read) 
                     VALUES (?, ?, ?, ?, NOW(), 0)";
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                error_log("Error preparing statement: " . $this->conn->error);
                return false;
            }
            
            $stmt->bind_param("issi", $userId, $type, $message, $relatedId);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error creating notification: " . $e->getMessage());
            return false;
        }
    }

    public function sendTrainDelayNotification($scheduleId, $delayMinutes, $reason = '') {
        // Get affected tickets and users
        try {
            if (!$this->conn) {
                return false;
            }
            
            $query = "SELECT t.ticket_id, t.user_id, u.email, u.username, u.no_phone, 
                            s.train_number, s.departure_station, s.arrival_station, s.departure_time 
                     FROM tickets t 
                     JOIN users u ON t.user_id = u.user_id 
                     JOIN schedules s ON t.schedule_id = s.schedule_id 
                     WHERE t.schedule_id = ? AND t.status = 'active'";
            
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                error_log("Error preparing statement: " . $this->conn->error);
                return false;
            }
            
            $stmt->bind_param("i", $scheduleId);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($ticket = $result->fetch_assoc()) {
                // Create delay message
                $message = $this->createDelayMessage($ticket, $delayMinutes, $reason);

                // Save to database
                $this->createNotification($ticket['user_id'], 'train_delay', $message, $ticket['ticket_id']);

                // Send email
                $this->sendEmail($ticket['email'], "KTM Train Delay - {$ticket['train_number']}", $message);

                // Send SMS if phone number is available
                if (!empty($ticket['no_phone'])) {
                    $this->sendSMS($ticket['no_phone'], $message);
                }
            }

            return true;
        } catch (Exception $e) {
            error_log("Error sending train delay notification: " . $e->getMessage());
            return false;
        }
    }

    public function sendTrainCancellationNotification($scheduleId, $reason = '') {
        // Get affected tickets and users
        try {
            if (!$this->conn) {
                return false;
            }
            
            $query = "SELECT t.ticket_id, t.user_id, u.email, u.username, u.no_phone, 
                            s.train_number, s.departure_station, s.arrival_station, s.departure_time 
                     FROM tickets t 
                     JOIN users u ON t.user_id = u.user_id 
                     JOIN schedules s ON t.schedule_id = s.schedule_id 
                     WHERE t.schedule_id = ? AND t.status = 'active'";
            
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                error_log("Error preparing statement: " . $this->conn->error);
                return false;
            }
            
            $stmt->bind_param("i", $scheduleId);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($ticket = $result->fetch_assoc()) {
                // Create cancellation message
                $message = $this->createCancellationMessage($ticket, $reason);

                // Save to database
                $this->createNotification($ticket['user_id'], 'train_cancellation', $message, $ticket['ticket_id']);

                // Send email
                $this->sendEmail($ticket['email'], "KTM Train Cancellation - {$ticket['train_number']}", $message);

                // Send SMS if phone number is available
                if (!empty($ticket['no_phone'])) {
                    $this->sendSMS($ticket['no_phone'], $message);
                }
            }

            return true;
        } catch (Exception $e) {
            error_log("Error sending train cancellation notification: " . $e->getMessage());
            return false;
        }
    }

    private function createDelayMessage($ticket, $delayMinutes, $reason) {
        $message = "Dear {$ticket['username']},\n\n";
        $message .= "Your train has been delayed:\n";
        $message .= "Train: {$ticket['train_number']}\n";
        $message .= "Route: {$ticket['departure_station']} to {$ticket['arrival_station']}\n";
        $message .= "Original Departure: " . date('d M Y, h:i A', strtotime($ticket['departure_time'])) . "\n";
        $message .= "Delay: {$delayMinutes} minutes\n";
        
        if (!empty($reason)) {
            $message .= "Reason: {$reason}\n";
        }

        $message .= "\nWe apologize for any inconvenience caused. For more information, please log in to your KTM account or contact our support.";
        return $message;
    }

    private function createCancellationMessage($ticket, $reason) {
        $message = "Dear {$ticket['username']},\n\n";
        $message .= "Unfortunately, your train has been cancelled:\n";
        $message .= "Train: {$ticket['train_number']}\n";
        $message .= "Route: {$ticket['departure_station']} to {$ticket['arrival_station']}\n";
        $message .= "Scheduled Departure: " . date('d M Y, h:i A', strtotime($ticket['departure_time'])) . "\n";
        
        if (!empty($reason)) {
            $message .= "Reason: {$reason}\n";
        }

        $message .= "\nPlease log in to your KTM account for refund information or contact our support for assistance.";
        return $message;
    }

    private function sendEmail($to, $subject, $message) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $message;
            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }

    private function sendSMS($phoneNumber, $message) {
        // Further exploration
        return true;
    }

    public function getUnreadNotifications($userId) {
        try {
            if (!$this->conn) {
                return [];
            }
            
            $query = "SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC";
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                error_log("Error preparing statement: " . $this->conn->error);
                return [];
            }
            
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting notifications: " . $e->getMessage());
            return [];
        }
    }

    public function markNotificationAsRead($notificationId) {
        try {
            if (!$this->conn) {
                return false;
            }
            
            $query = "UPDATE notifications SET is_read = 1 WHERE notification_id = ?";
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                error_log("Error preparing statement: " . $this->conn->error);
                return false;
            }
            
            $stmt->bind_param("i", $notificationId);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error marking notification as read: " . $e->getMessage());
            return false;
        }
    }
}
?>
