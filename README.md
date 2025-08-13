# 📋 Task Management System (PHP & MySQL)

[![Made with PHP](https://img.shields.io/badge/Made%20with-PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![Database MySQL](https://img.shields.io/badge/Database-MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Frontend Bootstrap](https://img.shields.io/badge/Frontend-Bootstrap-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![License MIT](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)](LICENSE)

---

A lightweight yet powerful **task management system** that helps teams and individuals organize tasks, manage deadlines, and track progress — all in one place. Built with **PHP**, **MySQL**, and **Bootstrap**, it is simple to install and easy to customize.

---

## 📖 About This Project

This Task Management System was designed to make project coordination seamless for small teams, organizations, or individuals.  
It provides:
- **Admins** with full control over user management and task assignments.
- **Users** with a clear view of their responsibilities and deadlines.

Key goals:
- **Simplicity** — quick setup and easy navigation.
- **Performance** — lightweight, works even on local servers like XAMPP.
- **Flexibility** — customizable features and UI.

---

## ✨ Features

- **Admin Dashboard**
  - Add, edit, or delete users.
  - Assign tasks with deadlines.
  - Manage task status: Pending, In Progress, Completed.

- **User Dashboard**
  - View tasks assigned to you.
  - Update task statuses.
  - View deadlines and progress.

- **Secure Authentication**
  - Separate admin and user logins.

---

## 🖼️ Screenshots

### 1. Login Page
![Login Page](screenshots/login%20page.png)

### 2. Admin Panel
![Admin Panel](screenshots/admin%20panel.png)

### 3. Manage Tasks Panel
![Manage Tasks Panel](screenshots/manage%20tasks%20panel.png)

### 4. User Dashboard
![User Dashboard](screenshots/user%20dashboard.png)

---

## 🚀 Installation

### 1️⃣ Clone the Repository
```bash
git clone https://github.com/kimatud/task-manager.git
cd task-manager
```

### 2️⃣ Import the Database
- Open **phpMyAdmin**.
- Create a database (e.g., `task_manager`).
- Import the `task_manager.sql` file from the repo.

### 3️⃣ Configure Database Connection
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

### 4️⃣ Run the Project
- Place the project in your XAMPP `htdocs` folder.
- Start Apache and MySQL from XAMPP.
- Visit `http://localhost/task-manager` in your browser.

---

## 🔑 Usage

- **Admin Login** → Manage users and tasks.  
- **User Login** → View and update tasks.  

Default admin credentials (change after setup):
```
Username: admin
Password: password
```

---

## 🛠 Technologies Used

- **PHP** (Core backend logic)
- **MySQL** (Database)
- **HTML5 / CSS3 / Bootstrap** (Frontend)
- **JavaScript** (Interactivity)

---

## 👨‍💻 Author

Created by [Dennis Kimatu](https://github.com/kimatud) — feel free to fork, improve, and share.

---

## 📜 License

This project is licensed under the MIT License — see the [LICENSE](LICENSE) file for details.
