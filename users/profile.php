<?php
/**
 * Личный кабинет "Волшебная ЛАВКА" (Просмотр)
 * Разработчик: АВВА ©2025
 */
require_once '../includes/config.php';
require_once '../includes/header.php';
require_once '../includes/functions.php';

// Проверка авторизации
if (!isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

$page_title = 'Личный кабинет';
$current_user = [];

// --- ПОЛУЧЕНИЕ АКТУАЛЬНЫХ ДАННЫХ ИЗ БАЗЫ ---
try {
    $uid = (int)$_SESSION['user_id'];
    if ($uid <= 0) {
        throw new Exception("Неверный ID пользователя");
    }

    $stmt = $mysqli->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Пользователь не найден");
    }
    
    $current_user = $result->fetch_assoc();
    $stmt->close();
} catch (Exception $e) {
    error_log("Ошибка загрузки данных профиля: " . $e->getMessage());
    echo '<div class="container section"><p class="empty-state">Ошибка загрузки данных</p></div>';
    require_once '../includes/footer.php';
    exit;
}
// --------------------------------------------

if (!$current_user) {
    echo '<div class="container section"><p class="empty-state">Ошибка загрузки данных</p></div>';
    require_once '../includes/footer.php';
    exit;
}
?>

<section class="section">
    <div class="container">
        <div class="profile-layout">
            <!-- Левая колонка: Меню -->
            <div class="profile-section">
                <div class="user-profile-header">
                    <div class="user-avatar-lg">
                        <?php 
                        $name = $current_user['username'];
                        echo mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8'); 
                        ?>
                    </div>
                    <h2><?php echo e($current_user['first_name'] ?? $current_user['username']); ?></h2>
                    <p class="text-muted"><?php echo e($current_user['username']); ?></p>
                </div>

                <nav class="profile-nav">
                    <a href="/users/profile.php" class="profile-nav-link active">
                        <span class="nav-icon">👤</span> Личные данные
                    </a>
                    <a href="/users/orders.php" class="profile-nav-link">
                        <span class="nav-icon">📦</span> История заказов
                    </a>
                    <a href="/users/edit_profile.php" class="profile-nav-link">
                        <span class="nav-icon">✏️</span> Настройки профиля
                    </a>
                    <?php if (hasRole('admin') || hasRole('moderator')): ?>
                        <a href="/admin/index.php" class="profile-nav-link">
                            <span class="nav-icon">⚙️</span> Панель управления
                        </a>
                    <?php endif; ?>
                    <a href="/logout.php" class="profile-nav-link logout">
                        <span class="nav-icon">🚪</span> Выход
                    </a>
                </nav>
            </div>

            <!-- Правая колонка: Данные -->
            <div class="profile-section">
                <div class="section-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h2 class="section-title">Мои данные</h2>
                </div>

                <div class="profile-info-card">
                    <div class="info-row">
                        <span class="info-label">Логин:</span>
                        <span class="info-value"><?php echo e($current_user['username']); ?></span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo e($current_user['email']); ?></span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Фамилия:</span>
                        <span class="info-value"><?php echo e($current_user['last_name'] ?? 'Не указано'); ?></span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Имя:</span>
                        <span class="info-value"><?php echo e($current_user['first_name'] ?? 'Не указано'); ?></span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Отчество:</span>
                        <span class="info-value"><?php echo e($current_user['middle_name'] ?? 'Не указано'); ?></span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Телефон:</span>
                        <span class="info-value"><?php echo e($current_user['phone'] ?? 'Не указано'); ?></span>
                    </div>

                    <div class="info-group">
                        <span class="info-label">Адрес доставки:</span>
                        <div class="info-value-block">
                            <?php 
                            // Проверяем, заполнены ли поля адреса
                            $has_address = !empty($current_user['city']) || !empty($current_user['street']);
                            
                            if ($has_address): 
                                // Формируем красивый вывод адреса
                                $address_parts = [];
                                if (!empty($current_user['zip_code'])) $address_parts[] = e($current_user['zip_code']);
                                if (!empty($current_user['region'])) $address_parts[] = e($current_user['region'] . ' обл.');
                                if (!empty($current_user['city'])) $address_parts[] = 'г. ' . e($current_user['city']);
                                if (!empty($current_user['street'])) $address_parts[] = 'ул. ' . e($current_user['street']);
                                if (!empty($current_user['house'])) $address_parts[] = 'д. ' . e($current_user['house']);
                                if (!empty($current_user['apartment'])) $address_parts[] = 'кв. ' . e($current_user['apartment']);
                                
                                echo implode(', ', $address_parts);
                            else: 
                            ?>
                                <p class="text-muted">Адрес не указан</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once '../includes/footer.php'; ?>
