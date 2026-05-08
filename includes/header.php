<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// Генерируем CSRF токен, если его ещё нет
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
<link rel="stylesheet" href="/css/magic.css">
<link rel="icon" type="image/svg+xml" href="/images/favicon.svg">
<meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
</head>
<body>
<header>
<nav class="navbar">
    <div class="logo">
        <a href="/index.php">
            <img src="/images/logo_.png" alt="Волшебная ЛАВКА" class="logo-img magic">
            <!--<span class="logo-text">Волшебная ЛАВКА</span>-->
        </a>
    </div>
    <div class="nav-links">
        <a href="/index.php" class="nav-link">Главная</a>
        <a href="/shop.php" class="nav-link">Каталог</a>
        <?php if (isLoggedIn()): ?>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="/admin/index.php" class="nav-link">Админка</a>
                <a href="/admin/cab.php" class="nav-link">Личный кабинет</a>
                <a href="/admin/edit_cab.php" class="nav-link">Редактировать профиль</a>
            <?php elseif ($_SESSION['role'] === 'moderator'): ?>
                <a href="/moderator/index_md.php" class="nav-link">Модерация</a>
                <a href="/moderator/cab_md.php" class="nav-link">Личный кабинет</a>
                <a href="/moderator/edit_cab_md.php" class="nav-link">Редактировать профиль</a>
            <?php else: ?>
                <a href="/users/profile.php" class="nav-link">Личный кабинет</a>
                <a href="/users/edit_profile.php" class="nav-link">Редактировать профиль</a>
            <?php endif; ?>
            <a href="/logout.php" class="nav-link">Выйти</a>
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
