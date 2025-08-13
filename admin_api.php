<?php
// admin_api.php - Handles API requests for Admin functionalities (users and tasks)

session_start(); // Start the session
require_once 'db_connect.php'; // Include the database connection file

// Set error reporting to log errors, but not display them to avoid corrupting JSON output
error_reporting(E_ALL);
ini_set('display_errors', 0); // Crucial: Do NOT display errors in production/API responses
ini_set('log_errors', 1); // Log errors to the PHP error log
ini_set('error_log', __DIR__ . '/php_error.log'); // Specify a custom error log file

// Start output buffering to catch any unexpected output before JSON
ob_start();

// Log file for debugging (adjust path as needed)
$logFile = 'admin_api_log.txt';

// Function to log messages
function logApiMessage($message) {
    global $logFile;
    file_put_contents($logFile, date('[Y-m-d H:i:s]') . ' ' . $message . PHP_EOL, FILE_APPEND);
}

logApiMessage('API Request Received: ' . $_SERVER['REQUEST_METHOD'] . ' for resource ' . ($_GET['resource'] ?? 'N/A'));


// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403); // Forbidden
    ob_clean(); // Clear any buffered output
    header_remove(); // Remove all previously set headers
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Access denied. Administrator privileges required.']);
    ob_flush(); // Send output immediately
    logApiMessage('Access Denied: User not authenticated or not admin.');
    exit(); // Terminate script
}

// Remove all previously set headers to ensure a clean JSON response
header_remove();
// Set content type to JSON for all responses from this script
header('Content-Type: application/json');


$method = $_SERVER['REQUEST_METHOD']; // Get the HTTP method

switch ($method) {
    case 'GET':
        handleGetRequest();
        break;
    case 'POST':
        handlePostRequest();
        break;
    case 'PUT':
        handlePutRequest();
        break;
    case 'DELETE':
        handleDeleteRequest();
        break;
    default:
        http_response_code(405); // Method Not Allowed
        ob_clean(); // Clear any buffered output
        echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
        ob_flush(); // Send output immediately
        logApiMessage('Method Not Allowed: ' . $method);
        exit(); // Terminate script
        break;
}

// No need for ob_end_clean() here, as each handler now exits after sending JSON.

