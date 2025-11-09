-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 09, 2025 at 08:19 AM
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
(3, NULL, NULL, 3, 'cust_demo', 'cust_demo@snaprent.com', NULL, 'customer123', 'CUSTOMER', 1, 1, '2025-11-05 01:36:24');

-- --------------------------------------------------------

--
-- Table structure for table `cameras`
--

CREATE TABLE `cameras` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `brand` varchar(80) NOT NULL,
  `type` varchar(80) DEFAULT NULL,
  `problem` varchar(100) DEFAULT NULL,
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
(38, 'M123', 'Canon', 'DSLR', 'test', 2423000.00, 'unavailable', NULL, NULL, '2025-11-01 22:36:37', '2025-11-02 20:44:01');

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
(69, 38, 'cam_69075bfd18bc57.63852226.png', '2025-11-02 13:26:21'),
(71, 38, 'cam_69075bfddbe498.63430344.jpg', '2025-11-02 13:26:23'),
(74, 38, 'cam_6909770b2f16e8.78425325.png', '2025-11-04 03:46:19');

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
`camera_id` bigint unsigned
,`name` varchar(120)
,`brand` varchar(80)
,`avg_rating` decimal(7,4)
,`review_count` bigint
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_rental_payment_status`
-- (See below for the actual view)
--
CREATE TABLE `v_rental_payment_status` (
`rental_id` bigint unsigned
,`rental_status` enum('pending','confirmed','rented','returned','cancelled')
,`paid_amount` decimal(34,2)
,`last_payment_status` enum('pending','verified','failed')
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
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `cameras`
--
ALTER TABLE `cameras`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `camera_images`
--
ALTER TABLE `camera_images`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

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
