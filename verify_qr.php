<?php
require_once 'config/database.php';
require_once 'includes/TokenManager.php';

header('Content-Type: application/json');

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['token']) || !isset($input['ticket_id'])) {
        throw new Exception('Missing required parameters');
    }

    $token = $input['token'];
    $ticket_id = $input['ticket_id'];

    // Initialize TokenManager
    $tokenManager = new TokenManager($conn);

    // Verify token
    $isValid = $tokenManager->verifyToken($token, $ticket_id);

    if ($isValid) {
        echo json_encode([
            'status' => 'success',
            'message' => 'QR code verified successfully'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid or expired QR code'
        ]);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

// Clean up expired tokens
try {
    $tokenManager->cleanupExpiredTokens();
} catch (Exception $e) {
    // Log cleanup error but don't affect response
    error_log("Token cleanup failed: " . $e->getMessage());
}
?>
