// script.js - Handles client-side interactions, AJAX calls, and UI updates

// Global array to store all non-admin users for multi-selection
let allNonAdminUsers = [];
// Global array to store currently selected user IDs for task assignment
let selectedTaskUserIds = new Set();

// Pagination variables for Users
let userCurrentPage = 1;
const userItemsPerPage = 5; // Display 5 users per page

// Pagination variables for Tasks
let taskCurrentPage = 1;
const taskItemsPerPage = 5; // Display 5 tasks per page

// State variable for assigned user dropdown visibility
let isAssignedUserDropdownOpen = false;


// --- Utility Functions ---

/**
 * Displays a custom modal for confirmation.
 * @param {string} title - The title of the modal.
 * @param {string} message - The message to display in the modal.
 * @param {string} confirmBtnText - Text for the confirm button.
 * @param {string} confirmBtnClass - Tailwind classes for the confirm button.
 * @returns {Promise<boolean>} - Resolves true if confirmed, false if cancelled.
 */
function showCustomModal(title, message, confirmBtnText = 'Confirm', confirmBtnClass = 'bg-red-600 hover:bg-red-700') {
    return new Promise((resolve) => {
        const modal = document.getElementById('customModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalMessage = document.getElementById('modalMessage');
        const modalConfirmBtn = document.getElementById('modalConfirmBtn');
        const modalCancelBtn = document.getElementById('modalCancelBtn');

        modalTitle.textContent = title;
        modalMessage.textContent = message;
        modalConfirmBtn.textContent = confirmBtnText;

        // Reset and apply new classes for confirm button
        modalConfirmBtn.className = ''; // Clear existing classes
        modalConfirmBtn.classList.add('py-2', 'px-4', 'rounded-md', 'shadow-sm', 'text-sm', 'font-medium', 'text-white', ...confirmBtnClass.split(' '));

        modal.classList.remove('hidden'); // Show the modal

        const confirmHandler = () => {
            modal.classList.add('hidden'); // Hide modal
            modalConfirmBtn.removeEventListener('click', confirmHandler);
            modalCancelBtn.removeEventListener('click', cancelHandler);
            resolve(true); // Resolve with true for confirmation
        };

        const cancelHandler = () => {
            modal.classList.add('hidden'); // Hide modal
            modalConfirmBtn.removeEventListener('click', confirmHandler);
            modalCancelBtn.removeEventListener('click', cancelHandler);
            resolve(false); // Resolve with false for cancellation
        };

        modalConfirmBtn.addEventListener('click', confirmHandler);
        modalCancelBtn.addEventListener('click', cancelHandler);
    });
}

/**
 * Displays a temporary message on the UI.
 * @param {string} message - The message to display.
 * @param {string} type - 'success' or 'error'.
 * @param {string} elementId - The ID of the element to display the message in.
 */
function displayMessage(message, type, elementId) {
    const messageElement = document.getElementById(elementId);
    if (messageElement) {
        messageElement.textContent = message;
        messageElement.classList.remove('hidden', 'text-green-600', 'text-red-600');
        if (type === 'success') {
            messageElement.classList.add('text-green-600');
        } else {
            messageElement.classList.add('text-red-600');
        }
        setTimeout(() => {
            messageElement.classList.add('hidden');
        }, 3000); // Hide after 3 seconds
    }
}

// --- Login Functionality ---

async function handleLogin(event) {
    event.preventDefault(); // Prevent default form submission

    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    const loginMessage = document.getElementById('loginMessage');

    try {
        const response = await fetch('auth.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded', // Standard form encoding
            },
            body: new URLSearchParams({
                action: 'login',
                username: username,
                password: password
            }).toString(),
        });

        const data = await response.json();

        if (data.success) {
            displayMessage('Login successful!', 'success', 'loginMessage');
            window.location.href = data.redirect; // Redirect to dashboard
        } else {
            displayMessage(data.message, 'error', 'loginMessage');
        }
    } catch (error) {
        console.error('Login error:', error);
        displayMessage('An error occurred during login. Please try again.', 'error', 'loginMessage');
    }
}

// --- Admin Dashboard Functions (Users) ---

/**
 * Fetches all users and populates the user table, with optional search filtering and pagination.
 * @param {string} searchTerm - Optional search term to filter users by username or email.
 * @param {number} page - The current page number.
 * @param {number} limit - The number of items per page.
 */
