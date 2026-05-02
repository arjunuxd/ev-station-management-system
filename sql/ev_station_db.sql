-- Create database
CREATE DATABASE IF NOT EXISTS ev_station_db 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE ev_station_db;

-- Reset tables safely
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS connectors;
DROP TABLE IF EXISTS stations;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- ======================
-- USERS TABLE
-- ======================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ✅ Admin (PLAIN PASSWORD)
INSERT INTO users (name, email, password, role) VALUES
('Admin', 'admin@ev.com', 'admin123', 'admin');

-- ✅ Sample Users (PLAIN PASSWORD)
INSERT INTO users (name, email, password, role) VALUES
('John Doe', 'john@example.com', 'user123', 'user'),
('Jane Smith', 'jane@example.com', 'user123', 'user');

-- ======================
-- STATIONS TABLE
-- ======================
CREATE TABLE stations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    location VARCHAR(255) NOT NULL,
    address TEXT,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    status ENUM('active','inactive','maintenance') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ======================
-- CONNECTORS TABLE
-- ======================
CREATE TABLE connectors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    station_id INT NOT NULL,
    connector_type ENUM('Type 1','Type 2','CCS','CHAdeMO','GB/T') NOT NULL,
    power_kw DECIMAL(6,2) NOT NULL,
    status ENUM('available','occupied','maintenance') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (station_id) REFERENCES stations(id) ON DELETE CASCADE
);

-- ======================
-- BOOKINGS TABLE
-- ======================
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    connector_id INT NOT NULL,
    station_id INT NOT NULL,
    booking_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('upcoming','active','completed','cancelled') DEFAULT 'upcoming',
    vehicle_number VARCHAR(30),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (connector_id) REFERENCES connectors(id) ON DELETE CASCADE,
    FOREIGN KEY (station_id) REFERENCES stations(id) ON DELETE CASCADE
);

-- ======================
-- SAMPLE DATA
-- ======================

-- Stations
INSERT INTO stations (name, location, address, status) VALUES
('Central Hub Station', 'Downtown', '123 Main Street', 'active'),
('North Park Charging', 'North Zone', '45 Park Avenue', 'active'),
('Mall Charging Point', 'Shopping Area', 'City Mall B1', 'active'),
('Tech Park Station', 'IT Zone', 'Sector 5', 'maintenance');

-- Connectors
INSERT INTO connectors (station_id, connector_type, power_kw, status) VALUES
(1, 'CCS', 50.00, 'available'),
(1, 'Type 2', 22.00, 'available'),
(2, 'CCS', 100.00, 'available'),
(3, 'Type 1', 7.40, 'available'),
(4, 'CCS', 150.00, 'maintenance');