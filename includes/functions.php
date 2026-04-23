<?php
/**
 * Общие функции для "Волшебная ЛАВКА"
 * Разработчик: АВВА © 2025
 */

// Подключение к базе данных
require_once __DIR__ . '/config.php';

// Проверяем и создаем соединение, если его нет
try {
    if (!isset($mysqli)) {
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($mysqli->connect_error) {
            die("Ошибка подключения к базе данных: " . $mysqli->connect_error);
        }
        
        $mysqli->set_charset("utf8mb4");
        echo "Подключение к базе данных успешно!<br>";
    }
} catch (Exception $e) {
    die("Ошибка подключения: " . $e->getMessage());
}

/**
 * Функция для безопасного вывода данных
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Функция для проверки авторизации
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

/**
 * Функция для проверки прав доступа
 */
function hasRole($role) {
    if (!isset($_SESSION['user_role'])) {
        return false;
    }
    return $_SESSION['user_role'] === $role || $_SESSION['user_role'] === 'admin';
}

/**
 * Функция для проверки прав администратора
 */
function isAdmin() {
    return hasRole('admin');
}

/**
 * Функция для проверки прав модератора
 */
function isModerator() {
    return hasRole('moderator');
}

/**
 * Функция для проверки прав пользователя
 */
function isCustomer() {
    return hasRole('customer');
}

/**
 * Функция для проверки прав администратора или модератора
 */
function isAdminOrModerator() {
    return isAdmin() || isModerator();
}

/**
 * Функция для получения текущего пользователя
 */
function getCurrentUser() {
    global $mysqli;
    
    // Проверяем, что пользователь авторизован
    if (!isLoggedIn()) {
        return null;
    }
    
    // Проверяем соединение с БД
    if (!$mysqli) {
        error_log("Нет соединения с базой данных");
        return null;
    }
    
    try {
        $user_id = (int)$_SESSION['user_id'];
        $stmt = $mysqli->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        return $user;
    } catch (Exception $e) {
        error_log("Ошибка в getCurrentUser: " . $e->getMessage());
        return null;
    }
}

/**
 * Функция для получения текущего пользователя с ролями
 */
function getCurrentUserWithRoles() {
    $user = getCurrentUser();
    
    if (!$user) {
        return null;
    }
    
    // Добавляем дополнительные поля для ролей
    $user['is_admin'] = isAdmin();
    $user['is_moderator'] = isModerator();
    $user['is_customer'] = isCustomer();
    $user['is_admin_or_moderator'] = isAdminOrModerator();
    
    return $user;
}

/**
 * Получение всех товаров из каталога
 * Обновлено для работы с category_id
 */
function getProducts($category_id = null, $limit = null, $new_only = false) {
    global $mysqli;
    
    // Проверяем соединение
    if (!$mysqli) {
        error_log("Нет соединения с базой данных");
        return [];
    }
    
    // Проверяем, что category_id - число
    if ($category_id !== null && !is_numeric($category_id)) {
        return [];
    }
    
    $sql = "SELECT p.*, u.username as seller_name, c.name as category_name 
            FROM products p 
            LEFT JOIN users u ON p.created_by = u.id 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE 1=1";
    $params = [];
    $types = '';
    
    if ($category_id) {
        $sql .= " AND p.category_id = ?";
        $params[] = $category_id;
        $types .= 'i';
    }
    
    if ($new_only) {
        $sql .= " AND p.is_new = 1";
    }
    
    $sql .= " ORDER BY p.created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT ?";
        $params[] = (int)$limit;
        $types .= 'i';
    }
    
    try {
        $stmt = $mysqli->prepare($sql);
        
        if (!$stmt) {
            error_log("Ошибка подготовки запроса: " . $mysqli->error);
            return [];
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            error_log("Ошибка выполнения запроса: " . $stmt->error);
            return [];
        }
        
        $result = $stmt->get_result();
        $products = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $products;
    } catch (Exception $e) {
        error_log("Ошибка в getProducts: " . $e->getMessage());
        return [];
    }
}

/**
 * Получение последних товаров
 * @param int $limit Количество товаров для получения
 * @return array Массив товаров
 */
