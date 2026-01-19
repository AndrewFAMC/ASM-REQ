-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 15, 2025 at 05:20 AM
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
-- Database: `hcc_asset_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `asset_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `performed_by` varchar(255) DEFAULT 'Administrator',
  `campus_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `asset_id`, `action`, `description`, `performed_by`, `campus_id`, `created_at`) VALUES
(341, 41, 'CREATED', 'Asset \'Chair\' registered in the system', 'Conception HCC', 2, '2025-10-15 02:24:27'),
(342, 42, 'CREATED', 'Asset \'Table\' registered in the system', 'Sta Rosa HCC', 1, '2025-10-15 02:25:00');

-- --------------------------------------------------------

--
-- Table structure for table `assets`
--

CREATE TABLE `assets` (
  `id` int(11) NOT NULL,
  `asset_name` varchar(255) NOT NULL,
  `category_id` int(11) NOT NULL,
  `status` enum('Active','Inactive','Maintenance','Retired') DEFAULT 'Active',
  `campus_id` int(11) NOT NULL,
  `location` varchar(255) NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `purchase_date` date NOT NULL,
  `value` decimal(15,2) NOT NULL,
  `inventory_date` date DEFAULT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `location_row` varchar(100) DEFAULT NULL,
  `location_section` varchar(100) DEFAULT NULL,
  `location_floor` varchar(100) DEFAULT NULL,
  `size` varchar(100) DEFAULT NULL,
  `article` varchar(255) DEFAULT NULL,
  `counted_by` varchar(255) DEFAULT NULL,
  `checked_by` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `serial_number` varchar(100) DEFAULT NULL,
  `barcode` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `assigned_to` varchar(255) DEFAULT NULL,
  `assigned_email` varchar(255) DEFAULT NULL,
  `assignment_date` date DEFAULT NULL,
  `unassigned_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `assets`
--

INSERT INTO `assets` (`id`, `asset_name`, `category_id`, `status`, `campus_id`, `location`, `room_id`, `purchase_date`, `value`, `quantity`, `serial_number`, `barcode`, `description`, `assigned_to`, `assigned_email`, `assignment_date`, `unassigned_date`, `created_at`, `updated_at`, `created_by`) VALUES
(41, 'Chair', 2, 'Active', 2, 'Canteen', NULL, '2025-10-15', 200.00, 10, '002202500012', '002202500012', NULL, NULL, NULL, NULL, NULL, '2025-10-15 02:24:27', '2025-10-15 02:24:27', 38),
(42, 'Table', 2, 'Active', 1, 'Library', NULL, '2025-10-15', 200.00, 2, '001202500428', '001202500428', NULL, NULL, NULL, NULL, NULL, '2025-10-15 02:25:00', '2025-10-15 02:25:00', 37);

--
-- Triggers `assets`
--
DELIMITER $$
CREATE TRIGGER `update_asset_assignment_history` AFTER UPDATE ON `assets` FOR EACH ROW BEGIN
    -- If assignment changed
    IF (OLD.assigned_to IS NULL AND NEW.assigned_to IS NOT NULL) OR
       (OLD.assigned_to IS NOT NULL AND NEW.assigned_to IS NULL) OR
       (OLD.assigned_to != NEW.assigned_to) THEN

        -- Close previous assignment if exists
        UPDATE asset_assignments
        SET unassigned_date = CURDATE()
        WHERE asset_id = NEW.id AND unassigned_date IS NULL;

        -- Create new assignment if assigned
        IF NEW.assigned_to IS NOT NULL THEN
            INSERT INTO asset_assignments (asset_id, assigned_to, assigned_email, assignment_date)
            VALUES (NEW.id, NEW.assigned_to, NEW.assigned_email, COALESCE(NEW.assignment_date, CURDATE()));
        END IF;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `asset_assignments`
--

