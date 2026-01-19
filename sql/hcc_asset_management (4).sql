-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 07, 2025 at 06:35 PM
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
(1633, NULL, 'CREATED', 'Asset \'Bookshelf\' registered in the system', 'Sta Rosa HCC', 1, '2025-10-28 02:48:52'),
(1634, NULL, 'LOCATION_ASSIGNED', 'Asset placed in location \'CM 104\' upon creation', 'Sta Rosa HCC', 1, '2025-10-28 02:48:52'),
(1635, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-28 02:49:34'),
(1636, NULL, 'STOCK_RESERVED', 'Stock of 1 reserved for request #83', 'Sta Rosa HCC', 1, '2025-10-28 02:50:35'),
(1637, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: 918ATBX2', 'Sta Rosa HCC', 1, '2025-10-28 02:50:35'),
(1638, NULL, 'ASSIGNED', 'Asset assigned to Mico Macapugay via approved request.', 'richard fermin', 1, '2025-10-28 02:53:05'),
(1639, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: 918ATBX2', 'richard fermin', 1, '2025-10-28 02:53:05'),
(1640, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-28 03:08:08'),
(1641, NULL, 'STOCK_RESERVED', 'Stock of 1 reserved for request #84', 'Sta Rosa HCC', 1, '2025-10-28 03:13:19'),
(1642, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: 5OS93NRD', 'Sta Rosa HCC', 1, '2025-10-28 03:13:19'),
(1643, NULL, 'ASSIGNED', 'Asset assigned to Mico Macapugay via approved request.', 'richard fermin', 1, '2025-10-28 03:13:33'),
(1644, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: 5OS93NRD', 'richard fermin', 1, '2025-10-28 03:13:33'),
(1645, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. Fully returned.', 'Mico Macapugay', 1, '2025-10-28 03:13:51'),
(1646, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. Fully returned.', 'Mico Macapugay', 1, '2025-10-28 03:13:54'),
(1647, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-28 03:17:12'),
(1648, NULL, 'STOCK_RESERVED', 'Stock of 1 reserved for request #85', 'Sta Rosa HCC', 1, '2025-10-28 03:17:21'),
(1649, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: KGIL5DBC', 'Sta Rosa HCC', 1, '2025-10-28 03:17:21'),
(1650, NULL, 'ASSIGNED', 'Asset assigned to Mico Macapugay via approved request.', 'richard fermin', 1, '2025-10-28 03:17:28'),
(1651, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: KGIL5DBC', 'richard fermin', 1, '2025-10-28 03:17:28'),
(1652, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-28 03:18:09'),
(1653, NULL, 'STOCK_RESERVED', 'Stock of 1 reserved for request #86', 'Sta Rosa HCC', 1, '2025-10-28 03:18:13'),
(1654, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: 7OB90SWE', 'Sta Rosa HCC', 1, '2025-10-28 03:18:13'),
(1655, NULL, 'ASSIGNED', 'Asset assigned to Mico Macapugay via approved request.', 'richard fermin', 1, '2025-10-28 03:18:24'),
(1656, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: 7OB90SWE', 'richard fermin', 1, '2025-10-28 03:18:24'),
(1657, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. Fully returned.', 'Mico Macapugay', 1, '2025-10-28 03:19:24'),
(1658, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. Fully returned.', 'Mico Macapugay', 1, '2025-10-28 03:19:25'),
(1659, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-28 03:19:39'),
(1660, NULL, 'STOCK_RESERVED', 'Stock of 1 reserved for request #87', 'Sta Rosa HCC', 1, '2025-10-28 03:19:44'),
(1661, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: ZVJNXLMP', 'Sta Rosa HCC', 1, '2025-10-28 03:19:44'),
(1662, NULL, 'ASSIGNED', 'Asset assigned to Mico Macapugay via approved request.', 'richard fermin', 1, '2025-10-28 03:19:53'),
(1663, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: ZVJNXLMP', 'richard fermin', 1, '2025-10-28 03:19:53'),
(1664, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-28 03:20:02'),
(1665, NULL, 'STOCK_RESERVED', 'Stock of 1 reserved for request #88', 'Sta Rosa HCC', 1, '2025-10-28 03:20:14'),
(1666, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: VFIE8BUN', 'Sta Rosa HCC', 1, '2025-10-28 03:20:14'),
(1667, NULL, 'ASSIGNED', 'Asset assigned to Mico Macapugay via approved request.', 'richard fermin', 1, '2025-10-28 03:20:29'),
(1668, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: VFIE8BUN', 'richard fermin', 1, '2025-10-28 03:20:29'),
(1669, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. Fully returned.', 'Mico Macapugay', 1, '2025-10-28 03:21:19'),
(1670, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. Fully returned.', 'Mico Macapugay', 1, '2025-10-28 03:21:21'),
(1671, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-28 03:21:29'),
(1672, NULL, 'STOCK_RESERVED', 'Stock of 1 reserved for request #89', 'Sta Rosa HCC', 1, '2025-10-28 03:21:34'),
(1673, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: WY7D23KH', 'Sta Rosa HCC', 1, '2025-10-28 03:21:34'),
(1674, NULL, 'ASSIGNED', 'Asset assigned to Mico Macapugay via approved request.', 'richard fermin', 1, '2025-10-28 03:21:43'),
(1675, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: WY7D23KH', 'richard fermin', 1, '2025-10-28 03:21:43'),
(1676, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-28 03:21:51'),
(1677, NULL, 'STOCK_RESERVED', 'Stock of 1 reserved for request #90', 'Sta Rosa HCC', 1, '2025-10-28 03:22:04'),
(1678, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: UBX0EN1A', 'Sta Rosa HCC', 1, '2025-10-28 03:22:04'),
(1679, NULL, 'ASSIGNED', 'Asset assigned to Mico Macapugay via approved request.', 'richard fermin', 1, '2025-10-28 03:22:14'),
(1680, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: UBX0EN1A', 'richard fermin', 1, '2025-10-28 03:22:14'),
(1681, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. Fully returned.', 'Mico Macapugay', 1, '2025-10-28 03:25:58'),
(1682, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. Fully returned.', 'Mico Macapugay', 1, '2025-10-28 03:26:00'),
(1683, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-28 03:26:08'),
(1684, NULL, 'STOCK_RESERVED', 'Stock of 1 reserved for request #91', 'Sta Rosa HCC', 1, '2025-10-28 03:26:14'),
(1685, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: ZGSUFTH2', 'Sta Rosa HCC', 1, '2025-10-28 03:26:14'),
(1686, NULL, 'ASSIGNED', 'Asset assigned to Mico Macapugay via approved request.', 'richard fermin', 1, '2025-10-28 03:26:22'),
(1687, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: ZGSUFTH2', 'richard fermin', 1, '2025-10-28 03:26:22'),
(1688, NULL, 'ASSIGNED', 'Added 1 unit(s) to existing assignment for staff member Mico Macapugay.', 'Mico Macapugay', 1, '2025-10-28 03:26:30'),
(1689, NULL, 'ASSIGNED', 'Added 1 unit(s) to existing assignment for staff member Mico Macapugay.', 'Mico Macapugay', 1, '2025-10-28 03:26:43'),
(1690, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. 2 remaining.', 'Mico Macapugay', 1, '2025-10-28 03:30:50'),
(1691, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-28 03:31:02'),
(1692, NULL, 'STOCK_RESERVED', 'Stock of 1 reserved for request #92', 'Sta Rosa HCC', 1, '2025-10-28 03:31:07'),
(1693, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: OTH9A8EM', 'Sta Rosa HCC', 1, '2025-10-28 03:31:07'),
(1694, NULL, 'ASSIGNED', 'Asset assigned to Mico Macapugay via approved request.', 'richard fermin', 1, '2025-10-28 03:31:28'),
(1695, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: OTH9A8EM', 'richard fermin', 1, '2025-10-28 03:31:28'),
(1696, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. 1 remaining.', 'Mico Macapugay', 1, '2025-10-28 03:31:54'),
(1697, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. Fully returned.', 'Mico Macapugay', 1, '2025-10-28 03:32:50'),
(1698, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. Fully returned.', 'Mico Macapugay', 1, '2025-10-28 03:32:52'),
(1699, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-28 03:33:02'),
(1700, NULL, 'STOCK_RESERVED', 'Stock of 1 reserved for request #93', 'Sta Rosa HCC', 1, '2025-10-28 03:33:06'),
(1701, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: T2EACDO1', 'Sta Rosa HCC', 1, '2025-10-28 03:33:06'),
(1702, NULL, 'ASSIGNED', 'Asset assigned to Mico Macapugay via approved request.', 'richard fermin', 1, '2025-10-28 03:33:22'),
(1703, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: T2EACDO1', 'richard fermin', 1, '2025-10-28 03:33:22'),
(1704, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-28 03:33:28'),
(1705, NULL, 'STOCK_RESERVED', 'Stock of 1 reserved for request #94', 'Sta Rosa HCC', 1, '2025-10-28 03:33:35'),
(1706, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: NDU89G5J', 'Sta Rosa HCC', 1, '2025-10-28 03:33:35'),
(1707, NULL, 'ASSIGNED', 'Asset assigned to Mico Macapugay via approved request.', 'richard fermin', 1, '2025-10-28 03:33:44'),
(1708, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: NDU89G5J', 'richard fermin', 1, '2025-10-28 03:33:44'),
(1709, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. Fully returned.', 'Mico Macapugay', 1, '2025-10-28 03:40:39'),
(1710, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-28 03:40:48'),
(1711, NULL, 'STOCK_RESERVED', 'Stock of 1 reserved for request #95', 'Sta Rosa HCC', 1, '2025-10-28 03:40:58'),
(1712, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: 89RBAOMW', 'Sta Rosa HCC', 1, '2025-10-28 03:40:58'),
(1713, NULL, 'ASSIGNED', 'Added 1 unit(s) to existing assignment for Mico Macapugay.', 'richard fermin', 1, '2025-10-28 03:41:12'),
(1714, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: 89RBAOMW', 'richard fermin', 1, '2025-10-28 03:41:12'),
(1715, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-28 03:41:34'),
(1716, NULL, 'STOCK_RESERVED', 'Stock of 1 reserved for request #96', 'Sta Rosa HCC', 1, '2025-10-28 03:41:59'),
(1717, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: 06U4SGKZ', 'Sta Rosa HCC', 1, '2025-10-28 03:41:59'),
(1718, NULL, 'ASSIGNED', 'Added 1 unit(s) to existing assignment for Mico Macapugay.', 'richard fermin', 1, '2025-10-28 03:42:13'),
(1719, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: 06U4SGKZ', 'richard fermin', 1, '2025-10-28 03:42:13'),
(1720, NULL, 'CREATED', 'Asset \'Cabinet\' registered in the system', 'Sta Rosa HCC', 1, '2025-10-28 03:55:26'),
(1721, NULL, 'LOCATION_ASSIGNED', 'Asset placed in location \'Accreditation Room\' upon creation', 'Sta Rosa HCC', 1, '2025-10-28 03:55:26'),
(1722, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-28 03:55:36'),
(1723, NULL, 'STOCK_RESERVED', 'Stock of 2 reserved for request #97', 'Sta Rosa HCC', 1, '2025-10-28 03:55:41'),
(1724, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: GT3F87DU', 'Sta Rosa HCC', 1, '2025-10-28 03:55:41'),
(1725, NULL, 'ASSIGNED', 'Asset assigned to Mico Macapugay via approved request.', 'richard fermin', 1, '2025-10-28 03:55:56'),
(1726, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: GT3F87DU', 'richard fermin', 1, '2025-10-28 03:55:56'),
(1727, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-28 03:56:14'),
(1728, NULL, 'STOCK_RESERVED', 'Stock of 2 reserved for request #98', 'Sta Rosa HCC', 1, '2025-10-28 03:56:20'),
(1729, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: AH21WX0P', 'Sta Rosa HCC', 1, '2025-10-28 03:56:20'),
(1730, NULL, 'ASSIGNED', 'Added 2 unit(s) to existing assignment for Mico Macapugay.', 'richard fermin', 1, '2025-10-28 03:56:39'),
(1731, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: AH21WX0P', 'richard fermin', 1, '2025-10-28 03:56:39'),
(1732, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. 2 remaining.', 'Mico Macapugay', 1, '2025-10-28 03:57:34'),
(1733, NULL, 'RETURNED', '2 unit(s) of asset returned by staff. Fully returned.', 'Mico Macapugay', 1, '2025-10-28 03:58:15'),
(1734, NULL, 'CREATED', 'Asset \'Aircon\' registered in the system', 'Sta Rosa HCC', 1, '2025-10-28 03:59:13'),
(1735, NULL, 'LOCATION_ASSIGNED', 'Asset placed in location \'Accreditation Room\' upon creation', 'Sta Rosa HCC', 1, '2025-10-28 03:59:13'),
(1736, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-28 03:59:40'),
(1737, NULL, 'STOCK_RESERVED', 'Stock of 2 reserved for request #99', 'Sta Rosa HCC', 1, '2025-10-28 03:59:45'),
(1738, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: 1AJ8YGDO', 'Sta Rosa HCC', 1, '2025-10-28 03:59:45'),
(1739, NULL, 'CREATED', 'Asset \'Bookshelf\' registered in the system', 'Sta Rosa HCC', 1, '2025-10-28 04:00:07'),
(1740, NULL, 'LOCATION_ASSIGNED', 'Asset placed in location \'AREA\' upon creation', 'Sta Rosa HCC', 1, '2025-10-28 04:00:07'),
(1741, NULL, 'ASSIGNED', 'Asset assigned to Mico Macapugay via approved request.', 'richard fermin', 1, '2025-10-28 04:00:17'),
(1742, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: 1AJ8YGDO', 'richard fermin', 1, '2025-10-28 04:00:17'),
(1743, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-28 04:00:26'),
(1744, NULL, 'STOCK_RESERVED', 'Stock of 1 reserved for request #100', 'Sta Rosa HCC', 1, '2025-10-28 04:00:34'),
(1745, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: WFYZ7T3Q', 'Sta Rosa HCC', 1, '2025-10-28 04:00:34'),
(1746, NULL, 'ASSIGNED', 'Added 1 unit(s) to existing assignment for Mico Macapugay.', 'richard fermin', 1, '2025-10-28 04:00:49'),
(1747, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: WFYZ7T3Q', 'richard fermin', 1, '2025-10-28 04:00:49'),
(1748, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-28 04:00:59'),
(1749, NULL, 'STOCK_RESERVED', 'Stock of 4 reserved for request #101', 'Sta Rosa HCC', 1, '2025-10-28 04:01:06'),
(1750, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: QEZ57P9C', 'Sta Rosa HCC', 1, '2025-10-28 04:01:06'),
(1751, NULL, 'ASSIGNED', 'Asset assigned to Mico Macapugay via approved request.', 'richard fermin', 1, '2025-10-28 04:01:20'),
(1752, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: QEZ57P9C', 'richard fermin', 1, '2025-10-28 04:01:20'),
(1753, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. 2 remaining.', 'Mico Macapugay', 1, '2025-10-28 04:03:11'),
(1754, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. 1 remaining.', 'Mico Macapugay', 1, '2025-10-28 04:03:17'),
(1755, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. Fully returned.', 'Mico Macapugay', 1, '2025-10-28 04:03:19'),
(1756, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. 3 remaining.', 'Mico Macapugay', 1, '2025-10-28 04:03:21'),
(1757, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. 2 remaining.', 'Mico Macapugay', 1, '2025-10-28 04:03:23'),
(1758, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. 1 remaining.', 'Mico Macapugay', 1, '2025-10-28 04:03:24'),
(1759, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. Fully returned.', 'Mico Macapugay', 1, '2025-10-28 04:03:26'),
(1760, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-28 04:04:58'),
(1761, NULL, 'STOCK_RESERVED', 'Stock of 1 reserved for request #102', 'Sta Rosa HCC', 1, '2025-10-28 04:05:05'),
(1762, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: 39DPUJNX', 'Sta Rosa HCC', 1, '2025-10-28 04:05:05'),
(1763, NULL, 'ASSIGNED', 'Asset assigned to Mico Macapugay via approved request.', 'richard fermin', 1, '2025-10-28 04:05:21'),
(1764, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: 39DPUJNX', 'richard fermin', 1, '2025-10-28 04:05:21'),
(1765, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-28 04:05:36'),
(1766, NULL, 'STOCK_RESERVED', 'Stock of 4 reserved for request #103', 'Sta Rosa HCC', 1, '2025-10-28 04:05:42'),
(1767, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: JQD6MTZU', 'Sta Rosa HCC', 1, '2025-10-28 04:05:42'),
(1768, NULL, 'ASSIGNED', 'Asset assigned to Mico Macapugay via approved request.', 'richard fermin', 1, '2025-10-28 04:05:50'),
(1769, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: JQD6MTZU', 'richard fermin', 1, '2025-10-28 04:05:50'),
(1770, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. Fully returned.', 'Mico Macapugay', 1, '2025-10-28 04:05:56'),
(1771, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. 3 remaining.', 'Mico Macapugay', 1, '2025-10-28 04:05:57'),
(1772, NULL, 'RETURNED', '3 unit(s) of asset returned by staff. Fully returned.', 'Mico Macapugay', 1, '2025-10-28 04:06:02'),
(1773, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-28 04:07:21'),
(1774, NULL, 'STOCK_RESERVED', 'Stock of 1 reserved for request #104', 'Sta Rosa HCC', 1, '2025-10-28 04:07:38'),
(1775, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: 14ZKC908', 'Sta Rosa HCC', 1, '2025-10-28 04:07:38'),
(1776, NULL, 'ASSIGNED', 'Asset assigned to Mico Macapugay via approved request.', 'richard fermin', 1, '2025-10-28 04:07:48'),
(1777, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: 14ZKC908', 'richard fermin', 1, '2025-10-28 04:07:48'),
(1778, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. Fully returned.', 'Mico Macapugay', 1, '2025-10-28 04:07:58'),
(1779, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-28 04:08:08'),
(1780, NULL, 'STOCK_RESERVED', 'Stock of 3 reserved for request #105', 'Sta Rosa HCC', 1, '2025-10-28 04:08:17'),
(1781, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: K7RU5LCI', 'Sta Rosa HCC', 1, '2025-10-28 04:08:17'),
(1782, NULL, 'ASSIGNED', 'Asset assigned to Mico Macapugay via approved request.', 'richard fermin', 1, '2025-10-28 04:08:26'),
(1783, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: K7RU5LCI', 'richard fermin', 1, '2025-10-28 04:08:26'),
(1784, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. 2 remaining.', 'Mico Macapugay', 1, '2025-10-28 04:08:34'),
(1785, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. 1 remaining.', 'Mico Macapugay', 1, '2025-10-28 04:11:39'),
(1786, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. Fully returned.', 'Mico Macapugay', 1, '2025-10-28 04:11:47'),
(1787, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-28 04:11:58'),
(1788, NULL, 'STOCK_RESERVED', 'Stock of 1 reserved for request #106', 'Sta Rosa HCC', 1, '2025-10-28 04:12:07'),
(1789, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: 46UER3KX', 'Sta Rosa HCC', 1, '2025-10-28 04:12:07'),
(1790, NULL, 'ASSIGNED', 'Asset assigned to Mico Macapugay via approved request.', 'richard fermin', 1, '2025-10-28 04:12:22'),
(1791, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: 46UER3KX', 'richard fermin', 1, '2025-10-28 04:12:22'),
(1792, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. Fully returned.', 'Mico Macapugay', 1, '2025-10-28 04:12:50'),
(1793, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-28 04:15:07'),
(1794, NULL, 'STOCK_RESERVED', 'Stock of 1 reserved for request #107', 'Sta Rosa HCC', 1, '2025-10-28 04:15:16'),
(1795, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: UOQAGIED', 'Sta Rosa HCC', 1, '2025-10-28 04:15:16'),
(1796, NULL, 'ASSIGNED', 'Asset assigned to Mico Macapugay via approved request.', 'richard fermin', 1, '2025-10-28 04:15:25'),
(1797, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: UOQAGIED', 'richard fermin', 1, '2025-10-28 04:15:25'),
(1798, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. Fully returned.', 'Mico Macapugay', 1, '2025-10-28 04:16:51'),
(1799, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-28 04:17:01'),
(1800, NULL, 'STOCK_RESERVED', 'Stock of 1 reserved for request #108', 'Sta Rosa HCC', 1, '2025-10-28 04:17:09'),
(1801, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: 3CKDJV7H', 'Sta Rosa HCC', 1, '2025-10-28 04:17:09'),
(1802, NULL, 'ASSIGNED', 'Asset assigned to Mico Macapugay via approved request.', 'richard fermin', 1, '2025-10-28 04:17:22'),
(1803, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: 3CKDJV7H', 'richard fermin', 1, '2025-10-28 04:17:22'),
(1804, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. Fully returned.', 'Mico Macapugay', 1, '2025-10-28 04:17:40'),
(1805, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-28 04:17:45'),
(1806, NULL, 'STOCK_RESERVED', 'Stock of 2 reserved for request #109', 'Sta Rosa HCC', 1, '2025-10-28 04:17:49'),
(1807, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: 5A72GNYP', 'Sta Rosa HCC', 1, '2025-10-28 04:17:49'),
(1808, NULL, 'ASSIGNED', 'Asset assigned to Mico Macapugay via approved request.', 'richard fermin', 1, '2025-10-28 04:18:02'),
(1809, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: 5A72GNYP', 'richard fermin', 1, '2025-10-28 04:18:02'),
(1810, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. 1 remaining.', 'Mico Macapugay', 1, '2025-10-28 04:18:19'),
(1811, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. Fully returned.', 'Mico Macapugay', 1, '2025-10-28 04:18:32'),
(1812, NULL, 'ASSIGNED', '2 unit(s) of asset assigned to Mico Macapugay', 'Sta Rosa HCC', 1, '2025-10-28 04:20:01'),
(1813, NULL, 'STOCK_IN', 'Stock In: +2 units. New quantity: 5. Notes: Stock In', 'Sta Rosa HCC', 1, '2025-10-28 04:20:01'),
(1814, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. 1 remaining.', 'Mico Macapugay', 1, '2025-10-28 04:21:06'),
(1815, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. Fully returned.', 'Mico Macapugay', 1, '2025-10-28 04:21:08'),
(1816, NULL, 'ASSIGNED', '2 unit(s) of asset assigned to Mico Macapugay', 'Sta Rosa HCC', 1, '2025-10-28 04:22:15'),
(1817, NULL, 'STOCK_IN', 'Stock In: +2 units. New quantity: 7. Notes: Stock In', 'Sta Rosa HCC', 1, '2025-10-28 04:22:15'),
(1818, NULL, 'RETURNED', '2 unit(s) of asset returned by staff. Fully returned.', 'Mico Macapugay', 1, '2025-10-28 04:23:59'),
(1819, NULL, 'ASSIGNED', '2 unit(s) of asset assigned to Mico Macapugay', 'Sta Rosa HCC', 1, '2025-10-28 04:24:20'),
(1820, NULL, 'STOCK_IN', 'Stock In: +2 units. New quantity: 11. Notes: Stock In', 'Sta Rosa HCC', 1, '2025-10-28 04:24:21'),
(1821, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. 1 remaining.', 'Mico Macapugay', 1, '2025-10-28 04:24:50'),
(1822, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. Fully returned.', 'Mico Macapugay', 1, '2025-10-28 04:24:53'),
(1823, NULL, 'ASSIGNED', '2 unit(s) of asset assigned to Mico Macapugay', 'Sta Rosa HCC', 1, '2025-10-28 04:25:34'),
(1824, NULL, 'STOCK_IN', 'Stock In: +2 units. New quantity: 15. Notes: Stock In', 'Sta Rosa HCC', 1, '2025-10-28 04:25:34'),
(1825, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. 1 remaining.', 'Mico Macapugay', 1, '2025-10-28 04:26:00'),
(1826, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. Fully returned.', 'Mico Macapugay', 1, '2025-10-28 04:26:02'),
(1827, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-28 04:28:32'),
(1828, NULL, 'STOCK_RESERVED', 'Stock of 2 reserved for request #110', 'Sta Rosa HCC', 1, '2025-10-28 04:28:46'),
(1829, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: 4LYIVGOQ', 'Sta Rosa HCC', 1, '2025-10-28 04:28:46'),
(1830, NULL, 'ASSIGNED', 'Asset assigned to Mico Macapugay via approved request.', 'richard fermin', 1, '2025-10-28 04:29:03'),
(1831, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: 4LYIVGOQ', 'richard fermin', 1, '2025-10-28 04:29:03'),
(1832, NULL, 'RETURNED', '2 unit(s) of asset returned by staff. Fully returned.', 'Mico Macapugay', 1, '2025-10-28 04:29:28'),
(1833, NULL, 'ARCHIVED', 'Asset \'Bookshelf\' archived', 'Sta Rosa HCC', 1, '2025-10-28 05:18:38'),
(1834, NULL, 'ARCHIVED', 'Asset \'Bookshelf\' archived', 'Sta Rosa HCC', 1, '2025-10-28 05:19:22'),
(1835, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-28 06:07:53'),
(1836, NULL, 'STOCK_RESERVED', 'Stock of 1 reserved for request #111', 'Sta Rosa HCC', 1, '2025-10-28 06:07:59'),
(1837, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: DP8X504S', 'Sta Rosa HCC', 1, '2025-10-28 06:07:59'),
(1838, NULL, 'ASSIGNED', 'Asset assigned to Mico Macapugay via approved request.', 'richard fermin', 1, '2025-10-28 06:08:11'),
(1839, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: DP8X504S', 'richard fermin', 1, '2025-10-28 06:08:11'),
(1840, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. Fully returned.', 'Mico Macapugay', 1, '2025-10-28 06:08:31'),
(1841, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-28 06:19:34'),
(1842, NULL, 'STOCK_RESERVED', 'Stock of 1 reserved for request #112', 'Sta Rosa HCC', 1, '2025-10-28 06:19:40'),
(1843, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: IM2CW13K', 'Sta Rosa HCC', 1, '2025-10-28 06:19:40'),
(1844, NULL, 'ASSIGNED', 'Asset assigned to Mico Macapugay via approved request.', 'richard fermin', 1, '2025-10-28 06:19:54'),
(1845, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: IM2CW13K', 'richard fermin', 1, '2025-10-28 06:19:54'),
(1846, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-28 06:20:20'),
(1847, NULL, 'STOCK_RESERVED', 'Stock of 2 reserved for request #113', 'Sta Rosa HCC', 1, '2025-10-28 06:20:29'),
(1848, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: ASO8PDYN', 'Sta Rosa HCC', 1, '2025-10-28 06:20:29'),
(1849, NULL, 'ASSIGNED', 'Added 2 unit(s) to existing assignment for Mico Macapugay.', 'richard fermin', 1, '2025-10-28 06:20:40'),
(1850, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: ASO8PDYN', 'richard fermin', 1, '2025-10-28 06:20:40'),
(1851, NULL, 'RETURNED', '3 unit(s) of asset returned by staff. Fully returned.', 'Mico Macapugay', 1, '2025-10-28 06:21:04'),
(1852, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-28 06:21:28'),
(1853, NULL, 'STOCK_RESERVED', 'Stock of 1 reserved for request #114', 'Sta Rosa HCC', 1, '2025-10-28 06:25:55'),
(1854, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: CPKION15', 'Sta Rosa HCC', 1, '2025-10-28 06:25:55'),
(1855, NULL, 'ASSIGNED', 'Asset assigned to Mico Macapugay via approved request.', 'richard fermin', 1, '2025-10-28 06:26:22'),
(1856, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: CPKION15', 'richard fermin', 1, '2025-10-28 06:26:22'),
(1857, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. Fully returned.', 'Mico Macapugay', 1, '2025-10-28 06:26:50'),
(1858, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-28 06:28:24'),
(1859, NULL, 'STOCK_RESERVED', 'Stock of 1 reserved for request #115', 'Sta Rosa HCC', 1, '2025-10-28 06:51:18'),
(1860, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: 9AUXH5M4', 'Sta Rosa HCC', 1, '2025-10-28 06:51:18'),
(1861, NULL, 'ASSIGNED', 'Asset assigned to Mico Macapugay via approved request.', 'richard fermin', 1, '2025-10-28 07:07:32'),
(1862, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: 9AUXH5M4', 'richard fermin', 1, '2025-10-28 07:07:32'),
(1863, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. Fully returned.', 'Mico Macapugay', 1, '2025-10-28 07:35:13'),
(1864, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-28 07:35:40'),
(1865, NULL, 'STOCK_RESERVED', 'Stock of 1 reserved for request #116', 'Sta Rosa HCC', 1, '2025-10-28 07:35:46'),
(1866, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: GLFIW2D7', 'Sta Rosa HCC', 1, '2025-10-28 07:35:46'),
(1867, NULL, 'ASSIGNED', 'Asset assigned to Mico Macapugay via approved request.', 'richard fermin', 1, '2025-10-28 07:35:55'),
(1868, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: GLFIW2D7', 'richard fermin', 1, '2025-10-28 07:35:55'),
(1869, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-28 07:36:29'),
(1870, NULL, 'STOCK_RESERVED', 'Stock of 1 reserved for request #117', 'Sta Rosa HCC', 1, '2025-10-28 07:36:34'),
(1871, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: KFX5QTVL', 'Sta Rosa HCC', 1, '2025-10-28 07:36:34'),
(1872, NULL, 'ASSIGNED', 'Added 1 unit(s) to existing assignment for Mico Macapugay.', 'richard fermin', 1, '2025-10-28 07:36:46'),
(1873, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: KFX5QTVL', 'richard fermin', 1, '2025-10-28 07:36:46'),
(1874, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. 1 remaining.', 'Mico Macapugay', 1, '2025-10-28 07:37:10'),
(1875, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. Fully returned.', 'Mico Macapugay', 1, '2025-10-28 07:43:21'),
(1876, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-28 07:44:09'),
(1877, NULL, 'STOCK_RESERVED', 'Stock of 1 reserved for request #118', 'Sta Rosa HCC', 1, '2025-10-28 07:44:24'),
(1878, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: 08UEB592', 'Sta Rosa HCC', 1, '2025-10-28 07:44:24'),
(1879, NULL, 'ASSIGNED', 'Asset assigned to Mico Macapugay via approved request.', 'richard fermin', 1, '2025-10-28 07:44:33'),
(1880, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: 08UEB592', 'richard fermin', 1, '2025-10-28 07:44:33'),
(1881, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-28 10:17:22'),
(1882, NULL, 'STOCK_RESERVED', 'Stock of 2 reserved for request #119', 'Sta Rosa HCC', 1, '2025-10-28 10:17:36'),
(1883, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: QFKJXT97', 'Sta Rosa HCC', 1, '2025-10-28 10:17:36'),
(1884, NULL, 'ASSIGNED', 'Added 2 unit(s) to existing assignment for Mico Macapugay.', 'richard fermin', 1, '2025-10-28 10:19:21'),
(1885, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: QFKJXT97', 'richard fermin', 1, '2025-10-28 10:19:21'),
(1886, NULL, 'RETURNED', '3 unit(s) of asset returned by staff. Fully returned.', 'Mico Macapugay', 1, '2025-10-28 10:22:01'),
(1887, NULL, 'DELETED', 'Asset \'Aircon\' deleted from system', 'richard fermin', 1, '2025-10-28 12:06:32'),
(1888, NULL, 'CREATED', 'Asset \'Aircon\' registered in the system', 'richard fermin', 1, '2025-10-28 12:06:57'),
(1889, NULL, 'LOCATION_ASSIGNED', 'Asset placed in location \'Kinder Garten\' upon creation', 'richard fermin', 1, '2025-10-28 12:06:57'),
(1890, NULL, 'ASSIGNED', 'Asset assigned to richard fermin', 'richard fermin', 1, '2025-10-28 12:06:57'),
(1891, NULL, 'DELETED', 'Asset \'Aircon\' deleted from system', 'Sta Rosa HCC', 1, '2025-10-28 12:50:40'),
(1892, NULL, 'CREATED', 'Asset \'Aircon\' registered in the system', 'Sta Rosa HCC', 1, '2025-10-28 12:53:40'),
(1893, NULL, 'LOCATION_ASSIGNED', 'Asset placed in location \'Audio Visual Room\' upon creation', 'Sta Rosa HCC', 1, '2025-10-28 12:53:40'),
(1894, NULL, 'CREATED', 'Asset \'Aircon\' registered in the system', 'Sta Rosa HCC', 1, '2025-10-28 13:12:45'),
(1895, NULL, 'LOCATION_ASSIGNED', 'Asset placed in location \'CM 103\' upon creation', 'Sta Rosa HCC', 1, '2025-10-28 13:12:45'),
(1896, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-28 13:20:42'),
(1897, NULL, 'STOCK_RESERVED', 'Stock of 1 reserved for request #120', 'Sta Rosa HCC', 1, '2025-10-28 13:21:21'),
(1898, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: LINOD6VY', 'Sta Rosa HCC', 1, '2025-10-28 13:21:21'),
(1899, NULL, 'ASSIGNED', 'Asset assigned to Mico Macapugay via approved request.', 'richard fermin', 1, '2025-10-28 13:22:41'),
(1900, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: LINOD6VY', 'richard fermin', 1, '2025-10-28 13:22:41'),
(1901, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. Fully returned.', 'Mico Macapugay', 1, '2025-10-28 13:29:08'),
(1902, NULL, 'STATUS_UPDATED', '1 unit(s) of asset \'Aircon\' marked as Inactive.', 'Sta Rosa HCC', 1, '2025-10-28 13:29:26'),
(1903, NULL, 'CREATED', 'Asset \'Aircon\' registered in the system', 'Sta Rosa HCC', 1, '2025-10-28 13:30:41'),
(1904, NULL, 'LOCATION_ASSIGNED', 'Asset placed in location \'Accreditation Room\' upon creation', 'Sta Rosa HCC', 1, '2025-10-28 13:30:41'),
(1905, NULL, 'STATUS_UPDATED', '1 unit(s) of asset \'Aircon\' marked as Inactive.', 'Sta Rosa HCC', 1, '2025-10-28 13:30:51'),
(1906, NULL, 'STATUS_UPDATED', '1 unit(s) of asset \'Aircon\' marked as Inactive.', 'Sta Rosa HCC', 1, '2025-10-28 13:31:12'),
(1907, NULL, 'STATUS_UPDATED', '14 unit(s) of asset \'Aircon\' marked as Inactive.', 'Sta Rosa HCC', 1, '2025-10-28 13:31:28'),
(1908, NULL, 'CREATED', 'Asset \'Candy\' registered in the system', 'Sta Rosa HCC', 1, '2025-10-28 13:32:44'),
(1909, NULL, 'LOCATION_ASSIGNED', 'Asset placed in location \'Accreditation Room\' upon creation', 'Sta Rosa HCC', 1, '2025-10-28 13:32:44'),
(1910, NULL, 'STATUS_UPDATED', '1 unit(s) of asset \'Candy\' marked as Inactive.', 'Sta Rosa HCC', 1, '2025-10-28 13:32:53'),
(1911, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-28 13:36:38'),
(1912, NULL, 'STATUS_UPDATED', '4 unit(s) of asset \'Candy\' marked as Inactive.', 'Sta Rosa HCC', 1, '2025-10-28 13:37:56'),
(1913, NULL, 'STATUS_UPDATED', '5 unit(s) of asset \'Candy\' marked as Inactive.', 'Sta Rosa HCC', 1, '2025-10-28 13:38:10'),
(1914, NULL, 'STOCK_RESERVED', 'Stock of 1 reserved for request #121', 'Sta Rosa HCC', 1, '2025-10-28 13:39:05'),
(1915, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: 4USK1XRZ', 'Sta Rosa HCC', 1, '2025-10-28 13:39:05'),
(1916, NULL, 'CREATED', 'Asset \'Aircon\' registered in the system', 'Sta Rosa HCC', 1, '2025-10-28 13:44:38'),
(1917, NULL, 'LOCATION_ASSIGNED', 'Asset placed in location \'Faculty Room\' upon creation', 'Sta Rosa HCC', 1, '2025-10-28 13:44:38'),
(1918, NULL, 'STATUS_UPDATED', '1 unit(s) of asset \'Aircon\' marked as Inactive.', 'Sta Rosa HCC', 1, '2025-10-28 13:44:49'),
(1919, NULL, 'STATUS_UPDATED', '1 unit(s) of asset \'Aircon\' marked as Active.', 'Sta Rosa HCC', 1, '2025-10-28 13:46:53'),
(1920, NULL, 'STATUS_UPDATED', '1 unit(s) of asset \'Candy\' marked as Active.', 'Sta Rosa HCC', 1, '2025-10-28 13:46:57'),
(1921, NULL, 'STATUS_UPDATED', '1 unit(s) of asset \'Candy\' marked as Active.', 'Sta Rosa HCC', 1, '2025-10-28 13:47:00'),
(1922, NULL, 'STATUS_UPDATED', '2 unit(s) of asset \'Candy\' marked as Inactive.', 'Sta Rosa HCC', 1, '2025-10-28 13:47:15'),
(1923, NULL, 'STATUS_UPDATED', '1 unit(s) of asset \'Candy\' marked as Inactive.', 'Sta Rosa HCC', 1, '2025-10-28 13:53:22'),
(1924, NULL, 'STATUS_UPDATED', '1 unit(s) of asset \'Candy\' marked as Active.', 'Sta Rosa HCC', 1, '2025-10-28 13:53:29'),
(1925, NULL, 'STATUS_UPDATED', '1 unit(s) of asset \'Candy\' marked as Active.', 'Sta Rosa HCC', 1, '2025-10-28 13:53:49'),
(1926, NULL, 'STATUS_UPDATED', '1 unit(s) of asset \'Candy\' marked as Inactive.', 'Sta Rosa HCC', 1, '2025-10-28 13:55:23'),
(1927, NULL, 'STATUS_UPDATED', '1 unit(s) of asset \'Candy\' marked as Active.', 'Sta Rosa HCC', 1, '2025-10-28 13:55:28'),
(1928, NULL, 'STATUS_UPDATED', '1 unit(s) of asset \'Candy\' marked as Active.', 'Sta Rosa HCC', 1, '2025-10-28 13:56:10'),
(1929, NULL, 'STATUS_UPDATED', '1 unit(s) of asset \'Candy\' marked as Inactive.', 'Sta Rosa HCC', 1, '2025-10-28 13:56:16'),
(1930, NULL, 'STATUS_UPDATED', '1 unit(s) of asset \'Candy\' marked as Inactive.', 'Sta Rosa HCC', 1, '2025-10-28 13:56:30'),
(1931, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-28 14:05:45'),
(1932, NULL, 'STOCK_RESERVED', 'Stock of 31 reserved for request #122', 'Sta Rosa HCC', 1, '2025-10-28 14:05:57'),
(1933, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: 4CSAD85Y', 'Sta Rosa HCC', 1, '2025-10-28 14:05:57'),
(1934, NULL, 'ASSIGNED', 'Asset assigned to Mico Macapugay via approved request.', 'richard fermin', 1, '2025-10-28 14:06:50'),
(1935, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: 4CSAD85Y', 'richard fermin', 1, '2025-10-28 14:06:50'),
(1936, NULL, 'ASSIGNED', 'Asset assigned to Mico Macapugay via approved request.', 'richard fermin', 1, '2025-10-28 14:06:57'),
(1937, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: 4USK1XRZ', 'richard fermin', 1, '2025-10-28 14:06:57'),
(1938, NULL, 'RETURNED', '31 unit(s) of asset returned by staff. Fully returned.', 'Mico Macapugay', 1, '2025-10-28 14:07:21'),
(1939, NULL, 'RETURNED', '1 unit(s) of asset returned by staff. Fully returned.', 'Mico Macapugay', 1, '2025-10-28 14:07:26'),
(1940, NULL, 'CREATED', 'Asset \'Computer\' registered in the system', 'Sta Rosa HCC', 1, '2025-10-28 14:15:14'),
(1941, NULL, 'LOCATION_ASSIGNED', 'Asset placed in location \'Faculty Room\' upon creation', 'Sta Rosa HCC', 1, '2025-10-28 14:15:14'),
(1942, NULL, 'CREATED', 'Asset \'Computer\' registered in the system', 'Sta Rosa HCC', 1, '2025-10-28 14:23:18'),
(1943, NULL, 'LOCATION_ASSIGNED', 'Asset placed in location \'Administration Office\' upon creation', 'Sta Rosa HCC', 1, '2025-10-28 14:23:18'),
(1944, NULL, 'STATUS_UPDATED', '11 unit(s) of asset \'Computer\' marked as Inactive.', 'Sta Rosa HCC', 1, '2025-10-28 14:23:36'),
(1945, NULL, 'CREATED', 'Asset \'Computer\' registered in the system', 'Sta Rosa HCC', 1, '2025-10-28 14:24:02'),
(1946, NULL, 'LOCATION_ASSIGNED', 'Asset placed in location \'Administration Office\' upon creation', 'Sta Rosa HCC', 1, '2025-10-28 14:24:02'),
(1947, NULL, 'STATUS_UPDATED', '1 unit(s) of asset \'Candy\' marked as Active.', 'Sta Rosa HCC', 1, '2025-10-28 14:24:07'),
(1948, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-29 00:34:35'),
(1949, NULL, 'REQUEST_REJECTED', 'Asset request rejected by admin. Reason: No reason provided.', 'Sta Rosa HCC', 1, '2025-10-29 00:35:25'),
(1950, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-29 00:35:42'),
(1951, NULL, 'STOCK_RESERVED', 'Stock of 5 reserved for request #124', 'Sta Rosa HCC', 1, '2025-10-29 00:36:37'),
(1952, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: L5Z1DY6Q', 'Sta Rosa HCC', 1, '2025-10-29 00:36:37'),
(1953, NULL, 'ASSIGNED', 'Asset assigned to Mico Macapugay via approved request.', 'richard fermin', 1, '2025-10-29 00:38:21'),
(1954, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: L5Z1DY6Q', 'richard fermin', 1, '2025-10-29 00:38:21'),
(1955, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-29 00:45:33'),
(1956, NULL, 'STOCK_RESERVED', 'Stock of 5 reserved for request #125', 'Sta Rosa HCC', 1, '2025-10-29 00:45:53'),
(1957, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: 45HPIO7V', 'Sta Rosa HCC', 1, '2025-10-29 00:45:53'),
(1958, NULL, 'ASSIGNED', 'Asset assigned to Mico Macapugay via approved request.', 'richard fermin', 1, '2025-10-29 00:47:29'),
(1959, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: 45HPIO7V', 'richard fermin', 1, '2025-10-29 00:47:29'),
(1960, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-29 00:47:53'),
(1961, NULL, 'STOCK_RESERVED', 'Stock of 6 reserved for request #126', 'Sta Rosa HCC', 1, '2025-10-29 00:48:13'),
(1962, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: AX3W6G71', 'Sta Rosa HCC', 1, '2025-10-29 00:48:13'),
(1963, NULL, 'ASSIGNED', 'Added 6 unit(s) to existing assignment for Mico Macapugay.', 'richard fermin', 1, '2025-10-29 00:49:01'),
(1964, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: AX3W6G71', 'richard fermin', 1, '2025-10-29 00:49:01'),
(1965, NULL, 'RETURNED', '11 unit(s) of asset returned by staff. Fully returned.', 'Mico Macapugay', 1, '2025-10-29 00:49:32'),
(1966, NULL, 'EMAIL_SENT', 'Account creation email sent to jennamariesolitario@gmail.com.', 'Sta Rosa HCC', 1, '2025-10-29 02:02:10'),
(1967, NULL, 'CREATED', 'Asset \'Camera\' registered in the system', 'Sta Rosa HCC', 1, '2025-10-29 02:33:11'),
(1968, NULL, 'LOCATION_ASSIGNED', 'Asset placed in location \'Lounge Room\' upon creation', 'Sta Rosa HCC', 1, '2025-10-29 02:33:11'),
(1969, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-29 07:12:39'),
(1970, NULL, 'STOCK_RESERVED', 'Stock of 30 reserved for request #127', 'Sta Rosa HCC', 1, '2025-10-29 07:12:56'),
(1971, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: 7S4UOT3E', 'Sta Rosa HCC', 1, '2025-10-29 07:12:56'),
(1972, NULL, 'ASSIGNED', 'Asset assigned to Mico Macapugay via approved request.', 'richard fermin', 1, '2025-10-29 07:13:53'),
(1973, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: 7S4UOT3E', 'richard fermin', 1, '2025-10-29 07:13:53'),
(1974, NULL, 'RETURNED', '30 unit(s) of asset returned by staff. Fully returned. Notes: dddd', 'Mico Macapugay', 1, '2025-10-29 07:15:10'),
(1975, NULL, 'STATUS_UPDATED', '9 unit(s) of asset \'Candy\' marked as Active.', 'Sta Rosa HCC', 1, '2025-10-29 07:15:48'),
(1976, NULL, 'RETURNED', '5 unit(s) of asset returned by staff. Fully returned.', 'Mico Macapugay', 1, '2025-10-29 08:24:13'),
(1977, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-29 08:24:45'),
(1978, NULL, 'STOCK_RESERVED', 'Stock of 2 reserved for request #128', 'Sta Rosa HCC', 1, '2025-10-29 08:25:36'),
(1979, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: DLNEXIM6', 'Sta Rosa HCC', 1, '2025-10-29 08:25:36'),
(1980, NULL, 'STATUS_UPDATED', '1 unit(s) of asset \'Candy\' marked as Inactive.', 'Sta Rosa HCC', 1, '2025-10-29 08:28:35'),
(1981, NULL, 'STATUS_UPDATED', '99 unit(s) of asset \'Candy\' marked as Inactive.', 'Sta Rosa HCC', 1, '2025-10-29 08:28:43'),
(1982, NULL, 'ASSIGNED', 'Asset assigned to Mico Macapugay via approved request.', 'richard fermin', 1, '2025-10-29 08:32:52'),
(1983, NULL, 'RELEASED', 'Asset released to staff by custodian. Receipt Code: DLNEXIM6', 'richard fermin', 1, '2025-10-29 08:32:52'),
(1984, NULL, 'REQUESTED', 'Asset requested by staff member Mico Macapugay', 'Mico Macapugay', 1, '2025-10-29 09:09:01'),
(1985, NULL, 'STOCK_RESERVED', 'Stock of 1 reserved for request #129', 'Sta Rosa HCC', 1, '2025-10-29 09:14:23'),
(1986, NULL, 'REQUEST_APPROVED', 'Asset request approved by admin. Receipt Code: F8KLGNR4', 'Sta Rosa HCC', 1, '2025-10-29 09:14:23'),
(1987, NULL, 'USER_CREATED', 'New user created by IT Admin: Micole Macapugay (mico.macapugay2004@gmail.com) with role office.', 'Mico Macapugay', 1, '2025-11-01 04:15:39'),
(1988, NULL, 'EMAIL_SENT', 'Account creation email sent to mico.macapugay2004@gmail.com.', 'Mico Macapugay', 1, '2025-11-01 04:16:09'),
(1989, 202, 'CREATED', 'Asset created: Printer', 'richard fermin', 1, '2025-11-01 05:26:25'),
(1990, 205, 'CREATED', 'Asset created: Aircon', 'richard fermin', 1, '2025-11-01 05:39:34'),
(1991, NULL, 'USER_CREATED', 'New user created by IT Admin: Micole Macapugay (mico.macapugay2004@gmail.com) with role office.', 'richard fermin', 1, '2025-11-01 06:23:33'),
(1992, NULL, 'EMAIL_SENT', 'Account creation email sent to mico.macapugay2004@gmail.com.', 'richard fermin', 1, '2025-11-01 06:24:04'),
(1993, 206, 'CREATED', 'Asset created: Chair', 'richard fermin', 1, '2025-11-01 06:47:38'),
(1994, NULL, 'USER_CREATED', 'New user created by IT Admin: Micole Macapugay (mico.macapugay2004@gmail.com) with role office.', 'richard fermin', 1, '2025-11-01 07:47:13'),
(1995, NULL, 'EMAIL_SENT', 'Account creation email sent to mico.macapugay2004@gmail.com.', 'richard fermin', 1, '2025-11-01 07:47:44'),
(1996, NULL, 'USER_CREATED', 'New user created by IT Admin: Micole Macapugay (mico.macapugay2004@gmail.com) with role office.', 'richard fermin', 1, '2025-11-01 07:52:26'),
(1997, NULL, 'EMAIL_SENT', 'Account creation email sent to mico.macapugay2004@gmail.com.', 'richard fermin', 1, '2025-11-01 07:52:56'),
(1998, NULL, 'USER_CREATED', 'New user created by IT Admin: Micole Macapugay (mico.macapugay2004@gmail.com) with role office.', 'richard fermin', 1, '2025-11-01 07:54:01'),
(1999, NULL, 'EMAIL_SENT', 'Account creation email sent to mico.macapugay2004@gmail.com.', 'richard fermin', 1, '2025-11-01 07:54:32'),
(2000, 207, 'CREATED', 'Asset created: Table', 'richard fermin', 1, '2025-11-01 08:37:53'),
(2001, 207, 'TAG_GENERATED', 'Inventory tag #MIS-110125-6293 generated for office ID #5 by custodian. Quantity: 1', 'richard fermin', 1, '2025-11-01 09:24:21'),
(2002, NULL, 'EMAIL_SENT', 'Tag generation notification sent to office ID 5 for tag MIS-110125-6293.', 'richard fermin', 1, '2025-11-01 09:24:51'),
(2003, NULL, 'USER_CREATED', 'New user created by IT Admin: Micole Macapugay (mico.macapugay2004@gmail.com) with role office.', 'System', NULL, '2025-11-01 09:55:32'),
(2004, NULL, 'EMAIL_SENT', 'Account creation email sent to mico.macapugay2004@gmail.com.', 'System', NULL, '2025-11-01 09:56:05'),
(2005, NULL, 'USER_CREATED', 'New user created by IT Admin: Jenna Solitario (jennamariesolitario@gmail.com) with role office.', 'System', NULL, '2025-11-01 10:29:40'),
(2006, NULL, 'EMAIL_SENT', 'Account creation email sent to jennamariesolitario@gmail.com.', 'System', NULL, '2025-11-01 10:30:10'),
(2007, 207, 'TAG_GENERATED', 'Inventory tag #MIS-110125-8848 generated for office ID #7 by custodian. Quantity: 1', 'richard fermin', 1, '2025-11-01 10:32:08'),
(2008, NULL, 'EMAIL_SENT', 'Tag generation notification sent to office ID 7 for tag MIS-110125-8848.', 'richard fermin', 1, '2025-11-01 10:32:38'),
(2009, 206, 'TAG_GENERATED', 'Inventory tag #MIS-110125-3587 generated for office ID #7 by custodian. Quantity: 1', 'richard fermin', 1, '2025-11-01 10:49:05'),
(2010, NULL, 'EMAIL_SENT', 'Tag generation notification sent to office ID 7 for tag MIS-110125-3587.', 'richard fermin', 1, '2025-11-01 10:49:35'),
(2011, 206, 'ASSET_VERIFIED', 'Office user Jenna Solitario verified receipt of the asset.', 'Jenna Solitario', 1, '2025-11-01 10:56:30'),
(2012, 206, 'STATUS_UPDATED', 'Office user Jenna Solitario updated asset status from \'Active\' to \'In Storage\'. Remarks: not use', 'Jenna Solitario', 1, '2025-11-01 11:19:20'),
(2013, 207, 'STATUS_UPDATED', 'Office user Jenna Solitario updated asset status from \'Active\' to \'Active\'. Remarks: use', 'Jenna Solitario', 1, '2025-11-01 11:20:13'),
(2014, 207, 'STATUS_UPDATED', 'Office user Jenna Solitario updated asset status from \'Active\' to \'In Storage\'. Remarks: not use', 'Jenna Solitario', 1, '2025-11-01 11:21:00'),
(2015, 206, 'STATUS_UPDATED', 'Office user Jenna Solitario updated asset status from \'\' to \'Active\'. Remarks: used', 'Jenna Solitario', 1, '2025-11-01 11:22:41'),
(2016, 207, 'STATUS_UPDATED', 'Office user Jenna Solitario updated asset status from \'\' to \'In Storage\'. Remarks: not use', 'Jenna Solitario', 1, '2025-11-01 11:22:53'),
(2017, 207, 'STATUS_UPDATED', 'Office user Jenna Solitario updated asset status from \'\' to \'Active\'. Remarks: used', 'Jenna Solitario', 1, '2025-11-01 11:23:13'),
(2018, 206, 'STATUS_UPDATED', 'Office user Jenna Solitario updated asset status from \'Active\' to \'In Storage\'. Remarks: not used', 'Jenna Solitario', 1, '2025-11-01 11:24:43'),
(2019, 206, 'STATUS_UPDATED', 'Office user Jenna Solitario updated asset status from \'\' to \'Active\'. Remarks: used', 'Jenna Solitario', 1, '2025-11-01 11:25:13'),
(2020, 206, 'STATUS_UPDATED', 'Office user Jenna Solitario updated asset status from \'Active\' to \'In Storage\'. Remarks: storage', 'Jenna Solitario', 1, '2025-11-01 11:25:48'),
(2021, 206, 'STATUS_UPDATED', 'Office user Jenna Solitario updated asset status from \'\' to \'Active\'. Remarks: used', 'Jenna Solitario', 1, '2025-11-01 11:26:39'),
(2022, 206, 'STATUS_UPDATED', 'Office user Jenna Solitario updated asset status from \'Active\' to \'In Storage\'. Remarks: not used', 'Jenna Solitario', 1, '2025-11-01 11:27:23'),
(2023, 206, 'STATUS_UPDATED', 'Office user Jenna Solitario updated asset status from \'\' to \'Active\'. Remarks: new\r\n', 'Jenna Solitario', 1, '2025-11-01 11:27:32'),
(2024, 206, 'STATUS_UPDATED', 'Office user Jenna Solitario updated asset status from \'Active\' to \'In Storage\'. Remarks: s', 'Jenna Solitario', 1, '2025-11-01 11:29:30'),
(2025, 206, 'STATUS_UPDATED', 'Office user Jenna Solitario updated asset status from \'\' to \'Active\'. Remarks: a', 'Jenna Solitario', 1, '2025-11-01 11:29:47');
INSERT INTO `activity_log` (`id`, `asset_id`, `action`, `description`, `performed_by`, `campus_id`, `created_at`) VALUES
(2026, 206, 'STATUS_UPDATED', 'Office user Jenna Solitario updated asset status from \'Active\' to \'In Storage\'. Remarks: k', 'Jenna Solitario', 1, '2025-11-01 11:30:41'),
(2027, 206, 'STATUS_UPDATED', 'Office user Jenna Solitario updated asset status from \'\' to \'Active\'. Remarks: k', 'Jenna Solitario', 1, '2025-11-01 11:30:48'),
(2028, NULL, 'USER_CREATED', 'New user created by IT Admin: Micole Macapugay (mico.macapugay@icloud.com) with role employee.', 'Mico Macapugay', 1, '2025-11-01 11:41:51'),
(2029, NULL, 'EMAIL_SENT', 'Account creation email sent to mico.macapugay@icloud.com.', 'Mico Macapugay', 1, '2025-11-01 11:42:21'),
(2030, NULL, 'USER_CREATED', 'New user created by IT Admin: Shaina (shainalyncruz3@gmail.com) with role office.', 'System', NULL, '2025-11-01 13:27:16'),
(2031, NULL, 'EMAIL_SENT', 'Account creation email sent to shainalyncruz3@gmail.com.', 'System', NULL, '2025-11-01 13:27:46'),
(2032, 206, 'TAG_GENERATED', 'Inventory tag #MIS-110125-4152 generated for office ID #8 by custodian. Quantity: 1', 'richard fermin', 1, '2025-11-01 13:30:07'),
(2033, NULL, 'EMAIL_SENT', 'Tag generation notification sent to office ID 8 for tag MIS-110125-4152.', 'richard fermin', 1, '2025-11-01 13:30:38'),
(2034, 206, 'ASSET_VERIFIED', 'Office user Shaina verified receipt of the asset.', 'Shaina', 1, '2025-11-01 13:32:50'),
(2035, NULL, 'USER_CREATED', 'New user created by IT Admin: Jenna Solitario (jennamariesolitario@gmail.com) with role office.', 'System', NULL, '2025-11-01 13:44:32'),
(2036, NULL, 'EMAIL_SENT', 'Account creation email sent to jennamariesolitario@gmail.com.', 'System', NULL, '2025-11-01 13:45:06'),
(2037, 206, 'TAG_GENERATED', 'Inventory tag #MIS-110125-9639 generated for office ID #9 by custodian. Quantity: 1', 'richard fermin', 1, '2025-11-01 13:46:18'),
(2038, NULL, 'EMAIL_SENT', 'Tag generation notification sent to office ID 9 for tag MIS-110125-9639.', 'richard fermin', 1, '2025-11-01 13:46:50'),
(2039, 206, 'ASSET_VERIFIED', 'Office user Jenna Solitario verified receipt of the asset.', 'Jenna Solitario', 1, '2025-11-01 13:47:00'),
(2040, NULL, 'USER_CREATED', 'New user created by IT Admin: Jenna Solitario (jennamariesolitario@gmail.com) with role office.', 'System', NULL, '2025-11-01 13:49:19'),
(2041, NULL, 'EMAIL_SENT', 'Account creation email sent to jennamariesolitario@gmail.com.', 'System', NULL, '2025-11-01 13:49:51'),
(2042, NULL, 'USER_CREATED', 'New user created by IT Admin: Micole Macapugay (mico.macapugay2004@gmail.com) with role office.', 'System', NULL, '2025-11-01 14:09:44'),
(2043, NULL, 'EMAIL_SENT', 'Account creation email sent to mico.macapugay2004@gmail.com.', 'System', NULL, '2025-11-01 14:10:15'),
(2044, 206, 'TAG_GENERATED', 'Inventory tag #MIS-110125-9398 generated for office ID #16 by custodian. Quantity: 1', 'richard fermin', 1, '2025-11-01 14:21:09'),
(2045, NULL, 'EMAIL_SENT', 'Tag generation notification sent to office ID 16 for tag MIS-110125-9398.', 'richard fermin', 1, '2025-11-01 14:21:39'),
(2046, 206, 'TAG_GENERATED', 'Inventory tag #MIS-110525-0341 generated for office ID #16 by custodian. Quantity: 10', 'richard fermin', 1, '2025-11-05 11:31:36'),
(2047, NULL, 'EMAIL_SENT', 'Tag generation notification sent to office ID 16 for tag MIS-110525-0341.', 'richard fermin', 1, '2025-11-05 11:32:07'),
(2048, 208, 'CREATED', 'Asset created: Board', 'Mico ', 1, '2025-11-06 10:45:12'),
(2049, 208, 'TAG_GENERATED', 'Inventory tag #MIS-110625-3207 generated for office ID #16 by custodian. Quantity: 1', 'Mico ', 1, '2025-11-06 10:45:36'),
(2050, NULL, 'EMAIL_SENT', 'Tag generation notification sent to office ID 16 for tag MIS-110625-3207.', 'Mico ', 1, '2025-11-06 10:46:08'),
(2051, 206, 'REQUEST_SUBMITTED', 'Asset request submitted by Micy for 1 unit(s)', 'Micy', 1, '2025-11-07 14:24:01'),
(2052, 206, 'REQUEST_APPROVED_CUSTODIAN', 'Request #131 approved by custodian', 'Mico ', 1, '2025-11-07 14:24:46'),
(2053, 206, 'REQUEST_SUBMITTED', 'Asset request submitted by Micy for 1 unit(s)', 'Micy', 1, '2025-11-07 14:36:36'),
(2054, 206, 'REQUEST_SUBMITTED', 'Asset request submitted by Micy for 1 unit(s)', 'Micy', 1, '2025-11-07 14:39:45'),
(2055, 206, 'REQUEST_SUBMITTED', 'Asset request submitted by Micy for 1 unit(s)', 'Micy', 1, '2025-11-07 14:43:11'),
(2056, 206, 'REQUEST_SUBMITTED', 'Asset request submitted by Micy for 1 unit(s)', 'Micy', 1, '2025-11-07 14:46:43'),
(2057, 206, 'REQUEST_SUBMITTED', 'Asset request submitted by Micy for 1 unit(s)', 'Micy', 1, '2025-11-07 14:51:53'),
(2058, 206, 'REQUEST_SUBMITTED', 'Asset request submitted by Micy for 1 unit(s)', 'Micy', 1, '2025-11-07 15:00:04'),
(2059, 206, 'REQUEST_SUBMITTED', 'Asset request submitted by Micy for 2 unit(s)', 'Micy', 1, '2025-11-07 15:01:01'),
(2060, 206, 'REQUEST_SUBMITTED', 'Asset request submitted by Micy for 3 unit(s)', 'Micy', 1, '2025-11-07 15:05:03'),
(2061, 206, 'REQUEST_SUBMITTED', 'Asset request submitted by Micy for 5 unit(s)', 'Micy', 1, '2025-11-07 15:05:50'),
(2062, 206, 'REQUEST_APPROVED_CUSTODIAN', 'Request #143 approved by custodian', 'Mico ', 1, '2025-11-07 15:07:31'),
(2063, 206, 'REQUEST_APPROVED_CUSTODIAN', 'Request #142 approved by custodian', 'Mico ', 1, '2025-11-07 15:07:37'),
(2064, 206, 'REQUEST_APPROVED_CUSTODIAN', 'Request #141 approved by custodian', 'Mico ', 1, '2025-11-07 15:10:04'),
(2065, 206, 'REQUEST_APPROVED_ADMIN', 'Request #143 approved by admin - ready for release', 'Mico ', 1, '2025-11-07 15:12:47'),
(2066, 206, 'ASSET_RELEASED', 'Asset released to requester via request #143', 'Mico ', 1, '2025-11-07 15:24:37'),
(2067, 206, 'REQUEST_APPROVED_ADMIN', 'Request #142 approved by admin - ready for release', 'Mico ', 1, '2025-11-07 15:32:45'),
(2068, 206, 'REQUEST_APPROVED_ADMIN', 'Request #141 approved by admin - ready for release', 'Mico ', 1, '2025-11-07 15:32:57'),
(2069, 206, 'ASSET_RELEASED', 'Asset released to requester via request #142', 'Mico ', 1, '2025-11-07 15:34:27'),
(2070, 206, 'ASSET_RELEASED', 'Asset released to requester via request #141', 'Mico ', 1, '2025-11-07 15:36:55'),
(2071, 206, 'ASSET_RETURNED', 'Asset returned in Good condition via request #143', 'Mico ', 1, '2025-11-07 15:39:25'),
(2072, 206, 'ASSET_RETURNED', 'Asset returned in Good condition via request #142', 'Mico ', 1, '2025-11-07 15:44:01'),
(2073, 206, 'ASSET_RETURNED', 'Asset returned in Good condition via request #141', 'Mico ', 1, '2025-11-07 15:47:02'),
(2074, 208, 'TAG_GENERATED', 'Inventory tag #MIS-110825-2998 generated for office ID #16 by custodian. Quantity: 1', 'Mico ', 1, '2025-11-07 17:05:05'),
(2075, NULL, 'EMAIL_SENT', 'Tag generation notification sent to office ID 16 for tag MIS-110825-2998.', 'Mico ', 1, '2025-11-07 17:05:36'),
(2076, 206, 'REQUEST_SUBMITTED', 'Asset request submitted by Micy for 7 unit(s)', 'Micy', 1, '2025-11-07 17:07:27'),
(2077, 206, 'REQUEST_APPROVED_CUSTODIAN', 'Request #144 approved by custodian', 'Mico ', 1, '2025-11-07 17:07:43'),
(2078, 206, 'REQUEST_SUBMITTED', 'Asset request submitted by Micy for 8 unit(s)', 'Micy', 1, '2025-11-07 17:08:47'),
(2079, 206, 'REQUEST_APPROVED_ADMIN', 'Request #144 approved by admin - ready for release', 'Mico ', 1, '2025-11-07 17:08:58'),
(2080, 206, 'REQUEST_APPROVED_ADMIN', 'Request #131 approved by admin - ready for release', 'Mico ', 1, '2025-11-07 17:09:13'),
(2081, 206, 'ASSET_RELEASED', 'Asset released to requester via request #144', 'Mico ', 1, '2025-11-07 17:09:58');

-- --------------------------------------------------------

--
-- Table structure for table `assets`
--

CREATE TABLE `assets` (
  `id` int(11) NOT NULL,
  `asset_name` varchar(255) NOT NULL,
  `category_id` int(11) NOT NULL,
  `status` enum('Active','Inactive','Damaged','Missing','Under Repair','Retired') DEFAULT 'Active' COMMENT 'Asset status: Active=in use, Inactive=in storage/available, Damaged=needs repair, Missing=lost/untraceable, Under Repair=being fixed, Retired=decommissioned',
  `campus_id` int(11) NOT NULL,
  `office_id` int(11) DEFAULT NULL,
  `location` varchar(255) NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `brand_id` int(11) DEFAULT NULL,
  `purchase_date` date NOT NULL,
  `value` decimal(15,2) NOT NULL,
  `original_value` decimal(15,2) DEFAULT NULL COMMENT 'Original purchase value',
  `current_value` decimal(15,2) DEFAULT NULL COMMENT 'Current depreciated value',
  `depreciation_rate` decimal(5,2) DEFAULT 0.00 COMMENT 'Annual depreciation rate in percentage',
  `last_depreciation_date` date DEFAULT NULL COMMENT 'Last date depreciation was calculated',
  `inventory_date` date DEFAULT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `inactive_quantity` int(11) NOT NULL DEFAULT 0,
  `serial_number` varchar(100) DEFAULT NULL,
  `barcode` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `assigned_to` varchar(255) DEFAULT NULL,
  `assigned_email` varchar(255) DEFAULT NULL,
  `assigned_to_id` int(11) DEFAULT NULL,
  `assignment_date` date DEFAULT NULL,
  `unassigned_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `size` varchar(100) DEFAULT NULL,
  `article` varchar(255) DEFAULT NULL,
  `counted_by` varchar(255) DEFAULT NULL,
  `checked_by` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `assets`
--

INSERT INTO `assets` (`id`, `asset_name`, `category_id`, `status`, `campus_id`, `office_id`, `location`, `room_id`, `brand_id`, `purchase_date`, `value`, `original_value`, `current_value`, `depreciation_rate`, `last_depreciation_date`, `inventory_date`, `supplier`, `quantity`, `inactive_quantity`, `serial_number`, `barcode`, `description`, `assigned_to`, `assigned_email`, `assigned_to_id`, `assignment_date`, `unassigned_date`, `created_at`, `updated_at`, `created_by`, `remarks`, `size`, `article`, `counted_by`, `checked_by`) VALUES
(202, 'Printer', 8, 'Inactive', 1, NULL, 'Library', NULL, NULL, '2025-10-30', 22000.00, 22000.00, 22000.00, 0.00, NULL, '2025-11-01', 'Willman', 1, 0, '', '', '', NULL, NULL, NULL, NULL, NULL, '2025-11-01 05:26:25', '2025-11-06 10:06:40', 140, 'WORKING', NULL, NULL, NULL, NULL),
(205, 'Aircon', 8, 'Inactive', 1, NULL, 'Library', NULL, NULL, '2025-10-30', 15000.00, 15000.00, 15000.00, 0.00, NULL, '2025-11-01', 'Willman', 1, 0, 'HCC2501083708', 'HCC2501083708', '', NULL, NULL, NULL, NULL, NULL, '2025-11-01 05:39:34', '2025-11-06 10:06:40', 140, 'WORKING', NULL, NULL, NULL, NULL),
(206, 'Chair', 8, 'Active', 1, NULL, 'Room 1', NULL, NULL, '2025-10-30', 450.00, 450.00, 450.00, 0.00, NULL, '2025-11-01', 'Willman', 21, 0, 'HCC2501081990', 'HCC2501081990', '', NULL, NULL, NULL, NULL, NULL, '2025-11-01 06:47:38', '2025-11-06 10:06:40', 140, 'WORKING', NULL, NULL, NULL, NULL),
(207, 'Table', 2, 'Inactive', 1, NULL, 'MIS', NULL, NULL, '2025-10-31', 1200.00, 1200.00, 1200.00, 0.00, NULL, '2025-11-01', 'Willman', 1, 0, 'HCC2501021481', 'HCC2501021481', '', NULL, NULL, NULL, NULL, NULL, '2025-11-01 08:37:53', '2025-11-06 10:06:40', 140, 'WORKING', NULL, NULL, NULL, NULL),
(208, 'Board', 3, 'Inactive', 1, NULL, 'Room 101', NULL, NULL, '2025-11-06', 1000.00, 1000.00, 1000.00, 0.00, NULL, '2025-11-06', 'Willman', 23, 0, 'HCC2501036846', 'HCC2501036846', '', NULL, NULL, NULL, NULL, NULL, '2025-11-06 10:45:12', '2025-11-07 17:05:05', 160, 'WORKING', NULL, NULL, NULL, NULL);

--
-- Triggers `assets`
--
DELIMITER $$
CREATE TRIGGER `trg_asset_depreciation_init` BEFORE INSERT ON `assets` FOR EACH ROW BEGIN
    IF NEW.original_value IS NULL THEN
        SET NEW.original_value = NEW.value;
    END IF;

    IF NEW.current_value IS NULL THEN
        SET NEW.current_value = NEW.value;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_asset_location_change` AFTER UPDATE ON `assets` FOR EACH ROW BEGIN
    IF (OLD.location != NEW.location) OR
       (OLD.room_id != NEW.room_id OR (OLD.room_id IS NULL AND NEW.room_id IS NOT NULL) OR (OLD.room_id IS NOT NULL AND NEW.room_id IS NULL)) OR
       (OLD.office_id != NEW.office_id OR (OLD.office_id IS NULL AND NEW.office_id IS NOT NULL) OR (OLD.office_id IS NOT NULL AND NEW.office_id IS NULL)) THEN

        INSERT INTO `asset_movement_logs`
        (`asset_id`, `from_location`, `to_location`, `from_room_id`, `to_room_id`, `from_office_id`, `to_office_id`, `movement_type`, `campus_id`)
        VALUES
        (NEW.id, OLD.location, NEW.location, OLD.room_id, NEW.room_id, OLD.office_id, NEW.office_id, 'transfer', NEW.campus_id);
    END IF;
END
$$
DELIMITER ;
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
  `assigned_to_id` int(11) DEFAULT NULL,
  `assigned_to` varchar(255) NOT NULL,
  `assigned_email` varchar(255) NOT NULL,
  `assigned_by` varchar(255) DEFAULT 'Administrator',
  `assignment_date` date NOT NULL,
  `quantity` int(11) DEFAULT NULL,
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
  `status` enum('active','returned','overdue','not_returned','lost') NOT NULL DEFAULT 'active',
  `return_date` datetime DEFAULT NULL,
  `actual_return_date` datetime DEFAULT NULL COMMENT 'Actual date item was returned',
  `return_status` enum('On Time','Returned Late','Overdue','Not Returned') DEFAULT NULL COMMENT 'Return status tracking',
  `overdue_notification_sent` tinyint(1) DEFAULT 0 COMMENT 'Flag if overdue notification was sent',
  `reminder_sent_date` datetime DEFAULT NULL COMMENT 'Date when reminder was sent',
  `days_overdue` int(11) DEFAULT 0 COMMENT 'Number of days overdue',
  `last_known_borrower` varchar(255) DEFAULT NULL COMMENT 'Last person who had the item',
  `condition_on_return` text DEFAULT NULL COMMENT 'Condition remarks on return: Complete, Missing parts, Damaged, etc.',
  `return_notes` text DEFAULT NULL,
  `recorded_by` varchar(255) DEFAULT 'Staff User',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `asset_borrowings`
--
DELIMITER $$
CREATE TRIGGER `trg_calculate_return_status` BEFORE UPDATE ON `asset_borrowings` FOR EACH ROW BEGIN
    IF NEW.status = 'returned' AND OLD.status = 'active' THEN
        SET NEW.actual_return_date = NOW();

        IF NEW.expected_return_date IS NOT NULL THEN
            IF DATE(NEW.actual_return_date) > NEW.expected_return_date THEN
                SET NEW.return_status = 'Returned Late';
                SET NEW.days_overdue = DATEDIFF(DATE(NEW.actual_return_date), NEW.expected_return_date);
            ELSE
                SET NEW.return_status = 'On Time';
                SET NEW.days_overdue = 0;
            END IF;
        END IF;
    END IF;

    IF NEW.status IN ('active', 'overdue') THEN
        SET NEW.last_known_borrower = NEW.borrower_name;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `asset_details`
-- (See below for the actual view)
--
CREATE TABLE `asset_details` (
`id` int(11)
,`asset_name` varchar(255)
,`category_name` varchar(50)
,`status` enum('Active','Inactive','Damaged','Missing','Under Repair','Retired')
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
  `next_maintenance_date` date DEFAULT NULL COMMENT 'Scheduled next maintenance',
  `status` enum('Pending','In Progress','Completed','Cancelled') DEFAULT 'Completed',
  `maintenance_date` date NOT NULL,
  `cost` decimal(10,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asset_movement_logs`
--

CREATE TABLE `asset_movement_logs` (
  `id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `from_location` varchar(255) DEFAULT NULL COMMENT 'Previous location',
  `to_location` varchar(255) NOT NULL COMMENT 'New location',
  `from_room_id` int(11) DEFAULT NULL,
  `to_room_id` int(11) DEFAULT NULL,
  `from_office_id` int(11) DEFAULT NULL,
  `to_office_id` int(11) DEFAULT NULL,
  `movement_type` enum('deployment','transfer','return','audit','maintenance','storage') NOT NULL,
  `moved_by` int(11) DEFAULT NULL COMMENT 'User who moved the asset',
  `reason` text DEFAULT NULL,
  `moved_date` datetime NOT NULL DEFAULT current_timestamp(),
  `verified_by` int(11) DEFAULT NULL COMMENT 'User who verified the movement',
  `verified_date` datetime DEFAULT NULL,
  `campus_id` int(11) NOT NULL,
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
(3, 'Aircon'),
(21, 'Armchair'),
(27, 'Bookshelf'),
(26, 'Cabinet'),
(16, 'Camera'),
(44, 'Candy'),
(31, 'CCTV Camera'),
(18, 'Clock'),
(5, 'Computer'),
(4, 'Electric Fan'),
(6, 'Laptop'),
(15, 'Microphone'),
(20, 'Monoblock Chair'),
(22, 'Office Chair'),
(11, 'Photocopier'),
(28, 'Podium'),
(10, 'Printer'),
(8, 'Projector'),
(30, 'Refrigerator'),
(12, 'Router'),
(50, 'SAMPLE'),
(32, 'Smart TV'),
(14, 'Speaker'),
(23, 'Student Chair'),
(25, 'Student Table'),
(13, 'Switch'),
(7, 'Tablet'),
(24, 'Teacher’s Table'),
(17, 'Telephone'),
(9, 'Television'),
(49, 'TESTING'),
(29, 'Water Dispenser'),
(19, 'Whiteboard');

-- --------------------------------------------------------

--
-- Table structure for table `asset_name_brands`
--

CREATE TABLE `asset_name_brands` (
  `asset_name_id` int(11) NOT NULL,
  `brand_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asset_receipts`
--

CREATE TABLE `asset_receipts` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL COMMENT 'FK to asset_requests.id',
  `asset_id` int(11) NOT NULL COMMENT 'FK to assets.id',
  `user_id` int(11) NOT NULL COMMENT 'FK to users.id (the staff who requested)',
  `receipt_code` varchar(50) NOT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asset_requests`
--

CREATE TABLE `asset_requests` (
  `id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `requester_id` int(11) NOT NULL,
  `campus_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `purpose` text DEFAULT NULL,
  `expected_return_date` date DEFAULT NULL,
  `status` enum('pending','custodian_review','department_review','approved','rejected','released','returned','cancelled') NOT NULL DEFAULT 'pending',
  `reminder_sent` tinyint(1) NOT NULL DEFAULT 0,
  `approved_by` int(11) DEFAULT NULL,
  `custodian_reviewed_by` int(11) DEFAULT NULL COMMENT 'Custodian who reviewed first',
  `custodian_reviewed_at` datetime DEFAULT NULL,
  `custodian_review_notes` text DEFAULT NULL,
  `department_approved_by` int(11) DEFAULT NULL COMMENT 'Department head approval',
  `department_approved_at` datetime DEFAULT NULL,
  `final_approved_by` int(11) DEFAULT NULL COMMENT 'Final admin approval',
  `final_approved_at` datetime DEFAULT NULL,
  `condition_remarks` text DEFAULT NULL COMMENT 'Condition: Complete, Missing Ink, Damaged, etc.',
  `approval_level_required` enum('department','custodian','admin','all') DEFAULT 'all',
  `approved_at` datetime DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `released_by` int(11) DEFAULT NULL,
  `released_date` datetime DEFAULT NULL,
  `release_notes` text DEFAULT NULL,
  `returned_date` datetime DEFAULT NULL,
  `returned_by` int(11) DEFAULT NULL,
  `return_condition` varchar(50) DEFAULT NULL,
  `return_notes` text DEFAULT NULL,
  `receipt_code` varchar(10) DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `review_date` timestamp NULL DEFAULT NULL,
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `approver_id` int(11) DEFAULT NULL,
  `approval_date` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `unique_code` varchar(20) DEFAULT NULL,
  `completed_by_id` int(11) DEFAULT NULL,
  `completion_date` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `asset_requests`
--

INSERT INTO `asset_requests` (`id`, `asset_id`, `requester_id`, `campus_id`, `quantity`, `purpose`, `expected_return_date`, `status`, `reminder_sent`, `approved_by`, `custodian_reviewed_by`, `custodian_reviewed_at`, `custodian_review_notes`, `department_approved_by`, `department_approved_at`, `final_approved_by`, `final_approved_at`, `condition_remarks`, `approval_level_required`, `approved_at`, `admin_notes`, `released_by`, `released_date`, `release_notes`, `returned_date`, `returned_by`, `return_condition`, `return_notes`, `receipt_code`, `reviewed_by`, `review_date`, `request_date`, `approver_id`, `approval_date`, `rejection_reason`, `unique_code`, `completed_by_id`, `completion_date`) VALUES
(131, 206, 161, 1, 1, 'itasasasasasasa', '2025-11-08', 'approved', 0, NULL, 160, '2025-11-07 22:24:46', '', NULL, NULL, 160, '2025-11-08 01:09:13', NULL, 'all', NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07 14:24:01', NULL, NULL, NULL, NULL, NULL, NULL),
(132, 206, 161, 1, 1, 'fgfdsasdfgfdsadffds', '2025-11-08', 'pending', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'all', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07 14:36:36', NULL, NULL, NULL, NULL, NULL, NULL),
(133, 206, 161, 1, 1, 'hjkkkkkkkkkkkkk', '2025-11-08', 'pending', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'all', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07 14:39:45', NULL, NULL, NULL, NULL, NULL, NULL),
(134, 206, 161, 1, 1, 'pppppppppppppppppppp', '2025-11-08', 'pending', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'all', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07 14:42:41', NULL, NULL, NULL, NULL, NULL, NULL),
(135, 206, 161, 1, 1, 'poiuytrewqghjk', '2025-11-08', 'pending', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'all', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07 14:45:43', NULL, NULL, NULL, NULL, NULL, NULL),
(136, 206, 161, 1, 1, 'plllllllllllllllllllllllllllllllllllll', '2025-11-08', 'pending', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'all', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07 14:50:52', NULL, NULL, NULL, NULL, NULL, NULL),
(140, 206, 161, 1, 1, 'ppppppppppppppppppppppppp', '2025-11-09', 'pending', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'all', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07 14:59:50', NULL, NULL, NULL, NULL, NULL, NULL),
(141, 206, 161, 1, 2, 'heyoooooooooooooyo', '2025-11-08', 'returned', 0, NULL, 160, '2025-11-07 23:10:04', '', NULL, NULL, 160, '2025-11-07 23:32:57', NULL, 'all', NULL, '', 160, '2025-11-07 23:36:55', '', '2025-11-07 23:47:02', 160, 'good', '', NULL, NULL, NULL, '2025-11-07 15:00:46', NULL, NULL, NULL, NULL, NULL, NULL),
(142, 206, 161, 1, 3, 'abcderfaapownns', '2025-11-08', 'returned', 0, NULL, 160, '2025-11-07 23:07:37', '', NULL, NULL, 160, '2025-11-07 23:32:45', NULL, 'all', NULL, '', 160, '2025-11-07 23:34:27', '', '2025-11-07 23:44:01', 160, 'good', '', NULL, NULL, NULL, '2025-11-07 15:04:56', NULL, NULL, NULL, NULL, NULL, NULL),
(143, 206, 161, 1, 5, 'hey12345678', '2025-11-09', 'returned', 0, NULL, 160, '2025-11-07 23:07:31', '', NULL, NULL, 160, '2025-11-07 23:12:47', NULL, 'all', NULL, '', 160, '2025-11-07 23:24:37', '', '2025-11-07 23:39:25', 160, 'good', '', NULL, NULL, NULL, '2025-11-07 15:05:44', NULL, NULL, NULL, NULL, NULL, NULL),
(144, 206, 161, 1, 7, 'adfkjhgfdsa', '2025-11-08', 'released', 0, NULL, 160, '2025-11-08 01:07:43', '', NULL, NULL, 160, '2025-11-08 01:08:58', NULL, 'all', NULL, '', 160, '2025-11-08 01:09:58', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07 17:07:17', NULL, NULL, NULL, NULL, NULL, NULL),
(145, 206, 161, 1, 8, 'sadadsassdasd', '2025-11-08', 'pending', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'all', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07 17:08:43', NULL, NULL, NULL, NULL, NULL, NULL);

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
-- Table structure for table `borrowing_chain`
--

CREATE TABLE `borrowing_chain` (
  `id` int(11) NOT NULL,
  `borrowing_id` int(11) NOT NULL COMMENT 'Original borrowing record',
  `asset_id` int(11) NOT NULL,
  `from_person` varchar(255) NOT NULL COMMENT 'Person who lent it',
  `to_person` varchar(255) NOT NULL COMMENT 'Person who received it',
  `to_person_contact` varchar(255) DEFAULT NULL,
  `transfer_date` datetime NOT NULL DEFAULT current_timestamp(),
  `expected_return_date` date DEFAULT NULL,
  `actual_return_date` datetime DEFAULT NULL,
  `status` enum('active','returned') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL COMMENT 'User who recorded this transfer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`id`, `name`, `created_at`) VALUES
(1, 'Samsung', '2025-10-15 04:36:31'),
(2, 'LG', '2025-10-15 04:36:31'),
(3, 'Apple', '2025-10-15 04:36:31'),
(4, 'Dell', '2025-10-15 04:36:31'),
(5, 'HP', '2025-10-15 04:36:31'),
(6, 'Acer', '2025-10-15 04:36:31'),
(7, 'Lenovo', '2025-10-15 04:36:31'),
(8, 'Sony', '2025-10-15 04:36:31'),
(9, 'Panasonic', '2025-10-15 04:36:31'),
(10, 'Epson', '2025-10-15 04:36:31'),
(11, 'Canon', '2025-10-15 04:36:31'),
(12, 'Brother', '2025-10-15 04:36:31'),
(13, 'Sharp', '2025-10-15 04:36:31'),
(14, 'Kyocera', '2025-10-15 04:36:31'),
(15, 'Cisco', '2025-10-15 04:36:31'),
(16, 'JBL', '2025-10-15 04:36:31'),
(17, 'Bose', '2025-10-15 04:36:31'),
(18, 'Logitech', '2025-10-15 04:36:31'),
(19, 'Uratex', '2025-10-15 04:36:31'),
(20, 'Orocan', '2025-10-15 04:36:31');

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
-- Table structure for table `department_approvers`
--

CREATE TABLE `department_approvers` (
  `id` int(11) NOT NULL,
  `office_id` int(11) NOT NULL COMMENT 'Department/Office',
  `approver_user_id` int(11) NOT NULL COMMENT 'Department head who approves',
  `approval_level` enum('primary','secondary','backup') DEFAULT 'primary',
  `can_approve_requests` tinyint(1) DEFAULT 1,
  `can_assign_assets` tinyint(1) DEFAULT 1,
  `max_approval_value` decimal(15,2) DEFAULT NULL COMMENT 'Maximum asset value they can approve',
  `is_active` tinyint(1) DEFAULT 1,
  `assigned_date` date NOT NULL,
  `campus_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_notifications`
--

CREATE TABLE `email_notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(500) NOT NULL,
  `body` text NOT NULL,
  `type` enum('return_reminder','overdue_alert','approval_request','approval_response','account_creation','general') NOT NULL,
  `status` enum('pending','sent','failed') DEFAULT 'pending',
  `sent_at` datetime DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `related_type` varchar(50) DEFAULT NULL,
  `related_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `quantity` int(11) DEFAULT 1,
  `unit_price` decimal(15,2) DEFAULT 0.00,
  `total_value` decimal(15,2) DEFAULT 0.00,
  `amount` decimal(15,2) DEFAULT 0.00,
  `supplier` varchar(255) DEFAULT NULL,
  `status` enum('Pending Verification','Active','Disposed','Transferred') NOT NULL DEFAULT 'Pending Verification',
  `is_borrowable` tinyint(1) NOT NULL DEFAULT 0,
  `borrowable_quantity` int(11) NOT NULL DEFAULT 0,
  `assigned_by_custodian_id` int(11) DEFAULT NULL,
  `verified_by_user_id` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_tags`
--

INSERT INTO `inventory_tags` (`id`, `asset_id`, `office_id`, `tag_number`, `inventory_date`, `article`, `size`, `counted_by`, `checked_by`, `location_row`, `location_section`, `location_floor`, `quantity`, `unit_price`, `total_value`, `amount`, `supplier`, `status`, `is_borrowable`, `borrowable_quantity`, `assigned_by_custodian_id`, `verified_by_user_id`, `verified_at`, `remarks`, `created_at`, `updated_at`) VALUES
(10, 206, 16, 'MIS-110125-9398', '2025-11-01', 'Plastic', '2kg', 'richard fermin', 'richard fermin', '4', 'MIS Office', '3rd', 1, 450.00, 450.00, 450.00, 'Willman', 'Pending Verification', 0, 0, 140, NULL, NULL, 'WORKING', '2025-11-01 14:21:09', '2025-11-01 14:21:09'),
(11, 206, 16, 'MIS-110525-0341', '2025-11-05', 'chair', '', 'richard fermin', 'richard fermin', '', 'MIS Office', '3rd', 10, 450.00, 4500.00, 4500.00, 'Willman', 'Pending Verification', 0, 0, 140, NULL, NULL, 'WORKING', '2025-11-05 11:31:36', '2025-11-05 11:31:36'),
(22, 208, 16, 'MIS-110625-3207', '2025-11-06', 'Board', '', 'Mico ', 'Mico ', '', 'MIS Office', '3rd', 1, 1000.00, 1000.00, 1000.00, 'Willman', 'Pending Verification', 0, 0, 160, NULL, NULL, 'WORKING', '2025-11-06 10:45:36', '2025-11-06 10:45:36'),
(23, 208, 16, 'MIS-110825-2998', '2025-11-07', 'plastic', '5', 'Mico ', 'Mico ', '1', 'MIS Office', '3rd', 1, 1000.00, 1000.00, 1000.00, 'Willman', 'Pending Verification', 0, 0, 160, NULL, NULL, 'WORKING', '2025-11-07 17:05:05', '2025-11-07 17:05:05');

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
(22, 5, 'Canteen', 1, 'NB-CANTEEN', 1, '2025-10-15 02:46:55', '2025-10-15 02:46:55'),
(42, 1, 'miko', 1, 'NEW-42', 1, '2025-10-21 01:16:02', '2025-10-21 01:16:02'),
(43, 1, 'AREA', 1, 'NEW-43', 1, '2025-10-21 02:52:06', '2025-10-21 02:52:06'),
(44, 1, 'TESTING', 1, 'NEW-44', 1, '2025-10-21 07:54:23', '2025-10-21 07:54:23'),
(45, 1, 'SAMPLE', 1, 'NEW-45', 1, '2025-10-25 03:08:35', '2025-10-25 03:08:35');

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
(306, 'hccconception@gmail.com', '::1', 1, '2025-10-15 02:54:02'),
(307, 'hccstarosa@gmail.com', '::1', 1, '2025-10-15 03:28:41'),
(308, 'hccconception@gmail.com', '::1', 1, '2025-10-15 03:38:56'),
(309, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-15 03:39:32'),
(310, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-15 03:39:55'),
(311, 'carlogabriel1818@gmail.com', '::1', 1, '2025-10-15 03:40:24'),
(312, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-15 04:09:08'),
(313, 'hccconception@gmail.com', '::1', 1, '2025-10-15 04:12:16'),
(314, 'carlogabriel1818@gmail.com', '::1', 1, '2025-10-15 04:12:33'),
(315, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-15 04:12:59'),
(316, 'hccconception@gmail.com', '::1', 1, '2025-10-15 04:21:31'),
(317, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-15 04:34:49'),
(318, 'hccconception@gmail.com', '::1', 1, '2025-10-15 04:36:51'),
(319, 'hccconception@gmail.com', '::1', 1, '2025-10-15 06:50:28'),
(320, 'hccstarosa@gmail.com', '::1', 1, '2025-10-15 06:55:30'),
(321, 'hccconception@gmail.com', '::1', 1, '2025-10-15 07:31:59'),
(322, 'mico.macapugay@icloud.com', '::1', 0, '2025-10-15 10:03:50'),
(323, 'hccconception@gmail.com', '::1', 1, '2025-10-15 10:04:03'),
(324, 'hccstarosa@gmail.com', '::1', 1, '2025-10-15 10:04:21'),
(325, 'hccconception@gmail.com', '::1', 1, '2025-10-15 10:06:05'),
(326, 'hccconception@gmail.com', '::1', 1, '2025-10-15 10:14:07'),
(327, 'hccconception@gmail.com', '::1', 1, '2025-10-15 10:29:03'),
(328, 'hccconception@gmail.com', '::1', 1, '2025-10-15 11:07:59'),
(329, 'hccstarosa@gmail.com', '::1', 1, '2025-10-15 11:22:45'),
(330, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-15 11:57:27'),
(331, 'hccconception@gmail.com', '::1', 1, '2025-10-15 13:12:07'),
(332, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-15 13:28:47'),
(333, 'hccconception@gmail.com', '::1', 1, '2025-10-15 13:30:38'),
(334, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-15 13:45:14'),
(335, 'carlogabriel1818@gmail.com', '::1', 1, '2025-10-15 13:46:49'),
(336, 'hccconception@gmail.com', '::1', 0, '2025-10-15 13:47:21'),
(337, 'hccconception@gmail.com', '::1', 1, '2025-10-15 13:47:26'),
(338, 'hccconception@gmail.com', '::1', 1, '2025-10-15 13:54:04'),
(339, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-15 14:24:20'),
(340, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-16 10:00:31'),
(341, 'hccconception@gmail.com', '::1', 1, '2025-10-16 10:09:52'),
(342, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-16 10:10:41'),
(343, 'hccconception@gmail.com', '::1', 1, '2025-10-16 10:40:49'),
(344, 'hccstarosa@gmail.com', '::1', 1, '2025-10-16 11:10:13'),
(345, 'hccstarosa@gmail.com', '::1', 1, '2025-10-16 12:14:45'),
(346, 'jennamariesolitario@gmail.com', '::1', 1, '2025-10-16 12:15:52'),
(347, 'hccstarosa@gmail.com', '::1', 1, '2025-10-16 12:17:05'),
(348, 'hccstarosa@gmail.com', '::1', 1, '2025-10-16 20:04:33'),
(349, 'hccstarosa@gmail.com', '::1', 1, '2025-10-16 20:06:15'),
(350, 'hccconception@gmail.com', '::1', 1, '2025-10-16 20:14:50'),
(351, 'hccstarosa@gmail.com', '::1', 1, '2025-10-16 20:19:27'),
(352, 'hccconception@gmail.com', '::1', 1, '2025-10-16 20:26:16'),
(353, 'hccstarosa@gmail.com', '::1', 1, '2025-10-16 20:31:33'),
(354, 'jennamariesolitario@gmail.com', '::1', 1, '2025-10-16 20:32:49'),
(355, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-16 20:33:20'),
(356, 'carlogabriel1818@gmail.com', '::1', 0, '2025-10-16 20:34:32'),
(357, 'carlogabriel1818@gmail.com', '::1', 1, '2025-10-16 20:34:38'),
(358, 'hccstarosa@gmail.com', '::1', 1, '2025-10-16 20:36:37'),
(359, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-16 20:37:40'),
(360, 'hccstarosa@gmail.com', '::1', 1, '2025-10-16 20:38:49'),
(361, 'jennamariesolitario@gmail.com', '::1', 1, '2025-10-16 20:47:30'),
(362, 'hccstarosa@gmail.com', '::1', 1, '2025-10-16 20:49:06'),
(363, 'jennamariesolitario@gmail.com', '::1', 1, '2025-10-16 20:49:53'),
(364, 'hccstarosa@gmail.com', '::1', 1, '2025-10-16 23:11:46'),
(365, 'hccstarosa@gmail.com', '::1', 1, '2025-10-16 23:54:59'),
(366, 'jennamariesolitario@gmail.com', '::1', 1, '2025-10-16 23:55:59'),
(367, 'hccstarosa@gmail.com', '::1', 1, '2025-10-16 23:58:53'),
(368, 'hccstarosa@gmail.com', '::1', 1, '2025-10-17 00:41:46'),
(369, 'hccstarosa@gmail.com', '::1', 1, '2025-10-17 00:51:22'),
(370, 'hccstarosa@gmail.com', '::1', 1, '2025-10-17 00:52:25'),
(371, 'jennamariesolitario@gmail.com', '::1', 1, '2025-10-17 00:55:49'),
(372, 'hccstarosa@gmail.com', '::1', 1, '2025-10-17 01:04:45'),
(373, 'jennamariesolitario@gmail.com', '::1', 1, '2025-10-17 01:06:06'),
(374, 'hccstarosa@gmail.com', '::1', 1, '2025-10-17 01:07:37'),
(375, 'hccstarosa@gmail.com', '::1', 1, '2025-10-17 01:10:07'),
(376, 'archie@gmail.com', '::1', 1, '2025-10-17 01:12:37'),
(377, 'jennamariesolitario@gmail.com', '::1', 1, '2025-10-17 01:14:20'),
(378, 'hccstarosa@gmail.com', '::1', 1, '2025-10-17 01:18:46'),
(379, 'hccstarosa@gmail.com', '::1', 1, '2025-10-17 01:22:18'),
(380, 'hccstarosa@gmail.com', '::1', 1, '2025-10-17 01:51:55'),
(381, 'hccstarosa@gmail.com', '::1', 1, '2025-10-17 02:42:54'),
(382, '123@gmail.com', '::1', 1, '2025-10-17 02:51:28'),
(383, 'hccstarosa@gmail.com', '::1', 1, '2025-10-17 02:53:03'),
(384, '123@gmail.com', '::1', 1, '2025-10-17 02:53:38'),
(385, 'hccstarosa@gmail.com', '::1', 1, '2025-10-17 02:59:30'),
(386, 'carlogabriel@gmail.com', '::1', 1, '2025-10-17 03:03:03'),
(387, 'hccstarosa@gmail.com', '::1', 1, '2025-10-17 10:31:57'),
(388, 'archie@gmail.com', '::1', 1, '2025-10-17 11:37:26'),
(389, 'archie@gmail.com', '::1', 1, '2025-10-17 11:38:44'),
(390, 'hccstarosa@gmail.com', '::1', 1, '2025-10-17 11:57:39'),
(391, 'hccstarosa@gmail.com', '::1', 1, '2025-10-17 23:01:13'),
(392, 'mico.macapugay2004@gmail.com', '::1', 1, '2025-10-17 23:05:00'),
(393, 'hccstarosa@gmail.com', '::1', 1, '2025-10-17 23:25:57'),
(394, 'hccstarosa@gmail.com', '::1', 1, '2025-10-17 23:41:48'),
(395, 'macapugaymicole65@gmail.com', '::1', 1, '2025-10-17 23:59:06'),
(396, 'macapugaymicole65@gmail.com', '::1', 1, '2025-10-18 01:02:07'),
(397, 'hccstarosa@gmail.com', '::1', 1, '2025-10-18 01:16:44'),
(398, 'hccstarosa@gmail.com', '192.168.100.158', 1, '2025-10-18 01:34:21'),
(399, 'mico.macapugay@icloud.com', '192.168.100.158', 1, '2025-10-18 01:42:16'),
(400, 'hccconception@gmail.com', '::1', 1, '2025-10-18 01:44:04'),
(401, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-18 01:47:16'),
(402, 'hccstarosa@gmail.com', '::1', 1, '2025-10-18 01:56:09'),
(403, 'hccstarosa@gmail.com', '::1', 1, '2025-10-18 02:11:39'),
(404, 'hccstarosa@gmail.com', '192.168.100.158', 1, '2025-10-18 03:49:46'),
(405, 'hccstarosa@gmail.com', '::1', 1, '2025-10-18 07:12:56'),
(406, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-18 07:22:24'),
(407, 'hccstarosa@gmail.com', '192.168.100.161', 1, '2025-10-18 07:27:59'),
(408, 'hccstarosa@gmail.com', '192.168.100.161', 1, '2025-10-18 07:37:55'),
(409, 'hccstarosa@gmail.com', '::1', 1, '2025-10-18 07:39:49'),
(410, 'mico.macapugay@icloud.com', '192.168.100.161', 1, '2025-10-18 07:54:01'),
(411, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-18 08:01:51'),
(412, 'hccstarosa@gmail.com', '192.168.100.161', 1, '2025-10-18 08:03:57'),
(413, 'hccstarosa@gmail.com', '::1', 1, '2025-10-18 08:05:54'),
(414, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-18 08:06:19'),
(415, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-18 08:15:44'),
(416, 'hccstarosa@gmail.com', '192.168.100.161', 1, '2025-10-18 08:20:16'),
(417, 'mico.macapugay@icloud.com', '192.168.100.161', 1, '2025-10-18 08:23:22'),
(418, 'hccstarosa@gmail.com', '192.168.100.161', 1, '2025-10-18 08:32:23'),
(419, 'mico.macapugay@icloud.com', '192.168.100.161', 1, '2025-10-18 08:33:07'),
(420, 'hccstarosa@gmail.com', '192.168.100.161', 1, '2025-10-18 08:34:23'),
(421, 'hccstarosa@gmail.com', '192.168.100.161', 1, '2025-10-18 08:38:21'),
(422, 'hccstarosa@gmail.com', '192.168.100.161', 1, '2025-10-18 08:41:37'),
(423, 'hccstarosa@gmail.com', '::1', 1, '2025-10-18 08:44:00'),
(424, 'hccstarosa@gmail.com', '192.168.100.161', 1, '2025-10-18 08:44:43'),
(425, 'hccstarosa@gmail.com', '192.168.100.161', 1, '2025-10-18 08:45:20'),
(426, 'hccstarosa@gmail.com', '::1', 1, '2025-10-18 09:16:46'),
(427, 'jennamariesolitario@gmail.com', '192.168.100.161', 1, '2025-10-18 09:18:17'),
(428, 'jennamariesolitario@gmail.com', '::1', 1, '2025-10-18 09:19:06'),
(429, 'jennamariesolitario@gmail.com', '192.168.100.161', 1, '2025-10-18 09:20:38'),
(430, 'hccstarosa@gmail.com', '192.168.100.161', 1, '2025-10-18 09:47:45'),
(431, 'mico.macapugay@icloud.com', '192.168.100.161', 1, '2025-10-18 09:50:16'),
(432, 'hccstarosa@gmail.com', '192.168.100.161', 1, '2025-10-18 09:52:00'),
(433, 'mico.macapugay@icloud.com', '192.168.100.161', 1, '2025-10-18 09:52:47'),
(434, 'jennamariesolitario@gmail.com', '192.168.100.161', 1, '2025-10-18 09:58:35'),
(435, 'hccstarosa@gmail.com', '192.168.100.161', 1, '2025-10-18 10:04:59'),
(436, 'hccstarosa@gmail.com', '192.168.100.161', 1, '2025-10-18 10:48:02'),
(437, 'jennamariesolitario@gmail.com', '::1', 1, '2025-10-18 12:22:12'),
(438, 'hccstarosa@gmail.com', '192.168.100.161', 1, '2025-10-18 12:35:18'),
(439, 'hccstarosa@gmail.com', '::1', 1, '2025-10-18 12:39:21'),
(440, 'jennamariesolitario@gmail.com', '::1', 1, '2025-10-18 12:40:03'),
(441, 'mico.macapugay@icloud.com', '192.168.100.161', 1, '2025-10-18 13:33:12'),
(442, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-18 13:38:20'),
(443, 'hccstarosa@gmail.com', '192.168.100.161', 1, '2025-10-18 13:39:08'),
(444, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-18 13:41:23'),
(445, 'mico.macapugay@icloud.com', '192.168.100.161', 1, '2025-10-18 13:44:14'),
(446, 'mico.macapugay@icloud.com', '192.168.100.161', 1, '2025-10-18 13:57:57'),
(447, 'hccstarosa@gmail.com', '::1', 1, '2025-10-18 14:06:11'),
(448, 'hccstarosa@gmail.com', '192.168.100.161', 1, '2025-10-18 14:07:23'),
(449, 'mico.macapugay@icloud.com', '192.168.100.161', 1, '2025-10-18 14:08:47'),
(450, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-18 14:13:25'),
(451, 'hccstarosa@gmail.com', '192.168.100.161', 1, '2025-10-18 14:15:08'),
(452, 'jennamariesolitario@gmail.com', '192.168.100.161', 1, '2025-10-18 14:20:00'),
(453, 'hccstarosa@gmail.com', '192.168.100.161', 1, '2025-10-18 14:23:45'),
(454, 'hccconception@gmail.com', '192.168.100.161', 1, '2025-10-18 14:53:52'),
(455, 'hccstarosa@gmail.com', '192.168.100.161', 1, '2025-10-18 14:54:08'),
(456, 'hccstarosa@gmail.com', '192.168.100.161', 1, '2025-10-18 23:21:37'),
(457, 'hccstarosa@gmail.com', '192.168.100.161', 1, '2025-10-19 04:06:28'),
(458, 'mico.macapugay@icloud.com', '192.168.100.161', 1, '2025-10-19 04:07:11'),
(459, 'hccstarosa@gmail.com', '192.168.100.161', 1, '2025-10-19 04:08:14'),
(460, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-19 04:08:28'),
(461, 'hccstarosa@gmail.com', '::1', 1, '2025-10-19 04:47:33'),
(462, 'hccstarosa@gmail.com', '192.168.100.161', 1, '2025-10-19 04:58:59'),
(463, 'mico.macapugay@icloud.com', '::1', 0, '2025-10-19 05:14:20'),
(464, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-19 05:16:37'),
(465, 'hccstarosa@gmail.com', '::1', 1, '2025-10-19 05:21:47'),
(466, 'hccstarosa@gmail.com', '192.168.100.161', 1, '2025-10-19 07:32:55'),
(467, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-19 08:03:54'),
(468, 'hccstarosa@gmail.com', '::1', 1, '2025-10-19 08:42:29'),
(469, 'mico.macapugay@icloud.com', '192.168.100.161', 1, '2025-10-19 08:45:03'),
(470, 'hccstarosa@gmail.com', '192.168.100.161', 1, '2025-10-19 08:45:48'),
(471, 'hccstarosa@gmail.com', '192.168.100.161', 1, '2025-10-19 08:49:08'),
(472, 'hccconception@gmail.com', '::1', 1, '2025-10-19 09:02:44'),
(473, 'hccstarosa@gmail.com', '192.168.100.161', 1, '2025-10-19 09:59:16'),
(474, 'hccstarosa@gmail.com', '::1', 1, '2025-10-19 10:07:10'),
(475, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-19 10:11:22'),
(476, 'hccstarosa@gmail.com', '::1', 1, '2025-10-19 10:27:38'),
(477, 'hccstarosa@gmail.com', '192.168.100.161', 1, '2025-10-19 11:39:30'),
(478, 'hccstarosa@gmail.com', '192.168.100.161', 1, '2025-10-19 11:41:44'),
(479, 'hccstarosa@gmail.com', '192.168.100.161', 1, '2025-10-19 12:35:40'),
(480, 'mico.macapugay@icloud.com', '192.168.100.161', 1, '2025-10-19 12:48:25'),
(481, 'mico.macapugay@icloud.com', '192.168.100.161', 1, '2025-10-19 12:49:37'),
(482, 'hccstarosa@gmail.com', '192.168.100.161', 1, '2025-10-19 12:49:51'),
(483, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-19 12:52:18'),
(484, 'hccstarosa@gmail.com', '::1', 1, '2025-10-20 01:19:58'),
(485, 'hccstarosa@gmail.com', '192.168.1.111', 1, '2025-10-20 01:26:15'),
(486, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-20 01:26:32'),
(487, 'hccstarosa@gmail.com', '::1', 1, '2025-10-20 01:35:02'),
(488, 'hccstarosa@gmail.com', '172.20.10.2', 1, '2025-10-20 01:51:01'),
(489, 'hccconception@gmail.com', '172.20.10.1', 1, '2025-10-20 01:53:44'),
(490, 'hccstarosa@gmail.com', '172.20.10.2', 1, '2025-10-20 01:54:14'),
(491, 'hccconception@gmail.com', '172.20.10.7', 1, '2025-10-20 01:56:19'),
(492, 'hccstarosa@gmail.com', '172.20.10.2', 1, '2025-10-20 02:00:56'),
(493, 'hccconception@gmail.com', '172.20.10.7', 1, '2025-10-20 02:06:31'),
(494, 'hccstarosa@gmail.com', '172.20.10.1', 1, '2025-10-20 02:07:24'),
(495, 'jennamariesolitario@gmail.com', '172.20.10.2', 1, '2025-10-20 02:07:39'),
(496, 'richardfermin30@icloud.com', '172.20.10.1', 0, '2025-10-20 02:10:55'),
(497, 'richardfermin30@icloud.com', '172.20.10.1', 0, '2025-10-20 02:12:53'),
(498, 'Richardfermin30@icloud.com', '172.20.10.1', 0, '2025-10-20 02:13:27'),
(499, 'hccstarosa@gmail.com', '172.20.10.2', 1, '2025-10-20 02:13:28'),
(500, 'richardfermin30@icloud.com', '172.20.10.1', 0, '2025-10-20 02:14:01'),
(501, 'Richardfermin30@icloud.com', '172.20.10.1', 0, '2025-10-20 02:15:08'),
(502, 'Richardfermin30@icloud.com', '172.20.10.1', 0, '2025-10-20 02:15:16'),
(503, 'hccsantarosa@gmail.com', '172.20.10.7', 0, '2025-10-20 02:15:23'),
(504, 'Shainalyncruz3@gmail.com', '172.20.10.2', 1, '2025-10-20 02:15:30'),
(505, 'richardfermin30@icloud.com', '172.20.10.1', 0, '2025-10-20 02:15:36'),
(506, 'hccsantarosa@gmail.com', '172.20.10.7', 0, '2025-10-20 02:15:40'),
(507, 'hccsantarosa@gmail.com', '172.20.10.7', 0, '2025-10-20 02:15:54'),
(508, 'richardgermin30@icloud.com', '172.20.10.1', 1, '2025-10-20 02:15:58'),
(509, 'hccstarosa@gmail.com', '172.20.10.7', 0, '2025-10-20 02:16:23'),
(510, 'hccstarosa@gmail.com', '172.20.10.7', 1, '2025-10-20 02:16:29'),
(511, 'hccstarosa@gmail.com', '172.20.10.1', 1, '2025-10-20 02:16:55'),
(512, 'richardfermin30@icloud.com', '172.20.10.1', 1, '2025-10-20 02:20:59'),
(513, 'hccstarosa@gmail.com', '172.20.10.1', 1, '2025-10-20 02:23:14'),
(514, 'richardfermin30@icloud.com', '172.20.10.1', 1, '2025-10-20 02:23:55'),
(515, 'hccstarosa@gmail.com', '::1', 1, '2025-10-20 02:31:59'),
(516, 'macapugaymicole65@gmail.com', '::1', 1, '2025-10-20 02:36:23'),
(517, 'hccstarosa@gmail.com', '::1', 1, '2025-10-20 06:20:56'),
(518, 'hccstarosa@gmail.com', '::1', 1, '2025-10-20 06:23:13'),
(519, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-20 06:37:30'),
(520, 'hccstarosa@gmail.com', '::1', 1, '2025-10-20 06:38:10'),
(521, 'hccstarosa@gmail.com', '192.168.0.191', 1, '2025-10-20 06:45:12'),
(522, 'hccstarosa@gmail.com', '::1', 1, '2025-10-20 06:51:41'),
(523, 'hccconception@gmail.com', '::1', 1, '2025-10-20 07:12:56'),
(524, 'hccstarosa@gmail.com', '::1', 1, '2025-10-20 07:33:22'),
(525, 'hccstarosa@gmail.com', '::1', 1, '2025-10-21 00:59:17'),
(526, 'mico.macapugay@icloud.com', '192.168.100.160', 1, '2025-10-21 01:20:26'),
(527, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-21 01:36:54'),
(528, 'hccstarosa@gmail.com', '::1', 1, '2025-10-21 01:37:14'),
(529, 'mico.macapugay@icloud.com', '192.168.100.160', 1, '2025-10-21 01:48:51'),
(530, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-21 01:50:22'),
(531, 'hccstarosa@gmail.com', '::1', 0, '2025-10-21 01:50:43'),
(532, 'hccstarosa@gmail.com', '::1', 1, '2025-10-21 01:50:56'),
(533, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-21 02:00:08'),
(534, 'hccstarosa@gmail.com', '192.168.100.160', 1, '2025-10-21 02:01:00'),
(535, 'carlogabriel1818@gmail.com', '192.168.100.160', 1, '2025-10-21 02:04:12'),
(536, 'mico.macapugay@icloud.com', '192.168.100.160', 1, '2025-10-21 02:07:44'),
(537, 'hccstarosa@gmail.com', '::1', 1, '2025-10-21 02:31:47'),
(538, 'mico.macapugay@icloud.com', '192.168.100.160', 1, '2025-10-21 02:42:29'),
(539, 'hccstarosa@gmail.com', '192.168.100.160', 1, '2025-10-21 02:43:06'),
(540, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-21 02:44:50'),
(541, 'carlogabriel1818@gmail.com', '::1', 1, '2025-10-21 02:48:24'),
(542, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-21 02:50:51'),
(543, 'mico.macapugay@icloud.com', '192.168.100.160', 1, '2025-10-21 03:03:56'),
(544, 'hccstarosa@gmail.com', '::1', 1, '2025-10-21 03:55:22'),
(545, 'hccstarosa@gmail.com', '192.168.100.160', 1, '2025-10-21 04:16:43'),
(546, 'mico.macapugay@icloud.com', '192.168.100.160', 1, '2025-10-21 04:17:14'),
(547, 'hccstarosa@gmail.com', '::1', 1, '2025-10-21 05:04:09'),
(548, 'hccstarosa@gmail.com', '::1', 1, '2025-10-21 05:07:30'),
(549, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-21 05:07:52'),
(550, 'hccstarosa@gmail.com', '192.168.100.160', 1, '2025-10-21 06:26:43'),
(551, 'mico.macapugay@icloud.com', '192.168.100.160', 1, '2025-10-21 06:27:39'),
(552, 'hccstarosa@gmail.com', '192.168.100.160', 1, '2025-10-21 06:29:14'),
(553, 'hccconception@gmail.com', '192.168.100.160', 0, '2025-10-21 06:31:05'),
(554, 'hccconception@gmail.com', '192.168.100.160', 1, '2025-10-21 06:31:18'),
(555, 'hccstarosa@gmail.com', '192.168.100.160', 1, '2025-10-21 06:32:10'),
(556, 'hccstarosa@gmail.com', '192.168.100.160', 1, '2025-10-21 07:48:27'),
(557, 'mico.macapugay@icloud.com', '192.168.100.160', 1, '2025-10-21 07:48:50'),
(558, 'hccstarosa@gmail.com', '::1', 1, '2025-10-21 07:51:23'),
(559, 'carlogabriel1818@gmail.com', '::1', 0, '2025-10-21 07:52:38'),
(560, 'carlogabriel1818@gmail.com', '::1', 1, '2025-10-21 07:52:44'),
(561, 'hccstarosa@gmail.com', '::1', 1, '2025-10-21 07:53:17'),
(562, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-21 08:50:00'),
(563, 'hccstarosa@gmail.com', '192.168.100.160', 1, '2025-10-21 08:50:09'),
(564, 'mico.macapugay@icloud.com', '192.168.100.160', 1, '2025-10-21 09:04:25'),
(565, 'hccstarosa@gmail.com', '::1', 1, '2025-10-21 09:06:15'),
(566, 'hccstarosa@gmail.com', '192.168.100.160', 1, '2025-10-21 09:44:04'),
(567, 'mico.macapugay@icloud.com', '192.168.100.160', 1, '2025-10-21 10:16:49'),
(568, 'hccstarosa@gmail.com', '192.168.100.161', 1, '2025-10-21 12:07:15'),
(569, 'mico.macapugay@icloud.com', '192.168.100.161', 1, '2025-10-21 12:11:24'),
(570, 'hccstarosa@gmail.com', '192.168.100.161', 1, '2025-10-21 12:12:15'),
(571, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-21 12:12:24'),
(572, 'hccstarosa@gmail.com', '192.168.100.161', 1, '2025-10-21 13:54:37'),
(573, 'hccstarosa@gmail.com', '192.168.100.161', 1, '2025-10-21 13:59:40'),
(574, 'hccstarosa@gmail.com', '192.168.100.161', 1, '2025-10-21 14:06:25'),
(575, 'hccstarosa@gmail.com', '172.20.10.1', 1, '2025-10-21 23:39:36'),
(576, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-21 23:41:09'),
(577, 'mico.macapugay@icloud.com', '172.20.10.1', 1, '2025-10-22 00:24:39'),
(578, 'hccstarosa@gmail.com', '::1', 1, '2025-10-22 00:24:55'),
(579, 'carlogabriel1818@gmail.com', '172.20.10.1', 1, '2025-10-22 01:32:49'),
(580, 'mico.macapugay@icloud.com', '172.20.10.1', 1, '2025-10-22 01:33:12'),
(581, 'hccstarosa@gmail.com', '172.20.10.1', 1, '2025-10-22 01:35:22'),
(582, 'mico.macapugay@icloud.com', '172.20.10.1', 1, '2025-10-22 01:35:40'),
(583, 'hccstarosa@gmail.com', '172.20.10.1', 1, '2025-10-22 01:35:56'),
(584, 'mico.macapugay@icloud.com', '172.20.10.1', 1, '2025-10-22 01:36:06'),
(585, 'hccstarosa@gmail.com', '10.156.147.8', 1, '2025-10-22 02:46:29'),
(586, 'mico. macapugay@icloud.com', '10.156.147.8', 0, '2025-10-22 02:48:28'),
(587, 'mico.macapugay@icloud.com', '10.156.147.8', 1, '2025-10-22 02:48:38'),
(588, 'hccstarosa@gmail.com', '::1', 0, '2025-10-22 05:24:10'),
(589, 'hccstarosa@gmail.com', '::1', 1, '2025-10-22 05:24:18'),
(590, 'mico.macapugay@icloud.com', '10.68.128.36', 0, '2025-10-22 05:24:35'),
(591, 'mico.macapugay@icloud.com', '10.68.128.36', 0, '2025-10-22 05:24:35'),
(592, 'mico.macapugay@icloud.com', '10.68.128.36', 1, '2025-10-22 05:24:46'),
(593, 'hccstarosa@gmail.com', '10.68.128.36', 1, '2025-10-22 05:28:08'),
(594, 'mico.macapugay@icloud.com', '::1', 0, '2025-10-22 05:28:34'),
(595, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-22 05:28:39'),
(596, 'jennamariesolitario@gmail.com', '10.68.128.36', 1, '2025-10-22 05:36:32'),
(597, 'mico.macapugay@icloud.com', '::1', 0, '2025-10-22 05:39:23'),
(598, 'hccstarosa@gmail.com', '::1', 1, '2025-10-22 05:39:33'),
(599, 'Hccstarosa@gmail.com', '10.68.128.36', 0, '2025-10-22 05:45:45'),
(600, 'Hccstarosa@gmail.com', '10.68.128.36', 0, '2025-10-22 05:45:49'),
(601, 'Hccstarosa@gmail.com', '10.68.128.36', 1, '2025-10-22 05:46:01'),
(602, 'Hccstanrosa@gmail.com', '10.68.128.36', 0, '2025-10-22 05:47:18'),
(603, 'Hccstarosa@gmail.com', '10.68.128.36', 1, '2025-10-22 05:47:47'),
(604, 'hccstarosa@gmail.com', '::1', 1, '2025-10-22 05:50:57'),
(605, 'hccstarosa@gmail.com', '::1', 1, '2025-10-22 13:19:15'),
(606, 'hccconception@gmail.com', '::1', 1, '2025-10-22 13:19:57'),
(607, 'hccstarosa@gmail.com', '::1', 1, '2025-10-22 13:20:19'),
(608, 'macapugaymicole65@gmail.com', '192.168.100.172', 1, '2025-10-22 13:26:07'),
(609, 'hccstarosa@gmail.com', '192.168.100.172', 1, '2025-10-22 13:27:36'),
(610, 'carlogabriel1818@gmail.com', '192.168.100.172', 1, '2025-10-22 13:29:00'),
(611, 'macapugaymicole65@gmail.com', '192.168.100.172', 0, '2025-10-22 13:29:40'),
(612, 'macapugaymicole65@gmail.com', '192.168.100.172', 1, '2025-10-22 13:29:45'),
(613, 'hccstarosa@gmail.com', '192.168.100.172', 1, '2025-10-22 13:30:29'),
(614, 'mico.macapugay@gmail.com', '192.168.100.161', 0, '2025-10-22 13:34:51'),
(615, 'mico.macapugay@gmail.com', '192.168.100.161', 0, '2025-10-22 13:34:59'),
(616, 'mico.macapugay@gmail.com', '192.168.100.161', 0, '2025-10-22 13:35:17'),
(617, 'mico.macapugay@icloud.com', '192.168.100.161', 1, '2025-10-22 13:35:49'),
(618, 'hccstarosa@gmail.com', '192.168.100.172', 1, '2025-10-22 14:27:23'),
(619, 'mico.macapugay@icloud.com', '192.168.100.161', 1, '2025-10-22 14:34:08'),
(620, 'mico.macapugay@icloud.com', '192.168.100.172', 1, '2025-10-23 09:50:35'),
(621, 'mico.macapugay@icloud.com', '192.168.100.172', 1, '2025-10-23 09:56:45'),
(622, 'mico.macapugay@icloud.com', '192.168.100.172', 1, '2025-10-23 11:04:50'),
(623, 'mico.macapugay@icloud.com', '192.168.100.172', 1, '2025-10-23 11:06:39'),
(624, 'hccstarosa@gmail.com', '192.168.100.172', 0, '2025-10-23 20:34:56'),
(625, 'hccstarosa@gmail.com', '192.168.100.172', 1, '2025-10-23 20:35:05'),
(626, 'shainalyncruz3@gmail.com', '192.168.100.172', 1, '2025-10-23 20:36:45'),
(627, 'hccstarosa@gmail.com', '192.168.100.172', 1, '2025-10-23 20:39:06'),
(628, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-23 20:39:34'),
(629, 'shainalyncruz3@gmail.com', '::1', 0, '2025-10-23 21:56:32'),
(630, 'shainalyncruz3@gmail.com', '::1', 1, '2025-10-23 21:56:39'),
(631, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-23 21:57:46'),
(632, 'hccstarosa@gmail.com', '::1', 1, '2025-10-23 22:08:04'),
(633, 'hccstarosa@gmail.com', '::1', 1, '2025-10-23 22:41:32'),
(634, 'mico.macapugay@icloud.com', '172.20.10.1', 1, '2025-10-23 22:42:45'),
(635, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-23 22:47:35'),
(636, 'hccstarosa@gmail.com', '::1', 0, '2025-10-23 23:03:19'),
(637, 'hccstarosa@gmail.com', '::1', 0, '2025-10-23 23:03:24'),
(638, 'hccstarosa@gmail.com', '::1', 1, '2025-10-23 23:03:31'),
(639, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-23 23:07:04'),
(640, 'hccstarosa@gmail.com', '::1', 1, '2025-10-23 23:07:26'),
(641, 'hccstarosa@gmail.com', '::1', 1, '2025-10-24 00:03:06'),
(642, 'mico.macapugay@icloud.com', '10.68.128.36', 1, '2025-10-24 00:05:34'),
(643, 'richardfermin30@gmail.com', '10.68.128.36', 0, '2025-10-24 00:08:08'),
(644, 'richardfermin30@gmail.com', '10.68.128.36', 0, '2025-10-24 00:08:18'),
(645, 'Richardfermin30@icloud.com', '10.68.128.36', 1, '2025-10-24 00:08:41'),
(646, 'hccstarosa@gmail.com', '10.68.128.36', 1, '2025-10-24 00:11:33'),
(647, 'richardfermin30@icloud.com', '::1', 0, '2025-10-24 00:11:47'),
(648, 'richardfermin30@icloud.com', '::1', 1, '2025-10-24 00:12:05'),
(649, 'hccconception@gmail.com', '::1', 1, '2025-10-24 00:21:06'),
(650, 'hccconception@gmail.com', '::1', 1, '2025-10-24 00:22:30'),
(651, 'hccconception@gmail.com', '::1', 1, '2025-10-24 00:27:06'),
(652, '59622022@holycross.edu.ph', '10.68.128.36', 1, '2025-10-24 00:29:47'),
(653, 'hccconception@gmail.com', '10.68.128.36', 1, '2025-10-24 00:35:05'),
(654, 'richardfermin30@icloud.com', '::1', 1, '2025-10-24 00:35:11'),
(655, 'hccstarosa@gmail.com', '10.68.128.36', 0, '2025-10-24 00:36:19'),
(656, 'hccstarosa@gmail.com', '10.68.128.36', 1, '2025-10-24 00:36:37'),
(657, 'hccstarosa@gmail.com', '::1', 1, '2025-10-24 00:49:31'),
(658, 'Richardfermin2004@gmail.com', '10.68.128.36', 0, '2025-10-24 00:49:45'),
(659, 'Richardfermin30@icloud.com', '10.68.128.36', 1, '2025-10-24 00:50:20'),
(660, 'ferminrichard@icloud.com', '10.68.128.36', 1, '2025-10-24 00:58:01'),
(661, 'Richardfermin30@icloud.com. Com', '10.68.128.36', 0, '2025-10-24 01:11:01'),
(662, 'Richardfermin30@icloud.com', '10.68.128.36', 1, '2025-10-24 01:11:14'),
(663, 'hccadmin@gmail.com', '::1', 0, '2025-10-24 01:18:29'),
(664, 'hccsyarosa@gmail.com', '::1', 0, '2025-10-24 01:18:41'),
(665, 'hccstarosa@gmail.com', '::1', 1, '2025-10-24 01:18:51'),
(666, 'mico.macapugay@icloud.com', '10.68.128.36', 1, '2025-10-24 01:22:11'),
(667, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-24 05:21:21'),
(668, 'hccstarosa@gmail.com', '192.168.100.172', 1, '2025-10-24 05:21:34'),
(669, 'hccstarosa@gmail.com', '::1', 1, '2025-10-24 06:11:24'),
(670, 'mico.macapugay@icloud.com', '192.168.100.172', 1, '2025-10-24 07:41:24'),
(671, 'hccstarosa@gmail.com', '192.168.100.172', 1, '2025-10-24 07:42:01'),
(672, 'hccstarosa@gmail.com', '192.168.100.172', 1, '2025-10-24 09:05:21'),
(673, 'mico.macapugay@icloud.com', '192.168.100.172', 1, '2025-10-24 09:09:49'),
(674, 'hccstarosa@gmail.com', '192.168.100.172', 1, '2025-10-24 09:10:43'),
(675, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-24 09:11:22'),
(676, 'hccstarosa@gmail.com', '::1', 1, '2025-10-24 10:00:55'),
(677, 'mico.macapugay@icloud.com', '::1', 0, '2025-10-24 10:16:36'),
(678, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-24 10:16:41'),
(679, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-24 12:57:09'),
(680, 'ferminrichard@icloud.com', '::1', 1, '2025-10-24 13:48:53'),
(681, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-24 13:50:39'),
(682, 'hccstarosa@gmail.com', '192.168.100.172', 1, '2025-10-24 22:26:48'),
(683, 'ferminrichard@icloud.com', '192.168.100.172', 1, '2025-10-25 00:24:17'),
(684, 'hccstarosa@gmail.com', '192.168.100.172', 1, '2025-10-25 00:32:04'),
(685, 'ferminrichard@icloud.com', '::1', 1, '2025-10-25 00:56:21'),
(686, 'mico.macapugay@icloud.com', '192.168.100.172', 1, '2025-10-25 00:59:51'),
(687, 'hccstarosa@gmail.com', '::1', 1, '2025-10-25 01:05:23'),
(688, 'ferminrichard@icloud.com', '::1', 1, '2025-10-25 01:12:13'),
(689, 'hccstarosa@gmail.com', '::1', 1, '2025-10-25 01:40:24'),
(690, 'ferminrichard@icloud.com', '::1', 1, '2025-10-25 01:41:22'),
(691, 'hccstarosa@gmail.com', '::1', 0, '2025-10-25 01:44:33'),
(692, 'hccstarosa@gmail.com', '::1', 1, '2025-10-25 01:44:39'),
(693, 'ferminrichard@icloud.com', '::1', 1, '2025-10-25 01:45:59'),
(694, 'hccstarosa@gmail.com', '::1', 1, '2025-10-25 01:46:40'),
(695, 'ferminrichard@icloud.com', '::1', 1, '2025-10-25 01:47:17'),
(696, 'hccstarosa@gmail.com', '::1', 1, '2025-10-25 01:52:09'),
(697, 'ferminrichard@icloud.com', '::1', 1, '2025-10-25 01:53:01'),
(698, 'hccstarosa@gmail.com', '::1', 1, '2025-10-25 01:57:44'),
(699, 'ferminrichard@icloud.com', '::1', 1, '2025-10-25 01:58:11'),
(700, 'hccstarosa@gmail.com', '::1', 1, '2025-10-25 02:15:43'),
(701, 'ferminrichard@icloud.com', '::1', 1, '2025-10-25 02:16:22'),
(702, 'hccstarosa@gmail.com', '::1', 1, '2025-10-25 02:19:25'),
(703, 'hccstarosa@gmail.com', '192.168.100.172', 1, '2025-10-25 02:20:41'),
(704, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-25 02:20:55'),
(705, 'mico.macapugay@icloud.com', '192.168.100.172', 1, '2025-10-25 02:57:02'),
(706, 'ferminrichard@icloud.com', '::1', 1, '2025-10-25 02:57:09'),
(707, 'hccstarosa@gmail.com', '::1', 1, '2025-10-25 02:58:11'),
(708, 'ferminrichard@icloud.com', '::1', 1, '2025-10-25 02:59:05'),
(709, 'hccstarosa@gmail.com', '::1', 1, '2025-10-25 03:00:08'),
(710, 'ferminrichard@icloud.com', '::1', 1, '2025-10-25 03:01:08'),
(711, 'hccstarosa@gmail.com', '192.168.100.172', 1, '2025-10-25 03:07:28'),
(712, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-25 03:08:52'),
(713, 'mico.macapugay@icloud.com', '192.168.100.161', 1, '2025-10-26 00:36:59'),
(714, 'hccstarosa@gmail.com', '192.168.100.172', 1, '2025-10-26 00:37:11'),
(715, 'ferminrichard@icloud.com', '::1', 1, '2025-10-26 00:37:23'),
(716, 'mico.macapugay@icloud.com', '192.168.100.161', 1, '2025-10-26 02:58:29'),
(717, 'hccstarosa@gmail.com', '192.168.100.172', 1, '2025-10-26 02:58:36'),
(718, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-26 05:09:08'),
(719, 'ferminrichard@icloud.com', '::1', 1, '2025-10-26 05:38:22'),
(720, 'mico.macapugay@icloud.com', '192.168.100.161', 1, '2025-10-26 05:38:27'),
(721, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-26 05:41:21'),
(722, 'ferminrichard@icloud.com', '::1', 1, '2025-10-26 05:48:33'),
(723, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-26 06:14:25'),
(724, 'ferminrichard@icloud.com', '::1', 0, '2025-10-26 06:16:57'),
(725, 'ferminrichard@icloud.com', '::1', 1, '2025-10-26 06:17:04'),
(726, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-26 06:49:53'),
(727, 'ferminrichard@icloud.com', '::1', 1, '2025-10-26 06:52:13'),
(728, 'mico.macapugay@icloud.com', '192.168.100.161', 1, '2025-10-26 09:02:20'),
(729, 'hccsuperadmin@gmail.com', '::1', 0, '2025-10-26 09:44:47'),
(730, 'hccsuperadmin@gmail.com', '::1', 0, '2025-10-26 09:44:55'),
(731, 'hccsuperadmin@gmail.com', '::1', 0, '2025-10-26 09:45:23'),
(732, 'hccsuperadmin@gmail.com', '::1', 0, '2025-10-26 09:46:53'),
(733, 'hccsuperadmin@gmail.com', '::1', 0, '2025-10-26 09:47:00'),
(734, 'hccsuperadmin@gmail.com', '::1', 0, '2025-10-26 09:47:08'),
(735, 'hccstarosa@gmail.com', '192.168.100.172', 1, '2025-10-26 09:47:40'),
(736, 'hccsuperadmin@gmail.com', '::1', 0, '2025-10-26 09:49:10'),
(737, 'hccsuperadmin@gmail.com', '::1', 0, '2025-10-26 09:49:13'),
(738, 'hccsuperadmin@gmail.com', '::1', 0, '2025-10-26 09:49:18'),
(739, 'hccsuperadmin@gmail.com', '::1', 0, '2025-10-26 09:53:18'),
(740, 'hccstarosa@gmail.com', '::1', 1, '2025-10-26 10:46:17'),
(741, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-26 10:46:44'),
(742, 'hccstarosa@gmail.com', '::1', 1, '2025-10-26 10:48:14');
INSERT INTO `login_attempts` (`id`, `username`, `ip_address`, `success`, `attempted_at`) VALUES
(743, 'hccstarosa@gmail.com', '::1', 1, '2025-10-26 11:37:05'),
(744, 'hccstarosa@gmail.com', '192.168.100.172', 1, '2025-10-26 11:37:16'),
(745, 'ferminrichard@icloud.com', '::1', 1, '2025-10-26 11:37:28'),
(746, 'hccstarosa@gmail.com', '::1', 1, '2025-10-26 12:40:28'),
(747, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-26 12:41:36'),
(748, 'ferminrichard@icloud.com', '::1', 1, '2025-10-26 13:50:51'),
(749, 'mico.macapugay@icloud.com', '192.168.100.172', 1, '2025-10-26 13:54:15'),
(750, 'hccstarosa@gmail.com', '192.168.100.172', 1, '2025-10-26 14:39:16'),
(751, 'hccstarosa@gmail.com', '::1', 1, '2025-10-26 14:41:05'),
(752, 'mico.macapugay@icloud.com', '192.168.100.172', 1, '2025-10-26 14:41:13'),
(753, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-26 14:42:51'),
(754, 'ferminrichard@icloud.com', '::1', 0, '2025-10-26 14:43:11'),
(755, 'ferminrichard@icloud.com', '::1', 1, '2025-10-26 14:43:20'),
(756, 'hccstarosa@gmail.com', '192.168.100.172', 1, '2025-10-26 14:44:15'),
(757, 'mico.macapugay@icloud.com', '192.168.100.172', 1, '2025-10-26 14:44:43'),
(758, 'hccstarosa@gmail.com', '192.168.100.172', 1, '2025-10-26 14:45:55'),
(759, 'mico.macapugay@icloud.com', '192.168.100.172', 1, '2025-10-26 14:47:14'),
(760, 'hccstarosa@gmail.com', '192.168.100.172', 1, '2025-10-26 14:47:56'),
(761, 'mico.macapugay@icloud.com', '192.168.100.172', 1, '2025-10-26 14:48:23'),
(762, 'hccstarosa@gmail.com', '::1', 1, '2025-10-26 14:49:03'),
(763, 'ferminrichard@icloud.com', '::1', 1, '2025-10-26 15:10:33'),
(764, 'hccstarosa@gmail.com', '::1', 1, '2025-10-26 15:10:59'),
(765, 'ferminrichard@icloud.com', '::1', 1, '2025-10-26 15:12:46'),
(766, 'hccstarosa@gmail.com', '::1', 1, '2025-10-26 15:13:26'),
(767, 'hccstarosa@gmail.com', '192.168.100.172', 1, '2025-10-26 15:17:12'),
(768, 'mico.macapugay@icloud.com', '172.20.10.1', 1, '2025-10-27 01:55:29'),
(769, 'ferminrichard@icloud.com', '::1', 1, '2025-10-27 01:55:40'),
(770, 'mico.macapugay@icloud.com', '10.167.95.37', 1, '2025-10-27 02:03:25'),
(771, 'hccstarosa@gmail.com', '10.167.95.36', 1, '2025-10-27 02:04:06'),
(772, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-27 02:10:38'),
(773, 'ferminrichard@icloud.com', '::1', 1, '2025-10-27 02:11:13'),
(774, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-27 02:11:53'),
(775, 'ferminrichard@icloud.com', '::1', 1, '2025-10-27 02:13:19'),
(776, 'Hccstarosa@gmail.com', '10.167.95.36', 1, '2025-10-27 02:16:34'),
(777, 'hccconception@gmail.com', '10.167.95.36', 1, '2025-10-27 02:17:30'),
(778, 'hccstarosa@gmail.com', '::1', 1, '2025-10-27 23:57:43'),
(779, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-28 02:49:06'),
(780, 'hccstarosa@gmail.com', '::1', 0, '2025-10-28 02:49:54'),
(781, 'hccstarosa@gmail.com', '::1', 1, '2025-10-28 02:50:03'),
(782, 'mico.macapugay@icloud.com', '::1', 0, '2025-10-28 02:50:47'),
(783, 'hccstarosa@gmail.com', '::1', 1, '2025-10-28 02:50:59'),
(784, 'mico.macapugay@icloud.com', '::1', 0, '2025-10-28 02:52:11'),
(785, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-28 02:52:17'),
(786, 'ferminrichard@icloud.com', '::1', 1, '2025-10-28 02:52:50'),
(787, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-28 02:53:18'),
(788, 'hccstarosa@gmail.com', '192.168.100.172', 1, '2025-10-28 03:11:51'),
(789, 'ferminrichard@icloud.com', '::1', 0, '2025-10-28 03:12:01'),
(790, 'mico.macapugay@icloud.com', '192.168.100.161', 1, '2025-10-28 03:12:20'),
(791, 'ferminrichard@icloud.com', '::1', 1, '2025-10-28 03:12:27'),
(792, 'hccstarosa@gmail.com', '::1', 1, '2025-10-28 05:05:29'),
(793, 'hccstarosa@gmail.com', '::1', 0, '2025-10-28 05:46:05'),
(794, 'hccstarosa@gmail.com', '::1', 1, '2025-10-28 05:46:11'),
(795, 'ferminrichard@icloud.com', '::1', 1, '2025-10-28 06:07:21'),
(796, 'mico.macapugay@icloud.com', '192.168.100.161', 1, '2025-10-28 06:07:38'),
(797, 'mico.macapugay@icloud.com', '192.168.100.161', 1, '2025-10-28 06:19:19'),
(798, 'hccstarosa@gmail.com', '192.168.100.161', 1, '2025-10-28 07:55:28'),
(799, 'ferminrichard@icloud.com', '::1', 1, '2025-10-28 10:13:23'),
(800, 'mico.macapugay@icloud.com', '192.168.100.161', 1, '2025-10-28 10:13:40'),
(801, 'ferminrichard@icloud.com', '::1', 1, '2025-10-28 11:27:13'),
(802, 'hccstarosa@gmail.com', '::1', 1, '2025-10-28 12:47:30'),
(803, 'mico.macapugay@icloud.com', '192.168.100.161', 1, '2025-10-28 13:20:27'),
(804, 'ferminrichard@icloud.com', '::1', 1, '2025-10-28 13:21:09'),
(805, 'mico.macapugay@icloud.com', '192.168.100.161', 1, '2025-10-28 13:29:02'),
(806, 'hccstarosa@gmail.com', '::1', 1, '2025-10-28 13:36:01'),
(807, 'mico.macapugay@icloud.com', '192.168.100.172', 1, '2025-10-28 14:05:28'),
(808, 'ferminrichard@icloud.com', '::1', 0, '2025-10-28 14:06:27'),
(809, 'ferminrichard@icloud.com', '::1', 1, '2025-10-28 14:06:37'),
(810, 'hccstarosa@gmail.com', '192.168.100.172', 1, '2025-10-28 14:07:41'),
(811, 'mico.macapugay@icloud.com', '::1', 0, '2025-10-28 14:14:26'),
(812, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-28 14:14:31'),
(813, 'ferminrichard@icloud.com', '::1', 0, '2025-10-28 14:18:07'),
(814, 'ferminrichard@icloud.com', '::1', 1, '2025-10-28 14:18:17'),
(815, 'ferminrichard@icloud.com', '::1', 1, '2025-10-29 00:26:22'),
(816, 'mico.macapugay@icloud.com', '172.20.10.1', 1, '2025-10-29 00:26:27'),
(817, 'hccstarosa@gmail.com', '172.20.10.1', 1, '2025-10-29 00:27:22'),
(818, 'mico.macapugay@icloud.com', '172.20.10.1', 1, '2025-10-29 00:33:21'),
(819, 'hccstarosa@gmail.com', '::1', 1, '2025-10-29 00:33:31'),
(820, 'ferminrichard@icloud.com', '::1', 0, '2025-10-29 00:37:23'),
(821, 'ferminrichard@icloud.com', '::1', 1, '2025-10-29 00:37:36'),
(822, 'hccstarosa@gmail.com', '172.20.10.1', 1, '2025-10-29 00:41:45'),
(823, 'hccstarosa@gmail.com', '::1', 1, '2025-10-29 00:44:37'),
(824, 'mico.macapugay@icloud.com', '172.20.10.1', 1, '2025-10-29 00:44:38'),
(825, 'ferminrichard@icloud.com', '::1', 1, '2025-10-29 00:46:30'),
(826, 'hccstarosa@gmail.com', '::1', 1, '2025-10-29 00:47:52'),
(827, 'ferminrichard@icloud.com', '::1', 1, '2025-10-29 00:48:37'),
(828, 'hccstarosa@gmail.com', '::1', 0, '2025-10-29 01:13:50'),
(829, 'hccstarosa@gmail.com', '::1', 0, '2025-10-29 01:14:07'),
(830, 'hccstarosa@gmail.com', '::1', 0, '2025-10-29 01:16:12'),
(831, 'hccstarosa@gmail.com', '::1', 1, '2025-10-29 01:16:20'),
(832, 'annpaulamendoza@gmail.com', '::1', 0, '2025-10-29 01:51:40'),
(833, 'hccstarosa@gmail.com', '::1', 1, '2025-10-29 01:59:57'),
(834, 'jennamariesolitario@gmail.com', '::1', 1, '2025-10-29 02:02:34'),
(835, 'hccstarosa@gmail.com', '::1', 1, '2025-10-29 02:19:15'),
(836, 'mico.macapugay@icloud.com', '172.20.10.1', 1, '2025-10-29 07:09:54'),
(837, 'hccstarosa@gmail.com', '::1', 1, '2025-10-29 07:10:13'),
(838, 'ferminrichard@icloud.com', '::1', 1, '2025-10-29 07:13:14'),
(839, 'hccstarosa@gmail.com', '::1', 1, '2025-10-29 07:14:25'),
(840, 'hccstarosa@gmail.com', '::1', 1, '2025-10-29 07:21:16'),
(841, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-29 08:26:47'),
(842, 'hccstarosa@gmail.com', '::1', 1, '2025-10-29 08:27:39'),
(843, 'ferminrichard@icloud.com', '::1', 1, '2025-10-29 08:32:01'),
(844, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-29 08:49:33'),
(845, 'hccstarosa@gmail.com', '::1', 1, '2025-10-29 09:14:11'),
(846, 'mico.macapugay@icloud.com', '::1', 1, '2025-10-29 09:14:39'),
(847, 'hccstarosa@gmail.com', '::1', 1, '2025-10-29 16:17:00'),
(848, 'mico.macapugay@icloud.com', '::1', 1, '2025-11-01 04:15:04'),
(849, 'ferminrichard@icloud.com', '::1', 1, '2025-11-01 04:19:35'),
(850, 'mico.macapugay2004@gmail.com', '::1', 1, '2025-11-01 09:26:03'),
(851, 'mico.macapugay2004@gmail.com', '::1', 0, '2025-11-01 09:33:38'),
(852, 'mico.macapugay2004@gmail.com', '::1', 0, '2025-11-01 09:33:45'),
(853, 'mico.macapugay2004@gmail.com', '::1', 1, '2025-11-01 09:33:51'),
(854, 'mico.macapugay2004@gmail.com', '::1', 0, '2025-11-01 09:52:18'),
(855, 'mico.macapugay2004@gmail.com', '::1', 0, '2025-11-01 09:52:24'),
(856, 'mico.macapugay2004@gmail.com', '::1', 0, '2025-11-01 09:52:32'),
(857, 'mico.macapugay2004@gmail.com', '::1', 0, '2025-11-01 09:52:36'),
(858, 'mico.macapugay2004@gmail.com', '::1', 0, '2025-11-01 09:52:48'),
(859, 'mico.macapugay2004@gmail.com', '::1', 0, '2025-11-01 09:53:24'),
(860, 'mico.macapugay2004@gmail.com', '::1', 0, '2025-11-01 09:53:30'),
(861, 'mico.macapugay2004@gmail.com', '::1', 0, '2025-11-01 09:53:52'),
(862, 'mico.macapugay2004@gmail.com', '::1', 0, '2025-11-01 09:54:19'),
(863, 'mico.macapugay2004@gmail.com', '::1', 0, '2025-11-01 09:54:23'),
(864, 'mico.macapugay2004@gmail.com', '::1', 0, '2025-11-01 09:56:26'),
(865, 'mico.macapugay2004@gmail.com', '::1', 0, '2025-11-01 09:56:31'),
(866, 'ferminrichard@icloud.com', '::1', 0, '2025-11-01 09:57:00'),
(867, 'ferminrichard@icloud.com', '::1', 1, '2025-11-01 09:57:05'),
(868, 'mico.macapugay2004@gmail.com', '::1', 0, '2025-11-01 09:57:24'),
(869, 'mico.macapugay2004@gmail.com', '::1', 0, '2025-11-01 10:06:56'),
(870, 'mico.macapugay2004@gmail.com', '::1', 0, '2025-11-01 10:10:33'),
(871, 'mico.macapugay2004@gmail.com', '::1', 0, '2025-11-01 10:10:40'),
(872, 'mico.macapugay2004@gmail.com', '::1', 0, '2025-11-01 10:27:33'),
(873, 'mico.macapugay2004@gmail.com', '::1', 0, '2025-11-01 10:27:38'),
(874, 'jennamariesolitario@gmail.com', '::1', 1, '2025-11-01 10:30:32'),
(875, 'ferminrichard@icloud.com', '192.168.100.172', 1, '2025-11-01 10:31:36'),
(876, 'jennamariesolitario@gmail.com', '192.168.100.172', 1, '2025-11-01 11:38:45'),
(877, 'mico.macapugay@icloud.com', '::1', 1, '2025-11-01 11:39:13'),
(878, 'mico.macapugay@icloud.com', '192.168.100.172', 1, '2025-11-01 11:42:37'),
(879, 'mico.macapugay@icloud.com', '::1', 1, '2025-11-01 11:44:52'),
(880, 'jennamariesolitario@gmail.com', '192.168.100.172', 1, '2025-11-01 12:04:42'),
(881, 'mico.macapugay@icloud.com', '192.168.100.172', 1, '2025-11-01 12:05:05'),
(882, 'jennamariesolitario@gmail.com', '::1', 1, '2025-11-01 12:05:12'),
(883, 'jennamariesolitario@gmail.com', '::1', 1, '2025-11-01 12:30:22'),
(884, 'jennamariesolitario@gmail.com', '::1', 1, '2025-11-01 12:33:01'),
(885, 'ferminrichard@icloud.com', '::1', 1, '2025-11-01 12:41:10'),
(886, 'jennamariesolitario@gmail.com', '::1', 1, '2025-11-01 12:57:09'),
(887, 'mico.macapugay@icloud.com', '192.168.100.172', 1, '2025-11-01 13:04:53'),
(888, 'ferminrichard@icloud.com', '::1', 1, '2025-11-01 13:05:09'),
(889, 'jennamariesolitario@gmail.com', '::1', 1, '2025-11-01 13:05:29'),
(890, 'jennamariesolitario@gmail.com', '::1', 1, '2025-11-01 13:13:56'),
(891, 'jennamariesolitario@gmail.com', '::1', 1, '2025-11-01 13:17:16'),
(892, 'jennamariesolitario@gmail.com', '::1', 1, '2025-11-01 13:22:47'),
(893, 'shainalyncruz3@gmail.com', '::1', 1, '2025-11-01 13:28:29'),
(894, 'ferminrichard30@icloud.com', '192.168.100.172', 0, '2025-11-01 13:29:06'),
(895, 'ferminrichard30@icloud.com', '192.168.100.172', 0, '2025-11-01 13:29:15'),
(896, 'ferminrichard30@icloud.com', '192.168.100.172', 0, '2025-11-01 13:29:26'),
(897, 'ferminrichard@icloud.com', '192.168.100.172', 1, '2025-11-01 13:29:41'),
(898, 'mico.macapugay@icloud.com', '192.168.100.172', 1, '2025-11-01 13:33:10'),
(899, 'shainalyncruz3@gmail.com', '192.168.100.172', 1, '2025-11-01 13:37:39'),
(900, 'mico.macapugay@icloud.com', '::1', 1, '2025-11-01 13:37:46'),
(901, 'shainalyncruz3@gmail.com', '::1', 1, '2025-11-01 13:38:28'),
(902, 'shainalyncruz3@gmail.com', '::1', 1, '2025-11-01 13:40:57'),
(903, 'shainalyncruz3@gmail.com', '192.168.100.172', 1, '2025-11-01 13:41:42'),
(904, 'shainalyncruz3@gmail.com', '::1', 1, '2025-11-01 13:43:05'),
(905, 'jennamariesolitario@gmail.com', '::1', 1, '2025-11-01 13:45:33'),
(906, 'ferminrichard@icloud.com', '192.168.100.172', 1, '2025-11-01 13:46:01'),
(907, 'jennamariesolitario@gmail.com', '::1', 1, '2025-11-01 13:47:12'),
(908, 'jennamariesolitario@gmail.com', '::1', 1, '2025-11-01 13:50:01'),
(909, 'jennamariesolitario@gmail.com', '::1', 1, '2025-11-01 13:50:23'),
(910, 'jennamariesolitario@gmail.com', '::1', 1, '2025-11-01 13:56:05'),
(911, 'mico.macapugay2004@gmail.com', '::1', 1, '2025-11-01 14:10:42'),
(912, 'mico.macapugay2004@gmail.com', '::1', 1, '2025-11-01 14:11:14'),
(913, 'mico.macapugay2004@gmail.com', '::1', 1, '2025-11-01 14:38:43'),
(914, 'mico.macapugay@icloud.com', '::1', 1, '2025-11-01 17:01:20'),
(915, 'ferminrichard@icloud.com', '::1', 1, '2025-11-01 17:01:38'),
(916, 'mico.macapugay2004@gmail.com', '::1', 1, '2025-11-01 17:02:30'),
(917, 'ferminrichard@icloud.com', '::1', 1, '2025-11-05 11:27:26'),
(918, 'mico.macapugay@icloud.com', '::1', 1, '2025-11-05 11:49:24'),
(919, 'mico.macapugay@icloud.com', '::1', 0, '2025-11-06 10:39:14'),
(920, 'jemusubeley@gmail.com', '::1', 1, '2025-11-06 10:43:07'),
(921, 'jemusubeley@gmail.com', '::1', 1, '2025-11-06 10:43:59'),
(922, 'jemusubeley@gmail.com', '::1', 1, '2025-11-06 11:25:20'),
(923, 'jemusubeley@gmail.com', '::1', 1, '2025-11-06 11:31:27'),
(924, 'jemusubeley@gmail.com', '::1', 1, '2025-11-06 11:32:37'),
(925, 'jemusubeley@gmail.com', '::1', 1, '2025-11-06 11:34:05'),
(926, 'jemusubeley@gmail.com', '::1', 1, '2025-11-06 11:42:31'),
(927, 'jemusubeley@gmail.com', '::1', 1, '2025-11-06 11:43:28'),
(928, 'jemusubeley@gmail.com', '::1', 1, '2025-11-06 11:43:59'),
(929, 'jemusubeley@gmail.com', '::1', 1, '2025-11-06 11:44:30'),
(930, 'jemusubeley@gmail.com', '::1', 1, '2025-11-06 11:56:31'),
(931, 'jemusubeley@gmail.com', '::1', 1, '2025-11-06 11:56:49'),
(932, 'jemusubeley@gmail.com', '::1', 1, '2025-11-06 12:00:53'),
(933, 'andrewbeley7@gmail.com', '::1', 1, '2025-11-06 12:02:38'),
(934, 'andrewbeley7@gmail.com', '::1', 1, '2025-11-06 12:06:43'),
(935, 'andrewbeley7@gmail.com', '::1', 1, '2025-11-06 12:13:59'),
(936, 'andrewbeley7@gmail.com', '::1', 1, '2025-11-06 12:23:14'),
(937, 'andrewbeley7@gmail.com', '::1', 1, '2025-11-06 12:28:47'),
(938, 'jemusubeley@gmail.com', '::1', 1, '2025-11-06 12:29:40'),
(939, 'jemusubeley@gmail.com', '::1', 1, '2025-11-06 12:54:39'),
(940, 'andrewbeley7@gmail.com', '::1', 1, '2025-11-06 12:54:57'),
(941, 'andrewbeley7@gmail.com', '::1', 1, '2025-11-06 13:16:18'),
(942, 'jemusubeley@gmail.com', '::1', 1, '2025-11-07 14:17:47'),
(943, 'andrewbeley7@gmail.com', '::1', 1, '2025-11-07 14:23:36'),
(944, 'jemusubeley@gmail.com', '::1', 1, '2025-11-07 14:24:35'),
(945, 'jemusubeley@gmail.com', '::1', 1, '2025-11-07 14:26:30'),
(946, 'jemusubeley@gmail.com', '::1', 1, '2025-11-07 14:35:35'),
(947, 'andrewbeley7@gmail.com', '::1', 1, '2025-11-07 14:59:15'),
(948, 'jemusubeley@gmail.com', '::1', 1, '2025-11-07 15:12:37'),
(949, 'jemusubeley@gmail.com', '::1', 1, '2025-11-07 15:16:53'),
(950, 'jemusubeley@gmail.com', '::1', 1, '2025-11-07 15:32:35'),
(951, 'jemusubeley@gmail.com', '::1', 1, '2025-11-07 15:33:31'),
(952, 'jemusubeley@gmail.com', '::1', 1, '2025-11-07 17:08:50'),
(953, 'jemusubeley@gmail.com', '::1', 1, '2025-11-07 17:09:49'),
(954, 'jemusubeley@gmail.com', '::1', 1, '2025-11-07 17:33:56');

-- --------------------------------------------------------

--
-- Table structure for table `missing_assets_reports`
--

CREATE TABLE `missing_assets_reports` (
  `id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `reported_by` int(11) NOT NULL COMMENT 'User who reported missing',
  `reported_date` datetime NOT NULL DEFAULT current_timestamp(),
  `last_known_location` varchar(255) DEFAULT NULL,
  `last_known_borrower` varchar(255) DEFAULT NULL,
  `last_known_borrower_contact` varchar(255) DEFAULT NULL,
  `last_seen_date` date DEFAULT NULL,
  `responsible_department` varchar(255) DEFAULT NULL,
  `description` text NOT NULL COMMENT 'Details about the missing asset',
  `status` enum('reported','investigating','found','permanently_lost') DEFAULT 'reported',
  `resolution_notes` text DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_date` datetime DEFAULT NULL,
  `campus_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'User who receives the notification',
  `type` enum('return_reminder','overdue_alert','approval_request','approval_response','missing_report','system_alert') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `related_type` enum('asset','borrowing','request','maintenance') DEFAULT NULL COMMENT 'Type of related entity',
  `related_id` int(11) DEFAULT NULL COMMENT 'ID of related entity',
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `action_url` varchar(500) DEFAULT NULL COMMENT 'URL to take action',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime DEFAULT NULL COMMENT 'When notification becomes irrelevant'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `message`, `related_type`, `related_id`, `is_read`, `read_at`, `priority`, `action_url`, `created_at`, `expires_at`) VALUES
(1, 160, 'return_reminder', 'Asset Return Reminder', 'Your borrowed Laptop (Dell XPS 15) is due for return in 2 days. Please return it on time.', NULL, NULL, 1, '2025-11-07 22:26:10', 'medium', NULL, '2025-11-06 12:01:06', NULL),
(2, 160, 'overdue_alert', 'URGENT: Asset Overdue!', 'Your borrowed Projector (Epson EB-X41) is now 5 days overdue. Please return immediately to avoid penalties.', NULL, NULL, 1, '2025-11-07 22:26:10', 'urgent', NULL, '2025-11-06 12:03:08', NULL),
(3, 160, 'approval_request', 'New Asset Request Pending', 'John Doe has requested to borrow a Camera (Canon EOS). Please review and approve.', NULL, NULL, 1, '2025-11-07 22:26:10', 'high', NULL, '2025-11-06 12:03:10', NULL),
(4, 160, 'approval_response', 'Request Approved', 'Your asset request for Tablet (iPad Pro) has been approved. Please collect from custodian office.', NULL, NULL, 1, '2025-11-07 22:26:10', 'high', NULL, '2025-11-06 12:03:12', NULL),
(5, 160, 'missing_report', 'Asset Marked as Missing', 'Scanner (HP ScanJet) borrowed by Jane Smith has been marked as missing after 60 days overdue.', NULL, NULL, 1, '2025-11-07 22:26:10', 'urgent', NULL, '2025-11-06 12:03:13', NULL),
(6, 160, 'system_alert', 'System Maintenance Notice', 'The asset management system will undergo scheduled maintenance on Saturday, 10 PM - 12 AM.', NULL, NULL, 1, '2025-11-07 22:26:10', 'low', NULL, '2025-11-06 12:03:15', NULL),
(7, 160, 'return_reminder', 'Asset Return Reminder', 'Your borrowed Laptop (Dell XPS 15) is due for return in 2 days. Please return it on time.', NULL, NULL, 1, '2025-11-07 22:26:10', 'medium', NULL, '2025-11-06 12:03:18', NULL),
(8, 160, 'overdue_alert', 'URGENT: Asset Overdue!', 'Your borrowed Projector (Epson EB-X41) is now 5 days overdue. Please return immediately to avoid penalties.', NULL, NULL, 1, '2025-11-07 22:26:10', 'urgent', NULL, '2025-11-06 12:03:19', NULL),
(9, 160, 'approval_request', 'New Asset Request Pending', 'John Doe has requested to borrow a Camera (Canon EOS). Please review and approve.', NULL, NULL, 1, '2025-11-07 22:26:10', 'high', NULL, '2025-11-06 12:03:19', NULL),
(10, 161, 'approval_response', 'Request Approved', 'Your asset request for Tablet (iPad Pro) has been approved. Please collect from custodian office.', NULL, NULL, 1, '2025-11-06 21:33:02', 'high', NULL, '2025-11-06 12:03:19', NULL),
(11, 160, 'system_alert', 'System Maintenance Notice', 'The asset management system will undergo scheduled maintenance on Saturday, 10 PM - 12 AM.', NULL, NULL, 1, '2025-11-07 22:26:10', 'low', NULL, '2025-11-06 12:03:20', NULL),
(12, 161, 'return_reminder', 'Asset Return Reminder', 'Your borrowed Laptop (Dell XPS 15) is due for return in 2 days. Please return it on time.', NULL, NULL, 1, '2025-11-06 21:16:23', 'medium', NULL, '2025-11-06 12:30:39', NULL),
(13, 160, 'overdue_alert', 'URGENT: Asset Overdue!', 'Your borrowed Projector (Epson EB-X41) is now 5 days overdue. Please return immediately to avoid penalties.', NULL, NULL, 1, '2025-11-07 22:26:10', 'urgent', NULL, '2025-11-06 12:30:39', NULL),
(14, 161, 'approval_request', 'New Asset Request Pending', 'John Doe has requested to borrow a Camera (Canon EOS). Please review and approve.', NULL, NULL, 1, '2025-11-06 21:16:23', 'high', NULL, '2025-11-06 12:30:40', NULL),
(15, 161, 'approval_response', 'Request Approved', 'Your asset request for Tablet (iPad Pro) has been approved. Please collect from custodian office.', NULL, NULL, 1, '2025-11-06 21:05:28', 'high', NULL, '2025-11-06 12:30:40', NULL),
(16, 161, 'system_alert', 'System Maintenance Notice', 'The asset management system will undergo scheduled maintenance on Saturday, 10 PM - 12 AM.', NULL, NULL, 1, '2025-11-06 20:58:48', 'low', NULL, '2025-11-06 12:30:40', NULL),
(17, 161, 'return_reminder', 'Asset Return Reminder', 'Your borrowed Laptop (Dell XPS 15) is due for return in 2 days. Please return it on time.', NULL, NULL, 1, '2025-11-06 21:33:02', 'medium', NULL, '2025-11-06 13:32:08', NULL),
(18, 161, 'return_reminder', 'Asset Return Reminder', 'Your borrowed Laptop (Dell XPS 15) is due for return in 2 days. Please return it on time.', NULL, NULL, 1, '2025-11-06 21:33:02', 'medium', NULL, '2025-11-06 13:32:15', NULL),
(19, 161, 'overdue_alert', 'URGENT: Asset Overdue!', 'Your borrowed Projector (Epson EB-X41) is now 5 days overdue. Please return immediately to avoid penalties.', NULL, NULL, 1, '2025-11-06 21:33:02', 'urgent', NULL, '2025-11-06 13:32:15', NULL),
(20, 161, 'approval_request', 'New Asset Request Pending', 'John Doe has requested to borrow a Camera (Canon EOS). Please review and approve.', NULL, NULL, 1, '2025-11-06 21:33:02', 'high', NULL, '2025-11-06 13:32:16', NULL),
(21, 161, 'approval_response', 'Request Approved', 'Your asset request for Tablet (iPad Pro) has been approved. Please collect from custodian office.', NULL, NULL, 1, '2025-11-06 21:33:02', 'high', NULL, '2025-11-06 13:32:16', NULL),
(22, 161, 'system_alert', 'System Maintenance Notice', 'The asset management system will undergo scheduled maintenance on Saturday, 10 PM - 12 AM.', NULL, NULL, 1, '2025-11-06 21:33:02', 'low', NULL, '2025-11-06 13:32:16', NULL),
(23, 140, 'approval_request', 'New Asset Request', 'Micy has requested Chair (Qty: 1). Please review and approve.', NULL, NULL, 0, NULL, 'high', '/AMS-REQ/custodian/approve_requests.php', '2025-11-07 14:24:01', NULL),
(24, 140, 'approval_request', 'New Asset Request', 'Micy has requested Chair (Qty: 1). Please review and approve.', NULL, NULL, 0, NULL, 'high', '/AMS-REQ/custodian/approve_requests.php', '2025-11-07 14:36:36', NULL),
(25, 140, 'approval_request', 'New Asset Request', 'Micy has requested Chair (Qty: 1). Please review and approve.', 'request', 133, 0, NULL, 'high', '/AMS-REQ/custodian/approve_requests.php', '2025-11-07 14:39:45', NULL),
(26, 140, 'approval_request', 'New Asset Request', 'Micy has requested Chair (Qty: 1). Please review and approve.', 'request', 134, 0, NULL, 'high', '/AMS-REQ/custodian/approve_requests.php', '2025-11-07 14:42:41', NULL),
(27, 140, 'approval_request', 'New Asset Request', 'Micy has requested Chair (Qty: 1). Please review and approve.', 'request', 135, 0, NULL, 'high', '/AMS-REQ/custodian/approve_requests.php', '2025-11-07 14:46:12', NULL),
(28, 140, 'approval_request', 'New Asset Request', 'Micy has requested Chair (Qty: 1). Please review and approve.', 'request', 136, 0, NULL, 'high', '/AMS-REQ/custodian/approve_requests.php', '2025-11-07 14:51:22', NULL),
(33, 140, 'approval_request', 'New Asset Request', 'Micy has requested Chair (Qty: 1). Please review and approve.', 'request', 140, 0, NULL, 'high', '/AMS-REQ/custodian/approve_requests.php', '2025-11-07 14:59:54', NULL),
(34, 160, 'approval_request', 'New Asset Request', 'Micy has requested Chair (Qty: 1). Please review and approve.', 'request', 140, 1, '2025-11-07 23:13:50', 'high', '/AMS-REQ/custodian/approve_requests.php', '2025-11-07 15:00:00', NULL),
(35, 140, 'approval_request', 'New Asset Request', 'Micy has requested Chair (Qty: 2). Please review and approve.', 'request', 141, 0, NULL, 'high', '/AMS-REQ/custodian/approve_requests.php', '2025-11-07 15:00:49', NULL),
(36, 160, 'approval_request', 'New Asset Request', 'Micy has requested Chair (Qty: 2). Please review and approve.', 'request', 141, 1, '2025-11-07 23:13:50', 'high', '/AMS-REQ/custodian/approve_requests.php', '2025-11-07 15:00:57', NULL),
(37, 140, 'approval_request', 'New Asset Request', 'Micy has requested Chair (Qty: 3). Please review and approve.', 'request', 142, 0, NULL, 'high', '/AMS-REQ/custodian/approve_requests.php', '2025-11-07 15:04:56', NULL),
(38, 160, 'approval_request', 'New Asset Request', 'Micy has requested Chair (Qty: 3). Please review and approve.', 'request', 142, 1, '2025-11-07 23:13:50', 'high', '/AMS-REQ/custodian/approve_requests.php', '2025-11-07 15:04:59', NULL),
(39, 140, 'approval_request', 'New Asset Request', 'Micy has requested Chair (Qty: 5). Please review and approve.', 'request', 143, 0, NULL, 'high', '/AMS-REQ/custodian/approve_requests.php', '2025-11-07 15:05:44', NULL),
(40, 160, 'approval_request', 'New Asset Request', 'Micy has requested Chair (Qty: 5). Please review and approve.', 'request', 143, 1, '2025-11-07 23:13:50', 'high', '/AMS-REQ/custodian/approve_requests.php', '2025-11-07 15:05:47', NULL),
(41, 161, 'approval_response', 'Request Approved by Custodian', 'Your request #141 for Chair has been approved by the custodian. Waiting for final admin approval.', 'request', 141, 0, NULL, 'high', '/AMS-REQ/my_requests.php?id=141', '2025-11-07 15:10:04', NULL),
(42, 161, 'approval_response', 'Asset Request Approved!', 'Your request #143 has been approved. Please coordinate with the custodian for asset pickup.', 'request', 143, 0, NULL, 'high', '/AMS-REQ/staff/my_requests.php?id=143', '2025-11-07 15:12:48', NULL),
(43, 160, 'system_alert', 'Asset Ready for Release', 'Request #143 has been fully approved. Please release the asset to the requester.', 'request', 143, 1, '2025-11-07 23:13:50', 'medium', '/AMS-REQ/custodian/requests.php?id=143', '2025-11-07 15:12:50', NULL),
(44, 161, '', 'Asset Released', 'Your requested asset has been released. Please return by 2025-11-09.', 'request', 143, 0, NULL, 'medium', '/AMS-REQ/my_requests.php?id=143', '2025-11-07 15:24:37', NULL),
(45, 161, 'approval_response', 'Asset Request Approved!', 'Your request #142 has been approved. Please coordinate with the custodian for asset pickup.', 'request', 142, 0, NULL, 'high', '/AMS-REQ/staff/my_requests.php?id=142', '2025-11-07 15:32:45', NULL),
(46, 160, 'system_alert', 'Asset Ready for Release', 'Request #142 has been fully approved. Please release the asset to the requester.', 'request', 142, 0, NULL, 'medium', '/AMS-REQ/custodian/requests.php?id=142', '2025-11-07 15:32:49', NULL),
(47, 161, 'approval_response', 'Asset Request Approved!', 'Your request #141 has been approved. Please coordinate with the custodian for asset pickup.', 'request', 141, 0, NULL, 'high', '/AMS-REQ/staff/my_requests.php?id=141', '2025-11-07 15:32:57', NULL),
(48, 160, 'system_alert', 'Asset Ready for Release', 'Request #141 has been fully approved. Please release the asset to the requester.', 'request', 141, 0, NULL, 'medium', '/AMS-REQ/custodian/requests.php?id=141', '2025-11-07 15:33:00', NULL),
(49, 161, '', 'Asset Released', 'Your requested asset has been released. Please return by 2025-11-08.', 'request', 142, 0, NULL, 'medium', '/AMS-REQ/my_requests.php?id=142', '2025-11-07 15:34:27', NULL),
(50, 161, '', 'Asset Released', 'Your requested asset has been released. Please return by 2025-11-08.', 'request', 141, 0, NULL, 'medium', '/AMS-REQ/my_requests.php?id=141', '2025-11-07 15:36:55', NULL),
(51, 161, '', 'Asset Return Processed', 'Your returned asset has been received and processed. Thank you!', 'request', 143, 0, NULL, 'low', '/AMS-REQ/my_requests.php?id=143', '2025-11-07 15:39:25', NULL),
(52, 161, '', 'Asset Return Processed', 'Your returned asset has been received and processed. Thank you!', 'request', 142, 0, NULL, 'low', '/AMS-REQ/my_requests.php?id=142', '2025-11-07 15:44:01', NULL),
(53, 161, '', 'Asset Return Processed', 'Your returned asset has been received and processed. Thank you!', 'request', 141, 0, NULL, 'low', '/AMS-REQ/my_requests.php?id=141', '2025-11-07 15:47:02', NULL),
(54, 140, '', 'Asset Returned', 'Asset from request #141 has been returned by Micy in Good condition.', 'request', 141, 0, NULL, 'medium', '/AMS-REQ/custodian/return_assets.php', '2025-11-07 15:47:05', NULL),
(55, 140, 'approval_request', 'New Asset Request', 'Micy has requested Chair (Qty: 7). Please review and approve.', 'request', 144, 0, NULL, 'high', '/AMS-REQ/custodian/approve_requests.php', '2025-11-07 17:07:17', NULL),
(56, 160, 'approval_request', 'New Asset Request', 'Micy has requested Chair (Qty: 7). Please review and approve.', 'request', 144, 0, NULL, 'high', '/AMS-REQ/custodian/approve_requests.php', '2025-11-07 17:07:21', NULL),
(57, 161, 'approval_response', 'Request Approved by Custodian', 'Your request #144 for Chair has been approved by the custodian. Waiting for final admin approval.', 'request', 144, 0, NULL, 'high', '/AMS-REQ/my_requests.php?id=144', '2025-11-07 17:07:43', NULL),
(58, 140, 'approval_request', 'New Asset Request', 'Micy has requested Chair (Qty: 8). Please review and approve.', 'request', 145, 0, NULL, 'high', '/AMS-REQ/custodian/approve_requests.php', '2025-11-07 17:08:43', NULL),
(59, 161, 'approval_response', 'Asset Request Approved!', 'Your request #144 has been approved. Please coordinate with the custodian for asset pickup.', 'request', 144, 0, NULL, 'high', '/AMS-REQ/staff/my_requests.php?id=144', '2025-11-07 17:08:58', NULL),
(60, 160, 'system_alert', 'Asset Ready for Release', 'Request #144 has been fully approved. Please release the asset to the requester.', 'request', 144, 0, NULL, 'medium', '/AMS-REQ/custodian/requests.php?id=144', '2025-11-07 17:09:02', NULL),
(61, 161, 'approval_response', 'Asset Request Approved!', 'Your request #131 has been approved. Please coordinate with the custodian for asset pickup.', 'request', 131, 0, NULL, 'high', '/AMS-REQ/staff/my_requests.php?id=131', '2025-11-07 17:09:13', NULL),
(62, 160, 'system_alert', 'Asset Ready for Release', 'Request #131 has been fully approved. Please release the asset to the requester.', 'request', 131, 0, NULL, 'medium', '/AMS-REQ/custodian/requests.php?id=131', '2025-11-07 17:09:17', NULL),
(63, 161, '', 'Asset Released', 'Your requested asset has been released. Please return by 2025-11-08.', 'request', 144, 0, NULL, 'medium', '/AMS-REQ/my_requests.php?id=144', '2025-11-07 17:09:58', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `offices`
--

CREATE TABLE `offices` (
  `id` int(11) NOT NULL,
  `office_name` varchar(255) NOT NULL,
  `floor` varchar(50) DEFAULT NULL,
  `section_code` varchar(100) DEFAULT NULL,
  `campus_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `offices`
--

INSERT INTO `offices` (`id`, `office_name`, `floor`, `section_code`, `campus_id`, `description`, `created_at`, `updated_at`) VALUES
(16, 'MIS', '3rd', 'MIS Office', 1, NULL, '2025-11-01 14:09:44', '2025-11-01 14:09:44');

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
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `report_title` varchar(255) NOT NULL,
  `report_type` enum('daily','weekly','monthly','yearly') NOT NULL,
  `campus_id` int(11) NOT NULL,
  `generation_date` datetime NOT NULL DEFAULT current_timestamp(),
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `generated_by` varchar(100) NOT NULL DEFAULT 'system'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Table structure for table `sms_notifications`
--

CREATE TABLE `sms_notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `phone_number` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `type` enum('return_reminder','overdue_alert','approval_notification','general') NOT NULL,
  `status` enum('pending','sent','failed','delivered') DEFAULT 'pending',
  `sent_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `provider_response` text DEFAULT NULL,
  `related_type` varchar(50) DEFAULT NULL,
  `related_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','integer','boolean','json','date') DEFAULT 'string',
  `description` text DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL COMMENT 'Group settings by category',
  `is_public` tinyint(1) DEFAULT 0 COMMENT 'Can be accessed by non-admins',
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `category`, `is_public`, `updated_by`, `updated_at`, `created_at`) VALUES
(1, 'reminder_days_before', '2', 'integer', 'Days before return date to send reminder', 'notifications', 0, NULL, '2025-11-06 10:20:23', '2025-11-06 10:20:23'),
(2, 'overdue_check_enabled', 'true', 'boolean', 'Enable automatic overdue checking', 'notifications', 0, NULL, '2025-11-06 10:20:23', '2025-11-06 10:20:23'),
(3, 'require_department_approval', 'true', 'boolean', 'Require department head approval for requests', 'workflow', 0, NULL, '2025-11-06 10:20:23', '2025-11-06 10:20:23'),
(4, 'require_custodian_review', 'true', 'boolean', 'Require custodian review before admin approval', 'workflow', 0, NULL, '2025-11-06 10:20:23', '2025-11-06 10:20:23'),
(5, 'allow_direct_borrowing', 'false', 'boolean', 'Allow direct borrowing without request process', 'workflow', 0, NULL, '2025-11-06 10:20:23', '2025-11-06 10:20:23'),
(6, 'depreciation_method', 'straight_line', 'string', 'Depreciation calculation method', 'finance', 0, NULL, '2025-11-06 10:20:23', '2025-11-06 10:20:23'),
(7, 'enable_sms_notifications', 'false', 'boolean', 'Enable SMS notifications', 'notifications', 0, NULL, '2025-11-06 10:20:23', '2025-11-06 10:20:23'),
(8, 'enable_email_notifications', 'true', 'boolean', 'Enable email notifications', 'notifications', 0, NULL, '2025-11-06 10:20:23', '2025-11-06 10:20:23'),
(9, 'max_borrowing_days', '30', 'integer', 'Maximum days an asset can be borrowed', 'borrowing', 0, NULL, '2025-11-06 10:20:23', '2025-11-06 10:20:23'),
(10, 'auto_missing_after_days', '60', 'integer', 'Auto-mark as missing after X days overdue', 'borrowing', 0, NULL, '2025-11-06 10:20:23', '2025-11-06 10:20:23');

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
  `role` enum('staff','custodian','admin','super_admin','office','auditor','employee') NOT NULL DEFAULT 'staff',
  `campus_id` int(11) DEFAULT NULL,
  `office_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL COMMENT 'User department for approval hierarchy',
  `profile_picture` varchar(255) DEFAULT NULL,
  `notification_preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`notification_preferences`)),
  `is_active` tinyint(1) DEFAULT 1,
  `force_password_change` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role`, `campus_id`, `office_id`, `department_id`, `profile_picture`, `notification_preferences`, `is_active`, `force_password_change`, `last_login`, `created_at`, `updated_at`) VALUES
(38, 'hccconception', 'hccconception@gmail.com', '$2y$10$j1qJWoc87Z0KvP97hqEPg.L5gucJsotzCQoJYzkA6rwNv55PAdVyO', 'Conception HCC', 'admin', 2, NULL, NULL, NULL, NULL, 1, 0, '2025-10-27 02:17:30', '2025-10-15 01:34:22', '2025-10-27 02:17:30'),
(101, 'hccstarosa', 'hccstarosa@gmail.com', '$2y$10$MNVW9EJXd.lOxmiy7HDv8Oxe5UdZJEP.3IBbvB9thWURNV1.bpqG2', 'Sta Rosa HCC', 'admin', 1, NULL, NULL, NULL, NULL, 1, 0, '2025-10-29 16:17:00', '2025-10-19 08:41:25', '2025-10-29 16:17:00'),
(140, 'fermin', 'ferminrichard@icloud.com', '$2y$10$6vseCCK7HOWqG/K/.eyN6erxzJx2TfKCUKLqRV6HFjh7FO/iEEy1G', 'richard fermin', 'custodian', 1, NULL, NULL, NULL, NULL, 1, 0, '2025-11-05 11:27:26', '2025-10-24 00:56:56', '2025-11-05 11:27:26'),
(143, 'superadmin', 'hccsuperadmin@gmail.com', '$2y$10$9e.MLV.u5g29s5zJz2/L9.oE/a2rVz.jC.zWz8zO.zWz8zO.zWz8z', 'IT Super Administrator', 'admin', 1, NULL, NULL, NULL, NULL, 1, 0, NULL, '2025-10-26 09:44:22', '2025-10-26 09:44:22'),
(155, 'mico.macapugay', 'mico.macapugay@icloud.com', '$2y$10$drJOPNMucFNugV1xklAEgOmdt7z4bLf.SsX5f/KmEe0RH5l3SS5F.', 'Micole Macapugay', 'office', 1, NULL, NULL, NULL, NULL, 1, 0, '2025-11-05 11:49:24', '2025-11-01 11:41:51', '2025-11-06 10:39:54'),
(159, 'mico.macapugay2004', 'mico.macapugay2004@gmail.com', '$2y$10$HiYmePZQGkVsxPyecDE/t.ZdfhsH6KoHo6/7.CGsaL28K1pUJTvda', 'Micole Macapugay', 'office', 1, 16, NULL, NULL, NULL, 1, 0, '2025-11-01 17:02:30', '2025-11-01 14:09:44', '2025-11-01 17:02:30'),
(160, 'mics', 'jemusubeley@gmail.com', '$2y$10$6.OGoRfxq4gjs.krObafO.rXESoRjNDI45aSCrrh8ram/nN3BzcNa', 'Mico ', 'custodian', 1, 16, NULL, NULL, NULL, 1, 0, '2025-11-07 17:33:56', '2025-11-06 10:42:46', '2025-11-07 17:34:17'),
(161, 'micsu', 'andrewbeley7@gmail.com', '$2y$10$kImPVPCnFSv9.lgkKfIYIejxFjjRD9FoZVQD6gz.BfMHc3g66goc2', 'Micy', 'employee', 1, 16, NULL, NULL, NULL, 1, 0, '2025-11-07 14:59:13', '2025-11-06 12:02:08', '2025-11-07 15:12:24');

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
(789, 161, 'a50577952280751aba55e58f56673a1e4004b6075dd3f0b0b30ba7557941bcc3231633ac7d3c226dadb6a539f0daefae8f2a40d1e9b287423e48ac1b93cde333', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-08 07:58:22', '2025-11-07 14:58:22'),
(790, 161, '30805f438f52a710110a2957c86fb26be178c1f2686d48edb0dec3d8cdb6d344341be05e3cf62aa4ee2e020a89d9097d74c446600b563bb300e817b97e1f733d', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-08 07:59:01', '2025-11-07 14:59:01');

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_assets_depreciation_status`
-- (See below for the actual view)
--
CREATE TABLE `view_assets_depreciation_status` (
`id` int(11)
,`asset_name` varchar(255)
,`original_value` decimal(15,2)
,`current_value` decimal(15,2)
,`depreciation_rate` decimal(5,2)
,`purchase_date` date
,`last_depreciation_date` date
,`months_since_purchase` bigint(21)
,`months_since_last_calc` bigint(21)
,`campus_name` varchar(100)
,`category_name` varchar(50)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_department_asset_utilization`
-- (See below for the actual view)
--
CREATE TABLE `view_department_asset_utilization` (
`office_id` int(11)
,`office_name` varchar(255)
,`campus_name` varchar(100)
,`total_assets` bigint(21)
,`total_value` decimal(37,2)
,`active_assets` bigint(21)
,`damaged_assets` bigint(21)
,`missing_assets` bigint(21)
,`total_borrowings` bigint(21)
,`overdue_borrowings` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_missing_assets_summary`
-- (See below for the actual view)
--
CREATE TABLE `view_missing_assets_summary` (
`report_id` int(11)
,`asset_id` int(11)
,`asset_name` varchar(255)
,`barcode` varchar(255)
,`serial_number` varchar(100)
,`asset_value` decimal(15,2)
,`last_known_location` varchar(255)
,`last_known_borrower` varchar(255)
,`reported_date` datetime
,`investigation_status` enum('reported','investigating','found','permanently_lost')
,`reported_by_name` varchar(100)
,`campus_name` varchar(100)
,`campus_id` int(11)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_overdue_borrowings`
-- (See below for the actual view)
--
CREATE TABLE `view_overdue_borrowings` (
`borrowing_id` int(11)
,`asset_id` int(11)
,`asset_name` varchar(255)
,`barcode` varchar(255)
,`borrower_name` varchar(255)
,`borrower_contact` varchar(255)
,`expected_return_date` date
,`days_overdue` int(7)
,`status` enum('active','returned','overdue','not_returned','lost')
,`overdue_notification_sent` tinyint(1)
,`recorded_by` varchar(255)
,`campus_name` varchar(100)
,`campus_id` int(11)
);

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

-- --------------------------------------------------------

--
-- Structure for view `view_assets_depreciation_status`
--
DROP TABLE IF EXISTS `view_assets_depreciation_status`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_assets_depreciation_status`  AS SELECT `a`.`id` AS `id`, `a`.`asset_name` AS `asset_name`, `a`.`original_value` AS `original_value`, `a`.`current_value` AS `current_value`, `a`.`depreciation_rate` AS `depreciation_rate`, `a`.`purchase_date` AS `purchase_date`, `a`.`last_depreciation_date` AS `last_depreciation_date`, timestampdiff(MONTH,`a`.`purchase_date`,curdate()) AS `months_since_purchase`, timestampdiff(MONTH,coalesce(`a`.`last_depreciation_date`,`a`.`purchase_date`),curdate()) AS `months_since_last_calc`, `c`.`campus_name` AS `campus_name`, `cat`.`category_name` AS `category_name` FROM ((`assets` `a` join `campuses` `c` on(`a`.`campus_id` = `c`.`id`)) join `categories` `cat` on(`a`.`category_id` = `cat`.`id`)) WHERE `a`.`depreciation_rate` > 0 AND `a`.`status` <> 'Retired' ;

-- --------------------------------------------------------

--
-- Structure for view `view_department_asset_utilization`
--
DROP TABLE IF EXISTS `view_department_asset_utilization`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_department_asset_utilization`  AS SELECT `o`.`id` AS `office_id`, `o`.`office_name` AS `office_name`, `c`.`campus_name` AS `campus_name`, count(distinct `a`.`id`) AS `total_assets`, sum(`a`.`current_value`) AS `total_value`, count(distinct case when `a`.`status` = 'Active' then `a`.`id` end) AS `active_assets`, count(distinct case when `a`.`status` = 'Damaged' then `a`.`id` end) AS `damaged_assets`, count(distinct case when `a`.`status` = 'Missing' then `a`.`id` end) AS `missing_assets`, count(distinct `ab`.`id`) AS `total_borrowings`, count(distinct case when `ab`.`status` = 'overdue' then `ab`.`id` end) AS `overdue_borrowings` FROM (((`offices` `o` join `campuses` `c` on(`o`.`campus_id` = `c`.`id`)) left join `assets` `a` on(`a`.`office_id` = `o`.`id`)) left join `asset_borrowings` `ab` on(`ab`.`asset_id` = `a`.`id`)) GROUP BY `o`.`id`, `o`.`office_name`, `c`.`campus_name` ;

-- --------------------------------------------------------

--
-- Structure for view `view_missing_assets_summary`
--
DROP TABLE IF EXISTS `view_missing_assets_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_missing_assets_summary`  AS SELECT `mar`.`id` AS `report_id`, `a`.`id` AS `asset_id`, `a`.`asset_name` AS `asset_name`, `a`.`barcode` AS `barcode`, `a`.`serial_number` AS `serial_number`, `a`.`value` AS `asset_value`, `mar`.`last_known_location` AS `last_known_location`, `mar`.`last_known_borrower` AS `last_known_borrower`, `mar`.`reported_date` AS `reported_date`, `mar`.`status` AS `investigation_status`, `u`.`full_name` AS `reported_by_name`, `c`.`campus_name` AS `campus_name`, `mar`.`campus_id` AS `campus_id` FROM (((`missing_assets_reports` `mar` join `assets` `a` on(`mar`.`asset_id` = `a`.`id`)) join `users` `u` on(`mar`.`reported_by` = `u`.`id`)) join `campuses` `c` on(`mar`.`campus_id` = `c`.`id`)) WHERE `mar`.`status` in ('reported','investigating') ;

-- --------------------------------------------------------

--
-- Structure for view `view_overdue_borrowings`
--
DROP TABLE IF EXISTS `view_overdue_borrowings`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_overdue_borrowings`  AS SELECT `ab`.`id` AS `borrowing_id`, `ab`.`asset_id` AS `asset_id`, `a`.`asset_name` AS `asset_name`, `a`.`barcode` AS `barcode`, `ab`.`borrower_name` AS `borrower_name`, `ab`.`borrower_contact` AS `borrower_contact`, `ab`.`expected_return_date` AS `expected_return_date`, to_days(curdate()) - to_days(`ab`.`expected_return_date`) AS `days_overdue`, `ab`.`status` AS `status`, `ab`.`overdue_notification_sent` AS `overdue_notification_sent`, `ab`.`recorded_by` AS `recorded_by`, `c`.`campus_name` AS `campus_name`, `a`.`campus_id` AS `campus_id` FROM ((`asset_borrowings` `ab` join `assets` `a` on(`ab`.`asset_id` = `a`.`id`)) join `campuses` `c` on(`a`.`campus_id` = `c`.`id`)) WHERE `ab`.`status` = 'active' AND `ab`.`expected_return_date` is not null AND `ab`.`expected_return_date` < curdate() ;

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
  ADD KEY `idx_assets_room_id` (`room_id`),
  ADD KEY `idx_brand_id` (`brand_id`),
  ADD KEY `idx_assets_assigned_to_id` (`assigned_to_id`),
  ADD KEY `fk_assets_office_id` (`office_id`);

--
-- Indexes for table `asset_assignments`
--
ALTER TABLE `asset_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asset_id` (`asset_id`),
  ADD KEY `idx_assigned_to_id` (`assigned_to_id`);

--
-- Indexes for table `asset_borrowings`
--
ALTER TABLE `asset_borrowings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_asset_id` (`asset_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_borrower_type` (`borrower_type`),
  ADD KEY `idx_borrowed_date` (`borrowed_date`),
  ADD KEY `idx_expected_return_date` (`expected_return_date`),
  ADD KEY `idx_return_status` (`return_status`),
  ADD KEY `idx_overdue_notification` (`overdue_notification_sent`);

--
-- Indexes for table `asset_maintenance`
--
ALTER TABLE `asset_maintenance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asset_id` (`asset_id`),
  ADD KEY `idx_maintenance_type` (`maintenance_type`),
  ADD KEY `idx_next_maintenance` (`next_maintenance_date`);

--
-- Indexes for table `asset_movement_logs`
--
ALTER TABLE `asset_movement_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_asset_id` (`asset_id`),
  ADD KEY `idx_movement_type` (`movement_type`),
  ADD KEY `idx_moved_date` (`moved_date`),
  ADD KEY `idx_campus_id` (`campus_id`),
  ADD KEY `fk_movement_moved_by` (`moved_by`),
  ADD KEY `fk_movement_verified_by` (`verified_by`),
  ADD KEY `fk_movement_from_room` (`from_room_id`),
  ADD KEY `fk_movement_to_room` (`to_room_id`),
  ADD KEY `fk_movement_from_office` (`from_office_id`),
  ADD KEY `fk_movement_to_office` (`to_office_id`);

--
-- Indexes for table `asset_names`
--
ALTER TABLE `asset_names`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `asset_name_brands`
--
ALTER TABLE `asset_name_brands`
  ADD PRIMARY KEY (`asset_name_id`,`brand_id`),
  ADD KEY `fk_anb_brand_id` (`brand_id`);

--
-- Indexes for table `asset_receipts`
--
ALTER TABLE `asset_receipts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `receipt_code` (`receipt_code`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `asset_id` (`asset_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `asset_requests`
--
ALTER TABLE `asset_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_code` (`unique_code`),
  ADD UNIQUE KEY `receipt_code` (`receipt_code`),
  ADD KEY `asset_id` (`asset_id`),
  ADD KEY `requester_id` (`requester_id`),
  ADD KEY `campus_id` (`campus_id`),
  ADD KEY `fk_requests_approved_by` (`approved_by`),
  ADD KEY `fk_requests_released_by` (`released_by`),
  ADD KEY `idx_receipt_code` (`receipt_code`),
  ADD KEY `idx_custodian_reviewed` (`custodian_reviewed_by`),
  ADD KEY `idx_dept_approved` (`department_approved_by`),
  ADD KEY `idx_final_approved` (`final_approved_by`);

--
-- Indexes for table `asset_scans`
--
ALTER TABLE `asset_scans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asset_id` (`asset_id`);

--
-- Indexes for table `borrowing_chain`
--
ALTER TABLE `borrowing_chain`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_borrowing_id` (`borrowing_id`),
  ADD KEY `idx_asset_id` (`asset_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_transfer_date` (`transfer_date`),
  ADD KEY `fk_borrowing_chain_recorder` (`recorded_by`);

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

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
-- Indexes for table `department_approvers`
--
ALTER TABLE `department_approvers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_office_approver` (`office_id`,`approver_user_id`),
  ADD KEY `idx_office_id` (`office_id`),
  ADD KEY `idx_approver_user_id` (`approver_user_id`),
  ADD KEY `idx_campus_id` (`campus_id`);

--
-- Indexes for table `email_notifications`
--
ALTER TABLE `email_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_created_at` (`created_at`);

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
-- Indexes for table `inventory_tags`
--
ALTER TABLE `inventory_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tag_number` (`tag_number`),
  ADD KEY `asset_id` (`asset_id`),
  ADD KEY `office_id` (`office_id`),
  ADD KEY `fk_inventory_tags_assigned_by` (`assigned_by_custodian_id`),
  ADD KEY `fk_inventory_tags_verified_by` (`verified_by_user_id`);

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
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_username_ip` (`username`,`ip_address`),
  ADD KEY `idx_attempted_at` (`attempted_at`);

--
-- Indexes for table `missing_assets_reports`
--
ALTER TABLE `missing_assets_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_asset_id` (`asset_id`),
  ADD KEY `idx_reported_by` (`reported_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_reported_date` (`reported_date`),
  ADD KEY `idx_campus_id` (`campus_id`),
  ADD KEY `fk_missing_resolved_by` (`resolved_by`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_priority` (`priority`);

--
-- Indexes for table `offices`
--
ALTER TABLE `offices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_office_per_campus` (`office_name`,`campus_id`),
  ADD KEY `campus_id` (`campus_id`);

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
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `campus_id` (`campus_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_room_building` (`room_name`,`building_id`),
  ADD KEY `idx_rooms_building_id` (`building_id`);

--
-- Indexes for table `sms_notifications`
--
ALTER TABLE `sms_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_setting_key` (`setting_key`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `fk_settings_updated_by` (`updated_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `campus_id` (`campus_id`),
  ADD KEY `fk_users_office_id` (`office_id`),
  ADD KEY `idx_users_department` (`department_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2082;

--
-- AUTO_INCREMENT for table `assets`
--
ALTER TABLE `assets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=209;

--
-- AUTO_INCREMENT for table `asset_assignments`
--
ALTER TABLE `asset_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=538;

--
-- AUTO_INCREMENT for table `asset_borrowings`
--
ALTER TABLE `asset_borrowings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `asset_maintenance`
--
ALTER TABLE `asset_maintenance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `asset_movement_logs`
--
ALTER TABLE `asset_movement_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `asset_names`
--
ALTER TABLE `asset_names`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `asset_receipts`
--
ALTER TABLE `asset_receipts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `asset_requests`
--
ALTER TABLE `asset_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=146;

--
-- AUTO_INCREMENT for table `asset_scans`
--
ALTER TABLE `asset_scans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=207;

--
-- AUTO_INCREMENT for table `borrowing_chain`
--
ALTER TABLE `borrowing_chain`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `buildings`
--
ALTER TABLE `buildings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

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
-- AUTO_INCREMENT for table `department_approvers`
--
ALTER TABLE `department_approvers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_notifications`
--
ALTER TABLE `email_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `it_support_users`
--
ALTER TABLE `it_support_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=955;

--
-- AUTO_INCREMENT for table `missing_assets_reports`
--
ALTER TABLE `missing_assets_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `offices`
--
ALTER TABLE `offices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sms_notifications`
--
ALTER TABLE `sms_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=162;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=798;

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
  ADD CONSTRAINT `fk_assets_assigned_to_user` FOREIGN KEY (`assigned_to_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_assets_brand_id` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_assets_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_assets_office_id` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE SET NULL,
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
-- Constraints for table `asset_movement_logs`
--
ALTER TABLE `asset_movement_logs`
  ADD CONSTRAINT `fk_movement_asset` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_movement_campus` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_movement_from_office` FOREIGN KEY (`from_office_id`) REFERENCES `offices` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_movement_from_room` FOREIGN KEY (`from_room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_movement_moved_by` FOREIGN KEY (`moved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_movement_to_office` FOREIGN KEY (`to_office_id`) REFERENCES `offices` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_movement_to_room` FOREIGN KEY (`to_room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_movement_verified_by` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `asset_name_brands`
--
ALTER TABLE `asset_name_brands`
  ADD CONSTRAINT `fk_anb_asset_name_id` FOREIGN KEY (`asset_name_id`) REFERENCES `asset_names` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_anb_brand_id` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `asset_receipts`
--
ALTER TABLE `asset_receipts`
  ADD CONSTRAINT `fk_receipt_asset` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_receipt_request` FOREIGN KEY (`request_id`) REFERENCES `asset_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_receipt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `asset_requests`
--
ALTER TABLE `asset_requests`
  ADD CONSTRAINT `asset_requests_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `asset_requests_ibfk_2` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `asset_requests_ibfk_3` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_asset_requests_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_asset_requests_released_by` FOREIGN KEY (`released_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_request_custodian_reviewer` FOREIGN KEY (`custodian_reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_request_dept_approver` FOREIGN KEY (`department_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_request_final_approver` FOREIGN KEY (`final_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_requests_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_requests_released_by` FOREIGN KEY (`released_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `asset_scans`
--
ALTER TABLE `asset_scans`
  ADD CONSTRAINT `asset_scans_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `borrowing_chain`
--
ALTER TABLE `borrowing_chain`
  ADD CONSTRAINT `fk_borrowing_chain_asset` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_borrowing_chain_borrowing` FOREIGN KEY (`borrowing_id`) REFERENCES `asset_borrowings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_borrowing_chain_recorder` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `buildings`
--
ALTER TABLE `buildings`
  ADD CONSTRAINT `buildings_ibfk_1` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `department_approvers`
--
ALTER TABLE `department_approvers`
  ADD CONSTRAINT `fk_dept_approver_campus` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dept_approver_office` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dept_approver_user` FOREIGN KEY (`approver_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `email_notifications`
--
ALTER TABLE `email_notifications`
  ADD CONSTRAINT `fk_email_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

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
  ADD CONSTRAINT `fk_inventory_tags_assigned_by` FOREIGN KEY (`assigned_by_custodian_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_inventory_tags_verified_by` FOREIGN KEY (`verified_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `inventory_tags_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_tags_ibfk_2` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE SET NULL;

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
-- Constraints for table `missing_assets_reports`
--
ALTER TABLE `missing_assets_reports`
  ADD CONSTRAINT `fk_missing_asset` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_missing_campus` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_missing_reported_by` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_missing_resolved_by` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `offices`
--
ALTER TABLE `offices`
  ADD CONSTRAINT `offices_ibfk_1` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `fk_rooms_building_id` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sms_notifications`
--
ALTER TABLE `sms_notifications`
  ADD CONSTRAINT `fk_sms_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD CONSTRAINT `fk_settings_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_office_id` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE SET NULL,
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
