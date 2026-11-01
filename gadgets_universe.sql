-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 29, 2026 at 06:21 PM
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
-- Database: `gadgets_universe`
--

-- --------------------------------------------------------

--
-- Table structure for table `gadgets`
--

CREATE TABLE `gadgets` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `brand` varchar(50) NOT NULL,
  `buying_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `selling_price` decimal(12,2) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `quantity` int(11) NOT NULL DEFAULT 0,
  `category` varchar(50) NOT NULL DEFAULT 'Other',
  `image_url` varchar(255) DEFAULT 'https://unsplash.com'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gadgets`
--

INSERT INTO `gadgets` (`id`, `name`, `brand`, `buying_price`, `selling_price`, `description`, `created_at`, `quantity`, `category`, `image_url`) VALUES
(4, 'Clear Back Cover', 'Samsung', 100.00, 250.00, 'Samsung Galaxy A55', '2026-10-05 18:30:52', 45, 'Mobile Phone Accessories', 'images/A55_backcover_1_c2a499e0.jpg,images/A55_backcover_2_12435207.jpg,images/A55_backcover_16dc6093.jpg'),
(5, 'Screen Protector', 'Samsung', 75.00, 300.00, 'Samsung Galaxy A55', '2026-10-05 18:30:52', 94, 'Mobile Phone Accessories', 'images/A55_Screen_Protector_1_b66a72a3.jpg,images/A55_Screen_Protector_84b505cc.jpg'),
(6, 'Charging Cable', 'Samsung', 50.00, 150.00, 'Fast Charging Cable for Samsung A55', '2026-10-05 18:30:52', 73, 'Mobile Phone Accessories', 'images/A55_Charging_Cable_1_7ec56060.jpg,images/A55_Charging_Cable_e22c1287.jpg'),
(7, 'Charging Plug', 'Samsung', 75.00, 325.00, 'Fast Charging Plug', '2026-10-17 15:40:17', 47, 'Mobile Phone Accessories', 'images/Samsung_Charging_Plug_1_96b0165e.jpg,images/Samsung_Charging_Plug_2_8cd18fb6.jpg,images/Samsung_Charging_Plug_e1b477b0.jpg'),
(8, 'Samsung A55 5G', 'Samsung', 16690.00, 19990.00, 'Colour: White\r\nSize & Type: 6.6-inch Super AMOLED\r\nResolution: 1080 x 2340 pixels (~390 ppi density)\r\nRefresh Rate: 120Hz\r\nBrightness: 1000 nits (HBM)\r\nProtection: Corning Gorilla Glass Victus+', '2026-10-17 15:45:07', 9, 'Mobile Phones', 'images/Samsung_A55_5G_1_4e33b319.jpg,images/Samsung_A55_5G_2_4bb97c1f.jpg,images/Samsung_A55_5G_9e3c9d78.jpg'),
(9, 'DJI Flip', 'DJI', 25000.00, 40000.00, '• Weight: Under 249 grams (C0 compliant, meaning no pilot exam is required in many regions)\r\n• Camera: 1/1.3-inch CMOS sensor capturing 48-megapixel photos and up to 4K/60fps HDR video (with 10-bit D-Log M support)\r\n• Flight Time: Up to 31 minutes (roughly 22 to 25 minutes in real-world conditions)\r\n• Design:Automatic power-on when unfolding the arms, plus integrated full-coverage propeller guards\r\n• Control Options: Palm takeoff/landing without a controller, smartphone via the DJI Fly app, or traditional remotes like the DJI RC-N3 or DJI RC 2\r\n• Intelligent Modes: Subject tracking (ActiveTrack), circle, rocket, droney, and spotligh', '2026-10-18 13:19:04', 7, 'Drone', 'images/DJI_Flip_1_74c6dee0.png,images/DJI_Flip_2_04eb3616.png,images/DJI_Flip_d48303c6.png'),
(10, 'DJI Flip Spare Battery', 'DJI', 3500.00, 5000.00, '', '2026-10-18 13:25:14', 27, 'Drone Accessories', 'images/DJI_Flip_battery_2_7886acbb.jpg,images/DJI_Flip_battery_6b869327.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_takes`
--

CREATE TABLE `inventory_takes` (
  `id` int(11) NOT NULL,
  `take_date` date NOT NULL,
  `take_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `inventory_takes`
--

INSERT INTO `inventory_takes` (`id`, `take_date`, `take_name`, `created_at`) VALUES
(1, '2026-10-20', 'October 2026', '2026-10-20 08:24:47'),
(2, '2026-10-20', 'October 2026', '2026-10-20 08:30:25'),
(3, '2026-10-20', 'October 2026 - 2', '2026-10-20 08:32:49'),
(4, '2026-10-20', 'Test', '2026-10-20 09:27:37'),
(5, '2026-10-20', 'Test 2', '2026-10-20 11:08:25');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_take_items`
--

CREATE TABLE `inventory_take_items` (
  `id` int(11) NOT NULL,
  `take_id` int(11) NOT NULL,
  `gadget_id` int(11) NOT NULL,
  `opening_stock` int(11) NOT NULL DEFAULT 0,
  `sold_since_last` int(11) NOT NULL DEFAULT 0,
  `expected_stock` int(11) NOT NULL DEFAULT 0,
  `actual_stock` int(11) NOT NULL DEFAULT 0,
  `variance` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `inventory_take_items`
--

INSERT INTO `inventory_take_items` (`id`, `take_id`, `gadget_id`, `opening_stock`, `sold_since_last`, `expected_stock`, `actual_stock`, `variance`) VALUES
(1, 1, 4, 0, 2, 0, 0, 0),
(2, 1, 5, 0, 4, 0, 0, 0),
(3, 1, 6, 0, 1, 0, 0, 0),
(4, 1, 7, 0, 4, 0, 0, 0),
(5, 1, 8, 0, 3, 0, 0, 0),
(6, 1, 9, 0, 1, 0, 0, 0),
(7, 1, 10, 0, 0, 0, 0, 0),
(8, 2, 4, 50, 0, 50, 50, 0),
(9, 2, 5, 100, 0, 100, 100, 0),
(10, 2, 6, 75, 0, 75, 75, 0),
(11, 2, 7, 50, 0, 50, 50, 0),
(12, 2, 8, 10, 0, 10, 10, 0),
(13, 2, 9, 7, 0, 7, 7, 0),
(14, 2, 10, 30, 0, 30, 30, 0),
(15, 3, 4, 50, 0, 50, 46, -4),
(16, 3, 5, 100, 0, 100, 94, -6),
(17, 3, 6, 75, 0, 75, 74, -1),
(18, 3, 7, 50, 0, 50, 48, -2),
(19, 3, 8, 10, 0, 10, 10, 0),
(20, 3, 9, 7, 0, 7, 7, 0),
(21, 3, 10, 30, 0, 30, 28, -2),
(22, 4, 4, 45, 0, 45, 45, 0),
(23, 4, 5, 94, 0, 94, 94, 0),
(24, 4, 6, 73, 0, 73, 73, 0),
(25, 4, 7, 48, 0, 48, 48, 0),
(26, 4, 8, 10, 0, 10, 10, 0),
(27, 4, 9, 7, 0, 7, 7, 0),
(28, 4, 10, 28, 0, 28, 28, 0),
(29, 5, 4, 45, 0, 45, 45, 0),
(30, 5, 5, 94, 0, 94, 94, 0),
(31, 5, 6, 73, 0, 73, 73, 0),
(32, 5, 7, 47, 0, 47, 47, 0),
(33, 5, 8, 9, 0, 9, 9, 0),
(34, 5, 9, 7, 0, 7, 7, 0),
(35, 5, 10, 27, 0, 27, 27, 0);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_code` varchar(32) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `first_name` varchar(50) NOT NULL DEFAULT '',
  `last_name` varchar(50) NOT NULL DEFAULT '',
  `delivery_address` varchar(255) NOT NULL DEFAULT '',
  `total_amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL DEFAULT 'cod',
  `payment_status` varchar(20) NOT NULL DEFAULT 'pending',
  `delivery_status` varchar(50) NOT NULL DEFAULT 'Pending',
  `payment_reference` varchar(64) DEFAULT NULL,
  `payment_phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_code`, `user_id`, `username`, `first_name`, `last_name`, `delivery_address`, `total_amount`, `payment_method`, `payment_status`, `delivery_status`, `payment_reference`, `payment_phone`, `created_at`) VALUES
(2, 'GU0000002', 7, 'mohammad', '', '', '', 20615.00, 'cod', 'pending', 'Pending', NULL, NULL, '2026-10-17 16:38:56'),
(3, NULL, 8, 'arhaamooziraully', 'Arhaam', 'Ooziraully', 'Parisot Road Mesnil, Phoenix', 21015.00, 'cod', 'pending', 'Pending', NULL, NULL, '2026-10-18 12:45:41'),
(4, 'GU0000004', 8, 'arhaamooziraully', '', '', '', 21015.00, 'cod', 'pending', 'Pending', NULL, NULL, '2026-10-18 12:45:41'),
(5, NULL, 9, 'arhaamooziraully1', 'Arhaam', 'Ooziraully', 'Vacoas', 300.00, 'cod', 'pending', 'Pending', NULL, NULL, '2026-10-18 12:52:48'),
(6, 'GU0000006', 9, 'arhaamooziraully1', '', '', '', 300.00, 'cod', 'pending', 'Pending', NULL, NULL, '2026-10-18 12:52:48'),
(7, 'GU0000007', 9, 'arhaamooziraully1', '', '', '', 300.00, 'mcb_juice', 'paid', 'Pending', 'JUICE-36F630CB', '57930098', '2026-10-18 13:07:41'),
(8, 'GU0000008', 9, 'arhaamooziraully1', 'Arhaam', 'Ooziraully', 'Vacoas', 19990.00, 'mcb_juice', 'paid', 'Pending', 'JUICE-362B2043', '57930098', '2026-10-18 13:10:10'),
(9, 'GU0000009', 9, 'arhaamooziraully1', 'Arhaam', 'Ooziraully', 'Vacoas', 325.00, 'mcb_juice', 'paid', 'Pending', 'JUICE-C2478F65', '57930098', '2026-10-18 13:12:22'),
(10, 'GU0000010', 9, 'arhaamooziraully1', 'Arhaam', 'Ooziraully', 'Vacoas', 40000.00, 'mcb_juice', 'paid', 'Pending', 'JUICE-AFFD6B61', '57930098', '2026-10-18 13:57:04'),
(11, 'GU0000011', 9, 'arhaamooziraully1', 'Arhaam', 'Ooziraully', 'Vacoas', 325.00, 'mcb_juice', 'paid', 'Pending', 'JUICE-00C14FA6', '57930098', '2026-10-18 14:11:48'),
(12, 'GU0000012', 9, 'arhaamooziraully1', 'Arhaam', 'Ooziraully', 'Vacoas', 250.00, 'mcb_juice', 'paid', 'Delivered', 'JUICE-B157CEB3', '57930098', '2026-10-18 14:19:25'),
(13, 'GU0000013', 9, 'arhaamooziraully1', 'Arhaam', 'Ooziraully', 'Vacoas', 400.00, 'mcb_juice', 'paid', 'Pending', 'JUICE-6E9B0988', '57930098', '2026-10-20 09:25:37'),
(14, 'GU0000014', 9, 'arhaamooziraully1', 'Arhaam', 'Ooziraully', 'Vacoas', 325.00, 'mcb_juice', 'paid', 'Pending', 'JUICE-244D94C2', '57930098', '2026-10-20 10:42:44'),
(15, 'GU0000015', 9, 'arhaamooziraully1', 'Arhaam', 'Ooziraully', 'Vacoas', 5000.00, 'mcb_juice', 'paid', 'Pending', 'JUICE-74ECDE69', '57930098', '2026-10-20 11:02:20'),
(16, 'GU0000016', 12, 'bradleybousoula', 'Bradley', 'Bousoula', 'Vacoas', 19990.00, 'mcb_juice', 'paid', 'Delivered', 'JUICE-62FB6C53', '57930098', '2026-10-20 11:07:23');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `gadget_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `gadget_id`, `product_name`, `unit_price`, `quantity`, `subtotal`) VALUES
(1, 2, 7, 'Charging Plug', 325.00, 1, 325.00),
(2, 2, 8, 'Samsung A55 5G', 19990.00, 1, 19990.00),
(3, 2, 5, 'Screen Protector', 300.00, 1, 300.00),
(4, 4, 6, 'Charging Cable', 150.00, 1, 150.00),
(5, 4, 7, 'Charging Plug', 325.00, 1, 325.00),
(6, 4, 4, 'Clear Back Cover', 250.00, 1, 250.00),
(7, 4, 8, 'Samsung A55 5G', 19990.00, 1, 19990.00),
(8, 4, 5, 'Screen Protector', 300.00, 1, 300.00),
(9, 6, 5, 'Screen Protector', 300.00, 1, 300.00),
(10, 7, 5, 'Screen Protector', 300.00, 1, 300.00),
(11, 8, 8, 'Samsung A55 5G', 19990.00, 1, 19990.00),
(12, 9, 7, 'Charging Plug', 325.00, 1, 325.00),
(13, 10, 9, 'DJI Flip', 40000.00, 1, 40000.00),
(14, 11, 7, 'Charging Plug', 325.00, 1, 325.00),
(15, 12, 4, 'Clear Back Cover', 250.00, 1, 250.00),
(16, 13, 6, 'Charging Cable', 150.00, 1, 150.00),
(17, 13, 4, 'Clear Back Cover', 250.00, 1, 250.00),
(18, 14, 7, 'Charging Plug', 325.00, 1, 325.00),
(19, 15, 10, 'DJI Flip Spare Battery', 5000.00, 1, 5000.00),
(20, 16, 8, 'Samsung A55 5G', 19990.00, 1, 19990.00);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL DEFAULT '',
  `last_name` varchar(50) NOT NULL DEFAULT '',
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `address` varchar(255) NOT NULL DEFAULT '',
  `dob` date DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `username`, `email`, `address`, `dob`, `password`, `created_at`) VALUES
(1, '', '', 'wass10', 'wassimsheik@hotmail.com', '', NULL, '$2y$10$xAW1tyOdI3xhfCYS0oc5qu0gkD2C35xo9f.6cK0w2o3y8ThwacfPG', '2026-10-05 17:32:20'),
(4, '', '', 'admin', 'admin@universe.com', '', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-10-05 17:41:37'),
(5, '', '', 'sheik10', 'sheik.wassim@outlook.com', '', NULL, '$2y$10$x0kwrBS8K6S1b92Ne2aNVulPLydGfDdHfv5k7Xaz7jnO5FHgRGKUC', '2026-10-05 18:06:08'),
(6, '', '', 'Bradley', 'bradley@hotmail.com', '', NULL, '$2y$10$P.OtzWCJMR0.pr5laQKd6OsLHII4PV2mFwwJ0BDYl5N7fFa/PBnTm', '2026-10-06 10:29:23'),
(7, '', '', 'mohammad', 'wassimsheik4@gmail.com', '', NULL, '$2y$10$SqdZ5M3hEuE0Ol.3hpctI.nsZY3YZDIqogdNRPyWZsSUAMPd73pbq', '2026-10-15 09:46:11'),
(8, 'Arhaam', 'Ooziraully', 'arhaamooziraully', 'arhaam.ooziraully@hotmail.com', 'Parisot Road Mesnil, Phoenix', '2022-04-10', '$2y$10$hiNK4dLiFu5h.XKgZYtdfeg2swSXZ0ypTmt71TLn3k3hUVRt963Gi', '2026-10-18 12:45:21'),
(9, 'Arhaam', 'Ooziraully', 'arhaamooziraully1', 'arhaam.ooziraully@gmail.com', 'Vacoas', '2022-04-10', '$2y$10$ty.M.WHbYm3eqL.Agl.FH.5MA0lzIltD7k5nE6KVj4nkFNUWECHaG', '2026-10-18 12:50:58'),
(12, 'Bradley', 'Bousoula', 'bradleybousoula', 'bradley1@hotmail.com', 'Vacoas', '2026-10-09', '$2y$10$ceELp29JluOhZERRBKqEhepJc/bIIrWXu4/X5Sjz8bTyfnwVhhtWu', '2026-10-20 11:06:50');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `gadgets`
--
ALTER TABLE `gadgets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_takes`
--
ALTER TABLE `inventory_takes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_take_items`
--
ALTER TABLE `inventory_take_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `take_id` (`take_id`),
  ADD KEY `gadget_id` (`gadget_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_code` (`order_code`),
  ADD KEY `idx_order_code` (`order_code`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `gadgets`
--
ALTER TABLE `gadgets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `inventory_takes`
--
ALTER TABLE `inventory_takes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `inventory_take_items`
--
ALTER TABLE `inventory_take_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `inventory_take_items`
--
ALTER TABLE `inventory_take_items`
  ADD CONSTRAINT `inventory_take_items_ibfk_1` FOREIGN KEY (`take_id`) REFERENCES `inventory_takes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_take_items_ibfk_2` FOREIGN KEY (`gadget_id`) REFERENCES `gadgets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
