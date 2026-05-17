/**
 * Скрипт управления отзывами (админ-панель)
 * Волшебная ЛАВКА © 2025
 */

document.addEventListener('DOMContentLoaded', function() {
    initReviewHandlers();
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
    const formData = new FormData();
    formData.append('approve_review', reviewId);
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]')?.value || '');

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
    const formData = new FormData();
    formData.append('delete_review', reviewId);
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]')?.value || '');

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