<?php
// user_api.php - Handles API requests for User functionalities (tasks)

session_start(); // Start the session
require_once 'db_connect.php'; // Include the database connection file

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Access denied. Please log in.']);
    exit();
}

// Set content type to JSON for GET and PUT requests
// For POST (file upload), content type will be multipart/form-data
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
}

$method = $_SERVER['REQUEST_METHOD']; // Get the HTTP method
$current_user_id = $_SESSION['user_id']; // Get the logged-in user's ID

switch ($method) {
    case 'GET':
        handleGetRequest($current_user_id);
        break;
    case 'POST':
        handlePostRequest($current_user_id); // New handler for file uploads
        break;
    case 'PUT':
        handlePutRequest($current_user_id);
        break;
    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
        break;
}

function handleGetRequest($user_id) {
    global $conn;
    if (isset($_GET['resource']) && $_GET['resource'] === 'tasks') {
        // Fetch tasks assigned to the current user, including form_path and completed_assignment_path
        $stmt = $conn->prepare("SELECT id, title, description, deadline, status, form_path, completed_assignment_path FROM tasks WHERE assigned_to_user_id = ? ORDER BY deadline ASC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $tasks = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $tasks[] = $row;
            }
        }
        echo json_encode(['success' => true, 'data' => $tasks]);
        $stmt->close();
    } else {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'Invalid resource specified.']);
    }
}

function handlePostRequest($user_id) {
    global $conn;

    if (isset($_GET['resource']) && $_GET['resource'] === 'tasks' && isset($_GET['action']) && $_GET['action'] === 'upload_completed_assignment') {
        $task_id = $_POST['task_id'] ?? '';

        if (empty($task_id)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Task ID is required for assignment upload.']);
            return;
        }

        // Verify the task belongs to the current user
        $stmt_check = $conn->prepare("SELECT id, completed_assignment_path FROM tasks WHERE id = ? AND assigned_to_user_id = ?");
        $stmt_check->bind_param("ii", $task_id, $user_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        if ($result_check->num_rows === 0) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'You do not have permission to upload for this task.']);
            $stmt_check->close();
            return;
        }
        $task_data = $result_check->fetch_assoc();
        $existing_completed_assignment_path = $task_data['completed_assignment_path'];
        $stmt_check->close();

        $completed_assignment_path = null;

        // Handle file upload
        if (isset($_FILES['completed_assignment_file']) && $_FILES['completed_assignment_file']['error'] === UPLOAD_ERR_OK) {
            $file_tmp_name = $_FILES['completed_assignment_file']['tmp_name'];
            $file_name = basename($_FILES['completed_assignment_file']['name']);
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_ext = ['pdf', 'doc', 'docx', 'txt', 'zip', 'rar', 'jpg', 'jpeg', 'png']; // Expanded allowed types
            $max_file_size = 10 * 1024 * 1024; // 10MB

            if (!in_array($file_ext, $allowed_ext)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid file type. Only common document/image/archive formats are allowed.']);
                return;
            }
            if ($_FILES['completed_assignment_file']['size'] > $max_file_size) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'File size exceeds 10MB limit.']);
                return;
            }

            $upload_dir = 'uploads/completed_assignments/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true); // Create directory if it doesn't exist
            }

            $unique_file_name = uniqid('assignment_') . '_' . $file_name;
            $destination_path = $upload_dir . $unique_file_name;

            if (move_uploaded_file($file_tmp_name, $destination_path)) {
                // If a new file is uploaded, delete the old one if it exists
                if ($existing_completed_assignment_path && file_exists($existing_completed_assignment_path)) {
                    unlink($existing_completed_assignment_path);
                }
                $completed_assignment_path = $destination_path; // Store the new relative path
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to upload file.']);
                return;
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No file uploaded or an upload error occurred.']);
            return;
        }

        // Update the task with the completed assignment path
        $stmt = $conn->prepare("UPDATE tasks SET completed_assignment_path = ? WHERE id = ? AND assigned_to_user_id = ?");
        $stmt->bind_param("sii", $completed_assignment_path, $task_id, $user_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Completed assignment uploaded successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update task with assignment path: ' . $stmt->error]);
        }
        $stmt->close();

    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid resource or action specified for POST.']);
    }
}

function handlePutRequest($user_id) {
    global $conn;
    $data = json_decode(file_get_contents('php://input'), true); // Get JSON PUT body

    if (isset($_GET['resource']) && $_GET['resource'] === 'tasks') {
        // Update task status
        $task_id = $data['id'] ?? '';
        $status = $data['status'] ?? '';

        if (empty($task_id) || empty($status)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Task ID and status are required for update.']);
            return;
        }

        // Ensure the task belongs to the current user before updating
        $stmt = $conn->prepare("UPDATE tasks SET status = ? WHERE id = ? AND assigned_to_user_id = ?");
        $stmt->bind_param("sii", $status, $task_id, $user_id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Task status updated successfully.']);
            } else {
                http_response_code(403); // Forbidden or Not Found
                echo json_encode(['success' => false, 'message' => 'Task not found or you do not have permission to update this task.']);
            }
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update task status: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid resource specified for PUT.']);
    }
}

$conn->close(); // Close the database connection at the end of the script