<?php
require_once 'includes/PasswordUtility.php';

$password = 'Admin@123';
$hash = PasswordUtility::hashPassword($password);
echo "Password hash for 'Admin@123': " . $hash;
?>
