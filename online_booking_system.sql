-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 05, 2026 at 04:02 AM
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
  `category` varchar(50) DEFAULT 'Update',
  `publish_date` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Date Published',
  `status` varchar(20) DEFAULT 'Live' COMMENT 'Status of Announcement'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `annoucement`
--

INSERT INTO `annoucement` (`annoucement_id`, `admin_id`, `title`, `content`, `category`, `publish_date`, `status`) VALUES
(18, 10000004, 'lol', 'hehe', 'Update', '2026-07-02 16:55:52', 'Live');

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
(1069, 10000000, 187, '2026-07-10', '08:57:00', '09:57:00', 'lo', 'Cancelled', 0, NULL),
(1070, 10000003, 187, '2026-07-10', '15:58:00', '16:58:00', 'l', 'Cancelled', 1, ''),
(1071, 10000000, 187, '2026-07-10', '13:56:00', '14:56:00', 'a', 'Cancelled', 0, NULL),
(1072, 10000003, 187, '2026-07-10', '13:59:00', '14:59:00', 'a', 'Cancelled', 1, 'proof_1783144775_10000003.png'),
(1075, 10000003, 163, '2026-07-10', '14:06:00', '15:06:00', 'b', 'Cancelled', 1, '');

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
  `category` varchar(50) DEFAULT 'General' COMMENT 'Categorizing Equipments',
  `campus` varchar(50) DEFAULT 'Cyberjaya' COMMENT 'MMU Campus'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `equipment`
--

INSERT INTO `equipment` (`equip_id`, `name`, `total_qty`, `avail_qty`, `status`, `category`, `campus`) VALUES
(1000, 'Badminton Racket', 10, 9, 'Available', 'Sports', 'Cyberjaya'),
(1001, 'Ping Pong Racket', 10, 9, 'Available', 'Sports', 'Cyberjaya'),
(1003, 'Badminton Net', 4, 4, 'Available', 'Laboratory', 'Cyberjaya'),
(1005, 'Ping Pong Net', 4, 4, 'Available', 'Lecture Hall', 'Cyberjaya');

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
  `image_path` varchar(255) DEFAULT NULL COMMENT 'The path of the image within img/facilities file',
  `faculty` varchar(255) NOT NULL COMMENT 'Type of Faculty'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `facility`
--