async function fetchUsers(searchTerm = '', page = userCurrentPage, limit = userItemsPerPage) {
    const userTableBody = document.getElementById('userTableBody');
    if (!userTableBody) return; // Only run on admin dashboard

    try {
        // Pass search term, page, and limit to the API
        const response = await fetch(`admin_api.php?resource=users&searchTerm=${encodeURIComponent(searchTerm)}&page=${page}&limit=${limit}`);
        
        let result;
        try {
            result = await response.json();
        } catch (jsonError) {
            const errorText = await response.text();
            console.error('JSON parsing error for fetchUsers:', jsonError);
            console.error('Raw response text for fetchUsers:', errorText);
            displayMessage('Server response error. Please check console for details.', 'error', 'userFormMessage');
            return; // Exit if JSON parsing failed
        }


        if (result.success) {
            userTableBody.innerHTML = ''; // Clear existing rows
            let userNumber = (page - 1) * limit + 1; // Initialize user counter for display

            if (result.data.length === 0) {
                userTableBody.innerHTML = `<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">No users found matching your search.</td></tr>`;
            } else {
                result.data.forEach(user => { // Use result.data directly as it's already filtered
                    const row = userTableBody.insertRow();
                    row.innerHTML = `
                        <td class="px-6 py-4">${userNumber++}</td> <!-- Display sequential number -->
                        <td class="px-6 py-4">${user.username}</td>
                        <td class="px-6 py-4">${user.email}</td>
                        <td class="px-6 py-4">${user.role}</td>
                        <td class="px-6 py-4 text-sm font-medium">
                            <button onclick="editUser(${user.id}, '${user.username}', '${user.email}', '${user.role}')"
                                    class="py-1 px-2 rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 mr-3">Edit</button>
                            <button onclick="confirmDeleteUser(${user.id})"
                                    class="py-1 px-2 rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700">Delete</button>
                        </td>
                    `;
                });
            }
            // Update pagination controls for users
            updatePaginationControls('user', result.totalRecords, page, limit);
        } else {
            console.error('Failed to fetch users:', result.message);
            userTableBody.innerHTML = `<tr><td colspan="5" class="px-6 py-4 text-center text-red-500">Error loading users: ${result.message}</td></tr>`;
        }
    } catch (error) {
        console.error('Error fetching users:', error);
        userTableBody.innerHTML = `<tr><td colspan="5" class="px-6 py-4 text-center text-red-500">An error occurred while fetching users.</td></tr>`;
    }
}

/**
 * Populates the user form for editing.
 * @param {number} id
 * @param {string} username
 * @param {string} email
 * @param {string} role
 */
function editUser(id, username, email, role) {
    document.getElementById('userId').value = id;
    document.getElementById('username').value = username;
    document.getElementById('email').value = email;
    document.getElementById('password').value = ''; // Clear password field for security
    document.getElementById('role').value = role;

    document.getElementById('saveUserBtn').textContent = 'Update User';
    document.getElementById('cancelEditUserBtn').classList.remove('hidden');
}

/**
 * Resets the user form to 'Add User' state.
 */
function resetUserForm() {
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('saveUserBtn').textContent = 'Add User';
    document.getElementById('cancelEditUserBtn').classList.add('hidden');
    const currentFormFileElement = document.getElementById('currentFormFile');
    if (currentFormFileElement) { // Add a check for existence
        currentFormFileElement.textContent = ''; // Clear current file info
    }
    document.getElementById('taskFormFile').value = ''; // Clear file input

    // Reset multi-select for users
    selectedTaskUserIds.clear();
    filterAvailableUsers(); // Re-render available users
    updateSelectedUsersDisplay(); // Clear selected users display
}

/**
 * Handles user form submission (add or update).
 * @param {Event} event
 */
async function handleUserFormSubmit(event) {
    event.preventDefault();

    const userId = document.getElementById('userId').value;
    const username = document.getElementById('username').value;
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const role = document.getElementById('role').value;

    const actionType = userId ? 'update' : 'add';
    const confirmationMessage = userId ? 'Are you sure you want to update this user?' : 'Are you sure you want to add this user?';
    const confirmBtnText = userId ? 'Update User' : 'Add User';
    const confirmBtnClass = userId ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-indigo-600 hover:bg-indigo-700';

    const confirmed = await showCustomModal(
        'Confirm User Action',
        confirmationMessage,
        confirmBtnText,
        confirmBtnClass
    );

    if (!confirmed) {
        displayMessage('User action cancelled.', 'error', 'userFormMessage');
        return;
    }

    const method = userId ? 'PUT' : 'POST';
    const url = `admin_api.php?resource=users`;
    const body = { id: userId, username, email, password, role };

    // Remove password from body if it's empty during update
    if (method === 'PUT' && password === '') {
        delete body.password;
    }

    try {
        const response = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });

        // Attempt to parse JSON. If it fails, log the raw response text.
        let result;
        try {
            result = await response.json();
        } catch (jsonError) {
            const errorText = await response.text();
            console.error('JSON parsing error for user form submit:', jsonError);
            console.error('Raw response text:', errorText);
            displayMessage('Server response error. Please check console for details.', 'error', 'userFormMessage');
            return; // Exit if JSON parsing failed
        }


        if (result.success) {
            displayMessage(result.message, 'success', 'userFormMessage'); // Display success message
            resetUserForm();
            fetchUsers(); // Refresh table
            populateAssignedUsersDropdown(); // Refresh dropdown in task section
        } else {
            displayMessage(result.message, 'error', 'userFormMessage');
        }
    } catch (error) {
        console.error('Error saving user:', error);
        displayMessage('An error occurred while saving user.', 'error', 'userFormMessage');
    }
}

/**
 * Confirms and deletes a user.
 * @param {number} id - User ID to delete.
 */
async function confirmDeleteUser(id) {
    const confirmed = await showCustomModal(
        'Confirm Deletion',
        'Are you sure you want to delete this user? This action cannot be undone.',
        'Delete User',
        'bg-red-600 hover:bg-red-700'
    );

    if (confirmed) {
        try {
            const response = await fetch(`admin_api.php?resource=users`, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });

            // Attempt to parse JSON. If it fails, log the raw response text.
            let result;
            try {
                result = await response.json();
            } catch (jsonError) {
                const errorText = await response.text();
                console.error('JSON parsing error for user delete:', jsonError);
                console.error('Raw response text:', errorText);
                displayMessage('Server response error. Please check console for details.', 'error', 'userFormMessage');
                return; // Exit if JSON parsing failed
            }

            if (result.success) {
                displayMessage(result.message, 'success', 'userFormMessage'); // Display success message
                fetchUsers(); // Refresh table
                fetchTasks(); // Tasks might be affected if user had tasks
                populateAssignedUsersDropdown(); // Refresh dropdown
            } else {
                displayMessage(result.message, 'error', 'userFormMessage');
            }
        } catch (error) {
            console.error('Error deleting user:', error);
            displayMessage('An error occurred while deleting user.', 'error', 'userFormMessage');
        }
    }
}

