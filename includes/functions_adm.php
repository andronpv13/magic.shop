<?php
require_once __DIR__ . '/config.php';

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

// ============================================
// ФУНКЦИИ АДМИН-ПАНЕЛИ (перемещены из js/validation.js)
// ============================================

function updateAdminContactInfo($email, $phone) {
    global $conn;
    $stmt = $conn->prepare("UPDATE users SET email = ?, phone = ? WHERE role = 'admin' LIMIT 1");
    $stmt->bind_param("ss", $email, $phone);
    return $stmt->execute();
}

function getAdminStats() {
    global $conn;
    return [
        'customers' => $conn->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetch_row()[0],
        'moderators' => $conn->query("SELECT COUNT(*) FROM users WHERE role='moderator'")->fetch_row()[0],
        'products' => $conn->query("SELECT COUNT(*) FROM products WHERE active=1")->fetch_row()[0],
        'orders' => $conn->query("SELECT COUNT(*) FROM orders")->fetch_row()[0],
        'revenue' => $conn->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status='completed'")->fetch_row()[0],
        'reviews' => $conn->query("SELECT COUNT(*) FROM reviews")->fetch_row()[0]
    ];
}

function getAllProducts() {
    return getProducts();
}

function addProduct($n, $d, $p, $cat, $st, $nw, $img, $cb) {
    global $conn;
    if (!empty($cat)) {
        ensureCategoryExists($cat);
    }
    $stmt = $conn->prepare("INSERT INTO products (name,description,price,category,stock,is_new,image,created_by) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->bind_param("ssdsisii", $n, $d, $p, $cat, $st, $nw, $img, $cb);
    return ['success' => $stmt->execute()];
}

function editProduct($id, $n, $d, $p, $cat, $st, $nw, $img) {
    global $conn;
    if (!empty($cat)) {
        ensureCategoryExists($cat);
    }
    $stmt = $conn->prepare("UPDATE products SET name=?,description=?,price=?,category=?,stock=?,is_new=?,image=? WHERE id=?");
    $stmt->bind_param("ssdsisii", $n, $d, $p, $cat, $st, $nw, $img, $id);
    return ['success' => $stmt->execute()];
}

function deleteProduct($id) {
    global $conn;
    $stmt = $conn->prepare("UPDATE products SET active=0 WHERE id=?");
    $stmt->bind_param("i", $id);
    return ['success' => $stmt->execute()];
}

function getCategoriesList() {
    global $conn;
    $stmt = $conn->prepare("SELECT c.name AS category, COUNT(p.id) AS product_count FROM categories c LEFT JOIN products p ON p.category = c.name AND p.active = 1 GROUP BY c.name ORDER BY c.name");
    if (!$stmt) {
        $stmt = $conn->prepare("SELECT category AS category, COUNT(*) AS product_count FROM products WHERE category IS NOT NULL AND category != '' AND active = 1 GROUP BY category ORDER BY category");
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getCategoryByName($n) {
    global $conn;
    $stmt = $conn->prepare("SELECT id, name FROM categories WHERE name = ? LIMIT 1");
    if (!$stmt) {
        $stmt = $conn->prepare("SELECT DISTINCT category AS category FROM products WHERE category = ? LIMIT 1");
    }
    $stmt->bind_param("s", $n);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function addCategory($name) {
    global $conn;
    $name = trim($name);

    if ($name === '') {
        return ['success' => false, 'message' => 'Название категории не может быть пустым'];
    }
    if (mb_strlen($name) > 50) {
        return ['success' => false, 'message' => 'Название категории не может превышать 50 символов'];
    }

    $stmt = $conn->prepare("SELECT id FROM categories WHERE name = ? LIMIT 1");
    $stmt->bind_param("s", $name);
    $stmt->execute();

    if ($stmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => 'Категория уже существует'];
    }

    $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
    $stmt->bind_param("s", $name);
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Категория добавлена', 'name' => $name];
    }

    return ['success' => false, 'message' => 'Ошибка при добавлении категории'];
}

function ensureCategoryExists($name) {
    global $conn;
    $name = trim($name);
    if ($name === '') {
        return false;
    }

    $stmt = $conn->prepare("SELECT id FROM categories WHERE name = ? LIMIT 1");
    if (!$stmt) {
        // Fallback for legacy databases without a dedicated categories table.
        return true;
    }
    $stmt->bind_param("s", $name);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        return true;
    }

    $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
    if (!$stmt) {
        return true;
    }
    $stmt->bind_param("s", $name);
    return $stmt->execute();
}

function countCategories() {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM categories");
    if (!$stmt) {
        $stmt = $conn->prepare("SELECT COUNT(DISTINCT category) as total FROM products WHERE category IS NOT NULL AND category != ''");
    }
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return (int)($result['total'] ?? 0);
}

function getProductsCountByCategory($category) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM products WHERE category = ? AND active = 1");
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return (int)($result['total'] ?? 0);
}

