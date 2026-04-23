<?php
/**
 * Функции администратора для "Волшебная ЛАВКА"
 * Разработчик: АВВА © 2025
 */

/**
 * Получение всех пользователей
 */
function getAllUsers() {
    global $mysqli;
    
    $result = $mysqli->query("SELECT id, username, email, first_name, last_name, role, phone, avatar, created_at FROM users ORDER BY created_at DESC");
    $users = $result->fetch_all(MYSQLI_ASSOC);
    
    return $users;
}

/**
 * Добавление пользователя (только модератора админом)
 */
function addUser($username, $email, $password, $first_name, $last_name, $role) {
    global $mysqli;
    
    if ($role !== 'moderator') {
        return ['success' => false, 'message' => 'Админ может добавлять только модераторов'];
    }
    
    // Проверка существования
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt->close();
        return ['success' => false, 'message' => 'Пользователь с таким именем или email уже существует'];
    }
    $stmt->close();
    
    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    
    $stmt = $mysqli->prepare("INSERT INTO users (username, email, password, first_name, last_name, role) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $username, $email, $password_hash, $first_name, $last_name, $role);
    
    if ($stmt->execute()) {
        $insert_id = $stmt->insert_id;
        $stmt->close();
        return ['success' => true, 'message' => 'Пользователь добавлен', 'id' => $insert_id];
    } else {
        $stmt->close();
        return ['success' => false, 'message' => 'Ошибка при регистрации'];
    }
}

/**
 * Удаление пользователя
 */
function deleteUser($user_id) {
    global $mysqli;
    
    // Нельзя удалить себя
    if ($user_id == $_SESSION['user_id']) {
        return ['success' => false, 'message' => 'Нельзя удалить собственный аккаунт'];
    }
    
    $stmt = $mysqli->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Пользователь удален'];
    } else {
        $stmt->close();
        return ['success' => false, 'message' => 'Ошибка при удалении пользователя'];
    }
}

/**
 * Сброс пароля пользователя
 */
function resetUserPassword($user_id, $new_password) {
    global $mysqli;
    
    $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
    
    $stmt = $mysqli->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $password_hash, $user_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Пароль изменен'];
    } else {
        $stmt->close();
        return ['success' => false, 'message' => 'Ошибка при смене пароля'];
    }
}

// ==================== ТОВАРЫ ====================

/**
 * Получение всех товаров (для админа)
 */
function getAllProducts() {
    global $mysqli;
    
    $result = $mysqli->query("SELECT p.*, u.username as seller_name, c.name as category_name 
                              FROM products p 
                              LEFT JOIN users u ON p.created_by = u.id 
                              LEFT JOIN categories c ON p.category_id = c.id 
                              ORDER BY p.created_at DESC");
    $products = $result->fetch_all(MYSQLI_ASSOC);
    
    return $products;
}

/**
 * Добавление товара админом
 */
function addProduct($name, $description, $price, $category_id, $stock, $is_new, $image_path, $created_by) {
    global $mysqli;
    
    // Если категория не выбрана (0 или null), сохраняем как NULL
    $final_category_id = (!empty($category_id) && $category_id > 0) ? (int)$category_id : null;
    
    $stmt = $mysqli->prepare("INSERT INTO products (name, description, price, image, category_id, stock, is_new, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdsiiis", $name, $description, $price, $image_path, $final_category_id, $stock, $is_new, $created_by);
    
    if ($stmt->execute()) {
        $insert_id = $stmt->insert_id;
        $stmt->close();
        return ['success' => true, 'id' => $insert_id];
    } else {
        $stmt->close();
        return ['success' => false, 'message' => 'Ошибка при добавлении товара'];
    }
}

/**
 * Редактирование товара
 */
function editProduct($id, $name, $description, $price, $category_id, $stock, $is_new, $image_path) {
    global $mysqli;
    
    // Если категория не выбрана (0 или null), сохраняем как NULL
    $final_category_id = (!empty($category_id) && $category_id > 0) ? (int)$category_id : null;
    
    $stmt = $mysqli->prepare("UPDATE products SET name = ?, description = ?, price = ?, category_id = ?, stock = ?, is_new = ?, image = ? WHERE id = ?");
    $stmt->bind_param("ssdsiisi", $name, $description, $price, $final_category_id, $stock, $is_new, $image_path, $id);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true];
    } else {
        $stmt->close();
        return ['success' => false, 'message' => 'Ошибка при обновлении товара'];
    }
}

