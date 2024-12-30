<?php
class PasswordUtility {
    // Password requirements
    private const MIN_LENGTH = 8;
    private const REQUIRE_UPPERCASE = true;
    private const REQUIRE_LOWERCASE = true;
    private const REQUIRE_NUMBERS = true;
    private const REQUIRE_SPECIAL = true;
    private const PEPPER = "KTM_SECURE_2024"; // Change this to a random string in production

    public static function hashPassword($password) {
        // Add pepper before hashing
        $pepperedPassword = $password . self::PEPPER;
        // Use Argon2id for password hashing (one of the strongest algorithms)
        return password_hash($pepperedPassword, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,  // 64MB
            'time_cost' => 4,        // 4 iterations
            'threads' => 3           // 3 parallel threads
        ]);
    }

    public static function verifyPassword($password, $hash) {
        $pepperedPassword = $password . self::PEPPER;
        return password_verify($pepperedPassword, $hash);
    }

    public static function validatePasswordStrength($password) {
        $errors = [];

        if (strlen($password) < self::MIN_LENGTH) {
            $errors[] = "Password must be at least " . self::MIN_LENGTH . " characters long";
        }

        if (self::REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }

        if (self::REQUIRE_LOWERCASE && !preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }

        if (self::REQUIRE_NUMBERS && !preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }

        if (self::REQUIRE_SPECIAL && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = "Password must contain at least one special character";
        }

        return $errors;
    }

    public static function generateSalt() {
        return bin2hex(random_bytes(16));
    }
}
?>
