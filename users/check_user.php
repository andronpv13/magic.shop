<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');
$type = $_POST['type'] ?? $_GET['type'] ?? '';
$value = trim($_POST['value'] ?? $_GET['value'] ?? '');
if (empty($value)) { echo json_encode(['exists' => false]); exit; }

global $conn;
$current_id = $_SESSION['user_id'] ?? 0;
$field = ($type === 'username') ? 'username' : 'email';
$stmt = $conn->prepare("SELECT id FROM users WHERE $field = ? AND id != ?");
$stmt->bind_param("si", $value, $current_id); $stmt->execute();
echo json_encode(['exists' => $stmt->get_result()->num_rows > 0]);