function getLatestProducts($limit = 3) {
    global $mysqli;
    
    // Проверяем соединение
    if (!$mysqli) {
        error_log("Нет соединения с базой данных");
        return [];
    }
    
    // Проверяем, что limit - это число
    $limit = (int)$limit;
    if ($limit <= 0) {
        $limit = 3;
    }
    
    try {
        $stmt = $mysqli->prepare("
            SELECT p.*, u.username as seller_name, c.name as category_name 
            FROM products p 
            LEFT JOIN users u ON p.created_by = u.id 
            LEFT JOIN categories c ON p.category_id = c.id 
            ORDER BY p.created_at DESC 
            LIMIT ?
        ");
        
        if (!$stmt) {
            error_log("Ошибка подготовки запроса: " . $mysqli->error);
            return [];
        }
        
        $stmt->bind_param("i", $limit);
        
        if (!$stmt->execute()) {
            error_log("Ошибка выполнения запроса: " . $stmt->error);
            $stmt->close();
            return [];
        }
        
        $result = $stmt->get_result();
        $products = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $products;
    } catch (Exception $e) {
        error_log("Ошибка в getLatestProducts: " . $e->getMessage());
        return [];
    }
}

/**
 * Получение товара по ID
 */
function getProductById($id) {
    global $mysqli;
    
    // Проверяем соединение
    if (!$mysqli) {
        error_log("Нет соединения с базой данных");
        return null;
    }
    
    // Проверяем, что id - число
    if (!is_numeric($id)) {
        return null;
    }
    
    try {
        $stmt = $mysqli->prepare("SELECT p.*, u.username as seller_name, c.name as category_name 
                                  FROM products p 
                                  LEFT JOIN users u ON p.created_by = u.id 
                                  LEFT JOIN categories c ON p.category_id = c.id 
                                  WHERE p.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $stmt->close();
        
        return $product;
    } catch (Exception $e) {
        error_log("Ошибка в getProductById: " . $e->getMessage());
        return null;
    }
}

/**
 * Возвращает текстовое название статуса заказа по его ID.
 */
function getOrderStatusName($statusId) {
    $statuses = [
        0 => 'Отменен',
        1 => 'В ожидании',
        2 => 'В обработке',
        3 => 'Отправлен',
        4 => 'Выполнен',
    ];
    return isset($statuses[$statusId]) ? $statuses[$statusId] : 'Неизвестно';
}

/**
 * Получает список товаров для конкретного заказа.
 */
function getOrderItems($order_id) {
    global $mysqli;
    
    // Проверяем соединение
    if (!$mysqli) {
        error_log("Нет соединения с базой данных");
        return [];
    }
    
    // Проверяем, что order_id - число
    if (!is_numeric($order_id)) {
        return [];
    }
    
    try {
        $items = [];
        $stmt = $mysqli->prepare("SELECT product_name, quantity FROM order_items WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result) {
            $items = $result->fetch_all(MYSQLI_ASSOC);
        }
        
        $stmt->close();
        return $items;
    } catch (Exception $e) {
        error_log("Ошибка в getOrderItems: " . $e->getMessage());
        return [];
    }
}

/**
 * Получение всех категорий (для datalist в магазине)
 * Возвращает массив имен строк
 */
function getCategories() {
    global $mysqli;
    
    // Проверяем соединение
    if (!$mysqli) {
        error_log("Нет соединения с базой данных");
        return [];
    }
    
    try {
        $sql = "SELECT name FROM categories ORDER BY name ASC";
        $result = $mysqli->query($sql);
        
        $categories = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row['name'];
            }
            $result->free();
        }
        
        return $categories;
    } catch (Exception $e) {
        error_log("Ошибка в getCategories: " . $e->getMessage());
        return [];
    }
}

/**
 * Получение списка всех категорий с ID (для select в админке)
 * Возвращает массив объектов ['id' => ..., 'name' => ...]
 */
function getCategoriesList() {
    global $mysqli;
    
    // Проверяем соединение
    if (!$mysqli) {
        error_log("Нет соединения с базой данных");
        return [];
    }
    
    try {
        $sql = "SELECT id, name FROM categories ORDER BY name ASC";
        $result = $mysqli->query($sql);
        
        $categories = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
            $result->free();
        }
        
        return $categories;
    } catch (Exception $e) {
        error_log("Ошибка в getCategoriesList: " . $e->getMessage());
        return [];
    }
}

/**
 * Получение категории по ID
 */