// --- Admin Dashboard Functions (Tasks) ---

/**
 * Populates the 'Assign To User(s)' multi-select with non-admin users.
 */
async function populateAssignedUsersDropdown() {
    const availableUsersList = document.getElementById('availableUsersList');
    if (!availableUsersList) return; // Only run on admin dashboard

    try {
        // Fetch ALL users for the dropdown, not just a paginated subset
        const response = await fetch('admin_api.php?resource=users&limit=9999'); // Request a very large limit
        const result = await response.json();

        if (result.success) {
            // Store all non-admin users globally
            allNonAdminUsers = result.data.filter(user => user.role !== 'admin');
            filterAvailableUsers(); // Initial render of available users
        } else {
            console.error('Failed to fetch users for dropdown:', result.message);
        }
    }
    catch (error) {
        console.error('Error fetching users for dropdown:', error);
    }
}

/**
 * Filters and renders the available users list based on search input.
 */
function filterAvailableUsers() {
    const searchTerm = document.getElementById('assignedUserSearch').value.toLowerCase();
    const availableUsersList = document.getElementById('availableUsersList');
    availableUsersList.innerHTML = ''; // Clear existing list

    const filteredUsers = allNonAdminUsers.filter(user =>
        user.username.toLowerCase().includes(searchTerm) ||
        user.email.toLowerCase().includes(searchTerm)
    );

    if (filteredUsers.length === 0) {
        availableUsersList.innerHTML = '<p class="p-2 text-gray-500">No users found.</p>';
        return;
    }

    filteredUsers.forEach(user => {
        const isSelected = selectedTaskUserIds.has(user.id);
        const userItem = document.createElement('div');
        userItem.className = `p-2 cursor-pointer hover:bg-gray-100 flex items-center justify-between ${isSelected ? 'bg-indigo-100' : ''}`;
        userItem.innerHTML = `
            <span>${user.username} (${user.email})</span>
            <input type="checkbox" data-user-id="${user.id}" class="form-checkbox h-5 w-5 text-indigo-600" ${isSelected ? 'checked' : ''}>
        `;
        userItem.querySelector('input[type="checkbox"]').addEventListener('change', (event) => {
            toggleUserSelection(user.id, event.target.checked, user.username);
        });
        availableUsersList.appendChild(userItem);
    });
    updateSelectedUsersDisplay(); // Update display after filtering
}

/**
 * Toggles a user's selection for task assignment.
 * @param {number} userId
 * @param {boolean} isChecked
 * @param {string} username
 */
function toggleUserSelection(userId, isChecked, username) {
    if (isChecked) {
        selectedTaskUserIds.add(userId);
    } else {
        selectedTaskUserIds.delete(userId);
    }
    updateSelectedUsersDisplay();
    // Re-filter to update checkbox state in the available list immediately
    filterAvailableUsers();
}

/**
 * Updates the display of selected users.
 */
function updateSelectedUsersDisplay() {
    const selectedUsersDisplay = document.getElementById('selectedUsersDisplay');
    if (!selectedUsersDisplay) {
        console.error("DOM element 'selectedUsersDisplay' not found.");
        return;
    }

    // Clear all existing content in the display area
    selectedUsersDisplay.innerHTML = '';

    // Create and append the "No users selected" text element
    const noUsersSelectedText = document.createElement('span');
    noUsersSelectedText.id = 'noUsersSelectedText';
    noUsersSelectedText.className = 'text-gray-500'; // Apply initial styling
    noUsersSelectedText.textContent = 'No users selected.';
    selectedUsersDisplay.appendChild(noUsersSelectedText);

    if (selectedTaskUserIds.size === 0) {
        // If no users are selected, ensure the "No users selected" text is visible
        noUsersSelectedText.classList.remove('hidden');
    } else {
        // If users are selected, hide the "No users selected" text
        noUsersSelectedText.classList.add('hidden');
        selectedTaskUserIds.forEach(userId => {
            const user = allNonAdminUsers.find(u => u.id === userId);
            if (user) {
                const selectedUserTag = document.createElement('span');
                selectedUserTag.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-indigo-200 text-indigo-800 mr-2 mb-2';
                selectedUserTag.innerHTML = `
                    ${user.username}
                    <button type="button" onclick="toggleUserSelection(${user.id}, false, '${user.username}')" class="flex-shrink-0 ml-1.5 h-4 w-4 rounded-full inline-flex items-center justify-center text-indigo-400 hover:bg-indigo-300 hover:text-indigo-500 focus:outline-none focus:bg-indigo-300 focus:text-indigo-500">
                        <span class="sr-only">Remove user</span>
                        <svg class="h-2 w-2" stroke="currentColor" fill="none" viewBox="0 0 8 8">
                            <path stroke-linecap="round" stroke-width="1.5" d="M1 1l6 6m0-6L1 7" />
                        </svg>
                    </button>
                `;
                // Insert new tags before the placeholder text
                selectedUsersDisplay.insertBefore(selectedUserTag, noUsersSelectedText);
            }
        });
    }
    // Update the hidden input with comma-separated IDs
    document.getElementById('assignedToUserIds').value = Array.from(selectedTaskUserIds).join(',');
}


