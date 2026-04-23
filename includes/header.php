<?php
/**
 * Шапка сайта "Волшебная ЛАВКА"
 * Разработчик: АВВА ©2025
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$current_user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? e($page_title) . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    <!-- Используем абсолютный путь к CSS -->
    <link rel="stylesheet" href="/css/style.css">
    <link rel="icon" href="/images/favicon.svg" type="image/svg+xml">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <div class="header-content">
                <a href="/" class="site-logo">
                    <h1><?php echo SITE_NAME; ?></h1>
                </a>
                
                <nav class="main-nav">
                    <a href="/" class="nav-link">Главная</a>
                    <a href="/shop.php" class="nav-link">Каталог</a>
                </nav>
                
                <div class="header-actions">
                    <a href="/cart/cart.php" class="cart-link">
                        <span class="cart-icon">🛒</span>
                        <span class="cart-count"><?php echo getCartCount(); ?></span>
                    </a>
                    
                    <?php if ($current_user): ?>
                        <a href="/users/profile.php" class="user-link">
                            <span class="user-icon">👤</span>
                            <span class="user-name"><?php echo e($current_user['username']); ?></span>
                        </a>
                        <a href="/logout.php" class="logout-link">Выход</a>
                    <?php else: ?>
                        <a href="/login.php" class="login-link">Вход</a>
                        <a href="/users/register.php" class="register-link">Регистрация</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>
    
    <main>