function getCategoryById($category_id) {
    global $mysqli;
    
    // Проверяем соединение
    if (!$mysqli) {
        error_log("Нет соединения с базой данных");
        return null;
    }
    
    // Проверяем, что category_id - число
    if (!$category_id || !is_numeric($category_id)) {
        return null;
    }
    
    try {
        $stmt = $mysqli->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $category = $result->fetch_assoc();
        $stmt->close();
        
        return $category;
    } catch (Exception $e) {
        error_log("Ошибка в getCategoryById: " . $e->getMessage());
        return null;
    }
}

/**
 * Добавление товара в корзину (session-based)
 */
function addToCart($product_id, $quantity = 1) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    $product_id = (int)$product_id;
    $quantity = (int)$quantity;
    
    if ($quantity <= 0) {
        return ['success' => false, 'message' => 'Некорректное количество'];
    }
    
    $product = getProductById($product_id);
    
    if (!$product) {
        return ['success' => false, 'message' => 'Товар не найден'];
    }
    
    if ($product['stock'] < $quantity) {
        return ['success' => false, 'message' => 'Недостаточно товара на складе'];
    }
    
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = [
            'product_id' => $product_id,
            'name' => $product['name'],
            'price' => $product['price'],
            'image' => $product['image'],
            'quantity' => $quantity,
            'stock' => $product['stock']
        ];
    }
    
    return [
        'success' => true,
        'message' => 'Товар добавлен в корзину',
        'cart_count' => getCartCount()
    ];
}

/**
 * Получение содержимого корзины
 */
function getCart() {
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return [];
    }
    return $_SESSION['cart'];
}

/**
 * Получение общей суммы корзины
 */
function getCartTotal() {
    $cart = getCart();
    $total = 0;
    foreach ($cart as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}

/**
 * Форматирует цену в удобный для чтения вид.
 */
function formatPrice($price) {
    $price = number_format($price, 2, '.', ' ');
    return $price . ' ₽';
}

/**
 * Удаление товара из корзины
 */
function removeFromCart($product_id) {
    $product_id = (int)$product_id;
    
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return [
            'success' => false,
            'message' => 'Корзина пуста',
            'cart_count' => 0,
            'cart_total' => 0
        ];
    }
    
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
    }
    
    return [
        'success' => true,
        'message' => 'Товар удален из корзины',
        'cart_count' => getCartCount(),
        'cart_total' => getCartTotal()
    ];
}

/**
 * Получение количества товаров в корзине
 */
function getCartCount() {
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return 0;
    }
    $count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $count += $item['quantity'];
    }
    return $count;
}

/**
 * Обновление количества товара в корзине
 */
function updateCartQuantity($product_id, $quantity) {
    $product_id = (int)$product_id;
    $quantity = (int)$quantity;
    
    if ($quantity <= 0) {
        return removeFromCart($product_id);
    }
    
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return [
            'success' => false,
            'message' => 'Корзина пуста',
            'cart_count' => 0,
            'cart_total' => 0
        ];
    }
    
    if (!isset($_SESSION['cart'][$product_id])) {
        return [
            'success' => false,
            'message' => 'Товар не найден в корзине',
            'cart_count' => getCartCount(),
            'cart_total' => getCartTotal()
        ];
    }
    
    $product = getProductById($product_id);
    if ($product && $product['stock'] < $quantity) {
        return [
            'success' => false,
            'message' => 'Недостаточно товара на складе',
            'cart_count' => getCartCount(),
            'cart_total' => getCartTotal()
        ];
    }
    
    $_SESSION['cart'][$product_id]['quantity'] = $quantity;
    $item_total = $_SESSION['cart'][$product_id]['price'] * $quantity;
    
    return [
        'success' => true,
        'message' => 'Количество обновлено',
        'cart_count' => getCartCount(),
        'item_total' => $item_total,
        'cart_total' => getCartTotal()
    ];
}

/**
 * Очистка корзины
 */
function clearCart() {
    $_SESSION['cart'] = [];
}

/**
 * Регистрация нового пользователя
 */
function registerUser($username, $email, $password, $first_name = '', $last_name = '') {
    global $mysqli;
    
    // Проверяем соединение
    if (!$mysqli) {
        return ['success' => false, 'message' => 'Ошибка соединения с базой данных'];
    }
    
    // Проверяем, что данные не пустые
    if (empty($username) || empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'Заполните все обязательные поля'];
    }
    
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
    
    $stmt = $mysqli->prepare("INSERT INTO users (username, email, password, first_name, last_name, role) VALUES (?, ?, ?, ?, ?, 'customer')");
    $stmt->bind_param("sssss", $username, $email, $password_hash, $first_name, $last_name);
    
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        $stmt->close();
        
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['user_role'] = 'customer';
        
        return ['success' => true, 'message' => 'Регистрация прошла успешно', 'user_id' => $user_id];
    } else {
        $stmt->close();
        return ['success' => false, 'message' => 'Ошибка при регистрации'];
    }
}

