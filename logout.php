<?php
/**
 * magic.shop — Выход из системы
 */

require_once __DIR__ . '/includes/config.php';

if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $username = $_SESSION['username'] ?? 'unknown';
    
    // 📝 Логирование выхода
    log_action($user_id, 'logout', "Пользователь {$username} вышел из системы", $log_file);
    
    // 🧹 Полная очистка сессии
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), 
            '', 
            time() - 42000,
            $params["path"], 
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    
    session_destroy();
}

// ✅ Исправлено: универсальный редирект
redirect_to('index.php');
?>