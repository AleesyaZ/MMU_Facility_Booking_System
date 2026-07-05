-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 05, 2026 at 12:04 PM
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
(20, 10000004, 'The Maintenance of Room CQAR3007 is Complete', 'The leak from the roof had been fixed. Sorry for the inconvenience caused from the water leak. Thank you for being patient with us as we appreciate your patience.', 'Update', '2026-07-05 17:31:25', 'Live'),
(21, 10000004, 'Avoidance of Being Barred', 'We are here to remind you to please pay the tuition fee in order to avoid being barred. Barring could lead you to being unable to get your Exam Slip and therefore aren\'t allowed to participate for any major final exams. Thank you for reading and we thank you for your cooperation.', 'Reminder', '2026-07-05 17:35:08', 'Live'),
(22, 10000004, 'OARS Club Event', 'The OARS club will be having an event at the MMU Stadium at around 7:00 PM until 10:00 PM. Feel free to participate and watch as there is no ticket fee. Please kindly do not litter trash at the MMU Stadium for it will result in a Penalty Strike. Thank you for your cooperation.', 'Event', '2026-07-05 17:39:50', 'Live');

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
(1077, 10000001, 163, '2026-07-06', '10:16:00', '12:16:00', 'Booked for studying with my friends for the upcoming finals', 'Approved', 0, NULL),
(1078, 10000001, 163, '2026-07-07', '14:18:00', '16:18:00', 'studying again for our finals', 'Approved', 0, NULL),
(1079, 10000000, 162, '2026-07-10', '20:00:00', '22:00:00', 'Studying at night with my fellow friends', 'Approved', 0, NULL),
(1080, 10000000, 162, '2026-07-06', '17:21:00', '19:21:00', 'Will be doing group studies.', 'Cancelled', 0, NULL),
(1081, 10000002, 187, '2026-06-29', '19:23:00', '21:23:00', 'Practising badminton', 'Approved', 0, NULL),
(1082, 10000002, 187, '2026-07-07', '19:24:00', '21:24:00', 'Final practise before the tournament', 'Approved', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `booking_equipment`
--

CREATE TABLE `booking_equipment` (
  `booking_id` int(11) NOT NULL COMMENT 'Booking Identification',
  `equip_id` int(11) NOT NULL COMMENT 'Equipment Identification',
  `quantity` int(11) NOT NULL COMMENT 'Quantity Requested'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking_equipment`
--

INSERT INTO `booking_equipment` (`booking_id`, `equip_id`, `quantity`) VALUES
(1081, 1006, 1),
(1081, 1008, 4),
(1082, 1006, 1),
(1082, 1008, 4);

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
(1006, 'Badminton Net', 6, 4, 'Available', 'Sports', 'Cyberjaya'),
(1007, 'Badminton Net', 6, 6, 'Available', 'Sports', 'Melaka'),
(1008, 'Badminton Racket', 16, 8, 'Available', 'Sports', 'Cyberjaya'),
(1009, 'Badminton Racket', 16, 16, 'Available', 'Sports', 'Melaka'),
(1010, 'Football', 8, 8, 'Available', 'Sports', 'Cyberjaya'),
(1011, 'Football', 6, 6, 'Available', 'Sports', 'Melaka'),
(1012, 'Ping Pong Racket', 10, 10, 'Available', 'Sports', 'Cyberjaya'),
(1013, 'Ping Pong Racket', 8, 8, 'Available', 'Sports', 'Melaka'),
(1014, 'Ping Pong Table', 3, 3, 'Available', 'Sports', 'Cyberjaya'),
(1015, 'Ping Pong Table', 3, 3, 'Available', 'Sports', 'Melaka'),
(1016, 'Tables', 30, 30, 'Available', 'General', 'Cyberjaya'),
(1017, 'Chairs', 80, 80, 'Available', 'General', 'Cyberjaya'),
(1018, 'Tables', 25, 25, 'Available', 'General', 'Melaka'),
(1020, 'Chairs', 100, 100, 'Available', 'General', 'Melaka'),
(1021, 'Projector', 10, 10, 'Available', 'Laboratory', 'Cyberjaya'),
(1022, 'Projector', 10, 10, 'Available', 'Laboratory', 'Melaka'),
(1023, 'Projector', 10, 10, 'Available', 'Lecture Hall', 'Cyberjaya'),
(1024, 'Projector', 10, 10, 'Available', 'Lecture Hall', 'Melaka'),
(1025, 'Projector', 10, 10, 'Available', 'Tutorial', 'Cyberjaya'),
(1026, 'Projector', 10, 10, 'Available', 'Tutorial', 'Melaka'),
(1027, 'Microphone', 10, 10, 'Available', 'Laboratory', 'Cyberjaya'),
(1028, 'Microphone', 10, 10, 'Available', 'Laboratory', 'Melaka'),
(1029, 'Microphone', 15, 15, 'Available', 'Lecture Hall', 'Cyberjaya'),
(1030, 'Microphone', 15, 15, 'Available', 'Lecture Hall', 'Melaka'),
(1031, 'Microphone', 10, 10, 'Available', 'Tutorial', 'Cyberjaya'),
(1032, 'Microphone', 10, 10, 'Available', 'Tutorial', 'Melaka'),
(1033, 'Mini Speaker', 10, 10, 'Available', 'Laboratory', 'Cyberjaya'),
(1034, 'Mini Speaker', 10, 10, 'Available', 'Laboratory', 'Melaka'),
(1035, 'Mini Speaker', 20, 20, 'Available', 'Lecture Hall', 'Cyberjaya'),
(1036, 'Mini Speaker', 20, 20, 'Available', 'Lecture Hall', 'Melaka'),
(1037, 'Mini Speaker', 10, 10, 'Available', 'Tutorial', 'Cyberjaya'),
(1038, 'Mini Speaker', 10, 10, 'Available', 'Tutorial', 'Melaka');

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
(161, 'CQAR3007 ', 'MMU Cyberjaya', 'Laboratory', 40, 'The FCI Lab is a computer laboratory used by students from the Faculty of Computing and Informatics for practical classes, programming, software development, and other computer-based learning activities.', 'Available', 'fac_1783067247_794.webp', 'FCI'),
(162, 'CQMX0001', 'MMU Cyberjaya', 'Lecture Hall', 100, 'A spacious lecture hall designed for teaching large groups of students. It is equipped with a projector, audio system, comfortable seating, air conditioning, and other essential teaching facilities to support lectures, presentations, and seminars.', 'Available', 'fac_1783067373_723.webp', 'FCI'),
(163, 'CQAR3002', 'MMU Cyberjaya', 'Tutorial', 40, 'A tutorial classroom designed for small-group learning, discussions, and interactive teaching sessions. It provides a comfortable environment for tutorials, group activities, and academic consultations.', 'Available', 'fac_1783067464_207.webp', 'FCI'),
(164, 'CQBR2004', 'MMU Cyberjaya', 'Tutorial', 40, 'A classroom used for tutorial sessions where students can participate in discussions, complete group activities, and receive guidance from lecturers in a more interactive learning environment.', 'Available', 'fac_1783072457_583.webp', 'FOM'),
(165, 'MBMR4003', 'MMU Cyberjaya', 'Lecture Hall', 100, 'A large classroom designed to accommodate a high number of students for lectures and presentations. The hall includes multimedia equipment, seating, and air conditioning to ensure an effective learning experience.', 'Available', 'fac_1783072533_218.webp', 'FOM'),
(166, 'CQBR1001 ', 'MMU Cyberjaya', 'Laboratory', 40, 'A specialized laboratory that supports practical learning for management students through business simulations, computer-based activities, data analysis, and collaborative learning.', 'Available', 'fac_1783072687_574.webp', 'FOM'),
(167, 'CQMX0004', 'MMU Cyberjaya', 'Lecture Hall', 100, 'A large classroom designed to accommodate a high number of students for lectures and presentations. The hall includes multimedia equipment, seating, and air conditioning to ensure an effective learning experience.', 'Available', 'fac_1783072920_990.jpg', 'FCM'),
(168, 'CQDR1001', 'MMU Cyberjaya', 'Laboratory', 40, 'A creative multimedia laboratory equipped with industry-standard computers and software for digital design, animation, video editing, 3D modeling, game development, and other multimedia production activities.', 'Available', 'fac_1783072990_847.jpg', 'FCM'),
(169, 'CQDR2003', 'MMU Cyberjaya', 'Tutorial', 80, 'A classroom for tutorial sessions, group discussions, and interactive learning activities between students and lecturers.', 'Available', 'fac_1783073138_792.jpg', 'FCM'),
(170, 'CQMX0003', 'MMU Cyberjaya', 'Lecture Hall', 100, 'A large classroom designed to accommodate a high number of students for lectures and presentations. The hall includes multimedia equipment, seating, and air conditioning to ensure an effective learning experience.', 'Available', 'fac_1783073853_456.jpg', 'FOE'),
(171, 'CQCR2010', 'MMU Cyberjaya', 'Tutorial', 40, 'A classroom used for tutorial sessions where students can participate in discussions, complete group activities, and receive guidance from lecturers in a more interactive learning environment.', 'Available', 'fac_1783073899_447.webp', 'FOE'),
(172, 'FCA Cinema', 'MMU Cyberjaya', 'Lecture Hall', 100, 'A lecture hall used for teaching film, television, and cinematic arts courses. It is equipped with multimedia presentation facilities to support lectures, seminars, and academic presentations.', 'Available', 'fac_1783074573_173.jpg', 'FCA'),
(173, 'CQDR3004', 'MMU Cyberjaya', 'Tutorial', 40, 'A classroom designed for small-group discussions, workshops, script analysis, and interactive learning activities that encourage collaboration between students and lecturers.', 'Available', 'fac_1783074633_521.webp', 'FCA'),
(174, 'CQDR1002', 'MMU Cyberjaya', 'Laboratory', 40, 'A specialized laboratory equipped with professional hardware and software for filmmaking, video editing, audio production, and other practical cinematic arts activities.', 'Available', 'fac_1783074792_573.jpg', 'FCA'),
(175, 'CQMX0005', 'MMU Cyberjaya', 'Lecture Hall', 100, 'A lecture hall designed for communication, media, and public relations courses. It provides a comfortable learning environment with multimedia facilities for lectures, presentations, and seminars.', 'Available', 'fac_1783075051_347.webp', 'FAC'),
(176, 'CQER1001', 'MMU Cyberjaya', 'Laboratory', 40, 'A specialized laboratory equipped with multimedia technology and communication software to support practical learning in broadcasting, media production, digital communication, and related activities.', 'Available', 'fac_1783075190_316.jpg', 'FAC'),
(177, 'CQER2005', 'MMU Cyberjaya', 'Tutorial', 40, 'A classroom used for tutorials, discussions, group activities, and presentations, allowing students to develop communication skills through interactive learning.', 'Available', 'fac_1783075233_253.webp', 'FAC'),
(179, 'CNMX1001', 'MMU Cyberjaya', 'Lecture Hall', 100, 'A spacious lecture hall designed for teaching large groups of students. It is equipped with a projector, audio system, comfortable seating, air conditioning, and other essential teaching facilities to support lectures, presentations, and seminars.', 'Available', 'fac_1783077056_886.jpg', 'General'),
(180, 'CNMX1002', 'MMU Cyberjaya', 'Lecture Hall', 100, 'A spacious lecture hall designed for teaching large groups of students. It is equipped with a projector, audio system, comfortable seating, air conditioning, and other essential teaching facilities to support lectures, presentations, and seminars.', 'Available', 'fac_1783077079_349.jpg', 'General'),
(181, 'CNMX1003', 'MMU Cyberjaya', 'Lecture Hall', 100, 'A spacious lecture hall designed for teaching large groups of students. It is equipped with a projector, audio system, comfortable seating, air conditioning, and other essential teaching facilities to support lectures, presentations, and seminars.', 'Available', 'fac_1783077097_655.jpg', 'General'),
(182, 'CNMX1004', 'MMU Cyberjaya', 'Lecture Hall', 100, 'A spacious lecture hall designed for teaching large groups of students. It is equipped with a projector, audio system, comfortable seating, air conditioning, and other essential teaching facilities to support lectures, presentations, and seminars.', 'Available', 'fac_1783077114_446.jpg', 'General'),
(183, 'CNMX1005', 'MMU Cyberjaya', 'Lecture Hall', 100, 'A spacious lecture hall designed for teaching large groups of students. It is equipped with a projector, audio system, comfortable seating, air conditioning, and other essential teaching facilities to support lectures, presentations, and seminars.', 'Available', 'fac_1783077130_174.jpg', 'General'),
(184, 'Main Purpose Hall', 'MMU Cyberjaya', 'Lecture Hall', 500, 'A spacious multipurpose hall designed to accommodate large gatherings, including university events, seminars, workshops, examinations, exhibitions, and student activities. The hall is equipped with audiovisual facilities, a stage, and flexible seating arrangements to support various functions.', 'Available', 'fac_1783077310_864.jpg', 'General'),
(185, 'Swimming Pool', 'MMU Cyberjaya', 'Sports', 100, 'A swimming facility designed for recreational swimming, training, and aquatic sports. It provides a safe and well-maintained environment for students and staff to exercise and participate in swimming activities.', 'Available', 'fac_1783077483_766.webp', 'General'),
(186, 'MMU Stadium', 'MMU Cyberjaya', 'Sports', 1000, 'A multi-purpose outdoor stadium used for sports competitions, athletics, football matches, university events, and recreational activities. The stadium provides seating for spectators and supports various sporting and community events.', 'Available', 'fac_1783077553_271.jpg', 'General'),
(187, 'Badminton Court', 'MMU Cyberjaya', 'Sports', 4, 'An indoor badminton court equipped with standard flooring, nets, and lighting to support recreational play, training sessions, and sports competitions.', 'Available', 'fac_1783077601_533.jpg', 'General'),
(188, 'Basketball Court', 'MMU Cyberjaya', 'Sports', 20, 'A basketball court designed for training, recreational games, and university competitions. It features standard basketball hoops, court markings, and sufficient space for team activities.', 'Maintenance', 'fac_1783077640_366.jpg', 'General'),
(189, 'Gym', 'MMU Cyberjaya', 'Sports', 50, 'A fitness facility equipped with a variety of exercise machines, free weights, and fitness equipment to support strength training, cardiovascular workouts, and overall physical wellness for students and staff.', 'Available', 'fac_1783077660_997.jpg', 'General'),
(190, 'MSMX2006 ', 'MMU Melaka', 'Lecture Hall', 100, 'A lecture hall designed for engineering and technology courses, equipped with multimedia teaching facilities to support lectures, seminars, and academic presentations.', 'Available', 'fac_1783079288_202.jpg', 'FET'),
(191, 'MLR0001 ', 'MMU Melaka', 'Laboratory', 40, 'A specialized engineering laboratory equipped with technical equipment, instruments, and software to support hands-on experiments, research, and practical learning.', 'Available', 'fac_1783079589_332.webp', 'FET'),
(192, 'CQCR1005', 'MMU Cyberjaya', 'Laboratory', 40, 'A specialized engineering laboratory equipped with modern instruments, technical equipment, and engineering software to support practical experiments, research, project development, and hands-on learning across various engineering disciplines.', 'Available', 'fac_1783079471_266.jpg', 'FOE'),
(194, 'MSMX3001 ', 'MMU Melaka', 'Lecture Hall', 100, 'A lecture hall used for information technology and computer science courses, providing multimedia teaching facilities for lectures, seminars, and presentations.', 'Available', 'fac_1783079772_391.jpg', 'FIST'),
(195, 'MNR2001 ', 'MMU Melaka', 'Laboratory', 40, 'A computer laboratory equipped with modern computers and specialized software to support programming, networking, cybersecurity, data analytics, and other practical computing activities.', 'Available', 'fac_1783079807_477.webp', 'FIST'),
(196, 'MNR3002', 'MMU Melaka', 'Tutorial', 40, 'A classroom designed for tutorials, discussions, programming exercises, and collaborative learning in information technology subjects.', 'Available', 'fac_1783079852_236.webp', 'FIST'),
(200, 'MSMX2007', 'MMU Melaka', 'Lecture Hall', 100, 'A lecture hall used for business, accounting, and management courses. It is equipped with multimedia teaching facilities to support lectures, seminars, and academic presentations.', 'Available', 'fac_1783080705_592.jpg', 'FOB'),
(201, 'MBR2010 ', 'MMU Melaka', 'Laboratory', 40, 'A business laboratory equipped with computers and specialized software to support business simulations, financial analysis, accounting applications, and practical learning activities.', 'Available', 'fac_1783080618_607.webp', 'FOB'),
(202, 'MAR1005 ', 'MMU Melaka', 'Tutorial', 40, 'A classroom designed for tutorials, group discussions, case studies, presentations, and collaborative learning activities in business-related subjects.', 'Available', 'fac_1783080676_831.jpg', 'FOB'),
(203, 'MVMX1001 ', 'MMU Melaka', 'Lecture Hall', 100, 'A lecture hall designed for law lectures, seminars, and academic presentations, equipped with multimedia facilities to support teaching and learning.', 'Available', 'fac_1783080980_414.jpg', 'FOL'),
(205, 'MVR1001 ', 'MMU Melaka', 'Laboratory', 40, 'A business laboratory equipped with computers and specialized software to support business simulations, financial analysis, accounting applications, and practical learning activities.', 'Available', 'fac_1783081127_883.jpg', 'FOL'),
(206, 'MLR1002', 'MMU Melaka', 'Tutorial', 40, 'A tutorial room for the faculty of Engineering and Technology.', 'Available', 'fac_1783240006_526.webp', 'FET'),
(207, 'MVR1003', 'MMU Melaka', 'Tutorial', 40, 'A tutorial class for students from Faculty of Law.', 'Available', 'fac_1783240337_673.jpg', 'FOL');

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
(100021, 10000000, NULL, '[No-Show] Didn\'t use the booked facility. Wasted facility utilization. ', 2, 'Active');

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
(66, 163, 'Monday', '08:00:00', '09:00:00', '2026-08-10', '2026-06-29'),
(67, 163, 'Wednesday', '08:00:00', '09:00:00', '2026-08-10', '2026-06-29'),
(68, 163, 'Thursday', '08:00:00', '09:00:00', '2026-08-10', '2026-06-29'),
(69, 163, 'Monday', '09:00:00', '10:00:00', '2026-08-10', '2026-06-29'),
(70, 163, 'Friday', '09:00:00', '10:00:00', '2026-08-10', '2026-06-29'),
(71, 163, 'Tuesday', '10:00:00', '11:00:00', '2026-08-10', '2026-06-29'),
(72, 163, 'Tuesday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(73, 163, 'Wednesday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(74, 163, 'Wednesday', '12:00:00', '13:00:00', '2026-08-10', '2026-06-29'),
(75, 163, 'Thursday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(76, 163, 'Thursday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(77, 163, 'Friday', '15:00:00', '16:00:00', '2026-08-10', '2026-06-29'),
(78, 163, 'Friday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(79, 163, 'Wednesday', '17:00:00', '18:00:00', '2026-08-10', '2026-06-29'),
(80, 163, 'Friday', '17:00:00', '18:00:00', '2026-08-10', '2026-06-29'),
(81, 163, 'Tuesday', '18:00:00', '19:00:00', '2026-08-10', '2026-06-29'),
(82, 161, 'Friday', '10:00:00', '11:00:00', '2026-08-10', '2026-06-29'),
(83, 161, 'Monday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(84, 161, 'Thursday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(85, 161, 'Friday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(86, 161, 'Monday', '12:00:00', '13:00:00', '2026-08-10', '2026-06-29'),
(87, 161, 'Wednesday', '12:00:00', '13:00:00', '2026-08-10', '2026-06-29'),
(88, 161, 'Tuesday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(89, 161, 'Tuesday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(90, 161, 'Thursday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(91, 161, 'Thursday', '17:00:00', '18:00:00', '2026-08-10', '2026-06-29'),
(92, 162, 'Monday', '08:00:00', '09:00:00', '2026-08-10', '2026-06-29'),
(93, 162, 'Monday', '09:00:00', '10:00:00', '2026-08-10', '2026-06-29'),
(94, 162, 'Tuesday', '10:00:00', '11:00:00', '2026-08-10', '2026-06-29'),
(95, 162, 'Thursday', '10:00:00', '11:00:00', '2026-08-10', '2026-06-29'),
(96, 162, 'Thursday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(97, 162, 'Wednesday', '12:00:00', '13:00:00', '2026-08-10', '2026-06-29'),
(98, 162, 'Wednesday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(99, 162, 'Tuesday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(100, 162, 'Tuesday', '15:00:00', '16:00:00', '2026-08-10', '2026-06-29'),
(101, 162, 'Wednesday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(102, 162, 'Thursday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(103, 162, 'Friday', '17:00:00', '18:00:00', '2026-08-10', '2026-06-29'),
(104, 162, 'Friday', '18:00:00', '19:00:00', '2026-08-10', '2026-06-29'),
(105, 192, 'Tuesday', '09:00:00', '10:00:00', '2026-08-10', '2026-06-29'),
(106, 192, 'Wednesday', '09:00:00', '10:00:00', '2026-08-10', '2026-06-29'),
(107, 192, 'Monday', '10:00:00', '11:00:00', '2026-08-10', '2026-06-29'),
(108, 192, 'Tuesday', '10:00:00', '11:00:00', '2026-08-10', '2026-06-29'),
(109, 192, 'Thursday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(110, 192, 'Thursday', '12:00:00', '13:00:00', '2026-08-10', '2026-06-29'),
(111, 192, 'Thursday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(112, 192, 'Friday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(113, 192, 'Friday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(114, 171, 'Wednesday', '10:00:00', '11:00:00', '2026-08-10', '2026-06-29'),
(115, 171, 'Monday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(116, 171, 'Tuesday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(117, 171, 'Monday', '12:00:00', '13:00:00', '2026-08-10', '2026-06-29'),
(118, 171, 'Thursday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(119, 171, 'Thursday', '15:00:00', '16:00:00', '2026-08-10', '2026-06-29'),
(120, 171, 'Friday', '15:00:00', '16:00:00', '2026-08-10', '2026-06-29'),
(121, 171, 'Friday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(122, 170, 'Friday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(123, 170, 'Friday', '12:00:00', '13:00:00', '2026-08-10', '2026-06-29'),
(124, 170, 'Thursday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(125, 170, 'Thursday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(126, 170, 'Wednesday', '15:00:00', '16:00:00', '2026-08-10', '2026-06-29'),
(127, 170, 'Tuesday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(128, 170, 'Wednesday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(129, 170, 'Tuesday', '17:00:00', '18:00:00', '2026-08-10', '2026-06-29'),
(130, 170, 'Monday', '18:00:00', '19:00:00', '2026-08-10', '2026-06-29'),
(131, 168, 'Monday', '08:00:00', '09:00:00', '2026-08-10', '2026-06-29'),
(132, 168, 'Tuesday', '08:00:00', '09:00:00', '2026-08-10', '2026-06-29'),
(133, 168, 'Wednesday', '08:00:00', '09:00:00', '2026-08-10', '2026-06-29'),
(134, 168, 'Thursday', '08:00:00', '09:00:00', '2026-08-10', '2026-06-29'),
(135, 168, 'Friday', '08:00:00', '09:00:00', '2026-08-10', '2026-06-29'),
(136, 168, 'Thursday', '09:00:00', '10:00:00', '2026-08-10', '2026-06-29'),
(137, 168, 'Friday', '09:00:00', '10:00:00', '2026-08-10', '2026-06-29'),
(138, 168, 'Tuesday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(139, 168, 'Wednesday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(140, 168, 'Wednesday', '12:00:00', '13:00:00', '2026-08-10', '2026-06-29'),
(141, 168, 'Monday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(142, 168, 'Tuesday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(143, 168, 'Tuesday', '15:00:00', '16:00:00', '2026-08-10', '2026-06-29'),
(144, 169, 'Tuesday', '08:00:00', '09:00:00', '2026-08-10', '2026-06-29'),
(145, 169, 'Tuesday', '10:00:00', '11:00:00', '2026-08-10', '2026-06-29'),
(146, 169, 'Wednesday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(147, 169, 'Monday', '12:00:00', '13:00:00', '2026-08-10', '2026-06-29'),
(148, 169, 'Wednesday', '12:00:00', '13:00:00', '2026-08-10', '2026-06-29'),
(149, 169, 'Monday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(150, 169, 'Thursday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(151, 169, 'Thursday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(152, 169, 'Monday', '15:00:00', '16:00:00', '2026-08-10', '2026-06-29'),
(153, 169, 'Thursday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(154, 169, 'Friday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(155, 169, 'Friday', '18:00:00', '19:00:00', '2026-08-10', '2026-06-29'),
(156, 167, 'Thursday', '09:00:00', '10:00:00', '2026-08-10', '2026-06-29'),
(157, 167, 'Friday', '09:00:00', '10:00:00', '2026-08-10', '2026-06-29'),
(158, 167, 'Thursday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(159, 167, 'Friday', '12:00:00', '13:00:00', '2026-08-10', '2026-06-29'),
(160, 167, 'Tuesday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(161, 167, 'Wednesday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(162, 167, 'Friday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(163, 167, 'Wednesday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(164, 167, 'Thursday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(165, 167, 'Monday', '15:00:00', '16:00:00', '2026-08-10', '2026-06-29'),
(166, 167, 'Tuesday', '15:00:00', '16:00:00', '2026-08-10', '2026-06-29'),
(167, 166, 'Monday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(168, 166, 'Tuesday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(169, 166, 'Tuesday', '12:00:00', '13:00:00', '2026-08-10', '2026-06-29'),
(170, 166, 'Tuesday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(171, 166, 'Wednesday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(172, 166, 'Wednesday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(173, 166, 'Friday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(174, 166, 'Wednesday', '15:00:00', '16:00:00', '2026-08-10', '2026-06-29'),
(175, 166, 'Thursday', '15:00:00', '16:00:00', '2026-08-10', '2026-06-29'),
(176, 166, 'Friday', '15:00:00', '16:00:00', '2026-08-10', '2026-06-29'),
(177, 164, 'Wednesday', '09:00:00', '10:00:00', '2026-08-10', '2026-06-29'),
(178, 164, 'Friday', '09:00:00', '10:00:00', '2026-08-10', '2026-06-29'),
(179, 164, 'Wednesday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(180, 164, 'Thursday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(181, 164, 'Tuesday', '12:00:00', '13:00:00', '2026-08-10', '2026-06-29'),
(182, 164, 'Tuesday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(183, 164, 'Monday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(184, 164, 'Wednesday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(185, 164, 'Friday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(186, 164, 'Thursday', '15:00:00', '16:00:00', '2026-08-10', '2026-06-29'),
(187, 164, 'Wednesday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(188, 164, 'Thursday', '18:00:00', '19:00:00', '2026-08-10', '2026-06-29'),
(189, 165, 'Friday', '10:00:00', '11:00:00', '2026-08-10', '2026-06-29'),
(190, 165, 'Tuesday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(191, 165, 'Friday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(192, 165, 'Wednesday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(193, 165, 'Thursday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(194, 165, 'Monday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(195, 165, 'Wednesday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(196, 165, 'Monday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(197, 165, 'Wednesday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(198, 165, 'Thursday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(199, 174, 'Tuesday', '09:00:00', '10:00:00', '2026-08-10', '2026-06-29'),
(200, 174, 'Thursday', '10:00:00', '11:00:00', '2026-08-10', '2026-06-29'),
(201, 174, 'Tuesday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(202, 174, 'Wednesday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(203, 174, 'Wednesday', '12:00:00', '13:00:00', '2026-08-10', '2026-06-29'),
(204, 174, 'Tuesday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(205, 174, 'Wednesday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(206, 174, 'Thursday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(207, 174, 'Friday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(208, 174, 'Friday', '15:00:00', '16:00:00', '2026-08-10', '2026-06-29'),
(209, 173, 'Wednesday', '09:00:00', '10:00:00', '2026-08-10', '2026-06-29'),
(210, 173, 'Tuesday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(211, 173, 'Wednesday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(212, 173, 'Thursday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(213, 173, 'Tuesday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(214, 173, 'Wednesday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(215, 173, 'Thursday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(216, 173, 'Friday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(217, 173, 'Wednesday', '15:00:00', '16:00:00', '2026-08-10', '2026-06-29'),
(218, 173, 'Tuesday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(219, 173, 'Friday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(220, 173, 'Friday', '17:00:00', '18:00:00', '2026-08-10', '2026-06-29'),
(221, 172, 'Thursday', '08:00:00', '09:00:00', '2026-08-10', '2026-06-29'),
(222, 172, 'Friday', '08:00:00', '09:00:00', '2026-08-10', '2026-06-29'),
(223, 172, 'Thursday', '09:00:00', '10:00:00', '2026-08-10', '2026-06-29'),
(224, 172, 'Tuesday', '10:00:00', '11:00:00', '2026-08-10', '2026-06-29'),
(225, 172, 'Wednesday', '10:00:00', '11:00:00', '2026-08-10', '2026-06-29'),
(226, 172, 'Thursday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(227, 172, 'Monday', '12:00:00', '13:00:00', '2026-08-10', '2026-06-29'),
(228, 172, 'Tuesday', '12:00:00', '13:00:00', '2026-08-10', '2026-06-29'),
(229, 172, 'Wednesday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(230, 172, 'Monday', '15:00:00', '16:00:00', '2026-08-10', '2026-06-29'),
(231, 172, 'Tuesday', '15:00:00', '16:00:00', '2026-08-10', '2026-06-29'),
(232, 172, 'Friday', '15:00:00', '16:00:00', '2026-08-10', '2026-06-29'),
(233, 172, 'Wednesday', '17:00:00', '18:00:00', '2026-08-10', '2026-06-29'),
(234, 176, 'Wednesday', '09:00:00', '10:00:00', '2026-08-10', '2026-06-29'),
(235, 176, 'Friday', '09:00:00', '10:00:00', '2026-08-10', '2026-06-29'),
(236, 176, 'Wednesday', '10:00:00', '11:00:00', '2026-08-10', '2026-06-29'),
(237, 176, 'Friday', '10:00:00', '11:00:00', '2026-08-10', '2026-06-29'),
(238, 176, 'Monday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(239, 176, 'Tuesday', '12:00:00', '13:00:00', '2026-08-10', '2026-06-29'),
(240, 176, 'Wednesday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(241, 176, 'Thursday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(242, 176, 'Wednesday', '15:00:00', '16:00:00', '2026-08-10', '2026-06-29'),
(243, 177, 'Monday', '09:00:00', '10:00:00', '2026-08-10', '2026-06-29'),
(244, 177, 'Tuesday', '09:00:00', '10:00:00', '2026-08-10', '2026-06-29'),
(245, 177, 'Wednesday', '09:00:00', '10:00:00', '2026-08-10', '2026-06-29'),
(246, 177, 'Monday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(247, 177, 'Tuesday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(248, 177, 'Wednesday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(249, 177, 'Thursday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(250, 177, 'Tuesday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(251, 177, 'Tuesday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(252, 177, 'Wednesday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(253, 177, 'Thursday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(254, 177, 'Monday', '15:00:00', '16:00:00', '2026-08-10', '2026-06-29'),
(255, 177, 'Friday', '15:00:00', '16:00:00', '2026-08-10', '2026-06-29'),
(256, 177, 'Tuesday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(257, 177, 'Wednesday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(258, 177, 'Thursday', '17:00:00', '18:00:00', '2026-08-10', '2026-06-29'),
(259, 175, 'Tuesday', '08:00:00', '09:00:00', '2026-08-10', '2026-06-29'),
(260, 175, 'Wednesday', '08:00:00', '09:00:00', '2026-08-10', '2026-06-29'),
(261, 175, 'Monday', '09:00:00', '10:00:00', '2026-08-10', '2026-06-29'),
(262, 175, 'Thursday', '09:00:00', '10:00:00', '2026-08-10', '2026-06-29'),
(263, 175, 'Monday', '10:00:00', '11:00:00', '2026-08-10', '2026-06-29'),
(264, 175, 'Thursday', '10:00:00', '11:00:00', '2026-08-10', '2026-06-29'),
(265, 175, 'Monday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(266, 175, 'Wednesday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(267, 175, 'Thursday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(268, 175, 'Friday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(269, 175, 'Monday', '12:00:00', '13:00:00', '2026-08-10', '2026-06-29'),
(270, 175, 'Monday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(271, 175, 'Monday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(272, 175, 'Tuesday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(273, 175, 'Wednesday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(274, 175, 'Monday', '15:00:00', '16:00:00', '2026-08-10', '2026-06-29'),
(275, 175, 'Wednesday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(276, 175, 'Thursday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(277, 175, 'Wednesday', '17:00:00', '18:00:00', '2026-08-10', '2026-06-29'),
(278, 191, 'Tuesday', '09:00:00', '10:00:00', '2026-08-10', '2026-06-29'),
(279, 191, 'Wednesday', '09:00:00', '10:00:00', '2026-08-10', '2026-06-29'),
(280, 191, 'Thursday', '09:00:00', '10:00:00', '2026-08-10', '2026-06-29'),
(281, 191, 'Tuesday', '10:00:00', '11:00:00', '2026-08-10', '2026-06-29'),
(282, 191, 'Wednesday', '10:00:00', '11:00:00', '2026-08-10', '2026-06-29'),
(283, 191, 'Thursday', '10:00:00', '11:00:00', '2026-08-10', '2026-06-29'),
(284, 191, 'Monday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(285, 191, 'Tuesday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(286, 191, 'Wednesday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(287, 191, 'Thursday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(288, 191, 'Tuesday', '12:00:00', '13:00:00', '2026-08-10', '2026-06-29'),
(289, 191, 'Wednesday', '12:00:00', '13:00:00', '2026-08-10', '2026-06-29'),
(290, 191, 'Thursday', '12:00:00', '13:00:00', '2026-08-10', '2026-06-29'),
(291, 191, 'Friday', '12:00:00', '13:00:00', '2026-08-10', '2026-06-29'),
(292, 191, 'Wednesday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(293, 191, 'Thursday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(294, 191, 'Friday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(295, 191, 'Wednesday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(296, 191, 'Thursday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(297, 191, 'Friday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(298, 191, 'Thursday', '15:00:00', '16:00:00', '2026-08-10', '2026-06-29'),
(299, 191, 'Friday', '15:00:00', '16:00:00', '2026-08-10', '2026-06-29'),
(300, 191, 'Friday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(301, 191, 'Friday', '17:00:00', '18:00:00', '2026-08-10', '2026-06-29'),
(302, 206, 'Tuesday', '08:00:00', '09:00:00', '2026-08-10', '2026-06-29'),
(303, 206, 'Wednesday', '08:00:00', '09:00:00', '2026-08-10', '2026-06-29'),
(304, 206, 'Thursday', '09:00:00', '10:00:00', '2026-08-10', '2026-06-29'),
(305, 206, 'Monday', '10:00:00', '11:00:00', '2026-08-10', '2026-06-29'),
(306, 206, 'Wednesday', '10:00:00', '11:00:00', '2026-08-10', '2026-06-29'),
(307, 206, 'Tuesday', '12:00:00', '13:00:00', '2026-08-10', '2026-06-29'),
(308, 206, 'Thursday', '12:00:00', '13:00:00', '2026-08-10', '2026-06-29'),
(309, 206, 'Monday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(310, 206, 'Wednesday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(311, 206, 'Friday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(312, 206, 'Wednesday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(313, 206, 'Thursday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(314, 206, 'Tuesday', '15:00:00', '16:00:00', '2026-08-10', '2026-06-29'),
(315, 206, 'Thursday', '15:00:00', '16:00:00', '2026-08-10', '2026-06-29'),
(316, 206, 'Tuesday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(317, 206, 'Thursday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(318, 206, 'Friday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(319, 206, 'Wednesday', '17:00:00', '18:00:00', '2026-08-10', '2026-06-29'),
(320, 206, 'Monday', '18:00:00', '19:00:00', '2026-08-10', '2026-06-29'),
(321, 190, 'Tuesday', '09:00:00', '10:00:00', '2026-08-10', '2026-06-29'),
(322, 190, 'Monday', '10:00:00', '11:00:00', '2026-08-10', '2026-06-29'),
(323, 190, 'Tuesday', '10:00:00', '11:00:00', '2026-08-10', '2026-06-29'),
(324, 190, 'Tuesday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(325, 190, 'Wednesday', '12:00:00', '13:00:00', '2026-08-10', '2026-06-29'),
(326, 190, 'Thursday', '12:00:00', '13:00:00', '2026-08-10', '2026-06-29'),
(327, 190, 'Thursday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(328, 190, 'Wednesday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(329, 190, 'Thursday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(330, 190, 'Monday', '15:00:00', '16:00:00', '2026-08-10', '2026-06-29'),
(331, 190, 'Tuesday', '15:00:00', '16:00:00', '2026-08-10', '2026-06-29'),
(332, 190, 'Wednesday', '15:00:00', '16:00:00', '2026-08-10', '2026-06-29'),
(333, 190, 'Tuesday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(334, 190, 'Thursday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(335, 190, 'Friday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(336, 190, 'Monday', '17:00:00', '18:00:00', '2026-08-10', '2026-06-29'),
(337, 195, 'Monday', '08:00:00', '09:00:00', '2026-08-10', '2026-06-29'),
(338, 195, 'Tuesday', '08:00:00', '09:00:00', '2026-08-10', '2026-06-29'),
(339, 195, 'Tuesday', '10:00:00', '11:00:00', '2026-08-10', '2026-06-29'),
(340, 195, 'Wednesday', '10:00:00', '11:00:00', '2026-08-10', '2026-06-29'),
(341, 195, 'Wednesday', '12:00:00', '13:00:00', '2026-08-10', '2026-06-29'),
(342, 195, 'Thursday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(343, 195, 'Wednesday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(344, 195, 'Thursday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(345, 195, 'Friday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(346, 195, 'Tuesday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(347, 195, 'Thursday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(348, 195, 'Monday', '17:00:00', '18:00:00', '2026-08-10', '2026-06-29'),
(349, 195, 'Friday', '17:00:00', '18:00:00', '2026-08-10', '2026-06-29'),
(350, 195, 'Tuesday', '18:00:00', '19:00:00', '2026-08-10', '2026-06-29'),
(351, 195, 'Thursday', '18:00:00', '19:00:00', '2026-08-10', '2026-06-29'),
(352, 195, 'Friday', '18:00:00', '19:00:00', '2026-08-10', '2026-06-29'),
(353, 196, 'Tuesday', '10:00:00', '11:00:00', '2026-08-10', '2026-06-29'),
(354, 196, 'Wednesday', '10:00:00', '11:00:00', '2026-08-10', '2026-06-29'),
(355, 196, 'Thursday', '10:00:00', '11:00:00', '2026-08-10', '2026-06-29'),
(356, 196, 'Tuesday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(357, 196, 'Wednesday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(358, 196, 'Thursday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(359, 196, 'Tuesday', '12:00:00', '13:00:00', '2026-08-10', '2026-06-29'),
(360, 196, 'Thursday', '12:00:00', '13:00:00', '2026-08-10', '2026-06-29'),
(361, 196, 'Wednesday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(362, 196, 'Tuesday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(363, 196, 'Monday', '15:00:00', '16:00:00', '2026-08-10', '2026-06-29'),
(364, 196, 'Monday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(365, 196, 'Tuesday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(366, 196, 'Wednesday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(367, 196, 'Thursday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(368, 196, 'Friday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(369, 194, 'Tuesday', '09:00:00', '10:00:00', '2026-08-10', '2026-06-29'),
(370, 194, 'Monday', '10:00:00', '11:00:00', '2026-08-10', '2026-06-29'),
(371, 194, 'Friday', '10:00:00', '11:00:00', '2026-08-10', '2026-06-29'),
(372, 194, 'Monday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(373, 194, 'Tuesday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(374, 194, 'Wednesday', '12:00:00', '13:00:00', '2026-08-10', '2026-06-29'),
(375, 194, 'Wednesday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(376, 194, 'Thursday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(377, 194, 'Monday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(378, 194, 'Tuesday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(379, 194, 'Wednesday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(380, 194, 'Friday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(381, 194, 'Wednesday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(382, 194, 'Thursday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(383, 194, 'Monday', '17:00:00', '18:00:00', '2026-08-10', '2026-06-29'),
(384, 194, 'Wednesday', '17:00:00', '18:00:00', '2026-08-10', '2026-06-29'),
(385, 194, 'Thursday', '17:00:00', '18:00:00', '2026-08-10', '2026-06-29'),
(386, 202, 'Thursday', '10:00:00', '11:00:00', '2026-08-10', '2026-06-29'),
(387, 202, 'Wednesday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(388, 202, 'Friday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(389, 202, 'Wednesday', '12:00:00', '13:00:00', '2026-08-10', '2026-06-29'),
(390, 202, 'Tuesday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(391, 202, 'Wednesday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(392, 202, 'Friday', '15:00:00', '16:00:00', '2026-08-10', '2026-06-29'),
(393, 202, 'Wednesday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(394, 202, 'Tuesday', '17:00:00', '18:00:00', '2026-08-10', '2026-06-29'),
(395, 201, 'Thursday', '09:00:00', '10:00:00', '2026-08-10', '2026-06-29'),
(396, 201, 'Wednesday', '10:00:00', '11:00:00', '2026-08-10', '2026-06-29'),
(397, 201, 'Thursday', '10:00:00', '11:00:00', '2026-08-10', '2026-06-29'),
(398, 201, 'Tuesday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(399, 201, 'Wednesday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(400, 201, 'Thursday', '12:00:00', '13:00:00', '2026-08-10', '2026-06-29'),
(401, 201, 'Tuesday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(402, 201, 'Wednesday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(403, 201, 'Monday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(404, 201, 'Thursday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(405, 201, 'Monday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(406, 201, 'Friday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(407, 201, 'Friday', '18:00:00', '19:00:00', '2026-08-10', '2026-06-29'),
(408, 200, 'Friday', '09:00:00', '10:00:00', '2026-08-10', '2026-06-29'),
(409, 200, 'Monday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(410, 200, 'Friday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(411, 200, 'Wednesday', '12:00:00', '13:00:00', '2026-08-10', '2026-06-29'),
(412, 200, 'Friday', '12:00:00', '13:00:00', '2026-08-10', '2026-06-29'),
(413, 200, 'Monday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(414, 200, 'Thursday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(415, 200, 'Tuesday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(416, 200, 'Wednesday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(417, 200, 'Wednesday', '15:00:00', '16:00:00', '2026-08-10', '2026-06-29'),
(418, 200, 'Thursday', '15:00:00', '16:00:00', '2026-08-10', '2026-06-29'),
(419, 200, 'Tuesday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(420, 203, 'Tuesday', '10:00:00', '11:00:00', '2026-08-10', '2026-06-29'),
(421, 203, 'Wednesday', '10:00:00', '11:00:00', '2026-08-10', '2026-06-29'),
(422, 203, 'Wednesday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(423, 203, 'Wednesday', '12:00:00', '13:00:00', '2026-08-10', '2026-06-29'),
(424, 203, 'Thursday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(425, 203, 'Friday', '15:00:00', '16:00:00', '2026-08-10', '2026-06-29'),
(426, 203, 'Monday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(427, 203, 'Tuesday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(428, 203, 'Friday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(429, 203, 'Tuesday', '17:00:00', '18:00:00', '2026-08-10', '2026-06-29'),
(430, 203, 'Thursday', '17:00:00', '18:00:00', '2026-08-10', '2026-06-29'),
(431, 205, 'Thursday', '09:00:00', '10:00:00', '2026-08-10', '2026-06-29'),
(432, 205, 'Friday', '09:00:00', '10:00:00', '2026-08-10', '2026-06-29'),
(433, 205, 'Monday', '10:00:00', '11:00:00', '2026-08-10', '2026-06-29'),
(434, 205, 'Wednesday', '10:00:00', '11:00:00', '2026-08-10', '2026-06-29'),
(435, 205, 'Thursday', '10:00:00', '11:00:00', '2026-08-10', '2026-06-29'),
(436, 205, 'Wednesday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(437, 205, 'Monday', '12:00:00', '13:00:00', '2026-08-10', '2026-06-29'),
(438, 205, 'Tuesday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(439, 205, 'Thursday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(440, 205, 'Wednesday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(441, 205, 'Tuesday', '15:00:00', '16:00:00', '2026-08-10', '2026-06-29'),
(442, 205, 'Thursday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(443, 207, 'Tuesday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(444, 207, 'Thursday', '11:00:00', '12:00:00', '2026-08-10', '2026-06-29'),
(445, 207, 'Thursday', '12:00:00', '13:00:00', '2026-08-10', '2026-06-29'),
(446, 207, 'Friday', '12:00:00', '13:00:00', '2026-08-10', '2026-06-29'),
(447, 207, 'Monday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(448, 207, 'Tuesday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(449, 207, 'Wednesday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(450, 207, 'Thursday', '13:00:00', '14:00:00', '2026-08-10', '2026-06-29'),
(451, 207, 'Tuesday', '14:00:00', '15:00:00', '2026-08-10', '2026-06-29'),
(452, 207, 'Monday', '15:00:00', '16:00:00', '2026-08-10', '2026-06-29'),
(453, 207, 'Friday', '15:00:00', '16:00:00', '2026-08-10', '2026-06-29'),
(454, 207, 'Tuesday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(455, 207, 'Friday', '16:00:00', '17:00:00', '2026-08-10', '2026-06-29'),
(456, 207, 'Thursday', '17:00:00', '18:00:00', '2026-08-10', '2026-06-29'),
(457, 207, 'Friday', '17:00:00', '18:00:00', '2026-08-10', '2026-06-29');

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
(10000000, 'IDRIS BIN SHAH NAHAR', 'idris.shah.nahar@student.mmu.edu.my', '$2y$10$5a8dT.LbgwR.2.0ytA9NY.BZC/9Gt0pJzj07keaF8SWmZja4Kydau', 'Student', '01164425881', 2, NULL, NULL, 1, 'Active', NULL),
(10000001, 'ALEESYA ZAARA BINTI EDI ZULKARNAIN\r\n\r\n', 'aleesya@student.mmu.edu.my', '$2y$10$3uFp8n0THCm1t5NSq1zgy.RSYz06h4dognjKLmrntOu8zdtoTKi..', 'Student', '01154067900', 2, NULL, NULL, 1, 'Active', NULL),
(10000002, 'SURIYA', 'suriya@student.mmu.edu.my', '$2y$10$bjm8sscxTfQDwzazBzmKC..GJDS4Soo2nWwfhPTQEtJMDzft3vui.', 'Student', '0123211695', 2, NULL, NULL, 1, 'Active', NULL),
(10000003, 'NOR IDAYU BINTI AHMAD AZAMI', 'idayu.azami@mmu.edu.my', '$2y$10$q7CSZJ1ZhRfO.wrkRQhv0euy1DCWGK55/hM0rsT2DNqjOImN3htg2', 'Lecturer', '0112223333', 2, NULL, NULL, 1, 'Active', NULL),
(10000004, 'MR ADMIN', 'admin1@mmu.edu.my', '$2y$10$qftMOyhSssrVZDjmUPjIwuauIL67zHORgCvXQyA0mooUg5AjMpB6C', 'Admin', '0112223333', 0, NULL, NULL, 1, 'Active', NULL),
(10000006, 'MS ADMIN', 'admin2@mmu.edu.my', '$2y$10$dOIn8ITc2Ngf9ZvgqixUjeWf/8He/lAQExPVyEyZdYMl615bHEDtC', 'Admin', '0123211695', 0, NULL, NULL, 1, 'Active', NULL);

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
  MODIFY `annoucement_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Announcement Identification', AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Booking Identification', AUTO_INCREMENT=1084;

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `equip_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Equipment Identification', AUTO_INCREMENT=1039;

--
-- AUTO_INCREMENT for table `facility`
--
ALTER TABLE `facility`
  MODIFY `facility_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Facility Identification ', AUTO_INCREMENT=208;

--
-- AUTO_INCREMENT for table `issue_report`
--
ALTER TABLE `issue_report`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Report Identification', AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `notification`
--
ALTER TABLE `notification`
  MODIFY `notify_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Notification Identification', AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `penalty`
--
ALTER TABLE `penalty`
  MODIFY `penalty_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Penalty Identification', AUTO_INCREMENT=100022;

--
-- AUTO_INCREMENT for table `timetable`
--
ALTER TABLE `timetable`
  MODIFY `timetable_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Timetable Identification', AUTO_INCREMENT=458;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'User Identification', AUTO_INCREMENT=10000010;

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
