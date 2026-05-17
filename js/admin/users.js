/**
 * Скрипт управления пользователями (модальные окна)
 * Волшебная ЛАВКА © 2025
 */

document.addEventListener('DOMContentLoaded', function() {
    initModalHandlers();
});

/**
 * Инициализация обработчиков модальных окон
 */
function initModalHandlers() {
    // Кнопки открытия модальных окон
    const openButtons = document.querySelectorAll('[data-modal-open]');
    openButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const modalId = this.dataset.modalOpen;
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'block';

                // Если нужно передать данные в модальное окно
                const userId = this.dataset.userId;
                const userName = this.dataset.userName;

                if (userId && document.getElementById('reset_user_id')) {
                    document.getElementById('reset_user_id').value = userId;
                }

                if (userName && document.getElementById('reset_username_display')) {
                    document.getElementById('reset_username_display').textContent = userName;
                }
            }
        });
    });

    // Кнопки закрытия модальных окон
    const closeButtons = document.querySelectorAll('[data-modal-close]');
    closeButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                modal.style.display = 'none';
            }
        });
    });

    // Закрытие по клику вне модального окна
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    });
}

/**
 * Открытие модального окна добавления модератора
 */
function openAddModeratorModal() {
    const modal = document.getElementById('addModeratorModal');
    if (modal) {
        modal.style.display = 'block';
    }
}

/**
 * Открытие модального окна сброса пароля
 * @param {string} userId - ID пользователя
 * @param {string} userName - Имя пользователя
 */
function openResetPasswordModal(userId, userName) {
    const modal = document.getElementById('resetPasswordModal');
    if (modal) {
        modal.style.display = 'block';

        const userIdInput = document.getElementById('reset_user_id');
        if (userIdInput) {
            userIdInput.value = userId;
        }

        const userNameDisplay = document.getElementById('reset_username_display');
        if (userNameDisplay && userName) {
            userNameDisplay.textContent = userName;
        }
    }
}

/**
 * Закрытие модального окна
 * @param {string} modalId - ID модального окна
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

/**
 * Закрытие всех модальных окон
 */
function closeAllModals() {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.style.display = 'none';
    });
}

/**
 * Инициализация валидации для страницы редактирования профиля администратора (edit_cab.php)
 * Вызывает глобальную функцию initEditProfileValidation из validation.js
 */
function initAdminEditProfileValidation() {
    if (typeof window.initEditProfileValidation === 'function') {
        window.initEditProfileValidation();
    }
}

// Автоматическая инициализация при загрузке DOM, если на странице есть форма редактирования профиля
document.addEventListener('DOMContentLoaded', function() {
    const editProfileForm = document.getElementById('editProfileForm');
    if (editProfileForm) {
        initAdminEditProfileValidation();
    }
});