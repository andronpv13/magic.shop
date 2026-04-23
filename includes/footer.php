<?php
/**
 * Подвал сайта "Волшебная ЛАВКА"
 * Разработчик: АВВА © 2025
 */
?>
    </main>

    <footer class="site-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>✨ Волшебная ЛАВКА</h3>
                    <p>Лучший магазин магических товаров</p>
                </div>
                <div class="footer-section">
                    <h3>Контакты</h3>
                    <?php 
                        // Значения по умолчанию, если в базе пусто
                        $email = !empty($site_settings['email']) ? $site_settings['email'] : 'info@magic.shop';
                        $phone = !empty($site_settings['phone']) ? $site_settings['phone'] : '+7 (999) 123-45-67';
                    ?>
                    <p>Email: <?php echo e($email); ?></p>
                    <p>Телефон: <?php echo e($phone); ?></p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Волшебная ЛАВКА. Все права защищены. Разработчик: <strong>АВВА</strong></p>
            </div>
        </div>
    </footer>

    <div id="notification-container" class="notification-container"></div>

</body>
</html>
