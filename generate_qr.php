<?php
session_start();
require_once 'config/database.php';
require_once 'phpqrcode/qrlib.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if ticket_id is provided
if (!isset($_GET['ticket_id'])) {
    header("Location: history.php");
    exit();
}

$ticket_id = $_GET['ticket_id'];
$user_id = $_SESSION['user_id'];

try {
    // Fetch ticket details with schedule information
    $sql = "SELECT t.qr_code 
            FROM tickets t
            WHERE t.ticket_id = ? AND t.user_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $ticket_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Invalid ticket");
    }

    $ticket = $result->fetch_assoc();
    $qrContent = $ticket['qr_code'];

    // Set header to image/png
    header('Content-Type: image/png');

    // Generate QR Code with error handling
    if (!QRcode::png($qrContent, false, QR_ECLEVEL_L, 6, 2)) {
        throw new Exception("Failed to generate QR code");
    }

} catch (Exception $e) {
    // If there's an error, output a simple error image
    $im = imagecreate(200, 50);
    $bgColor = imagecolorallocate($im, 255, 255, 255);
    $textColor = imagecolorallocate($im, 255, 0, 0);
    imagestring($im, 5, 10, 20, "QR Error: " . $e->getMessage(), $textColor);
    header('Content-Type: image/png');
    imagepng($im);
    imagedestroy($im);
}
?>
