<?php
class PasswordPolicy {
    // Password requirements
    const MIN_LENGTH = 8;
    const MAX_LENGTH = 50;
    const REQUIRE_UPPERCASE = true;
    const REQUIRE_LOWERCASE = true;
    const REQUIRE_NUMBERS = true;
    const REQUIRE_SPECIAL = true;
    const MAX_CONSECUTIVE_CHARS = 3;
    const MAX_FAILED_ATTEMPTS = 5;
    const LOCKOUT_DURATION = 15; // minutes
    const PASSWORD_HISTORY = 5; // number of previous passwords to check

    /**
     * Validate password strength
     */
    public static function validatePassword($password) {
        $errors = [];

        // Check length
        if (strlen($password) < self::MIN_LENGTH || strlen($password) > self::MAX_LENGTH) {
            $errors[] = "Password must be between " . self::MIN_LENGTH . " and " . self::MAX_LENGTH . " characters";
        }

        // Check for uppercase letters
        if (self::REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }

        // Check for lowercase letters
        if (self::REQUIRE_LOWERCASE && !preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }

        // Check for numbers
        if (self::REQUIRE_NUMBERS && !preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }

        // Check for special characters
        if (self::REQUIRE_SPECIAL && !preg_match('/[!@#$%^&*()\-_=+{};:,<.>]/', $password)) {
            $errors[] = "Password must contain at least one special character";
        }

        // Check for consecutive characters
        if (preg_match('/(.)\1{' . (self::MAX_CONSECUTIVE_CHARS - 1) . ',}/', $password)) {
            $errors[] = "Password cannot contain more than " . self::MAX_CONSECUTIVE_CHARS . " consecutive identical characters";
        }

        // Check for common patterns
        if (preg_match('/12345|qwerty|password|admin/i', $password)) {
            $errors[] = "Password contains a common pattern that is not allowed";
        }

        return $errors;
    }

    /**
     * Check if account should be locked based on failed attempts
     */
    public static function shouldLockAccount($failedAttempts) {
        return $failedAttempts >= self::MAX_FAILED_ATTEMPTS;
    }

    /**
     * Get lockout end time
     */
    public static function getLockoutTime() {
        return date('Y-m-d H:i:s', strtotime('+' . self::LOCKOUT_DURATION . ' minutes'));
    }

    /**
     * Generate a secure random password
     */
    public static function generateSecurePassword() {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '!@#$%^&*()-_=+';
        
        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];
        
        // Add more random characters
        $all = $uppercase . $lowercase . $numbers . $special;
        for ($i = 0; $i < 8; $i++) {
            $password .= $all[random_int(0, strlen($all) - 1)];
        }
        
        // Shuffle the password
        $password = str_shuffle($password);
        
        return $password;
    }

    /**
     * Hash password using Argon2id
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,  // 64MB
            'time_cost' => 4,        // 4 iterations
            'threads' => 3           // 3 threads
        ]);
    }

    /**
     * Verify password hash
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}
