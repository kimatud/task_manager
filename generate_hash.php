<?php
// generate_hash.php - Use this script to generate a password hash

$password = "password"; // The plain-text password you want to hash
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

echo "Plain password: " . $password . "\n";
echo "Hashed password: " . $hashed_password . "\n";