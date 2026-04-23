# 02 — Аутентификация и безопасность

## Система сессий

**Файл:** `includes/config.php`

### Настройки сессии

| Параметр | Значение | Описание |
|---|---|---|
| timeout | 600 секунд (10 мин) | Автоматический logout при неактивности |
| regeneration | 1800 секунд (30 мин) | Регенерация session ID для защиты от фиксации |
| httponly | true | Запрет доступа к cookies через JavaScript |
| samesite | 'Strict' | Защита от CSRF-атак |
| cookie_secure | (по умолчанию) | Требование HTTPS |

### Механизм автоматического выхода

При каждом запросе проверяется время последней активности:

```php
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 600)) {
    session_unset();
    session_destroy();
    header('Location: /login.php');
    exit;
}
$_SESSION['last_activity'] = time();
```

### Регенерация session ID

```php
if (!isset($_SESSION['created']) || (time() - $_SESSION['created'] > 1800)) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}
```

## Хелперы авторизации

**Файл:** `includes/config.php`

### `isLoggedIn()`

Проверяет наличие `$_SESSION['user_id']`. Возвращает `true` если пользователь авторизован.

### `hasRole($role)`

Проверяет `$_SESSION['role'] === $role`. Поддерживаемые роли: `admin`, `moderator`, `customer`.

### `requireLogin()`

Если пользователь не авторизован — редирект на `/login.php`. Используется на страницах checkout, profile, cart.

### `requireAdmin()`

Требует роль `admin`. Если роль не совпадает — редирект на `/login.php`.

### `requireModerator()`

Требует роль `moderator` **или** `admin`. Позволяет администратору accessing страницы модератора.

## Универсальный логин

**Файл:** `includes/functions.php` → `universalLogin($login, $password)`

### Алгоритм логина

1. Поиск пользователя по **username** ИЛИ **email** (prepared statement)
2. bcrypt верификация пароля через `password_verify()`
3. Установка сессионных переменных:
   - `$_SESSION['user_id']` = id пользователя
   - `$_SESSION['username']` = username
   - `$_SESSION['role']` = role
4. Редирект по роли:
   - `admin` → `/admin/index.php`
   - `moderator` → `/moderator/index_md.php`
   - `customer` → `/` (главная страница)

### Страница входа

**Файл:** `login.php`

- Универсальная форма для всех ролей
- Поле ввода: email или username + пароль
- Отображение ошибок через `$error` переменную
- Стилизация через `css/style.css`

## Регистрация покупателя

**Файл:** `includes/functions.php` → `registerCustomer($username, $email, $password, $first_name, $last_name, $phone, $city, $address)`

### Валидация

1. Проверка уникальности email (запрос к БД)
2. Проверка уникальности username (запрос к БД)
3. Хэширование пароля через `password_hash(PASSWORD_BCRYPT)`
4. Вставка в таблицу `users` с ролью `customer`

### Страница регистрации

**Файл:** `users/register.php`

- Форма с полями: username, email, password, first_name, last_name, phone, city, address
- После успешной регистрации — автоматический вход и редирект

## Меры безопасности

| Мера | Реализация | Файл |
|---|---|---|
| SQL-инъекции | Prepared statements (mysqli `prepare/bind_param`) везде | Все файлы functions* |
| XSS | `e()` = `htmlspecialchars(ENT_QUOTES, 'UTF-8')` на всех выводах | includes/config.php |
| Пароли | bcrypt (`password_hash` / `password_verify`) | functions.php |
| CSRF | **ОТСУТСТВУЕТ** — нет CSRF-токенов | — |
| Session fixation | Регенерация session ID каждые 30 мин | config.php |
| Session hijacking | httponly=true, samesite=Strict | config.php |
| Session timeout | 10 мин неактивности | config.php |
| Authorization | `requireAdmin()`, `requireModerator()`, проверка `customer_id` | Все защищённые страницы |
| File upload | Проверка расширения (JPG/PNG/GIF/WebP), лимит 5MB, `uniqid()` для имени | functions.php → uploadProductImage() |
| Role protection | Удаление admin запрещено, модератор редактирует только свои товары | functions_adm.php, functions_md.php |
| Order ownership | Проверка `customer_id` в `pay.php` и `order_det_pay.php` | pay.php, users/order_det_pay.php |

## Выход из системы

### Администратор

**Файл:** `admin/logout.php`

```php
session_unset();
session_destroy();
header('Location: /login.php');
```

### Модератор

**Файл:** `moderator/logout_md.php`

Аналогично администратору.

### Покупатель

**Файл:** `users/logout_user.php`

Аналогично, редирект на `/login.php`.

## Смена пароля

**Файл:** `includes/functions.php` → `changePassword($user_id, $old_password, $new_password)`

### Алгоритм смены пароля

1. Получение текущего хэша пароля из БД
2. Верификация старого пароля через `password_verify()`
3. Если верен — хэширование нового пароля и обновление в БД
4. Возврат результата (true/false)

### Страница смены пароля

**Файл:** `users/profile.php` (секция смены пароля)

- Поля: текущий пароль, новый пароль, подтверждение нового пароля
- Валидация совпадения нового пароля и подтверждения

## Сброс пароля администратором

**Файл:** `includes/functions_adm.php` → `resetUserPassword($user_id, $new_password)`

- Администратор задаёт новый пароль для любого пользователя (кроме admin)
- Пароль хэшируется через bcrypt перед сохранением
- Используется в `admin/manage_users.php` через модальное окно

## Ограничения ролей

### Admin

- Полный доступ ко всем функциям
- Управление пользователями (добавление и удаление модераторов и пользователей, сброс паролей)
- Управление всеми товарами, заказами, отзывами
- Просмотр статистики
- Редактирование своего профиля, смена пароля

### Moderator

- Добавление/редактирование/удаление **только своих** товаров (проверка `created_by`)
- Просмотр **всех** заказов и смена статуса
- **Не может** управлять пользователями или отзывами
- **Не может** удалять чужие товары
- Редактирование своего профиля, смена пароля

### Customer

- Просмотр каталога, добавление в корзину
- Оформление заказов, оплата
- Просмотр истории заказов
- Оставление отзывов (только на товары из completed заказов)
- Редактирование своего профиля, смена пароля
