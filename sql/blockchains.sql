-- phpMyAdmin SQL Dump
-- version 5.1.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 06, 2025 at 01:35 PM
-- Server version: 10.4.19-MariaDB
-- PHP Version: 7.4.19

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sdtoken`
--

-- --------------------------------------------------------

--
-- Table structure for table `blockchains`
--

CREATE TABLE `blockchains` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` double(8,2) DEFAULT NULL,
  `price_1d` double(8,2) DEFAULT NULL,
  `price_change_1d` double DEFAULT NULL,
  `logo` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `scan_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `active` int(11) NOT NULL,
  `svg_1d` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `smart` int(11) NOT NULL,
  `name_short` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `blockchains`
--

INSERT INTO `blockchains` (`id`, `name`, `token`, `price`, `price_1d`, `price_change_1d`, `logo`, `deleted_at`, `created_at`, `updated_at`, `scan_url`, `active`, `svg_1d`, `smart`, `name_short`) VALUES
(1, 'Ethereum ', 'Ether', 3.00, NULL, NULL, 'eth64.png', NULL, '2022-04-20 14:45:20', '2022-04-20 14:45:20', '', 0, '', 0, ''),
(2, 'BSC', 'BNB', 10.00, NULL, NULL, 'bnb64.png', NULL, '2022-04-20 14:45:20', '2022-04-20 14:45:20', '', 0, '', 0, ''),
(3, 'Avalanch ', 'Avax', 1.00, NULL, NULL, 'avax64.png', NULL, '2022-04-20 14:45:20', '2022-04-20 14:45:20', '', 0, '', 0, ''),
(4, 'Fantom ', 'FTM', 1.20, NULL, NULL, 'ftm64.png', NULL, '2022-04-20 14:45:20', '2022-04-20 14:45:20', '', 0, '', 0, ''),
(5, 'Polygon ', 'Matic', 1.40, NULL, NULL, 'matic64.png', NULL, '2022-04-20 14:45:20', '2022-04-20 14:45:20', '', 0, '', 0, '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `blockchains`
--
ALTER TABLE `blockchains`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `blockchains`
--
ALTER TABLE `blockchains`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
