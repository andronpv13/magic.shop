<?php
require_once 'config.php';
require_once 'functions.php';

// Генерируем новый CSRF токен для каждой страницы
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Волшебная ЛАВКА</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="icon" type="image/svg+xml" href="/images/favicon.svg">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">
                <a href="/index.php">
                    <img src="/images/logo.svg" alt="Волшебная ЛАВКА" class="logo-img">
                    <span class="logo-text">Волшебная ЛАВКА</span>
                </a>
            </div>
            
            <div class="nav-links">
                <a href="/index.php" class="nav-link">Главная</a>
                <a href="/shop.php" class="nav-link">Каталог</a>
                
                <?php if (isLoggedIn()): ?>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a href="/admin/index.php" class="nav-link">Админка</a>
                    <?php elseif ($_SESSION['role'] === 'moderator'): ?>
                        <a href="/moderator/index_md.php" class="nav-link">Модерация</a>
                    <?php endif; ?>
                    
                    <a href="/users/profile.php" class="nav-link">Личный кабинет</a>
                    <a href="/users/logout_user.php" class="nav-link">Выйти</a>
                <?php else: ?>
                    <a href="/login.php" class="nav-link">Войти</a>
                <?php endif; ?>
            </div>
            
            <div class="basket-icon">
                <a href="/basket/basket.php">
                    <span class="basket-emoji">🛒</span>
                    <span class="basket-count" id="basket-count"><?php echo getBasketCount(); ?></span>
                </a>
            </div>
        </nav>
    </header>

    <main>