/**
 * Fetches all tasks and populates the task table, with optional search filtering and pagination.
 * @param {string} searchTerm - Optional search term to filter tasks by title or assigned username.
 * @param {number} page - The current page number.
 * @param {number} limit - The number of items per page.
 */
async function fetchTasks(searchTerm = '', page = taskCurrentPage, limit = taskItemsPerPage) {
    const taskTableBody = document.getElementById('taskTableBody');
    if (!taskTableBody) return; // Only run on admin dashboard

    try {
        // Pass search term, page, and limit to the API
        const response = await fetch(`admin_api.php?resource=tasks&searchTerm=${encodeURIComponent(searchTerm)}&page=${page}&limit=${limit}`);
        
        let result;
        try {
            result = await response.json();
        } catch (jsonError) {
            const errorText = await response.text();
            console.error('JSON parsing error for fetchTasks:', jsonError);
            console.error('Raw response text for fetchTasks:', errorText);
            displayMessage('Server response error. Please check console for details.', 'error', 'taskFormMessage');
            return; // Exit if JSON parsing failed
        }

        if (result.success) {
            taskTableBody.innerHTML = ''; // Clear existing rows
            let taskNumber = (page - 1) * limit + 1; // Initialize task counter for display

            if (result.data.length === 0) {
                taskTableBody.innerHTML = `<tr><td colspan="8" class="px-6 py-4 text-center text-gray-500">No tasks found matching your search.</td></tr>`;
            } else {
                result.data.forEach(task => { // Use result.data directly as it's already filtered
                    const adminFormLink = task.form_path ?
                        `<a href="${task.form_path}" target="_blank" class="text-blue-600 hover:underline">View Assignment</a>` :
                        'N/A';
                    const completedAssignmentLink = task.completed_assignment_path ?
                        `<a href="${task.completed_assignment_path}" target="_blank" class="text-green-600 hover:underline">View Assignment</a>` :
                        'N/A';

                    const row = taskTableBody.insertRow();
                    row.innerHTML = `
                        <td class="px-6 py-4">${taskNumber++}</td> <!-- Display sequential number -->
                        <td class="px-6 py-4">${task.title}</td>
                        <td class="px-6 py-4">${task.assigned_to_username}</td>
                        <td class="px-6 py-4">${task.deadline}</td>
                        <td class="px-6 py-4">${task.status}</td>
                        <td class="px-6 py-4">${adminFormLink}</td>
                        <td class="px-6 py-4">${completedAssignmentLink}</td>
                        <td class="px-6 py-4 text-sm font-medium">
                            <button onclick="editTask(${task.id}, '${task.title}', '${task.description}', ${task.assigned_to_user_id}, '${task.deadline}', '${task.status}', '${task.form_path || ''}')"
                                    class="py-1 px-2 rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 mr-3">Edit</button>
                            <button onclick="confirmDeleteTask(${task.id})"
                                    class="py-1 px-2 rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700">Delete</button>
                        </td>
                    `;
                });
            }
            // Update pagination controls for tasks
            updatePaginationControls('task', result.totalRecords, page, limit);
        } else {
            console.error('Failed to fetch tasks:', result.message);
            taskTableBody.innerHTML = `<tr><td colspan="8" class="px-6 py-4 text-center text-red-500">Error loading tasks: ${result.message}</td></tr>`;
        }
    } catch (error) {
        console.error('Error fetching tasks:', error);
        taskTableBody.innerHTML = `<tr><td colspan="8" class="px-6 py-4 text-center text-red-500">An error occurred while fetching tasks.</td></tr>`;
    }
}

/**
 * Populates the task form for editing.
 * @param {number} id
 * @param {string} title
 * @param {string} description
 * @param {number} assigned_to_user_id - This will be a single ID for existing tasks
 * @param {string} deadline
 * @param {string} status
 * @param {string} form_path - New parameter for the form file path
 */
function editTask(id, title, description, assigned_to_user_id, deadline, status, form_path) {
    document.getElementById('taskId').value = id;
    document.getElementById('taskTitle').value = title;
    document.getElementById('taskDescription').value = description;
    document.getElementById('deadline').value = deadline;
    document.getElementById('taskStatus').value = status;

    // Clear and set selected users for editing existing task
    selectedTaskUserIds.clear();
    // When editing, we assume a task is assigned to a single user for now
    // If you implement true multi-assignment for existing tasks, this logic needs adjustment
    if (assigned_to_user_id) {
        selectedTaskUserIds.add(assigned_to_user_id);
    }
    filterAvailableUsers(); // Re-render available users with correct selections
    updateSelectedUsersDisplay(); // Update the selected users display

    const currentFormFileElement = document.getElementById('currentFormFile');
    if (currentFormFileElement) { // Add a check for existence
        if (form_path) {
            const fileName = form_path.split('/').pop(); // Get just the file name
            currentFormFileElement.innerHTML = `Current file: <a href="${form_path}" target="_blank" class="text-blue-600 hover:underline">${fileName}</a> (Upload new to replace)`;
        } else {
            currentFormFileElement.textContent = 'No file currently attached.';
        }
    }
    document.getElementById('taskFormFile').value = ''; // Clear file input for security/re-upload
    
    document.getElementById('saveTaskBtn').textContent = 'Update Task';
    document.getElementById('cancelEditTaskBtn').classList.remove('hidden');
}

