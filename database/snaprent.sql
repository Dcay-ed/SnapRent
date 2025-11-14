-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 14, 2025 at 12:54 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `snaprent`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `id` bigint UNSIGNED NOT NULL,
  `owner_id` bigint UNSIGNED DEFAULT NULL,
  `staff_id` bigint UNSIGNED DEFAULT NULL,
  `customer_id` bigint UNSIGNED DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(25) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('OWNER','STAFF','CUSTOMER') NOT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`id`, `owner_id`, `staff_id`, `customer_id`, `username`, `email`, `phone`, `password`, `role`, `is_verified`, `is_active`, `created_at`) VALUES
(1, 1, NULL, NULL, 'owner_demo', 'owner_demo@snaprent.com', NULL, 'owner123', 'OWNER', 1, 1, '2025-11-05 01:36:24'),
(2, NULL, 2, NULL, 'staff_demo', 'staff_demo@snaprent.com', NULL, 'staff123', 'STAFF', 1, 1, '2025-11-05 01:36:24'),
(3, NULL, NULL, 3, 'cust_demo', 'cust_demo@snaprent.com', NULL, 'customer123', 'CUSTOMER', 1, 1, '2025-11-05 01:36:24'),
(4, NULL, NULL, NULL, 'indra', 'indrastaff@gmail.com', '0812312415', 'indra123', 'CUSTOMER', 0, 1, '2025-11-12 17:19:21');

-- --------------------------------------------------------

--
-- Table structure for table `cameras`
--

