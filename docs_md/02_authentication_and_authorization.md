# 02 - Аутентификация и авторизация

## Конфигурация и сессии

**Файл:** `includes/config.php`

### Настройки подключения к БД

```php
host = localhost
db_name = shop_db
db_user = root
db_pass = Andronpv13
charset = utf8mb4
```

### Настройки сессий

| Параметр | Значение | Описание |
|---|---|---|
| session timeout | 600 секунд (10 мин) | Автоматический logout при неактивности |
| session regeneration | 1800 секунд (30 мин) | Регенерация session ID для защиты от фиксации |
| cookie_httponly | true | Защита от XSS-атак через JavaScript |
| cookie_samesite | 'Strict' | Защита от CSRF-атак |
| error_reporting | E_ALL | Полный вывод ошибок (display_errors = 1) |

### Хелперы авторизации

| Функция | Описание | Действие при отказе |
|---|---|---|
| `isLoggedIn()` | Проверяет `$_SESSION['user_id']` | Возвращает boolean |
| `hasRole($role)` | Проверяет `$_SESSION['role']` | Возвращает boolean |
| `requireLogin()` | Требует авторизацию | Редирект на `/login.php` |
| `requireAdmin()` | Требует роль `admin` | Редирект на `/` |
| `requireModerator()` | Требует `moderator` | Редирект на `/` |

---

## Универсальный логин

**Файл:** `includes/functions.php` → `universalLogin()`

### Процесс аутентификации

1. Пользователь вводит логин (username ИЛИ email) и пароль
2. Поиск пользователя: `WHERE username = ? OR email = ?`
3. Верификация пароля: `password_verify()`
4. Установка сессии:

   ```php
   $_SESSION['user_id'] = $user['id'];
   $_SESSION['username'] = $user['username'];
   $_SESSION['role'] = $user['role'];
   ```

5. Редирект по роли:

   - `admin` → `/admin/index.php`
   - `moderator` → `/moderator/index_md.php`
   - `customer` → `/shop.php`

### Файл `login.php`

- Единая точка входа для всех ролей
- Форма с полями: логин/email, пароль
- Вывод ошибок при неверных данных
- CSS стили встроены напрямую (не через header.php)

---

## Регистрация покупателя

**Файл:** `includes/functions.php` → `registerCustomer()`

### Параметры функции

```php
registerCustomer($username, $email, $password, $first_name, $last_name, $phone, $city, $address)
```

### Процесс регистрации

1. **Валидация email:** проверка на уникальность
2. **Хеширование пароля:** `password_hash($password, PASSWORD_BCRYPT)`
3. **Вставка в БД:** роль автоматически `customer`
4. **Возврат:** ID нового пользователя или false

### Файл `users/register.php`

- Форма регистрации с полями:
  - username, email, password, first_name, last_name, phone, city, address
- Валидация на стороне клиента (`validateForm()` в js/validation.js)
- Серверная валидация и вывод ошибок
- После успешной регистрации -- редирект на `users/edit_profile.php`

---

## Выход из системы

| Файл | Роль | Действие |
|---|---|---|
| `logout.php` | Admin | `session_unset()` + `session_destroy()` → редирект на `/` |
| `logout.php` | Moderator | `session_unset()` + `session_destroy()` → редирект на `/` |
| `logout.php` | Customer | `session_unset()` + `session_destroy()` → редирект на `/` |

---

## Управление пользователями (Admin)

**Файл:** `includes/functions_adm.php`

### Функции

| Функция | Описание | Ограничения |
|---|---|---|
| `getAllCustomers()` | Все пользователи кроме admin | — |
| `getUserById($user_id)` | Получить пользователя по ID | — |
| `addModerator($username, $email, $password, $first_name, $last_name)` | Создать модератора | Проверка уникальности email/username |
| `deleteUser($user_id)` | Удалить пользователя | **Запрещено удалять admin** |
| `resetUserPassword($user_id, $new_password)` | Сброс пароля | bcrypt хеширование |

### Файл `admin/manage_users.php`

- Таблица всех пользователей (кроме admin)
- Действия:
  - Удаление (с подтверждением)
  - Сброс пароля (модальное окно)
- Форма добавления модератора в верхней части страницы

---

## Меры безопасности

| Мера | Реализация |
|---|---|
| SQL-инъекции | Prepared statements (mysqli `prepare/bind_param`) везде |
| XSS | `e()` = `htmlspecialchars(ENT_QUOTES, 'UTF-8')` на всех выводах |
| Пароли | bcrypt (`password_hash` / `password_verify`) |
| CSRF | ⚠️ **ОТСУТСТВУЕТ** -- нет CSRF-токенов |
| Session fixation | Регенерация session ID каждые 30 мин |
| Session hijacking | httponly=true, samesite=Strict |
| Session timeout | 10 мин неактивности |
| Authorization | `requireAdmin()`, `requireModerator()`, проверка `customer_id` |
