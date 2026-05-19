<?php
// === Категории ===
function getCategories() {
    global $conn;
    // Получаем только категории, в которых есть активные товары
    $stmt = $conn->prepare("SELECT DISTINCT c.name FROM categories c
                            INNER JOIN products p ON c.id = p.category_id
                            WHERE p.active = 1
                            ORDER BY c.name");
    $stmt->execute();
    $result = $stmt->get_result();
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row['name'];
    }
    return $categories;
}

// === Товары ===
function getProducts($category = null, $limit = null) {
    global $conn;
    $sql = "SELECT p.*, c.name AS category_name, u.username as creator_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN users u ON p.created_by = u.id
            WHERE p.active = 1";

    if ($category) $sql .= " AND c.name = ?";
    if ($limit) $sql .= " LIMIT ?";

    $stmt = $conn->prepare($sql);
    if ($category && $limit) {
        $stmt->bind_param("si", $category, $limit);
    } elseif ($category) {
        $stmt->bind_param("s", $category);
    } elseif ($limit) {
        $stmt->bind_param("i", $limit);
    }

    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getProductById($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// === Администратор ===
function getAdminContactInfo() {
    global $conn;
    $stmt = $conn->prepare("SELECT email, phone FROM users WHERE role = 'admin' LIMIT 1");
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// === Пользователи ===
function getUserById($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $id); $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getUserByEmail($email) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function updateUserProfile($user_id, $name, $email) {
    global $conn;
    // Разделяем имя на first_name и last_name если есть пробел
    $name_parts = explode(' ', trim($name), 2);
    $first_name = $name_parts[0] ?? '';
    $last_name = $name_parts[1] ?? '';

    $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?");
    $stmt->bind_param("sssi", $first_name, $last_name, $email, $user_id);

    if ($stmt->execute()) {
        return ['success' => true];
    }
    return ['success' => false, 'message' => 'Ошибка при обновлении профиля'];
}

function changeUserPassword($user_id, $current_password, $new_password) {
    global $conn;
    $user = getUserById($user_id);

    if (!$user || !password_verify($current_password, $user['password'])) {
        return ['success' => false, 'message' => 'Неверный текущий пароль'];
    }

    $hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hash, $user_id);

    if ($stmt->execute()) {
        return ['success' => true];
    }
    return ['success' => false, 'message' => 'Ошибка при смене пароля'];
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return getUserById($_SESSION['user_id']);
}

function hasAnyRole($roles) {
    if (!isLoggedIn()) return false;
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    return in_array($_SESSION['role'], $roles, true);
}

// ✅ ДОБАВЛЕНО: Регистрация пользователя
function registerUser($username, $email, $password) {
    global $conn;
    // Проверка уникальности
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => 'Логин или email уже заняты'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'customer')");
    $stmt->bind_param("sss", $username, $email, $hash);

    if ($stmt->execute()) {
        $_SESSION['user_id'] = $conn->insert_id;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = 'customer';
        return ['success' => true];
    }
    return ['success' => false, 'message' => 'Ошибка при регистрации'];
}

// === Заказы ===
function createOrder($user_id, $items, $address = '', $comment = '') {
    global $conn;
    $conn->begin_transaction();
    try {
        $total = 0;
        foreach ($items as $item) $total += $item['price'] * $item['quantity'];

        // ✅ ИСПРАВЛЕНО: Добавлены адрес и комментарий в запрос
        // ✅ ИСПРАВЛЕНО: Добавлена генерация order_token для безопасного доступа к заказу
        $order_token = bin2hex(random_bytes(32)); // Генерируем уникальный токен из 64 символов

        $stmt = $conn->prepare("INSERT INTO orders (user_id, total, delivery_address, comment, status, order_token) VALUES (?, ?, ?, ?, 'pending', ?)");
        $stmt->bind_param("idsss", $user_id, $total, $address, $comment, $order_token);
        $stmt->execute();
        $order_id = $conn->insert_id;

        foreach ($items as $item) {
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiid", $order_id, $item['id'], $item['quantity'], $item['price']);
            $stmt->execute();
        }
        $conn->commit();
        return ['success' => true, 'order_id' => $order_id, 'order_token' => $order_token];
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => 'Ошибка создания заказа'];
    }
}