/**
 * Создание заказа из корзины
 */
function createOrder($delivery_address, $comment = '') {
    global $mysqli;
    
    // Проверяем соединение
    if (!$mysqli) {
        return ['success' => false, 'message' => 'Ошибка соединения с базой данных'];
    }
    
    $cart = getCart();
    
    if (empty($cart)) {
        return ['success' => false, 'message' => 'Корзина пуста'];
    }
    
    if (!isLoggedIn()) {
        return ['success' => false, 'message' => 'Необходимо авторизоваться'];
    }
    
    $user_id = $_SESSION['user_id'];
    $total_amount = getCartTotal();
    
    $mysqli->begin_transaction();
    
    try {
        $stmt = $mysqli->prepare("INSERT INTO orders (user_id, total_amount, status, delivery_address, comment) VALUES (?, ?, 'pending', ?, ?)");
        $stmt->bind_param("idss", $user_id, $total_amount, $delivery_address, $comment);
        $stmt->execute();
        $order_id = $stmt->insert_id;
        $stmt->close();
        
        foreach ($cart as $item) {
            $subtotal = $item['price'] * $item['quantity'];
            
            $stmt = $mysqli->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisiid", $order_id, $item['product_id'], $item['name'], $item['quantity'], $item['price'], $subtotal);
            $stmt->execute();
            $stmt->close();
            
            $stmt = $mysqli->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $stmt->bind_param("ii", $item['quantity'], $item['product_id']);
            $stmt->execute();
            $stmt->close();
        }
        
        $mysqli->commit();
        clearCart();
        
        return ['success' => true, 'message' => 'Заказ создан успешно', 'order_id' => $order_id];
        
    } catch (Exception $e) {
        $mysqli->rollback();
        return ['success' => false, 'message' => 'Ошибка при создании заказа: ' . $e->getMessage()];
    }
}

/**
 * Получение заказов пользователя
 */
function getUserOrders($user_id) {
    global $mysqli;
    
    // Проверяем соединение
    if (!$mysqli) {
        error_log("Нет соединения с базой данных");
        return [];
    }
    
    // Проверяем, что user_id - число
    if (!is_numeric($user_id)) {
        return [];
    }
    
    try {
        $stmt = $mysqli->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $orders = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $orders;
    } catch (Exception $e) {
        error_log("Ошибка в getUserOrders: " . $e->getMessage());
        return [];
    }
}

/**
 * Получение деталей заказа
 */
function getOrderDetails($order_id, $user_id) {
    global $mysqli;
    
    // Проверяем соединение
    if (!$mysqli) {
        error_log("Нет соединения с базой данных");
        return null;
    }
    
    // Проверяем, что order_id и user_id - числа
    if (!is_numeric($order_id) || !is_numeric($user_id)) {
        return null;
    }
    
    try {
        $stmt = $mysqli->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $order_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        $stmt->close();
        
        if ($order) {
            $stmt = $mysqli->prepare("SELECT * FROM order_items WHERE order_id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $order['items'] = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
        
        return $order;
    } catch (Exception $e) {
        error_log("Ошибка в getOrderDetails: " . $e->getMessage());
        return null;
    }
}

/**
 * Обновление статуса заказа (оплата)
 */
function updateOrderStatus($order_id, $status) {
    global $mysqli;
    
    // Проверяем соединение
    if (!$mysqli) {
        return ['success' => false, 'message' => 'Ошибка соединения с базой данных'];
    }
    
    // Проверяем, что order_id - число
    if (!is_numeric($order_id)) {
        return ['success' => false, 'message' => 'Неверный ID заказа'];
    }
    
    $allowed_statuses = ['pending', 'payment', 'completed', 'cancelled'];
    if (!in_array($status, $allowed_statuses)) {
        return ['success' => false, 'message' => 'Недопустимый статус'];
    }
    
    try {
        $stmt = $mysqli->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $order_id);
        
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Статус заказа обновлен'];
        } else {
            $stmt->close();
            return ['success' => false, 'message' => 'Ошибка при обновлении статуса'];
        }
    } catch (Exception $e) {
        error_log("Ошибка в updateOrderStatus: " . $e->getMessage());
        return ['success' => false, 'message' => 'Ошибка при обновлении статуса'];
    }
}