/**
 * Resets the task form to 'Add Task' state.
 */
function resetTaskForm() {
    document.getElementById('taskForm').reset();
    document.getElementById('taskId').value = '';
    document.getElementById('saveTaskBtn').textContent = 'Add Task';
    document.getElementById('cancelEditTaskBtn').classList.add('hidden');
    const currentFormFileElement = document.getElementById('currentFormFile');
    if (currentFormFileElement) { // Add a check for existence
        currentFormFileElement.textContent = ''; // Clear current file info
    }
    document.getElementById('taskFormFile').value = ''; // Clear file input

    // Reset multi-select for users
    selectedTaskUserIds.clear();
    filterAvailableUsers(); // Re-render available users
    updateSelectedUsersDisplay(); // Clear selected users display
    closeAssignedUserDropdown(); // Ensure dropdown is closed on form reset
}

/**
 * Handles task form submission (add or update), including file upload.
 * @param {Event} event
 */
async function handleTaskFormSubmit(event) {
    event.preventDefault(); // Prevent default form submission

    console.log('handleTaskFormSubmit triggered.'); // Debugging log

    const taskId = document.getElementById('taskId').value;
    const url = `admin_api.php?resource=tasks`;

    const actionType = taskId ? 'update' : 'add';
    const confirmationMessage = taskId ? 'Are you sure you want to update this task? Any uploaded file will replace the existing one.' : 'Are you sure you want to add this task?';
    const confirmBtnText = taskId ? 'Update Task' : 'Add Task';
    const confirmBtnClass = taskId ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-indigo-600 hover:bg-indigo-700';

    const confirmed = await showCustomModal(
        'Confirm Task Action',
        confirmationMessage,
        confirmBtnText,
        confirmBtnClass
    );

    if (!confirmed) {
        displayMessage('Task action cancelled.', 'error', 'taskFormMessage');
        return;
    }

    // Get selected user IDs from the global Set
    const assignedToUserIds = Array.from(selectedTaskUserIds);

    if (assignedToUserIds.length === 0 && !taskId) { // Only require selection for new tasks
        displayMessage('Please select at least one user to assign the task to.', 'error', 'taskFormMessage');
        return;
    }

    // Use FormData for file uploads and other form data
    const formData = new FormData(event.target);

    // Remove the old assigned_to_user_id if it exists, as we're now sending assigned_to_user_ids (plural)
    formData.delete('assigned_to_user_id');

    // Append taskId to formData explicitly if it exists (for updates)
    if (taskId) {
        formData.append('id', taskId);
        console.log('Appending taskId to FormData:', taskId); // Debugging log
    } else {
        console.log('No taskId found, likely adding a new task.'); // Debugging log
    }

    // Append the array of selected user IDs as a JSON string
    formData.append('assigned_to_user_ids', JSON.stringify(assignedToUserIds));
    console.log('Appending assigned_to_user_ids to FormData:', JSON.stringify(assignedToUserIds));


    // Log all FormData entries for debugging
    console.log('FormData contents:');
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }

    try {
        console.log('Initiating fetch POST request to:', url); // Debugging log
        const response = await fetch(url, {
            method: 'POST', // Always POST for task form submission with file upload
            body: formData // FormData automatically sets Content-Type: multipart/form-data
        });

        console.log('Fetch request completed. Response status:', response.status); // Debugging log
        let result;
        try {
            result = await response.json();
        } catch (jsonError) {
            const errorText = await response.text();
            console.error('JSON parsing error for task form submit:', jsonError);
            console.error('Raw response text:', errorText);
            displayMessage('Server response error. Please check console for details.', 'error', 'taskFormMessage');
            return; // Exit if JSON parsing failed
        }
        console.log('Response JSON:', result); // Debugging log

        if (result.success) {
            displayMessage(result.message, 'success', 'taskFormMessage'); // Display success message
            resetTaskForm();
            fetchTasks(); // Refresh table
        } else {
            displayMessage(result.message, 'error', 'taskFormMessage');
        }
    } catch (error) {
        console.error('Error saving task:', error);
        displayMessage('An error occurred while saving task.', 'error', 'taskFormMessage');
    }
}

/**
 * Confirms and deletes a task.
 * @param {number} id - Task ID to delete.
 */
async function confirmDeleteTask(id) {
    const confirmed = await showCustomModal(
        'Confirm Deletion',
        'Are you sure you want to delete this task? This action cannot be undone. This will also delete any associated forms (admin and completed assignment).',
        'Delete Task',
        'bg-red-600 hover:bg-red-700'
    );

    if (confirmed) {
        try {
            const response = await fetch(`admin_api.php?resource=tasks`, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });

            // Attempt to parse JSON. If it fails, log the raw response text.
            let result;
            try {
                result = await response.json();
            } catch (jsonError) {
                const errorText = await response.text();
                console.error('JSON parsing error for task delete:', jsonError);
                console.error('Raw response text:', errorText);
                displayMessage('Server response error. Please check console for details.', 'error', 'taskFormMessage');
                return; // Exit if JSON parsing failed
            }

            if (result.success) {
                displayMessage(result.message, 'success', 'taskFormMessage'); // Display success message
                fetchTasks(); // Refresh table
            } else {
                displayMessage(result.message, 'error', 'taskFormMessage');
            }
        } catch (error) {
            console.error('Error deleting task:', error);
            displayMessage('An error occurred while deleting task.', 'error', 'taskFormMessage');
        }
    }
}

