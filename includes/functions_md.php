<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

function getModeratorStats($uid) {
    global $conn;
    return [
        'products' => $conn->query("SELECT COUNT(*) FROM products WHERE created_by=$uid AND active=1")->fetch_row()[0],
        'orders' => $conn->query("SELECT COUNT(*) FROM orders")->fetch_row()[0],
        'total_value' => $conn->query("SELECT COALESCE(SUM(price*stock),0) FROM products WHERE created_by=$uid")->fetch_row()[0]
    ];
}
function getModeratorProducts($uid) {
    global $conn; $s=$conn->prepare("SELECT * FROM products WHERE created_by=? ORDER BY created_at DESC"); $s->bind_param("i",$uid); $s->execute(); return $s->get_result()->fetch_all(MYSQLI_ASSOC);
}
function isProductOwner($pid,$uid) {
    global $conn; $s=$conn->prepare("SELECT id FROM products WHERE id=? AND created_by=?"); $s->bind_param("ii",$pid,$uid); $s->execute(); return $s->get_result()->num_rows>0;
}
function addProductModerator($n,$d,$p,$cat,$st,$nw,$img,$cb) { return addProduct($n,$d,$p,$cat,$st,$nw,$img,$cb); }
function editProductModerator($id,$n,$d,$p,$cat,$st,$nw,$img,$uid) {
    if(!isProductOwner($id,$uid)) return ['success'=>false,'message'=>'Нет прав'];
    return editProduct($id,$n,$d,$p,$cat,$st,$nw,$img);
}
function deleteProductModerator($pid,$uid) {
    if(!isProductOwner($pid,$uid)) return ['success'=>false,'message'=>'Нет прав'];
    return deleteProduct($pid);
}
function getAllOrdersModerator($s=null) { return getAllOrders($s); }
function getOrderDetailsModerator($id) { return getOrderDetailsAdmin($id); }
function updateOrderStatusModerator($id,$st) { return updateOrderStatusAdmin($id,$st); }