INSERT INTO `facility` (`facility_id`, `facility_name`, `location`, `category`, `capacity`, `description`, `status`, `image_path`, `faculty`) VALUES
(161, 'Laboratory 1', 'MMU Cyberjaya', 'Labaratory', 40, 'The FCI Lab is a computer laboratory used by students from the Faculty of Computing and Informatics for practical classes, programming, software development, and other computer-based learning activities.', 'Available', 'fac_1783067247_794.webp', 'FCI'),
(162, 'CQMX0001', 'MMU Cyberjaya', 'Lecture Hall', 100, 'A spacious lecture hall designed for teaching large groups of students. It is equipped with a projector, audio system, comfortable seating, air conditioning, and other essential teaching facilities to support lectures, presentations, and seminars.', 'Available', 'fac_1783067373_723.webp', 'FCI'),
(163, 'Tutorial 1', 'MMU Cyberjaya', 'Tutorial', 40, 'A tutorial classroom designed for small-group learning, discussions, and interactive teaching sessions. It provides a comfortable environment for tutorials, group activities, and academic consultations.', 'Available', 'fac_1783067464_207.webp', 'FCI'),
(164, 'Tutorial 1', 'MMU Cyberjaya', 'Tutorial', 40, 'A classroom used for tutorial sessions where students can participate in discussions, complete group activities, and receive guidance from lecturers in a more interactive learning environment.', 'Available', 'fac_1783072457_583.webp', 'FOM'),
(165, 'FOM Theatre', 'MMU Cyberjaya', 'Lecture Hall', 100, 'A large classroom designed to accommodate a high number of students for lectures and presentations. The hall includes multimedia equipment, seating, and air conditioning to ensure an effective learning experience.', 'Available', 'fac_1783072533_218.webp', 'FOM'),
(166, 'Laboratory 1', 'MMU Cyberjaya', 'Labaratory', 40, 'A specialized laboratory that supports practical learning for management students through business simulations, computer-based activities, data analysis, and collaborative learning.', 'Available', 'fac_1783072687_574.webp', 'FOM'),
(167, 'Lecture Hall 1', 'MMU Cyberjaya', 'Lecture Hall', 100, 'A large classroom designed to accommodate a high number of students for lectures and presentations. The hall includes multimedia equipment, seating, and air conditioning to ensure an effective learning experience.', 'Available', 'fac_1783072920_990.jpg', 'FCM'),
(168, 'Laboratory 1', 'MMU Cyberjaya', 'Labaratory', 40, 'A creative multimedia laboratory equipped with industry-standard computers and software for digital design, animation, video editing, 3D modeling, game development, and other multimedia production activities.', 'Available', 'fac_1783072990_847.jpg', 'FCM'),
(169, 'Tutorial 1', 'MMU Cyberjaya', 'Tutorial', 80, 'A classroom for tutorial sessions, group discussions, and interactive learning activities between students and lecturers.', 'Available', 'fac_1783073138_792.jpg', 'FCM'),
(170, 'Lecture Hall 1', 'MMU Cyberjaya', 'Lecture Hall', 100, 'A large classroom designed to accommodate a high number of students for lectures and presentations. The hall includes multimedia equipment, seating, and air conditioning to ensure an effective learning experience.', 'Available', 'fac_1783073853_456.jpg', 'FOE'),
(171, 'Tutorial 1', 'MMU Cyberjaya', 'Tutorial', 40, 'A classroom used for tutorial sessions where students can participate in discussions, complete group activities, and receive guidance from lecturers in a more interactive learning environment.', 'Available', 'fac_1783073899_447.webp', 'FOE'),
(172, 'Lecture Hall 1', 'MMU Cyberjaya', 'Lecture Hall', 100, 'A lecture hall used for teaching film, television, and cinematic arts courses. It is equipped with multimedia presentation facilities to support lectures, seminars, and academic presentations.', 'Available', 'fac_1783074573_173.jpg', 'FCA'),
(173, 'Tutorial 1', 'MMU Cyberjaya', 'Tutorial', 40, 'A classroom designed for small-group discussions, workshops, script analysis, and interactive learning activities that encourage collaboration between students and lecturers.', 'Available', 'fac_1783074633_521.webp', 'FCA'),
(174, 'Laboratory 1', 'MMU Cyberjaya', 'Labaratory', 40, 'A specialized laboratory equipped with professional hardware and software for filmmaking, video editing, audio production, and other practical cinematic arts activities.', 'Available', 'fac_1783074792_573.jpg', 'FCA'),
(175, 'Lecture Hall 1', 'MMU Cyberjaya', 'Lecture Hall', 100, 'A lecture hall designed for communication, media, and public relations courses. It provides a comfortable learning environment with multimedia facilities for lectures, presentations, and seminars.', 'Available', 'fac_1783075051_347.webp', 'FAC'),
(176, 'Laboratory 1', 'MMU Cyberjaya', 'Labaratory', 40, 'A specialized laboratory equipped with multimedia technology and communication software to support practical learning in broadcasting, media production, digital communication, and related activities.', 'Available', 'fac_1783075190_316.jpg', 'FAC'),
(177, 'Tutorial 1', 'MMU Cyberjaya', 'Tutorial', 40, 'A classroom used for tutorials, discussions, group activities, and presentations, allowing students to develop communication skills through interactive learning.', 'Available', 'fac_1783075233_253.webp', 'FAC'),
(178, 'Tutorial 1', 'MMU Cyberjaya', 'Tutorial', 40, 'A classroom used for tutorials, discussions, group activities, and presentations, allowing students to develop communication skills through interactive learning.', 'Available', 'fac_1783075233_191.webp', 'FAC'),
(179, 'CNMX1001', 'MMU Cyberjaya', 'Lecture Hall', 100, 'A spacious lecture hall designed for teaching large groups of students. It is equipped with a projector, audio system, comfortable seating, air conditioning, and other essential teaching facilities to support lectures, presentations, and seminars.', 'Available', 'fac_1783077056_886.jpg', 'General'),
(180, 'CNMX1002', 'MMU Cyberjaya', 'Lecture Hall', 100, 'A spacious lecture hall designed for teaching large groups of students. It is equipped with a projector, audio system, comfortable seating, air conditioning, and other essential teaching facilities to support lectures, presentations, and seminars.', 'Available', 'fac_1783077079_349.jpg', 'General'),
(181, 'CNMX1003', 'MMU Cyberjaya', 'Lecture Hall', 100, 'A spacious lecture hall designed for teaching large groups of students. It is equipped with a projector, audio system, comfortable seating, air conditioning, and other essential teaching facilities to support lectures, presentations, and seminars.', 'Available', 'fac_1783077097_655.jpg', 'General'),
(182, 'CNMX1004', 'MMU Cyberjaya', 'Lecture Hall', 100, 'A spacious lecture hall designed for teaching large groups of students. It is equipped with a projector, audio system, comfortable seating, air conditioning, and other essential teaching facilities to support lectures, presentations, and seminars.', 'Available', 'fac_1783077114_446.jpg', 'General'),
(183, 'CNMX1005', 'MMU Cyberjaya', 'Lecture Hall', 100, 'A spacious lecture hall designed for teaching large groups of students. It is equipped with a projector, audio system, comfortable seating, air conditioning, and other essential teaching facilities to support lectures, presentations, and seminars.', 'Available', 'fac_1783077130_174.jpg', 'General'),
(184, 'Main Purpose Hall', 'MMU Cyberjaya', 'Lecture Hall', 500, 'A spacious multipurpose hall designed to accommodate large gatherings, including university events, seminars, workshops, examinations, exhibitions, and student activities. The hall is equipped with audiovisual facilities, a stage, and flexible seating arrangements to support various functions.', 'Available', 'fac_1783077310_864.jpg', 'General'),
(185, 'Swimming Pool', 'MMU Cyberjaya', 'Sports', 100, 'A swimming facility designed for recreational swimming, training, and aquatic sports. It provides a safe and well-maintained environment for students and staff to exercise and participate in swimming activities.', 'Available', 'fac_1783077483_766.webp', 'General'),
(186, 'MMU Stadium', 'MMU Cyberjaya', 'Sports', 1000, 'A multi-purpose outdoor stadium used for sports competitions, athletics, football matches, university events, and recreational activities. The stadium provides seating for spectators and supports various sporting and community events.', 'Available', 'fac_1783077553_271.jpg', 'General'),
(187, 'Badminton Court', 'MMU Cyberjaya', 'Sports', 4, 'An indoor badminton court equipped with standard flooring, nets, and lighting to support recreational play, training sessions, and sports competitions.', 'Available', 'fac_1783077601_533.jpg', 'General'),
(188, 'Basketball Court', 'MMU Cyberjaya', 'Sports', 20, 'A basketball court designed for training, recreational games, and university competitions. It features standard basketball hoops, court markings, and sufficient space for team activities.', 'Available', 'fac_1783077640_366.jpg', 'General'),
(189, 'Gym', 'MMU Cyberjaya', 'Sports', 50, 'A fitness facility equipped with a variety of exercise machines, free weights, and fitness equipment to support strength training, cardiovascular workouts, and overall physical wellness for students and staff.', 'Available', 'fac_1783077660_997.jpg', 'General'),
(190, 'Lecture Hall 1', 'MMU Melaka', 'Lecture Hall', 100, 'A lecture hall designed for engineering and technology courses, equipped with multimedia teaching facilities to support lectures, seminars, and academic presentations.', 'Available', 'fac_1783079288_202.jpg', 'FET'),
(191, 'Laboratory 1', 'MMU Melaka', 'Labaratory', 40, 'A specialized engineering laboratory equipped with technical equipment, instruments, and software to support hands-on experiments, research, and practical learning.', 'Available', 'fac_1783079589_332.webp', 'FET'),
(192, 'Laboratory 1', 'MMU Cyberjaya', 'Labaratory', 40, 'A specialized engineering laboratory equipped with modern instruments, technical equipment, and engineering software to support practical experiments, research, project development, and hands-on learning across various engineering disciplines.', 'Available', 'fac_1783079471_266.jpg', 'FOE'),
(194, 'Lecture Hall 1', 'MMU Melaka', 'Lecture Hall', 100, 'A lecture hall used for information technology and computer science courses, providing multimedia teaching facilities for lectures, seminars, and presentations.', 'Available', 'fac_1783079772_391.jpg', 'FIST'),
(195, 'Laboratory 1', 'MMU Melaka', 'Labaratory', 40, 'A computer laboratory equipped with modern computers and specialized software to support programming, networking, cybersecurity, data analytics, and other practical computing activities.', 'Available', 'fac_1783079807_477.webp', 'FIST'),
(196, 'Tutorial 1', 'MMU Melaka', 'Tutorial', 40, 'A classroom designed for tutorials, discussions, programming exercises, and collaborative learning in information technology subjects.', 'Available', 'fac_1783079852_236.webp', 'FIST'),
(199, 'Tutorial 1', 'MMU Melaka', 'Tutorial', 40, 'A classroom for small-group discussions, problem-solving sessions, and practical learning activities that enhance students\' understanding of engineering concepts.', 'Available', 'fac_1783080129_628.webp', 'FET'),
(200, 'Lecture Hall 1', 'MMU Melaka', 'Lecture Hall', 100, 'A lecture hall used for business, accounting, and management courses. It is equipped with multimedia teaching facilities to support lectures, seminars, and academic presentations.', 'Available', 'fac_1783080705_592.jpg', 'FOB'),
(201, 'Laboratory 1', 'MMU Melaka', 'Labaratory', 40, 'A business laboratory equipped with computers and specialized software to support business simulations, financial analysis, accounting applications, and practical learning activities.', 'Available', 'fac_1783080618_607.webp', 'FOB'),
(202, 'Tutorial 1', 'MMU Melaka', 'Tutorial', 40, 'A classroom designed for tutorials, group discussions, case studies, presentations, and collaborative learning activities in business-related subjects.', 'Available', 'fac_1783080676_831.jpg', 'FOB'),
(203, 'Lecture Hall 1', 'MMU Melaka', 'Lecture Hall', 100, 'A lecture hall designed for law lectures, seminars, and academic presentations, equipped with multimedia facilities to support teaching and learning.', 'Available', 'fac_1783080980_414.jpg', 'FOL'),
(204, 'Tutorial 1', 'MMU Melaka', 'Tutorial', 40, 'A classroom used for tutorials, legal discussions, case analysis, and interactive learning sessions that strengthen students\' understanding of legal principles.', 'Available', 'fac_1783081024_250.webp', 'FOL'),
(205, 'Laboratory (Moot Court)', 'MMU Melaka', 'Labaratory', 40, 'A business laboratory equipped with computers and specialized software to support business simulations, financial analysis, accounting applications, and practical learning activities.', 'Available', 'fac_1783081127_883.jpg', 'FOL');