function handleGetRequest() {
    global $conn;
    $searchTerm = $_GET['searchTerm'] ?? ''; // Get search term from query parameter
    $searchParam = '%' . $searchTerm . '%';

    // Get pagination parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5; // Default limit
    $offset = ($page - 1) * $limit;

    if (isset($_GET['resource']) && $_GET['resource'] === 'users') {
        // Count total records for pagination
        $countSql = "SELECT COUNT(*) AS total_records FROM users";
        if (!empty($searchTerm)) {
            $countSql .= " WHERE username LIKE ? OR email LIKE ?";
        }
        $countStmt = $conn->prepare($countSql);
        if (!empty($searchTerm)) {
            $countStmt->bind_param("ss", $searchParam, $searchParam);
        }
        $countStmt->execute();
        $totalRecords = $countStmt->get_result()->fetch_assoc()['total_records'];
        $countStmt->close();

        // Fetch paginated users
        $sql = "SELECT id, username, email, role FROM users";
        if (!empty($searchTerm)) {
            $sql .= " WHERE username LIKE ? OR email LIKE ?";
        }
        $sql .= " LIMIT ? OFFSET ?"; // Add LIMIT and OFFSET
        $stmt = $conn->prepare($sql);
        if (!empty($searchTerm)) {
            $stmt->bind_param("ssii", $searchParam, $searchParam, $limit, $offset);
        } else {
            $stmt->bind_param("ii", $limit, $offset);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $users = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
        ob_clean(); // Clear any buffered output
        echo json_encode(['success' => true, 'data' => $users, 'totalRecords' => $totalRecords]);
        ob_flush(); // Send output immediately
        logApiMessage('Fetched ' . count($users) . ' users (search: ' . $searchTerm . ', page: ' . $page . ')');
        $stmt->close();
        $conn->close(); // Close connection after use
        exit(); // Terminate script
    } elseif (isset($_GET['resource']) && $_GET['resource'] === 'tasks') {
        // Count total records for pagination
        $countSql = "SELECT COUNT(*) AS total_records FROM tasks t JOIN users u ON t.assigned_to_user_id = u.id";
        if (!empty($searchTerm)) {
            $countSql .= " WHERE t.title LIKE ? OR u.username LIKE ?";
        }
        $countStmt = $conn->prepare($countSql);
        if (!empty($searchTerm)) {
            $countStmt->bind_param("ss", $searchParam, $searchParam);
        }
        $countStmt->execute();
        $totalRecords = $countStmt->get_result()->fetch_assoc()['total_records'];
        $countStmt->close();

        // Fetch paginated tasks
        $sql = "SELECT t.id, t.title, t.description, t.assigned_to_user_id, u.username AS assigned_to_username, t.deadline, t.status, t.form_path, t.completed_assignment_path
                FROM tasks t
                JOIN users u ON t.assigned_to_user_id = u.id";
        if (!empty($searchTerm)) {
            $sql .= " WHERE t.title LIKE ? OR u.username LIKE ?";
        }
        $sql .= " LIMIT ? OFFSET ?"; // Add LIMIT and OFFSET
        $stmt = $conn->prepare($sql);
        if (!empty($searchTerm)) {
            $stmt->bind_param("ssii", $searchParam, $searchParam, $limit, $offset);
        } else {
            $stmt->bind_param("ii", $limit, $offset);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $tasks = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $tasks[] = $row;
            }
        }
        ob_clean(); // Clear any buffered output
        echo json_encode(['success' => true, 'data' => $tasks, 'totalRecords' => $totalRecords]);
        ob_flush(); // Send output immediately
        logApiMessage('Fetched ' . count($tasks) . ' tasks (search: ' . $searchTerm . ', page: ' . $page . ')');
        $stmt->close();
        $conn->close(); // Close connection after use
        exit(); // Terminate script
    } else {
        http_response_code(400); // Bad Request
        ob_clean(); // Clear any buffered output
        echo json_encode(['success' => false, 'message' => 'Invalid resource specified.']);
        ob_flush(); // Send output immediately
        logApiMessage('GET Request: Invalid resource.');
        exit(); // Terminate script
    }
}

