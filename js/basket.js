document.addEventListener('DOMContentLoaded', function() {
    // Update basket count on page load
    updateBasketCount();
    
    // Add to basket
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('add-to-basket')) {
            e.preventDefault();
            console.log('Add to basket button clicked');
            
            const button = e.target;
            const productId = button.dataset.productId;
            const quantity = parseInt(button.dataset.quantity) || 1;
            
            console.log('Product ID:', productId, 'Quantity:', quantity);
            
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                console.error('CSRF token not found');
                showNotification('Ошибка безопасности: CSRF токен не найден', 'error');
                return;
            }
            
            fetch('/basket/add_basket.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&quantity=${quantity}&csrf_token=${csrfToken.content}`
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    updateBasketCount();
                    showNotification(data.message, 'success');
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Ошибка при добавлении в корзину', 'error');
            });
        }
    });
    
    // Remove from basket
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-from-basket')) {
            e.preventDefault();
            const button = e.target;
            const productId = button.dataset.productId;
            const basketItem = button.closest('.basket-item');
            
            console.log('Remove from basket button clicked for product:', productId);
            
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                console.error('CSRF token not found');
                showNotification('Ошибка безопасности: CSRF токен не найден', 'error');
                return;
            }
            
            fetch('/basket/remove_basket.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&csrf_token=${csrfToken.content}`
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    // Удаляем элемент DOM
                    if (basketItem) {
                        basketItem.remove();
                    }
                    
                    // Обновляем счетчик и общую сумму
                    updateBasketCount();
                    updateBasketTotal(data.basket_total);
                    
                    // Если корзина пуста, перезагружаем страницу
                    if (document.querySelectorAll('.basket-item').length === 0) {
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    }
                    
                    showNotification(data.message, 'success');
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Ошибка при удалении из корзины', 'error');
            });
        }
    });
    
    // Update basket quantity
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('quantity-input')) {
            const input = e.target;
            const productId = input.dataset.productId;
            const quantity = parseInt(input.value) || 1;
            
            console.log('Update quantity for product:', productId, 'New quantity:', quantity);
            
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                console.error('CSRF token not found');
                showNotification('Ошибка безопасности: CSRF токен не найден', 'error');
                return;
            }
            
            fetch('/basket/update_basket.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&quantity=${quantity}&csrf_token=${csrfToken.content}`
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    updateBasketCount();
                    updateBasketTotal(data.basket_total);
                    updateItemTotal(data.item_total, productId);
                    showNotification(data.message, 'success');
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Ошибка при обновлении корзины', 'error');
            });
        }
    });
    
    // Increase quantity
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('increase-quantity')) {
            const button = e.target;
            const productId = button.dataset.productId;
            const input = document.querySelector(`.quantity-input[data-product-id="${productId}"]`);
            
            if (input) {
                const newQuantity = parseInt(input.value) + 1;
                input.value = newQuantity;
                input.dispatchEvent(new Event('change'));
            }
        }
    });
    
    // Decrease quantity
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('decrease-quantity')) {
            const button = e.target;
            const productId = button.dataset.productId;
            const input = document.querySelector(`.quantity-input[data-product-id="${productId}"]`);
            
            if (input) {
                const currentValue = parseInt(input.value);
                const newQuantity = Math.max(1, currentValue - 1);
                input.value = newQuantity;
                input.dispatchEvent(new Event('change'));
            }
        }
    });
});

function updateBasketCount() {
    const basketCountElement = document.getElementById('basket-count');
    if (!basketCountElement) {
        console.error('Basket count element not found');
        return;
    }
    
    // Если мы на странице корзины, обновляем количество на основе элементов в корзине
    const basketItems = document.querySelectorAll('.basket-item');
    if (basketItems.length > 0) {
        basketCountElement.textContent = basketItems.length;
        console.log('Basket count updated from DOM:', basketItems.length);
    }
}

function updateBasketTotal(total) {
    const basketTotalElement = document.querySelector('.basket-total');
    if (basketTotalElement) {
        basketTotalElement.textContent = 'Итого: ' + formatPrice(total);
        console.log('Basket total updated:', total);
    } else {
        console.error('Basket total element not found');
    }
}

function updateItemTotal(total, productId) {
    const itemTotalElement = document.querySelector(`.item-total[data-product-id="${productId}"]`);
    if (itemTotalElement) {
        itemTotalElement.textContent = formatPrice(total);
        console.log('Item total updated for product', productId, ':', total);
    } else {
        console.error('Item total element not found for product:', productId);
    }
}

function formatPrice(price) {
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB'
    }).format(price);
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}