CREATE TABLE `asset_assignments` (
  `id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `assigned_to` varchar(255) NOT NULL,
  `assigned_email` varchar(255) NOT NULL,
  `assigned_by` varchar(255) DEFAULT 'Administrator',
  `assignment_date` date NOT NULL,
  `unassigned_date` date DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asset_borrowings`
--

CREATE TABLE `asset_borrowings` (
  `id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `borrower_name` varchar(255) NOT NULL,
  `borrower_type` enum('Teacher','Student') NOT NULL,
  `borrower_contact` varchar(255) DEFAULT NULL,
  `expected_return_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `borrowed_date` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','returned') NOT NULL DEFAULT 'active',
  `return_date` datetime DEFAULT NULL,
  `return_notes` text DEFAULT NULL,
  `recorded_by` varchar(255) DEFAULT 'Staff User',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `asset_details`
-- (See below for the actual view)
--
CREATE TABLE `asset_details` (
`id` int(11)
,`asset_name` varchar(255)
,`category_name` varchar(50)
,`status` enum('Active','Inactive','Maintenance','Retired')
,`campus_code` varchar(10)
,`campus_name` varchar(100)
,`location` varchar(255)
,`purchase_date` date
,`value` decimal(15,2)
,`serial_number` varchar(100)
,`assigned_to` varchar(255)
,`assigned_email` varchar(255)
,`assignment_date` date
,`created_at` timestamp
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `asset_maintenance`
--

CREATE TABLE `asset_maintenance` (
  `id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `maintenance_type` enum('Cleaning','Repair','Inspection','Calibration','Other') NOT NULL,
  `description` text NOT NULL,
  `performed_by` varchar(255) DEFAULT 'Custodian',
  `status` enum('Pending','In Progress','Completed','Cancelled') DEFAULT 'Completed',
  `maintenance_date` date NOT NULL,
  `cost` decimal(10,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asset_names`
--

CREATE TABLE `asset_names` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `asset_names`
--

INSERT INTO `asset_names` (`id`, `name`) VALUES
(1, 'Chair'),
(2, 'Table');

-- --------------------------------------------------------

--
-- Table structure for table `asset_scans`
--

CREATE TABLE `asset_scans` (
  `id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `scan_type` enum('Status Check','Maintenance','Inventory','Location Update') NOT NULL,
  `scanned_by` varchar(255) DEFAULT 'Custodian',
  `scan_location` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buildings`
--

CREATE TABLE `buildings` (
  `id` int(11) NOT NULL,
  `building_name` varchar(100) NOT NULL,
  `campus_id` int(11) NOT NULL,
  `building_code` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `buildings`
--

INSERT INTO `buildings` (`id`, `building_name`, `campus_id`, `building_code`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Main Building', 1, NULL, NULL, '2025-10-15 02:44:49', '2025-10-15 02:44:49'),
(2, 'High School Building', 1, NULL, NULL, '2025-10-15 02:44:49', '2025-10-15 02:44:49'),
(3, 'Latest Building', 1, NULL, NULL, '2025-10-15 02:44:49', '2025-10-15 02:44:49'),
(4, 'Sta. Ines Building', 1, NULL, NULL, '2025-10-15 02:44:49', '2025-10-15 02:44:49'),
(5, 'New Building', 1, NULL, NULL, '2025-10-15 02:44:49', '2025-10-15 02:44:49');

-- --------------------------------------------------------

--
-- Table structure for table `campuses`
--

CREATE TABLE `campuses` (
  `id` int(11) NOT NULL,
  `campus_code` varchar(10) NOT NULL,
  `campus_name` varchar(100) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `campuses`
--

INSERT INTO `campuses` (`id`, `campus_code`, `campus_name`, `location`, `created_at`) VALUES
(1, 'main', 'Sta. Rosa, Nueva Ecija', 'Sta. Rosa, Nueva Ecija, Philippines', '2025-10-04 09:40:43'),
(2, 'north', 'Conception, Tarlac', 'Conception, Tarlac, Philippines', '2025-10-04 09:40:43');

-- --------------------------------------------------------

--
-- Stand-in structure for view `campus_statistics`
-- (See below for the actual view)
--
CREATE TABLE `campus_statistics` (
`campus_code` varchar(10)
,`campus_name` varchar(100)
,`total_assets` bigint(21)
,`active_assets` decimal(22,0)
,`maintenance_assets` decimal(22,0)
,`assigned_assets` decimal(22,0)
,`total_value` decimal(37,2)
,`average_value` decimal(19,6)
);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `category_name`, `description`, `created_at`) VALUES
(1, 'Electronics', 'Electronic devices and equipment', '2025-10-04 09:40:43'),
(2, 'Furniture', 'Office and classroom furniture', '2025-10-04 09:40:43'),
(3, 'Laboratory Equipment', 'Scientific and educational lab equipment', '2025-10-04 09:40:43'),
(4, 'Sports Equipment', 'Athletic and recreational equipment', '2025-10-04 09:40:43'),
(5, 'Vehicles', 'Transportation vehicles', '2025-10-04 09:40:43'),
(6, 'Books & Materials', 'Educational books and materials', '2025-10-04 09:40:43'),
(7, 'Software', 'Software licenses and digital assets', '2025-10-04 09:40:43'),
(8, 'Appliance', 'Miscellaneous assets', '2025-10-04 09:40:43');

-- --------------------------------------------------------

--
-- Table structure for table `email_verifications`
--

CREATE TABLE `email_verifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `verification_token` varchar(128) NOT NULL,
  `email` varchar(100) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

CREATE TABLE `inventory_items` (
  `id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `scanned_at` datetime NOT NULL,
  `location_found` varchar(255) DEFAULT NULL,
  `condition_notes` text DEFAULT NULL,
  `discrepancy` enum('None','Wrong Location','Damaged','Missing') DEFAULT 'None',
  `scanned_by` varchar(255) DEFAULT 'Custodian',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_sessions`
--

CREATE TABLE `inventory_sessions` (
  `id` int(11) NOT NULL,
  `session_name` varchar(255) NOT NULL,
  `campus_id` int(11) NOT NULL,
  `started_by` varchar(255) DEFAULT 'Custodian',
  `start_date` datetime NOT NULL,
  `end_date` datetime DEFAULT NULL,
  `status` enum('Active','Completed','Cancelled') DEFAULT 'Active',
  `total_expected` int(11) DEFAULT 0,
  `total_scanned` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_sessions`
--

INSERT INTO `inventory_sessions` (`id`, `session_name`, `campus_id`, `started_by`, `start_date`, `end_date`, `status`, `total_expected`, `total_scanned`, `notes`, `created_at`) VALUES
(1, 'Class Start', 1, 'Custodian', '2025-10-05 22:26:14', '2025-10-05 22:27:54', 'Completed', 1, 1, '\nCompleted: l', '2025-10-05 14:26:14'),
(2, 'testing', 1, 'Custodian', '2025-10-05 22:28:03', '2025-10-05 22:28:23', 'Completed', 1, 1, '\nCompleted: ;l', '2025-10-05 14:28:03'),
(3, 'testing', 2, 'Custodian', '2025-10-06 07:39:45', '2025-10-08 08:54:40', 'Completed', 4, 1, '\nCompleted: yt', '2025-10-05 23:39:45'),
(4, 'sample', 1, 'Custodian', '2025-10-06 11:55:33', '2025-10-06 11:56:24', 'Completed', 2, 2, '\nCompleted: jkgfvb', '2025-10-06 03:55:33'),
(5, 'sample', 2, 'Custodian', '2025-10-08 08:54:55', '2025-10-08 08:57:22', 'Completed', 5, 0, 'fne\nCompleted: dfs', '2025-10-08 00:54:55'),
(6, 'sample', 2, 'Custodian', '2025-10-08 08:57:34', NULL, 'Active', 6, 0, 'jabjkbeafukjqe', '2025-10-08 00:57:34'),
(7, 'sample', 1, 'Custodian', '2025-10-11 15:25:33', NULL, 'Active', 1, 0, '', '2025-10-11 07:25:33');

-- --------------------------------------------------------

--
-- Table structure for table `it_support_users`
--

CREATE TABLE `it_support_users` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` int(11) NOT NULL,
  `building_id` int(11) NOT NULL,
  `room_name` varchar(255) NOT NULL,
  `floor` tinyint(4) NOT NULL DEFAULT 1,
  `code` varchar(50) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`id`, `building_id`, `room_name`, `floor`, `code`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Kinder Garten', 1, 'MB-KG', 1, '2025-10-15 02:46:55', '2025-10-15 02:46:55'),
(2, 1, 'Library', 1, 'MB-LIB', 1, '2025-10-15 02:46:55', '2025-10-15 02:46:55'),
(3, 1, 'Registrar Office', 1, 'MB-REG', 1, '2025-10-15 02:46:55', '2025-10-15 02:46:55'),
(4, 1, 'Audio Visual Room', 1, 'MB-AVR', 1, '2025-10-15 02:46:55', '2025-10-15 02:46:55'),
(5, 1, 'Administration Office', 1, 'MB-ADMIN', 1, '2025-10-15 02:46:55', '2025-10-15 02:46:55'),
(6, 1, 'Storage Room', 1, 'MB-STO', 1, '2025-10-15 02:46:55', '2025-10-15 02:46:55'),
(7, 2, 'CM 105', 1, 'HSB-CM105', 1, '2025-10-15 02:46:55', '2025-10-15 02:46:55'),
(8, 2, 'CM 104', 1, 'HSB-CM104', 1, '2025-10-15 02:46:55', '2025-10-15 02:46:55'),
(9, 2, 'CM 103', 1, 'HSB-CM103', 1, '2025-10-15 02:46:55', '2025-10-15 02:46:55'),
(10, 2, 'Faculty Room', 1, 'HSB-FACULTY', 1, '2025-10-15 02:46:55', '2025-10-15 02:46:55'),
(11, 2, 'Accreditation Room', 1, 'HSB-ACCRED', 1, '2025-10-15 02:46:55', '2025-10-15 02:46:55'),
(12, 2, 'Lounge Room', 1, 'HSB-LOUNGE', 1, '2025-10-15 02:46:55', '2025-10-15 02:46:55'),
(13, 3, 'CL 104', 1, 'LB-CL104', 1, '2025-10-15 02:46:55', '2025-10-15 02:46:55'),
(14, 3, 'CL 103', 1, 'LB-CL103', 1, '2025-10-15 02:46:55', '2025-10-15 02:46:55'),
(15, 3, 'CL 102', 1, 'LB-CL102', 1, '2025-10-15 02:46:55', '2025-10-15 02:46:55'),
(16, 3, 'CL 101', 1, 'LB-CL101', 1, '2025-10-15 02:46:55', '2025-10-15 02:46:55'),
(17, 4, 'SI 101', 1, 'SIB-SI101', 1, '2025-10-15 02:46:55', '2025-10-15 02:46:55'),
(18, 4, 'SI 102', 1, 'SIB-SI102', 1, '2025-10-15 02:46:55', '2025-10-15 02:46:55'),
(19, 4, 'SI 103', 1, 'SIB-SI103', 1, '2025-10-15 02:46:55', '2025-10-15 02:46:55'),
(20, 4, 'SI 104', 1, 'SIB-SI104', 1, '2025-10-15 02:46:55', '2025-10-15 02:46:55'),
(21, 4, 'SI 105', 1, 'SIB-SI105', 1, '2025-10-15 02:46:55', '2025-10-15 02:46:55'),
(22, 5, 'Canteen', 1, 'NB-CANTEEN', 1, '2025-10-15 02:46:55', '2025-10-15 02:46:55');

--
-- Standardize Sta. Rosa Campus Locations (Campus ID = 1)
--

-- Step 1: Delete old, incorrect locations for Sta. Rosa Campus
DELETE FROM `locations` WHERE `building_id` IN (SELECT id FROM `buildings` WHERE campus_id = 1);

-- Step 2: Insert the new, standardized list of 23 valid rooms
INSERT INTO `locations` (`building_id`, `room_name`, `floor`, `code`, `is_active`) VALUES
(2, 'CM-105', 1, 'HSB-CM105', 1),
(2, 'CM-104', 1, 'HSB-CM104', 1),
(2, 'CM-103', 1, 'HSB-CM103', 1),
(2, 'FACULTY ROOM', 1, 'HSB-FACULTY', 1),
(2, 'ACCREDITATION ROOM', 1, 'HSB-ACCRED', 1),
(2, 'LOUNGE AREA', 1, 'HSB-LOUNGE', 1),
(3, 'CL-104', 1, 'LB-CL104', 1),
(3, 'CL-103', 1, 'LB-CL103', 1),
(3, 'CL-102', 1, 'LB-CL102', 1),
(3, 'CL-101', 1, 'LB-CL101', 1),
(1, 'KINDER GARTEN ROOM', 1, 'MB-KG', 1),
(1, 'LIBRARY', 1, 'MB-LIB', 1),
(1, 'REGISTRAR’S OFFICE', 1, 'MB-REG', 1),
(1, 'AUDIO-VISUAL ROOM', 1, 'MB-AVR', 1),
(1, 'ADMINISTRATION OFFICE', 1, 'MB-ADMIN', 1),
(1, 'STORAGE ROOM', 1, 'MB-STO', 1),
(4, 'SI-101', 1, 'SIB-SI101', 1),
(4, 'SI-102', 1, 'SIB-SI102', 1),
(4, 'SI-103', 1, 'SIB-SI103', 1),
(4, 'SI-104', 1, 'SIB-SI104', 1),
(4, 'SI-105', 1, 'SIB-SI105', 1),
(4, 'SI-106', 1, 'SIB-SI106', 1),
(5, 'CANTEEN', 1, 'NB-CANTEEN', 1);

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `success` tinyint(1) NOT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `username`, `ip_address`, `success`, `attempted_at`) VALUES
(1, 'mico.macapugay@icloud.com', '::1', 0, '2025-10-04 09:43:22'),
(2, 'mico.macapugay2004@gmail.com', '::1', 1, '2025-10-04 09:47:24'),
(3, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-04 09:48:18'),
(4, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-04 09:55:23'),
(5, 'mico.macapugay2004@gmail.com', '::1', 1, '2025-10-04 09:58:48'),
(6, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-04 09:59:09'),
(7, 'mico.macapugay2004@gmail.com', '::1', 1, '2025-10-04 10:09:27'),
(8, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-04 10:09:49'),
(9, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-04 11:13:14'),
(10, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-04 11:22:21'),
(11, 'mico.macapugay2004@gmail.com', '::1', 1, '2025-10-04 11:40:30'),
(12, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-04 11:40:55'),
(13, 'mico.macapugay@icloud.com', '::1', 0, '2025-10-04 11:43:51'),
(14, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-04 11:43:56'),
(15, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-05 00:39:53'),
(16, 'mico.macapugay2004@gmail.com', '::1', 1, '2025-10-05 02:44:59'),
(17, 'mico.macapugay2004@gmail.com', '::1', 1, '2025-10-05 02:45:24'),
(18, 'Micole@gmail.com', '::1', 1, '2025-10-05 03:09:53'),
(19, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-05 04:15:04'),
(20, 'Micole@gmail.com', '::1', 1, '2025-10-05 04:29:44'),
(21, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-05 04:57:34'),
(22, 'Micole@gmail.com', '::1', 1, '2025-10-05 08:52:16'),
(23, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-05 10:14:16'),
(24, 'Micole@gmail.com', '::1', 1, '2025-10-05 10:15:20'),
(25, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-05 12:29:33'),
(26, 'Micole@gmail.com', '::1', 1, '2025-10-05 13:18:25'),
(27, 'Micole@gmail.com', '::1', 1, '2025-10-05 14:30:54'),
(28, 'mico.macapugay2004@gmail.com', '::1', 1, '2025-10-05 14:31:22'),
(29, 'Micole@gmail.com', '::1', 1, '2025-10-05 14:34:04'),
(30, 'Carlo@gmail.com', '::1', 1, '2025-10-05 14:39:40'),
(31, 'Micole@gmail.com', '::1', 1, '2025-10-05 14:40:22'),
(32, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-05 14:41:07'),
(33, 'carlogabriel@gmail.com', '::1', 1, '2025-10-05 14:42:04'),
(34, 'Micole@gmail.com', '::1', 1, '2025-10-05 22:40:53'),
(35, 'carlogabriel@gmail.com', '::1', 1, '2025-10-05 22:44:17'),
(36, 'carlogabriel@gmail.com', '::1', 1, '2025-10-06 03:26:32'),
(37, 'Micole@gmail.com', '::1', 1, '2025-10-06 03:26:44'),
(38, 'Micole@gmail.com', '::1', 1, '2025-10-06 03:32:19'),
(39, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-06 03:33:48'),
(40, 'Micole@gmail.com', '::1', 0, '2025-10-06 03:48:59'),
(41, 'Micole@gmail.com', '::1', 1, '2025-10-06 03:49:08'),
(42, 'Micole@gmail.com', '::1', 1, '2025-10-06 11:54:53'),
(43, 'Micole@gmail.com', '::1', 1, '2025-10-06 15:34:07'),
(44, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-06 22:58:14'),
(45, 'mico.macapugay2004@gmail.com', '::1', 1, '2025-10-06 22:58:37'),
(46, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-06 23:19:04'),
(47, 'Micole@gmail.com', '::1', 1, '2025-10-07 14:12:29'),
(48, 'Micole@gmail.com', '::1', 1, '2025-10-07 14:14:33'),
(49, 'mico.macapugay2004@gmail.com', '::1', 1, '2025-10-07 14:17:01'),
(50, 'Micole@gmail.com', '::1', 1, '2025-10-07 14:17:26'),
(51, 'mico.macapugay2004@gmail.com', '::1', 1, '2025-10-07 14:48:32'),
(52, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-07 14:49:36'),
(53, 'mico.macapugay2004@gmail.com', '::1', 1, '2025-10-07 14:57:53'),
(54, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-07 15:04:09'),
(55, 'Micole@gmail.com', '::1', 1, '2025-10-07 15:13:30'),
(56, 'Micole@gmail.com', '::1', 1, '2025-10-07 22:08:03'),
(57, 'mico.macapugay2004@gmail.com', '::1', 1, '2025-10-07 22:09:13'),
(58, 'carlogabriel@gmail.com', '::1', 1, '2025-10-07 23:54:46'),
(59, 'Micole@gmail.com', '::1', 1, '2025-10-07 23:55:05'),
(60, 'Micole@gmail.com', '::1', 1, '2025-10-08 00:21:00'),
(61, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-08 00:21:26'),
(62, 'Micole@gmail.com', '::1', 1, '2025-10-08 00:25:43'),
(63, 'Micole@gmail.com', '::1', 1, '2025-10-08 00:26:00'),
(64, 'carlogabriel@gmail.com', '::1', 1, '2025-10-08 00:26:18'),
(65, 'carlogabriel@gmail.com', '::1', 0, '2025-10-08 00:27:35'),
(66, 'carlogabriel@gmail.com', '::1', 0, '2025-10-08 00:27:44'),
(67, 'carlogabriel@gmail.com', '::1', 1, '2025-10-08 00:27:57'),
(68, 'Richard@gmail.com', '::1', 1, '2025-10-08 00:29:57'),
(69, 'carlogabriel@gmail.com', '::1', 1, '2025-10-08 00:30:25'),
(70, 'carlogabriel@gmail.com', '::1', 1, '2025-10-08 00:47:56'),
(71, 'Ricahrd@gmail.com', '::1', 0, '2025-10-08 00:53:03'),
(72, 'Richard@gmail.com', '::1', 1, '2025-10-08 00:53:21'),
(73, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-08 01:06:47'),
(74, 'jena@gmail.com', '::1', 1, '2025-10-08 01:17:04'),
(75, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-08 01:52:42'),
(76, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-08 01:53:43'),
(77, 'Micole@gmail.com', '::1', 1, '2025-10-08 01:54:46'),
(78, 'jennamariesolitario@gmail.com', '::1', 0, '2025-10-08 02:09:52'),
(79, 'jennamariesolitario@gmail.com', '::1', 1, '2025-10-08 02:12:09'),
(80, 'solitariojenna@gmail.com', '::1', 1, '2025-10-08 02:19:48'),
(81, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-08 02:23:49'),
(82, 'shainacruz@gmail.com', '::1', 1, '2025-10-08 02:24:25'),
(83, 'carlo27@gmail.com', '::1', 1, '2025-10-08 02:33:48'),
(84, 'carlogabriel1818@gmail.com', '::1', 0, '2025-10-08 02:34:14'),
(85, 'carlogabriel1818@gmail.com', '::1', 1, '2025-10-08 02:34:23'),
(86, 'carlogabriel@gmail.com', '::1', 1, '2025-10-08 02:35:08'),
(87, 'fermin12@gmail.com', '::1', 1, '2025-10-08 02:48:45'),
(88, 'richardfermin30@gmail.com', '::1', 0, '2025-10-08 02:49:35'),
(89, 'richardfermin30@gmail.com', '::1', 1, '2025-10-08 02:49:51'),
(90, 'ichadfermin@gmail.com', '::1', 0, '2025-10-08 02:50:56'),
(91, 'ichadfermin@gmail.com', '::1', 0, '2025-10-08 02:51:18'),
(92, 'ichadfermin@gmail.com', '::1', 1, '2025-10-08 02:51:30'),
(93, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-08 02:53:51'),
(94, 'jennamariesolitario@gmail.com', '::1', 1, '2025-10-08 02:55:40'),
(95, 'fermin12@gmail.com', '::1', 1, '2025-10-08 02:57:12'),
(96, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-08 02:58:04'),
(97, 'fermin12@gmail.com', '::1', 1, '2025-10-08 02:58:56'),
(98, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-08 03:00:22'),
(99, 'fermin12@gmail.com', '::1', 1, '2025-10-08 03:04:57'),
(100, 'shanalyncruz3@gmail.com', '::1', 0, '2025-10-08 03:06:06'),
(101, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-08 03:06:16'),
(102, 'fermin12@gmail.com', '::1', 1, '2025-10-08 03:06:45'),
(103, 'fermin12@gmail.com', '::1', 1, '2025-10-08 03:07:42'),
(104, 'carlouigyugy@gmail.com', '::1', 0, '2025-10-08 03:30:51'),
(105, 'carlogabriel1818@gmail.com', '::1', 0, '2025-10-08 03:32:56'),
(106, 'carlogabriel1818@gmail.com', '::1', 1, '2025-10-08 03:33:06'),
(107, 'carlogabriel@gmail.com', '::1', 1, '2025-10-08 03:35:07'),
(108, 'carlo27@gmail.com', '::1', 1, '2025-10-08 03:39:16'),
(109, '234@gmail.com', '::1', 0, '2025-10-08 03:57:00'),
(110, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-08 03:58:12'),
(111, 'shanalyncruz3@gmail.com', '::1', 0, '2025-10-08 11:43:12'),
(112, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-08 11:43:25'),
(113, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-10 00:02:12'),
(114, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-10 00:05:45'),
(115, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-10 00:14:11'),
(116, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-10 01:14:38'),
(117, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-10 01:39:25'),
(118, 'jennamariesolitario@gmail.com', '::1', 1, '2025-10-10 01:43:45'),
(119, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-10 04:14:02'),
(120, 'Micole@gmail.com', '::1', 0, '2025-10-10 05:59:07'),
(121, 'jennamariesolitario@gmail.com', '::1', 1, '2025-10-10 05:59:19'),
(122, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-10 06:57:21'),
(123, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-10 09:03:44'),
(124, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-10 09:04:21'),
(125, 'jennamariesolitario@gmail.com', '::1', 1, '2025-10-10 09:04:57'),
(126, 'jennamariesolitario@gmail.com', '::1', 0, '2025-10-10 09:11:57'),
(127, 'jennamariesolitario@gmail.com', '::1', 1, '2025-10-10 09:12:02'),
(128, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-10 09:12:58'),
(129, 'carlogabriel@gmail.coma', '::1', 0, '2025-10-10 10:39:47'),
(130, 'carlogabriel@gmail.com', '::1', 1, '2025-10-10 10:39:55'),
(131, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-10 11:16:05'),
(132, 'richardfermin30@gmail.com', '::1', 1, '2025-10-10 11:36:59'),
(133, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-10 11:37:51'),
(134, 'carlogabriel@gmail.com', '::1', 1, '2025-10-10 12:30:06'),
(135, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-10 22:21:56'),
(136, 'Micole@gmail.com', '::1', 1, '2025-10-10 22:22:48'),
(137, 'jennamariesolitario@gmail.com', '::1', 1, '2025-10-10 22:23:50'),
(138, 'carlogabriel@gmail.com', '::1', 1, '2025-10-10 22:26:57'),
(139, 'jennamariesolitario@gmail.com', '::1', 1, '2025-10-10 22:34:17'),
(140, 'shainacruz@gmail.com', '::1', 1, '2025-10-11 04:19:07'),
(141, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-11 04:45:00'),
(142, 'richardfermin30@gmail.com', '::1', 0, '2025-10-11 04:47:44'),
(143, 'richardfermin30@gmail.com', '::1', 0, '2025-10-11 04:47:51'),
(144, 'fermin12@gmail.com', '::1', 1, '2025-10-11 04:48:06'),
(145, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-11 04:55:26'),
(146, 'jennamariesolitario@gmail.com', '::1', 1, '2025-10-11 05:25:13'),
(147, 'itadmin', '::1', 1, '2025-10-11 05:27:47'),
(148, 'fermin12@gmail.com', '::1', 1, '2025-10-11 05:30:00'),
(149, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-11 05:31:04'),
(150, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-11 05:38:31'),
(151, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-11 05:40:33'),
(152, 'ithccasset@gmail.com', '::1', 0, '2025-10-11 05:41:32'),
(153, 'ithccasset@gmail.com', '::1', 0, '2025-10-11 05:41:44'),
(154, 'ithccasset@gmail.com', '::1', 0, '2025-10-11 05:42:15'),
(155, 'ithccasset@gmail.com', '::1', 0, '2025-10-11 05:45:47'),
(156, 'ithccasset@gmail.com', '::1', 0, '2025-10-11 05:45:53'),
(157, 'itadmin', '::1', 0, '2025-10-11 05:46:24'),
(158, 'shainalyncruz3@gmail.com', '::1', 0, '2025-10-11 05:46:47'),
(159, 'itadmin', '::1', 0, '2025-10-11 05:57:54'),
(160, 'ithccasset@gmail.com', '::1', 0, '2025-10-11 05:58:19'),
(161, 'ithccasset@gmail.com', '::1', 0, '2025-10-11 05:58:30'),
(162, 'ithccasset@gmail.com', '::1', 0, '2025-10-11 05:59:43'),
(163, 'ithccasset@gmail.com', '::1', 0, '2025-10-11 05:59:53'),
(164, 'itadmin@hcc.edu.ph', '::1', 0, '2025-10-11 06:00:23'),
(165, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-11 06:08:22'),
(166, 'shainalyncruz3@gmail.com', '::1', 0, '2025-10-11 06:09:31'),
(167, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-11 06:09:36'),
(168, 'fermin12@gmail.com', '::1', 0, '2025-10-11 06:09:54'),
(169, 'fermin12@gmail.com', '::1', 1, '2025-10-11 06:10:00'),
(170, 'cruzshaina@gmail.com', '::1', 1, '2025-10-11 06:11:22'),
(171, 'fermin12@gmail.com', '::1', 1, '2025-10-11 06:25:17'),
(172, 'carlo27@gmail.com', '::1', 1, '2025-10-11 06:31:55'),
(173, 'carlogabriel1818@gmail.com', '::1', 1, '2025-10-11 06:33:17'),
(174, 'carlogabriel1818@gmail.com', '::1', 1, '2025-10-11 06:57:22'),
(175, 'fermin12@gmail.com', '::1', 1, '2025-10-11 07:24:09'),
(176, 'carlogabriel1818@gmail.com', '::1', 1, '2025-10-11 07:26:29'),
(177, 'carlogabriel1818@gmail.com', '::1', 1, '2025-10-11 08:00:00'),
(178, 'fermin12@gmail.com', '::1', 0, '2025-10-11 08:14:20'),
(179, 'fermin12@gmail.com', '::1', 1, '2025-10-11 08:14:26'),
(180, 'carlogabriel1818@gmail.com', '::1', 0, '2025-10-11 13:02:41'),
(181, 'carlogabriel1818@gmail.com', '::1', 1, '2025-10-11 13:02:47'),
(182, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-11 13:10:05'),
(183, 'carlogabriel1818@gmail.com', '::1', 1, '2025-10-11 13:24:03'),
(184, 'fermin12@gmail.com', '::1', 1, '2025-10-11 13:36:07'),
(185, 'carlogabriel1818@gmail.com', '::1', 0, '2025-10-11 13:38:51'),
(186, 'carlogabriel1818@gmail.com', '::1', 1, '2025-10-11 13:38:56'),
(187, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-11 13:39:40'),
(188, 'carlogabriel1818@gmail.com', '::1', 1, '2025-10-11 13:41:53'),
(189, 'fermin12@gmail.com', '::1', 1, '2025-10-11 23:08:08'),
(190, 'carlogabriel1818@gmail.com', '::1', 1, '2025-10-11 23:47:20'),
(191, 'fermin12@gmail.com', '::1', 0, '2025-10-11 23:48:38'),
(192, 'fermin12@gmail.com', '::1', 1, '2025-10-11 23:48:44'),
(193, 'carlogabriel1818@gmail.com', '::1', 1, '2025-10-12 00:09:09'),
(194, 'fermin12@gmail.com', '::1', 0, '2025-10-12 00:10:00'),
(195, 'fermin12@gmail.com', '::1', 1, '2025-10-12 00:10:05'),
(196, 'carlogabriel1818@gmail.com', '::1', 1, '2025-10-12 00:50:47'),
(197, 'fermin12@gmail.com', '::1', 1, '2025-10-12 00:53:37'),
(198, 'carlogabriel1818@gmail.com', '::1', 1, '2025-10-12 01:55:22'),
(199, 'fermin12@gmail.com', '::1', 1, '2025-10-12 01:55:44'),
(200, 'carlogabriel1818@gmail.com', '::1', 1, '2025-10-12 02:14:14'),
(201, 'fermin12@gmail.com', '::1', 1, '2025-10-12 02:18:41'),
(202, 'carlogabriel1818@gmail.com', '::1', 1, '2025-10-12 02:43:23'),
(203, 'fermin12@gmail.com', '::1', 1, '2025-10-12 02:44:06'),
(204, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-12 04:21:29'),
(205, 'carlogabriel1818@gmail.com', '::1', 1, '2025-10-12 04:52:17'),
(206, 'fermin12@gmail.com', '::1', 1, '2025-10-12 04:55:09'),
(207, 'carlogabriel1818@gmail.com', '::1', 1, '2025-10-12 04:55:48'),
(208, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-12 05:03:48'),
(209, 'carlogabriel1818@gmail.com', '::1', 1, '2025-10-12 06:32:50'),
(210, 'fermin12@gmail.com', '::1', 1, '2025-10-12 06:34:35'),
(211, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-12 06:35:19'),
(212, 'carlogabriel1818@gmail.com', '::1', 1, '2025-10-12 07:01:10'),
(213, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-12 07:01:29'),
(214, 'fermin12@gmail.com', '::1', 1, '2025-10-12 08:06:14'),
(215, 'carlogabriel1818@gmail.com', '::1', 1, '2025-10-12 08:07:14'),
(216, 'shainalyncruz3@gmail.com', '::1', 0, '2025-10-12 08:09:14'),
(217, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-12 08:09:20'),
(218, 'carlogabriel1818@gmail.com', '::1', 1, '2025-10-12 08:33:57'),
(219, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-12 08:34:54'),
(220, 'fermin12@gmail.com', '::1', 0, '2025-10-12 08:50:42'),
(221, 'fermin12@gmail.com', '::1', 1, '2025-10-12 08:50:48'),
(222, 'carlogabriel1818@gmail.com', '::1', 1, '2025-10-12 09:03:32'),
(223, 'fermin12@gmail.com', '::1', 1, '2025-10-12 09:06:20'),
(224, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-12 09:07:25'),
(225, 'macapugaymicole65@gmail.com', '::1', 1, '2025-10-12 09:08:20'),
(226, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-12 10:46:00'),
(227, 'macapugaymicole65@gmail.com', '::1', 0, '2025-10-12 11:06:37'),
(228, 'macapugaymicole65@gmail.com', '::1', 1, '2025-10-12 11:06:41'),
(229, 'shainalyncruz3@gmail.com', '::1', 0, '2025-10-12 11:07:51'),
(230, 'shainalyncruz3@gmail.com', '::1', 0, '2025-10-12 11:07:58'),
(231, 'shainalyncruz3@gmail.com', '::1', 0, '2025-10-12 11:08:04'),
(232, 'shainalyncruz3@gmail.com', '::1', 0, '2025-10-12 11:08:17'),
(233, 'jennamariesolitario@gmail.com', '::1', 0, '2025-10-12 11:08:43'),
(234, 'jennamariesolitario@gmail.com', '::1', 0, '2025-10-12 11:08:48'),
(235, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-12 11:09:24'),
(236, 'macapugaymicole65@gmail.com', '::1', 0, '2025-10-12 11:46:47'),
(237, 'macapugaymicole65@gmail.com', '::1', 1, '2025-10-12 11:46:52'),
(238, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-12 14:13:21'),
(239, 'macapugaymicole65@gmail.com', '::1', 0, '2025-10-12 14:14:30'),
(240, 'macapugaymicole65@gmail.com', '::1', 0, '2025-10-12 14:14:30'),
(241, 'macapugaymicole65@gmail.com', '::1', 0, '2025-10-12 14:14:35'),
(242, 'macapugaymicole65@gmail.com', '::1', 0, '2025-10-12 14:14:40'),
(243, 'macapugaymicole65@gmail.com', '::1', 0, '2025-10-12 14:14:46'),
(244, 'macapugaymicole65@gmail.com', '::1', 0, '2025-10-12 14:14:52'),
(245, 'macapugaymicole65@gmail.com', '::1', 1, '2025-10-12 14:14:57'),
(246, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-12 14:53:04'),
(247, 'annapaulamendoza@gmail.com', '::1', 1, '2025-10-12 15:01:08'),
(248, 'carlogabriel1818@gmail.com', '::1', 1, '2025-10-12 15:02:21'),
(249, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-12 15:03:00'),
(250, 'fermin12@gmail.com', '::1', 1, '2025-10-12 15:53:09'),
(251, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-12 16:35:33'),
(252, 'carlogabriel1818@gmail.com', '::1', 1, '2025-10-12 17:00:23'),
(253, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-12 17:02:11'),
(254, 'carlogabriel1818@gmail.com', '::1', 1, '2025-10-12 17:02:39'),
(255, 'fermin12@gmail.com', '::1', 1, '2025-10-13 00:46:57'),
(256, 'carlogabriel1818@gmail.com', '::1', 1, '2025-10-13 00:47:23'),
(257, 'ichadfermin@gmail.com', '::1', 0, '2025-10-13 00:50:17'),
(258, 'ichadfermin@gmail.com', '::1', 0, '2025-10-13 00:50:29'),
(259, 'ichadfermin@gmail.com', '::1', 1, '2025-10-13 00:50:38'),
(260, 'richardfermin30@gmail.com', '::1', 1, '2025-10-13 00:54:18'),
(261, 'carlogabriel1818@gmail.com', '::1', 1, '2025-10-13 13:25:37'),
(262, 'mico.macapugay@icloud.com', '::1', 0, '2025-10-13 13:28:05'),
(263, 'mico.macapugay@icloud.com', '::1', 0, '2025-10-13 13:28:12'),
(264, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-13 13:28:26'),
(265, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-14 08:08:16'),
(266, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-14 10:53:07'),
(267, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-14 10:59:35'),
(268, 'fermin12@gmail.com', '::1', 1, '2025-10-14 11:06:47'),
(269, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-14 11:07:05'),
(270, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-14 11:28:13'),
(271, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-14 12:00:31'),
(272, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-14 12:32:22'),
(273, 'carlogabriel1818@gmail.com', '::1', 1, '2025-10-14 13:13:04'),
(274, 'fermin12@gmail.com', '::1', 0, '2025-10-14 13:23:31'),
(275, 'fermin12@gmail.com', '::1', 1, '2025-10-14 13:23:37'),
(276, 'carlogabriel1818@gmail.com', '::1', 1, '2025-10-14 13:30:49'),
(277, 'fermin12@gmail.com', '::1', 1, '2025-10-14 13:34:56'),
(278, 'carlogabriel1818@gmail.com', '::1', 1, '2025-10-14 13:52:11'),
(279, 'fermin12@gmail.com', '::1', 1, '2025-10-14 13:55:25'),
(280, 'carlogabriel1818@gmail.com', '::1', 1, '2025-10-14 13:56:31'),
(281, 'fermin12@gmail.com', '::1', 1, '2025-10-14 14:02:02'),
(282, 'carlogabriel1818@gmail.com', '::1', 0, '2025-10-14 14:02:49'),
(283, 'carlogabriel1818@gmail.com', '::1', 1, '2025-10-14 14:02:55'),
(284, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-14 14:44:28'),
(285, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-14 23:33:43'),
(286, 'janicerobles@gmail.com', '::1', 1, '2025-10-14 23:37:04'),
(287, 'fermin12@gmail.com', '::1', 1, '2025-10-14 23:40:14'),
(288, 'janicerobles@gmail.com', '::1', 1, '2025-10-14 23:44:32'),
(289, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-15 01:12:55'),
(290, 'shainalyncruz3@gmail.com', '::1', 0, '2025-10-15 01:33:01'),
(291, 'hccstarosa@gmail.com', '::1', 1, '2025-10-15 01:35:02'),
(292, 'hccconception@gmail.com', '::1', 1, '2025-10-15 01:38:06'),
(293, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-15 01:48:13'),
(294, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-15 01:48:29'),
(295, 'hccconception@gmail.com', '::1', 1, '2025-10-15 01:49:31'),
(296, 'hccstarosa@gmail.com', '::1', 1, '2025-10-15 01:50:12'),
(297, 'hccconception@gmail.com', '::1', 1, '2025-10-15 02:13:23'),
(298, 'carlogabriel1818@gmail.com', '::1', 1, '2025-10-15 02:15:42'),
(299, 'hccstarosa@gmail.com', '::1', 1, '2025-10-15 02:16:14'),
(300, 'hccconception@gmail.com', '::1', 1, '2025-10-15 02:18:29'),
(301, 'hccstarosa@gmail.com', '::1', 1, '2025-10-15 02:20:52'),
(302, 'hccconception@gmail.com', '::1', 0, '2025-10-15 02:23:40'),
(303, 'hccconception@gmail.com', '::1', 1, '2025-10-15 02:23:45'),
(304, 'hccstarosa@gmail.com', '::1', 1, '2025-10-15 02:24:41'),
(305, 'hccconception@gmail.com', '::1', 1, '2025-10-15 02:25:12'),
(306, 'hccconception@gmail.com', '::1', 1, '2025-10-15 02:54:02');

-- --------------------------------------------------------

--
-- Table structure for table `offices`
--

CREATE TABLE IF NOT EXISTS `offices` (
  `id` int(11) NOT NULL,
  `office_name` varchar(255) NOT NULL,
  `floor` varchar(50) DEFAULT NULL,
  `section_code` varchar(100) DEFAULT NULL,
  `campus_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_tags`
--

CREATE TABLE `inventory_tags` (
  `id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `office_id` int(11) DEFAULT NULL,
  `tag_number` varchar(100) NOT NULL,
  `inventory_date` date DEFAULT NULL,
  `article` varchar(255) DEFAULT NULL,
  `size` varchar(100) DEFAULT NULL,
  `counted_by` varchar(255) DEFAULT NULL,
  `checked_by` varchar(255) DEFAULT NULL,
  `location_row` varchar(100) DEFAULT NULL,
  `location_section` varchar(100) DEFAULT NULL,
  `location_floor` varchar(100) DEFAULT NULL,
  `status` enum('Pending Verification','Active','Disposed','Transferred') NOT NULL DEFAULT 'Pending Verification',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `assigned_by_custodian_id` int(11) DEFAULT NULL,
  `verified_by_office_id` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reset_token` varchar(128) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `room_name` varchar(100) NOT NULL,
  `building_id` int(11) NOT NULL,
  `room_code` varchar(20) DEFAULT NULL,
  `room_type` enum('classroom','office','laboratory','storage','other') DEFAULT 'other',
  `capacity` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','manager','staff','custodian','office') NOT NULL DEFAULT 'staff',
  `campus_id` int(11) DEFAULT NULL,
  `office_id` int(11) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `force_password_change` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role`, `campus_id`, `profile_picture`, `is_active`, `force_password_change`, `last_login`, `created_at`, `updated_at`) VALUES
(37, 'hccstarosa', 'hccstarosa@gmail.com', '$2y$10$VMqf5c0yu2oArTz7UUL65uyrYrdvrT2B/qtj7KN0nT6vQbFJe1y96', 'Sta Rosa HCC', 'admin', 1, NULL, 1, 0, '2025-10-15 02:24:41', '2025-10-15 01:33:54', '2025-10-15 02:24:41'),
(38, 'hccconception', 'hccconception@gmail.com', '$2y$10$j1qJWoc87Z0KvP97hqEPg.L5gucJsotzCQoJYzkA6rwNv55PAdVyO', 'Conception HCC', 'admin', 2, NULL, 1, 0, '2025-10-15 02:54:02', '2025-10-15 01:34:22', '2025-10-15 02:54:02'),
(39, 'Mico', 'mico.macapugay@icloud.com', '$2y$10$YrHXaTdQuRE8IXkdvX4Y0u62J48wagX8R6umDqzZi5h8uBj1n51xK', 'Mico Macapugay', 'staff', 2, NULL, 1, 0, '2025-10-15 01:48:29', '2025-10-15 01:46:19', '2025-10-15 01:48:50'),
(40, 'Carlo', 'carlogabriel1818@gmail.com', '$2y$10$GOU2Zt7A9KFf2VP4c4IyDOBQKLpUygo5fqE46hRPBZtSgOGg0jCge', 'Carlo Gabriel', 'custodian', 2, NULL, 1, 0, '2025-10-15 02:15:42', '2025-10-15 02:15:09', '2025-10-15 02:15:59');

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_id` varchar(128) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_sessions`
--

INSERT INTO `user_sessions` (`id`, `user_id`, `session_id`, `ip_address`, `user_agent`, `expires_at`, `created_at`) VALUES
(241, 38, 'aabc0954f9617ed8aa0d18182ef7b341a89c638c43c510b63949a72a4afa5a5a91d10650cd2b95fea18fd9924d2c4e60e101f838b15c75eb57c4ec1ca9202f19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-16 03:16:40', '2025-10-15 02:54:02');

-- --------------------------------------------------------

--
-- Structure for view `asset_details`
--
DROP TABLE IF EXISTS `asset_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `asset_details`  AS SELECT `a`.`id` AS `id`, `a`.`asset_name` AS `asset_name`, `c`.`category_name` AS `category_name`, `a`.`status` AS `status`, `cam`.`campus_code` AS `campus_code`, `cam`.`campus_name` AS `campus_name`, `a`.`location` AS `location`, `a`.`purchase_date` AS `purchase_date`, `a`.`value` AS `value`, `a`.`serial_number` AS `serial_number`, `a`.`assigned_to` AS `assigned_to`, `a`.`assigned_email` AS `assigned_email`, `a`.`assignment_date` AS `assignment_date`, `a`.`created_at` AS `created_at`, `a`.`updated_at` AS `updated_at` FROM ((`assets` `a` join `categories` `c` on(`a`.`category_id` = `c`.`id`)) join `campuses` `cam` on(`a`.`campus_id` = `cam`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `campus_statistics`
--
DROP TABLE IF EXISTS `campus_statistics`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `campus_statistics`  AS SELECT `c`.`campus_code` AS `campus_code`, `c`.`campus_name` AS `campus_name`, count(`a`.`id`) AS `total_assets`, sum(case when `a`.`status` = 'Active' then 1 else 0 end) AS `active_assets`, sum(case when `a`.`status` = 'Maintenance' then 1 else 0 end) AS `maintenance_assets`, sum(case when `a`.`assigned_to` is not null and `a`.`assigned_to` <> '' then 1 else 0 end) AS `assigned_assets`, sum(`a`.`value`) AS `total_value`, avg(`a`.`value`) AS `average_value` FROM (`campuses` `c` left join `assets` `a` on(`c`.`id` = `a`.`campus_id`)) GROUP BY `c`.`id`, `c`.`campus_code`, `c`.`campus_name` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activity_log_asset` (`asset_id`),
  ADD KEY `idx_activity_log_date` (`created_at`),
  ADD KEY `idx_campus_id` (`campus_id`);

--
-- Indexes for table `assets`
--
ALTER TABLE `assets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `serial_number` (`serial_number`),
  ADD KEY `idx_assets_campus` (`campus_id`),
  ADD KEY `idx_assets_category` (`category_id`),
  ADD KEY `idx_assets_status` (`status`),
  ADD KEY `idx_assets_serial` (`serial_number`),
  ADD KEY `idx_barcode` (`barcode`),
  ADD KEY `idx_assets_created_by` (`created_by`),
  ADD KEY `idx_assets_room_id` (`room_id`);

--
-- Indexes for table `asset_assignments`
--
ALTER TABLE `asset_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asset_id` (`asset_id`);

--
-- Indexes for table `asset_borrowings`
--
ALTER TABLE `asset_borrowings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_asset_id` (`asset_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_borrower_type` (`borrower_type`),
  ADD KEY `idx_borrowed_date` (`borrowed_date`);

--
-- Indexes for table `asset_maintenance`
--
ALTER TABLE `asset_maintenance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asset_id` (`asset_id`);

--
-- Indexes for table `asset_names`
--
ALTER TABLE `asset_names`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `asset_scans`
--
ALTER TABLE `asset_scans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asset_id` (`asset_id`);

--
-- Indexes for table `buildings`
--
ALTER TABLE `buildings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_building_campus` (`building_name`,`campus_id`),
  ADD KEY `campus_id` (`campus_id`);

--
-- Indexes for table `campuses`
--
ALTER TABLE `campuses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `campus_code` (`campus_code`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `verification_token` (`verification_token`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_verification_token` (`verification_token`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `asset_id` (`asset_id`);

--
-- Indexes for table `inventory_sessions`
--
ALTER TABLE `inventory_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `campus_id` (`campus_id`);

--
-- Indexes for table `it_support_users`
--
ALTER TABLE `it_support_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_location_code` (`code`),
  ADD KEY `idx_building_id` (`building_id`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `inventory_tags`
--
ALTER TABLE `inventory_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tag_number` (`tag_number`),
  ADD KEY `asset_id` (`asset_id`),
  ADD KEY `office_id` (`office_id`);

--
-- Indexes for table `offices`
--
ALTER TABLE `offices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_offices_campus_id` (`campus_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_username_ip` (`username`,`ip_address`),
  ADD KEY `idx_attempted_at` (`attempted_at`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reset_token` (`reset_token`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_reset_token` (`reset_token`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_room_building` (`room_name`,`building_id`),
  ADD KEY `idx_rooms_building_id` (`building_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `campus_id` (`campus_id`),
  ADD KEY `fk_users_office_id` (`office_id`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_id` (`session_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_session_id` (`session_id`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=343;

--
-- AUTO_INCREMENT for table `assets`
--
ALTER TABLE `assets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `asset_assignments`
--
ALTER TABLE `asset_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `asset_borrowings`
--
ALTER TABLE `asset_borrowings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `asset_maintenance`
--
ALTER TABLE `asset_maintenance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `asset_names`
--
ALTER TABLE `asset_names`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `asset_scans`
--
ALTER TABLE `asset_scans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=196;

--
-- AUTO_INCREMENT for table `buildings`
--
ALTER TABLE `buildings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `campuses`
--
ALTER TABLE `campuses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `email_verifications`
--
ALTER TABLE `email_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `inventory_sessions`
--
ALTER TABLE `inventory_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `inventory_tags`
--
ALTER TABLE `inventory_tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `it_support_users`
--
ALTER TABLE `it_support_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `offices`
--
ALTER TABLE `offices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=307;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=242;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_activity_log_campus_id` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `assets`
--
ALTER TABLE `assets`
  ADD CONSTRAINT `assets_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `assets_ibfk_2` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`),
  ADD CONSTRAINT `fk_assets_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_assets_room_id` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `asset_assignments`
--
ALTER TABLE `asset_assignments`
  ADD CONSTRAINT `asset_assignments_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `asset_borrowings`
--
ALTER TABLE `asset_borrowings`
  ADD CONSTRAINT `fk_asset_borrowings_asset_id` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `asset_maintenance`
--
ALTER TABLE `asset_maintenance`
  ADD CONSTRAINT `asset_maintenance_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `asset_scans`
--
ALTER TABLE `asset_scans`
  ADD CONSTRAINT `asset_scans_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `buildings`
--
ALTER TABLE `buildings`
  ADD CONSTRAINT `buildings_ibfk_1` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD CONSTRAINT `email_verifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD CONSTRAINT `inventory_items_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `inventory_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_items_ibfk_2` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_sessions`
--
ALTER TABLE `inventory_sessions`
  ADD CONSTRAINT `inventory_sessions_ibfk_1` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`);

--
-- Constraints for table `inventory_tags`
--
ALTER TABLE `inventory_tags`
  ADD CONSTRAINT `inventory_tags_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_tags_ibfk_2` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_inventory_tags_assigned_by` FOREIGN KEY (`assigned_by_custodian_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_inventory_tags_verified_by` FOREIGN KEY (`verified_by_office_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `it_support_users`
--
ALTER TABLE `it_support_users`
  ADD CONSTRAINT `it_support_users_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `locations`
--
ALTER TABLE `locations`
  ADD CONSTRAINT `fk_locations_building_id` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `fk_rooms_building_id` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_office_id` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
