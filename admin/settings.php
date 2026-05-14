<?php
/**
 * Настройки стиля оформления сайта "Волшебная ЛАВКА"
 * Разработчик: АВВА © 2025
 */
$page_title = 'Настройки оформления';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions_adm.php';
requireRole('admin');

// Получение текущих настроек из сессии или значений по умолчанию
$current_settings = [
    'primary_color' => $_SESSION['site_primary_color'] ?? '#6B2D9E',
    'secondary_color' => $_SESSION['site_secondary_color'] ?? '#4361EE',
    'accent_color' => $_SESSION['site_accent_color'] ?? '#E01E5A',
    'background_type' => $_SESSION['site_background_type'] ?? 'gradient',
    'background_image' => $_SESSION['site_background'] ?? '',
    'favicon' => $_SESSION['site_favicon'] ?? '',
    'font_family' => $_SESSION['site_font_family'] ?? 'Montserrat, sans-serif',
    'border_radius' => $_SESSION['site_border_radius'] ?? '12',
    'use_categories' => $_SESSION['use_categories'] ?? true,
    'show_animations' => $_SESSION['site_show_animations'] ?? true,
    'card_shadow' => $_SESSION['site_card_shadow'] ?? 'medium'
];

// Обработка загрузки файлов
$upload_dir = __DIR__ . '/../../images/background/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$allowed_image_types = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml'];
$upload_errors = [];
$upload_success = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['site_background'])) {
    $file = $_FILES['site_background'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        if (in_array($file['type'], $allowed_image_types)) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_name = 'fon.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_name)) {
                $_SESSION['site_background'] = $new_name;
                $current_settings['background_image'] = $new_name;
                $upload_success[] = 'Фон успешно загружен';
            } else {
                $upload_errors[] = 'Ошибка перемещения файла фона';
            }
        } else {
            $upload_errors[] = 'Недопустимый формат фона (разрешены: JPEG, PNG, GIF, SVG)';
        }
    } elseif ($file['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload_errors[] = 'Ошибка загрузки фона: код ' . $file['error'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['site_favicon'])) {
    $file = $_FILES['site_favicon'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        if ($file['type'] === 'image/svg+xml' || $file['type'] === 'image/x-icon' || in_array($file['type'], $allowed_image_types)) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_name = 'favicon.' . ($file['type'] === 'image/svg+xml' ? 'svg' : ($ext ?: 'ico'));
            if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_name)) {
                $_SESSION['site_favicon'] = $new_name;
                $current_settings['favicon'] = $new_name;
                $upload_success[] = 'Фавиконка успешно загружена';
            } else {
                $upload_errors[] = 'Ошибка перемещения файла фавиконки';
            }
        } else {
            $upload_errors[] = 'Фавиконка должна быть в формате SVG, ICO, PNG или GIF';
        }
    } elseif ($file['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload_errors[] = 'Ошибка загрузки фавиконки: код ' . $file['error'];
    }
}

// Обработка сохранения цветовых настроек
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_colors'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = 'Ошибка безопасности (CSRF)';
    } else {
        $primary = sanitize($_POST['primary_color'] ?? '#6B2D9E');
        $secondary = sanitize($_POST['secondary_color'] ?? '#4361EE');
        $accent = sanitize($_POST['accent_color'] ?? '#E01E5A');

        // Валидация hex-цветов
        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $primary) &&
            preg_match('/^#[0-9A-Fa-f]{6}$/', $secondary) &&
            preg_match('/^#[0-9A-Fa-f]{6}$/', $accent)) {

            $_SESSION['site_primary_color'] = $primary;
            $_SESSION['site_secondary_color'] = $secondary;
            $_SESSION['site_accent_color'] = $accent;
            $current_settings['primary_color'] = $primary;
            $current_settings['secondary_color'] = $secondary;
            $current_settings['accent_color'] = $accent;
            $_SESSION['success'] = 'Цветовая схема обновлена';
        } else {
            $_SESSION['error'] = 'Некорректный формат цвета';
        }
    }
}