function handlePostRequest() {
    global $conn;

    if (isset($_GET['resource']) && $_GET['resource'] === 'tasks') {
        // Log raw POST data and FILES for debugging
        logApiMessage("Raw POST data: " . print_r($_POST, true));
        logApiMessage("Raw FILES data: " . print_r($_FILES, true));

        // Handle task creation (POST) or file update for existing task (POST with ID)
        $task_id = $_POST['id'] ?? ''; // Check if ID is provided for update scenario
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $deadline = $_POST['deadline'] ?? '';
        $status = $_POST['status'] ?? 'Pending';
        $form_path = null; // Initialize form_path to null

        // For multi-assignment, assigned_to_user_ids will be a JSON string
        $assigned_to_user_ids_json = $_POST['assigned_to_user_ids'] ?? '[]';
        $assigned_to_user_ids = json_decode($assigned_to_user_ids_json, true);

        // Check for JSON decoding errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Invalid JSON for assigned_to_user_ids: ' . json_last_error_msg()]);
            ob_flush();
            logApiMessage('JSON Decode Error for assigned_to_user_ids: ' . json_last_error_msg() . ' Raw JSON: ' . $assigned_to_user_ids_json);
            exit();
        }


        logApiMessage("Task POST request. Task ID: " . ($task_id ?: 'NEW') . ", Assigned User IDs: " . implode(',', $assigned_to_user_ids));

        // If it's an update, fetch the existing form_path BEFORE handling new upload
        if (!empty($task_id)) {
            $stmt_get_path = $conn->prepare("SELECT form_path FROM tasks WHERE id = ?");
            $stmt_get_path->bind_param("i", $task_id);
            $stmt_get_path->execute();
            $result_get_path = $stmt_get_path->get_result();
            if ($result_get_path->num_rows > 0) {
                $existing_path = $result_get_path->fetch_assoc()['form_path'];
                $form_path = $existing_path; // Store existing path to potentially delete later
                logApiMessage("Existing form_path for task $task_id: " . ($existing_path ?? 'N/A'));
            }
            $stmt_get_path->close();
        }


        // Handle file upload for admin form
        if (isset($_FILES['form_file']) && $_FILES['form_file']['error'] === UPLOAD_ERR_OK) {
            $file_tmp_name = $_FILES['form_file']['tmp_name'];
            $file_name = basename($_FILES['form_file']['name']);
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_ext = ['pdf', 'doc', 'docx', 'txt'];
            $max_file_size = 5 * 1024 * 1024; // 5MB

            logApiMessage("File upload detected. Name: $file_name, Size: " . $_FILES['form_file']['size']);

            if (!in_array($file_ext, $allowed_ext)) {
                http_response_code(400);
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Invalid file type. Only PDF, DOC, DOCX, TXT are allowed.']);
                ob_flush();
                logApiMessage('File upload failed: Invalid file type ' . $file_ext);
                exit();
            }
            if ($_FILES['form_file']['size'] > $max_file_size) {
                http_response_code(400);
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'File size exceeds 5MB limit.']);
                ob_flush();
                logApiMessage('File upload failed: Size exceeded ' . $_FILES['form_file']['size']);
                exit();
            }

            $upload_dir = 'uploads/forms/';
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) { // Attempt to create directory
                    http_response_code(500);
                    ob_clean();
                    echo json_encode(['success' => false, 'message' => 'Failed to create upload directory. Check permissions.']);
                    ob_flush();
                    logApiMessage('Failed to create upload directory: ' . $upload_dir);
                    exit();
                }
                logApiMessage('Created upload directory: ' . $upload_dir);
            }

            $unique_file_name = uniqid('admin_form_') . '_' . $file_name;
            $destination_path = $upload_dir . $unique_file_name;

            if (move_uploaded_file($file_tmp_name, $destination_path)) {
                logApiMessage("File moved successfully to: $destination_path");
                // If a new file is uploaded, delete the old one if it exists
                if (!empty($existing_path) && file_exists($existing_path)) {
                    if (unlink($existing_path)) {
                        logApiMessage("Old file deleted: $existing_path");
                    } else {
                        logApiMessage("Failed to delete old file: $existing_path");
                    }
                }
                $form_path = $destination_path; // Store the new relative path
            } else {
                http_response_code(500);
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Failed to upload file. Check directory permissions.']);
                ob_flush();
                logApiMessage('Failed to move uploaded file from ' . $file_tmp_name . ' to ' . $destination_path . '. Error: ' . error_get_last()['message'] ?? 'Unknown');
                exit();
            }
        } else if (isset($_FILES['form_file']) && $_FILES['form_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Handle other file upload errors (e.g., UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE)
            $phpFileUploadErrors = array(
                UPLOAD_ERR_OK => 'There is no error, the file uploaded with success.',
                UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
                UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
                UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
            );
            $errorMessage = $phpFileUploadErrors[$_FILES['form_file']['error']] ?? 'Unknown upload error.';
            http_response_code(400);
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'File upload error: ' . $errorMessage]);
            ob_flush();
            logApiMessage('File upload error (not UPLOAD_ERR_NO_FILE): ' . $errorMessage);
            exit();
        }
        // If no new file uploaded, $form_path retains the existing_path (if it was an update) or remains null (if new task)

        if (empty($task_id)) {
            // Add new task(s) for multiple users
            if (empty($title) || empty($deadline) || empty($assigned_to_user_ids)) {
                http_response_code(400);
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Title, assigned user(s), and deadline are required.']);
                ob_flush();
                logApiMessage('Add Task Failed: Missing required fields for new task.');
                exit();
            }

            $success_count = 0;
            $failed_users = [];

            foreach ($assigned_to_user_ids as $user_id) {
                $stmt = $conn->prepare("INSERT INTO tasks (title, description, assigned_to_user_id, deadline, status, form_path) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssisss", $title, $description, $user_id, $deadline, $status, $form_path);

                if ($stmt->execute()) {
                    $success_count++;
                    logApiMessage("Task assigned to user ID $user_id successfully.");

                    // --- Email Notification Placeholder ---
                    $user_email_stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
                    $user_email_stmt->bind_param("i", $user_id);
                    $user_email_stmt->execute();
                    $user_email_result = $user_email_stmt->get_result();
                    if ($user_email_result->num_rows > 0) {
                        $user_email = $user_email_result->fetch_assoc()['email'];
                        $log_message = "EMAIL TO: " . $user_email . "\n";
                        $log_message .= "SUBJECT: New Task Assigned: " . $title . "\n";
                        $log_message .= "BODY: You have been assigned a new task: " . $title . "\nDeadline: " . $deadline . "\n\n";
                        file_put_contents('email_log.txt', $log_message, FILE_APPEND);
                        logApiMessage("Email logged for user ID: $user_id");
                    }
                    $user_email_stmt->close();
                    // ------------------------------------

                } else {
                    $failed_users[] = $user_id;
                    logApiMessage("Failed to assign task to user ID $user_id: " . $stmt->error);
                }
            }
            if (isset($stmt)) $stmt->close(); // Close statement after loop

            $conn->close(); // Close connection after use

            if ($success_count > 0) {
                $message = "Successfully assigned task to $success_count user(s).";
                if (count($failed_users) > 0) {
                    $message .= " Failed for: " . implode(', ', $failed_users) . ".";
                }
                ob_clean();
                echo json_encode(['success' => true, 'message' => $message]);
                ob_flush();
                logApiMessage('API Request Finished.'); // Added this log here
                exit(); // Terminate script
            } else {
                http_response_code(500);
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Failed to assign task to any user.']);
                ob_flush();
                logApiMessage('API Request Finished.'); // Added this log here
                exit(); // Terminate script
            }

        } else {
            // Update existing task (single task_id provided)
            if (empty($title) || empty($deadline) || empty($status)) {
                http_response_code(400);
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Title, deadline, and status are required for update.']);
                ob_flush();
                logApiMessage('Update Task Failed: Missing required fields for existing task.');
                exit(); // Terminate script
            }

            $original_assigned_to_user_id = null;
            $stmt_get_original_user = $conn->prepare("SELECT assigned_to_user_id FROM tasks WHERE id = ?");
            $stmt_get_original_user->bind_param("i", $task_id);
            $stmt_get_original_user->execute();
            $result_original_user = $stmt_get_original_user->get_result();
            if ($result_original_user->num_rows > 0) {
                $original_assigned_to_user_id = $result_original_user->fetch_assoc()['assigned_to_user_id'];
            }
            $stmt_get_original_user->close();

            $stmt = $conn->prepare("UPDATE tasks SET title = ?, description = ?, assigned_to_user_id = ?, deadline = ?, status = ?, form_path = ? WHERE id = ?");
            $stmt->bind_param("ssisssi", $title, $description, $original_assigned_to_user_id, $deadline, $status, $form_path, $task_id);
            $message = 'Task updated successfully.';
            logApiMessage("Preparing UPDATE statement for task $task_id. Form Path: " . ($form_path ?? 'NULL'));


            if ($stmt->execute()) {
                ob_clean();
                echo json_encode(['success' => true, 'message' => $message]);
                ob_flush();
                logApiMessage("Task operation successful. Message: $message");

                if ($original_assigned_to_user_id) {
                    $user_email_stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
                    $user_email_stmt->bind_param("i", $original_assigned_to_user_id);
                    $user_email_stmt->execute();
                    $user_email_result = $user_email_stmt->get_result();
                    if ($user_email_result->num_rows > 0) {
                        $user_email = $user_email_result->fetch_assoc()['email'];
                        $log_message = "EMAIL TO: " . $user_email . "\n";
                        $log_message .= "SUBJECT: Task Updated: " . $title . "\n";
                        $log_message .= "BODY: Your assigned task has been updated: " . $title . "\nDeadline: " . $deadline . "\nStatus: " . $status . "\n\n";
                        file_put_contents('email_log.txt', $log_message, FILE_APPEND);
                        logApiMessage("Email logged for user ID: $original_assigned_to_user_id (Task Update)");
                    }
                    $user_email_stmt->close();
                }
                $stmt->close();
                $conn->close(); // Close connection after use
                logApiMessage('API Request Finished.'); // Added this log here
                exit(); // Terminate script
            } else {
                http_response_code(500);
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Failed to process task: ' . $stmt->error]);
                ob_flush();
                logApiMessage('Task operation failed: ' . $stmt->error);
                $stmt->close();
                $conn->close(); // Close connection after use
                logApiMessage('API Request Finished.'); // Added this log here
                exit(); // Terminate script
            }
        }
    } elseif (isset($_GET['resource']) && $_GET['resource'] === 'users') {
        // Add new user (no file upload for users)
        $data = json_decode(file_get_contents('php://input'), true);
        $username = $data['username'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $role = $data['role'] ?? 'user';

        if (empty($username) || empty($email) || empty($password)) {
            http_response_code(400);
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Username, email, and password are required.']);
            ob_flush();
            logApiMessage('Add User Failed: Missing required fields.');
            exit(); // Terminate script
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT); // Hash the password

        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);

        if ($stmt->execute()) {
            ob_clean();
            echo json_encode(['success' => true, 'message' => 'User added successfully.', 'id' => $stmt->insert_id]);
            ob_flush();
            logApiMessage('User added successfully. ID: ' . $stmt->insert_id);
            $stmt->close();
            $conn->close(); // Close connection after use
            logApiMessage('API Request Finished.'); // Added this log here
            exit(); // Terminate script
        } else {
            http_response_code(500);
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Failed to add user: ' . $stmt->error]);
            ob_flush();
            logApiMessage('Failed to add user: ' . $stmt->error);
            $stmt->close();
            $conn->close(); // Close connection after use
            logApiMessage('API Request Finished.'); // Added this log here
            exit(); // Terminate script
        }
    } else {
        http_response_code(400);
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid resource specified for POST.']);
        ob_flush();
        logApiMessage('POST Request: Invalid resource.');
        exit(); // Terminate script
    }
}

