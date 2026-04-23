<?php
/**
 * Функции модератора для "Волшебная ЛАВКА"
 * Разработчик: АВВА © 2025
 */

require_once __DIR__ . '/config.php';

// Только специфичные функции модератора

// ==================== ТОВАРЫ МОДЕРАТОРА ====================

/**
 * Получение товаров, созданных модератором
 */
function getModeratorProducts($moderator_id) {
    global $mysqli;
    
    $stmt = $mysqli->prepare("SELECT p.*, u.username as seller_name, c.name as category_name 
                              FROM products p 
                              LEFT JOIN users u ON p.created_by = u.id 
                              LEFT JOIN categories c ON p.category_id = c.id 
                              WHERE p.created_by = ? 
                              ORDER BY p.created_at DESC");
    $stmt->bind_param("i", $moderator_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $products;
}

/**
 * Добавление товара модератором
 */
function addModeratorProduct($name, $description, $price, $category_id, $stock, $is_new, $image_path, $created_by) {
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
 * Редактирование товара модератором
 */
function editModeratorProduct($id, $name, $description, $price, $category_id, $stock, $is_new, $image_path, $moderator_id) {
    global $mysqli;
    
    // Если категория не выбрана (0 или null), сохраняем как NULL
    $final_category_id = (!empty($category_id) && $category_id > 0) ? (int)$category_id : null;
    
    // Проверяем, что товар принадлежит модератору
    $product = getProductById($id);
    if (!$product || $product['created_by'] != $moderator_id) {
        return ['success' => false, 'message' => 'Вы не можете редактировать этот товар'];
    }
    
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
 * Удаление товара модератором
 */
function deleteModeratorProduct($id, $moderator_id) {
    global $mysqli;
    
    // Проверяем, что товар принадлежит модератору
    $product = getProductById($id);
    if (!$product || $product['created_by'] != $moderator_id) {
        return ['success' => false, 'message' => 'Вы не можете удалить этот товар'];
    }
    
    // Получаем путь к изображению
    $image_path = $product['image'];
    
    $stmt = $mysqli->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // Удаляем изображение, если оно было
        if ($image_path && file_exists(__DIR__ . '/../images/' . $image_path)) {
            unlink(__DIR__ . '/../images/' . $image_path);
        }
        
        $stmt->close();
        return ['success' => true, 'message' => 'Товар удален'];
    } else {
        $stmt->close();
        return ['success' => false, 'message' => 'Ошибка при удалении товара'];
    }
}

// ==================== ЗАКАЗЫ МОДЕРАТОРА ====================

/**
 * Получение заказов, связанных с товарами модератора
 */
function getModeratorOrders($moderator_id) {
    global $mysqli;
    
    $sql = "SELECT DISTINCT o.*, u.username 
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id 
            LEFT JOIN order_items oi ON o.id = oi.order_id 
            LEFT JOIN products p ON oi.product_id = p.id 
            WHERE p.created_by = ? 
            ORDER BY o.created_at DESC";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $moderator_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $orders;
}

/**
 * Получение деталей заказа, связанного с товарами модератора
 */
function getModeratorOrderDetails($order_id, $moderator_id) {
    global $mysqli;
    
    $stmt = $mysqli->prepare("SELECT o.*, u.username, u.email, u.phone, u.first_name, u.last_name 
                              FROM orders o 
                              LEFT JOIN users u ON o.user_id = u.id 
                              LEFT JOIN order_items oi ON o.id = oi.order_id 
                              LEFT JOIN products p ON oi.product_id = p.id 
                              WHERE o.id = ? AND p.created_by = ?");
    $stmt->bind_param("ii", $order_id, $moderator_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();
    
    if ($order) {
        $stmt = $mysqli->prepare("SELECT oi.*, p.image as product_image 
                                  FROM order_items oi 
                                  LEFT JOIN products p ON oi.product_id = p.id 
                                  WHERE oi.order_id = ? AND p.created_by = ?");
        $stmt->bind_param("ii", $order_id, $moderator_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $order['items'] = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
    
    return $order;
}

// ==================== ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ====================

/**
 * Получение товара по ID
 */
function getProductById($id) {
    global $mysqli;
    
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
}

/**
 * Получение списка всех категорий с ID (для select в админке)
 */
function getCategoriesList() {
    global $mysqli;
    
    $result = $mysqli->query("SELECT id, name FROM categories ORDER BY name ASC");
    $categories = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
    
    return $categories;
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
    $max_size = 5 * 1024 * 1024; // 5MB
    
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

?>
