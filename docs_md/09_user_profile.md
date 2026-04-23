# 09 — Личный кабинет покупателя

## Общие сведения

**Расположение:** `users/`  
**Требование:** `requireLogin()` для profile.php и order_det_pay.php

## Регистрация

**Файл:** `users/register.php`

### Подключение ядра

```php
require_once '../includes/config.php';
require_once '../includes/functions.php';
```

### Обработка POST

**Условие:** `$_SERVER['REQUEST_METHOD'] === 'POST'`

**Получение данных формы:**

- `username` — логин (уникальный)
- `email` — email (уникальный, используется как логин)
- `password` — пароль (хешируется bcrypt)
- `first_name` — имя
- `last_name` — фамилия
- `phone` — телефон
- `city` — город
- `address` — адрес доставки

**Логика:**

```php
if (isset($_POST['register'])) {
    $result = registerCustomer(
        $_POST['username'], $_POST['email'], $_POST['password'],
        $_POST['first_name'], $_POST['last_name'],
        $_POST['phone'], $_POST['city'], $_POST['address']
    );
    
    if ($result) {
        // Автоматический вход
        universalLogin($_POST['email'], $_POST['password']);
    } else {
        $error = "Email или username уже заняты";
    }
}
```

### Форма регистрации

| Поле | Тип | Требование | Валидация |
|---|---|---|---|
| Username | text | required | unique, min 3 символа |
| Email | email | required | unique, валидный формат |
| Пароль | password | required | min 6 символов |
| Имя | text | required | — |
| Фамилия | text | required | — |
| Телефон | tel | optional | — |
| Город | text | optional | — |
| Адрес | textarea | optional | — |

### Ссылка на вход

- "Уже есть аккаунт? [Войти](/login.php)"

## Профиль пользователя

**Файл:** `users/profile.php`

### Требования

- **Авторизация:** `requireLogin()`

### Подключение ядра

```php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireLogin();
```

### Структура страницы

Страница состоит из нескольких секций:

1. **Редактирование профиля**
2. **Смена пароля**
3. **История заказов**
4. **Отзывы** (возможность оставить)

---

### 1. Редактирование профиля

**GET-режим (отображение):**

```php
$customer = getCustomer($_SESSION['user_id']);
// Заполнение формы данными из БД
```

**Форма:**

| Поле | Тип | Изменяемо |
|---|---|---|
| Username | text | ❌ (readonly) |
| Email | email | ❌ (readonly) |
| Имя | text | ✅ |
| Фамилия | text | ✅ |
| Телефон | tel | ✅ |
| Город | text | ✅ |
| Адрес | textarea | ✅ |

**POST-режим (обновление):**

```php
if (isset($_POST['update_profile'])) {
    updateCustomerProfile(
        $_SESSION['user_id'],
        $_POST['first_name'], $_POST['last_name'],
        $_POST['phone'], $_POST['city'], $_POST['address']
    );
    $success = "Профиль обновлён";
}
```

**Функция:** `updateCustomerProfile($id, $first_name, $last_name, $phone, $city, $address)`

```sql
UPDATE users SET first_name=?, last_name=?, phone=?, city=?, address=? WHERE id=?
```

---

### 2. Смена пароля

**Форма:**

| Поле | Тип | Требование |
|---|---|---|
| Текущий пароль | password | required |
| Новый пароль | password | required, min 6 символов |
| Подтверждение | password | required, должно совпадать |

**POST-режим:**

```php
if (isset($_POST['change_password'])) {
    $old = $_POST['old_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    
    if ($new !== $confirm) {
        $error = "Пароли не совпадают";
    } else {
        $result = changePassword($_SESSION['user_id'], $old, $new);
        if ($result) {
            $success = "Пароль изменён";
        } else {
            $error = "Неверный текущий пароль";
        }
    }
}
```

**Функция:** `changePassword($user_id, $old_password, $new_password)`

1. SELECT password FROM users WHERE id = ?
2. password_verify($old_password, $hash)
3. Если верен — UPDATE с password_hash($new_password)

---

### 3. История заказов

**Данные:** `getCustomerOrders($_SESSION['user_id'])`

```sql
SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC
```

**Таблица заказов:**

| Колонка | Описание |
|---|---|
| ID | id_or |
| Сумма | total |
| Статус | badge (pending/payment/completed/cancelled) |
| Дата | created_at |
| Действия | "Подробнее" → order_det_pay.php?id= |

**Статусы и цвета:**

