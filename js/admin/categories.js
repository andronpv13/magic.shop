/**
 * Скрипт управления категориями товаров
 * Волшебная ЛАВКА © 2025
 */

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('new-category-form');
    if (form) {
        form.addEventListener('submit', handleAddCategory);
    }

    document.addEventListener('click', handleDeleteCategory);
});

/**
 * Обработчик добавления категории
 */
function handleAddCategory(e) {
    e.preventDefault();

    const categoryNameInput = document.getElementById('new-category-name');
    const categoryName = categoryNameInput ? categoryNameInput.value.trim() : '';

    if (!categoryName) {
        alert('Пожалуйста, введите название категории');
        return;
    }

    // Получаем CSRF токен из скрытого поля формы
    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        alert('Ошибка безопасности: CSRF токен не найден');
        return;
    }

    const formData = new FormData();
    formData.append('name', categoryName);
    formData.append('csrf_token', csrfToken);

    fetch('/admin/manage_category.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            addCategoryToList(categoryName);
            if (categoryNameInput) {
                categoryNameInput.value = '';
            }
        } else {
            alert('Ошибка при добавлении категории: ' + (data.message || 'Неизвестная ошибка'));
        }
    })
    .catch(() => alert('Ошибка при добавлении категории'));
}

/**
 * Добавление категории в список DOM
 */
function addCategoryToList(categoryName) {
    let list = document.getElementById('categories-list');
    if (!list) {
        const wrapper = document.querySelector('.categories-list-block');
        if (wrapper) {
            list = document.createElement('ul');
            list.id = 'categories-list';
            list.className = 'categories-list';
            wrapper.appendChild(list);
        }
    }

    if (!list) return;

    const emptyState = document.getElementById('empty-state-text');
    if (emptyState) {
        emptyState.remove();
    }

    const item = document.createElement('li');
    item.dataset.category = categoryName;
    item.innerHTML = `
        <span class="category-name">${escapeHtml(categoryName)}</span>
        <span class="category-count">(0)</span>
        <button type="button" class="btn btn-sm btn-delete delete-category" data-category="${escapeHtml(categoryName)}">Удалить</button>
    `;
    list.appendChild(item);
}

/**
 * Обработчик удаления категории
 */
function handleDeleteCategory(e) {
    if (!e.target.classList.contains('delete-category')) return;

    const category = e.target.dataset.category;
    if (!category || !confirm('Вы уверены, что хотите удалить эту категорию?')) {
        return;
    }

    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        alert('Ошибка безопасности: CSRF токен не найден');
        return;
    }

    const formData = new FormData();
    formData.append('category', category);
    formData.append('csrf_token', csrfToken);

    fetch('/admin/manage_category.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const li = e.target.closest('li');
            if (li) li.remove();

            const list = document.getElementById('categories-list');
            if (list && list.children.length === 0) {
                const emptyState = document.createElement('p');
                emptyState.className = 'empty-state';
                emptyState.id = 'empty-state-text';
                emptyState.textContent = 'Категории не найдены';
                if (list.parentNode) {
                    list.parentNode.insertBefore(emptyState, list);
                }
            }
        } else {
            alert('Ошибка при удалении категории: ' + (data.message || 'Неизвестная ошибка'));
        }
    })
    .catch(() => alert('Ошибка при удалении категории'));
}

/**
 * Получение CSRF токена из формы на странице
 */
function getCsrfToken() {
    const hiddenInput = document.querySelector('input[name="csrf_token"]');
    return hiddenInput ? hiddenInput.value : null;
}

/**
 * Экранирование HTML для безопасности
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}