// --- User Dashboard Functions ---

/**
 * Handles user's completed assignment file upload.
 * @param {number} taskId - The ID of the task to upload for.
 * @param {HTMLInputElement} fileInput - The file input element.
 */
async function handleCompletedAssignmentUpload(taskId, fileInput) {
    const file = fileInput.files[0];
    if (!file) {
        displayMessage('No file selected for upload.', 'error', 'userTaskMessage');
        return;
    }

    const formData = new FormData();
    formData.append('task_id', taskId);
    formData.append('completed_assignment_file', file);

    const confirmed = await showCustomModal(
        'Confirm Assignment Upload',
        'Are you sure you want to upload this assignment? This will replace any existing submission.',
        'Upload Assignment',
        'bg-green-600 hover:bg-green-700'
    );

    if (!confirmed) {
        displayMessage('Assignment upload cancelled.', 'error', 'userTaskMessage');
        // Clear the file input if the user cancels
        fileInput.value = '';
        return;
    }

    try {
        const response = await fetch(`user_api.php?resource=tasks&action=upload_completed_assignment`, {
            method: 'POST',
            body: formData
        });

        // Attempt to parse JSON. If it fails, log the raw response text.
        let result;
        try {
            result = await response.json();
        } catch (jsonError) {
            const errorText = await response.text();
            console.error('JSON parsing error for completed assignment upload:', jsonError);
            console.error('Raw response text:', errorText);
            displayMessage('Server response error. Please check console for details.', 'error', 'userTaskMessage');
            return; // Exit if JSON parsing failed
        }

        if (result.success) {
            displayMessage('Assignment uploaded successfully!', 'success', 'userTaskMessage'); // Display success message
            fetchUserTasks(); // Refresh table to show updated assignment link
        } else {
            displayMessage(result.message, 'error', 'userTaskMessage');
        }
    } catch (error) {
        console.error('Error uploading completed assignment:', error);
        displayMessage('An error occurred during upload. Please try again.', 'error', 'userTaskMessage');
    }
}


/**
 * Fetches tasks assigned to the logged-in user and populates the table.
 */
async function fetchUserTasks() {
    const userTaskTableBody = document.getElementById('userTaskTableBody');
    if (!userTaskTableBody) return; // Only run on user dashboard

    try {
        const response = await fetch('user_api.php?resource=tasks');
        
        let result;
        try {
            result = await response.json();
        } catch (jsonError) {
            const errorText = await response.text();
            console.error('JSON parsing error for fetchUserTasks:', jsonError);
            console.error('Raw response text for fetchUserTasks:', errorText);
            displayMessage('Server response error. Please check console for details.', 'error', 'userTaskMessage');
            return; // Exit if JSON parsing failed
        }

        if (result.success) {
            userTaskTableBody.innerHTML = ''; // Clear existing rows
            let taskNumber = 1; // Initialize task counter for display

            result.data.forEach(task => {
                const adminFormLink = task.form_path ?
                    `<a href="${task.form_path}" target="_blank" class="text-blue-600 hover:underline">View Assignment</a>` :
                    'N/A'; // Changed "Admin Form" to "Assignment"

                let completedAssignmentContent;
                // Use a unique ID for the file input for each task
                const fileInputId = `completedAssignmentFile_${task.id}`;

                if (task.completed_assignment_path) {
                    const fileName = task.completed_assignment_path.split('/').pop();
                    if (task.status === 'Completed') {
                        // If completed, only show "View Assignment" link, no change option
                        completedAssignmentContent = `
                            <a href="${task.completed_assignment_path}" target="_blank" class="text-green-600 hover:underline">View Assignment</a>
                        `;
                    } else {
                        // If not completed, show "View Assignment" link and "Change" option
                        completedAssignmentContent = `
                            <a href="${task.completed_assignment_path}" target="_blank" class="text-green-600 hover:underline">View Assignment</a>
                            <label for="${fileInputId}" class="ml-2 py-1 px-2 rounded-md shadow-sm text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700 cursor-pointer">Change</label>
                            <input type="file" id="${fileInputId}" style="display:none;" onchange="handleCompletedAssignmentUpload(${task.id}, this)" accept=".pdf,.doc,.docx,.txt,.zip,.rar,.jpg,.jpeg,.png">
                        `;
                    }
                } else {
                    // If no assignment uploaded yet, and task is completed, show a non-executable dropdown
                    if (task.status === 'Completed') {
                        completedAssignmentContent = `
                            <span class="inline-flex items-center px-3 py-1 rounded-md text-sm font-medium bg-gray-200 text-gray-500 cursor-not-allowed">
                                Not uploaded <svg class="ml-1 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </span>
                        `;
                    } else {
                        // If not completed, show interactive "Choose File" with dropdown look
                        completedAssignmentContent = `
                            <label for="${fileInputId}" class="inline-flex items-center px-3 py-1 rounded-md text-sm font-medium bg-green-600 hover:bg-green-700 text-white cursor-pointer">
                                Choose File <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </label>
                            <input type="file" id="${fileInputId}" style="display:none;" onchange="handleCompletedAssignmentUpload(${task.id}, this)" accept=".pdf,.doc,.docx,.txt,.zip,.rar,.jpg,.jpeg,.png">
                        `;
                    }
                }

                const statusDropdownDisabled = task.status === 'Completed' ? 'disabled' : ''; // Disable if completed

                const row = userTaskTableBody.insertRow();
                row.innerHTML = `
                    <td class="px-6 py-4">${taskNumber++}</td> <!-- Display sequential number -->
                    <td class="px-6 py-4">${task.title}</td>
                    <td class="px-6 py-4 max-w-xs overflow-hidden text-ellipsis">${task.description || 'N/A'}</td>
                    <td class="px-6 py-4">${task.deadline}</td>
                    <td class="px-6 py-4">
                        <select onchange="handleStatusChange(${task.id}, this.value, '${task.status}')"
                                class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                                ${statusDropdownDisabled}>
                            <option value="Pending" ${task.status === 'Pending' ? 'selected' : ''}>Pending</option>
                            <option value="In Progress" ${task.status === 'In Progress' ? 'selected' : ''}>In Progress</option>
                            <option value="Completed" ${task.status === 'Completed' ? 'selected' : ''}>Completed</option>
                            <option value="Deadline Reached" ${task.status === 'Deadline Reached' ? 'selected' : ''}>Deadline Reached</option>
                        </select>
                    </td>
                    <td class="px-6 py-4">${adminFormLink}</td>
                    <td class="px-6 py-4">${completedAssignmentContent}</td>
                    <td class="px-6 py-4 text-sm font-medium">
                        <span class="text-gray-500">${task.status}</span>
                    </td>
                `;
            });
        } else {
            console.error('Failed to fetch user tasks:', result.message);
            // Optionally display a message to the user
        }
    } catch (error) {
        console.error('Error fetching user tasks:', error);
        // Optionally display a message to the user
    }
}

