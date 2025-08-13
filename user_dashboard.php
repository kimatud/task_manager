<?php
// user_dashboard.php - User Dashboard
session_start(); // Start the session

// Check if user is logged in and is a regular user
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: index.php'); // Redirect to login if not authenticated or not a regular user
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Task Manager</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal pt-6"> <!-- Removed px-4 from body -->
    <header class="flex justify-between items-center bg-white p-6 rounded-lg shadow-md mb-6">
        <h1 class="text-4xl font-extrabold text-gray-900">My Tasks</h1>
        <div class="flex items-center space-x-4">
            <span class="text-lg font-medium text-gray-700">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
            <a href="auth.php?action=logout" class="py-2 px-4 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-md shadow-sm transition duration-150 ease-in-out">Logout</a>
        </div>
    </header>

    <main class="container mx-auto p-6 bg-white rounded-lg shadow-md"> <!-- Changed to container mx-auto p-6 -->
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Tasks Assigned To Me</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No.</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deadline</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assignment</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completed Assignment</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="userTaskTableBody" class="bg-white divide-y divide-gray-200">
                    <!-- User's tasks will be loaded here by JavaScript -->
                </tbody>
            </table>
        </div>
        <div id="userTaskMessage" class="text-center mt-4 hidden"></div> <!-- Message div for user tasks -->
    </main>

    <!-- Custom Modal for Confirmation -->
    <div id="customModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-sm">
            <h3 class="text-lg font-bold mb-4" id="modalTitle">Confirm Action</h3>
            <p class="text-gray-700 mb-6" id="modalMessage">Are you sure you want to proceed?</p>
            <div class="flex justify-end space-x-4">
                <button id="modalCancelBtn" class="py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancel</button>
                <button id="modalConfirmBtn" class="py-2 px-4 rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700">Confirm</button>
            </div>
        </div>
    </div>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Initialize fetch data on page load
        document.addEventListener('DOMContentLoaded', () => {
            fetchUserTasks();
        });
    </script>
</body>
</html>