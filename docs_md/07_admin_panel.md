# 07 — Панель администратора

## Общие сведения

**Расположение:** `admin/`  
**Тема оформления:** Тёмная тема (#2c3e50)  
**Требование:** `requireAdmin()` — доступ только для роли `admin`

**Подключение ядра (каждая страница):**

```php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/functions_adm.php';
requireAdmin();
```

## Дашборд

**Файл:** `admin/index.php`

### Статистика (6 карточек)

| Карточка | Данные | Функция |
|---|---|---|
| Товары | COUNT(*) FROM products | `getAdminStats()['total_products']` |
| Заказы | COUNT(*) FROM orders | `getAdminStats()['total_orders']` |
| Покупатели | COUNT(*) WHERE role='customer' | `getAdminStats()['total_customers']` |
| Модераторы | COUNT(*) WHERE role='moderator' | `getAdminStats()['total_moderators']` |
| Выручка | SUM(total) WHERE status='completed' | `getAdminStats()['total_revenue']` |
| Ожидают | COUNT(*) WHERE status='pending' | `getAdminStats()['pending_orders']` |

### Быстрые ссылки

- "Добавить товар" → `add_product.php`
- "Все товары" → `products.php`
- "Управление заказами" → `manage_orders.php`
- "Управление пользователями" → `manage_users.php`
- "Управление отзывами" → `manage_review.php`
- "Личный кабинет" → `cab.php`

## Управление товарами

### Список всех товаров

**Файл:** `admin/products.php`

**Данные:** `getAllProductsWithSeller()` — все товары с информацией о продавце

**Таблица:**

| Колонка | Описание |
|---|---|
| ID | productId |
| Изображение | thumbnail из `images/product/` |
| Название | name |
| Цена | price |
| Продавец | username создателя (created_by → users.username) |
| Дата | created_at |
| Действия | Редактировать, Удалить |

**Действия:**

#### Удаление товара

```php
if (isset($_GET['delete'])) {
    deleteProduct($_GET['delete']);
    header('Location: products.php');
}
```

**Функция:** `deleteProduct($productId)` — DELETE с CASCADE (order_items, reviews)

### Добавление товара

**Файл:** `admin/add_product.php`

**Обработка POST:**

1. Получение данных формы: `name`, `description`, `price`
2. Загрузка изображения: `uploadProductImage($_FILES['image'])`
3. Вызов `addProductAdmin($name, $description, $price, $image_path)`
4. Редирект на `products.php`

**Форма:**

| Поле | Тип | Требование |
|---|---|---|
| Название | text | required |
| Описание | textarea | required |
| Цена | number (step=0.01) | required |
| Изображение | file (jpg/png/gif/webp) | optional |

**Функция загрузки:** `uploadProductImage($file)`

- Проверка расширения
- Проверка размера (макс. 5MB)
- Проверка `getimagesize()`
- Генерация имени: `uniqid() . '_' . original_name`
- Сохранение в `images/product/`

### Редактирование товара

**Файл:** `admin/edit_product.php`

**Параметр:** GET `?id=` — productId

**GET-режим (отображение):**

1. `getProductWithSeller($productId)` — товар с продавцом
2. Заполнение формы текущими данными

**POST-режим (обновление):**

1. Получение данных формы
2. Если загружено новое изображение — `uploadProductImage()`
3. `updateProduct($productId, $name, $description, $price, $new_image)`
4. Редирект на `products.php`

**Функция:** `updateProduct($productId, $name, $description, $price, $image = null)`

- Если `$image === null` — старое изображение сохраняется

## Управление заказами

### Список заказов

**Файл:** `admin/manage_orders.php`

**Данные:** `getAllOrders($status)` — все заказы с фильтрацией

**Фильтр по статусу:**

```php
$status = isset($_GET['status']) ? $_GET['status'] : null;
$orders = getAllOrders($status);
```

**Таблица:**

| Колонка | Описание |
|---|---|
| ID | id_or |
| Покупатель | username + email |
| Сумма | total |
| Статус | badge с цветом (pending/payment/completed/cancelled) |
| Дата | created_at |
| Действия | "Просмотр" → order_details.php |

**Ссылки фильтра:**

- Все заказы
- Ожидают (pending)
- В оплате (payment)
- Оплачено (completed)
- Отменены (cancelled)

### Детали заказа

**Файл:** `admin/order_details.php`

**Параметр:** GET `?id=` — order_id

**Отображение:**

1. `getOrder($order_id)` — информация о заказе
2. `getOrderItems($order_id)` — позиции с товарами
3. Данные покупателя (из users)

**Таблица позиций:**

| Колонка | Описание |
|---|---|
| Изображение | product image |
| Название | product name |
| Количество | quantity |
| Цена | price (на момент покупки) |
| Сумма | quantity × price |

**Смена статуса:**

```php
if (isset($_POST['update_status'])) {
    updateOrderStatus($order_id, $_POST['status']);
    header("Location: order_details.php?id=$order_id");
}
```

**Форма:**

```html
<form method="POST">
    <select name="status">
        <option value="pending">Ожидает</option>
        <option value="payment">В оплате</option>
        <option value="completed">Оплачено</option>
        <option value="cancelled">Отменён</option>
    </select>
    <button type="submit" name="update_status">Обновить статус</button>
</form>
```

**Функция:** `updateOrderStatus($order_id, $status)` — UPDATE orders SET status

## Управление пользователями

**Файл:** `admin/manage_users.php`

### Таблица пользователей

**Данные:** `getAllCustomers()` — все пользователи кроме admin

**Таблица:**

| Колонка | Описание |
|---|---|
| ID | id |
| Username | username |
| Email | email |
| Имя | first_name + last_name |
| Роль | badge (customer/moderator) |
| Дата регистрации | created_at |
| Действия | Сброс пароля, Удалить |

### Добавление модератора

**Модальная форма:**

| Поле | Тип | Требование |
|---|---|---|
| Username | text | required, unique |
| Email | email | required, unique |
| Пароль | password | required |
| Имя | text | required |
| Фамилия | text | required |

**Обработка POST:**

```php
if (isset($_POST['add_moderator'])) {
    addModerator(
        $_POST['username'], $_POST['email'], $_POST['password'],
        $_POST['first_name'], $_POST['last_name']
    );
    header('Location: manage_users.php');
}
```

**Функция:** `addModerator(...)` — INSERT в users с role='moderator'

### Сброс пароля

**Модальная форма (для каждого пользователя):**

| Поле | Тип |
|---|---|
| Новый пароль | password |
| Подтверждение | password |

**Обработка POST:**

```php
if (isset($_POST['reset_password'])) {
    resetUserPassword($_POST['user_id'], $_POST['new_password']);
    header('Location: manage_users.php');
}
```

**Функция:** `resetUserPassword($user_id, $new_password)` — bcrypt + UPDATE

### Удаление пользователя

```php
if (isset($_GET['delete'])) {
    deleteUser($_GET['delete']);
    header('Location: manage_users.php');
}
```

**Функция:** `deleteUser($user_id)`

- **Защита:** если роль = 'admin' — возврат false (удаление запрещено)
- CASCADE удалит связанные заказы и отзывы

## Управление отзывами

**Файл:** `admin/manage_review.php`

### Таблица отзывов

**Данные:** `getAllReviews()` — все отзывы с информацией о товаре и авторе

**Таблица:**

| Колонка | Описание |
|---|---|
| ID | id_rev |
| Товар | product_name (ссылка на shop.php?id=) |
| Автор | username |
| Текст | comment (обрезанный) |
| Дата | created_at |
| Действия | "Удалить" |

### Удаление отзыва

```php
if (isset($_GET['delete'])) {
    deleteReview($_GET['delete']);
    header('Location: manage_review.php');
}
```

**Функция:** `deleteReview($review_id)` — DELETE из reviews

## Выход из системы

**Файл:** `admin/logout.php`

```php
session_unset();
session_destroy();
header('Location: /login.php');
exit;
```

## Сводная таблица функций администратора

**Файл:** `includes/functions_adm.php`

| Функция | Назначение |
|---|---|
| `getAllCustomers()` | Все пользователи кроме admin |
| `getUserById($user_id)` | Пользователь по ID |
| `addModerator(...)` | Создание модератора |
| `deleteUser($user_id)` | Удаление (защита admin) |
| `resetUserPassword($user_id, $new_password)` | Сброс пароля |
| `addProductAdmin(...)` | Добавление товара |
| `updateProduct(...)` | Редактирование товара |
| `deleteProduct($productId)` | Удаление товара |
| `getAllOrders($status)` | Все заказы с фильтром |
| `updateOrderStatus($order_id, $status)` | Смена статуса заказа |
| `getAllReviews()` | Все отзывы |
| `deleteReview($review_id)` | Удаление отзыва |
| `getAdminStats()` | Статистика (6 метрик) |
| `getAllProductsWithSeller()` | Товары с продавцами |
| `getProductWithSeller($productId)` | Товар с продавцом по ID |

## Структура страниц админ-панели

```
admin/
├── index.php                 # Дашборд: 6 карточек статистики
│   └── getAdminStats()
│
├── add_product.php           # Добавление товара
│   ├── uploadProductImage($_FILES)
│   └── addProductAdmin(name, desc, price, image)
│
├── edit_product.php          # Редактирование (GET ?id=)
│   ├── getProductWithSeller(id)
│   └── updateProduct(id, name, desc, price, image)
│
├── products.php              # Список всех товаров
│   ├── getAllProductsWithSeller()
│   └── deleteProduct(id)
│
├── manage_orders.php         # Управление заказами
│   ├── getAllOrders(status)
│   └── Фильтр: pending/completed/cancelled
│
├── order_details.php         # Детали заказа (GET ?id=)
│   ├── getOrder(id)
│   ├── getOrderItems(id)
│   └── updateOrderStatus(id, status)
│
├── manage_users.php          # CRUD пользователей
│   ├── getAllCustomers()
│   ├── addModerator(...)
│   ├── resetUserPassword(id, password)
│   └── deleteUser(id)
│
├── manage_review.php         # Просмотр и удаление отзывов
│   ├── getAllReviews()
│   └── deleteReview(id)
│
└── logout.php                # Уничтожение сессии
```
