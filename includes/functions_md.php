<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions_adm.php';
<<<<<<< HEAD

// ============================================
// ФУНКЦИИ ДЛЯ МОДЕРАТОРА
// ============================================
=======
>>>>>>> 17aa9fe80430601b55ac05d1a95d326b8163eefa

function getModeratorStats($uid) {
    global $conn;

    // Продукты: COUNT с подготовленным выражением
    $stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE created_by=? AND active=1");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_row()[0];
    $stmt->close();

    // Заказы: общее количество (без фильтрации по uid, поэтому prepare не нужен)
    $orders = $conn->query("SELECT COUNT(*) FROM orders")->fetch_row()[0];

    // Общая стоимость: SUM с подготовленным выражением
    $stmt2 = $conn->prepare("SELECT COALESCE(SUM(price*stock),0) FROM products WHERE created_by=?");
    $stmt2->bind_param("i", $uid);
    $stmt2->execute();
    $total_value = $stmt2->get_result()->fetch_row()[0];
    $stmt2->close();

    return [
        'products' => $products,
        'orders' => $orders,
        'total_value' => $total_value
    ];
}
function getModeratorProducts($uid) {
    global $conn;
    $s=$conn->prepare("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.created_by=? ORDER BY p.created_at DESC");
    $s->bind_param("i",$uid);
    $s->execute();
    return $s->get_result()->fetch_all(MYSQLI_ASSOC);
}
function isProductOwner($pid,$uid) {
    global $conn;
    $s=$conn->prepare("SELECT id FROM products WHERE id=? AND created_by=?");
    $s->bind_param("ii",$pid,$uid);
    $s->execute();
    return $s->get_result()->num_rows>0;
}
function addProductModerator($n,$d,$p,$cat,$st,$nw,$img,$cb) {
    // Проверка прав модератора
    if (!isLoggedIn() || !in_array($_SESSION['role'], ['moderator', 'admin'])) {
        return ['success' => false, 'message' => 'Недостаточно прав для добавления товара'];
    }
    return addProduct($n,$d,$p,$cat,$st,$nw,$img,$cb);
}

function editProductModerator($id,$n,$d,$p,$cat,$st,$nw,$img,$uid) {
    // Проверка прав модератора
    if (!isLoggedIn() || !in_array($_SESSION['role'], ['moderator', 'admin'])) {
        return ['success' => false, 'message' => 'Недостаточно прав для редактирования товара'];
    }
    if(!isProductOwner($id,$uid)) return ['success'=>false,'message'=>'Нет прав'];
    return editProduct($id,$n,$d,$p,$cat,$st,$nw,$img);
}

function deleteProductModerator($pid,$uid) {
    // Проверка прав модератора
    if (!isLoggedIn() || !in_array($_SESSION['role'], ['moderator', 'admin'])) {
        return ['success' => false, 'message' => 'Недостаточно прав для удаления товара'];
    }
    if(!csrf_verify()) return ['success'=>false,'message'=>'Ошибка безопасности (CSRF)'];
    if(!isProductOwner($pid,$uid)) return ['success'=>false,'message'=>'Нет прав'];
    return deleteProduct($pid);
}

function getAllOrdersModerator($s=null) {
    // Проверка прав модератора
    if (!isLoggedIn() || !in_array($_SESSION['role'], ['moderator', 'admin'])) {
        return [];
    }
    return getAllOrders($s);
}

function getOrderDetailsModerator($id) {
    // Проверка прав модератора
    if (!isLoggedIn() || !in_array($_SESSION['role'], ['moderator', 'admin'])) {
        return false;
    }
    return getOrderDetailsAdmin($id);
}

function updateOrderStatusModerator($id,$st) {
    // Проверка прав модератора
    if (!isLoggedIn() || !in_array($_SESSION['role'], ['moderator', 'admin'])) {
        return ['success' => false, 'message' => 'Недостаточно прав для изменения статуса заказа'];
    }
    return updateOrderStatusAdmin($id,$st);
<<<<<<< HEAD
}
=======
}
>>>>>>> 17aa9fe80430601b55ac05d1a95d326b8163eefa