function handlePutRequest() {
    global $conn;
    $data = json_decode(file_get_contents('php://input'), true); // Get JSON PUT body

    if (isset($_GET['resource']) && $_GET['resource'] === 'users') {
        // Edit user
        $id = $data['id'] ?? '';
        $username = $data['username'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? ''; // Optional: only update if provided
        $role = $data['role'] ?? 'user';

        logApiMessage("User PUT request. User ID: $id");

        if (empty($id) || empty($username) || empty($email)) {
            http_response_code(400);
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'User ID, username, and email are required for update.']);
            ob_flush();
            logApiMessage('Update User Failed: Missing required fields.');
            exit(); // Terminate script
        }

        // Prevent admin from updating themselves
        if ($id == $_SESSION['user_id'] && $_SESSION['role'] === 'admin') {
            http_response_code(403);
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Cannot update your own admin account role.']);
            ob_flush();
            logApiMessage('Update User Failed: Attempted to update own admin account role.');
            exit(); // Terminate script
        }

        $sql = "UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?";
        $params = "sssi";
        $values = [$username, $email, $role, $id];

        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET username = ?, email = ?, password = ?, role = ? WHERE id = ?";
            $params = "ssssi";
            $values = [$username, $email, $hashed_password, $role, $id];
            logApiMessage('Updating user password for ID: ' . $id);
        }

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($params, ...$values);

        if ($stmt->execute()) {
            ob_clean();
            echo json_encode(['success' => true, 'message' => 'User updated successfully.']);
            ob_flush();
            logApiMessage('User updated successfully. ID: ' . $id);
            $stmt->close();
            $conn->close(); // Close connection after use
            logApiMessage('API Request Finished.'); // Added this log here
            exit(); // Terminate script
        } else {
            http_response_code(500);
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Failed to update user: ' . $stmt->error]);
            ob_flush();
            logApiMessage('Failed to update user: ' . $stmt->error);
            $stmt->close();
            $conn->close(); // Close connection after use
            logApiMessage('API Request Finished.'); // Added this log here
            exit(); // Terminate script
        }
    } elseif (isset($_GET['resource']) && $_GET['resource'] === 'tasks') {
        // Edit task (text fields only, file upload handled separately or on POST)
        $id = $data['id'] ?? '';
        $title = $data['title'] ?? '';
        $description = $data['description'] ?? '';
        $assigned_to_user_id = $data['assigned_to_user_id'] ?? ''; // This will be the single original user ID
        $deadline = $data['deadline'] ?? '';
        $status = $data['status'] ?? 'Pending';
        // form_path is not updated via PUT for simplicity in this example.
        // A new file upload would be a POST request to update the file, or a separate endpoint.

        logApiMessage("Task PUT request. Task ID: $id");

        if (empty($id) || empty($title) || empty($assigned_to_user_id) || empty($deadline) || empty($status)) {
            http_response_code(400);
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Task ID, title, assigned user, deadline, and status are required for update.']);
            ob_flush();
            logApiMessage('Update Task Failed: Missing required fields.');
            exit(); // Terminate script
        }

        $stmt = $conn->prepare("UPDATE tasks SET title = ?, description = ?, assigned_to_user_id = ?, deadline = ?, status = ? WHERE id = ?");
        $stmt->bind_param("ssissi", $title, $description, $assigned_to_user_id, $deadline, $status, $id);

        if ($stmt->execute()) {
            ob_clean();
            echo json_encode(['success' => true, 'message' => 'Task updated successfully.']);
            ob_flush();
            logApiMessage('Task updated successfully. ID: ' . $id);
            $stmt->close();
            $conn->close(); // Close connection after use
            logApiMessage('API Request Finished.'); // Added this log here
            exit(); // Terminate script
        } else {
            http_response_code(500);
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Failed to update task: ' . $stmt->error]);
            ob_flush();
            logApiMessage('Failed to update task: ' . $stmt->error);
            $stmt->close();
            $conn->close(); // Close connection after use
            logApiMessage('API Request Finished.'); // Added this log here
            exit(); // Terminate script
        }
    } else {
        http_response_code(400);
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid resource specified for PUT.']);
        ob_flush();
        logApiMessage('PUT Request: Invalid resource.');
        exit(); // Terminate script
    }
}

