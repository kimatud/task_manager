<?php
// auth.php - Handles user authentication (login and logout)

session_start(); // Start the session to manage user login state
require_once 'db_connect.php'; // Include the database connection file

// Handle login request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = $_POST['username'] ?? ''; // Get username from POST data
    $password = $_POST['password'] ?? ''; // Get password from POST data

    // Prepare a SQL statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username); // Bind username parameter
    $stmt->execute(); // Execute the statement
    $result = $stmt->get_result(); // Get the result set

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc(); // Fetch user data
        // Verify the provided password against the hashed password in the database
        if (password_verify($password, $user['password'])) {
            // Password is correct, set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Return success response and redirect URL
            echo json_encode(['success' => true, 'redirect' => ($user['role'] === 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php')]);
        } else {
            // Invalid password
            echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
        }
    } else {
        // User not found
        echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
    }
    $stmt->close(); // Close the statement
    $conn->close(); // Close the database connection
    exit(); // Terminate script after sending response
}

// Handle logout request
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();   // Unset all session variables
    session_destroy(); // Destroy the session
    header('Location: index.php'); // Redirect to the login page
    exit();
}

// If no specific action, redirect to login page
// This ensures that if auth.php is accessed directly without a POST login request,
// it redirects to the login page.
if (!isset($_SESSION['user_id'])) {
    // If not logged in, ensure they are on the login page
    if (basename($_SERVER['PHP_SELF']) !== 'index.php') {
        header('Location: index.php');
        exit();
    }
}