/**
 * Handles status change, with confirmation for 'Completed'.
 * @param {number} taskId
 * @param {string} newStatus
 * @param {string} originalStatus
 */
async function handleStatusChange(taskId, newStatus, originalStatus) {
    if (newStatus === 'Completed') {
        const confirmed = await showCustomModal(
            'Confirm Task Completion',
            'Are you sure you want to mark this task as Completed? This action cannot be undone.',
            'Mark as Complete',
            'bg-green-600 hover:bg-green-700'
        );

        if (!confirmed) {
            // Revert the select box to its original value if user cancels
            const selectElement = document.querySelector(`select[onchange*="handleStatusChange(${taskId}"]`);
            if (selectElement) {
                selectElement.value = originalStatus;
            }
            return; // Stop further processing
        }
    }
    // Proceed with status update if not 'Completed' or if 'Completed' was confirmed
    updateTaskStatus(taskId, newStatus);
}

/**
 * Updates the status of a user's task.
 * @param {number} taskId
 * @param {string} newStatus
 */
async function updateTaskStatus(taskId, newStatus) {
    try {
        const response = await fetch(`user_api.php?resource=tasks`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: taskId, status: newStatus })
        });

        // Attempt to parse JSON. If it fails, log the raw response text.
        let result;
        try {
            result = await response.json();
        } catch (jsonError) {
            const errorText = await response.text();
            console.error('JSON parsing error for task status update:', jsonError);
            console.error('Raw response text:', errorText);
            displayMessage('Server response error. Please check console for details.', 'error', 'userTaskMessage');
            return; // Exit if JSON parsing failed
        }

        if (result.success) {
            displayMessage('Task status updated successfully!', 'success', 'userTaskMessage'); // Display success message
            fetchUserTasks(); // Refresh table to reflect changes
        } else {
            displayMessage(result.message, 'error', 'userTaskMessage');
            // If update fails, revert the select box to its original value
            fetchUserTasks(); // Re-fetch to ensure UI is in sync
        }
    } catch (error) {
        console.error('Error updating task status:', error);
        displayMessage('An error occurred while updating task status.', 'error', 'userTaskMessage');
        fetchUserTasks(); // Re-fetch to ensure UI is in sync
    }
}

/**
 * Updates the pagination controls for a given table.
 * @param {string} type - 'user' or 'task'
 * @param {number} totalRecords - Total number of records.
 * @param {number} currentPage - The current page number.
 * @param {number} itemsPerPage - Number of items per page.
 */