CREATE TABLE `cameras` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `brand` varchar(80) NOT NULL,
  `type` varchar(80) DEFAULT NULL,
  `problem` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `daily_price` decimal(12,2) NOT NULL,
  `status` enum('available','unavailable','maintenance') NOT NULL DEFAULT 'available',
  `condition_note` text,
  `owner_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `cameras`
--

INSERT INTO `cameras` (`id`, `name`, `brand`, `type`, `problem`, `daily_price`, `status`, `condition_note`, `owner_id`, `created_at`, `updated_at`) VALUES
(41, 'A461', 'Panasonic', 'Analog', 'Badan Kamera: Kotak yang kedap cahaya untuk melindungi bagian internalnya.\r\nLensa: Kumpulan lensa cembung yang memfokuskan cahaya dari objek ke sensor atau film.\r\nSensor/Film: Permukaan yang peka cahaya untuk merekam gambar, baik berupa sensor elektronik pada kamera digital atau film pada kamera analog.\r\nDiaphragma/Bukaan: Celah yang dapat diatur ukurannya untuk mengontrol jumlah cahaya yang masuk.\r\nShutter: Mekanisme yang membuka dan menutup untuk mengatur durasi cahaya yang masuk ke sensor atau film.', 215000.00, 'available', NULL, NULL, '2025-11-13 17:52:11', '2025-11-13 18:36:41'),
(42, 'A471', 'Sony', 'Analog', 'Badan Kamera: Kotak yang kedap cahaya untuk melindungi bagian internalnya.\r\nLensa: Kumpulan lensa cembung yang memfokuskan cahaya dari objek ke sensor atau film.\r\nSensor/Film: Permukaan yang peka cahaya untuk merekam gambar, baik berupa sensor elektronik pada kamera digital atau film pada kamera analog.\r\nDiaphragma/Bukaan: Celah yang dapat diatur ukurannya untuk mengontrol jumlah cahaya yang masuk.\r\nShutter: Mekanisme yang membuka dan menutup untuk mengatur durasi cahaya yang masuk ke sensor atau film.', 159000.00, 'available', NULL, NULL, '2025-11-13 17:52:54', '2025-11-13 18:39:12'),
(43, 'A483', 'Nikon', 'Analog', 'Badan Kamera: Kotak yang kedap cahaya untuk melindungi bagian internalnya.\r\nLensa: Kumpulan lensa cembung yang memfokuskan cahaya dari objek ke sensor atau film.\r\nSensor/Film: Permukaan yang peka cahaya untuk merekam gambar, baik berupa sensor elektronik pada kamera digital atau film pada kamera analog.\r\nDiaphragma/Bukaan: Celah yang dapat diatur ukurannya untuk mengontrol jumlah cahaya yang masuk.\r\nShutter: Mekanisme yang membuka dan menutup untuk mengatur durasi cahaya yang masuk ke sensor atau film.', 280000.00, 'available', NULL, NULL, '2025-11-13 17:53:34', '2025-11-13 18:39:07'),
(44, 'A500', 'Canon', 'Analog', 'Badan Kamera: Kotak yang kedap cahaya untuk melindungi bagian internalnya. Lensa: Kumpulan lensa cembung yang memfokuskan cahaya dari objek ke sensor atau film. Sensor/Film: Permukaan yang peka cahaya untuk merekam gambar, baik berupa sensor elektronik pada kamera digital atau film pada kamera analog. Diaphragma/Bukaan: Celah yang dapat diatur ukurannya untuk mengontrol jumlah cahaya yang masuk. Shutter: Mekanisme yang membuka dan menutup untuk mengatur durasi cahaya yang masuk ke sensor atau film.', 75000.00, 'available', 'Kondisi sangat baik', 1, '2025-11-13 18:02:33', '2025-11-13 18:36:59'),
(45, 'A501', 'Nikon', 'Analog', 'Badan Kamera: Kotak yang kedap cahaya untuk melindungi bagian internalnya. Lensa: Kumpulan lensa cembung yang memfokuskan cahaya dari objek ke sensor atau film. Sensor/Film: Permukaan yang peka cahaya untuk merekam gambar, baik berupa sensor elektronik pada kamera digital atau film pada kamera analog. Diaphragma/Bukaan: Celah yang dapat diatur ukurannya untuk mengontrol jumlah cahaya yang masuk. Shutter: Mekanisme yang membuka dan menutup untuk mengatur durasi cahaya yang masuk ke sensor atau film.', 80000.00, 'available', 'Kondisi sangat baik', 1, '2025-11-13 18:02:33', '2025-11-13 18:37:09'),
(46, 'A502', 'Olympus', 'Analog', 'Badan Kamera: Kotak yang kedap cahaya untuk melindungi bagian internalnya. Lensa: Kumpulan lensa cembung yang memfokuskan cahaya dari objek ke sensor atau film. Sensor/Film: Permukaan yang peka cahaya untuk merekam gambar, baik berupa sensor elektronik pada kamera digital atau film pada kamera analog. Diaphragma/Bukaan: Celah yang dapat diatur ukurannya untuk mengontrol jumlah cahaya yang masuk. Shutter: Mekanisme yang membuka dan menutup untuk mengatur durasi cahaya yang masuk ke sensor atau film.', 70000.00, 'available', 'Kondisi baik', 1, '2025-11-13 18:02:33', '2025-11-13 18:37:13'),
(47, 'A503', 'Leica', 'Analog', 'Badan Kamera: Kotak yang kedap cahaya untuk melindungi bagian internalnya. Lensa: Kumpulan lensa cembung yang memfokuskan cahaya dari objek ke sensor atau film. Sensor/Film: Permukaan yang peka cahaya untuk merekam gambar, baik berupa sensor elektronik pada kamera digital atau film pada kamera analog. Diaphragma/Bukaan: Celah yang dapat diatur ukurannya untuk mengontrol jumlah cahaya yang masuk. Shutter: Mekanisme yang membuka dan menutup untuk mengatur durasi cahaya yang masuk ke sensor atau film.', 85000.00, 'available', 'Masih berfungsi dengan baik', 1, '2025-11-13 18:02:33', '2025-11-13 18:37:16'),
(48, 'A504', 'Pentax', 'Analog', 'Badan Kamera: Kotak yang kedap cahaya untuk melindungi bagian internalnya. Lensa: Kumpulan lensa cembung yang memfokuskan cahaya dari objek ke sensor atau film. Sensor/Film: Permukaan yang peka cahaya untuk merekam gambar, baik berupa sensor elektronik pada kamera digital atau film pada kamera analog. Diaphragma/Bukaan: Celah yang dapat diatur ukurannya untuk mengontrol jumlah cahaya yang masuk. Shutter: Mekanisme yang membuka dan menutup untuk mengatur durasi cahaya yang masuk ke sensor atau film.', 78000.00, 'available', 'Kondisi sangat baik', 1, '2025-11-13 18:02:33', '2025-11-13 18:37:21'),
(49, 'DC500', 'Canon', 'Digicam', 'Badan Kamera: Kotak yang kedap cahaya untuk melindungi bagian internalnya. Lensa: ...', 95000.00, 'available', 'Kondisi sangat baik', 1, '2025-11-13 18:02:33', '2025-11-13 18:37:28'),
(50, 'DC501', 'Canon', 'Digicam', 'Badan Kamera: Kotak yang kedap cahaya untuk melindungi bagian internalnya. Lensa: ...', 90000.00, 'available', 'Kondisi baik', 1, '2025-11-13 18:02:33', '2025-11-13 18:37:35'),
(51, 'DC502', 'Canon', 'Digicam', 'Badan Kamera: Kotak yang kedap cahaya untuk melindungi bagian internalnya. Lensa: ...', 97000.00, 'available', 'Kondisi sangat baik', 1, '2025-11-13 18:02:33', '2025-11-13 18:37:40'),
(52, 'DC503', 'Nikon', 'Digicam', 'Badan Kamera: Kotak yang kedap cahaya untuk melindungi bagian internalnya. Lensa: ...', 89000.00, 'available', 'Lensa jernih', 1, '2025-11-13 18:02:33', '2025-11-13 18:37:44'),
(53, 'DC504', 'Sony', 'Digicam', 'Badan Kamera: Kotak yang kedap cahaya untuk melindungi bagian internalnya. Lensa: ...', 88000.00, 'available', 'Sensor normal', 1, '2025-11-13 18:02:33', '2025-11-13 18:37:49'),
(54, 'DC505', 'Lumix', 'Digicam', 'Badan Kamera: Kotak yang kedap cahaya untuk melindungi bagian internalnya. Lensa: ...', 94000.00, 'available', 'Kondisi prima', 1, '2025-11-13 18:02:33', '2025-11-13 18:37:54'),
(55, 'DC506', 'Sony', 'Digicam', 'Badan Kamera: Kotak yang kedap cahaya untuk melindungi bagian internalnya. Lensa: ...', 96000.00, 'available', 'Masih bagus', 1, '2025-11-13 18:02:33', '2025-11-13 18:38:13'),
(56, 'DC507', 'Nikon', 'Digicam', 'Badan Kamera: Kotak yang kedap cahaya untuk melindungi bagian internalnya. Lensa: ...', 187000.00, 'available', 'Baik digunakan', 1, '2025-11-13 18:02:33', '2025-11-13 18:38:07'),
(57, 'DC508', 'FujiFilm', 'Digicam', 'Badan Kamera: Kotak yang kedap cahaya untuk melindungi bagian internalnya. Lensa: ...', 191000.00, 'available', 'Kondisi baik', 1, '2025-11-13 18:02:33', '2025-11-13 18:38:20'),
(58, 'DC509', 'Nikon', 'Digicam', 'Badan Kamera: Kotak yang kedap cahaya untuk melindungi bagian internalnya. Lensa: ...', 99000.00, 'available', 'Kondisi prima', 1, '2025-11-13 18:02:33', '2025-11-13 18:38:28'),
(59, 'D500', 'Canon', 'DSLR', 'Badan Kamera: Kotak yang kedap cahaya ...', 120000.00, 'available', 'Kondisi prima, shutter count rendah', 1, '2025-11-13 18:02:33', '2025-11-13 18:35:12'),
(60, 'D501', 'canon', 'DSLR', 'Badan Kamera: Kotak yang kedap cahaya ...', 130000.00, 'available', 'Kondisi sangat baik', 1, '2025-11-13 18:02:33', '2025-11-13 18:38:40'),
(61, 'D502', 'canon', 'DSLR', 'Badan Kamera: Kotak yang kedap cahaya ...', 125000.00, 'available', 'Kondisi baik', 1, '2025-11-13 18:02:33', '2025-11-13 18:38:46'),
(62, 'D503', 'Canon', 'DSLR', 'Badan Kamera: Kotak yang kedap cahaya ...', 128000.00, 'available', 'Lensa normal', 1, '2025-11-13 18:02:33', '2025-11-13 18:38:51'),
(63, 'D504', 'FujiFilm', 'DSLR', 'Badan Kamera: Kotak yang kedap cahaya ...', 118000.00, 'available', 'Baik digunakan', 1, '2025-11-13 18:02:33', '2025-11-13 18:38:57'),
(64, 'D505', 'Nikon', 'DSLR', 'Badan Kamera: Kotak yang kedap cahaya ...', 127000.00, 'available', 'Sensor bersih', 1, '2025-11-13 18:02:33', '2025-11-13 18:35:07'),
(65, 'D506', 'Sony', 'DSLR', 'Badan Kamera: Kotak yang kedap cahaya ...', 121000.00, 'available', 'Kondisi bagus', 1, '2025-11-13 18:02:33', '2025-11-13 18:35:47'),
(66, 'D507', 'Sony', 'DSLR', 'Badan Kamera: Kotak yang kedap cahaya ...', 124000.00, 'available', 'Kondisi prima', 1, '2025-11-13 18:02:33', '2025-11-13 18:36:04'),
(67, 'D508', 'Canon', 'DSLR', 'Badan Kamera: Kotak yang kedap cahaya ...', 126000.00, 'available', 'Kondisi baik', 1, '2025-11-13 18:02:33', '2025-11-13 18:36:26'),
(68, 'M500', 'Sony', 'Mirrorless', 'Badan Kamera: Kotak yang kedap cahaya ...', 150000.00, 'available', 'Kondisi sangat baik', 1, '2025-11-13 18:02:33', '2025-11-13 18:40:11'),
(69, 'M501', 'Sony', 'Mirrorless', 'Badan Kamera: Kotak yang kedap cahaya ...', 155000.00, 'available', 'Kondisi prima', 1, '2025-11-13 18:02:33', '2025-11-13 18:40:37'),
(70, 'M502', 'canon', 'Mirrorless', 'Badan Kamera: Kotak yang kedap cahaya ...', 148000.00, 'available', 'Lensa bersih', 1, '2025-11-13 18:02:33', '2025-11-13 18:40:56'),
(71, 'M503', 'FujiFilm', 'Mirrorless', 'Badan Kamera: Kotak yang kedap cahaya ...', 152000.00, 'available', 'Sensor jernih', 1, '2025-11-13 18:02:33', '2025-11-13 18:41:15'),
(72, 'M504', 'Lumix', 'Mirrorless', 'Badan Kamera: Kotak yang kedap cahaya ...', 157000.00, 'available', 'Kondisi baik', 1, '2025-11-13 18:02:33', '2025-11-13 18:41:36'),
(73, 'M505', 'Lumix', 'Mirrorless', 'Badan Kamera: Kotak yang kedap cahaya ...', 149000.00, 'available', 'Baik digunakan', 1, '2025-11-13 18:02:33', '2025-11-13 18:41:54'),
(74, 'M506', 'Nikon', 'Mirrorless', 'Badan Kamera: Kotak yang kedap cahaya ...', 162000.00, 'available', 'Kondisi prima', 1, '2025-11-13 18:02:33', '2025-11-13 18:36:46'),
(75, 'M507', 'FujiFilm', 'Mirrorless', 'Badan Kamera: Kotak yang kedap cahaya ...', 158000.00, 'available', 'Kondisi sangat baik', 1, '2025-11-13 18:02:33', '2025-11-13 18:36:50'),
(76, 'M508', 'Nikon', 'Mirrorless', 'Badan Kamera: Kotak yang kedap cahaya ...', 160000.00, 'available', 'Sensor normal', 1, '2025-11-13 18:02:33', '2025-11-13 18:42:01');

-- --------------------------------------------------------

--
-- Table structure for table `camera_images`
--

CREATE TABLE `camera_images` (
  `id` bigint UNSIGNED NOT NULL,
  `camera_id` bigint UNSIGNED NOT NULL,
  `filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `camera_images`
