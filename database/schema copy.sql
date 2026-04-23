-- Удаляем базу, если она существует (ВНИМАНИЕ: это удалит все данные!)
DROP DATABASE IF EXISTS shop_db;

-- Создание базы данных
CREATE DATABASE IF NOT EXISTS shop_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE shop_db;

-- Таблица пользователей
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    first_name VARCHAR(50) DEFAULT NULL,
    last_name VARCHAR(50) DEFAULT NULL,
    middle_name VARCHAR(50) DEFAULT NULL, -- Отчество
    phone VARCHAR(20) DEFAULT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    role ENUM('admin', 'moderator', 'customer') NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Поля адреса (разделены)
    zip_code VARCHAR(20) DEFAULT NULL,      -- Индекс
    region VARCHAR(100) DEFAULT NULL,        -- Область/Регион
    city VARCHAR(100) DEFAULT NULL,           -- Населённый пункт
    street VARCHAR(100) DEFAULT NULL,         -- Улица
    house VARCHAR(20) DEFAULT NULL,            -- Дом
    apartment VARCHAR(20) DEFAULT NULL         -- Квартира
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица категорий (НОВАЯ)
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица товаров (ИЗМЕНЕНА: Добавлен внешний ключ на categories)
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    category_id INT DEFAULT NULL, -- ИЗМЕНЕНО: теперь храним ID категории
    stock INT NOT NULL DEFAULT 0,
    is_new TINYINT(1) DEFAULT 1,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL -- ИЗМЕНЕНО: связь с таблицей категорий
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица заказов
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'payment', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    payment_method VARCHAR(50) DEFAULT NULL,
    delivery_address TEXT, -- Здесь мы можем хранить строковое представление адреса для истории
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица позиций заказов
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(150) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица отзывов
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product_review (user_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Вставка пользователей
INSERT INTO users (username, password, email, first_name, last_name, role) VALUES
('root', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@magic.shop', 'Админ', 'Главный', 'admin'),
('moderator', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'moderator@magic.shop', 'Модер', 'Главный', 'moderator');

-- Вставка тестовых категорий (НОВОЕ)
INSERT INTO categories (name) VALUES ('Палочки'), ('Зелья'), ('Артефакты'), ('Транспорт'), ('Книги');

-- Вставка тестовых товаров (ИЗМЕНЕНО: Используем category_id вместо строки)
INSERT INTO products (name, description, price, image, category_id, stock, is_new, created_by) VALUES
('Волшебная палочка', 'Классическая волшебная палочка из бузины с сердцевиной из пера феникса. Идеально подходит для начинающих магов.', 1500.00, 'product/wand1.jpg', 1, 50, 1, 1),
('Зелье невидимости', 'Эффективное зелье невидимости, действует до 6 часов. Приготовлено по старинному рецепту.', 800.00, 'product/potion1.jpg', 2, 100, 1, 1),
('Мантия-невидимка', 'Настоящая мантия-невидимка. Делает владельца полностью невидимым. Размер универсальный.', 5000.00, 'product/cloak1.jpg', 3, 10, 1, 1),
('Летающая метла', 'Спортивная метла последнего поколения. Скорость до 200 км/ч. В комплекте подставка.', 12000.00, 'product/broom1.jpg', 4, 15, 1, 1),
('Магический кристалл', 'Кристалл для предсказаний. Помогает увидеть будущее. Размер 15 см.', 3500.00, 'product/crystal1.jpg', 3, 25, 1, 1),
('Книга заклинаний', 'Полное собрание заклинаний от beginners до master level. 500+ заклинаний.', 2000.00, 'product/book1.jpg', 5, 75, 1, 1);
