-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 14, 2026 at 12:53 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `inventaris`
--

-- --------------------------------------------------------

--
-- Table structure for table `administration_audit_logs`
--

CREATE TABLE `administration_audit_logs` (
  `id` bigint UNSIGNED NOT NULL,
  `actor_id` bigint UNSIGNED DEFAULT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_id` bigint UNSIGNED DEFAULT NULL,
  `changes` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `administration_audit_logs`
--

INSERT INTO `administration_audit_logs` (`id`, `actor_id`, `action`, `subject_type`, `subject_id`, `changes`, `created_at`, `updated_at`) VALUES
(1, 1, 'master_data.created', 'App\\Models\\Category', 1, '{\"after\": {\"id\": 1, \"name\": \"ayam\", \"is_active\": \"1\", \"description\": null}}', '2026-10-07 22:49:18', '2026-10-07 22:49:18'),
(2, 1, 'master_data.created', 'App\\Models\\Category', 2, '{\"after\": {\"id\": 2, \"name\": \"sapi\", \"is_active\": \"1\", \"description\": null}}', '2026-10-07 22:51:20', '2026-10-07 22:51:20'),
(3, 1, 'master_data.created', 'App\\Models\\Location', 1, '{\"after\": {\"id\": 1, \"code\": \"GDG-01\", \"name\": \"Main Kitchen\", \"type\": \"warehouse\", \"address\": \"jlnkaka\", \"is_active\": \"1\", \"description\": null}}', '2026-10-07 23:11:16', '2026-10-07 23:11:16'),
(4, 1, 'master_data.created', 'App\\Models\\Product', 1, '{\"after\": {\"id\": 1, \"sku\": \"SKU-BFF-001\", \"name\": \"ayam pop\", \"unit_id\": \"1\", \"is_active\": \"1\", \"category_id\": \"1\", \"description\": null}}', '2026-10-07 23:11:52', '2026-10-07 23:11:52'),
(5, 1, 'master_data.updated', 'App\\Models\\Product', 1, '{\"after\": {\"id\": 1, \"sku\": \"SKU-BFF-001\", \"name\": \"ayam pop\", \"unit_id\": \"1\", \"is_active\": \"1\", \"image_path\": \"products/22f6d053-8f08-4cc5-866d-83dc0a45e867.webp\", \"category_id\": \"1\", \"description\": null}, \"before\": {\"id\": 1, \"sku\": \"SKU-BFF-001\", \"name\": \"ayam pop\", \"unit_id\": 1, \"is_active\": 1, \"image_path\": null, \"category_id\": 1, \"description\": null}}', '2026-10-08 00:22:29', '2026-10-08 00:22:29'),
(6, 1, 'stock_in.received', 'App\\Models\\StockIn', 1, '{\"reference\": \"GR-2026-01\", \"item_count\": 1, \"quantities\": {\"stock-in-row-d-1791444355098-609\": {\"quantity\": \"2\", \"product_id\": \"1\"}}, \"location_id\": \"1\"}', '2026-10-08 00:26:19', '2026-10-08 00:26:19'),
(7, 1, 'master_data.created', 'App\\Models\\Product', 2, '{\"after\": {\"id\": 2, \"sku\": \"SKU-BFF-002\", \"name\": \"Ayam Bakar\", \"unit_id\": \"1\", \"is_active\": \"1\", \"image_path\": \"products/4695f6eb-b4d9-4758-a2ad-edfa83608b8c.webp\", \"category_id\": \"1\", \"description\": null}}', '2026-10-09 01:34:31', '2026-10-09 01:34:31'),
(8, 1, 'stock_in.received', 'App\\Models\\StockIn', 2, '{\"reference\": \"GR-2026-02\", \"item_count\": 1, \"quantities\": {\"stock-in-row-d-1791534879481-777\": {\"quantity\": \"2\", \"product_id\": \"2\"}}, \"location_id\": \"1\"}', '2026-10-09 01:35:10', '2026-10-09 01:35:10'),
(9, 1, 'master_data.created', 'App\\Models\\Location', 2, '{\"after\": {\"id\": 2, \"code\": \"GDG-02\", \"name\": \"Billiard\", \"type\": \"outlet\", \"address\": null, \"is_active\": \"1\", \"description\": null}}', '2026-10-09 02:06:20', '2026-10-09 02:06:20'),
(10, 1, 'user.created', 'App\\Models\\User', 2, '{\"after\": {\"name\": \"PIC\", \"email\": \"pic@gmail.com\", \"roles\": [\"pic_outlet\"], \"is_active\": true, \"location_id\": \"2\"}}', '2026-10-09 02:14:34', '2026-10-09 02:14:34'),
(11, 1, 'user.created', 'App\\Models\\User', 3, '{\"after\": {\"name\": \"finance\", \"email\": \"finance@gmail.com\", \"roles\": [\"finance\"], \"is_active\": true, \"location_id\": null}}', '2026-10-09 02:17:33', '2026-10-09 02:17:33'),
(12, 1, 'user.created', 'App\\Models\\User', 4, '{\"after\": {\"name\": \"staff\", \"email\": \"staff@gmail.com\", \"roles\": [\"staff_gudang\"], \"is_active\": true, \"location_id\": null}}', '2026-10-09 02:18:06', '2026-10-09 02:18:06'),
(13, 1, 'user.created', 'App\\Models\\User', 5, '{\"after\": {\"name\": \"management\", \"email\": \"management@gmail.com\", \"roles\": [\"management\"], \"is_active\": true, \"location_id\": null}}', '2026-10-09 02:18:57', '2026-10-09 02:18:57');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('laravel-cache-5c785c036466adea360111aa28563bfd556b5fba', 'i:1;', 1791537919),
('laravel-cache-5c785c036466adea360111aa28563bfd556b5fba:timer', 'i:1791537919;', 1791537919),
('laravel-cache-illuminate:queue:restart', 'i:1791535505;', 2106895505);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `is_active`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'ayam', NULL, 1, NULL, '2026-10-07 22:49:18', '2026-10-07 22:49:18'),
(2, 'sapi', NULL, 1, NULL, '2026-10-07 22:51:20', '2026-10-07 22:51:20'),
(3, 'Sayur & Buah', 'Produk segar sayuran dan buah-buahan', 1, NULL, '2026-10-07 23:01:17', '2026-10-07 23:01:17'),
(4, 'Daging & Ikan', 'Daging segar, frozen meat, dan produk ikan', 1, NULL, '2026-10-07 23:01:17', '2026-10-07 23:01:17'),
(5, 'Dairy & Telur', 'Susu, keju, yogurt, dan telur', 1, NULL, '2026-10-07 23:01:17', '2026-10-07 23:01:17'),
(6, 'Bahan Kering', 'Beras, tepung, gula, minyak, dan bahan pokok kering', 1, NULL, '2026-10-07 23:01:17', '2026-10-07 23:01:17'),
(7, 'Bumbu & Rempah', 'Bumbu masak, rempah, dan saus', 1, NULL, '2026-10-07 23:01:17', '2026-10-07 23:01:17'),
(8, 'Minuman', 'Minuman kemasan, jus, dan bahan minuman', 1, NULL, '2026-10-07 23:01:17', '2026-10-07 23:01:17'),
(9, 'Packaging', 'Kemasan, wadah, plastik, dan label', 1, NULL, '2026-10-07 23:01:17', '2026-10-07 23:01:17'),
(10, 'Peralatan', 'Peralatan dapur dan operasional', 1, NULL, '2026-10-07 23:01:17', '2026-10-07 23:01:17'),
(11, 'Kebersihan', 'Produk kebersihan dan sanitasi', 1, NULL, '2026-10-07 23:01:17', '2026-10-07 23:01:17'),
(12, 'Lainnya', 'Kategori umum untuk produk lainnya', 1, NULL, '2026-10-07 23:01:17', '2026-10-07 23:01:17');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` bigint UNSIGNED NOT NULL,
  `code` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`id`, `code`, `name`, `type`, `address`, `description`, `is_active`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'GDG-01', 'Main Kitchen', 'warehouse', 'jlnkaka', NULL, 1, NULL, '2026-10-07 23:11:15', '2026-10-07 23:11:15'),
