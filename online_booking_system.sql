-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 09, 2026 at 12:39 PM
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
-- Database: `online_booking_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `annoucement`
--

CREATE TABLE `annoucement` (
  `annoucement_id` int(11) NOT NULL COMMENT 'Announcement Identification',
  `admin_id` int(11) NOT NULL COMMENT 'Admin Identification',
  `title` varchar(255) NOT NULL COMMENT 'Announcement Title',
  `content` text NOT NULL COMMENT 'Announcement Detils',
  `publish_date` date NOT NULL DEFAULT current_timestamp() COMMENT 'Date Published'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `annoucement`
--

INSERT INTO `annoucement` (`annoucement_id`, `admin_id`, `title`, `content`, `publish_date`) VALUES
(10, 10000004, 'FCI CQAR2002 Under Maintenance ', 'The projector and the laboratory computers will be having an update. As for now, the laboratory remains unavailable for a short time.', '2026-05-01'),
(14, 10000004, 'I AM UNDER MAINTENCENCE', 'so like hello guys, today i am under maintence and i lowk probably need your help or someone help lol', '2026-06-09');

-- --------------------------------------------------------

--
-- Table structure for table `booking`
--

CREATE TABLE `booking` (
  `booking_id` int(11) NOT NULL COMMENT 'Booking Identification',
  `user_id` int(11) NOT NULL COMMENT 'User Who Made Booking',
  `facility_id` int(11) NOT NULL COMMENT 'Booked Facility Identification',
  `booking_date` date NOT NULL COMMENT 'Date of Booking',
  `start_time` time NOT NULL COMMENT 'Booking Start Time',
  `end_time` time NOT NULL COMMENT 'Booking End Time',
  `purpose` text DEFAULT NULL COMMENT 'Purpose of Booking',
  `status` varchar(50) NOT NULL COMMENT 'Booking Status',
  `is_priority` tinyint(1) DEFAULT 0 COMMENT 'Priority Status',
  `proof_file` varchar(255) DEFAULT NULL COMMENT 'The file for admins to review the lecturers MEMO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking`
--

