/**
 * Получить CSRF-токен из формы
 * @returns {string|null} CSRF-токен или null, если не найден
 */
function getCsrfToken() {
    const tokenInput = document.querySelector('input[name="csrf_token"]');
    if (!tokenInput || !tokenInput.value) {
        console.error('CSRF-токен не найден. Возможно, истекла сессия.');
        return null;
    }
    return tokenInput.value;
}

/**
 * Показать сообщение об ошибке
 * @param {string} message - Текст сообщения
 */
function showErrorMessage(message) {
    alert(message);
    console.error(message);
}

/**
 * Скрипт управления отзывами (админ-панель)
 * Волшебная ЛАВКА © 2025
 *
 * Этот файл содержит функции для администраторов и пользователей.
 * - initReviewHandlers() - инициализирует обработчики для админ-панели (только admin роль)
 * - initUserReviewHandlers() - инициализирует обработчики для пользовательской страницы review.php (user/moderator роли)
 */

// Инициализация только если это админ-страница (проверка по наличию модального окна editReviewModal)
document.addEventListener('DOMContentLoaded', function() {
    const adminModal = document.getElementById('editReviewModal');
    if (adminModal) {
        // Это админ-страница, запускаем админские обработчики
        initReviewHandlers();
    }
    // Для пользовательской страницы review.php используется отдельный вызов initUserReviewHandlers()
    // который подключается через footer.php только для не-admin пользователей
});

/**
 * Инициализация обработчиков отзывов
 */
function initReviewHandlers() {
    // Обработчик кнопки "Редактировать"
    const editButtons = document.querySelectorAll('[data-action="edit-review"]');
    editButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const reviewId = this.dataset.reviewId;
            const reviewRating = this.dataset.reviewRating;
            const reviewComment = this.dataset.reviewComment;
            showEditForm(reviewId, reviewRating, reviewComment);
        });
    });

    // Обработчик кнопки "Опубликовать"
    const publishButtons = document.querySelectorAll('[data-action="publish-review"]');
    publishButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const reviewId = this.dataset.reviewId;
            approveReview(reviewId);
        });
    });

    // Обработчик кнопки "Удалить"
    const deleteButtons = document.querySelectorAll('[data-action="delete-review"]');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const reviewId = this.dataset.reviewId;
            if (confirm('Удалить этот отзыв?')) {
                deleteReview(reviewId);
            }
        });
    });

    // Обработчик отправки формы редактирования
    const editForm = document.getElementById('editReviewForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitEditForm(this);
        });
    }

    // Закрытие формы редактирования
    const closeButtons = document.querySelectorAll('[data-modal-close="edit-review"]');
    closeButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            hideEditForm();
        });
    });
}

/**
 * Показать форму редактирования отзыва
 * @param {string} reviewId - ID отзыва
 * @param {number} rating - Оценка
 * @param {string} comment - Комментарий
 */
function showEditForm(reviewId, rating, comment) {
    const modal = document.getElementById('editReviewModal');
    if (modal) {
        modal.style.display = 'block';

        // Заполняем поля формы
        const reviewIdInput = document.getElementById('edit_review_id');
        const ratingInput = document.getElementById('edit_rating');
        const commentInput = document.getElementById('edit_comment');

        if (reviewIdInput) reviewIdInput.value = reviewId;
        if (ratingInput) ratingInput.value = rating;
        if (commentInput) commentInput.value = comment;
    }
}

/**
 * Скрыть форму редактирования отзыва
 */
function hideEditForm() {
    const modal = document.getElementById('editReviewModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

/**
 * Отправка формы редактирования отзыва
 * @param {HTMLFormElement} form - Форма редактирования
 */
function submitEditForm(form) {
    // Проверка CSRF-токена перед отправкой
    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        showErrorMessage('Ошибка безопасности: CSRF-токен не найден. Пожалуйста, обновите страницу и попробуйте снова.');
        return;
    }

    const formData = new FormData(form);

    fetch('/admin/manage_review.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        // Перезагружаем страницу для отображения изменений
        window.location.reload();
    })
    .catch(error => {
        console.error('Ошибка при обновлении отзыва:', error);
        alert('Ошибка при обновлении отзыва');
    });
}

/**
 * Публикация отзыва (одобрение)
 * @param {string} reviewId - ID отзыва
 */