(2, 'GDG-02', 'Billiard', 'outlet', NULL, NULL, 1, NULL, '2026-10-09 02:06:19', '2026-10-09 02:06:19');

-- --------------------------------------------------------

--
-- Table structure for table `low_stock_alerts`
--

CREATE TABLE `low_stock_alerts` (
  `id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `location_id` bigint UNSIGNED NOT NULL,
  `minimum_quantity` decimal(14,3) NOT NULL,
  `stock_quantity` decimal(14,3) NOT NULL,
  `triggered_at` timestamp NOT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `low_stock_alerts`
--

INSERT INTO `low_stock_alerts` (`id`, `product_id`, `location_id`, `minimum_quantity`, `stock_quantity`, `triggered_at`, `resolved_at`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 2.000, 2.000, '2026-10-08 20:17:38', NULL, '2026-10-08 20:17:38', '2026-10-08 20:17:38'),
(2, 2, 1, 2.000, 2.000, '2026-10-09 01:40:11', NULL, '2026-10-09 01:40:11', '2026-10-09 01:40:11');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_10_07_000001_create_roles_and_permissions_tables', 1),
(5, '2026_10_07_000002_add_status_and_soft_deletes_to_users_table', 1),
(6, '2026_10_07_000003_create_administration_audit_logs_table', 1),
(7, '2026_10_07_000004_create_master_data_tables', 2),
(8, '2026_10_08_000001_add_image_path_to_products_table', 3),
(9, '2026_10_08_000002_create_stock_movements_table', 3),
(10, '2026_10_08_000003_create_stock_ins_tables', 4),
(11, '2026_10_08_000004_create_stock_outs_tables', 5),
(12, '2026_10_08_000005_create_stock_opnames_tables', 6),
(13, '2026_10_08_000006_add_count_snapshot_to_stock_opname_items', 7),
(14, '2026_10_08_000007_create_stock_adjustments_table', 8),
(15, '2026_10_08_000008_create_purchasing_tables', 9),
(16, '2026_10_08_000009_add_batch_tracking_to_stock_movements', 10),
(17, '2026_10_08_000010_create_stock_transfers_tables', 11),
(18, '2026_10_08_000011_add_batch_received_at_to_stock_in_items', 12),
(19, '2026_10_08_000012_complete_stock_transfer_workflow', 13),
(20, '2026_10_08_000013_create_stock_transfer_requests', 13),
(21, '2026_10_09_000001_create_low_stock_alerts', 14),
(22, '2026_10_09_000002_create_whatsapp_alert_tables', 15);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint UNSIGNED NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `type`, `notifiable_type`, `notifiable_id`, `data`, `read_at`, `created_at`, `updated_at`) VALUES
('5c0e4498-a45c-466f-8251-f2e68520f8e1', 'App\\Notifications\\LowStockNotification', 'App\\Models\\User', 1, '{\"alert_id\":1,\"product_id\":1,\"product\":\"ayam pop\",\"sku\":\"SKU-BFF-001\",\"location_id\":1,\"location\":\"Main Kitchen\",\"stock_quantity\":2,\"minimum_quantity\":2,\"message\":\"Stok ayam pop di Main Kitchen berada di bawah batas minimum.\",\"url\":\"http:\\/\\/127.0.0.1:8000\\/low-stock\",\"triggered_at\":\"2026-10-09T03:17:38+00:00\"}', '2026-10-08 20:17:49', '2026-10-08 20:17:39', '2026-10-08 20:17:49'),
('905e9b88-51b0-477a-bf24-328051fffaef', 'App\\Notifications\\LowStockNotification', 'App\\Models\\User', 1, '{\"alert_id\":2,\"product_id\":2,\"product\":\"Ayam Bakar\",\"sku\":\"SKU-BFF-002\",\"location_id\":1,\"location\":\"Main Kitchen\",\"stock_quantity\":2,\"minimum_quantity\":2,\"message\":\"Stok Ayam Bakar di Main Kitchen berada di bawah batas minimum.\",\"url\":\"http:\\/\\/127.0.0.1:8000\\/low-stock\",\"triggered_at\":\"2026-10-09T08:40:11+00:00\"}', NULL, '2026-10-09 01:40:12', '2026-10-09 01:40:12');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `label`, `created_at`, `updated_at`) VALUES
(1, 'app.access', 'Access application pages', '2026-10-07 06:41:27', '2026-10-07 06:41:27'),
(2, 'users.manage', 'Manage users and roles', '2026-10-07 06:41:27', '2026-10-07 06:41:27'),
(3, 'stock.manage', 'Record stock movements', '2026-10-07 23:17:34', '2026-10-07 23:17:34'),
(4, 'opname.manage', 'Create and count stock opname', '2026-10-07 23:36:02', '2026-10-07 23:36:02'),
(5, 'opname.approve', 'Review and approve stock opname', '2026-10-07 23:36:03', '2026-10-07 23:36:03'),
(6, 'adjust.manage', 'Manage stock adjustments', '2026-10-07 23:41:15', '2026-10-07 23:41:15'),
(7, 'purchasing.manage', 'Create purchase requests and orders', '2026-10-07 23:54:28', '2026-10-07 23:54:28'),
(8, 'purchasing.approve', 'Approve or reject purchase requests', '2026-10-07 23:54:28', '2026-10-07 23:54:28'),
(9, 'transfer.manage', 'Dispatch stock transfers', '2026-10-08 00:47:06', '2026-10-08 00:47:06'),
(10, 'transfer.receive', 'Receive stock transfers at outlets', '2026-10-08 00:47:06', '2026-10-08 00:47:06'),
(11, 'reports.view', 'View inventory and operational reports', '2026-10-08 19:18:08', '2026-10-08 19:18:08'),
(12, 'low_stock.view', 'View low stock alerts and history', '2026-10-08 19:38:46', '2026-10-08 19:38:46'),
(13, 'low_stock.manage', 'Manage per-location minimum stock thresholds', '2026-10-08 19:38:46', '2026-10-08 19:38:46'),
(14, 'whatsapp.manage', 'Configure WhatsApp low stock alerts', '2026-10-08 19:50:06', '2026-10-08 19:50:06');

-- --------------------------------------------------------

--
-- Table structure for table `permission_role`
--

CREATE TABLE `permission_role` (
  `permission_id` bigint UNSIGNED NOT NULL,
  `role_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permission_role`
--

INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES
(1, 1),
(2, 1),
(3, 1),
(4, 1),
(5, 1),
(6, 1),
(7, 1),
(8, 1),
(9, 1),
(10, 1),
(11, 1),
(12, 1),
(13, 1),
(14, 1),
(1, 2),
(3, 2),
(4, 2),
(9, 2),
(11, 2),
(12, 2),
(1, 3),
(5, 3),
(7, 3),
(11, 3),
(1, 4),
(11, 4),
(1, 5),
(10, 5),
(11, 5),
(12, 5),
(1, 6),
(11, 6);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` bigint UNSIGNED NOT NULL,
  `sku` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_id` bigint UNSIGNED NOT NULL,
  `unit_id` bigint UNSIGNED NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `sku`, `name`, `category_id`, `unit_id`, `description`, `image_path`, `is_active`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'SKU-BFF-001', 'ayam pop', 1, 1, NULL, 'products/22f6d053-8f08-4cc5-866d-83dc0a45e867.webp', 1, NULL, '2026-10-07 23:11:51', '2026-10-08 00:22:29'),
(2, 'SKU-BFF-002', 'Ayam Bakar', 1, 1, NULL, 'products/4695f6eb-b4d9-4758-a2ad-edfa83608b8c.webp', 1, NULL, '2026-10-09 01:34:31', '2026-10-09 01:34:31');

-- --------------------------------------------------------

--
-- Table structure for table `product_location_stock_thresholds`
--

CREATE TABLE `product_location_stock_thresholds` (
  `id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `location_id` bigint UNSIGNED NOT NULL,
  `minimum_quantity` decimal(14,3) NOT NULL DEFAULT '0.000',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_location_stock_thresholds`
--

INSERT INTO `product_location_stock_thresholds` (`id`, `product_id`, `location_id`, `minimum_quantity`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 2.000, '2026-10-08 20:17:38', '2026-10-08 20:17:38'),
(2, 2, 1, 2.000, '2026-10-09 01:40:11', '2026-10-09 01:40:11');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` bigint UNSIGNED NOT NULL,
  `reference` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `purchase_request_id` bigint UNSIGNED NOT NULL,
  `supplier_id` bigint UNSIGNED NOT NULL,
  `location_id` bigint UNSIGNED NOT NULL,
  `created_by` bigint UNSIGNED NOT NULL,
  `expected_delivery` date DEFAULT NULL,
  `status` enum('issued','received') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'issued',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `received_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

CREATE TABLE `purchase_order_items` (
  `id` bigint UNSIGNED NOT NULL,
  `purchase_order_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `quantity` decimal(14,3) NOT NULL,
  `unit_price` decimal(14,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_requests`
--

CREATE TABLE `purchase_requests` (
  `id` bigint UNSIGNED NOT NULL,
  `reference` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `requested_by` bigint UNSIGNED NOT NULL,
  `required_date` date DEFAULT NULL,
  `status` enum('pending_approval','approved','rejected','ordered') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending_approval',
  `reviewed_by` bigint UNSIGNED DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `review_note` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_request_items`
--

CREATE TABLE `purchase_request_items` (
  `id` bigint UNSIGNED NOT NULL,
  `purchase_request_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `quantity` decimal(14,3) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `label`, `created_at`, `updated_at`) VALUES
(1, 'super_admin', 'Super Admin', '2026-10-07 06:41:27', '2026-10-07 06:41:27'),
(2, 'staff_gudang', 'Staff Gudang', '2026-10-07 06:41:27', '2026-10-07 06:41:27'),
(3, 'purchasing', 'Purchasing', '2026-10-07 06:41:27', '2026-10-07 06:41:27'),
(4, 'finance', 'Finance', '2026-10-07 06:41:27', '2026-10-07 06:41:27'),
(5, 'pic_outlet', 'PIC Outlet', '2026-10-07 20:30:55', '2026-10-07 20:30:55'),
(6, 'management', 'Management', '2026-10-07 20:30:55', '2026-10-07 20:30:55');

-- --------------------------------------------------------

--
-- Table structure for table `role_user`
--

CREATE TABLE `role_user` (
  `role_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_user`
--

INSERT INTO `role_user` (`role_id`, `user_id`) VALUES
(1, 1),
(5, 2),
(4, 3),
(2, 4),
(6, 5);

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('PWUS1moYHJCag7j3Re6Ajc4eOeVxOOnbD9KkYKyA', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiQnhBR1dLalJocXRXenpOWUl1SnJRM0xETU5mWTg3SXJBTE1mNlFjUiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9sb2dpbiI7czo1OiJyb3V0ZSI7czo1OiJsb2dpbiI7fX0=', 1791537895);

-- --------------------------------------------------------

--
-- Table structure for table `stock_adjustments`
--

CREATE TABLE `stock_adjustments` (
  `id` bigint UNSIGNED NOT NULL,
  `reference` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `location_id` bigint UNSIGNED NOT NULL,
  `created_by` bigint UNSIGNED NOT NULL,
  `direction` enum('increase','decrease') COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(14,3) NOT NULL,
  `old_quantity` decimal(14,3) NOT NULL,
  `new_quantity` decimal(14,3) NOT NULL,
  `reason` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_ins`
--

CREATE TABLE `stock_ins` (
  `id` bigint UNSIGNED NOT NULL,
  `reference` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `supplier_id` bigint UNSIGNED DEFAULT NULL,
  `purchase_order_id` bigint UNSIGNED DEFAULT NULL,
  `source` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_id` bigint UNSIGNED NOT NULL,
  `received_by` bigint UNSIGNED NOT NULL,
  `received_at` date NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_ins`
--

INSERT INTO `stock_ins` (`id`, `reference`, `supplier_id`, `purchase_order_id`, `source`, `location_id`, `received_by`, `received_at`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'GR-2026-01', NULL, NULL, 'Supplier', 1, 1, '2026-10-08', NULL, '2026-10-08 00:26:19', '2026-10-08 00:26:19'),
(2, 'GR-2026-02', NULL, NULL, 'Supplier', 1, 1, '2026-10-09', NULL, '2026-10-09 01:35:09', '2026-10-09 01:35:09');

-- --------------------------------------------------------

--
-- Table structure for table `stock_in_items`
--

CREATE TABLE `stock_in_items` (
  `id` bigint UNSIGNED NOT NULL,
  `stock_in_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `quantity` decimal(14,3) NOT NULL,
  `batch_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `batch_received_at` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_in_items`
--

INSERT INTO `stock_in_items` (`id`, `stock_in_id`, `product_id`, `quantity`, `batch_number`, `expiry_date`, `batch_received_at`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 2.000, NULL, NULL, NULL, '2026-10-08 00:26:19', '2026-10-08 00:26:19'),
(2, 2, 2, 2.000, NULL, '2026-10-11', NULL, '2026-10-09 01:35:10', '2026-10-09 01:35:10');

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `location_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `batch_id` bigint UNSIGNED DEFAULT NULL,
  `stock_in_item_id` bigint UNSIGNED DEFAULT NULL,
  `stock_out_item_id` bigint UNSIGNED DEFAULT NULL,
  `stock_opname_item_id` bigint UNSIGNED DEFAULT NULL,
  `stock_adjustment_id` bigint UNSIGNED DEFAULT NULL,
  `type` enum('in','out') COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(14,3) NOT NULL,
  `note` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `product_id`, `location_id`, `user_id`, `batch_id`, `stock_in_item_id`, `stock_out_item_id`, `stock_opname_item_id`, `stock_adjustment_id`, `type`, `quantity`, `note`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, NULL, 1, NULL, NULL, NULL, 'in', 2.000, 'Stock In GR-2026-01', '2026-10-08 00:26:19', '2026-10-08 00:26:19'),
(2, 2, 1, 1, 2, 2, NULL, NULL, NULL, 'in', 2.000, 'Stock In GR-2026-02', '2026-10-09 01:35:10', '2026-10-09 01:35:10');

-- --------------------------------------------------------

--
-- Table structure for table `stock_opnames`
--

CREATE TABLE `stock_opnames` (
  `id` bigint UNSIGNED NOT NULL,
  `reference` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `period_type` enum('weekly','month_end') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('counting','pending_approval','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'counting',
  `location_id` bigint UNSIGNED NOT NULL,
  `counted_by` bigint UNSIGNED NOT NULL,
  `reviewed_by` bigint UNSIGNED DEFAULT NULL,
  `snapshot_at` timestamp NOT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `movement_cutoff_id` bigint UNSIGNED DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `review_note` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_opname_items`
--

CREATE TABLE `stock_opname_items` (
  `id` bigint UNSIGNED NOT NULL,
  `stock_opname_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `system_quantity` decimal(14,3) NOT NULL,
  `system_quantity_at_count` decimal(14,3) DEFAULT NULL,
  `physical_quantity` decimal(14,3) DEFAULT NULL,
  `variance` decimal(14,3) NOT NULL DEFAULT '0.000',
  `adjustment_quantity` decimal(14,3) NOT NULL DEFAULT '0.000',
  `reason` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_outs`
--

CREATE TABLE `stock_outs` (
  `id` bigint UNSIGNED NOT NULL,
  `reference` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `destination` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_id` bigint UNSIGNED NOT NULL,
  `issued_by` bigint UNSIGNED NOT NULL,
  `issued_at` date NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_out_items`
--

CREATE TABLE `stock_out_items` (
  `id` bigint UNSIGNED NOT NULL,
  `stock_out_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `batch_id` bigint UNSIGNED DEFAULT NULL,
  `quantity` decimal(14,3) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_transfers`
--

CREATE TABLE `stock_transfers` (
  `id` bigint UNSIGNED NOT NULL,
  `reference` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_location_id` bigint UNSIGNED NOT NULL,
  `destination_location_id` bigint UNSIGNED NOT NULL,
  `stock_out_id` bigint UNSIGNED DEFAULT NULL,
  `stock_in_id` bigint UNSIGNED DEFAULT NULL,
  `stock_transfer_request_id` bigint UNSIGNED DEFAULT NULL,
  `dispatched_by` bigint UNSIGNED NOT NULL,
  `received_by` bigint UNSIGNED DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'in_transit',
  `dispatched_at` timestamp NOT NULL,
  `received_at` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_transfer_items`
--

CREATE TABLE `stock_transfer_items` (
  `id` bigint UNSIGNED NOT NULL,
  `stock_transfer_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `source_batch_id` bigint UNSIGNED DEFAULT NULL,
  `destination_batch_id` bigint UNSIGNED DEFAULT NULL,
  `stock_out_item_id` bigint UNSIGNED DEFAULT NULL,
  `stock_in_item_id` bigint UNSIGNED DEFAULT NULL,
  `quantity` decimal(14,3) NOT NULL,
  `received_quantity` decimal(14,3) NOT NULL DEFAULT '0.000',
  `discrepancy_quantity` decimal(14,3) NOT NULL DEFAULT '0.000',
  `discrepancy_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_transfer_receipts`
--

CREATE TABLE `stock_transfer_receipts` (
  `id` bigint UNSIGNED NOT NULL,
  `stock_transfer_id` bigint UNSIGNED NOT NULL,
  `stock_in_id` bigint UNSIGNED DEFAULT NULL,
  `received_by` bigint UNSIGNED DEFAULT NULL,
  `received_at` timestamp NOT NULL,
  `is_final` tinyint(1) NOT NULL DEFAULT '0',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_transfer_receipt_items`
--

CREATE TABLE `stock_transfer_receipt_items` (
  `id` bigint UNSIGNED NOT NULL,
  `stock_transfer_receipt_id` bigint UNSIGNED NOT NULL,
  `stock_transfer_item_id` bigint UNSIGNED NOT NULL,
  `stock_in_item_id` bigint UNSIGNED DEFAULT NULL,
  `quantity` decimal(14,3) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_transfer_requests`
--

CREATE TABLE `stock_transfer_requests` (
  `id` bigint UNSIGNED NOT NULL,
  `reference` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_id` bigint UNSIGNED NOT NULL,
  `requested_by` bigint UNSIGNED DEFAULT NULL,
  `picked_by` bigint UNSIGNED DEFAULT NULL,
  `stock_transfer_id` bigint UNSIGNED DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `requested_at` timestamp NOT NULL,
  `picked_at` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_transfer_request_items`
--

CREATE TABLE `stock_transfer_request_items` (
  `id` bigint UNSIGNED NOT NULL,
  `stock_transfer_request_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `quantity` decimal(14,3) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_person` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `units`
--

CREATE TABLE `units` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `symbol` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `units`
--

INSERT INTO `units` (`id`, `name`, `symbol`, `description`, `is_active`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'Kilogram', 'kg', 'Satuan berat', 1, NULL, '2026-10-07 23:01:17', '2026-10-07 23:01:17'),
(2, 'Gram', 'g', 'Satuan berat kecil', 1, NULL, '2026-10-07 23:01:17', '2026-10-07 23:01:17'),
(3, 'Ton', 'ton', 'Satuan berat besar', 1, NULL, '2026-10-07 23:01:17', '2026-10-07 23:01:17'),
(4, 'Liter', 'L', 'Satuan volume', 1, NULL, '2026-10-07 23:01:17', '2026-10-07 23:01:17'),
(5, 'Mililiter', 'mL', 'Satuan volume kecil', 1, NULL, '2026-10-07 23:01:17', '2026-10-07 23:01:17'),
(6, 'Pcs', 'pcs', 'Per buah / satuan', 1, NULL, '2026-10-07 23:01:17', '2026-10-07 23:01:17'),
(7, 'Lusin', 'lsn', '12 buah', 1, NULL, '2026-10-07 23:01:17', '2026-10-07 23:01:17'),
(8, 'Pack', 'pack', 'Per kemasan pack', 1, NULL, '2026-10-07 23:01:17', '2026-10-07 23:01:17'),
(9, 'Dus', 'dus', 'Per dus / karton', 1, NULL, '2026-10-07 23:01:17', '2026-10-07 23:01:17'),
(10, 'Karton', 'ktn', 'Per karton besar', 1, NULL, '2026-10-07 23:01:17', '2026-10-07 23:01:17'),
(11, 'Sachet', 'sct', 'Per sachet', 1, NULL, '2026-10-07 23:01:17', '2026-10-07 23:01:17'),
(12, 'Botol', 'btl', 'Per botol', 1, NULL, '2026-10-07 23:01:17', '2026-10-07 23:01:17'),
(13, 'Kaleng', 'klg', 'Per kaleng', 1, NULL, '2026-10-07 23:01:17', '2026-10-07 23:01:17'),
(14, 'Lembar', 'lbr', 'Per lembar', 1, NULL, '2026-10-07 23:01:17', '2026-10-07 23:01:17'),
(15, 'Roll', 'roll', 'Per roll', 1, NULL, '2026-10-07 23:01:17', '2026-10-07 23:01:17'),
(16, 'Ikat', 'ikt', 'Per ikat', 1, NULL, '2026-10-07 23:01:17', '2026-10-07 23:01:17'),
(17, 'Porsi', 'prs', 'Per porsi saji', 1, NULL, '2026-10-07 23:01:17', '2026-10-07 23:01:17');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `location_id` bigint UNSIGNED DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `is_active`, `location_id`, `remember_token`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Test User', 'test@example.com', NULL, '$2y$12$kU2ccxxkw0DWW/Nc4tzMUuKj7drYGaoUikX8FrMpbr/rY2gzeFV6W', 1, NULL, 'sz59SdBQbxx5rWkyc2veRtumriW2aFq2FK1KqsIU3XfCDRWBGBBz7Zrajl0B', '2026-10-07 06:41:27', '2026-10-07 06:41:27', NULL),
(2, 'PIC', 'pic@gmail.com', NULL, '$2y$12$vLiB7DJqZLgzBqpIL7JgUuw3yPfTPnaG5Zgt2i5aVSkOgnyiEN6VG', 1, 2, NULL, '2026-10-09 02:14:34', '2026-10-09 02:14:34', NULL),
(3, 'finance', 'finance@gmail.com', NULL, '$2y$12$LzRGF.qI9tvD/rKgUNZg7ujZzLNYrkhseVM4AnQussR04MbRtCm5y', 1, NULL, NULL, '2026-10-09 02:17:33', '2026-10-09 02:17:33', NULL),
(4, 'staff', 'staff@gmail.com', NULL, '$2y$12$2hkbe2HWrgraT41.zZaT2uBU25n8WR8LrR8rV2Ts657eysJwm6gvm', 1, NULL, NULL, '2026-10-09 02:18:05', '2026-10-09 02:18:05', NULL),
(5, 'management', 'management@gmail.com', NULL, '$2y$12$xQ2jySLqLbo3RyIuD6.F9ezQIHZLBI6ltUFOx1BJSbwbqPvnXqY6K', 1, NULL, NULL, '2026-10-09 02:18:56', '2026-10-09 02:18:56', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `whatsapp_alert_logs`
--

CREATE TABLE `whatsapp_alert_logs` (
  `id` bigint UNSIGNED NOT NULL,
  `low_stock_alert_id` bigint UNSIGNED NOT NULL,
  `recipient` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'queued',
  `provider_message_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `last_attempt_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `whatsapp_alert_logs`
--

INSERT INTO `whatsapp_alert_logs` (`id`, `low_stock_alert_id`, `recipient`, `status`, `provider_message_id`, `error_message`, `last_attempt_at`, `sent_at`, `created_at`, `updated_at`) VALUES
(1, 1, '6285161575431', 'failed', NULL, 'Nomor penerima sudah tidak aktif di pengaturan WhatsApp.', '2026-10-09 01:51:46', NULL, '2026-10-08 20:23:43', '2026-10-09 01:56:02'),
(2, 1, '6285759123390', 'sent', NULL, NULL, '2026-10-09 01:57:34', '2026-10-09 01:57:34', '2026-10-08 20:37:29', '2026-10-09 01:59:21'),
(3, 2, '6285759123390', 'sent', NULL, NULL, '2026-10-09 01:57:35', '2026-10-09 01:57:35', '2026-10-09 01:40:12', '2026-10-09 01:59:21');

-- --------------------------------------------------------

--
-- Table structure for table `whatsapp_alert_settings`
--

CREATE TABLE `whatsapp_alert_settings` (
  `id` bigint UNSIGNED NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '0',
  `recipients` json DEFAULT NULL,
  `message_template` text COLLATE utf8mb4_unicode_ci,
  `risk_accepted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `whatsapp_alert_settings`
--

INSERT INTO `whatsapp_alert_settings` (`id`, `enabled`, `recipients`, `message_template`, `risk_accepted_at`, `created_at`, `updated_at`) VALUES
(1, 1, '[\"6285759123390\"]', '?? *Peringatan Stok Minimum*\n\nProduk: *{product}*\nLokasi: {location}\nStok saat ini: *{stock} {unit}*\nBatas minimum: {minimum} {unit}\n\nMohon periksa dan lakukan pengadaan bila diperlukan.', '2026-10-08 20:23:43', '2026-10-08 19:55:05', '2026-10-09 02:01:38');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `administration_audit_logs`
--
ALTER TABLE `administration_audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `administration_audit_logs_actor_id_foreign` (`actor_id`),
  ADD KEY `administration_audit_logs_subject_type_subject_id_index` (`subject_type`,`subject_id`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `categories_name_unique` (`name`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `locations_code_unique` (`code`),
  ADD KEY `locations_type_index` (`type`);

--
-- Indexes for table `low_stock_alerts`
--
ALTER TABLE `low_stock_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `low_stock_alerts_product_id_foreign` (`product_id`),
  ADD KEY `low_stock_alert_history_index` (`location_id`,`resolved_at`,`triggered_at`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`),
  ADD KEY `notifications_unread_lookup_index` (`notifiable_type`,`notifiable_id`,`read_at`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_unique` (`name`);

--
-- Indexes for table `permission_role`
--
ALTER TABLE `permission_role`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `permission_role_role_id_foreign` (`role_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `products_sku_unique` (`sku`),
  ADD KEY `products_unit_id_foreign` (`unit_id`),
  ADD KEY `products_category_id_is_active_index` (`category_id`,`is_active`);

--
-- Indexes for table `product_location_stock_thresholds`
--
ALTER TABLE `product_location_stock_thresholds`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_location_threshold_unique` (`product_id`,`location_id`),
  ADD KEY `product_location_stock_thresholds_location_id_foreign` (`location_id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `purchase_orders_reference_unique` (`reference`),
  ADD UNIQUE KEY `purchase_orders_purchase_request_id_unique` (`purchase_request_id`),
  ADD KEY `purchase_orders_supplier_id_foreign` (`supplier_id`),
  ADD KEY `purchase_orders_location_id_foreign` (`location_id`),
  ADD KEY `purchase_orders_created_by_foreign` (`created_by`),
  ADD KEY `purchase_orders_status_expected_delivery_index` (`status`,`expected_delivery`);

--
-- Indexes for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `purchase_order_items_purchase_order_id_product_id_unique` (`purchase_order_id`,`product_id`),
  ADD KEY `purchase_order_items_product_id_foreign` (`product_id`);

--
-- Indexes for table `purchase_requests`
--
ALTER TABLE `purchase_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `purchase_requests_reference_unique` (`reference`),
  ADD KEY `purchase_requests_requested_by_foreign` (`requested_by`),
  ADD KEY `purchase_requests_reviewed_by_foreign` (`reviewed_by`),
  ADD KEY `purchase_requests_status_created_at_index` (`status`,`created_at`);

--
-- Indexes for table `purchase_request_items`
--
ALTER TABLE `purchase_request_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `purchase_request_items_purchase_request_id_product_id_unique` (`purchase_request_id`,`product_id`),
  ADD KEY `purchase_request_items_product_id_foreign` (`product_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_unique` (`name`);

--
-- Indexes for table `role_user`
--
ALTER TABLE `role_user`
  ADD PRIMARY KEY (`role_id`,`user_id`),
  ADD KEY `role_user_user_id_foreign` (`user_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `stock_adjustments`
--
ALTER TABLE `stock_adjustments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stock_adjustments_reference_unique` (`reference`),
  ADD KEY `stock_adjustments_product_id_foreign` (`product_id`),
  ADD KEY `stock_adjustments_created_by_foreign` (`created_by`),
  ADD KEY `stock_adjustments_location_id_product_id_created_at_index` (`location_id`,`product_id`,`created_at`);

--
-- Indexes for table `stock_ins`
--
ALTER TABLE `stock_ins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stock_ins_reference_unique` (`reference`),
  ADD KEY `stock_ins_supplier_id_foreign` (`supplier_id`),
  ADD KEY `stock_ins_received_by_foreign` (`received_by`),
  ADD KEY `stock_ins_location_id_received_at_index` (`location_id`,`received_at`),
  ADD KEY `stock_ins_purchase_order_id_foreign` (`purchase_order_id`);

--
-- Indexes for table `stock_in_items`
--
ALTER TABLE `stock_in_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stock_in_items_product_id_foreign` (`product_id`),
  ADD KEY `stock_in_items_receipt_product_index` (`stock_in_id`,`product_id`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stock_movements_location_id_foreign` (`location_id`),
  ADD KEY `stock_movements_user_id_foreign` (`user_id`),
  ADD KEY `stock_movements_product_id_location_id_created_at_index` (`product_id`,`location_id`,`created_at`),
  ADD KEY `stock_movements_stock_in_item_id_foreign` (`stock_in_item_id`),
  ADD KEY `stock_movements_stock_out_item_id_foreign` (`stock_out_item_id`),
  ADD KEY `stock_movements_stock_opname_item_id_foreign` (`stock_opname_item_id`),
  ADD KEY `stock_movements_stock_adjustment_id_foreign` (`stock_adjustment_id`),
  ADD KEY `stock_movements_batch_id_created_at_index` (`batch_id`,`created_at`);

--
-- Indexes for table `stock_opnames`
--
ALTER TABLE `stock_opnames`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stock_opnames_reference_unique` (`reference`),
  ADD KEY `stock_opnames_location_id_foreign` (`location_id`),
  ADD KEY `stock_opnames_counted_by_foreign` (`counted_by`),
  ADD KEY `stock_opnames_reviewed_by_foreign` (`reviewed_by`),
  ADD KEY `stock_opnames_status_period_type_index` (`status`,`period_type`);

--
-- Indexes for table `stock_opname_items`
--
ALTER TABLE `stock_opname_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stock_opname_items_stock_opname_id_product_id_unique` (`stock_opname_id`,`product_id`),
  ADD KEY `stock_opname_items_product_id_foreign` (`product_id`);

--
-- Indexes for table `stock_outs`
--
ALTER TABLE `stock_outs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stock_outs_reference_unique` (`reference`),
  ADD KEY `stock_outs_issued_by_foreign` (`issued_by`),
  ADD KEY `stock_outs_location_id_issued_at_index` (`location_id`,`issued_at`);

--
-- Indexes for table `stock_out_items`
--
ALTER TABLE `stock_out_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stock_out_items_product_id_foreign` (`product_id`),
  ADD KEY `stock_out_items_issue_product_index` (`stock_out_id`,`product_id`),
  ADD KEY `stock_out_items_batch_id_foreign` (`batch_id`);

--
-- Indexes for table `stock_transfers`
--
ALTER TABLE `stock_transfers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stock_transfers_reference_unique` (`reference`),
  ADD KEY `stock_transfers_destination_location_id_foreign` (`destination_location_id`),
  ADD KEY `stock_transfers_stock_out_id_foreign` (`stock_out_id`),
  ADD KEY `stock_transfers_stock_in_id_foreign` (`stock_in_id`),
  ADD KEY `stock_transfers_dispatched_by_foreign` (`dispatched_by`),
  ADD KEY `stock_transfers_received_by_foreign` (`received_by`),
  ADD KEY `stock_transfer_route_status_index` (`source_location_id`,`destination_location_id`,`status`),
  ADD KEY `stock_transfers_status_index` (`status`),
  ADD KEY `stock_transfers_stock_transfer_request_id_foreign` (`stock_transfer_request_id`);

--
-- Indexes for table `stock_transfer_items`
--
ALTER TABLE `stock_transfer_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stock_transfer_items_stock_out_item_id_unique` (`stock_out_item_id`),
  ADD UNIQUE KEY `stock_transfer_items_stock_in_item_id_unique` (`stock_in_item_id`),
  ADD KEY `stock_transfer_items_product_id_foreign` (`product_id`),
  ADD KEY `stock_transfer_items_source_batch_id_foreign` (`source_batch_id`),
  ADD KEY `stock_transfer_items_destination_batch_id_foreign` (`destination_batch_id`),
  ADD KEY `stock_transfer_items_stock_transfer_id_product_id_index` (`stock_transfer_id`,`product_id`);

--
-- Indexes for table `stock_transfer_receipts`
--
ALTER TABLE `stock_transfer_receipts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stock_transfer_receipts_stock_in_id_foreign` (`stock_in_id`),
  ADD KEY `stock_transfer_receipts_received_by_foreign` (`received_by`),
  ADD KEY `stock_transfer_receipts_stock_transfer_id_received_at_index` (`stock_transfer_id`,`received_at`);

--
-- Indexes for table `stock_transfer_receipt_items`
--
ALTER TABLE `stock_transfer_receipt_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stock_transfer_receipt_items_stock_transfer_receipt_id_foreign` (`stock_transfer_receipt_id`),
  ADD KEY `stock_transfer_receipt_items_stock_in_item_id_foreign` (`stock_in_item_id`),
  ADD KEY `transfer_receipt_item_lookup_index` (`stock_transfer_item_id`,`stock_transfer_receipt_id`);

--
-- Indexes for table `stock_transfer_requests`
--
ALTER TABLE `stock_transfer_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stock_transfer_requests_reference_unique` (`reference`),
  ADD UNIQUE KEY `stock_transfer_requests_stock_transfer_id_unique` (`stock_transfer_id`),
  ADD KEY `stock_transfer_requests_location_id_foreign` (`location_id`),
  ADD KEY `stock_transfer_requests_requested_by_foreign` (`requested_by`),
  ADD KEY `stock_transfer_requests_picked_by_foreign` (`picked_by`),
  ADD KEY `stock_transfer_requests_status_index` (`status`);

--
-- Indexes for table `stock_transfer_request_items`
--
ALTER TABLE `stock_transfer_request_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stock_transfer_request_items_product_id_foreign` (`product_id`),
  ADD KEY `transfer_request_product_index` (`stock_transfer_request_id`,`product_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `suppliers_name_unique` (`name`);

--
-- Indexes for table `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `units_name_unique` (`name`),
  ADD UNIQUE KEY `units_symbol_unique` (`symbol`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_location_id_foreign` (`location_id`);

--
-- Indexes for table `whatsapp_alert_logs`
--
ALTER TABLE `whatsapp_alert_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `whatsapp_alert_recipient_unique` (`low_stock_alert_id`,`recipient`),
  ADD KEY `whatsapp_alert_status_index` (`status`,`created_at`);

--
-- Indexes for table `whatsapp_alert_settings`
--
ALTER TABLE `whatsapp_alert_settings`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `administration_audit_logs`
--
ALTER TABLE `administration_audit_logs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=448;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `low_stock_alerts`
--
ALTER TABLE `low_stock_alerts`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `product_location_stock_thresholds`
--
ALTER TABLE `product_location_stock_thresholds`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_requests`
--
ALTER TABLE `purchase_requests`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_request_items`
--
ALTER TABLE `purchase_request_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `stock_adjustments`
--
ALTER TABLE `stock_adjustments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_ins`
--
ALTER TABLE `stock_ins`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `stock_in_items`
--
ALTER TABLE `stock_in_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `stock_opnames`
--
ALTER TABLE `stock_opnames`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_opname_items`
--
ALTER TABLE `stock_opname_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_outs`
--
ALTER TABLE `stock_outs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_out_items`
--
ALTER TABLE `stock_out_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_transfers`
--
ALTER TABLE `stock_transfers`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_transfer_items`
--
ALTER TABLE `stock_transfer_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_transfer_receipts`
--
ALTER TABLE `stock_transfer_receipts`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_transfer_receipt_items`
--
ALTER TABLE `stock_transfer_receipt_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_transfer_requests`
--
ALTER TABLE `stock_transfer_requests`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_transfer_request_items`
--
ALTER TABLE `stock_transfer_request_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `units`
--
ALTER TABLE `units`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `whatsapp_alert_logs`
--
ALTER TABLE `whatsapp_alert_logs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `whatsapp_alert_settings`
--
ALTER TABLE `whatsapp_alert_settings`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `administration_audit_logs`
--
ALTER TABLE `administration_audit_logs`
  ADD CONSTRAINT `administration_audit_logs_actor_id_foreign` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `low_stock_alerts`
--
ALTER TABLE `low_stock_alerts`
  ADD CONSTRAINT `low_stock_alerts_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `low_stock_alerts_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `permission_role`
--
ALTER TABLE `permission_role`
  ADD CONSTRAINT `permission_role_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `permission_role_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `products_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `product_location_stock_thresholds`
--
ALTER TABLE `product_location_stock_thresholds`
  ADD CONSTRAINT `product_location_stock_thresholds_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `product_location_stock_thresholds_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `purchase_orders_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `purchase_orders_purchase_request_id_foreign` FOREIGN KEY (`purchase_request_id`) REFERENCES `purchase_requests` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `purchase_orders_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD CONSTRAINT `purchase_order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `purchase_order_items_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_requests`
--
ALTER TABLE `purchase_requests`
  ADD CONSTRAINT `purchase_requests_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `purchase_requests_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `purchase_request_items`
--
ALTER TABLE `purchase_request_items`
  ADD CONSTRAINT `purchase_request_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `purchase_request_items_purchase_request_id_foreign` FOREIGN KEY (`purchase_request_id`) REFERENCES `purchase_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_user`
--
ALTER TABLE `role_user`
  ADD CONSTRAINT `role_user_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_adjustments`
--
ALTER TABLE `stock_adjustments`
  ADD CONSTRAINT `stock_adjustments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `stock_adjustments_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `stock_adjustments_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `stock_ins`
--
ALTER TABLE `stock_ins`
  ADD CONSTRAINT `stock_ins_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `stock_ins_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `stock_ins_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `stock_ins_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `stock_in_items`
--
ALTER TABLE `stock_in_items`
  ADD CONSTRAINT `stock_in_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `stock_in_items_stock_in_id_foreign` FOREIGN KEY (`stock_in_id`) REFERENCES `stock_ins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `stock_in_items` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `stock_movements_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `stock_movements_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `stock_movements_stock_adjustment_id_foreign` FOREIGN KEY (`stock_adjustment_id`) REFERENCES `stock_adjustments` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `stock_movements_stock_in_item_id_foreign` FOREIGN KEY (`stock_in_item_id`) REFERENCES `stock_in_items` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `stock_movements_stock_opname_item_id_foreign` FOREIGN KEY (`stock_opname_item_id`) REFERENCES `stock_opname_items` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `stock_movements_stock_out_item_id_foreign` FOREIGN KEY (`stock_out_item_id`) REFERENCES `stock_out_items` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `stock_movements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `stock_opnames`
--
ALTER TABLE `stock_opnames`
  ADD CONSTRAINT `stock_opnames_counted_by_foreign` FOREIGN KEY (`counted_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `stock_opnames_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `stock_opnames_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `stock_opname_items`
--
ALTER TABLE `stock_opname_items`
  ADD CONSTRAINT `stock_opname_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `stock_opname_items_stock_opname_id_foreign` FOREIGN KEY (`stock_opname_id`) REFERENCES `stock_opnames` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_outs`
--
ALTER TABLE `stock_outs`
  ADD CONSTRAINT `stock_outs_issued_by_foreign` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `stock_outs_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `stock_out_items`
--
ALTER TABLE `stock_out_items`
  ADD CONSTRAINT `stock_out_items_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `stock_in_items` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `stock_out_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `stock_out_items_stock_out_id_foreign` FOREIGN KEY (`stock_out_id`) REFERENCES `stock_outs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_transfers`
--
ALTER TABLE `stock_transfers`
  ADD CONSTRAINT `stock_transfers_destination_location_id_foreign` FOREIGN KEY (`destination_location_id`) REFERENCES `locations` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `stock_transfers_dispatched_by_foreign` FOREIGN KEY (`dispatched_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `stock_transfers_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `stock_transfers_source_location_id_foreign` FOREIGN KEY (`source_location_id`) REFERENCES `locations` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `stock_transfers_stock_in_id_foreign` FOREIGN KEY (`stock_in_id`) REFERENCES `stock_ins` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `stock_transfers_stock_out_id_foreign` FOREIGN KEY (`stock_out_id`) REFERENCES `stock_outs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `stock_transfers_stock_transfer_request_id_foreign` FOREIGN KEY (`stock_transfer_request_id`) REFERENCES `stock_transfer_requests` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `stock_transfer_items`
--
ALTER TABLE `stock_transfer_items`
  ADD CONSTRAINT `stock_transfer_items_destination_batch_id_foreign` FOREIGN KEY (`destination_batch_id`) REFERENCES `stock_in_items` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `stock_transfer_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `stock_transfer_items_source_batch_id_foreign` FOREIGN KEY (`source_batch_id`) REFERENCES `stock_in_items` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `stock_transfer_items_stock_in_item_id_foreign` FOREIGN KEY (`stock_in_item_id`) REFERENCES `stock_in_items` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `stock_transfer_items_stock_out_item_id_foreign` FOREIGN KEY (`stock_out_item_id`) REFERENCES `stock_out_items` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `stock_transfer_items_stock_transfer_id_foreign` FOREIGN KEY (`stock_transfer_id`) REFERENCES `stock_transfers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_transfer_receipts`
--
ALTER TABLE `stock_transfer_receipts`
  ADD CONSTRAINT `stock_transfer_receipts_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `stock_transfer_receipts_stock_in_id_foreign` FOREIGN KEY (`stock_in_id`) REFERENCES `stock_ins` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `stock_transfer_receipts_stock_transfer_id_foreign` FOREIGN KEY (`stock_transfer_id`) REFERENCES `stock_transfers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_transfer_receipt_items`
--
ALTER TABLE `stock_transfer_receipt_items`
  ADD CONSTRAINT `stock_transfer_receipt_items_stock_in_item_id_foreign` FOREIGN KEY (`stock_in_item_id`) REFERENCES `stock_in_items` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `stock_transfer_receipt_items_stock_transfer_item_id_foreign` FOREIGN KEY (`stock_transfer_item_id`) REFERENCES `stock_transfer_items` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `stock_transfer_receipt_items_stock_transfer_receipt_id_foreign` FOREIGN KEY (`stock_transfer_receipt_id`) REFERENCES `stock_transfer_receipts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_transfer_requests`
--
ALTER TABLE `stock_transfer_requests`
  ADD CONSTRAINT `stock_transfer_requests_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `stock_transfer_requests_picked_by_foreign` FOREIGN KEY (`picked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `stock_transfer_requests_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `stock_transfer_requests_stock_transfer_id_foreign` FOREIGN KEY (`stock_transfer_id`) REFERENCES `stock_transfers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `stock_transfer_request_items`
--
ALTER TABLE `stock_transfer_request_items`
  ADD CONSTRAINT `stock_transfer_request_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `stock_transfer_request_items_stock_transfer_request_id_foreign` FOREIGN KEY (`stock_transfer_request_id`) REFERENCES `stock_transfer_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `whatsapp_alert_logs`
--
ALTER TABLE `whatsapp_alert_logs`
  ADD CONSTRAINT `whatsapp_alert_logs_low_stock_alert_id_foreign` FOREIGN KEY (`low_stock_alert_id`) REFERENCES `low_stock_alerts` (`id`) ON DELETE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