INSERT INTO `booking` (`booking_id`, `user_id`, `facility_id`, `booking_date`, `start_time`, `end_time`, `purpose`, `status`, `is_priority`, `proof_file`) VALUES
(1018, 10000000, 101, '2026-06-09', '18:03:00', '19:03:00', 'a', 'Approved', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `booking_equipment`
--

CREATE TABLE `booking_equipment` (
  `booking_id` int(11) NOT NULL COMMENT 'Booking Identification',
  `equip_id` int(11) NOT NULL COMMENT 'Equipment Identification',
  `quantity` int(11) NOT NULL COMMENT 'Quantity Requested'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

CREATE TABLE `equipment` (
  `equip_id` int(11) NOT NULL COMMENT 'Equipment Identification',
  `name` varchar(255) NOT NULL COMMENT 'Equipment Name',
  `total_qty` int(11) NOT NULL COMMENT 'Total Quantity Owned',
  `avail_qty` int(11) NOT NULL COMMENT 'Currently Available Qty',
  `status` varchar(50) NOT NULL COMMENT 'Equipment Status',
  `category` varchar(50) DEFAULT 'General' COMMENT 'Categorizing Equipments'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `equipment`
--

INSERT INTO `equipment` (`equip_id`, `name`, `total_qty`, `avail_qty`, `status`, `category`) VALUES
(1000, 'Badminton Racket', 10, 7, 'Available', 'Sport'),
(1001, 'Ping Pong Racket', 10, 10, 'Available', 'Sport'),
(1003, 'Badminton Net', 4, 2, 'Available', 'Laboratory'),
(1005, 'Ping Pong Net', 4, 2, 'Available', 'Lecture Hall');

-- --------------------------------------------------------

--
-- Table structure for table `facility`
--

CREATE TABLE `facility` (
  `facility_id` int(11) NOT NULL COMMENT 'Facility Identification ',
  `facility_name` varchar(255) NOT NULL COMMENT 'Facility Name ',
  `location` varchar(255) NOT NULL COMMENT 'Facility Location',
  `category` varchar(100) NOT NULL COMMENT 'Type Of Facility',
  `capacity` int(11) NOT NULL COMMENT 'Maximum Numbers of Users Allowed',
  `description` text DEFAULT NULL COMMENT 'Description of the Facilities',
  `status` varchar(50) NOT NULL COMMENT 'Current Facility Status',
  `image_path` varchar(255) DEFAULT NULL COMMENT 'The path of the image within img/facilities file'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `facility`
--

INSERT INTO `facility` (`facility_id`, `facility_name`, `location`, `category`, `capacity`, `description`, `status`, `image_path`) VALUES
(100, 'Indoor Court', 'MMU Cyberjaya', 'Sport', 50, 'an indoor court lol', 'Available', 'LAB.jpg'),
(101, 'Outdoor Court', 'MMU Cyberjaya', 'Sport', 100, 'an outdoor court?', 'Available', 'LAB.jpg'),
(102, 'Outdoor Swimming Pool', 'MMU Cyberjaya', 'Sport', 20, 'swimming pool with bodies!', 'Available', 'LAB.jpg'),
(103, 'MPH CNMX1001', 'MMU Cyberjaya', 'Lecture Hall', 80, 'a lecture hall...', 'Available', 'LAB.jpg'),
(104, 'MPH CNMX1002', 'MMU Cyberjaya', 'Lecture Hall', 80, 'a boring lecture hall', 'Available', NULL),
(105, 'MPH CNMX1003', 'MMU Cyberjaya', 'Lecture Hall', 80, 'wow a lecture hall', 'Available', NULL),
(106, 'MPH CNMX1004', 'MMU Cyberjaya', 'Lecture Hall', 80, 'this lecture hall sucks', 'Available', NULL),
(107, 'FCI CQAR2001', 'MMU Melaka', 'Laboratory', 40, 'a lab?', 'Available', NULL),
(108, 'FCI CQAR2002', 'MMU Melaka', 'Laboratory', 40, 'a broken lab with broken dreams', 'Unavailable ', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `issue report`
--

CREATE TABLE `issue report` (
  `report_id` int(11) NOT NULL COMMENT 'Report Identification',
  `user_id` int(11) NOT NULL COMMENT 'User Who Submitted Report Identification',
  `facility_id` int(11) NOT NULL COMMENT 'Reported Facility Identification',
  `issue_type` varchar(100) NOT NULL COMMENT 'Category of Issue Reported',
  `description` text NOT NULL COMMENT 'Detailed Issue Description',
  `report_date` date NOT NULL COMMENT 'Date of Report Submission',
  `status` varchar(50) NOT NULL COMMENT 'Report Status'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `issue report`
--

INSERT INTO `issue report` (`report_id`, `user_id`, `facility_id`, `issue_type`, `description`, `report_date`, `status`) VALUES
(1, 10000001, 102, 'Swimming Pool Odor', 'The swimming pool had a terrible odor.', '2026-05-01', 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `notification`
--

CREATE TABLE `notification` (
  `notify_id` int(11) NOT NULL COMMENT 'Notification Identification',
  `user_id` int(11) NOT NULL COMMENT 'Recipient User Identification',
  `title` varchar(255) NOT NULL COMMENT 'Short Notification Title',
  `message` text NOT NULL COMMENT 'Full Notification Content',
  `is_read` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Read Status (Y/N)',
  `date_sent` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Date and Time Sent'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `penalty`
--

CREATE TABLE `penalty` (
  `penalty_id` int(11) NOT NULL COMMENT 'Penalty Identification',
  `user_id` int(11) NOT NULL COMMENT 'Penalised User Identification',
  `booking_id` int(11) NOT NULL COMMENT 'Related Booking That Caused the Penalty',
  `reason` text NOT NULL COMMENT 'Reason for Penalty',
  `strike_count` int(11) NOT NULL COMMENT 'Total Number of Strikes',
  `status` varchar(50) NOT NULL COMMENT 'Penalty Status'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_id` int(11) NOT NULL COMMENT 'User Identification',
  `name` varchar(255) NOT NULL COMMENT 'User Full Name',
  `email` varchar(255) NOT NULL COMMENT 'User Login Email Address',
  `password` varchar(255) DEFAULT NULL COMMENT 'Encrypted User Password',
  `role` varchar(50) NOT NULL COMMENT 'User Role',
  `contact_no` varchar(50) NOT NULL COMMENT 'User Contact Number',
  `booking_quota` int(11) NOT NULL COMMENT 'Booking Quota Limit',
  `otp_code` varchar(6) DEFAULT NULL COMMENT 'Verification Code',
  `otp_sent_at` datetime DEFAULT NULL COMMENT 'Expiration OTP time',
  `is_activated` tinyint(1) DEFAULT 0 COMMENT 'Account Activation Status'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `name`, `email`, `password`, `role`, `contact_no`, `booking_quota`, `otp_code`, `otp_sent_at`, `is_activated`) VALUES
(10000000, 'IDRIS BIN SHAH NAHAR', 'idris@student.mmu.edu.my', 'Stophe_3', 'Student', '01164425881', 2, NULL, NULL, 1),
(10000001, 'ALEESYA ZAARA BINTI EDI ZULKARNAIN\r\n\r\n', 'aleesya@student.mmu.edu.my', 'abcd1234', 'Student', '01154067900', 2, NULL, NULL, 1),
(10000002, 'SURIYA', 'suriya@student.mmu.edu.my', 'Stophe_3', 'Student', '0123211695', 2, NULL, NULL, 1),
(10000003, 'NOR IDAYU BINTI AHMAD AZAMI', 'idayu.azami@mmu.edu.my', 'abcd1234', 'Lecturer', '0112223333', 2, NULL, NULL, 1),
(10000004, 'MR ADMIN', 'admin1@mmu.edu.my', NULL, 'Admin', '0112223333', 2, NULL, NULL, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `annoucement`
--
ALTER TABLE `annoucement`
  ADD PRIMARY KEY (`annoucement_id`);

--
-- Indexes for table `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `facility_id` (`facility_id`),
  ADD KEY `user_id` (`user_id`) USING BTREE;

--
-- Indexes for table `booking_equipment`
--
ALTER TABLE `booking_equipment`
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `equip_id` (`equip_id`);

--
-- Indexes for table `equipment`
--
ALTER TABLE `equipment`
  ADD PRIMARY KEY (`equip_id`);

--
-- Indexes for table `facility`
--
ALTER TABLE `facility`
  ADD PRIMARY KEY (`facility_id`);

--
-- Indexes for table `issue report`
--
ALTER TABLE `issue report`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `facility_id` (`facility_id`);

--
-- Indexes for table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`notify_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `penalty`
--
ALTER TABLE `penalty`
  ADD PRIMARY KEY (`penalty_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `annoucement`
--
ALTER TABLE `annoucement`
  MODIFY `annoucement_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Announcement Identification', AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Booking Identification', AUTO_INCREMENT=1026;

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `equip_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Equipment Identification', AUTO_INCREMENT=1006;

--
-- AUTO_INCREMENT for table `facility`
--
ALTER TABLE `facility`
  MODIFY `facility_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Facility Identification ', AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT for table `issue report`
--
ALTER TABLE `issue report`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Report Identification', AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notification`
--
ALTER TABLE `notification`
  MODIFY `notify_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Notification Identification';

--
-- AUTO_INCREMENT for table `penalty`
--
ALTER TABLE `penalty`
  MODIFY `penalty_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Penalty Identification', AUTO_INCREMENT=100001;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'User Identification', AUTO_INCREMENT=10000005;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `annoucement`
--
ALTER TABLE `annoucement`
  ADD CONSTRAINT `fk_annoucement_user` FOREIGN KEY (`admin_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `booking`
--
ALTER TABLE `booking`
  ADD CONSTRAINT `fk_booking_facility` FOREIGN KEY (`facility_id`) REFERENCES `facility` (`facility_id`),
  ADD CONSTRAINT `fk_booking_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `booking_equipment`
--
ALTER TABLE `booking_equipment`
  ADD CONSTRAINT `fk_book_equip_booking` FOREIGN KEY (`booking_id`) REFERENCES `booking` (`booking_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_book_equip_equipment` FOREIGN KEY (`equip_id`) REFERENCES `equipment` (`equip_id`);

--
-- Constraints for table `issue report`
--
ALTER TABLE `issue report`
  ADD CONSTRAINT `fk_issue_facility` FOREIGN KEY (`facility_id`) REFERENCES `facility` (`facility_id`),
  ADD CONSTRAINT `fk_issue_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `penalty`
--
ALTER TABLE `penalty`
  ADD CONSTRAINT `fk_penalty_booking` FOREIGN KEY (`booking_id`) REFERENCES `booking` (`booking_id`),
  ADD CONSTRAINT `fk_penalty_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
