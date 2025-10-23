-- MySQL initialization script for Docker
-- This script runs when the MySQL container starts for the first time

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS tierone_orders CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user if it doesn't exist
CREATE USER IF NOT EXISTS 'tierone_user'@'%' IDENTIFIED BY 'tierone_password';

-- Grant privileges
GRANT ALL PRIVILEGES ON tierone_orders.* TO 'tierone_user'@'%';

-- Flush privileges
FLUSH PRIVILEGES;

-- Use the database
USE tierone_orders;

-- Set SQL mode for better compatibility
SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO';
