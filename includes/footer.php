<?php
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
        <p>&copy; 2025 Команда АВВА. Все права защищены.</p>
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
</footer>
<script src="/js/main.js"></script>
<script src="/js/basket.js"></script>
<script src="/js/detail.js"></script>
<script src="/js/validation.js"></script>
<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <?php
    $current_page = basename($_SERVER['PHP_SELF'], '.php');
    if ($current_page === 'manage_users') {
        echo '<script src="/js/admin/users.js"></script>';
    } elseif ($current_page === 'settings') {
        echo '<script src="/js/admin/settings.js"></script>';
    } elseif ($current_page === 'manage_category') {
        echo '<script src="/js/admin/categories.js"></script>';
    } elseif ($current_page === 'manage_review') {
        echo '<script src="/js/admin/review.js"></script>';
    }
    ?>
<?php endif; ?>
<?php if (basename($_SERVER['PHP_SELF'], '.php') === 'review' && isset($_SESSION['role']) && $_SESSION['role'] !== 'admin'): ?>
    <script src="/js/admin/review.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initUserReviewHandlers();
        });
    </script>
<?php endif; ?>
</body>
</html>