/**
 * Удаление товара
 */
function deleteProduct($id) {
    global $mysqli;
    
    // Получаем путь к изображению
    $product = getProductById($id); // Используем функцию из functions.php
    
    $stmt = $mysqli->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // Удаляем изображение, если оно было
        if ($product && $product['image'] && file_exists(__DIR__ . '/../images/' . $product['image'])) {
            unlink(__DIR__ . '/../images/' . $product['image']);
        }
        
        $stmt->close();
        return ['success' => true, 'message' => 'Товар удален'];
    } else {
        $stmt->close();
        return ['success' => false, 'message' => 'Ошибка при удалении товара'];
    }
}

// ==================== ЗАКАЗЫ ====================

/**
 * Получение всех заказов
 */
function getAllOrders($status = null) {
    global $mysqli;
    
    $sql = "SELECT o.*, u.username, u.email 
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id 
            ORDER BY o.created_at DESC";
    
    if ($status) {
        $sql = "SELECT o.*, u.username, u.email 
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id 
                WHERE o.status = ? 
                ORDER BY o.created_at DESC";
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("s", $status);
        $stmt->execute();
        $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        $result = $mysqli->query($sql);
        $orders = $result->fetch_all(MYSQLI_ASSOC);
    }
    
    return $orders;
}

/**
 * Получение деталей любого заказа
 */
