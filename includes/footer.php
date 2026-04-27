<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// Проверяем, загрузилась ли функция, на случай если файл вызван напрямую
if (!function_exists('getAdminContactInfo')) {
    function getAdminContactInfo() { return ['email' => '', 'phone' => '']; }
}

$admin_info = getAdminContactInfo();
?>
</main>
<footer>
<div class="footer-content">
    <div class="footer-section">
        <h3>О нас</h3>
        <p>Интернет-магазин "Волшебная ЛАВКА"</p>
        <p>© 2025 Команда АВВА</p>
    </div>
    <div class="footer-section">
        <h3>Контакты</h3>
        <p>Email: <?php echo isset($admin_info['email']) && $admin_info['email'] !== '' ? sanitize($admin_info['email']) : 'info@magicshop.ru'; ?></p>
        <p>Телефон: <?php echo isset($admin_info['phone']) && $admin_info['phone'] !== '' ? sanitize($admin_info['phone']) : '+7 (999) 123-45-67'; ?></p>
    </div>
    <div class="footer-section">
        <h3>Мы в соцсетях</h3>
        <div class="social-links">
            <a href="#" class="social-link">VK</a>
            <a href="#" class="social-link">Telegram</a>
            <a href="#" class="social-link">Instagram</a>
        </div>
    </div>
</div>
<div class="footer-bottom">
    <p>&copy; 2025 Волшебная ЛАВКА. Все права защищены.</p>
</div>
</footer>
<script src="/js/basket.js"></script>
<script src="/js/validation.js"></script>
</body>
</html>