// Обработка сохранения общих настроек
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_general'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = 'Ошибка безопасности (CSRF)';
    } else {
        $_SESSION['site_background_type'] = sanitize($_POST['background_type'] ?? 'gradient');
        $_SESSION['site_font_family'] = sanitize($_POST['font_family'] ?? 'Montserrat, sans-serif');
        $_SESSION['site_border_radius'] = intval($_POST['border_radius'] ?? 12);
        $_SESSION['use_categories'] = isset($_POST['use_categories']);
        $_SESSION['site_show_animations'] = isset($_POST['show_animations']);
        $_SESSION['site_card_shadow'] = sanitize($_POST['card_shadow'] ?? 'medium');

        $current_settings['background_type'] = $_SESSION['site_background_type'];
        $current_settings['font_family'] = $_SESSION['site_font_family'];
        $current_settings['border_radius'] = $_SESSION['site_border_radius'];
        $current_settings['use_categories'] = $_SESSION['use_categories'];
        $current_settings['show_animations'] = $_SESSION['site_show_animations'];
        $current_settings['card_shadow'] = $_SESSION['site_card_shadow'];

        $_SESSION['success'] = 'Общие настройки обновлены';
    }
}

// Сброс настроек к значениям по умолчанию
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_defaults'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = 'Ошибка безопасности (CSRF)';
    } else {
        unset($_SESSION['site_primary_color']);
        unset($_SESSION['site_secondary_color']);
        unset($_SESSION['site_accent_color']);
        unset($_SESSION['site_background_type']);
        unset($_SESSION['site_font_family']);
        unset($_SESSION['site_border_radius']);
        unset($_SESSION['site_show_animations']);
        unset($_SESSION['site_card_shadow']);

        $current_settings = [
            'primary_color' => '#6B2D9E',
            'secondary_color' => '#4361EE',
            'accent_color' => '#E01E5A',
            'background_type' => 'gradient',
            'background_image' => '',
            'favicon' => '',
            'font_family' => 'Montserrat, sans-serif',
            'border_radius' => '12',
            'use_categories' => true,
            'show_animations' => true,
            'card_shadow' => 'medium'
        ];
        $_SESSION['success'] = 'Настройки сброшены к значениям по умолчанию';
    }
}
?>

