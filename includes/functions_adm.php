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
    $category_error = '';

    if (!empty($cat)) {
        $cat_data = ensureCategoryExists($cat);
        if (is_array($cat_data) && isset($cat_data['id'])) {
            $category_id = $cat_data['id'];
        } elseif (is_numeric($cat)) {
            $category_id = (int)$cat;
        } else {
            $cat_obj = getCategoryByName($cat);
            if ($cat_obj && isset($cat_obj['id'])) {
                $category_id = (int)$cat_obj['id'];
            } else {
                $category_error = 'Не удалось определить категорию: ' . e($cat);
            }
        }
    } else {
        $category_error = 'Категория не указана';
    }

    if ($category_id === null) {
        return ['success' => false, 'message' => $category_error ?: 'Категория не найдена'];
    }
    $stmt = $conn->prepare("INSERT INTO products (name,description,price,category_id,stock,is_new,image,created_by) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->bind_param("ssdiissi", $n, $d, $p, $category_id, $st, $nw, $img, $cb);
    $success = $stmt->execute();
    return ['success' => $success, 'message' => $success ? 'Товар добавлен' : 'Ошибка при добавлении товара'];
}

function editProduct($id, $n, $d, $p, $cat, $st, $nw, $img) {
    global $conn;
    $category_id = null;
    $category_error = '';

    if (!empty($cat)) {
        $cat_data = ensureCategoryExists($cat);
        if (is_array($cat_data) && isset($cat_data['id'])) {
            $category_id = $cat_data['id'];
        } elseif (is_numeric($cat)) {
            $category_id = (int)$cat;
        } else {
            $cat_obj = getCategoryByName($cat);
            if ($cat_obj && isset($cat_obj['id'])) {
                $category_id = (int)$cat_obj['id'];
            } else {
                $category_error = 'Не удалось определить категорию: ' . e($cat);
            }
        }
    } else {
        $category_error = 'Категория не указана';
    }

    if ($category_id === null) {
        return ['success' => false, 'message' => $category_error ?: 'Категория не найдена'];
    }

    // Если изображение не передано (null), оставляем старое
    if ($img === null) {
        $stmt = $conn->prepare("UPDATE products SET name=?,description=?,price=?,category_id=?,stock=?,is_new=? WHERE id=?");
        $stmt->bind_param("ssdiisi", $n, $d, $p, $category_id, $st, $nw, $id);
    } else {
        $stmt = $conn->prepare("UPDATE products SET name=?,description=?,price=?,category_id=?,stock=?,is_new=?,image=? WHERE id=?");
        $stmt->bind_param("ssdiissi", $n, $d, $p, $category_id, $st, $nw, $img, $id);
    }
    $success = $stmt->execute();
    return ['success' => $success, 'message' => $success ? 'Товар обновлён' : 'Ошибка при обновлении товара'];
}

function deleteProduct($id) {
    global $conn;
    // Сначала получаем текущее изображение товара для удаления файла
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result && !empty($result['image'])) {
        $image_path = __DIR__ . '/../images/' . $result['image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }

    // Удаляем товар из базы данных
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $success = $stmt->execute();
    return ['success' => $success, 'message' => $success ? 'Товар удалён' : 'Ошибка при удалении товара'];
}

function getCategoriesList() {
    global $conn;
    // 🔒 ИСПОЛЬЗУЕМ ТОЛЬКО подготовленные выражения с таблицей categories
    // Убран legacy-код с прямой интерполяцией строк
    $stmt = $conn->prepare("SELECT c.name AS category, c.id AS category_id, COUNT(p.id) AS product_count FROM categories c LEFT JOIN products p ON p.category_id = c.id AND p.active = 1 GROUP BY c.id, c.name ORDER BY c.name");

    if (!$stmt) {
        log_error('getCategoriesList: Failed to prepare statement - ' . $conn->error, 'ERROR');
        return [];
    }

    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $result;
}

function getCategoryByName($n) {
    global $conn;
    // 🔒 ИСПОЛЬЗУЕМ ТОЛЬКО подготовленные выражения с таблицей categories
    // Убран legacy-код с потенциальной SQL-инъекцией
    $stmt = $conn->prepare("SELECT id, name FROM categories WHERE name = ? LIMIT 1");

    if (!$stmt) {
        log_error('getCategoryByName: Failed to prepare statement - ' . $conn->error, 'ERROR');
        return null;
    }

    $stmt->bind_param("s", $n);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result;
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
        return ['success' => false, 'message' => 'Название категории не может быть пустым', 'id' => null];
    }

    $stmt = $conn->prepare("SELECT id FROM categories WHERE name = ? LIMIT 1");
    if (!$stmt) {
        // Fallback for legacy databases without a dedicated categories table.
        log_error('ensureCategoryExists: categories table not available');
        return ['success' => false, 'message' => 'Таблица категорий недоступна', 'id' => null];
    }
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    if ($result) {
        return ['success' => true, 'id' => (int)$result['id'], 'name' => $name, 'created' => false];
    }

    $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
    if (!$stmt) {
        log_error('ensureCategoryExists: cannot prepare INSERT statement');
        return ['success' => false, 'message' => 'Ошибка подготовки запроса', 'id' => null];
    }
    $stmt->bind_param("s", $name);
    if ($stmt->execute()) {
        return ['success' => true, 'id' => (int)$conn->insert_id, 'name' => $name, 'created' => true];
    }

    log_error('ensureCategoryExists: failed to insert category: ' . $conn->error);
    return ['success' => false, 'message' => 'Ошибка при создании категории: ' . $conn->error, 'id' => null];
}

