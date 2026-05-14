-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 12, 2026 at 11:37 AM
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
  `status` varchar(50) NOT NULL COMMENT 'Booking Status'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `status` varchar(50) NOT NULL COMMENT 'Equipment Status'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `status` varchar(50) NOT NULL COMMENT 'Current Facility Status'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `password` varchar(255) NOT NULL COMMENT 'Encrypted User Password',
  `role` varchar(50) NOT NULL COMMENT 'User Role',
  `contact_no` varchar(50) NOT NULL COMMENT 'User Contact Number',
  `booking_quota` int(11) NOT NULL COMMENT 'Booking Quota Limit'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `name`, `email`, `password`, `role`, `contact_no`, `booking_quota`) VALUES
(10000000, 'IDRIS BIN SHAH NAHAR', 'IDRIS.SHAH.NAHAR@student.mmu.edu.my', 'Stophe_3', 'Student', '01164425881', 5);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `annoucement`
--
ALTER TABLE `annoucement`
  ADD PRIMARY KEY (`annoucement_id`),
  ADD UNIQUE KEY `user_id` (`admin_id`);

--
-- Indexes for table `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `facility_id` (`facility_id`);

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
  MODIFY `annoucement_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Announcement Identification';

--
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Booking Identification';

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `equip_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Equipment Identification';

--
-- AUTO_INCREMENT for table `facility`
--
ALTER TABLE `facility`
  MODIFY `facility_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Facility Identification ';

--
-- AUTO_INCREMENT for table `issue report`
--
ALTER TABLE `issue report`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Report Identification';

--
-- AUTO_INCREMENT for table `notification`
--
ALTER TABLE `notification`
  MODIFY `notify_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Notification Identification';

--
-- AUTO_INCREMENT for table `penalty`
--
ALTER TABLE `penalty`
  MODIFY `penalty_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Penalty Identification';

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'User Identification', AUTO_INCREMENT=10000001;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
