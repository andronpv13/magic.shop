# 08 — Панель модератора

## Общие сведения

**Расположение:** `moderator/`  
**Тема оформления:** Синяя тема (#2980b9)  
**Требование:** `requireModerator()` — доступ для ролей `moderator` **или** `admin`

**Подключение ядра (каждая страница):**

```php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/functions_md.php';
requireModerator();
```

## Ключевое отличие от администратора

Модератор **ограничен** в следующих аспектах:

| Функция | Админ | Модератор |
|---|---|---|
| Управление пользователями | ✅ | ❌ |
| Управление отзывами | ✅ | ❌ |
| Просмотр всех товаров | ✅ (с продавцами) | ✅ (только свои) |
| Редактирование товаров | ✅ (любых) | ✅ (только свои) |
| Удаление товаров | ✅ (любых) | ✅ (только свои) |
| Просмотр заказов | ✅ | ✅ (все) |
| Смена статуса заказов | ✅ | ✅ |
| Просмотр статистики | ✅ (полная) | ✅ (частичная) |

## Дашборд

**Файл:** `moderator/index_md.php`

### Статистика (3 карточки)

| Карточка | Данные | Функция |
|---|---|---|
| Мои товары | COUNT(*) WHERE created_by = moderator_id | `getModeratorStats($moderator_id)['my_products']` |
| Все заказы | COUNT(*) FROM orders | `getModeratorStats($moderator_id)['total_orders']` |
| Ожидают | COUNT(*) WHERE status='pending' | `getModeratorStats($moderator_id)['pending_orders']` |

**Примечание:**Moderator видит статистику только по **своим** товарам, но заказы — **все**.

### Быстрые ссылки

- "Добавить товар" → `add_product_md.php`
- "Мои товары" → `products_md.php`
- "Управление заказами" → `manage_orders_md.php`
- "Личный кабинет" → `cab_md.php`

## Управление товарами модератора

### Список "Мои товары"

**Файл:** `moderator/products_md.php`

**Данные:** `getModeratorProducts($moderator_id)` — только товары, созданные этим модератором

```sql
SELECT * FROM products WHERE created_by = ? ORDER BY created_at DESC
```

**Таблица:**

| Колонка | Описание |
|---|---|
| ID | productId |
| Изображение | thumbnail |
| Название | name |
| Цена | price |
| Дата | created_at |
| Действия | Редактировать, Удалить |

**Действия:**

#### Удаление товара

```php
if (isset($_GET['delete'])) {
    deleteProductModerator($_GET['delete'], $_SESSION['user_id']);
    header('Location: products_md.php');
}
```

**Функция:** `deleteProductModerator($productId, $moderator_id)`

1. Проверка `isProductOwner($productId, $moderator_id)`
2. Если владелец — DELETE
3. Если нет — возврат false (без ошибки)

### Добавление товара

**Файл:** `moderator/add_product_md.php`

**Обработка POST:**

1. Получение данных формы: `name`, `description`, `price`
2. Загрузка изображения: `uploadProductImage($_FILES['image'])`
3. Вызов `addProductModerator($name, $description, $price, $image_path, $moderator_id)`
4. Редирект на `products_md.php`

**Функция:** `addProductModerator(..., $moderator_id)`

```php
function addProductModerator($name, $description, $price, $image, $moderator_id) {
    global $mysqli;
    $stmt = $mysqli->prepare("
        INSERT INTO products (name, description, price, image, created_by, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("ssdis", $name, $description, $price, $image, $moderator_id);
    return $stmt->execute();
}
```

**Примечание:** Товар привязывается к модератору через `created_by`.

### Редактирование товара

**Файл:** `moderator/edit_product_md.php`

**Параметр:** GET `?id=` — productId

**GET-режим (отображение):**

1. Проверка `isProductOwner($productId, $moderator_id)`
2. Если не владелец — редирект на `products_md.php`
3. Если владелец — `getProduct($productId)` и заполнение формы

**POST-режим (обновление):**

1. Проверка владения
2. Получение данных формы
3. Если загружено новое изображение — `uploadProductImage()`
4. `updateProductModerator($productId, $name, $description, $price, $new_image, $moderator_id)`
5. Редирект на `products_md.php`

**Функция:** `updateProductModerator(..., $moderator_id)`

- Проверка `isProductOwner()` перед UPDATE
- Если `$image === null` — старое изображение сохраняется

```php
function updateProductModerator($productId, $name, $description, $price, $image, $moderator_id) {
    if (!isProductOwner($productId, $moderator_id)) {
        return false;
    }
    global $mysqli;
    if ($image) {
        $stmt = $mysqli->prepare("
            UPDATE products SET name=?, description=?, price=?, image=? 
            WHERE productId=? AND created_by=?
        ");
        $stmt->bind_param("sssiii", $name, $description, $price, $image, $productId, $moderator_id);
    } else {
        $stmt = $mysqli->prepare("
            UPDATE products SET name=?, description=?, price=? 
            WHERE productId=? AND created_by=?
        ");
        $stmt->bind_param("ssii", $name, $description, $price, $productId, $moderator_id);
    }
    return $stmt->execute();
}
```

## Управление заказами

### Список всех заказов

**Файл:** `moderator/manage_orders_md.php`

**Данные:** `getAllOrdersModerator($status)` — **все** заказы (аналогично админу)

```sql
SELECT o.*, u.username, u.email 
FROM orders o 
JOIN users u ON o.customer_id = u.id 
[WHERE o.status = ?] 
ORDER BY o.created_at DESC
```

**Таблица:**

| Колонка | Описание |
|---|---|
| ID | id_or |
| Покупатель | username |
| Сумма | total |
| Статус | badge (pending/payment/completed/cancelled) |
| Дата | created_at |
| Действия | "Просмотр" → order_details_md.php |

**Фильтр по статусу:**

- Все заказы
- Ожидают (pending)
- В оплате (payment)
- Оплачено (completed)
- Отменены (cancelled)

**Примечание:**Moderator видит **все** заказы, не только связанные с его товарами.

### Детали заказа

**Файл:** `moderator/order_details_md.php`

**Параметр:** GET `?id=` — order_id

**Отображение:**

1. `getOrder($order_id)` — информация о заказе
2. `getOrderItems($order_id)` — позиции с товарами

**Таблица позиций:**

| Колонка | Описание |
|---|---|
| Изображение | product image |
| Название | product name |
| Количество | quantity |
| Цена | price |
| Сумма | quantity × price |

**Смена статуса:**

```php
if (isset($_POST['update_status'])) {
    updateOrderStatusModerator($order_id, $_POST['status']);
    header("Location: order_details_md.php?id=$order_id");
}
```

**Функция:** `updateOrderStatusModerator($order_id, $status)`

```php
function updateOrderStatusModerator($order_id, $status) {
    global $mysqli;
    $stmt = $mysqli->prepare("UPDATE orders SET status=? WHERE id_or=?");
    $stmt->bind_param("si", $status, $order_id);
    return $stmt->execute();
}
```

**Примечание:** Moderator может менять статус **любого** заказа, не только связанных с его товарами.

## Проверка владения товаром

**Файл:** `includes/functions_md.php`

### `isProductOwner($product_id, $moderator_id)`

```php
function isProductOwner($product_id, $moderator_id) {
    global $mysqli;
    $stmt = $mysqli->prepare("SELECT COUNT(*) FROM products WHERE productId = ? AND created_by = ?");
    $stmt->bind_param("ii", $product_id, $moderator_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    return $count > 0;
}
```

**Использование:**

- `edit_product_md.php` — перед редактированием
- `deleteProductModerator()` — перед удалением
- `updateProductModerator()` — перед обновлением

## Статистика модератора

**Файл:** `includes/functions_md.php` → `getModeratorStats($moderator_id)`

```php
function getModeratorStats($moderator_id) {
    global $mysqli;
    $stats = [];
    
    // Мои товары
    $stmt = $mysqli->prepare("SELECT COUNT(*) FROM products WHERE created_by = ?");
    $stmt->bind_param("i", $moderator_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stats['my_products'] = $count;
    
    // Все заказы
    $stmt = $mysqli->prepare("SELECT COUNT(*) FROM orders");
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stats['total_orders'] = $count;
    
    // Ожидающие заказы
    $stmt = $mysqli->prepare("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stats['pending_orders'] = $count;
    
    return $stats;
}
```

## Выход из системы

**Файл:** `moderator/logout_md.php`

```php
session_unset();
session_destroy();
header('Location: /login.php');
exit;
```

## Сводная таблица функций модератора

**Файл:** `includes/functions_md.php`

| Функция | Назначение |
|---|---|
| `addProductModerator(..., $moderator_id)` | Добавление товара с привязкой к модератору |
| `getModeratorProducts($moderator_id)` | Только товары модератора |
| `isProductOwner($product_id, $moderator_id)` | Проверка владения товаром |
| `updateProductModerator(..., $moderator_id)` | Редактирование только своих товаров |
| `deleteProductModerator($productId, $moderator_id)` | Удаление только своих товаров |
| `getAllOrdersModerator($status)` | Все заказы (аналог админа) |
| `updateOrderStatusModerator($order_id, $status)` | Смена статуса любого заказа |
| `getModeratorStats($moderator_id)` | Статистика (3 метрики) |

## Структура страниц модератор-панели

```
moderator/
├── index_md.php                # Дашборд: 3 карточки статистики
│   └── getModeratorStats(moderator_id)
│
├── add_product_md.php          # Добавление товара
│   ├── uploadProductImage($_FILES)
│   └── addProductModerator(name, desc, price, image, moderator_id)
│
├── edit_product_md.php         # Редактирование СВОИХ товаров (GET ?id=)
│   ├── isProductOwner(id, moderator_id)
│   ├── getProduct(id)
│   └── updateProductModerator(id, name, desc, price, image, moderator_id)
│
├── products_md.php             # Список "Мои товары"
│   ├── getModeratorProducts(moderator_id)
│   └── deleteProductModerator(id, moderator_id)
│
├── manage_orders_md.php        # Управление ВСЕМИ заказами
│   ├── getAllOrdersModerator(status)
│   └── Фильтр: pending/completed/cancelled
│
├── order_details_md.php        # Детали любого заказа (GET ?id=)
│   ├── getOrder(id)
│   ├── getOrderItems(id)
│   └── updateOrderStatusModerator(id, status)
│
└── logout_md.php               # Уничтожение сессии
```

## Сравнение панелей: Админ vs Модератор

| Аспект | Админ | Модератор |
|---|---|---|
| **Тема** | Тёмная (#2c3e50) | Синяя (#2980b9) |
| **Статистика** | 6 метрик (полная) | 3 метрики (частичная) |
| **Товары** | Все товары | Только свои (created_by) |
| **Добавление товаров** | ✅ created_by = admin_id | ✅ created_by = moderator_id |
| **Редактирование** | Любые товары | Только свои (isProductOwner) |
| **Удаление** | Любые товары | Только свои (isProductOwner) |
| **Заказы** | Все заказы | Все заказы |
| **Смена статуса** | ✅ | ✅ |
| **Пользователи** | ✅ CRUD | ❌ |
| **Отзывы** | ✅ CRUD | ❌ |
| **Файлы функций** | functions_adm.php | functions_md.php |
| **Префикс страниц** | admin/*.php | moderator/*_md.php |
