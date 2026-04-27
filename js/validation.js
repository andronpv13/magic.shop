document.addEventListener('DOMContentLoaded', function() {
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');

    if (usernameInput) usernameInput.addEventListener('input', function() { validateField('username', this.value, 'username-feedback'); });
    if (emailInput) emailInput.addEventListener('input', function() { validateField('email', this.value, 'email-feedback'); });
    
    if (passwordInput && confirmPasswordInput) {
        const check = () => {
            if (passwordInput.value.length < 6) showFeedback('password-feedback', 'Минимум 6 символов', false);
            else if (passwordInput.value !== confirmPasswordInput.value) showFeedback('confirm-password-feedback', 'Пароли не совпадают', false);
            else { showFeedback('password-feedback', '✓', true); showFeedback('confirm-password-feedback', '✓', true); }
        };
        passwordInput.addEventListener('input', check);
        confirmPasswordInput.addEventListener('input', check);
    }
});

function validateField(type, value, feedbackId) {
    const feedback = document.getElementById(feedbackId);
    if (!feedback) return;
    if (value.length < 3) { showFeedback(feedbackId, 'Минимум 3 символа', false); return; }
    
    fetch('/users/check_user.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `type=${type}&value=${value}&csrf_token=${document.querySelector('meta[name="csrf-token"]').content}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.exists) showFeedback(feedbackId, 'Уже используется', false);
        else showFeedback(feedbackId, '✓ Доступен', true);
    });
}

function showFeedback(id, msg, isValid) {
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = msg;
    el.className = isValid ? 'valid' : 'invalid';
}