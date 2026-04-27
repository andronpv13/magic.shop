<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Управление товарами "Волшебная ЛАВКА"
 * Разработчик: АВВА © 2025
 */

$page_title = 'Управление товарами';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions_adm.php';

requireAdmin();

// Инициализируем переменные только для POST-запросов
$success = '';
$error = '';

// Обработка формы добавления товара
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    // Проверка CSRF токена
    if (!csrf_verify()) {
        $error = 'Ошибка безопасности: истек срок действия сессии. Попробуйте обновить страницу.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);
        
        // Логика категорий
        $use_categories = isset($_POST['use_categories']) && $_POST['use_categories'] === '1';
        $category_id = null;

        if ($use_categories) {
            $category_name = trim($_POST['category_name'] ?? '');
            $category_id_input = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;

            // Если выбрали из списка
            if ($category_id_input > 0) {
                $category_id = $category_id_input;
            } 
            // Если ввели новое название
            elseif (!empty($category_name)) {
                $existing_cat = getCategoryByName($category_name);
                if ($existing_cat) {
                    $category_id = $existing_cat['id'];
                } else {
                    $new_cat_result = addCategory($category_name);
                    if ($new_cat_result['success']) {
                        $category_id = $new_cat_result['id'];
                    } else {
                        $error = $new_cat_result['message'];
                    }
                }
            }
        } else {
            // Если чекбокс выключен, сбрасываем категорию товара
            $category_id = null;
        }
        
        $is_new = isset($_POST['is_new']) ? 1 : 0;
        
        if (empty($name)) {
            $error = 'Укажите название товара';
        } elseif ($price <= 0) {
            $error = 'Укажите корректную цену';
        } else {
            // Загрузка изображения
            $image_path = '';
            
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_result = uploadProductImage($_FILES['image']);
                
                if ($upload_result['success']) {
                    $image_path = $upload_result['filename'];
                } else {
                    $error = $upload_result['message'];
                }
            }
            
            if (empty($error)) {
                $created_by = $_SESSION['user_id'];
                $result = addProduct($name, $description, $price, $category_id, $stock, $is_new, $image_path, $created_by);
                
                if ($result['success']) {
                    $success = 'Товар добавлен';
                } else {
                    $error = $result['message'];
                }
            }
        }
    }
}

// Обработка удаления товара
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $product_id = (int)$_GET['delete'];
    $result = deleteProduct($product_id);
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['message'];
    }
}

// Получаем список товаров
$products = getAllProducts();

// Получаем список категорий
$categories = getCategoriesList();

// Получаем текущее состояние чекбокса "Использовать категории" из настроек
$use_categories = isset($_SESSION['use_categories']) ? $_SESSION['use_categories'] : false;
?>