function handleDeleteRequest() {
    global $conn;
    $data = json_decode(file_get_contents('php://input'), true); // Get JSON DELETE body

    if (isset($_GET['resource']) && $_GET['resource'] === 'users') {
        // Delete user
        $id = $data['id'] ?? '';

        logApiMessage("User DELETE request. User ID: $id");

        if (empty($id)) {
            http_response_code(400);
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'User ID is required for deletion.']);
            ob_flush();
            logApiMessage('Delete User Failed: Missing ID.');
            exit(); // Terminate script
        }

        // Prevent admin from deleting themselves
        if ($id == $_SESSION['user_id'] && $_SESSION['role'] === 'admin') {
            http_response_code(403);
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Cannot delete your own admin account.']);
            ob_flush();
            logApiMessage('Delete User Failed: Attempted to delete own admin account.');
            exit(); // Terminate script
        }

        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                ob_clean();
                echo json_encode(['success' => true, 'message' => 'User deleted successfully.']);
                ob_flush();
                logApiMessage('User deleted successfully. ID: ' . $id);
            } else {
                http_response_code(404); // Not Found
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'User not found.']);
                ob_flush();
                logApiMessage('Delete User Failed: User ID not found: ' . $id);
            }
            $stmt->close();
            $conn->close(); // Close connection after use
            logApiMessage('API Request Finished.'); // Added this log here
            exit(); // Terminate script
        } else {
            http_response_code(500);
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Failed to delete user: ' . $stmt->error]);
            ob_flush();
            logApiMessage('Failed to delete user: ' . $stmt->error);
            $stmt->close();
            $conn->close(); // Close connection after use
            logApiMessage('API Request Finished.'); // Added this log here
            exit(); // Terminate script
        }
    } elseif (isset($_GET['resource']) && $_GET['resource'] === 'tasks') {
        // Delete task
        $id = $data['id'] ?? '';

        logApiMessage("Task DELETE request. Task ID: $id");

        if (empty($id)) {
            http_response_code(400);
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Task ID is required for deletion.']);
            ob_flush();
            logApiMessage('Delete Task Failed: Missing ID.');
            exit(); // Terminate script
        }

        // Before deleting the task record, delete the associated admin form file if it exists
        $stmt_select_admin_form = $conn->prepare("SELECT form_path FROM tasks WHERE id = ?");
        $stmt_select_admin_form->bind_param("i", $id);
        $stmt_select_admin_form->execute();
        $result_select_admin_form = $stmt_select_admin_form->get_result();
        if ($result_select_admin_form->num_rows > 0) {
            $task_data = $result_select_admin_form->fetch_assoc();
            $file_to_delete = $task_data['form_path'];
            if ($file_to_delete && file_exists($file_to_delete)) {
                if (unlink($file_to_delete)) {
                    logApiMessage("Old admin form file deleted: $file_to_delete");
                } else {
                    logApiMessage("Failed to delete old admin form file: $file_to_delete");
                }
            }
        }
        $stmt_select_admin_form->close();

        // Also delete the associated completed assignment file if it exists
        $stmt_select_completed_assignment = $conn->prepare("SELECT completed_assignment_path FROM tasks WHERE id = ?");
        $stmt_select_completed_assignment->bind_param("i", $id);
        $stmt_select_completed_assignment->execute();
        $result_select_completed_assignment = $stmt_select_completed_assignment->get_result();
        if ($result_select_completed_assignment->num_rows > 0) {
            $task_data = $result_select_completed_assignment->fetch_assoc();
            $file_to_delete = $task_data['completed_assignment_path'];
            if ($file_to_delete && file_exists($file_to_delete)) {
                if (unlink($file_to_delete)) {
                    logApiMessage("Old completed assignment file deleted: $file_to_delete");
                } else {
                    logApiMessage("Failed to delete old completed assignment file: $file_to_delete");
                }
            }
        }
        $stmt_select_completed_assignment->close();


        $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                ob_clean();
                echo json_encode(['success' => true, 'message' => 'Task deleted successfully.']);
                ob_flush();
                logApiMessage('Task deleted successfully. ID: ' . $id);
            } else {
                http_response_code(404); // Not Found
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Task not found.']);
                ob_flush();
                logApiMessage('Delete Task Failed: Task ID not found: ' . $id);
            }
            $stmt->close();
            $conn->close(); // Close connection after use
            logApiMessage('API Request Finished.'); // Added this log here
            exit(); // Terminate script
        } else {
            http_response_code(500);
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Failed to delete task: ' . $stmt->error]);
            ob_flush();
            logApiMessage('Failed to delete task: ' . $stmt->error);
            $stmt->close();
            $conn->close(); // Close connection after use
            logApiMessage('API Request Finished.'); // Added this log here
            exit(); // Terminate script
        }
    } else {
        http_response_code(400);
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid resource specified for DELETE.']);
        ob_flush();
        logApiMessage('DELETE Request: Invalid resource.');
        exit(); // Terminate script
    }
}
