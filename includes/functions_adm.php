<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

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

function getAllProducts() { return getProducts(); }
function getProductsCountByCategory($cat_id) { /* Заглушка, если категории по ID */ return 0; }

function addProduct($n,$d,$p,$cat,$st,$nw,$img,$cb) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO products (name,description,price,category,stock,is_new,image,created_by) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->bind_param("ssdisssi",$n,$d,$p,$cat,$st,$nw,$img,$cb);
    return ['success'=>$stmt->execute()];
}
function editProduct($id,$n,$d,$p,$cat,$st,$nw,$img) {
    global $conn;
    $stmt = $conn->prepare("UPDATE products SET name=?,description=?,price=?,category=?,stock=?,is_new=?,image=? WHERE id=?");
    $stmt->bind_param("ssdisssi",$n,$d,$p,$cat,$st,$nw,$img,$id);
    return ['success'=>$stmt->execute()];
}
function deleteProduct($id) {
    global $conn;
    $stmt = $conn->prepare("UPDATE products SET active=0 WHERE id=?");
    $stmt->bind_param("i",$id); return ['success'=>$stmt->execute()];
}
function getCategoriesList() { global $conn; return $conn->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL ORDER BY category")->fetch_all(MYSQLI_ASSOC); }
function getCategoryByName($n) {
    global $conn; $res = $conn->query("SELECT DISTINCT category FROM products WHERE category='$n' LIMIT 1");
    return $res->fetch_assoc();
}
function addCategory($name) { return ['success'=>true, 'message'=>'Категория создана']; }
function uploadProductImage($file) {
    $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
    if (!in_array($file['type'],$allowed) || $file['size']>5*1024*1024) return ['success'=>false,'message'=>'Ошибка формата'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fn = uniqid('prod_').'.'.$ext;
    $dir = __DIR__.'/../images/product/';
    if (!is_dir($dir)) mkdir($dir,0755,true);
    if (move_uploaded_file($file['tmp_name'],$dir.$fn)) return ['success'=>true,'filename'=>'product/'.$fn];
    return ['success'=>false,'message'=>'Ошибка загрузки'];
}
function getAllOrders($s=null) {
    global $conn; $sql="SELECT o.*,u.username,u.email FROM orders o JOIN users u ON o.user_id=u.id";
    if($s) { $stmt=$conn->prepare($sql." WHERE o.status=?"); $stmt->bind_param("s",$s); } else { $stmt=$conn->prepare($sql); }
    $stmt->execute(); return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
function getOrderDetailsAdmin($id) {
    global $conn;
    $s=$conn->prepare("SELECT o.*,u.username,u.email,u.phone,u.name as first_name,u.surname as last_name FROM orders o LEFT JOIN users u ON o.user_id=u.id WHERE o.id=?");
    $s->bind_param("i",$id); $s->execute(); $o=$s->get_result()->fetch_assoc(); if(!$o) return false;
    $s=$conn->prepare("SELECT oi.quantity,oi.price,p.name as product_name FROM order_items oi JOIN products p ON oi.product_id=p.id WHERE oi.order_id=?");
    $s->bind_param("i",$id); $s->execute(); $o['items']=$s->get_result()->fetch_all(MYSQLI_ASSOC); return $o;
}
function updateOrderStatusAdmin($id,$st) { global $conn; $s=$conn->prepare("UPDATE orders SET status=? WHERE id=?"); $s->bind_param("si",$st,$id); return ['success'=>$s->execute()]; }
function getAllReviews() { global $conn; $s=$conn->prepare("SELECT r.*,u.username,p.name as product_name FROM reviews r JOIN users u ON r.user_id=u.id JOIN products p ON r.product_id=p.id ORDER BY r.created_at DESC"); $s->execute(); return $s->get_result()->fetch_all(MYSQLI_ASSOC); }
function deleteReview($id) { global $conn; $s=$conn->prepare("DELETE FROM reviews WHERE id=?"); $s->bind_param("i",$id); return ['success'=>$s->execute()]; }
function getAllUsers() { global $conn; $s=$conn->prepare("SELECT id,username,email,name as first_name,surname as last_name,role,created_at FROM users ORDER BY created_at DESC"); $s->execute(); return $s->get_result()->fetch_all(MYSQLI_ASSOC); }
function addUser($u,$e,$p,$fn,$ln,$r='moderator') {
    global $conn; $s=$conn->prepare("SELECT id FROM users WHERE username=? OR email=?"); $s->bind_param("ss",$u,$e); $s->execute();
    if($s->get_result()->num_rows>0) return ['success'=>false,'message'=>'Занято'];
    $h=password_hash($p,PASSWORD_DEFAULT); $s=$conn->prepare("INSERT INTO users (username,email,password,name,surname,role) VALUES (?,?,?,?,?,?)"); $s->bind_param("ssssss",$u,$e,$h,$fn,$ln,$r); return ['success'=>$s->execute()];
}
function deleteUser($id) { global $conn; $s=$conn->prepare("DELETE FROM users WHERE id=? AND role!='admin'"); $s->bind_param("i",$id); return ['success'=>$s->execute() && $s->affected_rows>0]; }
function resetUserPassword($id,$p) { global $conn; $h=password_hash($p,PASSWORD_DEFAULT); $s=$conn->prepare("UPDATE users SET password=? WHERE id=?"); $s->bind_param("si",$h,$id); return ['success'=>$s->execute()]; }