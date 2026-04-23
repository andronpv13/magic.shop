# 11 — Развёртывание и настройка

## Системные требования

| Компонент | Минимальная версия | Рекомендуемая версия |
|---|---|---|
| PHP | 7.4 | 8.1+ |
| MySQL / MariaDB | 5.7 / 10.2 | 8.0 / 10.6+ |
| Веб-сервер | Apache 2.4 / Nginx 1.18 | Apache 2.4 / Nginx 1.25+ |
| ОС | Windows 10 / Linux | Ubuntu 22.04 / Debian 12 |

## Установка

### Шаг 1: Настройка базы данных

#### 1.1 Создание базы данных

```sql
CREATE DATABASE shop_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

#### 1.2 Импорт схемы

```bash
mysql -u root -p shop_db < database/schema.sql
```

Или через phpMyAdmin:

1. Открыть phpMyAdmin
2. Выбрать базу `shop_db`
3. Перейти в "Импорт"
4. Загрузить файл `database/schema.sql`

#### 1.3 Структура schema.sql

Файл `database/schema.sql` содержит:

- CREATE TABLE для всех таблиц (users, products, orders, order_items, reviews)
- Внешние ключи (FOREIGN KEY) с ON DELETE CASCADE / SET NULL
- Индексы для оптимизации запросов
- Дефолтных пользователей (admin и moderator)

#### 1.4 Дефолтные пользователи

| Роль | Логин | Пароль |
|---|---|---|
| admin | root | toor |
| moderator | moderator | toor |

**ВАЖНО:** Сразу после установки смените пароли!

### Шаг 2: Настройка подключения к БД

**Файл:** `includes/config.php`

Отредактируйте параметры подключения:

```php
$host = 'localhost';
$db_name = 'shop_db';
$db_user = 'root';
$db_pass = 'toor';
$charset = 'utf8mb4';

$mysqli = new mysqli($host, $db_user, $db_pass, $db_name);
$mysqli->set_charset($charset);
```

**Для продакшена:**

- Используйте отдельного пользователя БД (не root)
- Задайте сложный пароль
- Ограничьте права пользователя (только SELECT, INSERT, UPDATE, DELETE)

### Шаг 3: Настройка веб-сервера

#### Apache (httpd.conf или .htaccess)

```apache
DocumentRoot "e:/serwers/home/shopai"
ServerName shopai.local

<Directory "e:/serwers/home/shopai">
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>

# Перезапись URL (если нужна)
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

**Модули Apache:**

- mod_rewrite (для ЧПУ)
- mod_php (или PHP-FPM)

#### Nginx + PHP-FPM

```nginx
server {
    listen 80;
    server_name shopai.local;
    root e:/serwers/home/shopai;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\. {
        deny all;
    }
}
```

### Шаг 4: Настройка PHP

**php.ini:**

```ini
; Основные настройки
upload_max_filesize = 5M        ; Макс. размер загружаемого файла
post_max_size = 10M             ; Макс. размер POST-запроса
max_execution_time = 30         ; Макс. время выполнения (сек)
memory_limit = 128M             ; Лимит памяти

; Сессии
session.cookie_httponly = 1     ; Защита от XSS
session.cookie_samesite = Strict ; Защита от CSRF
session.gc_maxlifetime = 1440   ; Время жизни сессии (24 мин)

; Ошибки (для продакшена)
display_errors = Off            ; Не показывать ошибки
log_errors = On                 ; Логировать ошибки
error_log = /var/log/php_errors.log

; Расширения
extension=mysqli
extension=mbstring
extension=json
```

### Шаг 5: Права на запись

**Необходимо дать права на запись:**

| Каталог | Назначение | Права |
|---|---|---|
| `images/product/` | Загрузка фото товаров | 755 (rwxr-xr-x) |
| `images/fon/` | Фоновые изображения | 755 (только чтение) |

**Windows:**

- Правый клик по папке → Свойства → Безопасность
- Дать права на запись для пользователя IIS/Apache

**Linux:**

```bash
chmod 755 images/product/
chown www-data:www-data images/product/
```

### Шаг 6: Проверка установки

1. **Откройте браузер:** `http://shopai.local`
2. **Главная страница:** Должна отображаться hero-секция, товары, отзывы
3. **Вход админа:**
   - Перейти на `http://shopai.local/login.php`
   - Логин: `root`, Пароль: `toor`
   - Должен произойти редирект на `/admin/index.php`
4. **Дашборд админа:** Отображение 6 карточек статистики
5. **Каталог:** `http://shopai.local/shop.php` — список товаров

### Шаг 7: Смена паролей

**Обязательно после установки!**

#### Через панель администратора

1. Войти как admin (root/toor)
2. Перейти в "Управление пользователями"
3. Для пользователя admin:
   - Сбросить пароль на сложный
4. Для пользователя moderator:
   - Сбросить пароль на сложный

#### Через SQL

```sql
-- Смена пароля admin (новый пароль: admin_secure_2024)
UPDATE users SET password = '$2y$10$...' WHERE username = 'root';

-- Смена пароля moderator
UPDATE users SET password = '$2y$10$...' WHERE username = 'moderator';
```

