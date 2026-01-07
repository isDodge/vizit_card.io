-- ============================================
-- INZZO Sakura Collection - Database Schema
-- ============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Удаляем старую базу если существует
DROP DATABASE IF EXISTS inzzo_db;

-- Создаем новую базу данных
CREATE DATABASE inzzo_db 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- Используем созданную базу
USE inzzo_db;

-- ============================================
-- Таблица администраторов
-- ============================================
CREATE TABLE admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username)
) ENGINE=InnoDB;

-- ============================================
-- Таблица попыток входа (защита от брутфорса)
-- ============================================
CREATE TABLE login_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    attempts INT DEFAULT 1,
    last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    locked_until TIMESTAMP NULL,
    INDEX idx_username (username),
    INDEX idx_locked (locked_until)
) ENGINE=InnoDB;

-- ============================================
-- Таблица товаров
-- ============================================
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL CHECK (price > 0),
    original_price DECIMAL(10,2),
    image VARCHAR(255) NOT NULL DEFAULT 'placeholder.jpg',
    is_new BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    stock INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FULLTEXT idx_search (name, description),
    INDEX idx_slug (slug),
    INDEX idx_new (is_new),
    INDEX idx_active (is_active),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- ============================================
-- Таблица корзины
-- ============================================
CREATE TABLE cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    session_id VARCHAR(128) NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1 CHECK (quantity > 0),
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_session (session_id),
    INDEX idx_product (product_id),
    UNIQUE KEY unique_cart_item (session_id, product_id)
) ENGINE=InnoDB;

-- ============================================
-- Таблица заказов (обновленная с Telegram)
-- ============================================
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    customer_phone VARCHAR(50) NOT NULL,
    customer_city VARCHAR(100) NOT NULL,
    customer_address TEXT NOT NULL,
    telegram_username VARCHAR(100) NOT NULL,
    promo_code VARCHAR(50),
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('new', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'new',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_order_number (order_number),
    INDEX idx_status (status),
    INDEX idx_created (created_at),
    INDEX idx_telegram (telegram_username)
) ENGINE=InnoDB;

-- ============================================
-- Таблица элементов заказа
-- ============================================
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    product_price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    INDEX idx_order (order_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB;

-- ============================================
-- Тестовые данные
-- ============================================

-- Тестовый администратор (пароль: Admin123!)
INSERT INTO admins (username, password_hash, email) VALUES 
('admin', '$2y$10$7MnvTZkPhfN.3HXgL9QyZeO8vY6V2jQpFcW8KmJtR4sNqB3dC5aE7G', 'admin@inzzo.com');

-- Тестовые товары в стиле сакуры
INSERT INTO products (name, slug, description, price, image, is_new, stock) VALUES
('Sakura Kimono Hoodie', 'sakura-kimono-hoodie', 'Худи с вышивкой цветущей сакуры на спине. Премиальный хлопок, свободный крой.', 14900.00, 'hoodie-sakura.jpg', TRUE, 12),
('Cherry Blossom Bomber', 'cherry-blossom-bomber', 'Бомбер с принтом лепестков сакуры. Легкий, но теплый, идеален для весны.', 19900.00, 'bomber-blossom.jpg', TRUE, 8),
('Zen Sakura Pants', 'zen-sakura-pants', 'Широкие брюки с узором сакуры по бокам. Комфорт и элегантность.', 9900.00, 'pants-zen.jpg', FALSE, 20),
('Moonlight Sakura T-Shirt', 'moonlight-sakura-t-shirt', 'Футболка с принтом сакуры при лунном свете. 100% японский хлопок.', 5900.00, 'tshirt-moonlight.jpg', TRUE, 25),
('Sakura Night Robe', 'sakura-night-robe', 'Халат-кимоно с цветочным узором сакуры. Шелковая подкладка.', 26900.00, 'robe-night.jpg', FALSE, 5),
('Blossom Windbreaker', 'blossom-windbreaker', 'Ветровка с градиентом от сиреневого к розовому. Водоотталкивающая ткань.', 17900.00, 'windbreaker-blossom.jpg', TRUE, 10),
('Sakura Garden Sweater', 'sakura-garden-sweater', 'Свитер с объемным узором садовой сакуры. Мягкая шерсть.', 12900.00, 'sweater-garden.jpg', FALSE, 15),
('Petals Cargo Pants', 'petals-cargo-pants', 'Карго с карманами в форме лепестков. Утилитарный стиль.', 11900.00, 'pants-cargo.jpg', TRUE, 7);

-- ============================================
-- Тестовые заказы (для демонстрации)
-- ============================================
INSERT INTO orders (order_number, customer_name, customer_email, customer_phone, customer_city, customer_address, telegram_username, total_amount, status) VALUES
('INZ-20241201-001', 'Иванов Иван Иванович', 'ivanov@example.com', '+7 (999) 123-45-67', 'Москва', 'Пункт выдачи CDEK, ул. Тверская, 10', 'ivanov_ii', 29800.00, 'delivered'),
('INZ-20241205-002', 'Петрова Анна Сергеевна', 'petrova@example.com', '+7 (999) 765-43-21', 'Санкт-Петербург', 'Пункт выдачи СДЭК, Невский пр., 50', 'anna_petrova', 44800.00, 'shipped'),
('INZ-20241210-003', 'Сидоров Алексей', 'sidorov@example.com', '+7 (777) 888-99-00', 'Казань', 'Пункт выдачи Boxberry, ул. Баумана, 30', 'alex_sidorov', 5900.00, 'processing');

INSERT INTO order_items (order_id, product_id, product_name, product_price, quantity, subtotal) VALUES
(1, 1, 'Sakura Kimono Hoodie', 14900.00, 2, 29800.00),
(2, 2, 'Cherry Blossom Bomber', 19900.00, 1, 19900.00),
(2, 4, 'Moonlight Sakura T-Shirt', 5900.00, 3, 17700.00),
(2, 6, 'Blossom Windbreaker', 17900.00, 1, 17900.00),
(3, 4, 'Moonlight Sakura T-Shirt', 5900.00, 1, 5900.00);

-- Включаем проверки внешних ключей
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- Проверочные запросы
-- ============================================
SELECT 'База данных создана успешно!' as message;
SELECT COUNT(*) as total_products FROM products;
SELECT COUNT(*) as total_admins FROM admins;