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
        // Проверка на наличие пробелов и tab в подтверждении пароля
        const hasSpacesOrTab = /[\s\t]/.test(confirm);
        const isValid = confirm.length > 0 && !hasSpacesOrTab && pass === confirm;

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

// Инициализация валидации для страницы редактирования профиля (edit_profile.php, edit_cab.php и edit_cab_md.php)
function initEditProfileValidation() {
    // Поддержка всех форм: editProfileForm (пользователь), editCabForm (администратор), editCabMdForm (модератор)
    const form = document.getElementById('editProfileForm') || document.getElementById('editCabForm') || document.getElementById('editCabMdForm');
    const saveBtn = document.getElementById('saveBtn');

    if (!form) return;

    // Элементы формы
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('password_confirm') || document.getElementById('confirm_password');
    const currentPasswordInput = document.getElementById('current_password');
    const phoneInput = document.getElementById('phone');
    const zipInput = document.getElementById('zip_code');

    let validationState = {
        username: true,
        email: true,
        password: true,
        confirm: true,
        phone: true,
        zip: true
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

    // Обновление состояния кнопки сохранения
    function updateSubmitButton() {
        const isValid = Object.values(validationState).every(Boolean);
        if (saveBtn) {
            saveBtn.disabled = !isValid;
        }
    }

    // Установка статуса валидации для поля (используем глобальную функцию если есть)
    function setFieldStatus(inputElement, isValid, errorClass = 'error', successClass = 'success') {
        if (!inputElement) return;

        // Удаляем предыдущие классы
        inputElement.classList.remove(errorClass, successClass);

        // Добавляем новый статус
        if (isValid && inputElement.value.trim() !== '') {
            inputElement.classList.add(successClass);
        } else if (!isValid && inputElement.value.trim() !== '') {
            inputElement.classList.add(errorClass);
        } else {
            inputElement.classList.remove(successClass, errorClass);
        }
    }

    // --- Валидация логина (с AJAX проверкой уникальности) ---
    const validateUsername = debounce(async () => {
        if (!usernameInput) return;

        const value = usernameInput.value.trim();
        const regex = /^[a-zA-Zа-яА-ЯёЁ]{4,10}$/;

        if (!regex.test(value)) {
            validationState.username = false;
            setFieldStatus(usernameInput, false);
            updateSubmitButton();
            return;
        }

        // AJAX проверка уникальности
        try {
            const response = await fetch((window.apiBaseUrl || '../') + 'users/check_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({ type: 'username', value: value })
            });
            const data = await response.json();

            validationState.username = data.available === true;
            setFieldStatus(usernameInput, validationState.username);
        } catch (e) {
            validationState.username = false;
            setFieldStatus(usernameInput, false);
        }
        updateSubmitButton();
    }, 500);

    // --- Валидация Email (с AJAX проверкой уникальности) ---
    const validateEmail = debounce(async () => {
        if (!emailInput) return;

        const value = emailInput.value.trim();
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!regex.test(value)) {
            validationState.email = false;
            setFieldStatus(emailInput, false);
            updateSubmitButton();
            return;
        }

        // AJAX проверка уникальности
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

    // --- Валидация телефона (форматирование и проверка длины) ---
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
                validationState.phone = false;
                setFieldStatus(phoneInput, false);
            } else if (digits.length >= 10 && digits.length <= 15) {
                validationState.phone = true;
                setFieldStatus(phoneInput, true);
            } else {
                validationState.phone = true;
                phoneInput.classList.remove('success', 'error');
            }
            updateSubmitButton();
        });
    }

    // --- Валидация индекса (6 цифр) ---
    if (zipInput) {
        zipInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 6) {
                value = value.slice(0, 6);
            }
            e.target.value = value;

            if (value.length > 0 && value.length !== 6) {
                validationState.zip = false;
                setFieldStatus(zipInput, false);
            } else if (value.length === 6) {
                validationState.zip = true;
                setFieldStatus(zipInput, true);
            } else {
                validationState.zip = true;
                zipInput.classList.remove('success', 'error');
            }
            updateSubmitButton();
        });
    }

    // --- Валидация текущего пароля (AJAX проверка + проверка на пробелы/tab) ---
    const validateCurrentPassword = debounce(async () => {
        if (!currentPasswordInput) return;

        const value = currentPasswordInput.value;

        // Если поле пустое - не показываем ошибку (поле необязательное)
        if (value.trim() === '') {
            currentPasswordInput.classList.remove('success', 'error');
            return;
        }

        // Проверка на наличие пробелов и tab
        if (/[\s\t]/.test(value)) {
            setFieldStatus(currentPasswordInput, false);
            return;
        }

        // AJAX проверка текущего пароля
        try {
            const response = await fetch((window.apiBaseUrl || '../') + 'users/check_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({ type: 'current_password', value: value })
            });
            const data = await response.json();

            if (data.valid === true) {
                setFieldStatus(currentPasswordInput, true);
            } else {
                setFieldStatus(currentPasswordInput, false);
            }
        } catch (e) {
            setFieldStatus(currentPasswordInput, false);
        }
    }, 500);

    // --- Функция проверки схожести паролей ---
    function arePasswordsSimilar(newPass, currentPass) {
        if (!newPass || !currentPass) return false;

        // Нормализуем пароли (приводим к нижнему регистру)
        const newLower = newPass.toLowerCase();
        const currLower = currentPass.toLowerCase();

        // Если пароли идентичны - это похоже
        if (newLower === currLower) return true;

        // Проверка: новый пароль содержит текущий как подстроку
        if (currLower.length >= 4 && newLower.includes(currLower)) return true;

        // Проверка: текущий пароль содержит новый как подстроку
        if (newLower.length >= 4 && currLower.includes(newLower)) return true;

        // Проверка по Levenshtein distance (расстояние редактирования)
        // Если расстояние меньше 30% от длины более короткого пароля - считаем похожими
        const len1 = newLower.length;
        const len2 = currLower.length;
        const minLen = Math.min(len1, len2);

        if (minLen === 0) return false;

        // Вычисляем расстояние Левенштейна
        const matrix = [];
        for (let i = 0; i <= len1; i++) {
            matrix[i] = [i];
        }
        for (let j = 0; j <= len2; j++) {
            matrix[0][j] = j;
        }
        for (let i = 1; i <= len1; i++) {
            for (let j = 1; j <= len2; j++) {
                const cost = newLower[i - 1] === currLower[j - 1] ? 0 : 1;
                matrix[i][j] = Math.min(
                    matrix[i - 1][j] + 1,      // удаление
                    matrix[i][j - 1] + 1,      // вставка
                    matrix[i - 1][j - 1] + cost // замена
                );
            }
        }

        const distance = matrix[len1][len2];
        const threshold = Math.floor(minLen * 0.3); // 30% порог

        return distance <= threshold && distance > 0;
    }

    // --- Валидация пароля ---
    const validatePassword = () => {
        if (!passwordInput) return;

        const value = passwordInput.value;

        // Проверка на наличие пробелов и tab
        if (/[\s\t]/.test(value)) {
            validationState.password = false;
            setFieldStatus(passwordInput, false);
        } else if (value.length > 0 && value.length < 6) {
            validationState.password = false;
            setFieldStatus(passwordInput, false);
        } else if (value.length >= 6) {
            // Дополнительная проверка: если введен текущий пароль, проверяем схожесть
            if (currentPasswordInput && currentPasswordInput.value.trim() !== '') {
                if (arePasswordsSimilar(value, currentPasswordInput.value)) {
                    validationState.password = false;
                    setFieldStatus(passwordInput, false);
                    return;
                }
            }
            validationState.password = true;
            setFieldStatus(passwordInput, true);
        } else {
            validationState.password = true;
            passwordInput.classList.remove('success', 'error');
        }

        // Перепроверяем подтверждение
        if (confirmInput && confirmInput.value) {
            validateConfirmPassword();
        }

        updateSubmitButton();
    };

    // --- Валидация подтверждения пароля ---
    const validateConfirmPassword = () => {
        if (!confirmInput || !passwordInput) return;

        const pass = passwordInput.value;
        const confirm = confirmInput.value;

        // Проверка на наличие пробелов и tab в подтверждении пароля
        if (/[\s\t]/.test(confirm)) {
            validationState.confirm = false;
            setFieldStatus(confirmInput, false);
        } else if (confirm.length > 0 && pass !== confirm) {
            validationState.confirm = false;
            setFieldStatus(confirmInput, false);
        } else if (confirm.length > 0 && pass === confirm && pass.length >= 6) {
            validationState.confirm = true;
            setFieldStatus(confirmInput, true);
        } else {
            validationState.confirm = true;
            confirmInput.classList.remove('success', 'error');
        }

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

    if (confirmInput) {
        confirmInput.addEventListener('input', validateConfirmPassword);
        initPasswordToggle(confirmInput.id);
    }

    if (currentPasswordInput) {
        currentPasswordInput.addEventListener('input', validateCurrentPassword);
        initPasswordToggle('current_password');
    }

    // Инициализация состояния кнопки
    updateSubmitButton();
}

// Экспортируем функцию глобально для использования на страницах редактирования профиля
if (typeof window.initEditProfileValidationExport !== 'function') {
    window.initEditProfileValidationExport = true;
    window.initEditProfileValidation = initEditProfileValidation;
}

// Автоматическая инициализация валидации для страниц редактирования профиля
document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.initEditProfileValidation === 'function') {
        window.initEditProfileValidation();
    }
});