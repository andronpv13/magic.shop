document.addEventListener('DOMContentLoaded', function() {
    // Username validation
    const usernameInput = document.getElementById('username');
    if (usernameInput) {
        usernameInput.addEventListener('input', function() {
            validateUsername(this.value);
        });
    }
    
    // Email validation
    const emailInput = document.getElementById('email');
    if (emailInput) {
        emailInput.addEventListener('input', function() {
            validateEmail(this.value);
        });
    }
    
    // Password validation
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    if (passwordInput && confirmPasswordInput) {
        passwordInput.addEventListener('input', function() {
            validatePassword(this.value, confirmPasswordInput.value);
        });
        
        confirmPasswordInput.addEventListener('input', function() {
            validatePassword(passwordInput.value, this.value);
        });
    }
});

function validateUsername(username) {
    const feedback = document.getElementById('username-feedback');
    if (!feedback) return;
    
    if (username.length < 3) {
        feedback.textContent = 'Логин должен содержать минимум 3 символа';
        feedback.className = 'invalid';
        return false;
    }
    
    fetch('/users/check_user.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `username=${username}&csrf_token=${document.querySelector('meta[name="csrf-token"]').content}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.exists) {
            feedback.textContent = 'Этот логин уже используется';
            feedback.className = 'invalid';
        } else {
            feedback.textContent = '✓ Логин доступен';
            feedback.className = 'valid';
        }
    });
    
    return true;
}

function validateEmail(email) {
    const feedback = document.getElementById('email-feedback');
    if (!feedback) return;
    
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        feedback.textContent = 'Введите корректный email';
        feedback.className = 'invalid';
        return false;
    }
    
    fetch('/users/check_user.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `email=${email}&csrf_token=${document.querySelector('meta[name="csrf-token"]').content}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.exists) {
            feedback.textContent = 'Этот email уже используется';
            feedback.className = 'invalid';
        } else {
            feedback.textContent = '✓ Email доступен';
            feedback.className = 'valid';
        }
    });
    
    return true;
}

function validatePassword(password, confirmPassword) {
    const passwordFeedback = document.getElementById('password-feedback');
    const confirmFeedback = document.getElementById('confirm-password-feedback');
    
    if (!passwordFeedback || !confirmFeedback) return;
    
    if (password.length < 8) {
        passwordFeedback.textContent = 'Пароль должен содержать минимум 8 символов';
        passwordFeedback.className = 'invalid';
        return false;
    }
    
    if (password !== confirmPassword) {
        confirmFeedback.textContent = 'Пароли не совпадают';
        confirmFeedback.className = 'invalid';
        return false;
    }
    
    passwordFeedback.textContent = '✓ Пароль соответствует требованиям';
    passwordFeedback.className = 'valid';
    confirmFeedback.textContent = '✓ Пароли совпадают';
    confirmFeedback.className = 'valid';
    
    return true;
}
