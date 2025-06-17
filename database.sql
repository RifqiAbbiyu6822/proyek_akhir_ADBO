-- Create database
CREATE DATABASE IF NOT EXISTS lensarental;
USE lensarental;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create lenses table
CREATE TABLE IF NOT EXISTS lenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price_per_day DECIMAL(10,2) NOT NULL,
    status ENUM('available', 'rented') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create rentals table
CREATE TABLE IF NOT EXISTS rentals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    lens_id INT NOT NULL,
    rental_date DATE NOT NULL,
    return_date DATE NOT NULL,
    status ENUM('active', 'returned') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (lens_id) REFERENCES lenses(id)
);

-- Create fines table
CREATE TABLE IF NOT EXISTS fines (
    id INT PRIMARY KEY AUTO_INCREMENT,
    rental_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'paid') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rental_id) REFERENCES rentals(id)
);

-- Insert sample data
INSERT INTO users (name, email, password) VALUES
('Admin User', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); -- password: password

INSERT INTO lenses (name, description, price_per_day) VALUES
('Canon EF 50mm f/1.8 STM', 'Standard prime lens with wide aperture', 50000),
('Nikon 70-200mm f/2.8G ED VR II', 'Professional telephoto zoom lens', 100000),
('Sony FE 24-70mm f/2.8 GM', 'Professional standard zoom lens', 120000),
('Fujifilm XF 35mm f/1.4 R', 'Compact prime lens for Fujifilm X-mount', 45000); 