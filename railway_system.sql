-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `railway_system`
--

CREATE DATABASE IF NOT EXISTS `railway_system`;
USE `railway_system`;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `no_phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staffs`
--

CREATE TABLE `staffs` (
  `staff_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff') NOT NULL DEFAULT 'staff',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`staff_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `schedule_id` int(11) NOT NULL AUTO_INCREMENT,
  `train_number` varchar(20) NOT NULL,
  `departure_station` varchar(100) NOT NULL,
  `arrival_station` varchar(100) NOT NULL,
  `departure_time` datetime NOT NULL,
  `arrival_time` datetime NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `available_seats` int(11) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`schedule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `ticket_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `booking_date` timestamp DEFAULT CURRENT_TIMESTAMP,
  `seat_number` varchar(10) NOT NULL,
  `status` enum('active','cancelled','completed') NOT NULL DEFAULT 'active',
  `qr_code` varchar(255) NOT NULL,
  `payment_status` enum('pending','paid','refunded') NOT NULL DEFAULT 'pending',
  `payment_amount` decimal(10,2) NOT NULL,
  PRIMARY KEY (`ticket_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`schedule_id`) REFERENCES `schedules`(`schedule_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

-- Create payments table
CREATE TABLE IF NOT EXISTS `payments` (
    `payment_id` INT PRIMARY KEY AUTO_INCREMENT,
    `ticket_id` INT NOT NULL,
    `payment_method` VARCHAR(50) NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `payment_date` DATETIME NOT NULL,
    `status` VARCHAR(20) DEFAULT 'completed',
    FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create refunds table
CREATE TABLE IF NOT EXISTS `refunds` (
    `refund_id` INT PRIMARY KEY AUTO_INCREMENT,
    `ticket_id` INT NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `refund_date` DATETIME NOT NULL,
    `status` VARCHAR(20) DEFAULT 'pending',
    FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Default admin account
--

INSERT INTO `staffs` (`username`, `email`, `password`, `role`) VALUES
('admin', 'admin@railway.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
-- Default password: password