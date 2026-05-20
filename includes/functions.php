<?php
/**
 * magic.shop — Основные функции и хелперы
 * Все ответы и пояснения только на русском
 */

// ============================================================================
// 🧰 ХЕЛПЕРЫ ДЛЯ РАБОТЫ С ПУТЯМИ (ИСПРАВЛЕНИЕ: жёсткая привязка к корню)
// ============================================================================

/**
 * Генерирует абсолютный URL к файлу проекта
 * Работает в корне домена и в подкаталогах
 * @param string $path Относительный путь от корня проекта
 * @return string Полный URL
 */
function site_url($path = '') {
    $path = ltrim($path, '/');
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script_dir = dirname($_SERVER['SCRIPT_NAME']);
    $script_dir = ($script_dir === '\\' || $script_dir === '/') ? '' : $script_dir;
    $script_dir = rtrim($script_dir, '/');
    return "{$protocol}://{$host}{$script_dir}/{$path}";
}

/**
 * Генерирует абсолютный путь к файлу на диске
 * @param string $path Относительный путь от корня проекта
 * @return string Абсолютный путь в файловой системе
 */
function site_path($path = '') {
    $path = ltrim($path, '/');
    return rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . $path;
}

/**
 * Безопасный редирект с использованием site_url()
 * @param string $path Куда перенаправить
 * @return void
 */
function redirect_to($path) {
    $url = site_url($path);
    if (ob_get_length()) ob_clean();
    header("Location: {$url}");
    exit;
}

// ============================================================================
// 🔐 ФУНКЦИИ АВТОРИЗАЦИИ И БЕЗОПАСНОСТИ
// ============================================================================

/**
 * Проверка: пользователь авторизован
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Проверка: роль администратора
 */
function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

/**
 * Проверка: роль модератора
 */
function isModerator() {
    return isLoggedIn() && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'moderator');
}

/**
 * Требовать авторизацию + редирект с сохранением возврата
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $return = $_SERVER['REQUEST_URI'];
        $_SESSION['return_to'] = $return;
        redirect_to('login.php');
    }
}

/**
 * Требовать роль администратора
 */
function requireAdmin() {
    if (!isAdmin()) {
        redirect_to('index.php');
    }
}

/**
 * Требовать роль модератора или выше
 */
function requireModerator() {
    if (!isModerator()) {
        redirect_to('index.php');
    }
}

// ============================================================================
// 🧹 САНТИЗАЦИЯ И ЭКРАНИРОВАНИЕ
// ============================================================================

/**
 * Санитизация строки: обрезка + htmlspecialchars
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Быстрый вывод экранированного значения
 */
function e($data) {
    echo sanitize($data);
}

/**
 * Валидация email с проверкой DNS (опционально)
 */
function isValidEmail($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $domain = substr(strrchr($email, "@"), 1);
    // Если DNS-проверка не прошла — логируем, но не блокируем (чтобы не ломать регистрацию)
    if (!checkdnsrr($domain, "MX") && !checkdnsrr($domain, "A")) {
        error_log("[WARN] Email domain has no DNS records: {$domain}");
    }
    return true;
}

// ============================================================================
// 🛒 ФУНКЦИИ КОРЗИНЫ
// ============================================================================

/**
 * Добавить товар в корзину (сессия)
 */
function addToBasket($product_id, $quantity = 1) {
    if (!isset($_SESSION['basket'])) {
        $_SESSION['basket'] = [];
    }
    $pid = (int)$product_id;
    $qty = (int)$quantity;
    if ($qty <= 0) return false;
    
    if (isset($_SESSION['basket'][$pid])) {
        $_SESSION['basket'][$pid] += $qty;
    } else {
        $_SESSION['basket'][$pid] = $qty;
    }
    return true;
}

/**
 * Удалить товар из корзины
 */
function removeFromBasket($product_id) {
    if (isset($_SESSION['basket'][$product_id])) {
        unset($_SESSION['basket'][$product_id]);
        return true;
    }
    return false;
}

/**
 * Обновить количество товара в корзине
 */
function updateBasketQuantity($product_id, $quantity) {
    $qty = (int)$quantity;
    if ($qty <= 0) {
        return removeFromBasket($product_id);
    }
    if (isset($_SESSION['basket'][$product_id])) {
        $_SESSION['basket'][$product_id] = $qty;
        return true;
    }
    return false;
}

/**
 * Получить товары корзины с данными из БД
 * @param mysqli $conn Подключение к БД
 * @return array Массив товаров с полями из БД + quantity
 */
function getBasketItems($conn) {
    if (empty($_SESSION['basket'])) return [];
    
    $ids = array_keys($_SESSION['basket']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    $stmt = $conn->prepare("SELECT id, name, price, image, stock, active FROM products WHERE id IN ($placeholders) AND active = 1");
    $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $row['quantity'] = $_SESSION['basket'][$row['id']];
        $row['subtotal'] = $row['price'] * $row['quantity'];
        // Проверка доступности
        $row['available'] = $row['stock'] >= $row['quantity'];
        $items[] = $row;
    }
    $stmt->close();
    return $items;
}

/**
 * Посчитать общую сумму корзины
 */
function getBasketTotal($items) {
    return array_sum(array_column($items, 'subtotal'));
}

// ============================================================================
// 📦 ФУНКЦИИ ЗАКАЗОВ
// ============================================================================