--

INSERT INTO `camera_images` (`id`, `camera_id`, `filename`, `created_at`) VALUES
(78, 41, 'cam_6915b85bf00c80.91755679.png', '2025-11-13 10:52:12'),
(79, 42, 'cam_6915b886aef809.08099013.png', '2025-11-13 10:52:55'),
(80, 43, 'cam_6915b8aeb62ee0.12066633.png', '2025-11-13 10:53:35'),
(81, 44, 'cam_6915bf5cd12c62.94697843.png', '2025-11-13 11:22:05'),
(82, 45, 'cam_6915bfa0a33322.56848155.png', '2025-11-13 11:23:13'),
(83, 46, 'cam_6915bfbd10ad38.20220733.png', '2025-11-13 11:23:41'),
(84, 47, 'cam_6915bfcd3653a6.52995635.png', '2025-11-13 11:23:57'),
(86, 74, 'cam_6915c02e903bf9.11730040.png', '2025-11-13 11:25:35'),
(87, 75, 'cam_6915c0595f4055.80628033.png', '2025-11-13 11:26:17'),
(88, 48, 'cam_6915c071e07784.72846213.png', '2025-11-13 11:26:42'),
(89, 52, 'cam_6915c0ac5869e3.69229079.png', '2025-11-13 11:27:40'),
(90, 53, 'cam_6915c0c2f250d4.85913307.png', '2025-11-13 11:28:03'),
(91, 76, 'cam_6915c0e52fabd8.32917275.png', '2025-11-13 11:28:37'),
(92, 49, 'cam_6915c11a63a386.30606055.png', '2025-11-13 11:29:30'),
(93, 50, 'cam_6915c13362dcc8.29523815.png', '2025-11-13 11:29:55'),
(94, 51, 'cam_6915c14ecbd5b2.22074417.png', '2025-11-13 11:30:23'),
(95, 54, 'cam_6915c179206ef0.70367903.png', '2025-11-13 11:31:05'),
(96, 55, 'cam_6915c18f7ad875.58499392.png', '2025-11-13 11:31:27'),
(97, 56, 'cam_6915c1a8545eb9.20128515.png', '2025-11-13 11:31:52'),
(98, 57, 'cam_6915c1ba697b43.65849672.png', '2025-11-13 11:32:10'),
(99, 58, 'cam_6915c1deb3ad30.14402617.png', '2025-11-13 11:32:47'),
(100, 60, 'cam_6915c20cb9e444.67155595.png', '2025-11-13 11:33:33'),
(101, 61, 'cam_6915c21bd4a0d3.10039652.png', '2025-11-13 11:33:48'),
(102, 62, 'cam_6915c22ceb2444.11661291.png', '2025-11-13 11:34:05'),
(103, 63, 'cam_6915c242407b55.09512753.png', '2025-11-13 11:34:26'),
(104, 64, 'cam_6915c26b6ae354.63598909.png', '2025-11-13 11:35:07'),
(105, 65, 'cam_6915c293739532.35036513.png', '2025-11-13 11:35:47'),
(106, 66, 'cam_6915c2a492be76.95751431.png', '2025-11-13 11:36:05'),
(107, 67, 'cam_6915c2ba30dcc0.93971750.png', '2025-11-13 11:36:26'),
(108, 68, 'cam_6915c39b7bc8c9.43591323.png', '2025-11-13 11:40:11'),
(109, 69, 'cam_6915c3b56f1e47.34560152.png', '2025-11-13 11:40:37'),
(110, 70, 'cam_6915c3c8eb8694.73140976.png', '2025-11-13 11:40:57'),
(111, 71, 'cam_6915c3db9aaec0.06537057.png', '2025-11-13 11:41:16'),
(112, 72, 'cam_6915c3f04c9af3.65158339.png', '2025-11-13 11:41:36'),
(113, 73, 'cam_6915c4025996c6.85056768.png', '2025-11-13 11:41:54'),
(115, 59, 'cam_691633f88c5324.50359557.png', '2025-11-13 19:39:36');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` bigint UNSIGNED NOT NULL,
  `customer_code` varchar(10) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `address` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `customer_code`, `full_name`, `address`) VALUES
(3, 'C1', 'Customer Demo', 'Jl. Sudirman No.45, Bandung');

--
-- Triggers `customers`
--
DELIMITER $$
CREATE TRIGGER `trg_customers_code` BEFORE INSERT ON `customers` FOR EACH ROW BEGIN
  DECLARE next_id INT;
  SET next_id = IFNULL((SELECT MAX(customer_id) FROM customers), 0) + 1;
  SET NEW.customer_code = CONCAT('C', next_id);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `owners`
--

CREATE TABLE `owners` (
  `owner_id` bigint UNSIGNED NOT NULL,
  `owner_code` varchar(10) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `address` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `owners`
--

INSERT INTO `owners` (`owner_id`, `owner_code`, `full_name`, `address`) VALUES
(1, 'O1', 'Owner Demo', 'Jl. Merdeka No.10, Jakarta');

--
-- Triggers `owners`
--
DELIMITER $$
CREATE TRIGGER `trg_owners_code` BEFORE INSERT ON `owners` FOR EACH ROW BEGIN
  DECLARE next_id INT;
  SET next_id = IFNULL((SELECT MAX(owner_id) FROM owners), 0) + 1;
  SET NEW.owner_code = CONCAT('O', next_id);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` bigint UNSIGNED NOT NULL,
  `rental_id` bigint UNSIGNED NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `method` enum('cash','e-banking') NOT NULL,
  `status` enum('pending','verified','failed') NOT NULL DEFAULT 'pending',
  `paid_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rentals`
