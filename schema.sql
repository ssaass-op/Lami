CREATE DATABASE IF NOT EXISTS `lami_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `lami_db`;
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `interests`;
DROP TABLE IF EXISTS `matches_preferences`;
DROP TABLE IF EXISTS `privacy_settings`;
DROP TABLE IF EXISTS `profiles`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) UNIQUE NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('standard', 'moderator', 'admin') DEFAULT 'standard',
  `status` ENUM('pending', 'verified', 'suspended') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_email` (`email`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `profiles` (
  `user_id` INT PRIMARY KEY,
  `gender` ENUM('male', 'female', 'other') NOT NULL,
  `dob` DATE NOT NULL,
  `religion` VARCHAR(50) DEFAULT NULL,
  `caste` VARCHAR(50) DEFAULT NULL,
  `education` VARCHAR(100) DEFAULT NULL,
  `profession` VARCHAR(100) DEFAULT NULL,
  `location` VARCHAR(100) DEFAULT NULL,
  `height` DECIMAL(5,2) DEFAULT NULL, 
  `bio` TEXT DEFAULT NULL,
  `income_range` VARCHAR(50) DEFAULT NULL,
  `marital_status` ENUM('never_married', 'divorced', 'widowed', 'awaited') DEFAULT 'never_married',
  `photo_path` VARCHAR(255) DEFAULT NULL,
  `identity_doc_path` VARCHAR(255) DEFAULT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_gender` (`gender`),
  INDEX `idx_religion` (`religion`),
  INDEX `idx_caste` (`caste`),
  INDEX `idx_location` (`location`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `privacy_settings` (
  `user_id` INT PRIMARY KEY,
  `show_phone` TINYINT(1) DEFAULT 0, 
  `show_photo` TINYINT(1) DEFAULT 1, 
  `show_income` TINYINT(1) DEFAULT 0, 
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `matches_preferences` (
  `user_id` INT PRIMARY KEY,
  `min_age` INT DEFAULT 18,
  `max_age` INT DEFAULT 70,
  `preferred_religion` VARCHAR(50) DEFAULT NULL,
  `preferred_caste` VARCHAR(50) DEFAULT NULL,
  `preferred_location` VARCHAR(100) DEFAULT NULL,
  `preferred_education` VARCHAR(100) DEFAULT NULL,
  `preferred_marital_status` ENUM('any', 'never_married', 'divorced', 'widowed', 'awaited') DEFAULT 'any',
  `preferred_profession` VARCHAR(100) DEFAULT NULL,
  `min_height` DECIMAL(5,2) DEFAULT NULL,
  `max_height` DECIMAL(5,2) DEFAULT NULL,
  `preferred_income` VARCHAR(50) DEFAULT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `interests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sender_id` INT NOT NULL,
  `receiver_id` INT NOT NULL,
  `status` ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_interest` (`sender_id`, `receiver_id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sender_id` INT NOT NULL,
  `receiver_id` INT NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_chat` (`sender_id`, `receiver_id`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `audit_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `admin_id` INT NOT NULL,
  `action` VARCHAR(255) NOT NULL,
  `target_user_id` INT DEFAULT NULL,
  `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`target_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
