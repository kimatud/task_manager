<?php
// db_connect.php - Handles the database connection
ob_start(); // Start output buffering at the very top

$servername = "localhost";
$username = "root"; // Your MySQL username
$password = "";     // Your MySQL password
$dbname = "task_manager"; // The database name you created

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    ob_end_clean(); // Clear any buffered output before dying
    die("Connection failed: " . $conn->connect_error);
}
ob_end_clean(); // End output buffering here