<section class="section">
    <div class="container">
        <nav class="breadcrumbs">
            <a href="/admin/index.php">Админ-панель</a>
            <span class="separator">/</span>
            <span class="current">Товары</span>
        </nav>

        <h1 class="page-title">Управление товарами</h1>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo e($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo e($error); ?></div>
        <?php endif; ?>

        <!-- Панель управления категориями -->
        <div class="category-control-panel">
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="use_categories" name="use_categories" value="1" 
                           onchange="toggleCategories(this)" <?php echo $use_categories ? 'checked' : ''; ?>>
                    Использовать категории
                </label>
            </div>
            
            <button type="button" class="btn btn-primary" onclick="openCategoriesModal()">
                Редактировать категории
            </button>
            
            <a href="/admin/add_product.php" class="btn btn-primary">
                Добавить товар
            </a>
        </div>

        <!-- Список товаров -->
        <div class="products-list">
            <h2>Список товаров</h2>
            
            <?php if (!empty($products)): ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Изображение</th>
                                <th>Название</th>
                                <th>Категория</th>
                                <th>Цена</th>
                                <th>Остаток</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <?php if ($product['image']): ?>
                                            <div class="table-image">
                                                <img src="<?php echo getProductImage($product['image']); ?>" alt="<?php echo e($product['name']); ?>" style="width: 50px; height: 50px; object-fit: cover;">
                                            </div>
                                        <?php else: ?>
                                            <div class="table-image-placeholder">🎁</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="/admin/edit_product.php?id=<?php echo $product['id']; ?>">
                                            <?php echo e($product['name']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if (!empty($product['category_name'])): ?>
                                            <?php echo e($product['category_name']); ?>
                                        <?php else: ?>
                                            Без категории
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo number_format($product['price'], 0, ',', ' '); ?> ₽</td>
                                    <td><?php echo $product['stock']; ?></td>
                                    <td>
                                        <?php if ($product['is_new']): ?>
                                            <span class="badge badge-new">NEW</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="/admin/edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-edit">
                                                Редактировать
                                            </a>
                                            <a href="/admin/products.php?delete=<?php echo $product['id']; ?>" class="btn btn-sm btn-delete"
                                               onclick="return confirm('Вы уверены, что хотите удалить этот товар?');">
                                                Удалить
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="empty-state">Товары не найдены</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Модальное окно для управления категориями -->
<div id="categories-modal" class="modal">
    <div class="modal-content">
        <h2>Управление категориями</h2>
        
        <div class="add-category-form">
            <h3>Добавить новую категорию</h3>
            <form id="new-category-form">
                <div class="form-group">
                    <label for="new-category-name">Название категории:</label>
                    <input type="text" id="new-category-name" name="name" required>
                </div>
                <button type="submit" class="btn btn-primary">Добавить</button>
            </form>
        </div>
        
        <div class="categories-list">
            <h3>Существующие категории</h3>
            <ul id="categories-list">
                <?php foreach ($categories as $cat): ?>
                    <li data-id="<?php echo $cat['id']; ?>">
                        <span class="category-name"><?php echo e($cat['name']); ?></span>
                        <button class="btn btn-sm btn-delete delete-category" data-id="<?php echo $cat['id']; ?>">
                            Удалить
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div class="modal-actions">
            <button type="button" class="btn btn-outline" onclick="closeCategoriesModal()">Закрыть</button>
        </div>
    </div>
</div>

<script>
    // При загрузке страницы
    document.addEventListener('DOMContentLoaded', function() {
        const checkbox = document.getElementById('use_categories');
        if (checkbox) {
            const localState = localStorage.getItem('use_categories');
            if (localState !== null) {
                checkbox.checked = localState === 'true';
            }
        }
    });

    // Функция для переключения использования категорий
    function toggleCategories(checkbox) {
        console.log('Начинаем переключение категорий');
        
        const formData = new FormData();
        formData.append('use_categories', checkbox.checked ? '1' : '0');
        formData.append('csrf_token', '<?php echo csrf_token(); ?>');
        
        const url = '/admin/update_settings.php?ajax=1';
        console.log('URL запроса:', url);
        console.log('Данные формы:', Object.fromEntries(formData));
        
        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Сырой ответ сервера:', response);
            console.log('Статус ответа:', response.status);
            
            // Проверяем тип контента
            const contentType = response.headers.get('Content-Type');
            if (contentType && contentType.includes('application/json')) {
                return response.json();
            } else {
                // Если сервер вернул HTML (ошибка PHP), пытаем вытащить текст
                return response.text().then(text => {
                    console.error('Сервер вернул HTML вместо JSON:', text);
                    return { success: false, message: 'Внутренняя ошибка сервера' };
                });
            }
        })
        .then(data => {
            console.log('Распарсированные данные:', data);
            
            if (data.success) {
                // Сохраняем в локальное хранилище
                localStorage.setItem('use_categories', checkbox.checked ? 'true' : 'false');
                
                // Визуальное подтверждение (зеленая рамка на 2 сек)
                const checkboxElement = document.getElementById('use_categories');
                if (checkboxElement) {
                    checkboxElement.style.borderColor = '#4caf50';
                    setTimeout(() => {
                        checkboxElement.style.borderColor = '';
                    }, 2000);
                }
            } else {
                console.error('Ошибка при обновлении настроек:', data.message);
                alert('Ошибка при обновлении настроек: ' + (data.message || 'Неизвестная ошибка'));
            }
        })
        .catch(error => {
            console.error('Ошибка сети:', error);
            alert('Ошибка при отправке запроса');
        });
    }
    
    // Функции для работы с модальным окном категорий
    function openCategoriesModal() {
        document.getElementById('categories-modal').style.display = 'flex';
    }
    
    function closeCategoriesModal() {
        document.getElementById('categories-modal').style.display = 'none';
    }
    
    // Обработка формы добавления новой категории
    document.getElementById('new-category-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const categoryName = document.getElementById('new-category-name').value.trim();
        
        if (!categoryName) {
            alert('Пожалуйста, введите название категории');
            return;
        }
        
        const formData = new FormData();
        formData.append('name', categoryName);
        formData.append('csrf_token', '<?php echo csrf_token(); ?>');
        
        fetch('/admin/add_category.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Ответ сервера (add):', response);
            const contentType = response.headers.get('Content-Type');
            if (contentType && contentType.includes('application/json')) {
                return response.json();
            } else {
                return response.text().then(text => {
                    console.error('Сервер вернул HTML:', text);
                    return { success: false, message: 'Внутренняя ошибка сервера' };
                });
            }
        })
        .then(data => {
            if (data.success) {
                // Добавляем новую категорию в список
                const categoriesList = document.getElementById('categories-list');
                const newCategory = document.createElement('li');
                newCategory.dataset.id = data.id;
                newCategory.innerHTML = `
                    <span class="category-name">${categoryName}</span>
                    <button class="btn btn-sm btn-delete delete-category" data-id="${data.id}">
                        Удалить
                    </button>
                `;
                categoriesList.appendChild(newCategory);
                
                // Очищаем поле ввода
                document.getElementById('new-category-name').value = '';
            } else {
                alert('Ошибка при добавлении категории: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Ошибка сети:', error);
            alert('Ошибка при добавлении категории');
        });
    });
    
    // Обработка удаления категории
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-category')) {
            const categoryId = e.target.dataset.id;
            
            if (!confirm('Вы уверены, что хотите удалить эту категорию?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('category_id', categoryId);
            formData.append('csrf_token', '<?php echo csrf_token(); ?>');
            
            fetch('/admin/delete_category.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Ответ сервера (del):', response);
                const contentType = response.headers.get('Content-Type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json();
                } else {
                    return response.text().then(text => {
                        console.error('Сервер вернул HTML:', text);
                        return { success: false, message: 'Внутренняя ошибка сервера' };
                    });
                }
            })
            .then(data => {
                if (data.success) {
                    // Удаляем категорию из списка
                    const categoryItem = e.target.closest('li');
                    categoryItem.remove();
                } else {
                    alert('Ошибка при удалении категории: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Ошибка сети:', error);
                alert('Ошибка при удалении категории');
            });
        }
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
