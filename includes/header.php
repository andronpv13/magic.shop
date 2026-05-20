<?php
/**
 * magic.shop — Шапка сайта
 * Подключается в начале каждой страницы
 */
if (!defined('ROOT_PATH')) {
    require_once __DIR__ . '/config.php';
}
$page_title = $page_title ?? 'Волшебная ЛАВКА';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Интернет-магазин магических товаров — Волшебная ЛАВКА">
    <title><?= e($page_title) ?> | Волшебная ЛАВКА</title>
    
    <!-- ✅ Динамические пути к ассетам -->
    <link rel="stylesheet" href="<?= site_url('css/magic.css') ?>">
    <link rel="icon" href="<?= site_url('images/favicon.ico') ?>" type="image/x-icon">
    
    <!-- JS с отложенной загрузкой -->
    <script src="<?= site_url('js/main.js') ?>" defer></script>
    <script src="<?= site_url('js/basket.js') ?>" defer></script>
</head>
<body>
    <!-- 🔔 Всплывающие уведомления -->
    <div id="toast-container" aria-live="polite"></div>
    
    <!-- 🧭 Навигация -->
    <header class="site-header">
        <div class="container header-inner">
            <a href="<?= site_url('index.php') ?>" class="logo">
                <span class="logo-icon">✨</span>
                <span class="logo-text">Волшебная ЛАВКА</span>
            </a>
            
            <nav class="main-nav" aria-label="Главная навигация">
                <a href="<?= site_url('index.php') ?>">Главная</a>
                <a href="<?= site_url('shop.php') ?>">Каталог</a>
                
                <?php if (isLoggedIn()): ?>
                    <?php if (isAdmin()): ?>
                        <a href="<?= site_url('admin/index.php') ?>" class="nav-admin">🛠️ Админка</a>
                    <?php elseif (isModerator()): ?>
                        <a href="<?= site_url('moderator/index_md.php') ?>" class="nav-mod">🔍 Модерация</a>
                    <?php endif; ?>
                    
                    <a href="<?= site_url('users/profile.php') ?>">👤 Профиль</a>
                    <a href="<?= site_url('users/orders.php') ?>">📦 Заказы</a>
                    <a href="<?= site_url('basket/basket.php') ?>" class="nav-basket">
                        🛒 Корзина 
                        <?php $cnt = array_sum($_SESSION['basket'] ?? []); if ($cnt): ?>
                            <span class="badge"><?= (int)$cnt ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?= site_url('logout.php') ?>" class="nav-logout">🚪 Выход</a>
                <?php else: ?>
                    <a href="<?= site_url('login.php') ?>">🔐 Войти</a>
                    <a href="<?= site_url('users/register.php') ?>" class="btn btn-sm">Регистрация</a>
                <?php endif; ?>
            </nav>
            
            <!-- 🍔 Мобильное меню (гамбургер) -->
            <button class="mobile-toggle" aria-label="Меню" aria-expanded="false">☰</button>
        </div>
    </header>
    
    <!-- 📣 Баннер (если есть) -->
    <?php if (defined('SHOW_BANNER') && SHOW_BANNER && !isAdmin()): ?>
        <div class="banner">
            <div class="container">
                <?= defined('BANNER_TEXT') ? e(BANNER_TEXT) : '🎁 Скидка 10% на первый заказ!' ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- 🍞 Хлебные крошки (опционально) -->
    <?php if (!empty($breadcrumbs)): ?>
        <nav class="breadcrumbs" aria-label="Хлебные крошки">
            <div class="container">
                <?php foreach ($breadcrumbs as $i => $crumb): ?>
                    <?php if ($i < count($breadcrumbs) - 1): ?>
                        <a href="<?= e($crumb['url']) ?>"><?= e($crumb['title']) ?></a> / 
                    <?php else: ?>
                        <span><?= e($crumb['title']) ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </nav>
    <?php endif; ?>
    
    <!-- 📦 Основной контент -->
    <main class="container">