<?php
require_once 'config.php';

// Product functions
function getProducts($category = null, $limit = null) {
    global $conn;
    
    $sql = "SELECT p.*, u.name as creator_name 
            FROM products p 
            LEFT JOIN users u ON p.created_by = u.id 
            WHERE p.active = 1";
    
    if ($category) {
        $sql .= " AND p.category = ?";
    }
    
    if ($limit) {
        $sql .= " LIMIT ?";
    }
    
    $stmt = $conn->prepare($sql);
    
    if ($category && $limit) {
        $stmt->bind_param("si", $category, $limit);
    } elseif ($category) {
        $stmt->bind_param("s", $category);
    } elseif ($limit) {
        $stmt->bind_param("i", $limit);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getProductById($id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND active = 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

// User functions
function getUserById($id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

// Order functions
function createOrder($user_id, $items) {
    global $conn;
    
    $conn->begin_transaction();
    
    try {
        $total = 0;
        foreach ($items as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total, status) VALUES (?, ?, 'pending')");
        $stmt->bind_param("id", $user_id, $total);
        $stmt->execute();
        $order_id = $conn->insert_id;
        
        foreach ($items as $item) {
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiid", $order_id, $item['id'], $item['quantity'], $item['price']);
            $stmt->execute();
        }
        
        $conn->commit();
        return $order_id;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}

function getOrdersByUser($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT o.*, GROUP_CONCAT(p.name) as products 
                           FROM orders o 
                           LEFT JOIN order_items oi ON o.id = oi.order_id 
                           LEFT JOIN products p ON oi.product_id = p.id 
                           WHERE o.user_id = ? 
                           GROUP BY o.id 
                           ORDER BY o.created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Review functions
function getReviewsByProduct($product_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT r.*, u.name 
                           FROM reviews r 
                           JOIN users u ON r.user_id = u.id 
                           WHERE r.product_id = ? 
                           ORDER BY r.created_at DESC");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

function addReview($user_id, $product_id, $rating, $comment) {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO reviews (user_id, product_id, rating, comment) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $user_id, $product_id, $rating, $comment);
    return $stmt->execute();
}

// Basket functions
function addToBasket($product_id, $quantity) {
    global $conn;
    
    $product = getProductById($product_id);
    if (!$product) return false;
    
    if (!isset($_SESSION['basket'])) {
        $_SESSION['basket'] = [];
    }
    
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
