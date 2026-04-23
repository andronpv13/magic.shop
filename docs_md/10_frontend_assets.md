# 10 — Фронтенд ресурсы

## CSS

**Файл:** `css/style.css`

### CSS Variables

```css
:root {
    /* Цвета */
    --primary: #3498db;
    --primary-dark: #2980b9;
    --secondary: #2c3e50;
    --accent: #e74c3c;
    --success: #27ae60;
    --warning: #f39c12;
    --danger: #e74c3c;
    --light: #ecf0f1;
    --dark: #2c3e50;
    --white: #ffffff;
    --gray: #95a5a6;
    --gray-light: #bdc3c7;
    
    /* Отступы */
    --spacing-xs: 5px;
    --spacing-sm: 10px;
    --spacing-md: 20px;
    --spacing-lg: 40px;
    --spacing-xl: 60px;
    
    /* Типографика */
    --font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    --font-size-base: 16px;
    --font-size-sm: 14px;
    --font-size-lg: 20px;
    --font-size-xl: 32px;
    
    /* Радиусы */
    --radius-sm: 4px;
    --radius-md: 8px;
    --radius-lg: 12px;
    --radius-xl: 20px;
    
    /* Тени */
    --shadow-sm: 0 2px 4px rgba(0,0,0,0.1);
    --shadow-md: 0 4px 8px rgba(0,0,0,0.15);
    --shadow-lg: 0 8px 16px rgba(0,0,0,0.2);
}
```

### Основные компоненты

#### Site Header

```css
.site-header {
    position: sticky;
    top: 0;
    z-index: 1000;
    background: var(--white);
    box-shadow: var(--shadow-md);
    padding: var(--spacing-sm) var(--spacing-md);
}
```

**Элементы:**

- `.logo` — логотип "Волшебная ЛАВКА"
- `.nav` — навигационное меню
- `.cart-count` — бейдж количества товаров в корзине
- `.user-menu` — выпающее меню пользователя

#### Кнопки

```css
.btn {
    display: inline-block;
    padding: var(--spacing-sm) var(--spacing-md);
    border: none;
    border-radius: var(--radius-md);
    cursor: pointer;
    font-size: var(--font-size-base);
    transition: all 0.3s ease;
}

.btn-primary { background: var(--primary); color: var(--white); }
.btn-primary:hover { background: var(--primary-dark); }

.btn-success { background: var(--success); color: var(--white); }
.btn-danger { background: var(--danger); color: var(--white); }
.btn-warning { background: var(--warning); color: var(--white); }
```

#### Формы

```css
.form-group {
    margin-bottom: var(--spacing-md);
}

.form-group label {
    display: block;
    margin-bottom: var(--spacing-xs);
    font-weight: 600;
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: var(--spacing-sm);
    border: 1px solid var(--gray-light);
    border-radius: var(--radius-sm);
    font-size: var(--font-size-base);
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
}
```

#### Оповещения

```css
.alert {
    padding: var(--spacing-md);
    border-radius: var(--radius-md);
    margin-bottom: var(--spacing-md);
}

.alert-success { background: #d4edda; color: #155724; }
.alert-danger { background: #f8d7da; color: #721c24; }
.alert-warning { background: #fff3cd; color: #856404; }
```

#### Hero-секция

```css
.hero {
    background: url('../images/fon/fon.jpg') center/cover no-repeat;
    padding: var(--spacing-xl) var(--spacing-md);
    text-align: center;
    color: var(--white);
}

.hero h1 {
    font-size: var(--font-size-xl);
    margin-bottom: var(--spacing-md);
}
```

#### Сетка товаров

```css
.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: var(--spacing-md);
    padding: var(--spacing-md);
}

.product-card {
    background: var(--white);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}

.product-card img {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.product-card .product-info {
    padding: var(--spacing-md);
}

.product-card .product-title {
    font-size: var(--font-size-lg);
    margin-bottom: var(--spacing-sm);
}

.product-card .product-price {
    font-size: var(--font-size-lg);
    font-weight: 700;
    color: var(--primary);
}
```

#### Badge статусов

```css
.badge {
    display: inline-block;
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--radius-xl);
    font-size: var(--font-size-sm);
    font-weight: 600;
}

.badge-pending { background: var(--warning); color: var(--white); }
.badge-payment { background: var(--primary); color: var(--white); }
.badge-completed { background: var(--success); color: var(--white); }
.badge-cancelled { background: var(--danger); color: var(--white); }
```

#### Таблицы

```css
.table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: var(--spacing-md);
}

.table th,
.table td {
    padding: var(--spacing-sm);
    border: 1px solid var(--gray-light);
    text-align: left;
}

.table th {
    background: var(--light);
    font-weight: 600;
}

.table tr:hover {
    background: #f5f5f5;
}
```

#### Карточки статистики (дашборды)

```css
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-md);
    padding: var(--spacing-md);
}

.stat-card {
    background: var(--white);
    border-radius: var(--radius-lg);
    padding: var(--spacing-md);
    text-align: center;
    box-shadow: var(--shadow-sm);
}

.stat-card .stat-value {
    font-size: var(--font-size-xl);
    font-weight: 700;
    color: var(--primary);
}

.stat-card .stat-label {
    color: var(--gray);
    font-size: var(--font-size-sm);
}
```

### Адаптивный дизайн

```css
@media (max-width: 768px) {
    .site-header {
        flex-direction: column;
    }
    
    .nav {
        flex-direction: column;
    }
    
    .products-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .table {
        font-size: var(--font-size-sm);
    }
}
```

### Print Styles