function countCategories() {
    global $conn;
    // 🔒 ИСПОЛЬЗУЕМ ТОЛЬКО таблицу categories с подготовленными выражениями
    // Убран legacy-код с прямой интерполяцией
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM categories");

    if (!$stmt) {
        log_error('countCategories: Failed to prepare statement - ' . $conn->error, 'ERROR');
        return 0;
    }

    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($result['total'] ?? 0);
}

function getProductsCountByCategory($category) {
    global $conn;
    // 🔒 Если передано число - считаем category_id, иначе ищем по имени через безопасную функцию
    if (is_numeric($category)) {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM products WHERE category_id = ? AND active = 1");
        if (!$stmt) {
            log_error('getProductsCountByCategory: Failed to prepare statement - ' . $conn->error, 'ERROR');
            return 0;
        }
        $stmt->bind_param("i", $category);
    } else {
        // Для обратной совместимости: ищем категорию по имени через безопасную функцию getCategoryByName
        $cat_obj = getCategoryByName($category);
        if (!$cat_obj) {
            return 0;
        }
        $category_id = (int)$cat_obj['id'];
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM products WHERE category_id = ? AND active = 1");
        if (!$stmt) {
            log_error('getProductsCountByCategory: Failed to prepare statement - ' . $conn->error, 'ERROR');
            return 0;
        }
        $stmt->bind_param("i", $category_id);
    }
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
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
        if (!$stmt->execute()) {
            log_error('deleteCategory: failed to delete category: ' . $conn->error);
            return ['success' => false, 'message' => 'Ошибка при удалении категории: ' . $conn->error];
        }
        $affected = $stmt->affected_rows;
        $stmt->close();

        // Если ни одна строка не была затронута, категория не существовала
        if ($affected === 0) {
            return ['success' => false, 'message' => 'Категория не найдена'];
        }
    } else {
        log_error('deleteCategory: cannot prepare DELETE statement: ' . $conn->error);
        return ['success' => false, 'message' => 'Ошибка подготовки запроса'];
    }

    // Затем устанавливаем category_id = NULL для всех товаров этой категории
    // Примечание: в реальной БД с FK ON DELETE RESTRICT это может не сработать
    // В таком случае нужно сначала удалить/переместить товары
    $stmt = $conn->prepare("UPDATE products SET category_id = NULL WHERE category_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $category_id);
        if (!$stmt->execute()) {
            log_error('deleteCategory: failed to update products: ' . $conn->error);
            // Это не критичная ошибка, категория уже удалена
        }
        $stmt->close();
    }

    return ['success' => true, 'message' => 'Категория удалена'];
}

function uploadProductImage($file) {
    // 🔒 УЛУЧШЕННАЯ ПРОВЕРКА БЕЗОПАСНОСТИ ЗАГРУЖАЕМЫХ ФАЙЛОВ

    // Проверка наличия ошибки загрузки
    if (!isset($file) || !is_array($file) || $file['error'] !== UPLOAD_ERR_OK) {
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => 'Файл превышает максимальный размер, установленный в php.ini',
            UPLOAD_ERR_FORM_SIZE => 'Файл превышает максимальный размер формы',
            UPLOAD_ERR_PARTIAL => 'Файл загружен частично',
            UPLOAD_ERR_NO_FILE => 'Файл не был загружен',
            UPLOAD_ERR_NO_TMP_DIR => 'Отсутствует временная папка',
            UPLOAD_ERR_CANT_WRITE => 'Не удалось записать файл на диск',
            UPLOAD_ERR_EXTENSION => 'Расширение PHP остановило загрузку'
        ];
        $error_code = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        return ['success' => false, 'message' => $upload_errors[$error_code] ?? 'Ошибка загрузки файла'];
    }

    // Разрешённые MIME-типы
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    // 🔒 Проверка MIME-типа через finfo (более надёжно чем $_FILES['type'])
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $real_mime = $finfo->file($file['tmp_name']);

    if (!in_array($real_mime, $allowed_types, true)) {
        log_error(sprintf(
            'uploadProductImage: Blocked dangerous file upload - claimed_type=%s, real_type=%s, filename=%s',
            $file['type'] ?? 'unknown',
            $real_mime,
            $file['name'] ?? 'unknown'
        ), 'SECURITY');
        return ['success' => false, 'message' => 'Недопустимый формат файла. Разрешены только JPG, PNG, GIF, WebP'];
    }

    // Проверка размера файла (макс. 5MB)
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'Размер файла не должен превышать 5MB'];
    }

    // 🔒 Дополнительная проверка: getimagesize для подтверждения что это изображение
    $image_info = @getimagesize($file['tmp_name']);
    if ($image_info === false) {
        log_error('uploadProductImage: File is not a valid image - ' . $file['name'], 'SECURITY');
        return ['success' => false, 'message' => 'Файл не является корректным изображением'];
    }

    // Проверка расширения файла
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowed_extensions, true)) {
        return ['success' => false, 'message' => 'Недопустимое расширение файла'];
    }

    // Генерация безопасного имени файла
    $fn = uniqid('prod_', true) . '.' . $ext;

    // Создание директории если не существует
    $dir = __DIR__ . '/../images/product/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    // Запрет записи исполняемых файлов (.htaccess для защиты директории)
    $htaccess_file = $dir . '.htaccess';
    if (!file_exists($htaccess_file)) {
        file_put_contents($htaccess_file, "RemoveHandler .php .phtml .php3\nphp_flag engine off\n");
    }

    $target_path = $dir . $fn;

    // 🔒 Перемещение файла с проверкой результата
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        // Дополнительная защита: установка прав только на чтение
        chmod($target_path, 0644);
        return ['success' => true, 'filename' => 'product/' . $fn];
    }

    log_error('uploadProductImage: Failed to move uploaded file - ' . $file['name'], 'ERROR');
    return ['success' => false, 'message' => 'Ошибка при сохранении файла'];
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