<style>
    .settings-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem;
    }

    .settings-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .settings-header h1 {
        color: var(--color-purple-bright, #9D4EDD);
        font-size: 2.5rem;
        margin-bottom: 0.5rem;
    }

    .settings-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
        gap: 2rem;
        margin-bottom: 2rem;
    }

    .settings-card {
        background: var(--gradient-card, linear-gradient(145deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%));
        border-radius: var(--radius-lg, 12px);
        padding: 2rem;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: var(--shadow-medium, 0 8px 15px rgba(0, 0, 0, 0.2));
    }

    .settings-card h2 {
        color: var(--color-gold, #FFD700);
        font-size: 1.5rem;
        margin-bottom: 1.5rem;
        border-bottom: 2px solid var(--color-purple-light, #C77DFF);
        padding-bottom: 0.5rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        color: var(--color-lavender, #B8B8FF);
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .form-group input[type="text"],
    .form-group input[type="number"],
    .form-group select {
        width: 100%;
        padding: 0.75rem;
        border-radius: var(--radius-md, 8px);
        border: 2px solid var(--color-purple-light, #C77DFF);
        background: rgba(255, 255, 255, 0.9);
        color: var(--color-black, #1A1A2E);
        font-size: 1rem;
        transition: var(--transition-fast, 0.2s ease);
    }

    .form-group input[type="text"]:focus,
    .form-group input[type="number"]:focus,
    .form-group select:focus {
        outline: none;
        border-color: var(--color-gold, #FFD700);
        box-shadow: var(--glow-gold, 0 0 20px rgba(255, 215, 0, 0.6));
    }

    .color-picker-group {
        display: flex;
        gap: 1rem;
        align-items: center;
    }

    .color-picker-group input[type="color"] {
        width: 60px;
        height: 60px;
        border: none;
        border-radius: var(--radius-md, 8px);
        cursor: pointer;
        background: transparent;
    }

    .color-picker-group input[type="text"] {
        flex: 1;
    }

    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .checkbox-group input[type="checkbox"] {
        width: 20px;
        height: 20px;
        cursor: pointer;
    }

    .btn {
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: var(--radius-md, 8px);
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition-normal, 0.3s ease);
        text-decoration: none;
        display: inline-block;
    }

    .btn-primary {
        background: var(--gradient-magic-primary, linear-gradient(135deg, #6B2D9E 0%, #4361EE 50%, #9D4EDD 100%));
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: var(--glow-purple, 0 0 20px rgba(157, 78, 221, 0.6));
    }

    .btn-secondary {
        background: var(--gradient-silver, linear-gradient(135deg, #E8E8E8 0%, #C0C0C0 100%));
        color: var(--color-black, #1A1A2E);
    }

    .btn-danger {
        background: var(--gradient-magic-accent, linear-gradient(135deg, #E01E5A 0%, #9D4EDD 100%));
        color: white;
    }

    .btn-group {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
    }

    .alert {
        padding: 1rem;
        border-radius: var(--radius-md, 8px);
        margin-bottom: 1rem;
        font-weight: 500;
    }

    .alert-success {
        background: rgba(46, 196, 182, 0.2);
        border: 2px solid var(--color-emerald, #2EC4B6);
        color: var(--color-emerald-light, #6FFFE9);
    }

    .alert-error {
        background: rgba(224, 30, 90, 0.2);
        border: 2px solid var(--color-ruby, #E01E5A);
        color: var(--color-ruby-light, #FF6B9D);
    }

    .preview-section {
        margin-top: 2rem;
        padding: 2rem;
        background: var(--gradient-card, linear-gradient(145deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%));
        border-radius: var(--radius-lg, 12px);
    }

    .preview-section h2 {
        color: var(--color-gold, #FFD700);
        margin-bottom: 1rem;
    }

    .style-preview {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .preview-card {
        padding: 1.5rem;
        border-radius: var(--radius-lg, 12px);
        background: rgba(255, 255, 255, 0.1);
        min-width: 200px;
    }

    .preview-color {
        width: 100px;
        height: 100px;
        border-radius: var(--radius-md, 8px);
        margin-bottom: 0.5rem;
        border: 2px solid white;
    }

    .back-link {
        display: inline-block;
        margin-top: 2rem;
        color: var(--color-lavender, #B8B8FF);
        text-decoration: none;
        transition: var(--transition-fast, 0.2s ease);
    }

    .back-link:hover {
        color: var(--color-gold, #FFD700);
        text-decoration: underline;
    }

    @media (max-width: 768px) {
        .settings-grid {
            grid-template-columns: 1fr;
        }

        .color-picker-group {
            flex-direction: column;
            align-items: stretch;
        }
    }
</style>

<section class="settings-container">
    <div class="settings-header">
        <h1>✨ Настройки оформления сайта ✨</h1>
        <p style="color: var(--color-lavender, #B8B8FF);">Управляйте стилем и внешним видом вашего магазина</p>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo e($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error"><?php echo e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <?php foreach ($upload_success as $msg): ?>
        <div class="alert alert-success"><?php echo e($msg); ?></div>
    <?php endforeach; ?>

    <?php foreach ($upload_errors as $err): ?>
        <div class="alert alert-error"><?php echo e($err); ?></div>
    <?php endforeach; ?>

    <div class="settings-grid">
        <!-- Цветовая схема -->
        <div class="settings-card">
            <h2>🎨 Цветовая схема</h2>
            <form method="POST" class="settings-form">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

                <div class="form-group">
                    <label>Основной цвет</label>
                    <div class="color-picker-group">
                        <input type="color" id="primary_color_picker" value="<?php echo e($current_settings['primary_color']); ?>">
                        <input type="text" name="primary_color" id="primary_color" value="<?php echo e($current_settings['primary_color']); ?>" pattern="#[0-9A-Fa-f]{6}" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Вторичный цвет</label>
                    <div class="color-picker-group">
                        <input type="color" id="secondary_color_picker" value="<?php echo e($current_settings['secondary_color']); ?>">
                        <input type="text" name="secondary_color" id="secondary_color" value="<?php echo e($current_settings['secondary_color']); ?>" pattern="#[0-9A-Fa-f]{6}" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Акцентный цвет</label>
                    <div class="color-picker-group">
                        <input type="color" id="accent_color_picker" value="<?php echo e($current_settings['accent_color']); ?>">
                        <input type="text" name="accent_color" id="accent_color" value="<?php echo e($current_settings['accent_color']); ?>" pattern="#[0-9A-Fa-f]{6}" required>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" name="save_colors" class="btn btn-primary">Сохранить цвета</button>
                </div>
            </form>
        </div>

        <!-- Фон и фавикон -->
        <div class="settings-card">
            <h2>🖼️ Фон и иконки</h2>
            <form method="POST" enctype="multipart/form-data" class="settings-form">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

                <div class="form-group">
                    <label>Тип фона</label>
                    <select name="background_type">
                        <option value="gradient" <?php echo $current_settings['background_type'] === 'gradient' ? 'selected' : ''; ?>>Градиент</option>
                        <option value="image" <?php echo $current_settings['background_type'] === 'image' ? 'selected' : ''; ?>>Изображение</option>
                        <option value="solid" <?php echo $current_settings['background_type'] === 'solid' ? 'selected' : ''; ?>>Сплошной цвет</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Фоновое изображение</label>
                    <input type="file" name="site_background" accept="image/*">
                    <?php if (!empty($current_settings['background_image'])): ?>
                        <p style="margin-top: 0.5rem; color: var(--color-lavender);">
                            Текущий фон: <strong><?php echo e($current_settings['background_image']); ?></strong>
                        </p>
                        <img src="/images/background/<?php echo e($current_settings['background_image']); ?>"
                             alt="Текущий фон"
                             style="max-width: 200px; margin-top: 0.5rem; border-radius: 8px;">
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Фавиконка (иконка сайта)</label>
                    <input type="file" name="site_favicon" accept="image/*,.ico">
                    <?php if (!empty($current_settings['favicon'])): ?>
                        <p style="margin-top: 0.5rem; color: var(--color-lavender);">
                            Текущая фавиконка: <strong><?php echo e($current_settings['favicon']); ?></strong>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">Загрузить файлы</button>
                </div>
            </form>
        </div>

        <!-- Общие настройки -->
        <div class="settings-card">
            <h2>⚙️ Общие настройки</h2>
            <form method="POST" class="settings-form">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

                <div class="form-group">
                    <label>Шрифт</label>
                    <select name="font_family">
                        <option value="Montserrat, sans-serif" <?php echo $current_settings['font_family'] === 'Montserrat, sans-serif' ? 'selected' : ''; ?>>Montserrat</option>
                        <option value="'Open Sans', sans-serif" <?php echo $current_settings['font_family'] === "'Open Sans', sans-serif" ? 'selected' : ''; ?>>Open Sans</option>
                        <option value="'Roboto', sans-serif" <?php echo $current_settings['font_family'] === "'Roboto', sans-serif" ? 'selected' : ''; ?>>Roboto</option>
                        <option value="'Playfair Display', serif" <?php echo $current_settings['font_family'] === "'Playfair Display', serif" ? 'selected' : ''; ?>>Playfair Display</option>
                        <option value="'Lato', sans-serif" <?php echo $current_settings['font_family'] === "'Lato', sans-serif" ? 'selected' : ''; ?>>Lato</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Радиус скругления (px)</label>
                    <input type="number" name="border_radius" value="<?php echo e($current_settings['border_radius']); ?>" min="0" max="50">
                </div>

                <div class="form-group">
                    <label>Тень карточек</label>
                    <select name="card_shadow">
                        <option value="none" <?php echo $current_settings['card_shadow'] === 'none' ? 'selected' : ''; ?>>Без тени</option>
                        <option value="soft" <?php echo $current_settings['card_shadow'] === 'soft' ? 'selected' : ''; ?>>Мягкая</option>
                        <option value="medium" <?php echo $current_settings['card_shadow'] === 'medium' ? 'selected' : ''; ?>>Средняя</option>
                        <option value="strong" <?php echo $current_settings['card_shadow'] === 'strong' ? 'selected' : ''; ?>>Сильная</option>
                    </select>
                </div>

                <div class="form-group checkbox-group">
                    <input type="checkbox" name="use_categories" id="use_categories" <?php echo $current_settings['use_categories'] ? 'checked' : ''; ?>>
                    <label for="use_categories">Использовать категории</label>
                </div>

                <div class="form-group checkbox-group">
                    <input type="checkbox" name="show_animations" id="show_animations" <?php echo $current_settings['show_animations'] ? 'checked' : ''; ?>>
                    <label for="show_animations">Показывать анимации</label>
                </div>

                <div class="btn-group">
                    <button type="submit" name="save_general" class="btn btn-primary">Сохранить настройки</button>
                </div>
            </form>
        </div>

        <!-- Предпросмотр -->
        <div class="settings-card">
            <h2>👁️ Предпросмотр</h2>
            <div class="preview-section">
                <div class="style-preview">
                    <div class="preview-card">
                        <div class="preview-color" style="background-color: <?php echo e($current_settings['primary_color']); ?>;"></div>
                        <p>Основной</p>
                        <small><?php echo e($current_settings['primary_color']); ?></small>
                    </div>

                    <div class="preview-card">
                        <div class="preview-color" style="background-color: <?php echo e($current_settings['secondary_color']); ?>;"></div>
                        <p>Вторичный</p>
                        <small><?php echo e($current_settings['secondary_color']); ?></small>
                    </div>

                    <div class="preview-card">
                        <div class="preview-color" style="background-color: <?php echo e($current_settings['accent_color']); ?>;"></div>
                        <p>Акцентный</p>
                        <small><?php echo e($current_settings['accent_color']); ?></small>
                    </div>
                </div>

                <div style="margin-top: 1.5rem;">
                    <p><strong>Шрифт:</strong> <?php echo e($current_settings['font_family']); ?></p>
                    <p><strong>Радиус:</strong> <?php echo e($current_settings['border_radius']); ?>px</p>
                    <p><strong>Тень:</strong> <?php echo e($current_settings['card_shadow']); ?></p>
                    <p><strong>Анимации:</strong> <?php echo $current_settings['show_animations'] ? 'Включены' : 'Выключены'; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Сброс настроек -->
    <div class="settings-card" style="margin-top: 2rem;">
        <h2>⚠️ Зона опасности</h2>
        <p style="color: var(--color-lavender); margin-bottom: 1rem;">
            Сбросьте все настройки к значениям по умолчанию. Это действие нельзя отменить.
        </p>
        <form method="POST" onsubmit="return confirm('Вы уверены, что хотите сбросить все настройки?');">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <button type="submit" name="reset_defaults" class="btn btn-danger">Сбросить к настройкам по умолчанию</button>
        </form>
    </div>

    <a href="/admin/index.php" class="back-link">← Назад в админ-панель</a>
</section>

<script>
    // Синхронизация color picker и text input
    document.getElementById('primary_color_picker').addEventListener('input', function(e) {
        document.getElementById('primary_color').value = e.target.value;
    });

    document.getElementById('secondary_color_picker').addEventListener('input', function(e) {
        document.getElementById('secondary_color').value = e.target.value;
    });

    document.getElementById('accent_color_picker').addEventListener('input', function(e) {
        document.getElementById('accent_color').value = e.target.value;
    });

    // Обратная синхронизация
    document.getElementById('primary_color').addEventListener('input', function(e) {
        document.getElementById('primary_color_picker').value = e.target.value;
    });

    document.getElementById('secondary_color').addEventListener('input', function(e) {
        document.getElementById('secondary_color_picker').value = e.target.value;
    });

    document.getElementById('accent_color').addEventListener('input', function(e) {
        document.getElementById('accent_color_picker').value = e.target.value;
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
