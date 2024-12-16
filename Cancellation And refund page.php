<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancellation and Refund</title>
    <style>
        .content { 
            padding: 20px; 
            text-align: center; 
        }
        .content h2 { 
            color: #004080; 
            font-size: 28px; 
            margin-bottom: 15px; 
        }
        .content p { 
            margin: 10px 0; 
            line-height: 1.6; 
            font-size: 16px; 
        }
        .steps, .policy { 
            text-align: left; 
            display: inline-block; 
            margin: 20px auto; 
        }
        .steps li, .policy li { 
            margin: 10px 0; 
            font-size: 16px; 
        }
    </style>
</head>
<body>

    <?php include 'Head_and_Foot\header.php'; ?>

    <div class="content">
        <h2>Cancellation and Refund Policy</h2>
        <p>We understand that plans can change. Use our cancellation feature to get a refund under our policies.</p>
        
        <h2>Steps to Cancel Your Ticket</h2>
        <div class="steps">
            <ol>
                <li>Log in to your KTM account.</li>
                <li>Navigate to "My Bookings" and select the ticket you want to cancel.</li>
                <li>Click "Cancel Ticket" and confirm your request.</li>
                <li>You will receive a confirmation email and refund details.</li>
            </ol>
        </div>

        <h2>Refund Policy</h2>
        <div class="policy">
            <ul>
                <li>Cancellation requests must be made at least 24 hours before departure.</li>
                <li>Service charges will apply for all refunds.</li>
                <li>Refunds will be processed within 7 working days.</li>
                <li>No refunds for missed departures.</li>
            </ul>
        </div>
    </div>

    <?php include 'Head_and_Foot\footer.php'; ?>

</body>
</html>