-- --------------------------------------------------------

--
-- Table structure for table `issue_report`
--

CREATE TABLE `issue_report` (
  `report_id` int(11) NOT NULL COMMENT 'Report Identification',
  `user_id` int(11) NOT NULL COMMENT 'User Who Submitted Report Identification',
  `facility_id` int(11) NOT NULL COMMENT 'Reported Facility Identification',
  `issue_type` varchar(100) NOT NULL COMMENT 'Category of Issue Reported',
  `description` text NOT NULL COMMENT 'Detailed Issue Description',
  `report_date` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Report Date',
  `status` varchar(50) NOT NULL COMMENT 'Report Status',
  `admin_reply` text DEFAULT NULL COMMENT 'Admin''s Reply',
  `reply_date` datetime DEFAULT NULL COMMENT 'Admin Reply Date',
  `issue_image` varchar(255) DEFAULT NULL COMMENT 'Image of Issues'
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

--
-- Dumping data for table `notification`
--

INSERT INTO `notification` (`notify_id`, `user_id`, `title`, `message`, `is_read`, `date_sent`) VALUES
(10, 10000000, 'Booking Cancelled: Schedule Update', 'Your booking for Badminton Court on 2026-07-10 has been cancelled because the slot is now reserved for a recurring Academic Class.', 1, '2026-07-04 12:57:49'),
(11, 10000003, 'Booking Cancelled: Schedule Update', 'Your booking for Badminton Court on 2026-07-10 has been cancelled because the slot is now reserved for a recurring Academic Class.', 1, '2026-07-04 12:59:15'),
(12, 10000003, 'Booking Request Approved', 'Your reservation for Badminton Court on 2026-07-10 (1:59 PM) has been officially approved by the Admin.', 1, '2026-07-04 14:00:13'),
(13, 10000000, 'Booking Overridden by Admin', 'Your booking for Badminton Court on 2026-07-10 has been cancelled as the slot was needed for an academic priority session.', 1, '2026-07-04 14:00:13');

-- --------------------------------------------------------

--
-- Table structure for table `penalty`
--

CREATE TABLE `penalty` (
  `penalty_id` int(11) NOT NULL COMMENT 'Penalty Identification',
  `user_id` int(11) NOT NULL COMMENT 'Penalised User Identification',
  `booking_id` int(11) DEFAULT NULL,
  `reason` text NOT NULL COMMENT 'Reason for Penalty',
  `strike_count` int(11) NOT NULL COMMENT 'Total Number of Strikes',
  `status` varchar(50) NOT NULL COMMENT 'Penalty Status'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `penalty`
--

INSERT INTO `penalty` (`penalty_id`, `user_id`, `booking_id`, `reason`, `strike_count`, `status`) VALUES
(100018, 10000000, NULL, '[No-Show] lol', 1, 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `timetable`
--

CREATE TABLE `timetable` (
  `timetable_id` int(11) NOT NULL COMMENT 'Timetable Identification',
  `facility_id` int(11) NOT NULL COMMENT 'Facility Identification',
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL COMMENT 'Days for the weekly schedule',
  `start_time` time NOT NULL COMMENT 'Starting time for class',
  `end_time` time NOT NULL COMMENT 'Ending time for class',
  `expiry_date` date NOT NULL COMMENT 'Expiration date of the timetable',
  `start_date` date DEFAULT NULL COMMENT 'Date the recurring schedule begins from'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timetable`
--

INSERT INTO `timetable` (`timetable_id`, `facility_id`, `day_of_week`, `start_time`, `end_time`, `expiry_date`, `start_date`) VALUES
(42, 187, 'Monday', '08:00:00', '09:00:00', '2026-08-17', '2026-06-29'),
(43, 187, 'Monday', '09:00:00', '10:00:00', '2026-08-17', '2026-06-29'),
(44, 187, 'Tuesday', '10:00:00', '11:00:00', '2026-08-17', '2026-06-29'),
(45, 187, 'Tuesday', '11:00:00', '12:00:00', '2026-08-17', '2026-06-29'),
(46, 187, 'Wednesday', '12:00:00', '13:00:00', '2026-08-17', '2026-06-29'),
(47, 187, 'Wednesday', '13:00:00', '14:00:00', '2026-08-17', '2026-06-29'),
(48, 187, 'Thursday', '14:00:00', '15:00:00', '2026-08-17', '2026-06-29'),
(49, 187, 'Thursday', '15:00:00', '16:00:00', '2026-08-17', '2026-06-29'),
(50, 187, 'Friday', '16:00:00', '17:00:00', '2026-08-17', '2026-06-29'),
(51, 187, 'Friday', '17:00:00', '18:00:00', '2026-08-17', '2026-06-29');

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
  `is_activated` tinyint(1) DEFAULT 0 COMMENT 'Account Activation Status',
  `status` varchar(20) DEFAULT 'Active' COMMENT 'Penalty Status',
  `suspension_start` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `name`, `email`, `password`, `role`, `contact_no`, `booking_quota`, `otp_code`, `otp_sent_at`, `is_activated`, `status`, `suspension_start`) VALUES
(10000000, 'IDRIS BIN SHAH NAHAR', 'idris.shah.nahar@student.mmu.edu.my', '$2y$10$enKHIFtAMnz7BBtYJtf0beeGb6N4MLYu9fXlGGq5sRklIr0XnHEkq', 'Student', '01164425881', 2, NULL, NULL, 1, 'Active', NULL),
(10000001, 'ALEESYA ZAARA BINTI EDI ZULKARNAIN\r\n\r\n', 'aleesya@student.mmu.edu.my', '$2y$10$3uFp8n0THCm1t5NSq1zgy.RSYz06h4dognjKLmrntOu8zdtoTKi..', 'Student', '01154067900', 2, NULL, NULL, 1, 'Active', NULL),
(10000002, 'SURIYA', 'suriya@student.mmu.edu.my', '$2y$10$bjm8sscxTfQDwzazBzmKC..GJDS4Soo2nWwfhPTQEtJMDzft3vui.', 'Student', '0123211695', 2, NULL, NULL, 1, 'Active', NULL),
(10000003, 'NOR IDAYU BINTI AHMAD AZAMI', 'idayu.azami@mmu.edu.my', '$2y$10$q7CSZJ1ZhRfO.wrkRQhv0euy1DCWGK55/hM0rsT2DNqjOImN3htg2', 'Lecturer', '0112223333', 2, NULL, NULL, 1, 'Active', NULL),
(10000004, 'MR ADMIN', 'admin1@mmu.edu.my', '$2y$10$qftMOyhSssrVZDjmUPjIwuauIL67zHORgCvXQyA0mooUg5AjMpB6C', 'Admin', '0112223333', 2, NULL, NULL, 1, 'Active', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `annoucement`
--
ALTER TABLE `annoucement`
  ADD PRIMARY KEY (`annoucement_id`),
  ADD KEY `fk_annoucement_user` (`admin_id`);

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
-- Indexes for table `issue_report`
--
ALTER TABLE `issue_report`
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
-- Indexes for table `timetable`
--
ALTER TABLE `timetable`
  ADD PRIMARY KEY (`timetable_id`),
  ADD KEY `fk_timetable_facility` (`facility_id`);

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
  MODIFY `annoucement_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Announcement Identification', AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Booking Identification', AUTO_INCREMENT=1076;

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `equip_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Equipment Identification', AUTO_INCREMENT=1006;

--
-- AUTO_INCREMENT for table `facility`
--
ALTER TABLE `facility`
  MODIFY `facility_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Facility Identification ', AUTO_INCREMENT=206;

--
-- AUTO_INCREMENT for table `issue_report`
--
ALTER TABLE `issue_report`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Report Identification', AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `notification`
--
ALTER TABLE `notification`
  MODIFY `notify_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Notification Identification', AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `penalty`
--
ALTER TABLE `penalty`
  MODIFY `penalty_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Penalty Identification', AUTO_INCREMENT=100019;

--
-- AUTO_INCREMENT for table `timetable`
--
ALTER TABLE `timetable`
  MODIFY `timetable_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Timetable Identification', AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'User Identification', AUTO_INCREMENT=10000006;

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
  ADD CONSTRAINT `fk_booking_facility` FOREIGN KEY (`facility_id`) REFERENCES `facility` (`facility_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_booking_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `booking_equipment`
--
ALTER TABLE `booking_equipment`
  ADD CONSTRAINT `fk_book_equip_booking` FOREIGN KEY (`booking_id`) REFERENCES `booking` (`booking_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_book_equip_equipment` FOREIGN KEY (`equip_id`) REFERENCES `equipment` (`equip_id`) ON DELETE CASCADE;

--
-- Constraints for table `issue_report`
--
ALTER TABLE `issue_report`
  ADD CONSTRAINT `fk_issue_facility` FOREIGN KEY (`facility_id`) REFERENCES `facility` (`facility_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_issue_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `penalty`
--
ALTER TABLE `penalty`
  ADD CONSTRAINT `fk_penalty_booking` FOREIGN KEY (`booking_id`) REFERENCES `booking` (`booking_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_penalty_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `timetable`
--
ALTER TABLE `timetable`
  ADD CONSTRAINT `fk_timetable_facility` FOREIGN KEY (`facility_id`) REFERENCES `facility` (`facility_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
