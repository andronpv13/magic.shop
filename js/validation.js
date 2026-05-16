// Конфигурация API
if (typeof window.apiBaseUrl === 'undefined') {
    window.apiBaseUrl = '../';
}

// Универсальная функция переключения видимости пароля (глобальная)
function initPasswordToggle(inputId) {
    const input = document.getElementById(inputId);
    if (!input) return;

    const wrapper = input.parentElement;
    // Ищем или создаем кнопку глаза
    let toggleBtn = wrapper.querySelector('.password-toggle');

    if (!toggleBtn) {
        toggleBtn = document.createElement('button');
        toggleBtn.type = 'button';
        toggleBtn.className = 'password-toggle';
        toggleBtn.setAttribute('aria-label', 'Показать/скрыть пароль');
        // Инициализируем tooltip: по умолчанию пароль скрыт (type='password'), поэтому подсказка "Показать пароль"
        toggleBtn.setAttribute('data-tooltip', 'Показать пароль');
        toggleBtn.innerHTML = ''; // Очищаем содержимое, иконка через CSS ::before
        wrapper.appendChild(toggleBtn);
    }

    // Удаляем предыдущие обработчики (клонированием)
    const newBtn = toggleBtn.cloneNode(true);
    toggleBtn.parentNode.replaceChild(newBtn, toggleBtn);
    toggleBtn = newBtn;

    // Функция обновления tooltip
    function updateTooltip() {
        const isPasswordVisible = input.type === 'text';
        // При открытом глазе (пароль виден) - подсказка "Скрыть пароль"
        // При закрытом глазе (пароль скрыт) - подсказка "Показать пароль"
        toggleBtn.setAttribute('data-tooltip', isPasswordVisible ? 'Скрыть пароль' : 'Показать пароль');
    }

    // Инициализируем tooltip при создании кнопки
    updateTooltip();

    toggleBtn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();

        const isPassword = input.type === 'password';
        // Переключаем тип input
        input.type = isPassword ? 'text' : 'password';

        // Меняем иконку: если показываем пароль (type='text'), добавляем класс active для перечёркнутого глаза
        toggleBtn.classList.toggle('active', isPassword);

        // Обновляем tooltip
        updateTooltip();

        // Возвращаем фокус на input для удобства
        input.focus();
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('registerForm');

    // Проверка наличия поля пароля для страницы входа (если форма регистрации отсутствует)
    const loginPasswordInput = document.getElementById('password');
    const isLoginPage = loginPasswordInput && !form;

    // Если это не страница регистрации и не страница входа - выходим
    if (!form && !isLoginPage) return;

    // Инициализация кнопки глаза для страницы входа (если это страница входа)
    if (isLoginPage && loginPasswordInput) {
        initPasswordToggle('password');
        // Для страницы входа не нужна валидация, только кнопка глаза
        return;
    }

    // Элементы формы (для страницы регистрации)
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const submitBtn = document.getElementById('submitBtn');

    let validationState = {
        username: false,
        email: false,
        password: false,
        confirm: false
    };

    // --- Утилиты ---

    // Debounce для AJAX запросов
    function debounce(func, delay) {
        let timeout;
        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), delay);
        };
    }

    // Обновление состояния кнопки отправки
    function updateSubmitButton() {
        const isValid = Object.values(validationState).every(Boolean);
        if (submitBtn) {
            submitBtn.disabled = !isValid;
            submitBtn.style.opacity = isValid ? '1' : '0.6';
            submitBtn.style.cursor = isValid ? 'pointer' : 'not-allowed';
        }
    }

    // Установка статуса валидации для поля
    function setFieldStatus(inputElement, isValid, errorClass = 'error', successClass = 'success') {
        if (!inputElement) return;

        // Удаляем предыдущие классы
        inputElement.classList.remove(errorClass, successClass);

        // Добавляем новый статус
        if (isValid) {
            inputElement.classList.add(successClass);
        } else {
            inputElement.classList.add(errorClass);
        }
    }

    // Получаем CSRF токен из meta тега
    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    // --- Логика валидации полей ---

    // 1. Валидация Логина
    const validateUsername = debounce(async () => {
        const value = usernameInput.value.trim();
        const regex = /^[a-zA-Zа-яА-ЯёЁ]{4,10}$/;

        if (!regex.test(value)) {
            validationState.username = false;
            setFieldStatus(usernameInput, false);
            updateSubmitButton();
            return;
        }

        // AJAX проверка уникальности с CSRF токеном
        try {
            const response = await fetch((window.apiBaseUrl || '../') + 'users/check_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({ type: 'username', value: value })
            });
            const data = await response.json();

            // Если available: true, значит логин свободен
            validationState.username = data.available === true;
            setFieldStatus(usernameInput, validationState.username);
        } catch (e) {
            validationState.username = false;
            setFieldStatus(usernameInput, false);
        }
        updateSubmitButton();
    }, 500);

    // 2. Валидация Email
    const validateEmail = debounce(async () => {
        const value = emailInput.value.trim();
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!regex.test(value)) {
            validationState.email = false;
            setFieldStatus(emailInput, false);
            updateSubmitButton();
            return;
        }

        // AJAX проверка уникальности с CSRF токеном
        try {
            const response = await fetch((window.apiBaseUrl || '../') + 'users/check_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({ type: 'email', value: value })
            });
            const data = await response.json();

            validationState.email = data.available === true;
            setFieldStatus(emailInput, validationState.email);
        } catch (e) {
            validationState.email = false;
            setFieldStatus(emailInput, false);
        }
        updateSubmitButton();
    }, 500);

    // 3. Валидация Пароля
    const validatePassword = () => {
        const value = passwordInput.value;
        // Пароль должен быть не менее 6 символов и не содержать пробелы и tab
        const isValid = value.length >= 6 && !/[\s\t]/.test(value);

        validationState.password = isValid;
        setFieldStatus(passwordInput, isValid);

        // Если поле подтверждения уже заполнено, перепроверяем его
        if (confirmPasswordInput.value) {
            validateConfirmPassword();
        }

        updateSubmitButton();
    };

    // 4. Валидация Подтверждения пароля
    const validateConfirmPassword = () => {
        const pass = passwordInput.value;
        const confirm = confirmPasswordInput.value;
        const isValid = confirm.length > 0 && pass === confirm;

        validationState.confirm = isValid;
        setFieldStatus(confirmPasswordInput, isValid);
        updateSubmitButton();
    };

    // --- Инициализация слушателей ---

    if (usernameInput) {
        usernameInput.addEventListener('input', validateUsername);
    }

    if (emailInput) {
        emailInput.addEventListener('input', validateEmail);
    }

    if (passwordInput) {
        passwordInput.addEventListener('input', validatePassword);
        initPasswordToggle('password');
    }

    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', validateConfirmPassword);
        initPasswordToggle('confirm_password');
    }

    // Блокируем кнопку изначально
    updateSubmitButton();

    // Экспортируем функцию глобально для использования на других страницах (например, login.php)
    // Вызываем после завершения инициализации, чтобы функция была доступна
    if (typeof window.initPasswordToggleExport !== 'function') {
        window.initPasswordToggleExport = true;
        window.initPasswordToggle = initPasswordToggle;
    }
});

