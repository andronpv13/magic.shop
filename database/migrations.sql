-- ============================================================================
-- ФАЙЛ МИГРАЦИЙ БАЗЫ ДАННЫХ
-- Описание: Исправление индексов, добавление полей для безопасности и функционала
-- Версия: 1.2 (Полностью совместимый синтаксис для MySQL 8.0)
-- ============================================================================

USE shop_db;

-- Индексы для таблицы users
ALTER TABLE users ADD INDEX idx_users_email (email);
ALTER TABLE users ADD INDEX idx_users_role (role);
ALTER TABLE users ADD INDEX idx_users_created_at (created_at);

-- Индексы для таблицы products
ALTER TABLE products ADD INDEX idx_products_category_id (category_id);
ALTER TABLE products ADD INDEX idx_products_price (price);
ALTER TABLE products ADD INDEX idx_products_stock (stock);
ALTER TABLE products ADD INDEX idx_products_active (is_active);
ALTER TABLE products ADD INDEX idx_products_created_at (created_at);

-- Индексы для таблицы categories
ALTER TABLE categories ADD INDEX idx_categories_parent_id (parent_id);
ALTER TABLE categories ADD INDEX idx_categories_slug (slug);

-- Индексы для таблицы orders
ALTER TABLE orders ADD INDEX idx_orders_user_id (user_id);
ALTER TABLE orders ADD INDEX idx_orders_status (status);
ALTER TABLE orders ADD INDEX idx_orders_created_at (created_at);
ALTER TABLE orders ADD INDEX idx_orders_token (order_token);

-- Индексы для таблицы order_items
ALTER TABLE order_items ADD INDEX idx_order_items_order_id (order_id);
ALTER TABLE order_items ADD INDEX idx_order_items_product_id (product_id);

-- Индексы для таблицы reviews
ALTER TABLE reviews ADD INDEX idx_reviews_product_id (product_id);
ALTER TABLE reviews ADD INDEX idx_reviews_user_id (user_id);
ALTER TABLE reviews ADD INDEX idx_reviews_rating (rating);
ALTER TABLE reviews ADD INDEX idx_reviews_is_approved (is_approved);

-- Индексы для таблицы password_resets
ALTER TABLE password_resets ADD INDEX idx_password_resets_email (email);
ALTER TABLE password_resets ADD INDEX idx_password_resets_expires_at (expires_at);

-- 2. ДОБАВЛЕНИЕ ПОЛЕЙ ДЛЯ БЕЗОПАСНОСТИ И ФУНКЦИОНАЛА

-- Добавление поля order_token для безопасной передачи ID заказа (Проблема #16)
ALTER TABLE orders ADD COLUMN order_token VARCHAR(64) UNIQUE AFTER id;

-- Добавление полей для модерации отзывов (Проблема #18)
ALTER TABLE reviews ADD COLUMN is_approved TINYINT(1) DEFAULT 0 AFTER comment;
ALTER TABLE reviews ADD COLUMN edited_at DATETIME NULL AFTER updated_at;

-- Добавление поля last_failed_login для Rate Limiting (Проблема #14)
ALTER TABLE users ADD COLUMN last_failed_login DATETIME NULL AFTER last_login;
ALTER TABLE users ADD COLUMN failed_login_attempts INT DEFAULT 0 AFTER last_failed_login;

-- Добавление поля image_processed для отслеживания обработки изображений (Проблема #11)
ALTER TABLE products ADD COLUMN image_processed TINYINT(1) DEFAULT 0 AFTER image;

-- 3. ОБНОВЛЕНИЕ СУЩЕСТВУЮЩИХ ЗАПИСЕЙ (если требуется)
-- Устанавливаем токены для существующих заказов
UPDATE orders SET order_token = MD5(CONCAT(id, '-', created_at, '-', RAND())) WHERE order_token IS NULL;