<?php
// check_deadlines.php - Script to automatically update task statuses when deadlines are reached

// Include the database connection file
// Make sure db_connect.php is in the same directory or adjust the path accordingly
require_once 'db_connect.php';

// Log file for script execution (optional, but good for debugging cron jobs)
$logFile = 'deadline_checker_log.txt';

// Function to log messages
function logMessage($message) {
    global $logFile;
    file_put_contents($logFile, date('[Y-m-d H:i:s]') . ' ' . $message . PHP_EOL, FILE_APPEND);
}

logMessage('Starting deadline check script.');

try {
    // Get the current date in 'YYYY-MM-DD' format for comparison
    $currentDate = date('Y-m-d');

    // Prepare the SQL statement to find tasks that are past their deadline
    // and are not yet 'Completed' or already 'Deadline Reached'
    $stmt = $conn->prepare("UPDATE tasks
                            SET status = 'Deadline Reached'
                            WHERE deadline < ?
                            AND status IN ('Pending', 'In Progress')"); // Only update if status is Pending or In Progress

    if ($stmt === false) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    // Bind the current date parameter
    $stmt->bind_param("s", $currentDate);

    // Execute the statement
    if ($stmt->execute()) {
        $affectedRows = $stmt->affected_rows;
        logMessage("Successfully updated $affectedRows tasks to 'Deadline Reached'.");
    } else {
        throw new Exception("Failed to execute statement: " . $stmt->error);
    }

    // Close the statement
    $stmt->close();

} catch (Exception $e) {
    logMessage("Error during deadline check: " . $e->getMessage());
} finally {
    // Close the database connection
    if (isset($conn)) {
        $conn->close();
    }
    logMessage('Finished deadline check script.');
}