function getOrdersByUser($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT o.*, GROUP_CONCAT(p.name) as products FROM orders o LEFT JOIN order_items oi ON o.id = oi.order_id LEFT JOIN products p ON oi.product_id = p.id WHERE o.user_id = ? GROUP BY o.id ORDER BY o.created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// ✅ ДОБАВЛЕНО: Алиас для совместимости с orders.php
function getUserOrders($user_id) {
    return getOrdersByUser($user_id);
}

function getOrderItems($order_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT oi.quantity, oi.price, p.name as product_name, p.image as product_image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getOrderDetails($order_id, $user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    if (!$order) return false;
    $order['items'] = getOrderItems($order_id);
    return $order;
}

/**
 * Обновление статуса заказа
 *
 * @param int $order_id ID заказа
 * @param string $status Новый статус
 * @return array Результат операции
 *
 * ⚠️ ВАЖНО: Эта функция теперь включает внутреннюю проверку прав доступа!
 * Вызывающий код ДОЛЖЕН проверять права через requireAdmin() или requireModerator() ПЕРЕД вызовом,
 * но функция также выполняет дополнительную проверку для защиты от случайных вызовов.
 */
function updateOrderStatus($order_id, $status) {
    global $conn;

    // 🔒 ВНУТРЕННЯЯ ПРОВЕРКА ПРАВ: Функция требует авторизации и роли администратора/модератора
    if (!isLoggedIn()) {
        log_error('updateOrderStatus: Unauthorized access attempt - user not logged in', 'SECURITY');
        return ['success' => false, 'message' => 'Требуется авторизация'];
    }

    // Проверка роли пользователя
    $allowed_roles = ['admin', 'moderator'];
    if (!in_array($_SESSION['role'], $allowed_roles, true)) {
        log_error(sprintf(
            'updateOrderStatus: Unauthorized role access attempt - user_id=%d, role=%s',
            $_SESSION['user_id'] ?? 0,
            $_SESSION['role'] ?? 'none'
        ), 'SECURITY');
        return ['success' => false, 'message' => 'Недостаточно прав для выполнения операции'];
    }

    // Дополнительная валидация статуса
    $valid_statuses = ['pending', 'payment', 'completed', 'cancelled'];
    if (!in_array($status, $valid_statuses, true)) {
        log_error(sprintf(
            'updateOrderStatus: Invalid status attempt - order_id=%d, status=%s',
            $order_id,
            $status
        ), 'SECURITY');
        return ['success' => false, 'message' => 'Недопустимый статус заказа'];
    }

    // Проверка существования заказа
    $stmt = $conn->prepare("SELECT id FROM orders WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        log_error('updateOrderStatus: Order not found - order_id=' . $order_id, 'WARNING');
        $stmt->close();
        return ['success' => false, 'message' => 'Заказ не найден'];
    }
    $stmt->close();

    // Обновление статуса заказа с использованием подготовленного выражения
    $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $status, $order_id);
    $success = $stmt->execute();
    $affected_rows = $stmt->affected_rows;
    $stmt->close();

    if ($success && $affected_rows > 0) {
        log_action('Order status updated', [
            'order_id' => $order_id,
            'new_status' => $status,
            'user_id' => $_SESSION['user_id'],
            'role' => $_SESSION['role']
        ]);
        return ['success' => true, 'message' => 'Статус заказа обновлён'];
    }

    return ['success' => false, 'message' => 'Ошибка при обновлении статуса заказа'];
}

/**
 * Оплата заказа пользователем
 * Позволяет пользователю изменить статус своего заказа на 'completed' после оплаты
 *
 * @param int $order_id ID заказа
 * @param int $user_id ID пользователя
 * @return array Результат операции
 */
function payForOrder($order_id, $user_id) {
    global $conn;

    if (!isLoggedIn()) {
        log_error('payForOrder: Unauthorized access attempt - user not logged in', 'SECURITY');
        return ['success' => false, 'message' => 'Требуется авторизация'];
    }

    // Проверка что пользователь пытается оплатить свой собственный заказ
    $stmt = $conn->prepare("SELECT id, status, total FROM orders WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        log_error('payForOrder: Order not found or access denied - order_id=' . $order_id . ', user_id=' . $user_id, 'WARNING');
        $stmt->close();
        return ['success' => false, 'message' => 'Заказ не найден или доступ запрещён'];
    }

    $order = $result->fetch_assoc();
    $stmt->close();

    // Проверка статуса заказа - можно оплатить только pending или payment
    if ($order['status'] === 'completed') {
        return ['success' => false, 'message' => 'Заказ уже оплачен'];
    }

    if ($order['status'] === 'cancelled') {
        return ['success' => false, 'message' => 'Нельзя оплатить отменённый заказ'];
    }

    // Обновление статуса заказа на completed
    $stmt = $conn->prepare("UPDATE orders SET status = 'completed', updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $success = $stmt->execute();
    $affected_rows = $stmt->affected_rows;
    $stmt->close();

    if ($success && $affected_rows > 0) {
        log_action('Order paid by user', [
            'order_id' => $order_id,
            'user_id' => $user_id,
            'amount' => $order['total']
        ]);
        return ['success' => true, 'message' => 'Оплата прошла успешно! Заказ оплачен.'];
    }

    return ['success' => false, 'message' => 'Ошибка при обработке оплаты'];
}

// === Отзывы ===
function getReviewsByProduct($product_id) {
    global $conn;
    // ✅ ИСПРАВЛЕНО: Добавлена проверка is_approved для модерации отзывов
    $stmt = $conn->prepare("SELECT r.*, u.username FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? AND r.is_approved = 1 ORDER BY r.created_at DESC");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function addReview($user_id, $product_id, $rating, $comment) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO reviews (user_id, product_id, rating, comment) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $user_id, $product_id, $rating, $comment);
    return $stmt->execute();
}

// ✅ ДОБАВЛЕНО: Получение купленных товаров для отзывов
function getPurchasedProducts($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT DISTINCT p.id, p.name, p.image FROM orders o JOIN order_items oi ON o.id=oi.order_id JOIN products p ON oi.product_id=p.id WHERE o.user_id=? AND o.status='completed' AND p.id NOT IN (SELECT product_id FROM reviews WHERE user_id=?)");
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// === Корзина ===
function addToBasket($product_id, $quantity) {
    $product = getProductById($product_id);
    if (!$product) return false;
    if (!isset($_SESSION['basket'])) $_SESSION['basket'] = [];

    $found = false;
    foreach ($_SESSION['basket'] as &$item) {
        if ($item['id'] == $product_id) {
            $item['quantity'] += $quantity;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $_SESSION['basket'][] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'image' => $product['image'],
            'quantity' => $quantity
        ];
    }
    return true;
}

function removeFromBasket($product_id) {
    if (!isset($_SESSION['basket'])) return false;
    foreach ($_SESSION['basket'] as $key => $item) {
        if ($item['id'] == $product_id) {
            unset($_SESSION['basket'][$key]);
            $_SESSION['basket'] = array_values($_SESSION['basket']);
            return true;
        }
    }
    return false;
}

function updateBasket($product_id, $quantity) {
    if (!isset($_SESSION['basket'])) return false;
    foreach ($_SESSION['basket'] as &$item) {
        if ($item['id'] == $product_id) {
            $item['quantity'] = $quantity;
            return true;
        }
    }
    return false;
}

// ✅ ИСПРАВЛЕНО: Считает общее количество товаров (штук), а не позиций
function getBasketCount() {
    if (!isset($_SESSION['basket'])) return 0;
    $count = 0;
    foreach ($_SESSION['basket'] as $item) {
        $count += (int)$item['quantity'];
    }
    return (int)$count;
}

function getBasketTotal() {
    if (!isset($_SESSION['basket'])) return 0;
    $total = 0;
    foreach ($_SESSION['basket'] as $item) $total += $item['price'] * $item['quantity'];
    return $total;
}

// === Утилиты ===
function getOrderStatusName($s) {
    return ['pending'=>'Ожидает','payment'=>'Ожидает оплаты','completed'=>'Оплачен','cancelled'=>'Отменён'][$s] ?? $s;
}
function formatPrice($p) { return number_format((float)$p, 0, ',', ' ') . ' ₽'; }

/**
 * Возвращает корректный путь к изображению товара.
 * Проверяет наличие файла на сервере. Если файл отсутствует или имя пустое — возвращает заглушку.
 *
 * @param string $image_val Значение из базы данных (например 'product/image.jpg')
 * @return string Полный URL путь
 */
function getProductImage($image_val) {
    $fallback = '/images/no_photo.png';

    // 1. Если в базе пусто — сразу заглушка
    if (empty($image_val)) {
        return $fallback;
    }

    // 2. Нормализуем путь (убираем лишние слеши)
    $path = ltrim($image_val, '/');

    // Если в базе записано только имя файла, добавляем папку product/
    if (strpos($path, 'product/') === false) {
        $path = 'product/' . $path;
    }

    // 3. Проверяем физическое наличие файла на сервере
    // __DIR__ указывает на папку includes/, поднимаемся на уровень выше к корню
    $full_server_path = __DIR__ . '/../images/' . $path;

    if (file_exists($full_server_path)) {
        return '/images/' . $path;
    }

    // 4. Файл потерян — возвращаем заглушку
    return $fallback;
}
?>