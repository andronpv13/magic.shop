<?php
/**
 * Управление отзывами "Волшебная ЛАВКА"
 * Разработчик: АВВА © 2025
 */

$page_title = 'Отзывы - Админ-панель';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions_adm.php';

requireAdmin();

// Обработка действий с отзывами
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверка CSRF
    if (!csrf_verify()) {
        $message = 'Ошибка безопасности: истек срок действия сессии.';
    } else {
        // Публикация отзыва
        if (isset($_POST['approve_review'])) {
            $review_id = (int)$_POST['approve_review'];
            $result = approveReview($review_id);
            if ($result['success']) {
                header('Location: manage_review.php?message=' . urlencode('Отзыв опубликован'));
                exit;
            } else {
                $message = 'Ошибка при публикации отзыва';
            }
        }
        // Обновление отзыва
        elseif (isset($_POST['update_review'])) {
            $review_id = (int)$_POST['review_id'];
            $rating = (int)$_POST['rating'];
            $comment = trim($_POST['comment']);

            if ($rating < 1 || $rating > 5) {
                $message = 'Оценка должна быть от 1 до 5';
            } elseif (empty($comment)) {
                $message = 'Комментарий не может быть пустым';
            } else {
                $result = updateReview($review_id, $rating, $comment);
                if ($result['success']) {
                    header('Location: manage_review.php?message=' . urlencode('Отзыв обновлён'));
                    exit;
                } else {
                    $message = 'Ошибка при обновлении отзыва';
                }
            }
        }
        // Удаление отзыва
        elseif (isset($_POST['delete_review'])) {
            $review_id = (int)$_POST['delete_review'];
            $result = deleteReview($review_id);
            if ($result['success']) {
                header('Location: manage_review.php?message=' . urlencode('Отзыв удалён'));
                exit;
            } else {
                $message = 'Ошибка при удалении отзыва';
            }
        }
    }
}

$reviews = getAllReviews();
$message = $_GET['message'] ?? $message ?? '';
?>

<section class="section">
    <div class="container">
        <h1 class="page-title">Управление отзывами</h1>

        <?php if ($message): ?>
            <div class="alert <?php echo strpos($message, 'Ошибка') !== false ? 'alert-error' : 'alert-success'; ?>">
                <?php echo e($message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($reviews)): ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Автор</th>
                            <th>Товар</th>
                            <th>Оценка</th>
                            <th>Комментарий</th>
                            <th>Дата</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reviews as $review): ?>
                            <tr>
                                <td data-label="ID"><?php echo $review['id']; ?></td>
                                <td data-label="Автор"><?php echo e($review['username']); ?></td>
                                <td data-label="Товар">
                                    <a href="/shop.php?product_id=<?php echo $review['product_id']; ?>">
                                        <?php echo e($review['product_name']); ?>
                                    </a>
                                </td>
                                <td data-label="Оценка">
                                    <span class="review-stars">
                                        <?php echo str_repeat('⭐', $review['rating']); ?>
                                    </span>
                                </td>
                                <td data-label="Комментарий" class="review-comment-cell">
                                    <?php echo e($review['comment']); ?>
                                </td>
                                <td data-label="Дата"><?php echo date('d.m.Y', strtotime($review['created_at'])); ?></td>
                                <td data-label="Статус">
                                    <?php if ($review['is_approved']): ?>
                                        <span class="status-badge status-published">✅ Опубликовано</span>
                                    <?php else: ?>
                                        <span class="status-badge status-pending">⏳ На модерации</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Действия" class="action-buttons">
                                    <!-- Кнопка редактировать -->
                                    <button type="button"
                                            class="btn btn-sm btn-edit"
                                            data-action="edit-review"
                                            data-review-id="<?php echo $review['id']; ?>"
                                            data-review-rating="<?php echo $review['rating']; ?>"
                                            data-review-comment="<?php echo e($review['comment']); ?>"
                                            title="Редактировать">
                                        ✏️
                                    </button>

                                    <!-- Кнопка опубликовать -->
                                    <?php if (!$review['is_approved']): ?>
                                        <button type="button"
                                                class="btn btn-sm btn-publish"
                                                data-action="publish-review"
                                                data-review-id="<?php echo $review['id']; ?>"
                                                title="Опубликовать">
                                            ✅
                                        </button>
                                    <?php endif; ?>

                                    <!-- Кнопка удалить -->
                                    <button type="button"
                                            class="btn btn-sm btn-delete"
                                            data-action="delete-review"
                                            data-review-id="<?php echo $review['id']; ?>"
                                            title="Удалить">
                                        🗑️
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="empty-state">Отзывов пока нет</p>
        <?php endif; ?>

        <!-- Модальное окно редактирования отзыва -->
        <div id="editReviewModal" class="modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Редактирование отзыва</h2>
                </div>
                <form method="POST" id="editReviewForm">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="update_review" value="1">
                    <input type="hidden" name="review_id" id="edit_review_id">

                    <div align="right" class="form-group">
                        <label for="edit_rating">Оценка:</label>
                        <select name="rating" id="edit_rating" required>
                            <option value="1">1 ⭐</option>
                            <option value="2">2 ⭐⭐</option>
                            <option value="3">3 ⭐⭐⭐</option>
                            <option value="4">4 ⭐⭐⭐⭐</option>
                            <option value="5">5 ⭐⭐⭐⭐⭐</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit_comment">Отзыв:</label>
                        <textarea width=100% name="comment" id="edit_comment" rows="3" required></textarea>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                        <button type="button" class="btn btn-secondary" data-modal-close="edit-review">Отмена</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Подключение скрипта управления отзывами -->
<script src="/js/admin/review.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>