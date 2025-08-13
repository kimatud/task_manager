<?php
// admin_dashboard.php - Admin Dashboard Page
session_start(); // Start the session

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php'); // Redirect to login if not logged in or not admin
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Task Manager</title>
    <link rel="stylesheet" href="style.css">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="script.js" defer></script>
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal h-screen overflow-hidden flex flex-col">

    <!-- Custom Modal Structure (Hidden by default) -->
    <div id="customModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white p-6 rounded-lg shadow-xl max-w-sm w-full mx-auto">
            <h3 id="modalTitle" class="text-lg font-semibold mb-4 text-gray-900">Modal Title</h3>
            <p id="modalMessage" class="text-sm text-gray-700 mb-6">Modal Message</p>
            <div class="flex justify-end space-x-3">
                <button id="modalCancelBtn" class="py-2 px-4 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-gray-200 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Cancel
                </button>
                <button id="modalConfirmBtn" class="py-2 px-4 rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Confirm
                </button>
            </div>
        </div>
    </div>

    <!-- Top Bar (Fixed) -->
    <nav class="bg-indigo-600 p-4 text-white shadow-md flex-shrink-0">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">Admin Dashboard</h1>
            <div class="flex items-center space-x-4">
                <span class="text-lg">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <a href="auth.php?action=logout" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-md shadow-sm transition duration-150 ease-in-out">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Fixed Section: Tabs -->
    <div class="flex-shrink-0 p-4 pb-0">
        <div class="container mx-auto">
            <!-- Tabs for User Management and Task Management -->
            <div class="mb-6 border-b border-gray-200">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <button id="usersTab" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm text-indigo-600 border-indigo-500 focus:outline-none">
                        Manage Users
                    </button>
                    <button id="tasksTab" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none">
                        Manage Tasks
                    </button>
                </nav>
            </div>
        </div>
    </div>

    <!-- Scrollable Section: All Tab Content (Headers, Forms, Tables, Pagination) -->
    <div class="flex-grow overflow-y-auto p-4 pt-0">
        <div class="container mx-auto">
            <!-- User Management Section (Header, Form, and Table) -->
            <div id="usersSection" class="tab-content">
                <h2 class="text-2xl font-semibold text-gray-800 mb-6">User Management</h2>

                <!-- Add/Edit User Form -->
                <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                    <h3 class="text-xl font-semibold text-gray-700 mb-4">Add/Edit User</h3>
                    <form id="userForm" class="space-y-4">
                        <input type="hidden" id="userId" name="id">
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                            <input type="text" id="username" name="username" required
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" id="email" name="email" required
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">Password (Leave blank to keep current)</label>
                            <input type="password" id="password" name="password"
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                            <select id="role" name="role" required
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div id="userFormMessage" class="text-center text-red-600 font-medium hidden"></div>
                        <div class="flex justify-end space-x-3">
                            <button type="button" id="cancelEditUserBtn" class="hidden py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Cancel
                            </button>
                            <button type="submit" id="saveUserBtn"
                                    class="py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Add User
                            </button>
                        </div>
                    </form>
                </div>

                <!-- User List Table -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h3 class="text-xl font-semibold text-gray-700 mb-4">All Users</h3>
                    <div class="mb-4">
                        <input type="text" id="userSearchInput" placeholder="Search users by username or email..."
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 sticky top-0 z-10">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="userTableBody" class="bg-white divide-y divide-gray-200">
                                <!-- User rows will be loaded here by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                    <!-- User Pagination Controls -->
                    <div id="userPagination" class="flex justify-center items-center space-x-4 mt-4">
                        <button id="userPrevPage" class="bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded-md shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">Previous</button>
                        <span id="userPageInfo" class="text-gray-700"></span>
                        <button id="userNextPage" class="bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded-md shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">Next</button>
                    </div>
                </div>
            </div>

            <!-- Task Management Section (Header, Form, and Table) -->
            <div id="tasksSection" class="tab-content hidden">
                <h2 class="text-2xl font-semibold text-gray-800 mb-6">Task Management</h2>

                <!-- Add/Edit Task Form -->
                <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                    <h3 class="text-xl font-semibold text-gray-700 mb-4">Add/Edit Task</h3>
                    <form id="taskForm" class="space-y-4" enctype="multipart/form-data">
                        <input type="hidden" id="taskId" name="id">
                        <div>
                            <label for="taskTitle" class="block text-sm font-medium text-gray-700">Title</label>
                            <input type="text" id="taskTitle" name="title" required
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="taskDescription" class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea id="taskDescription" name="description" rows="3"
                                      class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                        </div>
                        <div>
                            <label for="assignedToUserSearch" class="block text-sm font-medium text-gray-700">Assign To User(s)</label>
                            <input type="hidden" id="assignedToUserIds" name="assigned_to_user_ids">
                            <div class="relative mt-1" id="assignedUsersDropdownContainer">
                                <div class="relative">
                                    <input type="text" id="assignedUserSearch" placeholder="Search users to assign..."
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm pr-10" readonly>
                                    <button type="button" id="assignedUserDropdownToggle" class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer" onclick="toggleAssignedUserDropdown()">
                                        <!-- Dropdown Arrow Icon (Lucide React equivalent, using SVG for direct HTML) -->
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-down h-5 w-5 text-gray-400">
                                            <path d="m6 9 6 6 6-6"/>
                                        </svg>
                                    </button>
                                </div>
                                <!-- This is the new container that will be toggled -->
                                <div id="availableUsersListContainer" class="absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 max-h-48 overflow-y-auto hidden">
                                    <div id="availableUsersList">
                                        <!-- Available users will be loaded here by JavaScript -->
                                    </div>
                                </div>
                            </div>
                            <div id="selectedUsersDisplay" class="mt-2 p-2 border border-gray-200 rounded-md bg-gray-50 flex flex-wrap items-center min-h-[40px]">
                                <span id="noUsersSelectedText" class="text-gray-500">No users selected.</span>
                                <!-- Selected user tags will be appended here -->
                            </div>
                        </div>
                        <div>
                            <label for="deadline" class="block text-sm font-medium text-gray-700">Deadline</label>
                            <input type="date" id="deadline" name="deadline" required
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="taskStatus" class="block text-sm font-medium text-gray-700">Status</label>
                            <select id="taskStatus" name="status" required
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <option value="Pending">Pending</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Completed">Completed</option>
                                <option value="Deadline Reached">Deadline Reached</option>
                            </select>
                        </div>
                        <div>
                            <label for="taskFormFile" class="block text-sm font-medium text-gray-700">Upload Assignment Form (PDF, DOC, DOCX, TXT)</label>
                            <input type="file" id="taskFormFile" name="form_file" accept=".pdf,.doc,.docx,.txt"
                                   class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            <p id="currentFormFile" class="mt-2 text-sm text-gray-600"></p>
                        </div>
                        <div id="taskFormMessage" class="text-center text-red-600 font-medium hidden"></div>
                        <div class="flex justify-end space-x-3">
                            <button type="button" id="cancelEditTaskBtn" class="py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Cancel
                            </button>
                            <button type="submit" id="saveTaskBtn"
                                    class="py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Add Task
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Task List Table -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h3 class="text-xl font-semibold text-gray-700 mb-4">All Tasks</h3>
                    <div class="mb-4">
                        <input type="text" id="taskSearchInput" placeholder="Search tasks by title or assigned user..."
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    <div class="overflow-x-auto"> <!-- Removed max-h for outer div, will rely on flex-grow -->
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 sticky top-0 z-10">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned To</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deadline</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Admin Form</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completed Assignment</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="taskTableBody" class="bg-white divide-y divide-gray-200">
                                <!-- Task rows will be loaded here by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                    <!-- Task Pagination Controls -->
                    <div id="taskPagination" class="flex justify-center items-center space-x-4 mt-4">
                        <button id="taskPrevPage" class="bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded-md shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">Previous</button>
                        <span id="taskPageInfo" class="text-gray-700"></span>
                        <button id="taskNextPage" class="bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded-md shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">Next</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab switching logic
        document.addEventListener('DOMContentLoaded', () => {
            const usersTab = document.getElementById('usersTab');
            const tasksTab = document.getElementById('tasksTab');

            const usersSection = document.getElementById('usersSection');
            const tasksSection = document.getElementById('tasksSection');


            function showUsersTab() {
                usersSection.classList.remove('hidden');
                tasksSection.classList.add('hidden');

                usersTab.classList.add('text-indigo-600', 'border-indigo-500');
                usersTab.classList.remove('text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
                tasksTab.classList.remove('text-indigo-600', 'border-indigo-500');
                tasksTab.classList.add('text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
                fetchUsers(); // Re-fetch users when tab is shown
            }

            function showTasksTab() {
                tasksSection.classList.remove('hidden');
                usersSection.classList.add('hidden');

                tasksTab.classList.add('text-indigo-600', 'border-indigo-500');
                tasksTab.classList.remove('text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
                usersTab.classList.remove('text-indigo-600', 'border-indigo-500');
                usersTab.classList.add('text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
                fetchTasks(); // Re-fetch tasks when tab is shown
            }

            usersTab.addEventListener('click', showUsersTab);
            tasksTab.addEventListener('click', showTasksTab);

            // Show users tab by default on page load
            showUsersTab();
        });
    </script>
</body>
</html>