```css
@media print {
    .site-header,
    .site-footer,
    .btn,
    .no-print {
        display: none !important;
    }
    
    body {
        background: white;
        color: black;
    }
    
    .table th,
    .table td {
        border: 1px solid #000;
    }
}
```

## JavaScript

**Файл:** `js/script.js`

### Инициализация корзины

```javascript
document.addEventListener('DOMContentLoaded', function() {
    initCart();
    initAlerts();
    initFormValidation();
});
```

### `initCart()`

Навешивает обработчики на все кнопки "В корзину":

```javascript
function initCart() {
    document.addEventListener('click', function(e) {
        const button = e.target.closest('.add-to-cart');
        if (!button) return;
        
        const productId = button.dataset.productId;
        const quantity = 1;
        
        addToCart(productId, quantity, button);
    });
}
```

### `addToCart(productId, quantity, button)`

AJAX-запрос к `/cart/add_to_cart.php`:

```javascript
function addToCart(productId, quantity, button) {
    fetch('/cart/add_to_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            updateCartCount(data.cart_count);
            showNotification(data.message, 'success');
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(err => showNotification('Ошибка при добавлении', 'error'));
}
```

### `updateCartCount(count)`

Обновление бейджа с CSS-анимацией:

```javascript
function updateCartCount(count) {
    const badge = document.querySelector('.cart-count');
    if (badge) {
        badge.textContent = count;
        badge.classList.add('animate');
        setTimeout(() => badge.classList.remove('animate'), 300);
    }
}
```

**CSS анимация:**

```css
.cart-count.animate {
    animation: scale 0.3s ease;
}

@keyframes scale {
    0% { transform: scale(1); }
    50% { transform: scale(1.3); }
    100% { transform: scale(1); }
}
```

### `showNotification(message, type)`

Toast-уведомление с авто-скрытием:

```javascript
function showNotification(message, type = 'success') {
    // Инъекция CSS keyframes
    if (!document.querySelector('#notification-styles')) {
        const style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    }
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        border-radius: 8px;
        background: ${type === 'success' ? '#27ae60' : '#e74c3c'};
        color: white;
        z-index: 9999;
        animation: slideIn 0.3s ease;
    `;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}
```

### `initAlerts()`

Авто-скрытие `.alert` через 5 секунд:

```javascript
function initAlerts() {
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
}
```

### `initFormValidation()`

Валидация обязательных полей:

```javascript
function initFormValidation() {
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });
}

function validateForm(form) {
    let valid = true;
    form.querySelectorAll('[required]').forEach(input => {
        if (!input.value.trim()) {
            valid = false;
            input.style.borderColor = 'red';
        } else {
            input.style.borderColor = '';
        }
    });
    return valid;
}
```

### `confirmDelete()`

Подтверждение удаления:

```javascript
function confirmDelete(message = 'Вы уверены, что хотите удалить этот элемент?') {
    return confirm(message);
}
```

**Использование:**

```html
<a href="delete.php?id=5" onclick="return confirmDelete()">Удалить</a>
```

### Обработка корзины на странице cart.php

```javascript
// Обновление количества товара
document.querySelectorAll('.cart-quantity').forEach(input => {
    input.addEventListener('change', function() {
        const productId = this.dataset.productId;
        const quantity = parseInt(this.value);
        
        fetch('/cart/update_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `product_id=${productId}&quantity=${quantity}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                updateRowTotal(productId, data.item_total);
                updateCartTotal(data.cart_total);
                updateCartCount(data.cart_count);
            }
        });
    });
});

// Удаление товара из корзины
document.querySelectorAll('.remove-from-cart').forEach(btn => {
    btn.addEventListener('click', function() {
        const productId = this.dataset.productId;
        
        fetch('/cart/remove_from_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `product_id=${productId}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                removeRow(productId);
                updateCartTotal(data.cart_total);
                updateCartCount(data.cart_count);
                
                if (data.cart_count === 0) {
                    location.reload();
                }
            }
        });
    });
});
```

### Обработка оплаты на pay.php

```javascript
function processPayment(method) {
    const methods = {
        'card': 'Банковская карта',
        'sbp': 'СБП',
        'cash': 'Наличные'
    };
    
    alert(`Оплата через ${methods[method]} прошла успешно!`);
    window.location.href = '/users/profile.php';
}
```

## Изображения

### Фоновые изображения

**Расположение:** `images/fon/`

| Файл | Использование |
|---|---|
| fon.jpg | Фон hero-секции и body |

### Изображения товаров

**Расположение:** `images/product/`

- Создаётся динамически при загрузке через `uploadProductImage()`
- Формат имени: `uniqid()_original_name.ext`
- Поддерживаемые форматы: JPG, PNG, GIF, WebP
- Максимальный размер: 5MB

**Отображение:**

```html
<img src="/images/product/64a1b2c3d4e5f_product.jpg" alt="Название товара">
```

## Подключение ресурсов

### Публичные страницы (index.php, shop.php, checkout.php, pay.php)

```html
<head>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <!-- контент -->
    <?php include 'includes/footer.php'; ?>
    <script src="/js/script.js"></script>
</body>
```

### Страница входа (login.php)

```html
<head>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <!-- форма без header/footer -->
</body>
```

### Панели админа/модератора

```html
<head>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body class="admin-body / moderator-body">
    <!-- контент панели -->
    <script src="/js/script.js"></script>
</body>
```

### Страницы пользователя (users/)

```html
<head>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <!-- контент -->
    <?php include '../includes/footer.php'; ?>
    <script src="/js/script.js"></script>
</body>
```
