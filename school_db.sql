-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 14, 2026 at 04:54 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

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
  `status` enum('on_time','present','late','absent') NOT NULL DEFAULT 'on_time',
  `date_added` datetime DEFAULT current_timestamp(),
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `student_id`, `scan_date`, `scan_time`, `status`, `date_added`, `created_at`) VALUES
(20, 56, '2026-05-10', '10:36:43', 'late', '2026-05-10 10:36:43', '2026-05-10 10:36:43'),
(33, 56, '2026-05-13', '17:00:00', 'absent', '2026-05-13 22:29:15', '2026-05-13 22:29:15'),
(34, 81, '2026-05-13', '17:00:00', 'absent', '2026-05-13 22:29:19', '2026-05-13 22:29:19'),
(35, 81, '2026-05-14', '08:00:38', 'present', '2026-05-14 08:00:38', '2026-05-14 08:00:38'),
(36, 56, '2026-05-14', '09:04:21', 'late', '2026-05-14 09:04:21', '2026-05-14 09:04:21'),
(37, 82, '2026-05-14', '10:31:01', 'late', '2026-05-14 10:31:01', '2026-05-14 10:31:01');

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

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `student_id`, `message`, `created_at`, `sender`, `is_read`) VALUES
(18, 56, 'Your child Kimmy Arda was marked LATE at 10:36 AM.', '2026-05-10 10:36:43', 'system', 1),
(31, 56, 'Your child Kimmy Arda was marked ABSENT on May 13, 2026. No QR scan was recorded.', '2026-05-13 22:29:19', 'system', 0),
(32, 81, 'Your child Denver Bentulan was marked ABSENT on May 13, 2026. No QR scan was recorded.', '2026-05-13 22:29:23', 'system', 0),
(33, 81, 'Your child Denver Bentulan was marked PRESENT at 08:00 AM.', '2026-05-14 08:00:38', 'system', 0),
(34, 56, 'Your child Kimmy Arda was marked LATE at 09:04 AM.', '2026-05-14 09:04:21', 'system', 0),
(35, 82, 'Your child Russel Lupian was marked LATE at 10:31 AM.', '2026-05-14 10:31:01', 'system', 0);

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
(1, 'Denver Bentulan', 'denverbentulan87@gmail.com', 'admin', '$2y$10$seOtld2KXpKZaZImlNVHg.dFYzc4udXRqbv8bv.y.b1ys711a.a7y', '2026-04-13 18:20:27', NULL, NULL, NULL),
(35, 'John Denver Bentulan', 'johndenverbentulan@gmail.com', 'parent', '$2y$10$306wnr4OUr3w4oGqhROuOu2zBEaVRlWL5zKRORkBc4xKpSPCfykSa', '2026-05-02 11:52:57', '', NULL, NULL),
(56, 'Kimmy Arda', 'torrefielnicole90@gmail.com', 'student', '$2y$10$gqffDkzX9JAsVZ1lyEKZPu16iuOvLAIExprQovAUDvJ1Oh.DAPOpe', '2026-05-07 10:54:53', 'johndenverbentulan@gmail.com', 'qrcodes/qr_7c95029080450b9e5813d5f13a16f225.png', '7c95029080450b9e5813d5f13a16f225'),
(81, 'Denver Bentulan', 'ardavankim8@gmail.com', 'student', '$2y$10$BTTX.4b3srK2yXCidhYsiOTadexavgFBsBG8aMUFVDUXGrvFRoldW', '2026-05-13 21:54:18', 'johndenverbentulan@gmail.com', 'qrcodes/qr_00cf6490e77fa3b1f281116653b818e3.png', '00cf6490e77fa3b1f281116653b818e3'),
(82, 'Russel Lupian', 'lupianrussel@gmail.com', 'student', '$2y$10$8kS7UtVjyTEyYwzXfA62mOPh5WfikJ8j.1We1Y4aPDgdvVV.blnAO', '2026-05-14 10:26:26', 'johndenverbentulan@gmail.com', 'qrcodes/qr_4459d4db3ca0881785b0639c005c1d5f.png', '4459d4db3ca0881785b0639c005c1d5f');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

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
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user`
--
ALTER TABLE `user`
  ADD CONSTRAINT `user_ibfk_1` FOREIGN KEY (`parent_email`) REFERENCES `user` (`email`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