function updatePaginationControls(type, totalRecords, currentPage, itemsPerPage) {
    const totalPages = Math.ceil(totalRecords / itemsPerPage);
    const pageInfoSpan = document.getElementById(`${type}PageInfo`);
    let prevBtn = document.getElementById(`${type}PrevPage`);
    let nextBtn = document.getElementById(`${type}NextPage`);

    if (!pageInfoSpan || !prevBtn || !nextBtn) {
        console.warn(`Pagination elements for ${type} not found. Skipping pagination update.`);
        return;
    }

    pageInfoSpan.textContent = `Page ${currentPage} of ${totalPages}`;

    // Enable/disable buttons
    prevBtn.disabled = currentPage <= 1;
    nextBtn.disabled = currentPage >= totalPages;

    // --- DEBUGGING LOGS ---
    console.log(`--- Pagination Update for ${type} ---`);
    console.log(`Total Records: ${totalRecords}`);
    console.log(`Items Per Page: ${itemsPerPage}`);
    console.log(`Current Page: ${currentPage}`);
    console.log(`Total Pages: ${totalPages}`);
    console.log(`Prev Button Disabled: ${prevBtn.disabled}`);
    console.log(`Next Button Disabled: ${nextBtn.disabled}`);
    // --- END DEBUGGING LOGS ---

    // Remove existing event listeners to prevent multiple firings
    // Check if clickHandler property exists before removing to avoid errors on initial load
    if (prevBtn.clickHandler) {
        prevBtn.removeEventListener('click', prevBtn.clickHandler);
    }
    if (nextBtn.clickHandler) {
        nextBtn.removeEventListener('click', nextBtn.clickHandler);
    }

    // Define new click handlers
    const prevClickHandler = () => {
        if (type === 'user') {
            userCurrentPage--;
            console.log(`User Prev Clicked. New userCurrentPage: ${userCurrentPage}`); // Debug
            fetchUsers(document.getElementById('userSearchInput').value, userCurrentPage, userItemsPerPage);
        } else {
            taskCurrentPage--;
            console.log(`Task Prev Clicked. New taskCurrentPage: ${taskCurrentPage}`); // Debug
            fetchTasks(document.getElementById('taskSearchInput').value, taskCurrentPage, taskItemsPerPage);
        }
    };

    const nextClickHandler = () => {
        if (type === 'user') {
            userCurrentPage++;
            console.log(`User Next Clicked. New userCurrentPage: ${userCurrentPage}`); // Debug
            fetchUsers(document.getElementById('userSearchInput').value, userCurrentPage, userItemsPerPage);
        } else {
            taskCurrentPage++;
            console.log(`Task Next Clicked. New taskCurrentPage: ${taskCurrentPage}`); // Debug
            fetchTasks(document.getElementById('taskSearchInput').value, taskCurrentPage, taskItemsPerPage);
        }
    };

    // Store the handlers on the elements themselves to easily remove them later
    prevBtn.clickHandler = prevClickHandler;
    nextBtn.clickHandler = nextClickHandler;

    // Add new event listeners
    prevBtn.addEventListener('click', prevBtn.clickHandler);
    nextBtn.addEventListener('click', nextBtn.clickHandler);
}

/**
 * Toggles the visibility of the assigned user dropdown.
 */
function toggleAssignedUserDropdown() {
    const availableUsersListContainer = document.getElementById('availableUsersListContainer');
    if (availableUsersListContainer) {
        availableUsersListContainer.classList.toggle('hidden');
        isAssignedUserDropdownOpen = !availableUsersListContainer.classList.contains('hidden');
        if (isAssignedUserDropdownOpen) {
            filterAvailableUsers(); // Refresh the list when opening
        }
    }
}

/**
 * Closes the assigned user dropdown.
 */
function closeAssignedUserDropdown() {
    const availableUsersListContainer = document.getElementById('availableUsersListContainer');
    if (availableUsersListContainer && !availableUsersListContainer.classList.contains('hidden')) {
        availableUsersListContainer.classList.add('hidden');
        isAssignedUserDropdownOpen = false;
    }
}


// Initial fetches and event listeners when the DOM is fully loaded
document.addEventListener('DOMContentLoaded', () => {
    // Only fetch if the elements exist (i.e., we are on the admin dashboard)
    if (document.getElementById('userTableBody')) {
        fetchUsers();
        populateAssignedUsersDropdown(); // For the task assignment multi-select
    }
    if (document.getElementById('taskTableBody')) {
        fetchTasks();
    }

    // Add event listeners for search inputs
    const userSearchInput = document.getElementById('userSearchInput');
    if (userSearchInput) {
        userSearchInput.addEventListener('input', (event) => {
            userCurrentPage = 1; // Reset to first page on new search
            fetchUsers(event.target.value);
        });
    }

    const taskSearchInput = document.getElementById('taskSearchInput');
    if (taskSearchInput) {
        taskSearchInput.addEventListener('input', (event) => {
            taskCurrentPage = 1; // Reset to first page on new search
            fetchTasks(event.target.value);
        });
    }

    // Event listeners for user form
    const userForm = document.getElementById('userForm');
    if (userForm) {
        userForm.addEventListener('submit', handleUserFormSubmit);
    }
    const cancelEditUserBtn = document.getElementById('cancelEditUserBtn');
    if (cancelEditUserBtn) {
        cancelEditUserBtn.addEventListener('click', resetUserForm);
    }

    // Event listeners for task form
    const taskForm = document.getElementById('taskForm');
    if (taskForm) {
        taskForm.addEventListener('submit', handleTaskFormSubmit);
    }
    const cancelEditTaskBtn = document.getElementById('cancelEditTaskBtn');
    if (cancelEditTaskBtn) {
        cancelEditTaskBtn.addEventListener('click', resetTaskForm);
    }

    // Event listener for assigned user search filter
    const assignedUserSearch = document.getElementById('assignedUserSearch');
    if (assignedUserSearch) {
        // Toggle dropdown visibility when the search input is clicked
        assignedUserSearch.addEventListener('click', (event) => {
            event.stopPropagation(); // Prevent this click from immediately closing the dropdown via document listener
            toggleAssignedUserDropdown();
        });
        // Filter as user types (regardless of dropdown open/closed state)
        assignedUserSearch.addEventListener('input', filterAvailableUsers);
    }

    // Close dropdown when clicking anywhere else on the document
    document.addEventListener('click', (event) => {
        const assignedUsersDropdownContainer = document.getElementById('assignedUsersDropdownContainer');
        const assignedUserSearchInput = document.getElementById('assignedUserSearch');

        // Check if the click target is outside the dropdown container AND outside the search input
        if (assignedUsersDropdownContainer && assignedUserSearchInput &&
            !assignedUsersDropdownContainer.contains(event.target) &&
            !assignedUserSearchInput.contains(event.target)) {
            closeAssignedUserDropdown();
        }
    });
});
