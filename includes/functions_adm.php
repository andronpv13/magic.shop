<?php
// functions_adm.php - Дополнительные функции для админ-панели
// Основные функции (getCategories, getProducts, etc.) находятся в functions.php

require_once __DIR__ . '/config.php';

// ============================================
// ФУНКЦИИ АДМИН-ПАНЕЛИ
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
    $category_id = null;
    if (!empty($cat)) {
        $cat_data = ensureCategoryExists($cat);
        if (is_array($cat_data) && isset($cat_data['id'])) {
            $category_id = $cat_data['id'];
        } elseif (is_numeric($cat)) {
            $category_id = (int)$cat;
        } else {
            $cat_obj = getCategoryByName($cat);
            $category_id = $cat_obj ? (int)$cat_obj['id'] : null;
        }
    }
    if ($category_id === null) {
        return ['success' => false, 'message' => 'Категория не найдена'];
    }
    $stmt = $conn->prepare("INSERT INTO products (name,description,price,category_id,stock,is_new,image,created_by) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->bind_param("ssdiissi", $n, $d, $p, $category_id, $st, $nw, $img, $cb);
    return ['success' => $stmt->execute()];
}

function editProduct($id, $n, $d, $p, $cat, $st, $nw, $img) {
    global $conn;
    $category_id = null;
    if (!empty($cat)) {
        $cat_data = ensureCategoryExists($cat);
        if (is_array($cat_data) && isset($cat_data['id'])) {
            $category_id = $cat_data['id'];
        } elseif (is_numeric($cat)) {
            $category_id = (int)$cat;
        } else {
            $cat_obj = getCategoryByName($cat);
            $category_id = $cat_obj ? (int)$cat_obj['id'] : null;
        }
    }
    if ($category_id === null) {
        return ['success' => false, 'message' => 'Категория не найдена'];
    }
    $stmt = $conn->prepare("UPDATE products SET name=?,description=?,price=?,category_id=?,stock=?,is_new=?,image=? WHERE id=?");
    $stmt->bind_param("ssdiissii", $n, $d, $p, $category_id, $st, $nw, $img, $id);
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
    $stmt = $conn->prepare("SELECT c.name AS category, c.id AS category_id, COUNT(p.id) AS product_count FROM categories c LEFT JOIN products p ON p.category_id = c.id AND p.active = 1 GROUP BY c.id, c.name ORDER BY c.name");
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
    $result = $stmt->get_result()->fetch_assoc();
    if ($result) {
        return ['id' => (int)$result['id'], 'name' => $name];
    }

    $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
    if (!$stmt) {
        return true;
    }
    $stmt->bind_param("s", $name);
    if ($stmt->execute()) {
        return ['id' => (int)$conn->insert_id, 'name' => $name];
    }
    return false;
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
    // Если передано число - считаем category_id, иначе ищем по имени
    if (is_numeric($category)) {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM products WHERE category_id = ? AND active = 1");
        $stmt->bind_param("i", $category);
    } else {
        // Для обратной совместимости: ищем категорию по имени
        $cat_obj = getCategoryByName($category);
        if (!$cat_obj) {
            return 0;
        }
        $category_id = (int)$cat_obj['id'];
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM products WHERE category_id = ? AND active = 1");
        $stmt->bind_param("i", $category_id);
    }
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return (int)($result['total'] ?? 0);
}

function deleteCategory($category) {
    global $conn;

    // Определяем category_id: если передано число - это ID, иначе ищем по имени
    if (is_numeric($category)) {
        $category_id = (int)$category;
    } else {
        $cat_obj = getCategoryByName($category);
        if (!$cat_obj) {
            return ['success' => false, 'message' => 'Категория не найдена'];
        }
        $category_id = (int)$cat_obj['id'];
    }

    // Сначала удаляем категорию из таблицы categories
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $stmt->close();
    }

    // Затем устанавливаем category_id = NULL для всех товаров этой категории
    // Примечание: в реальной БД с FK ON DELETE RESTRICT это может не сработать
    // В таком случае нужно сначала удалить/переместить товары
    $stmt = $conn->prepare("UPDATE products SET category_id = NULL WHERE category_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $category_id);
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