**Генерация bcrypt хэша:**

```php
echo password_hash('new_secure_password', PASSWORD_BCRYPT);
```

## Конфигурация для продакшена

### 1. Отключение отображения ошибок

**Файл:** `includes/config.php`

```php
error_reporting(E_ALL);
ini_set('display_errors', 0);        // Off для продакшена
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/php_errors.log');
```

### 2. HTTPS

**Обязательно для продакшена!**

**Файл:** `includes/config.php`

```php
// Принудительный HTTPS
if ($_SERVER['HTTPS'] !== 'on') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}

// Secure cookie для сессии
ini_set('session.cookie_secure', 1);
```

### 3. CSRF-защита

**Важно:** В текущей версии CSRF-токены **отсутствуют**. Для продакшена рекомендуется добавить:

```php
// Генерация токена
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Проверка токена
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
```

**Добавление в формы:**

```html
<input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
```

**Проверка в POST-обработке:**

```php
if (!verifyCsrfToken($_POST['csrf_token'])) {
    die('CSRF token validation failed');
}
```

### 4. Rate Limiting

Защита от брутфорса на login.php:

```php
// Простая защита через сессии
function checkLoginAttempts() {
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = ['count' => 0, 'time' => time()];
    }
    
    $attempts = &$_SESSION['login_attempts'];
    
    // Сброс через 15 минут
    if (time() - $attempts['time'] > 900) {
        $attempts['count'] = 0;
        $attempts['time'] = time();
    }
    
    // Блокировка после 5 попыток
    if ($attempts['count'] >= 5) {
        die('Слишком много попыток. Подождите 15 минут.');
    }
    
    $attempts['count']++;
}
```

### 5. Резервное копирование БД

**Автоматизация через cron (Linux):**

```bash
#每天 2:00
0 2 * * * mysqldump -u root -p shop_db > /backup/shopai/shop_db_$(date +\%Y\%m\%d).sql
```

**Windows (Task Scheduler):**

```batch
@echo off
set BACKUP_DIR=e:\backup\shopai
set DATE=%date:~6,4%%date:~3,2%%date:~0,2%
mysqldump -u root -ptoor shop_db > %BACKUP_DIR%\shop_db_%DATE%.sql
```

### 6. Мониторинг

**Рекомендуемые метрики:**

- Время ответа сервера (< 200ms)
- Количество активных сессий
- Размер БД
- Ошибки PHP (log-файл)
- Использование дискового пространства (images/product/)

## Чек-лист перед запуском

- [ ] База данных создана и импортирована
- [ ] Подключение к БД настроено
- [ ] Веб-сервер настроен и работает
- [ ] PHP модули активированы (mysqli, mbstring)
- [ ] Права на запись для `images/product/`
- [ ] Пароли дефолтных пользователей сменены
- [ ] HTTPS включён
- [ ] display_errors = Off
- [ ] CSRF-защита добавлена (опционально)
- [ ] Rate limiting настроен (опционально)
- [ ] Резервное копирование настроено
- [ ] Тестирование всех ролей (admin, moderator, customer)
- [ ] Тестирование корзины и оформления заказа
- [ ] Тестирование загрузки изображений

## Возможные проблемы и решения

### Ошибка: "Connection failed: Access denied"

**Причина:** Неправильные учётные данные БД

**Решение:**

1. Проверить `$db_user` и `$db_pass` в `config.php`
2. Убедиться, что пользователь существует в MySQL
3. Проверить права пользователя

### Ошибка: "Table 'shop_db.users' doesn't exist"

**Причина:** Схема БД не импортирована

**Решение:**

```bash
mysql -u root -p shop_db < database/schema.sql
```

### Ошибка: "Failed to open stream: Permission denied"

**Причина:** Нет прав на запись в `images/product/`

**Решение:**

```bash
chmod 755 images/product/
chown www-data:www-data images/product/
```

### Ошибка: "Session data not working"

**Причина:** Неправильная настройка сессий

**Решение:**

1. Проверить `session.save_path` в php.ini
2. Убедиться, что `session_start()` вызывается перед любым выводом
3. Проверить права на директорию сессий

### Ошибка: "Image upload failed"

**Причина:** Лимит размера файла

**Решение:**

```ini
upload_max_filesize = 5M
post_max_size = 10M
```

### Ошибка: "Headers already sent"

**Причина:** Вывод до `header()` или `session_start()`

**Решение:**

1. Убрать echo/print до header()
2. Убрать BOM в начале файлов (UTF-8 без BOM)
3. Проверить, что `session_start()` в начале файла

## Обновление проекта

### Резервное копирование перед обновлением

```bash
# БД
mysqldump -u root -p shop_db > backup_before_update.sql

# Файлы
tar -czf backup_files.tar.gz e:/serwers/home/shopai/
```

### Импорт новой схемы

```bash
mysql -u root -p shop_db < database/schema.sql
```

**Примечание:** Если схема изменилась, использовать ALTER TABLE вместо полного импорта.

## Поддержка

**Команда:** АВВА  
**Технологии:** PHP, MySQL, HTML5, CSS3, JavaScript  
**Документация:** `docs_md/`
