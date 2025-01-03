<?php
class TokenManager {
    private $conn;
    private const TOKEN_VALIDITY = 86400; // 24 hours in seconds
    private const PEPPER = "KTM_SECURE_TICKET_"; // Add a pepper for additional security

    public function __construct($database_connection) {
        $this->conn = $database_connection;
    }

    public function generateSecureToken($ticket_id, $user_id) {
        // Generate a cryptographically secure random token
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', time() + self::TOKEN_VALIDITY);
        
        // Create a hash of the token with ticket data
        $tokenHash = hash('sha256', self::PEPPER . $token . $ticket_id . $user_id);
        
        // Store token in database
        $sql = "INSERT INTO auth_tokens (token, token_hash, ticket_id, user_id, expiry_time) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssiis", $token, $tokenHash, $ticket_id, $user_id, $expiry);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to store token");
        }
        
        return $token;
    }

    public function verifyToken($token) {
        // First, clean up expired tokens
        $this->cleanupExpiredTokens();

        // Get token data
        $sql = "SELECT t.*, tk.user_id, tk.status as ticket_status
                FROM auth_tokens at
                JOIN tickets tk ON at.ticket_id = tk.ticket_id
                WHERE at.token = ? 
                AND at.expiry_time > NOW() 
                AND at.is_used = 0";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return false;
        }

        $tokenData = $result->fetch_assoc();
        
        // Verify token hash
        $expectedHash = hash('sha256', self::PEPPER . $token . $tokenData['ticket_id'] . $tokenData['user_id']);
        if (!hash_equals($tokenData['token_hash'], $expectedHash)) {
            return false;
        }

        // Check ticket status
        if ($tokenData['ticket_status'] !== 'active') {
            return false;
        }

        // Mark token as used
        $this->markTokenAsUsed($token);

        return [
            'ticket_id' => $tokenData['ticket_id'],
            'user_id' => $tokenData['user_id'],
            'expiry_time' => $tokenData['expiry_time']
        ];
    }

    private function markTokenAsUsed($token) {
        $sql = "UPDATE auth_tokens SET is_used = 1, used_time = NOW() WHERE token = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $token);
        $stmt->execute();
    }

    public function cleanupExpiredTokens() {
        $sql = "DELETE FROM auth_tokens WHERE expiry_time < NOW()";
        $this->conn->query($sql);
    }

    public function revokeToken($token) {
        $sql = "UPDATE auth_tokens SET is_used = 1, used_time = NOW(), revoked = 1 WHERE token = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $token);
        return $stmt->execute();
    }
}
?>