function deleteCategory($category) {
    global $conn;

    // Сначала удаляем категорию из таблицы categories
    $stmt = $conn->prepare("DELETE FROM categories WHERE name = ?");
    if ($stmt) {
        $stmt->bind_param("s", $category);
        $stmt->execute();
        $stmt->close();
    }

    // Затем устанавливаем category = NULL для всех товаров этой категории
    $stmt = $conn->prepare("UPDATE products SET category = NULL WHERE category = ?");
    if ($stmt) {
        $stmt->bind_param("s", $category);
        $stmt->execute();
        $stmt->close();
    }

    return ['success' => true];
}

function uploadProductImage($file) {
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed) || $file['size'] > 5*1024*1024) {
        return ['success' => false, 'message' => 'Ошибка формата'];
    }
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fn = uniqid('prod_') . '.' . $ext;
    $dir = __DIR__ . '/../images/product/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    if (move_uploaded_file($file['tmp_name'], $dir . $fn)) {
        return ['success' => true, 'filename' => 'product/' . $fn];
    }
    return ['success' => false, 'message' => 'Ошибка загрузки'];
}

function getAllOrders($s = null) {
    global $conn;
    $sql = "SELECT o.*, u.username, u.email FROM orders o JOIN users u ON o.user_id = u.id";
    if ($s) {
        $stmt = $conn->prepare($sql . " WHERE o.status = ?");
        $stmt->bind_param("s", $s);
    } else {
        $stmt = $conn->prepare($sql);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getOrderDetailsAdmin($id) {
    global $conn;
    $s = $conn->prepare("SELECT o.*, u.username, u.email, u.phone, u.first_name, u.last_name FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?");
    $s->bind_param("i", $id);
    $s->execute();
    $o = $s->get_result()->fetch_assoc();
    if (!$o) return false;
    $s = $conn->prepare("SELECT oi.quantity, oi.price, p.name as product_name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
    $s->bind_param("i", $id);
    $s->execute();
    $items = $s->get_result()->fetch_all(MYSQLI_ASSOC);
    // Добавляем вычисление subtotal для каждого элемента
    foreach ($items as &$item) {
        $item['subtotal'] = $item['quantity'] * $item['price'];
    }
    $o['items'] = $items;
    return $o;
}

function updateOrderStatusAdmin($id, $st) {
    global $conn;
    $s = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $s->bind_param("si", $st, $id);
    return ['success' => $s->execute()];
}

function getAllReviews() {
    global $conn;
    $s = $conn->prepare("SELECT r.*, u.username, p.name as product_name FROM reviews r JOIN users u ON r.user_id = u.id JOIN products p ON r.product_id = p.id ORDER BY r.created_at DESC");
    $s->execute();
    return $s->get_result()->fetch_all(MYSQLI_ASSOC);
}

function deleteReview($id) {
    global $conn;
    $s = $conn->prepare("DELETE FROM reviews WHERE id = ?");
    $s->bind_param("i", $id);
    return ['success' => $s->execute()];
}

function getAllUsers() {
    global $conn;
    $s = $conn->prepare("SELECT id, username, email, first_name, last_name, role, created_at FROM users ORDER BY created_at DESC");
    $s->execute();
    return $s->get_result()->fetch_all(MYSQLI_ASSOC);
}

function addUser($u, $e, $p, $fn, $ln, $r = 'moderator') {
    global $conn;
    $s = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $s->bind_param("ss", $u, $e);
    $s->execute();
    if ($s->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => 'Занято'];
    }
    $h = password_hash($p, PASSWORD_DEFAULT);
    $s = $conn->prepare("INSERT INTO users (username, email, password, first_name, last_name, role) VALUES (?,?,?,?,?,?)");
    $s->bind_param("ssssss", $u, $e, $h, $fn, $ln, $r);
    return ['success' => $s->execute()];
}

function deleteUser($id) {
    global $conn;
    $s = $conn->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
    $s->bind_param("i", $id);
    return ['success' => $s->execute() && $s->affected_rows > 0];
}

function resetUserPassword($id, $p) {
    global $conn;
    $h = password_hash($p, PASSWORD_DEFAULT);
    $s = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $s->bind_param("si", $h, $id);
    return ['success' => $s->execute()];
}
?>