/**
 * Добавление отзыва
 */
function addReview($user_id, $product_id, $rating, $comment) {
    global $mysqli;
    
    // Проверяем соединение
    if (!$mysqli) {
        return ['success' => false, 'message' => 'Ошибка соединения с базой данных'];
    }
    
    // Проверяем, что все данные - числа
    if (!is_numeric($user_id) || !is_numeric($product_id) || !is_numeric($rating)) {
        return ['success' => false, 'message' => 'Неверные данные'];
    }
    
    // Проверяем рейтинг
    if ($rating < 1 || $rating > 5) {
        return ['success' => false, 'message' => 'Неверный рейтинг'];
    }
    
    if (!canReviewProduct($user_id, $product_id)) {
        return ['success' => false, 'message' => 'Вы уже оставляли отзыв на этот товар'];
    }
    
    try {
        $stmt = $mysqli->prepare("INSERT INTO reviews (user_id, product_id, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $user_id, $product_id, $rating, $comment);
        
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Отзыв добавлен'];
        } else {
            $stmt->close();
            return ['success' => false, 'message' => 'Ошибка при добавлении отзыва'];
        }
    } catch (Exception $e) {
        error_log("Ошибка в addReview: " . $e->getMessage());
        return ['success' => false, 'message' => 'Ошибка при добавлении отзыва'];
    }
}

/**
 * Получение отзывов для товара
 */
function getProductReviews($product_id, $limit = null) {
    global $mysqli;
    
    // Проверяем соединение
    if (!$mysqli) {
        error_log("Нет соединения с базой данных");
        return [];
    }
    
    // Проверяем, что product_id - число
    if (!is_numeric($product_id)) {
        return [];
    }
    
    try {
        $sql = "SELECT r.*, u.username, u.first_name, u.last_name 
                FROM reviews r 
                LEFT JOIN users u ON r.user_id = u.id 
                WHERE r.product_id = ? 
                ORDER BY r.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $reviews = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $reviews;
    } catch (Exception $e) {
        error_log("Ошибка в getProductReviews: " . $e->getMessage());
        return [];
    }
}

/**
 * Проверка возможности оставить отзыв
 */
function canReviewProduct($user_id, $product_id) {
    global $mysqli;
    
    // Проверяем соединение
    if (!$mysqli) {
        error_log("Нет соединения с базой данных");
        return false;
    }
    
    // Проверяем, что user_id и product_id - числа
    if (!is_numeric($user_id) || !is_numeric($product_id)) {
        return false;
    }
    
    try {
        $stmt = $mysqli->prepare("SELECT COUNT(*) as count 
                                  FROM orders o 
                                  JOIN order_items oi ON o.id = oi.order_id 
                                  WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'completed'");
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if ($row['count'] == 0) {
            return false;
        }
        
        $stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM reviews WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row['count'] == 0;
    } catch (Exception $e) {
        error_log("Ошибка в canReviewProduct: " . $e->getMessage());
        return false;
    }
}

/**
 * Получение последних отзывов
 */
function getLatestReviews($limit = 5) {
    global $mysqli;
    
    // Проверяем соединение
    if (!$mysqli) {
        error_log("Нет соединения с базой данных");
        return [];
    }
    
    try {
        $stmt = $mysqli->prepare("SELECT r.*, u.username, p.name as product_name 
                                  FROM reviews r 
                                  LEFT JOIN users u ON r.user_id = u.id 
                                  LEFT JOIN products p ON r.product_id = p.id 
                                  ORDER BY r.created_at DESC 
                                  LIMIT ?");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $reviews = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $reviews;
    } catch (Exception $e) {
        error_log("Ошибка в getLatestReviews: " . $e->getMessage());
        return [];
    }
}

/**
 * Загрузка изображения товара
 */
function uploadProductImage($file) {
    $upload_dir = __DIR__ . '/../images/product/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    $max_size = 5 * 1024 * 1024; //5MB
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_extensions)) {
        return ['success' => false, 'message' => 'Недопустимый формат файла'];
    }
    
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'Файл слишком большой (макс. 5MB)'];
    }
    
    $new_filename = uniqid('product_') . '.' . $file_extension;
    $destination = $upload_dir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => true, 'filename' => 'product/' . $new_filename];
    } else {
        return ['success' => false, 'message' => 'Ошибка при загрузке файла'];
    }
}

