<?php
// === Категории ===
function getCategories() {
    global $conn;
    // Получаем только категории, в которых есть активные товары
    $stmt = $conn->prepare("SELECT DISTINCT p.category AS category
                            FROM products p
                            WHERE p.category IS NOT NULL
                            AND p.category != ''
                            AND p.active = 1
                            ORDER BY p.category");
    $stmt->execute();
    $result = $stmt->get_result();
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
    return $categories;
}

// === Товары ===
function getProducts($category = null, $limit = null) {
    global $conn;
    $sql = "SELECT p.*, p.category AS category_name, u.username as creator_name
            FROM products p
            LEFT JOIN users u ON p.created_by = u.id
            WHERE p.active = 1";

    if ($category) $sql .= " AND p.category = ?";
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
    $stmt = $conn->prepare("SELECT p.*, p.category AS category_name FROM products p WHERE p.id = ? AND p.active = 1");
    $stmt->bind_param("i", $id); $stmt->execute();
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
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total, delivery_address, comment, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt->bind_param("idss", $user_id, $total, $address, $comment);
        $stmt->execute();
        $order_id = $conn->insert_id;

        foreach ($items as $item) {
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiid", $order_id, $item['id'], $item['quantity'], $item['price']);
            $stmt->execute();
        }
        $conn->commit();
        return ['success' => true, 'order_id' => $order_id];
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
    $stmt = $conn->prepare("SELECT oi.quantity, oi.price, p.name as product_name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
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

function updateOrderStatus($order_id, $status) {
    global $conn;
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $order_id);
    return ['success' => $stmt->execute()];
}

// === Отзывы ===
function getReviewsByProduct($product_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT r.*, u.username FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? ORDER BY r.created_at DESC");
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
    return $count;
}

function getBasketTotal() {
    if (!isset($_SESSION['basket'])) return 0;
    $total = 0;
    foreach ($_SESSION['basket'] as $item) $total += $item['price'] * $item['quantity'];
    return $total;
}

// === Утилиты ===
function getOrderStatusName($s) {
    return ['pending'=>'Ожидает','payment'=>'Ожидает оплаты','completed'=>'Завершён','cancelled'=>'Отменён'][$s] ?? $s;
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
