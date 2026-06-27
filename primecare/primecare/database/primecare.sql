-- PrimeCare Pharmaceutical Distributors
-- Database Schema and Sample Seed Data
-- Import this inside phpMyAdmin or run in MySQL command line

CREATE DATABASE IF NOT EXISTS `primecare_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `primecare_db`;

-- --------------------------------------------------------

-- Table structure for table `users`
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `fullname` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `username` VARCHAR(50) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` VARCHAR(20) NOT NULL DEFAULT 'client'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `medicines`
CREATE TABLE IF NOT EXISTS `medicines` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `pack` VARCHAR(100) NOT NULL DEFAULT 'BOX',
  `size` VARCHAR(100) NOT NULL DEFAULT '100\'s',
  `description` TEXT NOT NULL,
  `brand` VARCHAR(100) NOT NULL DEFAULT '',
  `price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `stock` INT NOT NULL DEFAULT 0,
  `pieces_per_box` INT NOT NULL DEFAULT 10,
  `availability` VARCHAR(50) NOT NULL DEFAULT 'Available',
  `expiry_date` DATE NOT NULL,
  `image` VARCHAR(255) NOT NULL DEFAULT 'default.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `inquiries`
CREATE TABLE IF NOT EXISTS `inquiries` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `medicine_id` INT NOT NULL,
  `message` TEXT NOT NULL,
  `response` TEXT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'Pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `orders`
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `fullname` VARCHAR(100) NOT NULL,
  `address` TEXT NOT NULL,
  `contact_number` VARCHAR(50) NOT NULL,
  `medicine_id` INT NOT NULL,
  `quantity` INT NOT NULL,
  `notes` TEXT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'Pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Seed initial data
-- Password for admin: admin123 (hashed with bcrypt: $2y$10$f6B0vYREmN87zRzX5m39fOc6XG.3q.6M8C8nS0dcoenT3S4JvKDei)
-- Password for user1: user123 (hashed with bcrypt: $2y$10$r9k7T4SSTT2iE.nBq/z0qOnf9r391.eH79Vf0iYmGleXWv437q6C6)

INSERT INTO `users` (`id`, `fullname`, `email`, `username`, `password`, `role`) VALUES
(1, 'PrimeCare System Administrator', 'admin@primecare.com', 'admin', '$2y$10$f6B0vYREmN87zRzX5m39fOc6XG.3q.6M8C8nS0dcoenT3S4JvKDei', 'admin'),
(2, 'Sample Pharmacy Client', 'client@primecare.com', 'user1', '$2y$10$r9k7T4SSTT2iE.nBq/z0qOnf9r391.eH79Vf0iYmGleXWv437q6C6', 'client')
ON DUPLICATE KEY UPDATE `id`=`id`;



