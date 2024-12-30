<?php
session_start();
require_once '../config/database.php';

// Check if staff is logged in
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan QR Code - KTM Railway System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <style>
        body {
            overflow-x: hidden;
        }
        .sidebar {
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding: 20px;
            background-color: #343a40;
            color: white;
            width: 250px;
            z-index: 1000;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
            width: calc(100% - 250px);
        }
        .nav-link {
            color: white;
            margin: 10px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .nav-link:hover {
            color: #17a2b8;
        }
        .nav-link.active {
            background-color: #0056b3;
            color: white;
            border-radius: 5px;
            padding: 8px 15px;
        }
        #qr-reader {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
        }
        #qr-reader__scan_region {
            background: white;
        }
        #qr-reader__dashboard {
            padding: 10px;
        }
        .result-container {
            margin-top: 20px;
            padding: 20px;
            border-radius: 5px;
        }
        .result-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        .result-error {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        .scan-overlay {
            position: fixed;
            top: 0;
            left: 250px; /* Align with main content */
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .scan-result {
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 600px;
            width: 90%;
            margin: 20px;
        }
        .ticket-info {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            background: #f8f9fa;
        }
        .ticket-info-row {
            display: flex;
            margin-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 10px;
        }
        .ticket-info-label {
            font-weight: bold;
            width: 150px;
        }
        .verification-success {
            text-align: center;
            padding: 20px;
            background: #d4edda;
            border-radius: 8px;
            margin: 20px 0;
            display: none;
        }
        .verification-success i {
            font-size: 48px;
            color: #28a745;
            margin-bottom: 10px;
        }
        .verification-result {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .verification-time {
            font-size: 14px;
        }
        .scanner-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .instructions-card {
            height: 100%;
        }
        #qr-reader__status {
            display: none; /* Hide the default status text */
        }
        #qr-reader__camera_selection {
            width: 100%;
            margin-bottom: 10px;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="container-fluid p-0">
        <div class="row g-0">
            <!-- Include the sidebar -->
            <?php include 'sidebar.php'; ?>

            <!-- Main Content -->
            <div class="main-content">
                <div class="container">
                    <h2 class="mb-4">Scan QR Code</h2>
                    
                    <div class="row">
                        <div class="col-md-7">
                            <div class="scanner-container">
                                <div id="qr-reader"></div>
                                <div class="text-center mt-3">
                                    <button class="btn btn-primary" onclick="switchCamera()">
                                        <i class='bx bx-refresh'></i> Switch Camera
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-5">
                            <div class="card instructions-card">
                                <div class="card-header">
                                    <h5 class="mb-0">Instructions</h5>
                                </div>
                                <div class="card-body">
                                    <ol class="mb-4">
                                        <li>Allow camera access when prompted</li>
                                        <li>Position the QR code within the scanner frame</li>
                                        <li>Hold steady until the code is recognized</li>
                                        <li>View verification result and ticket details</li>
                                    </ol>
                                    <div class="alert alert-info">
                                        <i class='bx bx-info-circle'></i>
                                        Make sure the QR code is well-lit and clearly visible
                                    </div>
                                    <div class="mt-4">
                                        <h6>Status Indicators:</h6>
                                        <div class="d-flex align-items-center mb-2">
                                            <i class='bx bx-check-circle text-success' style="font-size: 24px;"></i>
                                            <span class="ms-2">Green - Valid Ticket</span>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <i class='bx bx-x-circle text-danger' style="font-size: 24px;"></i>
                                            <span class="ms-2">Red - Invalid/Used Ticket</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scan Result Overlay -->
    <div class="scan-overlay" id="scanOverlay">
        <div class="scan-result">
            <h4 class="mb-4">Ticket Details</h4>
            
            <div class="verification-success" id="verificationSuccess">
                <i class='bx bx-check-circle'></i>
                <h4>Ticket Successfully Verified!</h4>
                <p>This is a valid ticket and has been marked as scanned.</p>
            </div>

            <div class="ticket-info" id="ticketDetails">
                <!-- Ticket details will be inserted here -->
            </div>

            <div class="mt-3 text-center">
                <button class="btn btn-secondary" onclick="closeScanResult()">
                    <i class='bx bx-x'></i> Close
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
        let html5QrcodeScanner;
        let currentTicketId = null;
        let currentCamera = 'environment'; // Default to back camera

        function startScanner() {
            if (html5QrcodeScanner) {
                html5QrcodeScanner.clear();
            }

            html5QrcodeScanner = new Html5QrcodeScanner(
                "qr-reader", 
                { 
                    fps: 10,
                    qrbox: {width: 250, height: 250},
                    aspectRatio: 1.0,
                    facingMode: currentCamera
                }
            );

            html5QrcodeScanner.render(onScanSuccess, onScanFailure);
        }

        function switchCamera() {
            currentCamera = currentCamera === 'environment' ? 'user' : 'environment';
            startScanner();
        }

        function displayTicketDetails(ticket) {
            const details = `
                <div class="verification-result text-center mb-4">
                    <i class='bx bx-check-circle' style="font-size: 64px; color: #28a745;"></i>
                    <h3 class="mt-2">Ticket Successfully Verified!</h3>
                </div>
                <div class="ticket-info-row">
                    <div class="ticket-info-label">Ticket ID:</div>
                    <div>#${ticket.ticket_id}</div>
                </div>
                <div class="ticket-info-row">
                    <div class="ticket-info-label">Train Number:</div>
                    <div>${ticket.train_number}</div>
                </div>
                <div class="ticket-info-row">
                    <div class="ticket-info-label">From:</div>
                    <div>${ticket.departure_station}</div>
                </div>
                <div class="ticket-info-row">
                    <div class="ticket-info-label">To:</div>
                    <div>${ticket.arrival_station}</div>
                </div>
                <div class="ticket-info-row">
                    <div class="ticket-info-label">Departure:</div>
                    <div>${formatDateTime(ticket.departure_time)}</div>
                </div>
                <div class="ticket-info-row">
                    <div class="ticket-info-label">Arrival:</div>
                    <div>${formatDateTime(ticket.arrival_time)}</div>
                </div>
                <div class="ticket-info-row">
                    <div class="ticket-info-label">Platform:</div>
                    <div>${ticket.platform_number}</div>
                </div>
                ${ticket.seat_number ? `
                <div class="ticket-info-row">
                    <div class="ticket-info-label">Seat Number:</div>
                    <div>${ticket.seat_number}</div>
                </div>` : ''}
                <div class="ticket-info-row">
                    <div class="ticket-info-label">Price:</div>
                    <div>RM ${parseFloat(ticket.price).toFixed(2)}</div>
                </div>
                <div class="verification-time text-center mt-3">
                    <small class="text-muted">Verified at ${formatDateTime(new Date())}</small>
                </div>
            `;

            document.getElementById('ticketDetails').innerHTML = details;
            document.getElementById('scanOverlay').style.display = 'flex';
        }

        function formatDateTime(dateTimeStr) {
            const dt = new Date(dateTimeStr);
            return dt.toLocaleString('en-MY', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });
        }

        function onScanSuccess(decodedText, decodedResult) {
            try {
                const ticketData = JSON.parse(decodedText);
                if (ticketData.ticket_id) {
                    // Stop scanning temporarily
                    if (html5QrcodeScanner) {
                        html5QrcodeScanner.pause();
                    }

                    // Immediately verify the ticket
                    fetch('verify_ticket.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'ticket_id=' + ticketData.ticket_id
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Play success sound
                            const audio = new Audio('assets/scan-success.mp3');
                            audio.play();

                            // Show success message
                            document.getElementById('verificationSuccess').style.display = 'block';
                            document.getElementById('verifyButton').style.display = 'none';
                            
                            // Fetch and display ticket details
                            fetch('verify_ticket.php?ticket_id=' + ticketData.ticket_id)
                                .then(response => response.json())
                                .then(detailsData => {
                                    if (detailsData.success) {
                                        displayTicketDetails(detailsData.ticket);
                                    }
                                });

                            // Resume scanner after 3 seconds
                            setTimeout(() => {
                                document.getElementById('scanOverlay').style.display = 'none';
                                if (html5QrcodeScanner) {
                                    html5QrcodeScanner.resume();
                                }
                            }, 3000);
                        } else {
                            // Show error message
                            const errorDiv = document.createElement('div');
                            errorDiv.className = 'alert alert-danger text-center';
                            errorDiv.innerHTML = `
                                <i class='bx bx-error-circle' style="font-size: 48px; display: block; margin-bottom: 10px;"></i>
                                <h4>Verification Failed</h4>
                                <p>${data.message}</p>
                            `;
                            document.getElementById('ticketDetails').innerHTML = '';
                            document.getElementById('verificationSuccess').style.display = 'none';
                            document.getElementById('verifyButton').style.display = 'none';
                            document.getElementById('ticketDetails').appendChild(errorDiv);
                            document.getElementById('scanOverlay').style.display = 'flex';

                            // Play error sound
                            const errorAudio = new Audio('assets/scan-error.mp3');
                            errorAudio.play();

                            // Resume scanner after 2 seconds
                            setTimeout(() => {
                                document.getElementById('scanOverlay').style.display = 'none';
                                if (html5QrcodeScanner) {
                                    html5QrcodeScanner.resume();
                                }
                            }, 2000);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error verifying ticket');
                        if (html5QrcodeScanner) {
                            html5QrcodeScanner.resume();
                        }
                    });
                }
            } catch (error) {
                console.error('Error parsing QR code:', error);
                if (html5QrcodeScanner) {
                    html5QrcodeScanner.resume();
                }
            }
        }

        function onScanFailure(error) {
            // Handle scan failure silently
        }

        function verifyTicket() {
            if (!currentTicketId) return;

            fetch('verify_ticket.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'ticket_id=' + currentTicketId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('verifyButton').style.display = 'none';
                    document.getElementById('verificationSuccess').style.display = 'block';
                    
                    // Log the verification
                    fetch('log_verification.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'ticket_id=' + currentTicketId
                    });
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error verifying ticket');
            });
        }

        function closeScanResult() {
            document.getElementById('scanOverlay').style.display = 'none';
            if (html5QrcodeScanner) {
                html5QrcodeScanner.resume();
            }
        }

        window.onload = startScanner;
    </script>
</body>
</html>
