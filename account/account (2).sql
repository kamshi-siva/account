-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 12, 2025 at 04:54 AM
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
-- Database: `account`
--

-- --------------------------------------------------------

--
-- Table structure for table `allowance_rules`
--

CREATE TABLE `allowance_rules` (
  `id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `default_allowance` decimal(10,2) DEFAULT 1000.00,
  `year_end_bonus` decimal(10,2) DEFAULT 5000.00,
  `sales_threshold` decimal(10,2) DEFAULT 50000.00,
  `extra_allowance` decimal(10,2) DEFAULT 2000.00,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `allowance_rules`
--

INSERT INTO `allowance_rules` (`id`, `restaurant_id`, `default_allowance`, `year_end_bonus`, `sales_threshold`, `extra_allowance`, `updated_at`) VALUES
(1, 9, 5000.00, 1000.00, 1000.00, 1000.00, '2025-10-22 05:31:11');

-- --------------------------------------------------------

--
-- Table structure for table `cashiers`
--

CREATE TABLE `cashiers` (
  `id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `ic_no` varchar(50) NOT NULL,
  `address` varchar(255) NOT NULL,
  `id_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cashiers`
--

INSERT INTO `cashiers` (`id`, `restaurant_id`, `name`, `phone`, `password`, `ic_no`, `address`, `id_image`, `created_at`) VALUES
(1, 5, 'kayathiri', '0766260347', '$2y$10$nlBXhL.9w3pciDWZMqhZhu9DVdPpReLKLu33IzSphuakY5zVphHnS', '', '', NULL, '2025-10-03 05:17:54'),
(2, 6, 'mona', 'mona@gmail.com', '$2y$10$iyoV8kL.rjELn9z1iQwJw.Tszjqt7M4luVFPppqHzugbyi9f1.LpW', '', '', NULL, '2025-10-03 05:54:42'),
(3, 6, 'arshan', '0771596426', '$2y$10$HY8L4aHfcV58pQ58rs710uIpi9NHCe7PL7Ol.vIU1Fq8RgJMJYREa', '', '', NULL, '2025-10-03 06:05:28'),
(4, 10, 'anoj', '0760895878', '$2y$10$YFv6w/786TH0gMteuG4zTeZ01eAkF4fVhT/Bo3heV8mP8lPmi1mhO', '', '', NULL, '2025-10-03 09:15:38'),
(5, 11, 'dakshan', '0765631025', '$2y$10$9sme2BJFYMMHx22mKAnVqOSCN1Y4eaTlf6bZmRgu2iEt8WPS1oaTq', '', '', NULL, '2025-10-03 09:58:11'),
(6, 9, 'kapinan', '0707412745', '$2y$10$PVNpUa3GV2G9cKFCR4HMpOCW2wSLg4PnxZIVxxHgd47VjPBaKhwzO', '200221801745', 'No 122/4, 3rd lane, poonthoddam, vavuniya', 'cashier_6_1759742287.png', '2025-10-06 03:50:43'),
(9, 9, 'kanna', '0769644056', '', '200027602647', 'kannakaiamman kovilady, vaddu west vaddukkoddai', 'cashier_7_1759815753.png', '2025-10-13 10:20:54');

-- --------------------------------------------------------

--
-- Table structure for table `cashier_advance`
--

CREATE TABLE `cashier_advance` (
  `id` int(11) NOT NULL,
  `cashier_id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `reason` varchar(255) DEFAULT NULL,
  `given_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cashier_advance`
--

INSERT INTO `cashier_advance` (`id`, `cashier_id`, `restaurant_id`, `amount`, `reason`, `given_date`) VALUES
(1, 6, 9, 5000.00, 'Personal need', '2025-10-07'),
(3, 9, 9, 5000.00, 'Personal need', '2025-10-22'),
(5, 6, 9, 2000.00, 'Personal need', '2025-12-02');

-- --------------------------------------------------------

--
-- Table structure for table `cashier_allowance_rules`
--

CREATE TABLE `cashier_allowance_rules` (
  `id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `min_sales` decimal(10,2) NOT NULL,
  `max_sales` decimal(10,2) NOT NULL,
  `percentage` decimal(5,2) DEFAULT 0.00,
  `fixed_amount` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cashier_salary`
--

CREATE TABLE `cashier_salary` (
  `id` int(11) NOT NULL,
  `cashier_id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `month` varchar(20) NOT NULL,
  `year` int(4) NOT NULL,
  `basic_salary` decimal(10,2) NOT NULL DEFAULT 0.00,
  `bonus` decimal(10,2) NOT NULL DEFAULT 0.00,
  `deductions` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_salary` decimal(10,2) GENERATED ALWAYS AS (`basic_salary` + `bonus` + `allowance` - `deductions`) STORED,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `allowance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('Paid','Pending') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cashier_salary`
--

INSERT INTO `cashier_salary` (`id`, `cashier_id`, `restaurant_id`, `month`, `year`, `basic_salary`, `bonus`, `deductions`, `created_at`, `allowance`, `status`) VALUES
(4, 6, 9, 'October', 2025, 30000.00, 10000.00, 5000.00, '2025-10-07 06:34:41', 0.00, 'Paid'),
(7, 9, 9, 'October', 2025, 30000.00, 10000.00, 5000.00, '2025-10-22 05:12:46', 3000.00, 'Pending'),
(11, 6, 9, 'November', 2025, 30000.00, 10000.00, 0.00, '2025-10-22 05:41:15', 6000.00, 'Pending'),
(13, 6, 9, 'December', 2025, 30000.00, 10000.00, 2000.00, '2025-10-22 06:17:20', 7000.00, 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `restaurant_id`, `name`, `created_at`) VALUES
(1, 4, 'biriyani', '2025-10-02 10:26:24'),
(2, 6, 'starter', '2025-10-03 06:11:10'),
(3, 6, 'biriyani', '2025-10-03 06:11:19'),
(4, 6, 'Rice', '2025-10-03 06:11:30'),
(5, 6, 'Noodles', '2025-10-03 06:11:43'),
(6, 6, 'kottu', '2025-10-03 06:12:00'),
(7, 10, 'Milkshakes', '2025-10-03 09:18:20'),
(8, 10, 'Foldovers', '2025-10-03 09:18:56'),
(9, 10, 'Grid cakes', '2025-10-03 09:19:17'),
(10, 10, 'Mojitos', '2025-10-03 09:19:28'),
(11, 11, 'Chocolate', '2025-10-03 09:56:57'),
(12, 9, 'Beverages', '2025-10-06 05:08:32'),
(13, 9, 'Desserts', '2025-10-06 06:15:14'),
(14, 9, 'Soups', '2025-10-16 07:09:03');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `total_purchases` decimal(10,2) DEFAULT 0.00,
  `loyalty_points` int(11) DEFAULT 0,
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deleted_cashiers`
--

CREATE TABLE `deleted_cashiers` (
  `id` int(11) NOT NULL,
  `cashier_id` int(11) DEFAULT NULL,
  `restaurant_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `ic_no` varchar(50) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `id_image` varchar(255) DEFAULT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `expense_name` varchar(150) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `restaurant_id`, `expense_name`, `amount`, `date`) VALUES
(1, 9, 'Cashier Salary', 60000.00, '2025-10-13'),
(2, 9, 'Rent', 20000.00, '2025-10-13'),
(3, 9, 'bill', 5000.00, '2025-10-13');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `cashier_id` int(11) NOT NULL,
  `order_number` varchar(50) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL,
  `payment_method` enum('Cash','Card','UPI') DEFAULT 'Cash',
  `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `restaurant_id`, `cashier_id`, `order_number`, `customer_phone`, `total`, `payment_method`, `paid_amount`, `created_at`) VALUES
(1, 6, 3, 'ORD1759482132875', NULL, 1995.00, '', 0.00, '2025-10-03 09:02:12'),
(2, 6, 3, 'ORD1759482155163', NULL, 2100.00, 'Cash', 0.00, '2025-10-03 09:02:35'),
(3, 11, 5, 'ORD1759486045129', NULL, 525.00, 'Cash', 0.00, '2025-10-03 10:07:25'),
(4, 9, 6, 'ORD1759727363909', NULL, 682.50, 'Cash', 0.00, '2025-10-06 05:09:23'),
(5, 9, 6, 'ORD1759730050775', NULL, 1050.00, 'Cash', 0.00, '2025-10-06 05:54:10'),
(6, 9, 6, 'ORD1759828147396', NULL, 3412.50, 'Cash', 0.00, '2025-10-07 09:09:07'),
(7, 9, 6, 'ORD1759899809589', '', 514.50, 'Cash', 0.00, '2025-10-08 05:03:29'),
(8, 9, 6, 'ORD1759900002385', '0764875869', 1039.50, 'Cash', 0.00, '2025-10-08 05:06:42'),
(9, 9, 6, 'ORD1759989544989', '0764875869', 525.00, 'Cash', 0.00, '2025-10-09 05:59:04'),
(10, 9, 6, 'ORD1759993059699', '0764875869', 5145.00, 'Cash', 0.00, '2025-10-09 06:57:39'),
(11, 9, 6, 'ORD1759993584631', '0764875869', 3412.50, 'Cash', 0.00, '2025-10-09 07:06:24'),
(12, 9, 6, 'ORD1759993707672', '0764875869', 5145.00, 'Cash', 0.00, '2025-10-09 07:08:27'),
(13, 9, 6, 'ORD1759994432662', '0764875869', 5145.00, 'Cash', 0.00, '2025-10-09 07:20:32'),
(14, 9, 6, 'ORD1759998805277', '0764875869', 2572.50, 'Cash', 0.00, '2025-10-09 08:33:25'),
(15, 9, 6, 'ORD1760000117371', '0764875869', 682.50, 'Cash', 0.00, '2025-10-09 08:55:17'),
(16, 9, 6, 'ORD1760001698672', '0764875869', 514.50, 'Cash', 0.00, '2025-10-09 09:21:38'),
(17, 9, 6, 'ORD1760001702947', '0764875869', 514.50, 'Cash', 0.00, '2025-10-09 09:21:42'),
(18, 9, 6, 'ORD1760001716197', '0764875869', 682.50, 'Cash', 0.00, '2025-10-09 09:21:56'),
(19, 9, 6, 'ORD1760001739749', '0764875869', 682.50, 'Cash', 0.00, '2025-10-09 09:22:19'),
(20, 9, 6, 'ORD1760001947907', '0764875869', 1543.50, 'Cash', 0.00, '2025-10-09 09:25:47'),
(21, 9, 6, 'ORD1760003636290', '0764875869', 514.50, 'Cash', 0.00, '2025-10-09 09:53:56'),
(22, 9, 6, 'ORD1760005131324', '0764875869', 514.50, 'Cash', 0.00, '2025-10-09 10:18:51'),
(23, 9, 6, 'ORD1760007974473', '0764875869', 514.50, 'Cash', 0.00, '2025-10-09 11:06:14'),
(24, 9, 6, 'ORD1760009164788', '0764875869', 682.50, 'Cash', 0.00, '2025-10-09 11:26:04'),
(25, 9, 6, NULL, '', 0.00, 'Cash', 700.00, '2025-10-10 05:32:02'),
(26, 9, 6, NULL, '', 0.00, 'Cash', 700.00, '2025-10-10 05:34:46'),
(29, 9, 6, 'ORD1760075560962', '0764875869', 682.50, '', 700.00, '2025-10-10 05:52:40'),
(30, 9, 6, 'ORD1760075640631', '', 682.50, '', 700.00, '2025-10-10 05:54:00'),
(31, 9, 6, 'ORD1760076765578', '+94764875869', 682.50, 'Cash', 0.00, '2025-10-10 06:12:45'),
(32, 9, 6, 'ORD1760080163982', '+94', 514.50, 'Cash', 0.00, '2025-10-10 07:09:23'),
(33, 9, 6, 'ORD1760088171904', '+94764875869', 514.50, '', 0.00, '2025-10-10 09:22:51'),
(34, 9, 6, 'ORD1760090305278', '+94764875869', 682.50, 'Cash', 0.00, '2025-10-10 09:58:25'),
(35, 9, 6, 'ORD1760091909625', '+94', 525.00, '', 0.00, '2025-10-10 10:25:09'),
(36, 9, 6, 'ORD1760331444825', '+94764875869', 525.00, 'Cash', 0.00, '2025-10-13 04:57:24'),
(37, 9, 6, 'ORD1760437959679', '+94764875869', 5145.00, 'Cash', 0.00, '2025-10-14 10:32:39');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(150) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `price`) VALUES
(1, 1, NULL, 'Vegetable Kottu', 1, 900.00),
(2, 1, NULL, 'Vegetable Noodles', 1, 1000.00),
(3, 2, NULL, 'Vegetable Kottu', 1, 900.00),
(4, 2, NULL, 'Veg biriyani', 1, 1100.00),
(5, 3, NULL, 'icecream', 1, 500.00),
(6, 4, NULL, 'Apple Juice', 1, 650.00),
(7, 5, NULL, 'Orange Juice', 2, 500.00),
(8, 6, NULL, 'Apple Juice', 5, 650.00),
(9, 7, NULL, 'Caramel', 1, 490.00),
(10, 8, NULL, 'Caramel', 1, 490.00),
(11, 8, NULL, 'Papaya Juice', 1, 500.00),
(12, 9, NULL, 'Orange Juice', 1, 500.00),
(13, 10, NULL, 'Brownie', 10, 490.00),
(14, 11, NULL, 'Apple Juice', 5, 650.00),
(15, 12, NULL, 'Brownie', 10, 490.00),
(16, 13, NULL, 'Brownie', 10, 490.00),
(17, 14, NULL, 'Brownie', 5, 490.00),
(18, 15, NULL, 'Apple Juice', 1, 650.00),
(19, 16, NULL, 'Brownie', 1, 490.00),
(20, 17, NULL, 'Brownie', 1, 490.00),
(21, 18, NULL, 'Apple Juice', 1, 650.00),
(22, 19, NULL, 'Apple Juice', 1, 650.00),
(23, 20, NULL, 'Brownie', 3, 490.00),
(24, 21, NULL, 'Caramel', 1, 490.00),
(25, 22, NULL, 'Caramel', 1, 490.00),
(26, 23, NULL, 'Caramel', 1, 490.00),
(27, 24, 0, 'Apple Juice', 1, 650.00),
(28, 25, 19, '', 1, 650.00),
(29, 26, 19, '', 1, 650.00),
(32, 29, 19, 'Apple Juice', 1, 650.00),
(33, 30, 19, 'Apple Juice', 1, 650.00),
(34, 31, NULL, 'Apple Juice', 1, 650.00),
(35, 32, NULL, 'Watalappam', 1, 490.00),
(36, 33, NULL, 'Watalappam', 1, 490.00),
(37, 34, NULL, 'Apple Juice', 1, 650.00),
(38, 35, NULL, 'Papaya Juice', 1, 500.00),
(39, 36, NULL, 'Papaya Juice', 1, 500.00),
(40, 37, NULL, 'Watalappam', 10, 490.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `cost_price` decimal(10,2) DEFAULT 0.00,
  `expiry_date` date DEFAULT NULL,
  `status` enum('Available','Unavailable') DEFAULT 'Available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `category_id` int(11) DEFAULT NULL,
  `supplier_name` varchar(150) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `unit` varchar(50) DEFAULT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `low_stock_limit` int(11) DEFAULT 10,
  `expiry_alert_days` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `restaurant_id`, `product_name`, `price`, `cost_price`, `expiry_date`, `status`, `created_at`, `category_id`, `supplier_name`, `quantity`, `unit`, `barcode`, `low_stock_limit`, `expiry_alert_days`) VALUES
(1, 4, 'veg biriyani', 1100.00, 0.00, NULL, 'Available', '2025-10-02 10:26:53', 1, NULL, 0, NULL, NULL, 10, NULL),
(2, 6, 'Garlic fry', 1300.00, 0.00, NULL, 'Available', '2025-10-03 06:13:20', 2, NULL, 0, NULL, NULL, 10, NULL),
(3, 6, 'Veg biriyani', 1100.00, 0.00, NULL, 'Available', '2025-10-03 06:13:36', 3, NULL, 0, NULL, NULL, 10, NULL),
(4, 6, 'Plain rice', 700.00, 0.00, NULL, 'Available', '2025-10-03 06:13:51', 4, NULL, 0, NULL, NULL, 10, NULL),
(5, 6, 'Vegetable Noodles', 1000.00, 0.00, NULL, 'Available', '2025-10-03 06:14:30', 5, NULL, 0, NULL, NULL, 10, NULL),
(6, 6, 'Vegetable Kottu', 900.00, 0.00, NULL, 'Available', '2025-10-03 06:15:03', 6, NULL, 0, NULL, NULL, 10, NULL),
(7, 6, 'Chilli gobi', 1100.00, 0.00, NULL, 'Available', '2025-10-03 09:06:07', 2, NULL, 0, NULL, NULL, 10, NULL),
(8, 6, 'Paneer Biriyani', 1100.00, 0.00, NULL, 'Available', '2025-10-03 09:06:42', 3, NULL, 0, NULL, NULL, 10, NULL),
(9, 6, 'Vegetable fried rice', 800.00, 0.00, NULL, 'Available', '2025-10-03 09:09:02', 4, NULL, 0, NULL, NULL, 10, NULL),
(10, 10, 'Vanila Shakes', 650.00, 0.00, NULL, 'Available', '2025-10-03 09:20:14', 7, NULL, 0, NULL, NULL, 10, NULL),
(11, 10, 'White Chocolate Shakes', 850.00, 0.00, NULL, 'Available', '2025-10-03 09:20:56', 7, NULL, 0, NULL, NULL, 10, NULL),
(12, 10, 'Classic Foldover', 950.00, 0.00, NULL, 'Available', '2025-10-03 09:21:33', 8, NULL, 0, NULL, NULL, 10, NULL),
(13, 10, 'Crispy Chocolate Folds', 1400.00, 0.00, NULL, 'Available', '2025-10-03 09:22:22', 8, NULL, 0, NULL, NULL, 10, NULL),
(14, 10, 'Classic Grid', 950.00, 0.00, NULL, 'Available', '2025-10-03 09:22:51', 9, NULL, 0, NULL, NULL, 10, NULL),
(15, 10, 'Cinnamon Grid', 1300.00, 0.00, NULL, 'Available', '2025-10-03 09:23:26', 9, NULL, 0, NULL, NULL, 10, NULL),
(16, 10, 'Tropical blue mojito', 700.00, 0.00, NULL, 'Available', '2025-10-03 09:24:00', 10, NULL, 0, NULL, NULL, 10, NULL),
(17, 10, 'Raspberry mojito', 750.00, 0.00, NULL, 'Available', '2025-10-03 09:24:30', 10, NULL, 0, NULL, NULL, 10, NULL),
(18, 11, 'icecream', 500.00, 0.00, NULL, 'Available', '2025-10-03 09:57:27', 11, NULL, 0, NULL, NULL, 10, NULL),
(19, 9, 'Apple Juice', 650.00, 500.00, '2025-12-18', 'Available', '2025-10-06 05:09:10', 12, 'boss', 5, NULL, NULL, 15, NULL),
(20, 9, 'Orange Juice', 500.00, 300.00, '2025-12-18', 'Available', '2025-10-06 05:50:40', 12, 'boss', 19, NULL, NULL, 10, NULL),
(21, 9, 'Papaya Juice', 500.00, 300.00, '2025-10-18', 'Available', '2025-10-06 06:14:49', 12, 'boss', 7, NULL, NULL, 10, NULL),
(22, 9, 'Caramel', 490.00, 300.00, '2025-12-08', 'Available', '2025-10-06 06:15:50', 13, 'boss', 15, NULL, NULL, 10, NULL),
(23, 9, 'Brownie', 490.00, 250.00, '2025-12-09', 'Available', '2025-10-09 06:52:51', 13, 'boss', 10, NULL, NULL, 10, NULL),
(25, 9, 'Watalappam', 490.00, 0.00, NULL, 'Available', '2025-10-10 05:09:10', 13, NULL, 10, '', '', 10, NULL),
(26, 9, 'Lime Juice', 400.00, 0.00, NULL, 'Available', '2025-10-10 10:47:58', 12, NULL, 20, '', '', 10, NULL),
(29, 9, 'Hot & Spicy seafood soup', 2560.00, 1900.00, NULL, 'Available', '2025-10-16 07:10:20', 14, NULL, 10, '', '', 10, NULL),
(30, 9, 'Sweet Corn Soup', 1790.00, 1000.00, NULL, 'Available', '2025-10-17 05:01:48', 14, NULL, 10, '', '', 10, NULL),
(31, 9, 'Vanilla Milkshake', 990.00, 700.00, NULL, 'Available', '2025-10-17 11:15:12', 12, NULL, 5, '', '', 10, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_batches`
--

CREATE TABLE `product_batches` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `expiry_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_batches`
--

INSERT INTO `product_batches` (`id`, `product_id`, `restaurant_id`, `quantity`, `expiry_date`) VALUES
(1, 23, 9, 8, '2025-12-10'),
(2, 23, 9, 20, '2026-02-09'),
(3, 19, 9, 5, '2025-12-09'),
(4, 22, 9, 10, '2025-12-09'),
(6, 26, 0, 10, '2025-12-10'),
(8, 25, 0, 10, '2026-02-11'),
(9, 26, 0, 10, '2025-10-11'),
(12, 29, 0, 10, '2025-10-17'),
(13, 30, 0, 10, '2025-10-16'),
(15, 31, 0, 5, '2025-10-17');

-- --------------------------------------------------------

--
-- Table structure for table `product_stock`
--

CREATE TABLE `product_stock` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `cost_price` decimal(10,2) DEFAULT 0.00,
  `supplier_id` int(11) DEFAULT NULL,
  `purchase_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `restaurants`
--

CREATE TABLE `restaurants` (
  `id` int(11) NOT NULL,
  `restaurant_code` varchar(20) DEFAULT NULL,
  `request_id` int(11) DEFAULT NULL,
  `restaurant_name` varchar(150) NOT NULL,
  `address` text NOT NULL,
  `phone` varchar(20) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expiry_alert_days` int(11) DEFAULT 7,
  `low_stock_alert` int(11) DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `restaurants`
--

INSERT INTO `restaurants` (`id`, `restaurant_code`, `request_id`, `restaurant_name`, `address`, `phone`, `logo`, `password`, `status`, `created_at`, `expiry_alert_days`, `low_stock_alert`) VALUES
(1, NULL, 1, 'Adchaya Pathra', '63, Sir Ramanathan road, Ramanathan Road, Jaffna 40000', '0772736333', NULL, '$2y$10$vp3QIgjetR4WuukmQKl.EudxT9ozlwAs3.zw.NGJDFxja/OI6y95q', 'Active', '2025-10-02 07:07:20', 7, 10),
(4, 'REST-0004', NULL, 'Adchaya Pathra', '63, Sir Ramanathan road, Ramanathan Road, Jaffna 40000', '0772736333-1', NULL, '$2y$10$vp3QIgjetR4WuukmQKl.EudxT9ozlwAs3.zw.NGJDFxja/OI6y95q', 'Active', '2025-10-02 07:12:59', 7, 10),
(5, 'REST-0005', NULL, 'MOJU Restaurant', 'Main Street, Jaffna', '0212227573', 'uploads/logos/1759398746_524ac8d845ed.png', '$2y$10$RQCQkkwsXIrJ7ZcoGj6sruPuPB.OikQNUCkbjpp6cTQsp4LYxUDXS', 'Active', '2025-10-02 09:53:39', 7, 10),
(6, 'REST-0006', NULL, 'Singai Restaurant', '214 Stanley Road, Jaffna', '0772141214', 'uploads/logos/1759399558_c2ea5ff0983e.jpg', '$2y$10$1OXLCL.CR2q3eHKOjzkrHeoULHZ4zH5CgrgmYt3TITMSZAvtKp8QW', 'Active', '2025-10-02 10:07:32', 7, 10),
(7, 'REST-0007', NULL, 'Moonlight Restaurant', '817 Hospital road, Jaffna 40000', '0212214000', 'uploads/logos/1759401699_e0101d72a582.png', '$2y$10$K9J.yfokd56FQfsMcMDFzODG4Iwzb4p4957BXnE9nTdp4xSBsIg1O', 'Active', '2025-10-02 10:42:19', 7, 10),
(8, 'REST-0008', NULL, 'Salem RR Biriyani', '817 Hospital road, Jaffna 40000', '09999999999', 'uploads/logos/1759465989_3ef87056e2e2.png', '$2y$10$Thi3m4.tb1UGZaqXVJKENOFg5x.0IkEqRGYdQej4lSMUdsJGyZl.i', 'Active', '2025-10-03 04:34:12', 7, 10),
(9, 'REST-0009', NULL, 'Chinese Dragon Cafe', '229 Jaffna-point pedro road, Jaffna 40000', '0117808080', 'uploads/logos/1759474828_fbc148373257.png', '$2y$10$0ouRN4WhuUzLKxOCEtLCVusR1T7zpoy3OxvS/Y/bcwhT5FEKpWWQy', 'Active', '2025-10-03 07:00:49', 10, 10),
(10, 'REST-0010', NULL, 'Zoco Jaffna', 'Science Hall road, People\'s Bank, Kannathiddy Junction, Jaffna 40000', '0755757545', 'uploads/logos/1759482818_9828e55504b9.png', '$2y$10$IjsbgqSoTeIAAhYb89uWKuhqJH4TPyXREk5nlM1f9AEZNDZ2sF082', 'Active', '2025-10-03 09:13:59', 7, 10),
(11, 'REST-0011', NULL, 'Vanni Inn', '204 brown road, Jaffna', '0776525074', 'uploads/logos/1759485324_de8dbae6d0d2.png', '$2y$10$uIBTrJAXPPLDXSBUHbyiG.59NL5CresNc3eSGWb4nTqbOR.2qCaV6', 'Active', '2025-10-03 09:55:45', 7, 10),
(12, 'REST-0012', NULL, 'A Plus Biriyani', 'sub post office, 678 Hospital St, Jaffna 40000', '0777214321', 'uploads/logos/1759403979_a12eb8200bf4.png', '$2y$10$EaXGnqQuAK0s2o/2izkYPeE3cRRXc.ZVzco5QWWem4KnIqr0skQZm', 'Active', '2025-10-04 13:16:32', 7, 10);

-- --------------------------------------------------------

--
-- Table structure for table `restaurant_requests`
--

CREATE TABLE `restaurant_requests` (
  `id` int(11) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `restaurant_name` varchar(150) NOT NULL,
  `address` text NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `restaurant_requests`
--

INSERT INTO `restaurant_requests` (`id`, `logo`, `restaurant_name`, `address`, `phone`, `password`, `status`, `created_at`) VALUES
(1, NULL, 'Adchaya Pathra', '63, Sir Ramanathan road, Ramanathan Road, Jaffna 40000', '0772736333', '$2y$10$vp3QIgjetR4WuukmQKl.EudxT9ozlwAs3.zw.NGJDFxja/OI6y95q', 'Approved', '2025-10-02 06:48:52'),
(2, 'uploads/logos/1759398746_524ac8d845ed.png', 'MOJU Restaurant', 'Main Street, Jaffna', '0212227573', '$2y$10$RQCQkkwsXIrJ7ZcoGj6sruPuPB.OikQNUCkbjpp6cTQsp4LYxUDXS', 'Approved', '2025-10-02 09:52:26'),
(3, 'uploads/logos/1759399558_c2ea5ff0983e.jpg', 'Singai Restaurant', '214 Stanley Road, Jaffna', '0772141214', '$2y$10$1OXLCL.CR2q3eHKOjzkrHeoULHZ4zH5CgrgmYt3TITMSZAvtKp8QW', 'Approved', '2025-10-02 10:05:58'),
(4, 'uploads/logos/1759401699_e0101d72a582.png', 'Moonlight Restaurant', '817 Hospital road, Jaffna 40000', '0212214000', '$2y$10$K9J.yfokd56FQfsMcMDFzODG4Iwzb4p4957BXnE9nTdp4xSBsIg1O', 'Approved', '2025-10-02 10:41:39'),
(5, 'uploads/logos/1759403979_a12eb8200bf4.png', 'A Plus Biriyani', 'sub post office, 678 Hospital St, Jaffna 40000', '0777214321', '$2y$10$EaXGnqQuAK0s2o/2izkYPeE3cRRXc.ZVzco5QWWem4KnIqr0skQZm', 'Approved', '2025-10-02 11:19:39'),
(6, 'uploads/logos/1759465989_3ef87056e2e2.png', 'Salem RR Biriyani', '817 Hospital road, Jaffna 40000', '09999999999', '$2y$10$Thi3m4.tb1UGZaqXVJKENOFg5x.0IkEqRGYdQej4lSMUdsJGyZl.i', 'Approved', '2025-10-03 04:33:10'),
(7, 'uploads/logos/1759474828_fbc148373257.png', 'Chinese Dragon Cafe', '229 Jaffna-point pedro road, Jaffna 40000', '0117808080', '$2y$10$0ouRN4WhuUzLKxOCEtLCVusR1T7zpoy3OxvS/Y/bcwhT5FEKpWWQy', 'Approved', '2025-10-03 07:00:28'),
(8, 'uploads/logos/1759482818_9828e55504b9.png', 'Zoco Jaffna', 'Science Hall road, People\'s Bank, Kannathiddy Junction, Jaffna 40000', '0755757545', '$2y$10$IjsbgqSoTeIAAhYb89uWKuhqJH4TPyXREk5nlM1f9AEZNDZ2sF082', 'Approved', '2025-10-03 09:13:38'),
(9, 'uploads/logos/1759485324_de8dbae6d0d2.png', 'Vanni Inn', '204 brown road, Jaffna', '0776525074', '$2y$10$uIBTrJAXPPLDXSBUHbyiG.59NL5CresNc3eSGWb4nTqbOR.2qCaV6', 'Approved', '2025-10-03 09:55:24'),
(10, 'uploads/logos/1759723126_76ec530063ae.jpg', 'Fort Corner', 'Near the panni park circular Junction Beach,\r\nPannai Bridge,\r\nJaffna 40000', '0763636366', '$2y$10$fTwyaJRvFUFcszPAG3rvROrKy9InsZIrI3DfijsUzF5PI9dC3PnWu', 'Pending', '2025-10-06 03:58:46');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `low_stock_limit` int(11) DEFAULT 10,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expiry_alert_days` int(11) DEFAULT 7
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `restaurant_id`, `low_stock_limit`, `updated_at`, `expiry_alert_days`) VALUES
(1, 1, 5, '2025-10-09 05:03:56', 7),
(2, 9, 10, '2025-10-17 11:24:57', 10),
(3, 9, 10, '2025-10-17 11:24:57', 10),
(4, 9, 10, '2025-10-17 11:24:57', 10),
(5, 9, 10, '2025-10-17 11:24:57', 10);

-- --------------------------------------------------------

--
-- Table structure for table `super_admins`
--

CREATE TABLE `super_admins` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `super_admins`
--

INSERT INTO `super_admins` (`id`, `name`, `email`, `password`, `created_at`) VALUES
(1, 'Admin', 'admin@gmail.com', '$2y$10$MmxPHsG5JK7WPnVMOqXDrOUdcWyR1.6RL0dDcJH3K8dufeeUjC5BK', '2025-10-02 06:57:09'),
(5, 'kamshi', 'kamshi18@gmail.com', '$2y$10$mbQT518qpUFxLTqM703xye.1tDaDm3bb2RkIoA/oWzxLy6SO9Lwo.', '2025-10-02 07:02:07');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `allowance_rules`
--
ALTER TABLE `allowance_rules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cashiers`
--
ALTER TABLE `cashiers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD KEY `restaurant_id` (`restaurant_id`);

--
-- Indexes for table `cashier_advance`
--
ALTER TABLE `cashier_advance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cashier_id` (`cashier_id`);

--
-- Indexes for table `cashier_allowance_rules`
--
ALTER TABLE `cashier_allowance_rules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cashier_salary`
--
ALTER TABLE `cashier_salary`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cashier_id` (`cashier_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `restaurant_id` (`restaurant_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD KEY `restaurant_id` (`restaurant_id`);

--
-- Indexes for table `deleted_cashiers`
--
ALTER TABLE `deleted_cashiers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `restaurant_id` (`restaurant_id`),
  ADD KEY `cashier_id` (`cashier_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `restaurant_id` (`restaurant_id`),
  ADD KEY `products_ibfk_2` (`category_id`);

--
-- Indexes for table `product_batches`
--
ALTER TABLE `product_batches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product_stock`
--
ALTER TABLE `product_stock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `fk_stock_supplier` (`supplier_id`);

--
-- Indexes for table `restaurants`
--
ALTER TABLE `restaurants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD KEY `request_id` (`request_id`);

--
-- Indexes for table `restaurant_requests`
--
ALTER TABLE `restaurant_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `restaurant_id` (`restaurant_id`);

--
-- Indexes for table `super_admins`
--
ALTER TABLE `super_admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `restaurant_id` (`restaurant_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `restaurant_id` (`restaurant_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `allowance_rules`
--
ALTER TABLE `allowance_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cashiers`
--
ALTER TABLE `cashiers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `cashier_advance`
--
ALTER TABLE `cashier_advance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `cashier_allowance_rules`
--
ALTER TABLE `cashier_allowance_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cashier_salary`
--
ALTER TABLE `cashier_salary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deleted_cashiers`
--
ALTER TABLE `deleted_cashiers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `product_batches`
--
ALTER TABLE `product_batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `product_stock`
--
ALTER TABLE `product_stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `restaurants`
--
ALTER TABLE `restaurants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `restaurant_requests`
--
ALTER TABLE `restaurant_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `super_admins`
--
ALTER TABLE `super_admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cashiers`
--
ALTER TABLE `cashiers`
  ADD CONSTRAINT `cashiers_ibfk_1` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cashier_advance`
--
ALTER TABLE `cashier_advance`
  ADD CONSTRAINT `cashier_advance_ibfk_1` FOREIGN KEY (`cashier_id`) REFERENCES `cashiers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cashier_salary`
--
ALTER TABLE `cashier_salary`
  ADD CONSTRAINT `fk_cashier_salary_cashier` FOREIGN KEY (`cashier_id`) REFERENCES `cashiers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`cashier_id`) REFERENCES `cashiers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_batches`
--
ALTER TABLE `product_batches`
  ADD CONSTRAINT `product_batches_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `product_stock`
--
ALTER TABLE `product_stock`
  ADD CONSTRAINT `fk_stock_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `product_stock_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `restaurants`
--
ALTER TABLE `restaurants`
  ADD CONSTRAINT `restaurants_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `restaurant_requests` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `settings`
--
ALTER TABLE `settings`
  ADD CONSTRAINT `settings_ibfk_1` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD CONSTRAINT `fk_suppliers_restaurant` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
