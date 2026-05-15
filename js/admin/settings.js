/**
 * Скрипт управления настройками товаров и категориями
 * Волшебная ЛАВКА © 2025
 */

document.addEventListener('DOMContentLoaded', function() {
    // Инициализация состояния чекбокса категорий из localStorage
    initCategoryCheckbox();

    // Инициализация синхронизации цветовых пикеров
    initColorPickers();
});

/**
 * Инициализация чекбокса использования категорий
 */
function initCategoryCheckbox() {
    const checkbox = document.getElementById('use_categories');
    if (checkbox) {
        const localState = localStorage.getItem('use_categories');
        if (localState !== null) {
            checkbox.checked = localState === 'true';
        }
    }
}

/**
 * Переключение использования категорий (AJAX запрос)
 * @param {HTMLInputElement} checkbox - элемент чекбокса
 */
function toggleCategories(checkbox) {
    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        alert('Ошибка безопасности: CSRF токен не найден');
        return;
    }

    const formData = new FormData();
    formData.append('use_categories', checkbox.checked ? '1' : '0');
    formData.append('csrf_token', csrfToken);

    fetch('/admin/update_settings.php?ajax=1', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            localStorage.setItem('use_categories', checkbox.checked ? 'true' : 'false');
        } else {
            alert('Ошибка при обновлении настроек: ' + (data.message || 'Неизвестная ошибка'));
        }
    })
    .catch(() => alert('Ошибка при отправке запроса'));
}

/**
 * Переключение видимости поля категории в формах добавления/редактирования товара
 * @param {HTMLInputElement} checkbox - элемент чекбокса
 */
function toggleCategoryField(checkbox) {
    const categoryGroup = document.getElementById('category-group');
    const categorySelect = document.getElementById('category');
    const categoryInput = document.getElementById('category_name');

    if (!categoryGroup) return;

    if (checkbox.checked) {
        categoryGroup.style.display = 'block';
        categoryGroup.style.opacity = '1';
        if (categorySelect) categorySelect.disabled = false;
        if (categoryInput) categoryInput.disabled = false;
    } else {
        categoryGroup.style.display = 'none';
        categoryGroup.style.opacity = '0.5';
        if (categorySelect) {
            categorySelect.value = '';
            categorySelect.disabled = true;
        }
        if (categoryInput) {
            categoryInput.value = '';
            categoryInput.disabled = true;
        }
    }
}

/**
 * Инициализация синхронизации color picker и text input для настроек оформления
 */
function initColorPickers() {
    const colorPairs = [
        { picker: 'primary_color_picker', input: 'primary_color' },
        { picker: 'secondary_color_picker', input: 'secondary_color' },
        { picker: 'accent_color_picker', input: 'accent_color' }
    ];

    colorPairs.forEach(pair => {
        const picker = document.getElementById(pair.picker);
        const input = document.getElementById(pair.input);

        if (picker && input) {
            // Синхронизация от picker к input
            picker.addEventListener('input', function(e) {
                input.value = e.target.value;
            });

            // Обратная синхронизация от input к picker
            input.addEventListener('input', function(e) {
                picker.value = e.target.value;
            });
        }
    });
}

/**
 * Получение CSRF токена из формы на странице
 */
function getCsrfToken() {
    const hiddenInput = document.querySelector('input[name="csrf_token"]');
    return hiddenInput ? hiddenInput.value : null;
}