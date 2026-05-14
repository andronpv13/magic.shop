-- Удаление базы данных если существует (ОСТОРОЖНО: удалит все данные!)
DROP DATABASE IF EXISTS shop_db;

-- Создание новой базы данных
CREATE DATABASE shop_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE shop_db;

-- ==========================================
-- ТАБЛИЦА USERS
-- ==========================================
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    middle_name VARCHAR(50),
    phone VARCHAR(20),
    zip_code VARCHAR(10),
    region VARCHAR(50),
    city VARCHAR(50),
    street VARCHAR(100),
    house VARCHAR(10),
    apartment VARCHAR(10),
    role ENUM('admin', 'moderator', 'customer') NOT NULL DEFAULT 'customer',
    -- Поля для безопасности и логирования
    last_login TIMESTAMP NULL DEFAULT NULL,
    failed_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Индексы
    INDEX idx_users_email (email),
    INDEX idx_users_username (username),
    INDEX idx_users_role (role)
);

-- ==========================================
-- ТАБЛИЦА CATEGORIES
-- ==========================================
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT, -- Добавлено описание категории
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_categories_name (name)
);

-- ==========================================
-- ТАБЛИЦА PRODUCTS
-- ==========================================
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255), -- Увеличено до 255 для длинных путей
    -- Удалено поле category (VARCHAR), так как теперь используем category_id
    category_id INT NOT NULL,
    stock INT DEFAULT 0,
    is_new BOOLEAN DEFAULT FALSE,
    active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Внешние ключи
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    
    -- Индексы
    INDEX idx_products_category_id (category_id),
    INDEX idx_products_price (price),
    INDEX idx_products_active (active),
    INDEX idx_products_is_new (is_new),
    INDEX idx_products_created_by (created_by)
);

-- ==========================================
-- ТАБЛИЦА ORDERS
-- ==========================================
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    delivery_address TEXT,
    comment TEXT,
    payment_method VARCHAR(50) DEFAULT 'card', -- Добавлено
    status ENUM('pending', 'payment', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Токен для безопасного доступа к заказу без перебора ID
    order_token VARCHAR(64) UNIQUE, 
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Индексы
    INDEX idx_orders_user_id (user_id),
    INDEX idx_orders_status (status),
    INDEX idx_orders_created_at (created_at),
    INDEX idx_orders_token (order_token)
);

-- ==========================================
-- ТАБЛИЦА ORDER_ITEMS
-- ==========================================
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    
    -- Индексы
    INDEX idx_order_items_order_id (order_id),
    INDEX idx_order_items_product_id (product_id)
);

-- ==========================================
-- ТАБЛИЦА REVIEWS
-- ==========================================
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    is_approved BOOLEAN DEFAULT FALSE, -- Для модерации отзывов
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    
    -- Индексы
    INDEX idx_reviews_product_id (product_id),
    INDEX idx_reviews_user_id (user_id),
    INDEX idx_reviews_approved (is_approved)
);

-- ==========================================
-- ЗАПОЛНЕНИЕ ДАННЫМИ
-- ==========================================

-- Insert default users (Пароль 'toor')
INSERT INTO users (username, password, email, first_name, last_name, role) VALUES
('root', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@magicshop.ru', 'Администратор', 'Системы', 'admin'),
('moderator', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'moderator@magicshop.ru', 'Модератор', 'Системы', 'moderator');

INSERT INTO categories (name, description) VALUES
('Магические предметы', 'Палочки, артефакты и магические кристаллы'),
('Инструменты', 'Котелки, весы и ингредиенты'),
('Книги', 'Гримуары, учебники и карты');

-- Sample products (Теперь используем category_id вместо названия категории)
-- ID категорий: 1=Магические предметы, 2=Инструменты, 3=Книги
INSERT INTO products (name, description, price, image, category_id, created_by, stock) VALUES
('Волшебная палочка', 'Идеальная для начинающих волшебников', 5000.00, 'wand1.jpg', 1, 1, 10),
('Котелок зельеварения', 'Прочный медный котел', 3500.00, 'cauldron1.jpg', 2, 1, 5),
<<<<<<< HEAD
('Книга заклинаний', 'Сборник основных заклинаний', 1200.00, 'book1.jpg', 3, 1, 20);
=======
('Книга заклинаний', 'Сборник основных заклинаний', 1200.00, 'book1.jpg', 3, 1, 20);
>>>>>>> 17aa9fe80430601b55ac05d1a95d326b8163eefa
