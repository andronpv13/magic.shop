document.addEventListener('DOMContentLoaded', function() {
    // Update basket count on page load
    updateBasketCount();
    
    // Add to basket
    const addToBasketButtons = document.querySelectorAll('.add-to-basket');
    addToBasketButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.dataset.productId;
            const quantity = parseInt(this.dataset.quantity) || 1;
            
            fetch('/basket/add_basket.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&quantity=${quantity}&csrf_token=${document.querySelector('meta[name="csrf-token"]').content}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateBasketCount();
                    showNotification(data.message, 'success');
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                showNotification('Ошибка при добавлении в корзину', 'error');
            });
        });
    });
    
    // Remove from basket
    const removeFromBasketButtons = document.querySelectorAll('.remove-from-basket');
    removeFromBasketButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            
            fetch('/basket/remove_basket.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&csrf_token=${document.querySelector('meta[name="csrf-token"]').content}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateBasketCount();
                    updateBasketTotal(data.basket_total);
                    this.closest('.basket-item').remove();
                    showNotification(data.message, 'success');
                } else {
                    showNotification(data.message, 'error');
                }
            })
        });
    });
    
    // Update basket quantity
    const quantityInputs = document.querySelectorAll('.quantity-input');
    quantityInputs.forEach(input => {
        input.addEventListener('change', function() {
            const productId = this.dataset.productId;
            const quantity = parseInt(this.value) || 1;
            
            fetch('/basket/update_basket.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&quantity=${quantity}&csrf_token=${document.querySelector('meta[name="csrf-token"]').content}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateBasketCount();
                    updateBasketTotal(data.basket_total);
                    updateItemTotal(data.item_total, productId);
                    showNotification(data.message, 'success');
                } else {
                    showNotification(data.message, 'error');
                }
            })
        });
    });
    
    // Increase quantity
    const increaseButtons = document.querySelectorAll('.increase-quantity');
    increaseButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const input = document.querySelector(`.quantity-input[data-product-id="${productId}"]`);
            const newQuantity = parseInt(input.value) + 1;
            input.value = newQuantity;
            input.dispatchEvent(new Event('change'));
        });
    });
    
    // Decrease quantity
    const decreaseButtons = document.querySelectorAll('.decrease-quantity');
    decreaseButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const input = document.querySelector(`.quantity-input[data-product-id="${productId}"]`);
            const newQuantity = Math.max(1, parseInt(input.value) - 1);
            input.value = newQuantity;
            input.dispatchEvent(new Event('change'));
        });
    });
});

function updateBasketCount() {
    fetch('/basket/get_basket_count.php')
    .then(response => response.json())
    .then(data => {
        document.getElementById('basket-count').textContent = data.count;
    });
}

function updateBasketTotal(total) {
    document.querySelector('.basket-total').textContent = formatPrice(total);
}

function updateItemTotal(total, productId) {
    const itemTotalElement = document.querySelector(`.item-total[data-product-id="${productId}"]`);
    if (itemTotalElement) {
        itemTotalElement.textContent = formatPrice(total);
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