/**
 * Создать заказ из корзины (транзакция)
 * @return array ['success'=>bool, 'order_id'=>int|null, 'error'=>string|null]
 */
function createOrder($conn, $user_id, $delivery_address, $comment = '') {
    global $log_file;
    
    if (empty($_SESSION['basket'])) {
        return ['success' => false, 'error' => 'Корзина пуста'];
    }
    
    $items = getBasketItems($conn);
    if (empty($items)) {
        return ['success' => false, 'error' => 'Нет активных товаров в корзине'];
    }
    
    $total = getBasketTotal($items);
    $token = bin2hex(random_bytes(16));
    
    try {
        $conn->begin_transaction();
        
        // Создаём заказ
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total, status, order_token, delivery_address, comment) VALUES (?, ?, 'pending', ?, ?, ?)");
        $stmt->bind_param("idsss", $user_id, $total, $token, $delivery_address, $comment);
        $stmt->execute();
        $order_id = $conn->insert_id;
        $stmt->close();
        
        // Добавляем позиции заказа
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        foreach ($items as $item) {
            // Проверка остатков в рамках транзакции
            if ($item['stock'] < $item['quantity']) {
                throw new Exception("Товар \"{$item['name']}\" недоступен в нужном количестве");
            }
            $stmt->bind_param("iiid", $order_id, $item['id'], $item['quantity'], $item['price']);
            $stmt->execute();
            
            // Списываем остаток
            $upd = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $upd->bind_param("ii", $item['quantity'], $item['id']);
            $upd->execute();
            $upd->close();
        }
        $stmt->close();
        
        // Очищаем корзину
        unset($_SESSION['basket']);
        
        $conn->commit();
        log_action($user_id, "order_created", "Заказ #{$order_id} на сумму {$total} ₽", $log_file);
        
        return ['success' => true, 'order_id' => $order_id, 'token' => $token];
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("[ERROR] createOrder: " . $e->getMessage());
        log_action($user_id, "order_error", $e->getMessage(), $log_file);
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Обновить статус заказа (только для авторизованных с правами!)
 * ⚠️ Проверка прав должна выполняться ВЫЗЫВАЮЩИМ кодом
 */
function updateOrderStatus($conn, $order_id, $new_status, $updated_by) {
    $allowed = ['pending', 'payment', 'completed', 'cancelled'];
    if (!in_array($new_status, $allowed)) {
        return false;
    }
    
    $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    $result = $stmt->execute();
    $stmt->close();
    
    if ($result) {
        log_action($updated_by, "order_status_changed", "Заказ #{$order_id} → {$new_status}", $log_file);
    }
    return $result;
}

// ============================================================================
// 🗣️ ФУНКЦИИ ОТЗЫВОВ
// ============================================================================

/**
 * Добавить отзыв (требует модерации для не-админов)
 */
function addReview($conn, $user_id, $product_id, $rating, $text) {
    $rating = (int)$rating;
    if ($rating < 1 || $rating > 5) return false;
    if (empty($text) || mb_strlen($text) > 1000) return false;
    
    // Проверка: пользователь покупал этот товар
    $stmt = $conn->prepare("SELECT o.id FROM orders o JOIN order_items oi ON o.id = oi.order_id WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'completed' LIMIT 1");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $can_review = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    
    if (!$can_review) {
        return false;
    }
    
    // Для админов — сразу публикуем, для остальных — на модерацию
    $is_approved = isAdmin() ? 1 : 0;
    
    $stmt = $conn->prepare("INSERT INTO reviews (user_id, product_id, rating, text, is_approved) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiisi", $user_id, $product_id, $rating, $text, $is_approved);
    $result = $stmt->execute();
    $stmt->close();
    
    if ($result) {
        log_action($user_id, "review_added", "Отзыв на товар #{$product_id}", $log_file);
    }
    return $result;
}

/**
 * Получить отзывы по товару (только одобренные, если не админ)
 */
function getProductReviews($conn, $product_id, $include_pending = false) {
    $sql = "SELECT r.id, r.rating, r.text, r.created_at, u.username 
            FROM reviews r 
            JOIN users u ON r.user_id = u.id 
            WHERE r.product_id = ?";
    if (!$include_pending || !isAdmin()) {
        $sql .= " AND r.is_approved = 1";
    }
    $sql .= " ORDER BY r.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $reviews = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $reviews;
}

// ============================================================================
// 📝 ЛОГИРОВАНИЕ
// ============================================================================

/**
 * Записать действие в лог-файл с ротацией по дате
 */
function log_action($user_id, $action, $details, $log_dir = 'logs') {
    // Создаём папку логов, если нет
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $date = date('Y-m-d');
    $log_file = "{$log_dir}/app_{$date}.log";
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 100);
    
    $line = "[{$timestamp}] [IP:{$ip}] [UID:{$user_id}] [{$action}] {$details} [UA:{$user_agent}]\n";
    
    // Проверка размера лога (ротация при >10МБ)
    if (file_exists($log_file) && filesize($log_file) > 10 * 1024 * 1024) {
        rename($log_file, "{$log_file}.old");
    }
    
    file_put_contents($log_file, $line, FILE_APPEND | LOCK_EX);
}

/**
 * Логирование ошибок с уровнем
 */
function log_error($message, $level = 'ERROR', $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $ctx = empty($context) ? '' : ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
    $line = "[{$timestamp}] [{$level}] {$message}{$ctx}\n";
    error_log($line, 3, 'logs/php_errors.log');
}
?>