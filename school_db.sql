-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 02, 2026 at 11:29 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `school_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `scan_date` date NOT NULL,
  `scan_time` time NOT NULL,
  `status` enum('on_time','late') DEFAULT 'on_time',
  `date_added` datetime DEFAULT current_timestamp(),
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `student_id`, `scan_date`, `scan_time`, `status`, `date_added`, `created_at`) VALUES
(5, 42, '2026-05-02', '14:43:10', 'late', '2026-05-02 14:43:10', '2026-05-02 14:43:10'),
(6, 40, '2026-05-02', '14:44:09', 'late', '2026-05-02 14:44:09', '2026-05-02 14:44:09'),
(7, 43, '2026-05-02', '16:03:57', '', '2026-05-02 16:03:57', '2026-05-02 16:03:57');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `sender` varchar(100) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','admin','parent') NOT NULL,
  `parent_email` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `name`, `email`, `password`, `role`, `parent_email`) VALUES
(3, 'Russel Lupian', 'russellupian37@gmail.com', '$2y$10$4UAoRWnvkLO0lWUvENlS6eWPan8NsYIqygZdh0vyoXy2v/srhl/be', 'student', NULL),
(4, 'Denver Bentulan', 'denverbentulan87@gmail.com', '$2y$10$ME5S6fxzSr1y3tMAA65Cru3JCOQEI9fA8uIxDQ9JspugbqdF1eKiG', 'student', NULL),
(5, 'Van Kim Arda', 'ardavankim69@gmail.com', '$2y$10$QJaxNT1FAR9jBOATASpqYOMU1w8/xf3RSBWn7N/vySKpBfSSnqca.', 'student', NULL),
(6, 'Clark Nam-Ay', 'clarkkyy27@gmail.com', '$2y$10$5MfyKV1i2ySEkAI0i9yqg.JjWxI0UPTs2aO9.NM0TJsBPGaNwYP4u', 'student', NULL),
(7, 'Lito Lapid', 'litolapid@gmail.com', '$2y$10$8oXaTHXCNzCa5mr1wnnmF.imC/OZFD/AATspxodkH7fsrTssY.oke', 'student', NULL),
(8, 'Juan Dela Cruz', 'juandelacruz@gmail.com', '$2y$10$ovYYGaEU7vkc9fRqIh.mm.uAP0IojpV6PFMc1xGuVabk70k6YvV/y', 'student', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role` enum('admin','teacher','student','parent') NOT NULL DEFAULT 'student',
  `password` varchar(255) NOT NULL,
  `date_added` datetime DEFAULT current_timestamp(),
  `parent_email` varchar(255) DEFAULT NULL,
  `qr_code` varchar(500) DEFAULT NULL,
  `qr_token` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `role`, `password`, `date_added`, `parent_email`, `qr_code`, `qr_token`) VALUES
(1, 'Denver Bentulan', 'denverbentulan87@gmail.com', 'admin', '$2y$10$YG.eXrTA4TZlH3jEmt73Xe0jrCFP2hasnjPlL3fMvppm4Epb.HDla', '2026-04-13 18:20:27', NULL, NULL, NULL),
(35, 'John Denver Bentulan', 'johndenverbentulan@gmail.com', 'parent', '$2y$10$306wnr4OUr3w4oGqhROuOu2zBEaVRlWL5zKRORkBc4xKpSPCfykSa', '2026-05-02 11:52:57', '', NULL, NULL),
(40, 'Nicole Torifiel', 'torrefielnicole90@gmail.com', 'student', '$2y$10$YSOfLIrfldxhcEti4Ew7.Oq86i0lbVVMiZiI8sBKSwfazGpBvfV5q', '2026-05-02 13:12:23', 'johndenverbentulan@gmail.com', 'qrcodes/qr_d50f6ee5110d202b4c86f36fc2a990ee.png', 'd50f6ee5110d202b4c86f36fc2a990ee'),
(42, 'Alvin Mata', 'nicoletorrefiel909@gmail.com', 'student', '$2y$10$YBDfDYTzG7kxh2th0a4b0OailS5HBpNaR2t2MXukLuXO730NJjqcG', '2026-05-02 14:04:20', 'torrefielnicole90@gmail.com', 'qrcodes/qr_efb2ca8765b6d701bc222e727fdde842.png', 'efb2ca8765b6d701bc222e727fdde842'),
(43, 'Joe Estose', 'sansicas776@gmail.com', 'student', '$2y$10$0gMSkgjvcEsLM27I73ijde6YE.55Xcv9hLYVsahwevcHvi5qSmQwG', '2026-05-02 16:03:23', 'torrefielnicole90@gmail.com', 'qrcodes/qr_415a6e68624d6ddf928869791db8913b.png', '415a6e68624d6ddf928869791db8913b');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`student_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `parent_email` (`parent_email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `user` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user`
--
ALTER TABLE `user`
  ADD CONSTRAINT `user_ibfk_1` FOREIGN KEY (`parent_email`) REFERENCES `user` (`email`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
