<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Authentication</title>
    <style>
        .banner img { width: 100%; height: auto; }
        .content { padding: 20px; text-align: center; }
        .content h2 { color: #004080; font-size: 28px; margin-bottom: 15px; }
        .content p { margin: 10px 0; line-height: 1.6; font-size: 16px; }
        .steps { text-align: left; display: inline-block; margin: 20px auto; }
        .steps li { margin: 10px 0; font-size: 16px; }
        .qr-code img { 
            margin-top: 20px; 
            width: 200px; 
            animation: bounce 2s infinite alternate; 
        }
        
        @keyframes bounce {
            0% {
                transform: scale(1);
                opacity: 1;
            }
            100% {
                transform: scale(1.1);
                opacity: 0.8;
            }
        }
        .qr-code:hover {
            animation: rotate 2s infinite; 
        }

        @keyframes rotate {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }

    </style>
</head>
<body>

    <?php include 'Head_and_Foot\header.php'; ?>
    <div class="content">
        <h2>QR Code-Based Authentication</h2>
        <p>Secure your railway ticket with our QR Code-based authentication. Scan your unique QR code at the station entry points for smooth and fast verification.</p>
        
        <h2>How to Generate a QR Code</h2>
        <div class="steps">
            <ol>
                <li>Log in to your KTM account.</li>
                <li>Book your ticket and complete the payment process.</li>
                <li>Download the QR Code from your account dashboard.</li>
                <li>Present the QR Code at the railway station for validation.</li>
            </ol>
        </div>

        <h2>Your QR Code</h2>
        <div class="qr-code">
            <img src="ex.png" alt="QR Code Sample">
            <p>This QR Code is unique to your booking. Please keep it safe.</p>
        </div>
    </div>

    <?php include 'Head_and_Foot\footer.php'; ?>
</body>
</html>
