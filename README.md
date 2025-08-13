# Task Management System (PHP & MySQL)

A simple yet effective task management system built with **PHP**, **MySQL**, and **Bootstrap**.  
It allows administrators to manage users and tasks, while users can view and update the status of their assigned tasks.

---

## Features

- **Admin Dashboard**
  - Add, edit, or delete users.
  - Assign tasks with deadlines.
  - Manage task status: Pending, In Progress, Completed.

- **User Dashboard**
  - View tasks assigned to you.
  - Update the status of tasks.
  - View deadlines and progress.

- **Authentication**
  - Secure login for administrators and users.

---

## Screenshots

### 1. Login Page
![Login Page](screenshots/login%20page.png)

### 2. Admin Panel
![Admin Panel](screenshots/admin%20panel.png)

### 3. Manage Tasks Panel
![Manage Tasks Panel](screenshots/manage%20tasks%20panel.png)

### 4. User Dashboard
![User Dashboard](screenshots/user%20dashboard.png)

---

## Installation

### 1. Clone the Repository
```bash
git clone https://github.com/kimatud/task-manager.git
cd task-manager
```

### 2. Import the Database
- Open **phpMyAdmin**.
- Create a database (e.g., `task_manager`).
- Import the `task_manager.sql` file from the repo.

### 3. Configure Database Connection
Edit `config.php`:
```php
<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "task_manager";
$conn = mysqli_connect($host, $user, $pass, $db);
?>
```

### 4. Run the Project
- Place the project in your XAMPP `htdocs` folder.
- Start Apache and MySQL from XAMPP.
- Visit `http://localhost/task-manager` in your browser.

---

## Usage

- **Admin Login** → Manage users and tasks.  
- **User Login** → View and update tasks.  

Default admin credentials (change after setup):
```
Username: admin
Password: password
```

---

## Technologies Used

- **PHP** (Core backend logic)
- **MySQL** (Database)
- **HTML5 / CSS3 / Bootstrap** (Frontend)
- **JavaScript** (Interactivity)

---

## Author

Created by [Dennis Kimatu](https://github.com/kimatud) — feel free to fork, improve, and share.

---

## License

This project is licensed under the MIT License — see the [LICENSE](LICENSE) file for details.