--

CREATE TABLE `rentals` (
  `id` bigint UNSIGNED NOT NULL,
  `customer_id` bigint UNSIGNED NOT NULL,
  `staff_id` bigint UNSIGNED DEFAULT NULL,
  `camera_id` bigint UNSIGNED NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `total_price` decimal(12,2) NOT NULL,
  `status` enum('pending','confirmed','rented','returned','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `returned_at` datetime DEFAULT NULL,
  `late_fee` decimal(12,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` bigint UNSIGNED NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `total_revenue` decimal(14,2) NOT NULL DEFAULT '0.00',
  `rentals_count` int UNSIGNED NOT NULL DEFAULT '0',
  `product_performance` json DEFAULT NULL,
  `stock_snapshot` json DEFAULT NULL,
  `created_by` bigint UNSIGNED NOT NULL,
  `approved_by` bigint UNSIGNED DEFAULT NULL,
  `generated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` bigint UNSIGNED NOT NULL,
  `camera_id` bigint UNSIGNED NOT NULL,
  `customer_id` bigint UNSIGNED NOT NULL,
  `rating` tinyint UNSIGNED NOT NULL,
  `comment` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staffs`
--

CREATE TABLE `staffs` (
  `staff_id` bigint UNSIGNED NOT NULL,
  `staff_code` varchar(10) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `staffs`
--

INSERT INTO `staffs` (`staff_id`, `staff_code`, `full_name`) VALUES
(2, 'S1', 'Staff Demo');

--
-- Triggers `staffs`
--
DELIMITER $$
CREATE TRIGGER `trg_staffs_code` BEFORE INSERT ON `staffs` FOR EACH ROW BEGIN
  DECLARE next_id INT;
  SET next_id = IFNULL((SELECT MAX(staff_id) FROM staffs), 0) + 1;
  SET NEW.staff_code = CONCAT('S', next_id);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_camera_rating`
-- (See below for the actual view)
--
CREATE TABLE `v_camera_rating` (
`avg_rating` decimal(7,4)
,`brand` varchar(80)
,`camera_id` bigint unsigned
,`name` varchar(120)
,`review_count` bigint
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_rental_payment_status`
-- (See below for the actual view)
--
CREATE TABLE `v_rental_payment_status` (
`last_payment_status` enum('pending','verified','failed')
,`paid_amount` decimal(34,2)
,`rental_id` bigint unsigned
,`rental_status` enum('pending','confirmed','rented','returned','cancelled')
);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_accounts_username` (`username`),
  ADD UNIQUE KEY `uk_accounts_email` (`email`),
  ADD KEY `idx_accounts_role` (`role`),
  ADD KEY `idx_accounts_owner_id` (`owner_id`),
  ADD KEY `idx_accounts_staff_id` (`staff_id`),
  ADD KEY `idx_accounts_customer_id` (`customer_id`);

--
-- Indexes for table `cameras`
--
ALTER TABLE `cameras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cameras_status` (`status`),
  ADD KEY `idx_cameras_brand` (`brand`),
  ADD KEY `fk_cameras_owner` (`owner_id`);

--
-- Indexes for table `camera_images`
--
ALTER TABLE `camera_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_camera` (`camera_id`,`id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `customer_code` (`customer_code`);

--
-- Indexes for table `owners`
--
ALTER TABLE `owners`
  ADD PRIMARY KEY (`owner_id`),
  ADD UNIQUE KEY `owner_code` (`owner_code`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payments_rental` (`rental_id`);

--
-- Indexes for table `rentals`
--
ALTER TABLE `rentals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rentals_camera_dates` (`camera_id`,`start_date`,`end_date`),
  ADD KEY `idx_rentals_customer` (`customer_id`),
  ADD KEY `fk_rentals_staff` (`staff_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_reports_created_by` (`created_by`),
  ADD KEY `fk_reports_approved_by` (`approved_by`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reviews_camera` (`camera_id`),
  ADD KEY `idx_reviews_customer` (`customer_id`);

--
-- Indexes for table `staffs`
--
ALTER TABLE `staffs`
  ADD PRIMARY KEY (`staff_id`),
  ADD UNIQUE KEY `staff_code` (`staff_code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `cameras`
--
ALTER TABLE `cameras`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `camera_images`
--
ALTER TABLE `camera_images`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=116;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rentals`
--
ALTER TABLE `rentals`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------

--
-- Structure for view `v_camera_rating`
--
DROP TABLE IF EXISTS `v_camera_rating`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_camera_rating`  AS SELECT `c`.`id` AS `camera_id`, `c`.`name` AS `name`, `c`.`brand` AS `brand`, avg(`r`.`rating`) AS `avg_rating`, count(`r`.`id`) AS `review_count` FROM (`cameras` `c` left join `reviews` `r` on((`r`.`camera_id` = `c`.`id`))) GROUP BY `c`.`id`, `c`.`name`, `c`.`brand` ;

-- --------------------------------------------------------

--
-- Structure for view `v_rental_payment_status`
--
DROP TABLE IF EXISTS `v_rental_payment_status`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_rental_payment_status`  AS SELECT `rn`.`id` AS `rental_id`, `rn`.`status` AS `rental_status`, ifnull(sum(`p`.`amount`),0) AS `paid_amount`, max(`p`.`status`) AS `last_payment_status` FROM (`rentals` `rn` left join `payments` `p` on((`p`.`rental_id` = `rn`.`id`))) GROUP BY `rn`.`id`, `rn`.`status` ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accounts`
--
ALTER TABLE `accounts`
  ADD CONSTRAINT `fk_accounts_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_accounts_owner` FOREIGN KEY (`owner_id`) REFERENCES `owners` (`owner_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_accounts_staff` FOREIGN KEY (`staff_id`) REFERENCES `staffs` (`staff_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `cameras`
--
ALTER TABLE `cameras`
  ADD CONSTRAINT `fk_cameras_owner` FOREIGN KEY (`owner_id`) REFERENCES `owners` (`owner_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `camera_images`
--
ALTER TABLE `camera_images`
  ADD CONSTRAINT `fk_camimg_camera` FOREIGN KEY (`camera_id`) REFERENCES `cameras` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `fk_customers_account` FOREIGN KEY (`customer_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `owners`
--
ALTER TABLE `owners`
  ADD CONSTRAINT `fk_owners_account` FOREIGN KEY (`owner_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `rentals`
--
ALTER TABLE `rentals`
  ADD CONSTRAINT `fk_rentals_camera` FOREIGN KEY (`camera_id`) REFERENCES `cameras` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rentals_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rentals_staff` FOREIGN KEY (`staff_id`) REFERENCES `staffs` (`staff_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `fk_reports_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `accounts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reports_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_reviews_camera` FOREIGN KEY (`camera_id`) REFERENCES `cameras` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reviews_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `staffs`
--
ALTER TABLE `staffs`
  ADD CONSTRAINT `fk_staffs_account` FOREIGN KEY (`staff_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
