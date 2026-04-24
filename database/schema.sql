-- Удаление базы данных если существует
DROP DATABASE IF EXISTS shop_db;

-- Создание новой базы данных
CREATE DATABASE shop_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE shop_db;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(50) NOT NULL,
    surname VARCHAR(50) NOT NULL,
    patronymic VARCHAR(50),
    phone VARCHAR(20),
    address TEXT,
    role ENUM('admin', 'moderator', 'customer') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(100),
    category VARCHAR(50),
    active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Orders table
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'payment', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Order items table
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Reviews table
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Insert default users
INSERT INTO users (username, password, email, name, surname, role) VALUES
('root', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@magicshop.ru', 'Администратор', 'Системы', 'admin'),
('moderator', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'moderator@magicshop.ru', 'Модератор', 'Системы', 'moderator');

-- Insert sample products
INSERT INTO products (name, description, price, image, category, created_by) VALUES
('Волшебная палочка', 'Идеальная для начинающих волшебников', 5000, 'wand1.jpg', 'Магические предметы', 1),
('Котелок зельеварения', 'Прочный медный котел с антипригарным покрытием', 3500, 'cauldron1.jpg', 'Инструменты', 1),
('Книга заклинаний', 'Сборник основных заклинаний для новичков', 1200, 'book1.jpg', 'Книги', 1),
('Кристалл шепота', 'Помогает передавать сообщения на расстоянии', 2500, 'crystal1.jpg', 'Магические предметы', 1),
('Зелье удачи', 'Увеличивает удачу на 24 часа', 800, 'potion1.jpg', 'Зелья', 1),
('Щит защиты', 'Базовый защитный амулет', 1800, 'shield1.jpg', 'Амулеты', 1);
