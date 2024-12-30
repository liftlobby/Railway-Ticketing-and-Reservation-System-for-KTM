<?php
session_start();
require_once 'config/database.php';
require_once 'includes/MessageUtility.php';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8');
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $problem = htmlspecialchars(trim($_POST['problem'] ?? ''), ENT_QUOTES, 'UTF-8');
    $ticketNumber = htmlspecialchars(trim($_POST['ticket-number'] ?? ''), ENT_QUOTES, 'UTF-8');
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($problem)) {
        MessageUtility::setErrorMessage("Please fill in all required fields.");
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        MessageUtility::setErrorMessage("Please enter a valid email address.");
    } else {
        try {
            // Start transaction
            $conn->begin_transaction();
            
            // Insert the report
            $stmt = $conn->prepare("INSERT INTO reports (user_id, name, email, problem_description, ticket_number, status, priority) VALUES (?, ?, ?, ?, ?, 'pending', 'medium')");
            $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            $stmt->bind_param("issss", $userId, $name, $email, $problem, $ticketNumber);
            
            if ($stmt->execute()) {
                $reportId = $conn->insert_id;
                
                // Try to send email, but don't let email failure affect the report submission
                try {
                    // Prepare email content
                    $to = $email;
                    $subject = "Report Submission Confirmation - KTM Railway System";
                    $message = "
                        <html>
                        <head>
                            <title>Report Submission Confirmation</title>
                        </head>
                        <body>
                            <h2>Thank you for your report</h2>
                            <p>Dear $name,</p>
                            <p>We have received your report regarding the following issue:</p>
                            <p><em>\"" . substr($problem, 0, 100) . (strlen($problem) > 100 ? '...' : '') . "\"</em></p>
                            <p>Your Report ID is: <strong>#$reportId</strong></p>
                            " . ($ticketNumber ? "<p>Referenced Ticket Number: $ticketNumber</p>" : "") . "
                            <p>We will review your report and get back to you as soon as possible.</p>
                            <p>Best regards,<br>KTM Railway System Team</p>
                        </body>
                        </html>
                    ";
                    
                    $headers = "MIME-Version: 1.0\r\n";
                    $headers .= "Content-type: text/html; charset=utf-8\r\n";
                    $headers .= "From: KTM Railway System <noreply@ktm.com>\r\n";
                    
                    // Try to send email but don't throw error if it fails
                    @mail($to, $subject, $message, $headers);
                } catch (Exception $emailError) {
                    // Log email error but continue with report submission
                    error_log("Failed to send confirmation email for report #$reportId: " . $emailError->getMessage());
                }
                
                $conn->commit();
                
                // Store report details in session for display
                $_SESSION['last_report'] = [
                    'id' => $reportId,
                    'name' => $name,
                    'email' => $email,
                    'problem' => $problem,
                    'ticket_number' => $ticketNumber,
                    'submitted_at' => date('Y-m-d H:i:s')
                ];
                
                MessageUtility::setSuccessMessage("
                    <div class='report-confirmation'>
                        <h4>Report Submitted Successfully!</h4>
                        <p>Report ID: <strong>#$reportId</strong></p>
                        <p>Please save this Report ID for future reference.</p>
                        <hr>
                        <div class='report-details'>
                            <p><strong>Name:</strong> $name</p>
                            <p><strong>Email:</strong> $email</p>
                            " . ($ticketNumber ? "<p><strong>Ticket Number:</strong> $ticketNumber</p>" : "") . "
                            <p><strong>Status:</strong> Pending Review</p>
                        </div>
                        <p class='note'>Our team will review your report and respond via email.</p>
                    </div>
                ");
                
                // Clear form data
                $name = $email = $problem = $ticketNumber = '';
            } else {
                throw new Exception("Failed to submit report. Please try again.");
            }
        } catch (Exception $e) {
            $conn->rollback();
            MessageUtility::setErrorMessage($e->getMessage());
        }
    }
}

// Get report details from session if available
$lastReport = $_SESSION['last_report'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report a Problem - KTM Railway System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .form-container {
            max-width: 600px;
            margin: 30px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .form-container h2 {
            color: #0056b3;
            margin-bottom: 30px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            font-weight: 500;
            margin-bottom: 5px;
            color: #333;
        }
        textarea {
            min-height: 120px;
            resize: vertical;
        }
        .btn-submit {
            background-color: #0056b3;
            color: white;
            padding: 10px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
        }
        .btn-submit:hover {
            background-color: #003d82;
        }
        .feedback-container {
            margin-top: 20px;
            padding: 15px;
            border-radius: 5px;
        }
        .feedback-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .feedback-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .report-confirmation {
            text-align: left;
            padding: 15px;
        }
        .report-confirmation h4 {
            color: #155724;
            margin-bottom: 15px;
        }
        .report-details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .report-details p {
            margin-bottom: 8px;
        }
        .note {
            font-style: italic;
            color: #666;
            margin-top: 15px;
            font-size: 0.9em;
        }
        hr {
            margin: 15px 0;
            border-color: #c3e6cb;
        }
    </style>
</head>
<body>
    <?php include 'Head_and_Foot/header.php'; ?>
    
    <main class="container">
        <div class="form-container">
            <h2>Report a Problem</h2>
            
            <?php if (MessageUtility::hasSuccessMessage()): ?>
                <div class="feedback-container feedback-success">
                    <?php echo MessageUtility::getSuccessMessage(); ?>
                </div>
            <?php elseif (MessageUtility::hasErrorMessage()): ?>
                <div class="feedback-container feedback-error">
                    <?php echo MessageUtility::getErrorMessage(); ?>
                </div>
            <?php endif; ?>

            <?php if (!isset($_SESSION['last_report'])): ?>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="name">Your Name *</label>
                    <input type="text" class="form-control" id="name" name="name" required 
                           value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="email">Your Email *</label>
                    <input type="email" class="form-control" id="email" name="email" required
                           value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="problem">Problem Description *</label>
                    <textarea class="form-control" id="problem" name="problem" required><?php echo isset($problem) ? htmlspecialchars($problem) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label for="ticket-number">Ticket Number (if applicable)</label>
                    <input type="text" class="form-control" id="ticket-number" name="ticket-number"
                           value="<?php echo isset($ticketNumber) ? htmlspecialchars($ticketNumber) : ''; ?>">
                </div>

                <button type="submit" class="btn btn-submit">Submit Report</button>
            </form>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'Head_and_Foot/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php
    // Clear the last report from session after displaying
    if (isset($_SESSION['last_report'])) {
        unset($_SESSION['last_report']);
    }
    ?>
</body>
</html>