function approveReview(reviewId) {
    // Проверка CSRF-токена перед отправкой
    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        showErrorMessage('Ошибка безопасности: CSRF-токен не найден. Пожалуйста, обновите страницу и попробуйте снова.');
        return;
    }

    const formData = new FormData();
    formData.append('approve_review', reviewId);
    formData.append('csrf_token', csrfToken);

    fetch('/admin/manage_review.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        // Перезагружаем страницу для отображения изменений
        window.location.reload();
    })
    .catch(error => {
        console.error('Ошибка при публикации отзыва:', error);
        alert('Ошибка при публикации отзыва');
    });
}

/**
 * Удаление отзыва
 * @param {string} reviewId - ID отзыва
 */
function deleteReview(reviewId) {
    // Проверка CSRF-токена перед отправкой
    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        showErrorMessage('Ошибка безопасности: CSRF-токен не найден. Пожалуйста, обновите страницу и попробуйте снова.');
        return;
    }

    const formData = new FormData();
    formData.append('delete_review', reviewId);
    formData.append('csrf_token', csrfToken);

    fetch('/admin/manage_review.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        // Перезагружаем страницу для отображения изменений
        window.location.reload();
    })
    .catch(error => {
        console.error('Ошибка при удалении отзыва:', error);
        alert('Ошибка при удалении отзыва');
    });
}

/**
 * Открытие модального окна редактирования
 * @param {string} reviewId - ID отзыва
 * @param {number} rating - Оценка
 * @param {string} comment - Комментарий
 */
function openEditReviewModal(reviewId, rating, comment) {
    showEditForm(reviewId, rating, comment);
}

/**
 * Закрытие модального окна редактирования
 */
function closeEditReviewModal() {
    hideEditForm();
}

/**
 * Инициализация обработчиков отзывов для пользовательской страницы review.php
 * Эта функция вызывается только для обычных пользователей и модераторов (не админов)
 * Использует те же базовые функции (getCsrfToken, showErrorMessage), что и админ-скрипт
 */
function initUserReviewHandlers() {
    // Проверка: не запускать, если уже инициализировано или если это админ-страница
    if (window.userReviewHandlersInitialized) {
        return;
    }
    window.userReviewHandlersInitialized = true;

    // Обработчик кнопки "Редактировать" на странице пользователя
    const userEditButtons = document.querySelectorAll('.btn-edit-review');

    if (userEditButtons.length > 0) {
        userEditButtons.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const reviewId = this.getAttribute('data-id');
                const card = this.closest('.user-review-card');
                const editForm = card.querySelector('.review-edit-form[data-review-id="' + reviewId + '"]');
                const displayContent = card.querySelector('.review-display-content');

                if (editForm && displayContent) {
                    // Скрываем отображение отзыва и показываем форму редактирования
                    displayContent.style.display = 'none';
                    editForm.classList.add('active');
                }
            });
        });
    }

    // Обработка кнопки отмены редактирования
    const cancelButtons = document.querySelectorAll('.btn-cancel-edit');

    if (cancelButtons.length > 0) {
        cancelButtons.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const form = this.closest('.review-edit-form');
                const card = form.closest('.user-review-card');
                const displayContent = card.querySelector('.review-display-content');

                if (form && displayContent) {
                    // Скрываем форму и показываем отображение отзыва
                    form.classList.remove('active');
                    displayContent.style.display = 'block';
                }
            });
        });
    }

    // Обработка отправки формы редактирования отзыва через AJAX
    const editForms = document.querySelectorAll('.review-edit-form');

    if (editForms.length > 0) {
        editForms.forEach(function(form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const reviewId = this.getAttribute('data-review-id');
                const card = this.closest('.user-review-card');
                const displayContent = card.querySelector('.review-display-content');

                // Добавляем action в URL
                const actionUrl = '/users/review.php?action=update_review';

                fetch(actionUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        // Обновляем отображение отзыва с новыми данными
                        const newRating = formData.get('rating');
                        const newComment = formData.get('comment');

                        // Обновляем рейтинг
                        const ratingDisplay = displayContent.querySelector('.review-rating-display');
                        let stars = '';
                        for (let i = 0; i < newRating; i++) {
                            stars += '⭐';
                        }
                        ratingDisplay.innerHTML = stars;

                        // Обновляем комментарий
                        const commentDisplay = displayContent.querySelector('.review-comment');
                        commentDisplay.textContent = newComment;

                        // Скрываем форму и показываем отображение
                        form.classList.remove('active');
                        displayContent.style.display = 'block';

                        // Устанавливаем статус "На модерации"
                        const statusDiv = displayContent.querySelector('.review-status');
                        statusDiv.className = 'review-status pending';
                        statusDiv.innerHTML = '⏳ На модерации';
                    } else {
                        alert(data.message || 'Ошибка обновления отзыва');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Произошла ошибка при обновлении отзыва');
                });
            });
        });
    }
}