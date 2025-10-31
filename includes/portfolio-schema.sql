-- Portfolio Management System Database Schema
-- Run this script to create all required tables for the Portfolio Management System
-- Date: October 31, 2025
-- Version: 1.0

-- Set SQL mode
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- Table structure for table `portfolios`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `portfolios` (
  `portfolio_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `portfolio_name` VARCHAR(100) NOT NULL UNIQUE,
  `portfolio_type` ENUM('Own', 'Portfolio Manager', 'Unlisted & AIF') NOT NULL,
  `description` TEXT,
  `status` ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `created_from` VARCHAR(40) NULL,
  `updated_at` TIMESTAMP NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `updated_from` VARCHAR(40) NULL,
  INDEX `idx_status` (`status`),
  INDEX `idx_type` (`portfolio_type`),
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `portfolio_combinations`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `portfolio_combinations` (
  `combination_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `combination_name` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT,
  `created_by` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_created_by` (`created_by`),
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `portfolio_combination_mapping`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `portfolio_combination_mapping` (
  `mapping_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `combination_id` INT UNSIGNED NOT NULL,
  `portfolio_id` INT UNSIGNED NOT NULL,
  UNIQUE KEY `unique_mapping` (`combination_id`, `portfolio_id`),
  INDEX `idx_combination` (`combination_id`),
  INDEX `idx_portfolio` (`portfolio_id`),
  FOREIGN KEY (`combination_id`) REFERENCES `portfolio_combinations` (`combination_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`portfolio_id`) REFERENCES `portfolios` (`portfolio_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `transactions`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `transactions` (
  `transaction_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `portfolio_id` INT UNSIGNED NOT NULL,
  `transaction_date` DATE NOT NULL,
  `stock_code` VARCHAR(50) NOT NULL,
  `stock_name` VARCHAR(200) NOT NULL,
  `instrument_type` VARCHAR(50) DEFAULT 'Spot',
  `transaction_type` ENUM('BUY', 'SELL') NOT NULL,
  `quantity` DECIMAL(15,2) NOT NULL,
  `price` DECIMAL(15,4) NOT NULL,
  `transaction_value` DECIMAL(18,2) NOT NULL,
  `expiry_date` DATE NULL,
  `strike_price` DECIMAL(15,2) NULL,
  `upload_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `source_file` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_portfolio_date` (`portfolio_id`, `transaction_date`),
  INDEX `idx_stock` (`stock_code`),
  INDEX `idx_transaction_date` (`transaction_date`),
  FOREIGN KEY (`portfolio_id`) REFERENCES `portfolios` (`portfolio_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `holdings`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `holdings` (
  `holding_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `portfolio_id` INT UNSIGNED NOT NULL,
  `stock_code` VARCHAR(50) NOT NULL,
  `stock_name` VARCHAR(200) NOT NULL,
  `current_quantity` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `avg_cost_price` DECIMAL(15,4) NOT NULL DEFAULT 0,
  `total_invested` DECIMAL(18,2) NOT NULL DEFAULT 0,
  `current_market_price` DECIMAL(15,4) NULL,
  `current_value` DECIMAL(18,2) NULL,
  `unrealized_pl` DECIMAL(18,2) NULL,
  `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_portfolio_stock` (`portfolio_id`, `stock_code`),
  INDEX `idx_stock` (`stock_code`),
  FOREIGN KEY (`portfolio_id`) REFERENCES `portfolios` (`portfolio_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `realized_pl`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `realized_pl` (
  `pl_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `portfolio_id` INT UNSIGNED NOT NULL,
  `stock_code` VARCHAR(50) NOT NULL,
  `sell_date` DATE NOT NULL,
  `quantity_sold` DECIMAL(15,2) NOT NULL,
  `sell_price` DECIMAL(15,4) NOT NULL,
  `avg_buy_price` DECIMAL(15,4) NOT NULL,
  `realized_pl` DECIMAL(18,2) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_portfolio_date` (`portfolio_id`, `sell_date`),
  INDEX `idx_stock` (`stock_code`),
  FOREIGN KEY (`portfolio_id`) REFERENCES `portfolios` (`portfolio_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `benchmark_data`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `benchmark_data` (
  `benchmark_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `index_name` VARCHAR(50) NOT NULL,
  `date` DATE NOT NULL,
  `close_value` DECIMAL(15,2) NOT NULL,
  `return_pct` DECIMAL(10,4) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_index_date` (`index_name`, `date`),
  INDEX `idx_date` (`date`),
  INDEX `idx_index` (`index_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `file_uploads`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `file_uploads` (
  `upload_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_size` INT UNSIGNED NOT NULL,
  `upload_date` DATE NOT NULL,
  `status` ENUM('Pending', 'Validated', 'Imported', 'Failed') DEFAULT 'Pending',
  `validation_errors` TEXT NULL,
  `records_count` INT UNSIGNED NULL,
  `uploaded_by` BIGINT UNSIGNED NOT NULL,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `uploaded_from` VARCHAR(40) NULL,
  INDEX `idx_status` (`status`),
  INDEX `idx_date` (`upload_date`),
  FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `performance_cache`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `performance_cache` (
  `cache_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `cache_key` VARCHAR(255) NOT NULL UNIQUE,
  `portfolio_id` INT UNSIGNED NULL,
  `combination_id` INT UNSIGNED NULL,
  `calculation_type` VARCHAR(50) NOT NULL,
  `start_date` DATE NULL,
  `end_date` DATE NULL,
  `result_data` TEXT NOT NULL,
  `calculated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `expires_at` TIMESTAMP NULL,
  INDEX `idx_portfolio` (`portfolio_id`),
  INDEX `idx_combination` (`combination_id`),
  INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