function getOrderDetailsAdmin($order_id) {
    global $mysqli;
    
    $stmt = $mysqli->prepare("SELECT o.*, u.username, u.email, u.phone, u.first_name, u.last_name 
                              FROM orders o 
                              LEFT JOIN users u ON o.user_id = u.id 
                              WHERE o.id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();
    
    if ($order) {
        $stmt = $mysqli->prepare("SELECT oi.*, p.image as product_image 
                                  FROM order_items oi 
                                  LEFT JOIN products p ON oi.product_id = p.id 
                                  WHERE oi.order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $order['items'] = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
    
    return $order;
}

/**
 * Изменение статуса заказа (оплата)
 */
function updateOrderStatusAdmin($order_id, $status) {
    global $mysqli;
    
    $allowed_statuses = ['pending', 'payment', 'completed', 'cancelled'];
    
    if (!in_array($status, $allowed_statuses)) {
        return ['success' => false, 'message' => 'Недопустимый статус'];
    }
    
    $stmt = $mysqli->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $order_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Статус заказа обновлен'];
    } else {
        $stmt->close();
        return ['success' => false, 'message' => 'Ошибка при обновлении статуса'];
    }
}

// ==================== ОТЗЫВЫ ====================

/**
 * Получение всех отзывов
 */
function getAllReviews() {
    global $mysqli;
    
    $result = $mysqli->query("SELECT r.*, u.username, p.name as product_name 
                              FROM reviews r 
                              LEFT JOIN users u ON r.user_id = u.id 
                              LEFT JOIN products p ON r.product_id = p.id 
                              ORDER BY r.created_at DESC");
    $reviews = $result->fetch_all(MYSQLI_ASSOC);
    
    return $reviews;
}

/**
 * Удаление отзыва
 */
function deleteReview($id) {
    global $mysqli;
    
    $stmt = $mysqli->prepare("DELETE FROM reviews WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Отзыв удален'];
    } else {
        $stmt->close();
        return ['success' => false, 'message' => 'Ошибка при удалении отзыва'];
    }
}

// ==================== СТАТИСТИКА ====================

/**
 * Получение статистики для дашборда админа
 */
function getAdminStats() {
    global $mysqli;
    
    $stats = [];
    
    // Количество пользователей
    $result = $mysqli->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'");
    $row = $result->fetch_assoc();
    $stats['customers'] = $row['count'];
    
    // Количество модераторов
    $result = $mysqli->query("SELECT COUNT(*) as count FROM users WHERE role = 'moderator'");
    $row = $result->fetch_assoc();
    $stats['moderators'] = $row['count'];
    
    // Количество товаров
    $result = $mysqli->query("SELECT COUNT(*) as count FROM products");
    $row = $result->fetch_assoc();
    $stats['products'] = $row['count'];
    
    // Количество заказов
    $result = $mysqli->query("SELECT COUNT(*) as count FROM orders");
    $row = $result->fetch_assoc();
    $stats['orders'] = $row['count'];
    
    // Общая выручка
    $result = $mysqli->query("SELECT SUM(total_amount) as total FROM orders WHERE status = 'completed'");
    $row = $result->fetch_assoc();
    $stats['revenue'] = $row['total'] ?? 0;
    
    // Количество отзывов
    $result = $mysqli->query("SELECT COUNT(*) as count FROM reviews");
    $row = $result->fetch_assoc();
    $stats['reviews'] = $row['count'];
    
    return $stats;
}

// ==================== ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ КАТЕГОРИЙ ====================

/**
 * Получение категории по её имени
 */
function getCategoryByName($category_name) {
    global $mysqli;
    
    if (empty($category_name)) return null;
    
    $stmt = $mysqli->prepare("SELECT * FROM categories WHERE name = ?");
    $stmt->bind_param("s", $category_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $category = $result->fetch_assoc();
    $stmt->close();
    
    return $category;
}

/**
 * Добавление новой категории
 */
function addCategory($name) {
    global $mysqli;
    
    $stmt = $mysqli->prepare("INSERT INTO categories (name) VALUES (?)");
    $stmt->bind_param("s", $name);
    
    if ($stmt->execute()) {
        // Сохраняем ID до закрытия запроса
        $insert_id = $stmt->insert_id;
        $stmt->close();
        return ['success' => true, 'id' => $insert_id];
    } else {
        $stmt->close();
        return ['success' => false, 'message' => 'Ошибка при добавлении категории'];
    }
}

/**
 * Получение всех категорий
 */
function getAllCategories() {
    global $mysqli;
    
    $result = $mysqli->query("SELECT * FROM categories ORDER BY name ASC");
    $categories = $result->fetch_all(MYSQLI_ASSOC);
    
    return $categories;
}

/**
 * Получение категории по ID
 */
function getCategoryByIdAdmin($id) {
    global $mysqli;
    
    $stmt = $mysqli->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $category = $result->fetch_assoc();
    $stmt->close();
    
    return $category;
}

/**
 * Удаление категории
 */
function deleteCategory($id) {
    global $mysqli;
    
    // Проверяем, есть ли товары в этой категории
    $stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if ($row['count'] > 0) {
        return ['success' => false, 'message' => 'Невозможно удалить категорию, в которой есть товары'];
    }
    
    $stmt = $mysqli->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Категория удалена'];
    } else {
        $stmt->close();
        return ['success' => false, 'message' => 'Ошибка при удалении категории'];
    }
}

/**
 * Подсчет общего количества категорий
 */
function countCategories() {
    global $mysqli;
    
    $result = $mysqli->query("SELECT COUNT(*) as count FROM categories");
    $row = $result->fetch_assoc();
    
    return $row['count'];
}

/**
 * Логирование действий администратора
 */
function logAdminAction($action) {
    global $mysqli;
    
    $user_id = $_SESSION['user_id'] ?? 0;
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    $stmt = $mysqli->prepare("INSERT INTO admin_actions (user_id, action, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("isss", $user_id, $action, $ip_address, $user_agent);
    
    if ($stmt->execute()) {
        $stmt->close();
        return true;
    } else {
        $stmt->close();
        return false;
    }
}

/**
 * Получение истории действий администратора
 */
function getAdminActions($limit = 50) {
    global $mysqli;
    
    $stmt = $mysqli->prepare("SELECT a.*, u.username 
                              FROM admin_actions a 
                              LEFT JOIN users u ON a.user_id = u.id 
                              ORDER BY a.created_at DESC 
                              LIMIT ?");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $actions = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $actions;
}

/**
 * Обновление категории
 */
function updateCategory($id, $name) {
    global $mysqli;
    
    $stmt = $mysqli->prepare("UPDATE categories SET name = ? WHERE id = ?");
    $stmt->bind_param("si", $name, $id);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Категория обновлена'];
    } else {
        $stmt->close();
        return ['success' => false, 'message' => 'Ошибка при обновлении категории'];
    }
}