| Статус | Цвет | Описание |
|---|---|---|
| pending | жёлтый (#f39c12) | Ожидает обработки |
| payment | синий (#3498db) | Ожидает оплаты |
| completed | зелёный (#27ae60) | Оплачен |
| cancelled | красный (#e74c3c) | Отменён |

---

### 4. Отзывы

#### Товары для отзывов

**Данные:** `getCustomerPurchasedProducts($_SESSION['user_id'])`

```sql
SELECT DISTINCT p.* 
FROM products p
JOIN order_items oi ON p.productId = oi.product_id
JOIN orders o ON oi.order_id = o.id_or
WHERE o.customer_id = ? AND o.status = 'completed'
```

**Логика:** Для каждого completed-заказа получаются все товары, пользователь может оставить отзыв на каждый.

#### Проверка возможности отзыва

**Функция:** `canReviewProduct($customer_id, $product_id)`

```php
function canReviewProduct($customer_id, $product_id) {
    // 1. Купил ли товар (completed заказ)
    $purchased = check if product in completed orders
    
    // 2. Не оставлял ли уже отзыв
    $already_reviewed = check if exists in reviews
    
    return $purchased && !$already_reviewed;
}
```

#### Форма добавления отзыва

**Отображение:** На странице `shop.php?id=` (детальная страница товара)

```php
if (isLoggedIn() && canReviewProduct($_SESSION['user_id'], $product_id)) {
    // Показать форму отзыва
}
```

**Форма:**

| Поле | Тип | Требование |
|---|---|---|
| Текст отзыва | textarea | required |

**POST-режим (на shop.php):**

```php
if (isset($_POST['add_review'])) {
    addReview($product_id, $_SESSION['user_id'], $_POST['comment']);
    header("Location: shop.php?id=$product_id");
}
```

**Функция:** `addReview($product_id, $customer_id, $comment)`

```sql
INSERT INTO reviews (product_id, customer_id, comment, created_at) 
VALUES (?, ?, ?, NOW())
```

**UNIQUE KEY (product_id, customer_id)** предотвращает дубликаты.

## Детали заказа

**Файл:** `users/order_det_pay.php`

### Требования для оплаты

- **Авторизация:** `requireLogin()`
- **Параметр:** GET `?id=` — order_id

### Проверка владения заказом

```php
$order_id = $_GET['id'];
$order = getOrder($order_id);

// Проверка: заказ принадлежит текущему пользователю
if (!$order || $order['customer_id'] != $_SESSION['user_id']) {
    header('Location: profile.php');
    exit;
}
```

### Отображение

**Информация о заказе:**

- Номер заказа: `id_or`
- Сумма: `total`
- Статус: badge с цветом
- Дата создания: `created_at`

**Таблица позиций:**

```php
$items = getOrderItems($order_id);
```

| Колонка | Описание |
|---|---|
| Изображение | product image |
| Название | product name (ссылка на shop.php?id=) |
| Количество | quantity |
| Цена | price |
| Сумма | quantity × price |

**Кнопка оплаты:**

- Если статус `pending` — показ кнопки "Оплатить"
- Ссылка: `/pay.php?order_id={$order_id}`

## Выход из системы

**Файл:** `users/logout_user.php`

```php
session_unset();
session_destroy();
header('Location: /login.php');
exit;
```

## Поток данных покупателя

```
users/register.php
  ├── POST register ─────────────→ registerCustomer(...)
  │   ├── Проверка уникальности email/username
  │   ├── bcrypt hash пароля
  │   └── INSERT INTO users (role='customer')
  │
  └── Успех ────────────────────→ universalLogin() → redirect /

users/profile.php
  ├── GET ───────────────────────→ getCustomer(id) → форма профиля
  │   ├── GET ───────────────────→ getCustomerOrders(id) → история заказов
  │   └── GET ───────────────────→ getCustomerPurchasedProducts(id) → товары для отзывов
  │
  ├── POST update_profile ───────→ updateCustomerProfile(...)
  │   └── UPDATE users SET first_name, last_name, phone, city, address
  │
  └── POST change_password ──────→ changePassword(...)
      ├── password_verify(old)
      └── UPDATE users SET password = bcrypt(new)

users/order_det_pay.php
  ├── GET ?id= ──────────────────→ getOrder(id)
  │   ├── Проверка: customer_id == $_SESSION['user_id']
  │   ├── getOrderItems(id) ───→ товары
  │   └── Кнопка "Оплатить" ───→ pay.php?order_id=
  │
  └── Не владелец ───────────────→ redirect profile.php

shop.php?id=X (детальная страница)
  ├── getProduct(id) ────────────→ товар
  ├── getProductReviews(id) ───→ отзывы
  ├── canReviewProduct(user_id, product_id)
  │   ├── completed заказ с этим товаром?
  │   └── нет существующего отзыва?
  │
  └── Если можно ────────────────→ форма отзыва
      └── POST add_review ──────→ addReview(product_id, user_id, comment)
```

## Сводная таблица функций для покупателя

**Файл:** `includes/functions.php`

| Функция | Назначение |
|---|---|
| `registerCustomer(...)` | Регистрация нового пользователя |
| `getCustomer($id)` | Профиль покупателя |
| `updateCustomerProfile(...)` | Обновление профиля |
| `changePassword(...)` | Смена пароля |
| `getCustomerOrders($customer_id)` | История заказов |
| `getOrder($order_id)` | Заказ по ID |
| `getOrderItems($order_id)` | Позиции заказа |
| `getCustomerPurchasedProducts($customer_id)` | Купленные товары (completed) |
| `canReviewProduct($customer_id, $product_id)` | Можно ли оставить отзыв |
| `addReview($product_id, $customer_id, $comment)` | Добавление отзыва |
| `getProductReviews($product_id)` | Отзывы на товар |

## Структура страниц покупателя

```
users/
├── register.php              # Регистрация нового пользователя
│   ├── POST register ───────→ registerCustomer(...)
│   └── Успех ───────────────→ universalLogin() → redirect /
│
├── profile.php               # Личный кабинет (требует авторизации)
│   ├── Секция 1: Редактирование профиля
│   │   ├── GET ────────────→ getCustomer(id)
│   │   └── POST ───────────→ updateCustomerProfile(...)
│   │
│   ├── Секция 2: Смена пароля
│   │   └── POST ───────────→ changePassword(...)
│   │
│   ├── Секция 3: История заказов
│   │   └── GET ────────────→ getCustomerOrders(id)
│   │
│   └── Секция 4: Отзывы
│       └── GET ────────────→ getCustomerPurchasedProducts(id)
│
├── order_det_pay.php         # Детали заказа (требует авторизации)
│   ├── GET ?id= ───────────→ getOrder(id) + getOrderItems(id)
│   ├── Проверка владельца ─→ redirect если чужой
│   └── Кнопка "Оплатить" ──→ pay.php?order_id=
│
└── logout_user.php           # Уничтожение сессии
```
