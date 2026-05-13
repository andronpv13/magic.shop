USE shop_db;
-- 1. Добавляем отсутствующие столбцы связей, если их нет
-- Для таблицы products
ALTER TABLE products ADD COLUMN IF NOT EXISTS category_id INT UNSIGNED DEFAULT NULL;

-- Для таблицы reviews (часто требуется product_id и user_id)
ALTER TABLE reviews ADD COLUMN IF NOT EXISTS product_id INT UNSIGNED DEFAULT NULL;
ALTER TABLE reviews ADD COLUMN IF NOT EXISTS user_id INT UNSIGNED DEFAULT NULL;

-- Для таблицы order_items (требуется order_id и product_id)
ALTER TABLE order_items ADD COLUMN IF NOT EXISTS order_id INT UNSIGNED DEFAULT NULL;
ALTER TABLE order_items ADD COLUMN IF NOT EXISTS product_id INT UNSIGNED DEFAULT NULL;

-- 2. Теперь создаем индексы (без IF NOT EXISTS, так как в MySQL это работает только через ALTER IGNORE или проверку)
-- Используем конструкцию, которая не выдаст ошибку, если индекс уже есть (в новых версиях) 
-- или просто создаем их. Если скрипт падает на повторном запуске, удалите индексы вручную перед запуском.

-- Индексы для users
ALTER TABLE users ADD INDEX idx_users_email (email);

-- Индексы для products
ALTER TABLE products ADD INDEX idx_products_category_id (category_id);
ALTER TABLE products ADD INDEX idx_products_slug (slug);

-- Индексы для orders
ALTER TABLE orders ADD INDEX idx_orders_user_id (user_id);
ALTER TABLE orders ADD INDEX idx_orders_status (status);
ALTER TABLE orders ADD INDEX idx_orders_created_at (created_at);

-- Индексы для order_items
ALTER TABLE order_items ADD INDEX idx_order_items_order_id (order_id);
ALTER TABLE order_items ADD INDEX idx_order_items_product_id (product_id);

-- Индексы для reviews
ALTER TABLE reviews ADD INDEX idx_reviews_product_id (product_id);
ALTER TABLE reviews ADD INDEX idx_reviews_user_id (user_id);

-- Индексы для categories (если есть slug)
ALTER TABLE categories ADD INDEX idx_categories_slug (slug);
ALTER TABLE categories ADD INDEX idx_categories_parent_id (parent_id);

-- 3. (Опционально) Добавляем внешние ключи для целостности данных
-- Внимание: выполните это только если уверены, что данные соответствуют типам
ALTER TABLE products 
  ADD CONSTRAINT fk_products_category 
  FOREIGN KEY (category_id) REFERENCES categories(id) 
  ON DELETE SET NULL;

ALTER TABLE orders 
  ADD CONSTRAINT fk_orders_user 
  FOREIGN KEY (user_id) REFERENCES users(id) 
  ON DELETE CASCADE;

ALTER TABLE reviews 
  ADD CONSTRAINT fk_reviews_product 
  FOREIGN KEY (product_id) REFERENCES products(id) 
  ON DELETE CASCADE,
  ADD CONSTRAINT fk_reviews_user 
  FOREIGN KEY (user_id) REFERENCES users(id) 
  ON DELETE CASCADE;

ALTER TABLE order_items 
  ADD CONSTRAINT fk_order_items_order 
  FOREIGN KEY (order_id) REFERENCES orders(id) 
  ON DELETE CASCADE,
  ADD CONSTRAINT fk_order_items_product 
  FOREIGN KEY (product_id) REFERENCES products(id) 
  ON DELETE RESTRICT;