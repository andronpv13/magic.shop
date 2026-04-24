<?php
require_once 'config.php';
require_once 'functions.php';

function updateAdminContactInfo($email, $phone) {
    global $conn;
    
    $stmt = $conn->prepare("UPDATE users SET email = ?, phone = ? WHERE role = 'admin' LIMIT 1");
    $stmt->bind_param("ss", $email, $phone);
    return $stmt->execute();
}
