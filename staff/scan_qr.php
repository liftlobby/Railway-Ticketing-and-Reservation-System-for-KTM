<?php
session_start();
require_once '../config/database.php';
require_once '../includes/MessageUtility.php';

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
    <link href="https://unpkg.com/html5-qrcode"></link>
    <style>
        .sidebar {
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding: 20px;
            background-color: #343a40;
            color: white;
            width: 250px;
        }
        .content {
            margin-left: 250px;
            padding: 20px;
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
        }
        .dashboard-header {
            margin-bottom: 30px;
        }
        .dashboard-header h2 {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            padding: 15px 20px;
            font-weight: 600;
        }
        .card-body {
            padding: 20px;
        }
        #qr-reader {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
        }
        .scan-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1050;
        }
        .scan-result {
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 600px;
            width: 90%;
            margin: 20px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            max-height: 90vh;
            overflow-y: auto;
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
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        .ticket-info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .ticket-info-label {
            font-weight: 600;
            min-width: 150px;
            color: #2c3e50;
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
            text-transform: uppercase;
        }
        .status-badge.active {
            background-color: #d4edda;
            color: #155724;
        }
        .status-badge.used {
            background-color: #cce5ff;
            color: #004085;
        }
        .status-badge.cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        .camera-switch {
            margin-bottom: 15px;
            text-align: center;
        }
        #manual-entry {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }
        .alert {
            margin-bottom: 20px;
            border: none;
            border-radius: 8px;
        }
        .btn {
            border-radius: 6px;
            padding: 8px 16px;
            font-weight: 500;
        }
        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .btn-primary:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
        }
        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
        }
        .btn-secondary:hover {
            background-color: #5c636a;
            border-color: #565e64;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col content">
                <div class="dashboard-header">
                    <h2>Scan QR Code</h2>
                    <p class="text-muted mb-0">Scan and verify ticket QR codes</p>
                </div>

                <?php MessageUtility::displayMessages(); ?>

                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <i class='bx bx-qr-scan me-2'></i>QR Scanner
                            </div>
                            <div class="card-body">
                                <div class="camera-switch">
                                    <button class="btn btn-outline-primary mb-3" onclick="switchCamera()">
                                        <i class='bx bx-refresh me-1'></i> Switch Camera
                                    </button>
                                </div>
                                <div id="qr-reader"></div>
                                <div id="manual-entry">
                                    <h5 class="mb-3">Manual Entry</h5>
                                    <div class="input-group">
                                        <input type="text" id="manual-ticket-id" class="form-control" placeholder="Enter Ticket ID">
                                        <button class="btn btn-primary" onclick="checkManualTicket()">
                                            <i class='bx bx-search me-1'></i> Check Ticket
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <i class='bx bx-info-circle me-2'></i>Instructions
                            </div>
                            <div class="card-body">
                                <ol class="ps-3 mb-4">
                                    <li class="mb-2">Position the QR code within the scanner frame</li>
                                    <li class="mb-2">Hold the device steady until the code is recognized</li>
                                    <li class="mb-2">Verify the ticket information displayed</li>
                                    <li class="mb-2">Click "Verify Ticket" to mark it as used</li>
                                </ol>
                                <div class="alert alert-info mb-0">
                                    <i class='bx bx-info-circle me-2'></i>
                                    If scanning doesn't work, you can manually enter the ticket ID below the scanner.
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <i class='bx bx-time-five me-2'></i>Time Window
                            </div>
                            <div class="card-body">
                                <p class="mb-0">Tickets can only be verified:</p>
                                <ul class="mb-0 ps-3">
                                    <li>Up to 3 hours before departure</li>
                                    <li>Up to 3 hours after departure</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scan Result Modal -->
    <div class="scan-overlay" id="scanOverlay">
        <div class="scan-result">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0"><i class='bx bx-ticket me-2'></i>Ticket Information</h4>
                <button type="button" class="btn-close" onclick="closeScanResult()" aria-label="Close"></button>
            </div>
            <div id="ticketInfo" class="ticket-info">
                <!-- Ticket information will be populated here -->
            </div>
            <div class="d-flex justify-content-end gap-3 mt-4">
                <button class="btn btn-secondary" onclick="closeScanResult()">
                    <i class='bx bx-x me-1'></i>Close
                </button>
                <button class="btn btn-primary" onclick="verifyTicket()" id="verifyButton">
                    <i class='bx bx-check me-1'></i>Verify Ticket
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
        let html5QrcodeScanner;
        let currentTicketId = null;
        let currentCamera = 'environment';

        function showAlert(message, type = 'success') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.role = 'alert';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            document.querySelector('.dashboard-header').insertAdjacentElement('afterend', alertDiv);
            
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }

        function onScanSuccess(decodedText) {
            try {
                // First try to parse as JSON
                const ticketData = JSON.parse(decodedText);
                if (ticketData.ticket_id) {
                    checkTicket(ticketData.ticket_id);
                } else if (ticketData.id) {
                    checkTicket(ticketData.id); // Fallback for different JSON structure
                }
            } catch (e) {
                // If not JSON, try to use the text directly as ticket ID
                const ticketId = decodedText.trim();
                if (ticketId.match(/^\d+$/)) {
                    checkTicket(ticketId);
                } else {
                    console.error('Invalid QR code format:', decodedText);
                    showAlert('Invalid QR code format. Please try scanning again.', 'danger');
                }
            }
        }

        function checkManualTicket() {
            const ticketId = document.getElementById('manual-ticket-id').value;
            if (ticketId && ticketId.match(/^\d+$/)) {
                checkTicket(ticketId);
            } else {
                showAlert('Please enter a valid ticket ID', 'danger');
            }
        }

        function checkTicket(ticketId) {
            currentTicketId = ticketId;
            
            // Show loading state
            showAlert('Checking ticket...', 'info');
            
            fetch(`verify_ticket.php?ticket_id=${ticketId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.ticket) {
                        displayTicketInfo(data.ticket);
                        document.getElementById('scanOverlay').style.display = 'flex';
                    } else {
                        throw new Error(data.message || 'Error checking ticket');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert(error.message || 'Error checking ticket. Please try again.', 'danger');
                });
        }

        function displayTicketInfo(ticket) {
            // Show the overlay immediately
            document.getElementById('scanOverlay').style.display = 'flex';
            
            const verifyButton = document.getElementById('verifyButton');
            
            // Format dates for better display
            const departureTime = new Date(ticket.departure_time);
            const formattedDepartureTime = departureTime.toLocaleString('en-MY', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });

            const statusClass = {
                'active': 'active',
                'used': 'used',
                'cancelled': 'cancelled'
            }[ticket.status] || '';

            const ticketInfo = document.getElementById('ticketInfo');
            ticketInfo.innerHTML = `
                <div class="alert alert-info mb-4">
                    <i class='bx bx-info-circle me-2'></i>
                    Scan completed successfully. Please verify the ticket details below.
                </div>
                <div class="ticket-info-row">
                    <div class="ticket-info-label">Ticket ID</div>
                    <div class="fw-bold">${ticket.ticket_id || 'N/A'}</div>
                </div>
                <div class="ticket-info-row">
                    <div class="ticket-info-label">Passenger</div>
                    <div>${ticket.username || 'N/A'}</div>
                </div>
                <div class="ticket-info-row">
                    <div class="ticket-info-label">Train</div>
                    <div>${ticket.train_number || 'N/A'}</div>
                </div>
                <div class="ticket-info-row">
                    <div class="ticket-info-label">From</div>
                    <div>${ticket.departure_station || 'N/A'}</div>
                </div>
                <div class="ticket-info-row">
                    <div class="ticket-info-label">To</div>
                    <div>${ticket.arrival_station || 'N/A'}</div>
                </div>
                <div class="ticket-info-row">
                    <div class="ticket-info-label">Departure</div>
                    <div>${formattedDepartureTime || 'N/A'}</div>
                </div>
                <div class="ticket-info-row">
                    <div class="ticket-info-label">Platform</div>
                    <div>${ticket.platform_number || 'N/A'}</div>
                </div>
                <div class="ticket-info-row">
                    <div class="ticket-info-label">Seats</div>
                    <div>${ticket.seat_display || 'N/A'}</div>
                </div>
                <div class="ticket-info-row">
                    <div class="ticket-info-label">Number of Seats</div>
                    <div>${ticket.num_seats || 1}</div>
                </div>
                <div class="ticket-info-row">
                    <div class="ticket-info-label">Status</div>
                    <div><span class="status-badge ${statusClass}">${(ticket.status || 'unknown').toUpperCase()}</span></div>
                </div>
            `;

            // Update verify button state based on ticket status
            if (ticket.status === 'active') {
                verifyButton.disabled = false;
                verifyButton.classList.remove('btn-secondary');
                verifyButton.classList.add('btn-primary');
                verifyButton.title = 'Click to verify this ticket';
            } else {
                verifyButton.disabled = true;
                verifyButton.classList.remove('btn-primary');
                verifyButton.classList.add('btn-secondary');
                verifyButton.title = `This ticket cannot be verified (Status: ${ticket.status})`;
            }
        }

        function verifyTicket() {
            if (!currentTicketId) return;

            const formData = new FormData();
            formData.append('ticket_id', currentTicketId);

            fetch('verify_ticket.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Ticket verified successfully!', 'success');
                    closeScanResult();
                    location.reload();
                } else {
                    showAlert(data.message || 'Error verifying ticket', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error verifying ticket', 'danger');
            });
        }

        function closeScanResult() {
            document.getElementById('scanOverlay').style.display = 'none';
            currentTicketId = null;
        }

        function switchCamera() {
            if (html5QrcodeScanner) {
                html5QrcodeScanner.clear();
            }
            currentCamera = currentCamera === 'environment' ? 'user' : 'environment';
            initializeScanner();
        }

        function initializeScanner() {
            const config = {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.0,
                facingMode: currentCamera
            };

            html5QrcodeScanner = new Html5Qrcode("qr-reader");
            html5QrcodeScanner.start(
                { facingMode: config.facingMode },
                config,
                onScanSuccess
            ).catch(err => {
                console.error('Error starting scanner:', err);
                showAlert('Error starting camera. Please check camera permissions.', 'danger');
            });
        }

        // Initialize scanner when page loads
        document.addEventListener('DOMContentLoaded', initializeScanner);
    </script>
</body>
</html>