// Валидация для страницы редактирования профиля (edit_profile.php)
function initEditProfileValidation() {
    const form = document.getElementById('editProfileForm');
    const saveBtn = document.getElementById('saveBtn');

    if (!form) return;

    // Валидация телефона
    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                if (value.length >= 11) {
                    value = '+7 (' + value.slice(1, 4) + ') ' + value.slice(4, 7) + '-' + value.slice(7, 9) + '-' + value.slice(9, 11);
                } else if (value.length >= 8) {
                    value = '+7 (' + value.slice(1, 4) + ') ' + value.slice(4, 7) + '-' + value.slice(7, 9);
                } else if (value.length >= 5) {
                    value = '+7 (' + value.slice(1, 4) + ') ' + value.slice(4, 7);
                } else if (value.length >= 2) {
                    value = '+7 (' + value.slice(1, 4);
                } else {
                    value = '+7';
                }
            }
            e.target.value = value;

            // Валидация количества цифр
            const digits = value.replace(/\D/g, '');
            if (digits.length > 0 && (digits.length < 10 || digits.length > 15)) {
                e.target.classList.add('error');
                e.target.classList.remove('success');
            } else if (digits.length >= 10 && digits.length <= 15) {
                e.target.classList.add('success');
                e.target.classList.remove('error');
            } else {
                e.target.classList.remove('success', 'error');
            }
        });
    }

    // Валидация индекса
    const zipInput = document.getElementById('zip_code');
    if (zipInput) {
        zipInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 6) {
                value = value.slice(0, 6);
            }
            e.target.value = value;

            if (value.length > 0 && value.length !== 6) {
                e.target.classList.add('error');
                e.target.classList.remove('success');
            } else if (value.length === 6) {
                e.target.classList.add('success');
                e.target.classList.remove('error');
            } else {
                e.target.classList.remove('success', 'error');
            }
        });
    }

    // Валидация паролей в реальном времени
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('password_confirm');

    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const value = this.value;
            if (value.length > 0 && value.length < 6) {
                this.classList.add('error');
                this.classList.remove('success');
            } else if (value.length >= 6) {
                this.classList.add('success');
                this.classList.remove('error');
            } else {
                this.classList.remove('success', 'error');
            }

            // Перепроверяем подтверждение
            if (confirmInput && confirmInput.value) {
                if (confirmInput.value === this.value && this.value.length >= 6) {
                    confirmInput.classList.add('success');
                    confirmInput.classList.remove('error');
                } else {
                    confirmInput.classList.add('error');
                    confirmInput.classList.remove('success');
                }
            }
        });
    }

    if (confirmInput) {
        confirmInput.addEventListener('input', function() {
            if (passwordInput && this.value) {
                if (this.value === passwordInput.value && passwordInput.value.length >= 6) {
                    this.classList.add('success');
                    this.classList.remove('error');
                } else {
                    this.classList.add('error');
                    this.classList.remove('success');
                }
            } else {
                this.classList.remove('success', 'error');
            }
        });
    }

    initEditProfilePasswordToggles();
}

// Инициализация кнопок глаза для формы редактирования профиля (использует глобальную функцию)
function initEditProfilePasswordToggles() {
    const passwordWrappers = document.querySelectorAll('.password-wrapper');
    passwordWrappers.forEach(wrapper => {
        const input = wrapper.querySelector('input[type="password"], input[type="text"]');
        if (input) {
            initPasswordToggle(input.id);
        }
    });
}

// Инициализация валидации для edit_profile.php при загрузке DOM
document.addEventListener('DOMContentLoaded', function() {
    initEditProfileValidation();
});