/**
 * Форматирует адрес пользователя в одну строку из отдельных полей.
 * 
 * @param array $user Массив данных пользователя
 * @return string Строка с адресом
 */
function formatUserAddress($user) {
    if (empty($user)) {
        return '';
    }
    
    $parts = [];
    
    if (!empty($user['zip_code'])) {
        $parts[] = htmlspecialchars($user['zip_code'], ENT_QUOTES, 'UTF-8');
    }
    if (!empty($user['region'])) {
        $parts[] = htmlspecialchars($user['region'] . ' обл.', ENT_QUOTES, 'UTF-8');
    }
    if (!empty($user['city'])) {
        $parts[] = 'г. ' . htmlspecialchars($user['city'], ENT_QUOTES, 'UTF-8');
    }
    if (!empty($user['street'])) {
        $parts[] = 'ул. ' . htmlspecialchars($user['street'], ENT_QUOTES, 'UTF-8');
    }
    if (!empty($user['house'])) {
        $parts[] = 'д. ' . htmlspecialchars($user['house'], ENT_QUOTES, 'UTF-8');
    }
    if (!empty($user['apartment'])) {
        $parts[] = 'кв. ' . htmlspecialchars($user['apartment'], ENT_QUOTES, 'UTF-8');
    }
    
    return implode(', ', $parts);
}

/**
 * Обновление данных пользователя
 */
function updateUser($user_id, $data) {
    global $mysqli;
    
    // Проверяем соединение
    if (!$mysqli) {
        return false;
    }
    
    // Проверяем, что user_id - число
    if (!is_numeric($user_id)) {
        return false;
    }
    
    // Проверяем, что данные не пустые
    if (empty($data)) {
        return false;
    }
    
    // Извлекаем переменные
    $username = isset($data['username']) ? trim($data['username']) : null;
    $email = isset($data['email']) ? trim($data['email']) : null;
    $last_name = isset($data['last_name']) ? trim($data['last_name']) : null;
    $first_name = isset($data['first_name']) ? trim($data['first_name']) : null;
    $middle_name = isset($data['middle_name']) ? trim($data['middle_name']) : null;
    $phone = isset($data['phone']) ? trim($data['phone']) : null;
    $zip_code = isset($data['zip_code']) ? trim($data['zip_code']) : null;
    $region = isset($data['region']) ? trim($data['region']) : null;
    $city = isset($data['city']) ? trim($data['city']) : null;
    $street = isset($data['street']) ? trim($data['street']) : null;
    $house = isset($data['house']) ? trim($data['house']) : null;
    $apartment = isset($data['apartment']) ? trim($data['apartment']) : null;
    $password = isset($data['password']) ? trim($data['password']) : null;

    // Проверяем уникальность username и email, если они изменились
    if ($username) {
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->bind_param("si", $username, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            return false;
        }
    }
    
    // Аналогично для email
    if ($email) {
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            return false;
        }
    }

    // Подготовка запроса
    if (!empty($password)) {
        $sql = "UPDATE users SET 
                    username = ?, email = ?, last_name = ?, first_name = ?, middle_name = ?, 
                    phone = ?, zip_code = ?, region = ?, city = ?, street = ?, house = ?, apartment = ?, 
                    password = ? 
                    WHERE id = ?";
        
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt->bind_param("ssssssssssssi", 
            $username, $email, $last_name, $first_name, $middle_name, 
            $phone, $zip_code, $region, $city, $street, $house, $apartment, 
            $password_hash, 
            $user_id
        );
    } else {
        $sql = "UPDATE users SET 
                    username = ?, email = ?, last_name = ?, first_name = ?, middle_name = ?, 
                    phone = ?, zip_code = ?, region = ?, city = ?, street = ?, house = ?, apartment = ? 
                    WHERE id = ?";
        
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("sssssssssssi", 
            $username, $email, $last_name, $first_name, $middle_name, 
            $phone, $zip_code, $region, $city, $street, $house, $apartment, 
            $user_id
        );
    }

    // Выполнение
    if ($stmt->execute()) {
        $stmt->close();
        
        // Обновляем сессию, если изменились имя или email
        if ($username && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id) {
            $_SESSION['username'] = $username;
        }
        if ($email && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id) {
            $_SESSION['email'] = $email;
        }
        
        return true;
    } else {
        $stmt->close();
        return false;
    }
}
