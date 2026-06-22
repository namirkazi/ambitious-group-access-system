-- Visitor Management System Database
-- Run this in phpMyAdmin or MySQL command line

CREATE DATABASE IF NOT EXISTS visitor_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE visitor_management;

-- Visitors table: stores unique visitor identity
CREATE TABLE IF NOT EXISTS visitors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(100),
    photo_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_phone (phone),
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- Visit logs table: stores each individual visit
CREATE TABLE IF NOT EXISTS visit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visitor_id INT NOT NULL,
    host_name VARCHAR(100) NOT NULL,
    host_department VARCHAR(100),
    purpose TEXT NOT NULL,
    check_in TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    check_out TIMESTAMP NULL,
    badge_number VARCHAR(20),
    status ENUM('checked_in', 'checked_out') DEFAULT 'checked_in',
    notes TEXT,
    FOREIGN KEY (visitor_id) REFERENCES visitors(id) ON DELETE CASCADE,
    INDEX idx_visitor_id (visitor_id),
    INDEX idx_check_in (check_in),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Hosts/departments for dropdown
CREATE TABLE IF NOT EXISTS hosts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    department VARCHAR(100),
    email VARCHAR(100),
    active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

-- Seed some sample hosts
INSERT INTO hosts (name, department, email) VALUES
('Ahmed Al-Rashid', 'IT Department', 'ahmed@company.com'),
('Sara Al-Mazrouei', 'Human Resources', 'sara@company.com'),
('Khalid Al-Mansoori', 'Finance', 'khalid@company.com'),
('Fatima Al-Hameli', 'Operations', 'fatima@company.com'),
('Omar Al-Suwaidi', 'Management', 'omar@company.com');
