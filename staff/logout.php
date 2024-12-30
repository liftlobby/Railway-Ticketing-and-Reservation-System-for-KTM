<?php
session_start();

// Log the logout activity if staff_id is set
if (isset($_SESSION['staff_id'])) {
    require_once '../config/database.php';
    
    $stmt = $conn->prepare("INSERT INTO activity_logs (staff_id, action, description, ip_address, user_agent) VALUES (?, 'staff_logout', 'Staff logged out', ?, ?)");
    $stmt->bind_param("iss", $_SESSION['staff_id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
    $stmt->execute();
}

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
?>
