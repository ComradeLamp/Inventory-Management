-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 19, 2025 at 06:16 AM
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
-- Database: `bicol_depot`
--

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `user_id`, `subject`, `content`, `created_at`) VALUES
(1, 21, 'qawer', 'adfgh', '2025-05-16 06:36:25'),
(2, 21, 'sdfgsdfg', 'dffggfsa', '2025-05-16 06:36:39'),
(3, 21, 'sdfgsdfg', 'dffggfsa', '2025-05-16 06:36:49'),
(4, 2, 'gdhgsdjhsa', 'uyuyewuyurwe', '2025-05-16 06:37:27'),
(5, 21, 'dsjhsg', 'hsjafjhhfjsh\\r\\n', '2025-05-16 06:38:19'),
(6, 2, 'jhgsdgshf', 'mndsfhjgfdh\\r\\n', '2025-05-16 06:45:14'),
(7, 18, 'Walang PC', 'sqfaf', '2025-05-18 02:05:09'),
(8, 1, 'Test', 'message test directory', '2025-05-18 13:42:27'),
(9, 1, 'Test', 'message test directory', '2025-05-18 13:43:15'),
(10, 1, 'Walang PC', 'gesajhfvjasvfjasjkfjht test 2\\r\\n', '2025-05-18 13:43:23'),
(11, 1, 'subject none', '12345678', '2025-05-18 13:44:08'),
(12, 1, 'Walang PC', 'wala daw pc here', '2025-05-18 14:34:11'),
(13, 1, 'Walang PC', 'test', '2025-05-19 00:57:46'),
(14, 1, 'Walang PC', 'test2', '2025-05-19 01:02:56'),
(15, 2, 'Walang PC', 'WLANG PC', '2025-05-19 02:31:35');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `image`, `category`, `price`, `quantity`) VALUES
(1, 'Lenovo ThinkPad T14', 'Business-class laptop', 'assets/img/LenovoThinkPadT14.jpg', 'Laptop', 999.99, 17),
(3, 'HP Spectre x360', 'Convertible 2-in-1 laptop', 'assets/img/HPSpectrex360.jpg', 'Laptop', 1099.99, 9),
(4, 'Asus ROG Zephyrus G14', 'Gaming powerhouse laptop', 'assets/img/AsusROGZephyrusG14.jpg', 'Laptop', 1399.99, 8),
(5, 'NVIDIA GeForce RTX 3060', 'Great performance for 1080p and 1440p gaming', 'assets/img/NVIDIAGeForceRTX3060.jpg', 'GPU', 379.99, 9),
(6, 'AMD Radeon RX 6600', 'Mid-range AMD graphics card', 'assets/img/AMDRadeonRX6600.jpg', 'GPU', 329.99, 9),
(7, 'Intel Arc A750', 'Intel’s entry to discrete graphics', 'assets/img/IntelArcA750.jpg', 'GPU', 279.99, 10),
(8, 'ASUS ROG Strix B550-F', 'For AMD Ryzen builds', 'assets/img/ASUSROGStrixB550F.jpg', 'Motherboard', 189.99, 10),
(9, 'MSI MAG B660M Mortar', 'Intel 12th gen support', 'assets/img/MSIMAGB660MMortar.jpg', 'Motherboard', 169.99, 10),
(10, 'Gigabyte Z690 AORUS Elite', 'Z690 chipset for performance builds', 'assets/img/GigabyteZ690AORUSElite.jpg', 'Motherboard', 229.99, 10),
(11, 'NZXT H510', 'Minimalist mid-tower case', 'assets/img/NZXTH510.jpg', 'PC Case', 89.99, 10),
(12, 'Corsair 4000D', 'Great airflow and cable management', 'assets/img/Corsair4000D.jpg', 'PC Case', 94.99, 10),
(13, 'Fractal Design Meshify C', 'Airflow focused mid-tower case', 'assets/img/FractalDesignMeshifyC.jpg', 'PC Case', 99.99, 10),
(14, 'Cooler Master MasterBox Q300L', 'Compact and modular design', 'assets/img/CoolerMasterMasterBoxQ300L.jpg', 'PC Case', 59.99, 10),
(15, 'Samsung 970 EVO Plus 1TB', 'High-speed NVMe storage', 'assets/img/Samsung970EVOPlus1TB.jpg', 'SSD', 129.99, 10),
(16, 'Crucial MX500 500GB', 'Reliable SATA SSD', 'assets/img/CrucialMX500500GB.jpg', 'SSD', 59.99, 10),
(17, 'WD Black SN770 1TB', 'Performance NVMe drive', 'assets/img/WDBlackSN7701TB.jpg', 'SSD', 119.99, 10),
(18, 'Kingston A2000 512GB', 'Budget NVMe SSD', 'assets/img/KingstonA2000512GB.jpg', 'SSD', 49.99, 9),
(19, 'Seagate FireCuda 510 1TB', 'Gaming optimized SSD', 'assets/img/SeagateFireCuda5101TB.jpg', 'SSD', 109.99, 10),
(20, 'Intel Core i7-12700K', '12th gen Intel CPU', 'assets/img/IntelCorei712700K.jpg', 'CPU', 399.99, 10),
(21, 'AMD Ryzen 7 5800X', 'Powerful 8-core AMD CPU', 'assets/img/AMDRyzen75800X.jpg', 'CPU', 349.99, 10),
(23, 'AMD Ryzen 5 5600X', 'Solid 6-core gaming CPU', 'assets/img/AMDRyzen55600X.jpg', 'CPU', 179.99, 10),
(24, 'Intel Core i9-12900K', 'Top-tier 12th gen Intel', 'assets/img/IntelCorei912900K.jpg', 'CPU', 599.99, 9),
(25, 'AMD Ryzen 9 5900X', '12-core AMD monster', 'assets/img/AMDRyzen95900X.jpg', 'CPU', 549.99, 10),
(29, 'Top 1', 'Top 1', NULL, 'GPU', 100.00, 4),
(30, 'asdasdasda', 'asdasd', NULL, 'Laptop', 1.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled') DEFAULT 'pending',
  `reserved_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `user_id`, `product_id`, `quantity`, `status`, `reserved_at`) VALUES
(3, 1, 1, 1, 'cancelled', '2025-05-14 05:23:04'),
(4, 1, 1, 1, 'cancelled', '2025-05-14 05:30:38'),
(5, 1, 3, 1, 'cancelled', '2025-05-14 05:35:50'),
(6, 1, 3, 1, 'cancelled', '2025-05-14 05:36:11'),
(7, 1, 5, 1, 'cancelled', '2025-05-14 05:36:17'),
(8, 1, 1, 1, 'cancelled', '2025-05-14 05:49:00'),
(9, 1, 1, 1, 'cancelled', '2025-05-14 05:49:05'),
(10, 2, 1, 1, 'cancelled', '2025-05-14 06:01:13'),
(11, 2, 1, 1, 'cancelled', '2025-05-14 06:01:19'),
(12, 2, 1, 1, 'cancelled', '2025-05-14 06:01:22'),
(13, 1, 3, 1, 'cancelled', '2025-05-14 06:09:06'),
(14, 1, 7, 1, 'cancelled', '2025-05-14 06:10:08'),
(15, 1, 19, 1, 'cancelled', '2025-05-14 06:32:57'),
(16, 1, 1, 1, 'cancelled', '2025-05-14 06:52:44'),
(17, 21, 1, 2, 'pending', '2025-05-14 08:08:08'),
(18, 1, 1, 6, 'cancelled', '2025-05-14 08:13:51'),
(19, 18, 28, 1, 'pending', '2025-05-18 02:05:32'),
(20, 18, 4, 1, 'pending', '2025-05-18 02:05:42'),
(21, 18, 24, 1, 'pending', '2025-05-18 02:05:47'),
(22, 18, 18, 1, 'pending', '2025-05-18 02:05:53'),
(23, 2, 6, 1, 'pending', '2025-05-18 03:51:43'),
(24, 1, 4, 1, 'pending', '2025-05-18 14:16:16'),
(25, 1, 5, 1, 'pending', '2025-05-18 14:16:20'),
(26, 2, 29, 2, 'pending', '2025-05-19 02:31:02');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('customer','admin') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `login_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(10) DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`, `login_at`, `status`) VALUES
(1, 'ComradeCom15', 'castilloroimartina@gmail.com', '$2y$10$zBchefJywa1uEKGFRVOqKOyeYfH0A/9CCFVoqJ56A4rO3Z8vkx7S2', 'customer', '2025-05-04 17:46:40', '2025-05-19 02:30:33', 'active'),
(2, 'ModeratorCom', 'moderator@gmail.com', '$2y$10$XoxmJMey4I5/qi6fezqoOeql/gPVAzzxGGyCmH3jsTulj0IyTGM.a', 'admin', '2025-05-07 08:34:36', '2025-05-19 02:30:48', 'active'),
(17, 'admin', 'admin@gmail.com', '$2y$10$4eoJRzzZk/mmBQGMuhCdHesskQjMX6LAcUNF39336rMbzDhrM0PR2', 'admin', '2025-05-12 19:54:41', '2025-05-18 14:29:06', 'active'),
(18, 'test2', 'test2@gmail.com', '$2y$10$I6c7mx6am/RkTkc9L7ZDVOIcQVSPgaQPPfAl989ceyY7TUCFizbAK', 'customer', '2025-05-12 19:57:53', '2025-05-18 14:28:01', 'active'),
(19, 'rere', 'jjj@gmail.com', '$2y$10$AST9LL09uVvGe0Cz.Lqh.O9vht26.zF0o5UtQHgQuLstANsdZ2YmG', 'customer', '2025-05-14 02:51:31', '2025-05-14 02:52:07', 'active'),
(21, 'dhan', 'dhan@gmail.com', '$2y$10$gbkD.BEaJRWwhrbesVXt2euT3Hknmr9t.51X0Ae5Xy6SsNIQbBclG', 'customer', '2025-05-14 08:07:02', '2025-05-16 06:38:09', 'inactive'),
(25, 'Manager-Com', 'manager@gmail.com', '$2y$10$h0rgpTnVXOtaJl2YH6r2hu7ojE9nkwRnF9rkKP.AM6l.527XpVGfe', 'customer', '2025-05-19 02:19:39', '2025-05-19 02:19:39', 'active'),
(26, 'Customer', 'customer@gmail.com', '$2y$10$8KUPjbN9JML45zxAozPB0edP6YiY7TaL31x.Qd79BC9K/2hc11.Fm', 'customer', '2025-05-19 02:23:32', '2025-05-19 02:23:42', 'active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
