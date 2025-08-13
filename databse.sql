--
-- Database: `task_manager`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

-- Drop table if it exists to ensure a clean slate for re-import
DROP TABLE IF EXISTS `tasks`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL UNIQUE,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

-- The password for 'admin' should be hashed for 'password'.
-- Use generate_hash.php to get the correct hash for your environment.
-- Replace 'YOUR_NEWLY_GENERATED_HASH_HERE' with the output from generate_hash.php
INSERT INTO `users` (`username`, `password`, `email`, `role`) VALUES
('admin', '$2y$10$vnYzkQWdazxoiahNUCsnWOR91QYo5fX/p3b/byVQYwnC34sduMV1W', 'admin@example.com', 'admin');


-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text,
  `assigned_to_user_id` int(11) NOT NULL,
  `deadline` date NOT NULL,
  `status` enum('Pending','In Progress','Completed') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `form_path` varchar(255) DEFAULT NULL, -- Admin uploaded form path
  `completed_assignment_path` varchar(255) DEFAULT NULL, -- New column for user-uploaded completed assignment
  PRIMARY KEY (`id`),
  FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

