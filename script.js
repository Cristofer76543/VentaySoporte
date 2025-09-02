document.addEventListener('DOMContentLoaded', function() {
    // Variables y elementos
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    const cartButton = document.querySelector('.cart-button');
    const cartPopup = document.getElementById('cart-popup');
    const cartOverlay = document.getElementById('cart-overlay');
    const closeCartBtn = document.getElementById('close-cart');

    // --- Funciones carrito ---
    function openCart() {
        cartOverlay.classList.add('active');
        cartPopup.classList.add('active');
    }

    function closeCart() {
        cartPopup.classList.remove('active');
        cartOverlay.classList.remove('active');
    }

    function showNotification(message, isSuccess) {
        const notification = document.getElementById('notification');
        notification.textContent = message;
        notification.className = `notification ${isSuccess ? 'success' : 'error'}`;
        notification.style.display = 'block';
        setTimeout(() => {
            notification.style.display = 'none';
        }, 3000);
    }

    function saveCart() {
        localStorage.setItem('cart', JSON.stringify(cart));
    }

    function updateCart() {
        const cartItems = document.getElementById('cart-items');
        let total = 0;
        cartItems.innerHTML = '';

        cart.forEach((item, index) => {
            const itemTotal = item.price * item.quantity;
            total += itemTotal;

            const itemElement = document.createElement('div');
            itemElement.className = 'cart-product';
            itemElement.innerHTML = `
    <div class="cart-product-name">
        ${item.image ? `<img src="${item.image}" alt="${item.name}" style="max-width:50px; vertical-align:middle; margin-right:5px;">` : ''}
        <span class="product-category">[${item.category}]</span> ${item.name}
        ${item.configuration ? `<div class="config-details">Configuración: ${JSON.stringify(item.configuration)}</div>` : ''}
    </div>
    <div class="cart-product-price">$${item.price.toFixed(2)}</div>
    <div class="cart-product-quantity">
        <input type="number" min="1" value="${item.quantity}" data-index="${index}" class="cart-quantity-input" style="width: 50px;">
    </div>
    <div class="cart-product-total">$${itemTotal.toFixed(2)}</div>
    <div>
        <button class="remove-from-cart" data-index="${index}" title="Eliminar producto">X</button>
    </div>
`;
            cartItems.appendChild(itemElement);
        });

        document.getElementById('cart-total').textContent = total.toFixed(2);

        // Eventos eliminar producto
        document.querySelectorAll('.remove-from-cart').forEach(button => {
            button.addEventListener('click', function() {
                const idx = parseInt(this.dataset.index);
                if (!isNaN(idx)) {
                    cart.splice(idx, 1);
                    saveCart();
                    updateCart();
                    showNotification('Producto eliminado del carrito', true);
                }
            });
        });

        // Eventos cambio cantidad
        document.querySelectorAll('.cart-quantity-input').forEach(input => {
            input.addEventListener('change', function() {
                const idx = parseInt(this.dataset.index);
                let newQuantity = parseInt(this.value);
                if (isNaN(newQuantity) || newQuantity < 1) {
                    newQuantity = 1;
                    this.value = 1;
                }
                if (!isNaN(idx) && cart[idx]) {
                    cart[idx].quantity = newQuantity;
                    saveCart();
                    updateCart();
                    showNotification('Cantidad actualizada', true);
                }
            });
        });
    }

    // Inicializar carrito al cargar la página
    updateCart();

    // Eventos abrir/cerrar carrito
    cartButton.addEventListener('click', function(e) {
        e.stopPropagation();
        openCart();
    });

    closeCartBtn.addEventListener('click', closeCart);

    document.addEventListener('click', function(e) {
        if (!cartPopup.contains(e.target) && e.target !== cartButton) {
            closeCart();
        }
    });

    cartPopup.addEventListener('click', e => e.stopPropagation());

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && cartPopup.classList.contains('active')) {
            closeCart();
        }
    });

    // Limpiar carrito
    document.getElementById('clear-cart').addEventListener('click', function() {
        cart.length = 0;
        saveCart();
        updateCart();
        showNotification('Carrito limpiado', true);
    });

    // Añadir productos normales al carrito
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('add-to-cart')) {
            const button = e.target;
            const uniqueId = button.dataset.uniqueid;
            const id = button.dataset.id;
            const category = button.dataset.category;
            const price = parseFloat(button.dataset.price);
            const name = button.dataset.name;
            const quantityInput = document.getElementById(`quantity-input-${uniqueId}`);
            const quantity = parseInt(quantityInput ? quantityInput.value : 1) || 1;
            const maxQuantity = parseInt(button.dataset.quantity) || 1000;

            if (isNaN(quantity) || quantity < 1 || quantity > maxQuantity) {
                alert(`Cantidad debe ser entre 1 y ${maxQuantity}`);
                if(quantityInput) quantityInput.value = 1;
                return;
            }

            let configuration = null;

            const cartItemId = `${uniqueId}-${JSON.stringify(configuration)}`;

            const existingItem = cart.find(item => item.cartItemId === cartItemId);

            if (existingItem) {
                existingItem.quantity += quantity;
            } else {
                cart.push({
                    cartItemId,
                    uniqueId,
                    id,
                    category,
                    price,
                    quantity,
                    name,
                    configuration
                });
            }

            
            saveCart();
            updateCart();
            showNotification('Producto agregado al carrito', true);
        }
    });

    // --- Productos personalizados ---
const productPreview = document.getElementById('product-preview');
const componentTypeSelect = document.getElementById('component-type');
const customProductInfoDiv = document.createElement('div');
customProductInfoDiv.id = 'custom-product-info';
customProductInfoDiv.style.marginTop = '10px';
customProductInfoDiv.style.border = '1px solid #ccc';
customProductInfoDiv.style.padding = '10px';
customProductInfoDiv.style.display = 'none';
document.getElementById('custom-product-form').insertBefore(customProductInfoDiv, document.getElementById('custom-quantity'));

componentTypeSelect.addEventListener('change', function() {
    const selectedType = this.value;

    // Ocultar todas las secciones de configuración
    document.querySelectorAll('.config-section').forEach(section => {
        section.style.display = 'none';
    });

    // Mostrar la sección correspondiente
    if (selectedType === 'resistencia') {
        document.getElementById('resistencia-options').style.display = 'block';
    } else if (selectedType === 'conector') {
        document.getElementById('conector-options').style.display = 'block';
    } else if (selectedType === 'capacitor') {
        document.getElementById('capacitor-options').style.display = 'block';
    } else if (selectedType === 'terminal') {
        document.getElementById('terminal-options').style.display = 'block';
    }

    if (!selectedType) {
        customProductInfoDiv.style.display = 'none';
        customProductInfoDiv.innerHTML = '';
        return;
    }

    // Consultar info del producto base desde la BD
    fetch(`index.php?action=get_custom_product&tipo=${encodeURIComponent(selectedType)}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const product = data.product;
            productPreview.innerHTML = `
                <strong>Producto base:</strong> ${product.descripcion}<br>
                <img src="${product.imagen}" alt="${product.descripcion}" style="max-width:100px; display:block; margin: 5px 0;">
                <strong>Precio base:</strong> $${product.precio.toFixed(2)}
            `;
        } else {
            productPreview.innerHTML = `<p>No hay información disponible.</p>`;
        }
    })
    .catch(() => {
        productPreview.innerHTML = `<p>Error al cargar la información.</p>`;
    });
});

    const customModal = document.getElementById('custom-product-modal');
    const openCustomModalBtn = document.getElementById('open-custom-modal');
    const closeCustomModalBtn = document.getElementById('close-custom-modal');
    const customProductForm = document.getElementById('custom-product-form');

    openCustomModalBtn.addEventListener('click', () => {
        customModal.style.display = 'block';
    });

    closeCustomModalBtn.addEventListener('click', () => {
        customModal.style.display = 'none';
    });

    document.getElementById('component-type').addEventListener('change', function() {
        const selectedType = this.value;
        document.querySelectorAll('.config-section').forEach(section => {
            section.style.display = 'none';
        });

        if (selectedType === 'resistencia') {
            document.getElementById('resistencia-options').style.display = 'block';
        } else if (selectedType === 'conector') {
            document.getElementById('conector-options').style.display = 'block';
        } else if (selectedType === 'capacitor') {
            document.getElementById('capacitor-options').style.display = 'block';
        } else if (selectedType === 'terminal') {
            document.getElementById('terminal-options').style.display = 'block';
        }
    });

customProductForm.addEventListener('submit', function(e) {
    e.preventDefault();

    const componentType = componentTypeSelect.value;
    const quantity = parseInt(document.getElementById('custom-quantity').value);

    let configuration = {};

    if (componentType === 'resistencia') {
        const resistenciaValue = document.getElementById('resistencia-value').value;
        const resistenciaUnit = document.getElementById('resistencia-unit').value;
        configuration = { value: resistenciaValue + resistenciaUnit };
    } else if (componentType === 'conector') {
        const conectorColor = document.getElementById('conector-color').value;
        const viasConector = document.getElementById('vias-conector').value;
        configuration = { color: conectorColor, vias: viasConector };
    } else if (componentType === 'capacitor') {
        const capacitorValue = document.getElementById('capacitor-value').value;
        configuration = { value: capacitorValue };
    } else if (componentType === 'terminal') {
        const terminalType = document.getElementById('terminal-type').value;
        configuration = { terminalType: terminalType };
    }

    fetch(`index.php?action=get_custom_product&tipo=${encodeURIComponent(componentType)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const product = data.product;

                // ID único combinando id y configuración para distinguir productos con distinta configuración
                const cartItemId = `custom-${product.id}-${JSON.stringify(configuration)}`;

                const existingItem = cart.find(item => item.cartItemId === cartItemId);

                if (existingItem) {
                    existingItem.quantity += quantity;
                } else {
                    cart.push({
                        cartItemId,
                        uniqueId: product.id,
                        id: product.id,
                        category: 'custom',
                        price: product.precio,
                        quantity,
                        name: product.descripcion,
                        configuration,
                        image: product.imagen
                    });
                }

                saveCart();
                updateCart();
                customModal.style.display = 'none';
                customProductForm.reset();
                customProductInfoDiv.style.display = 'none';
                customProductInfoDiv.innerHTML = '';
                showNotification('¡Producto personalizado añadido!', true);
            } else {
                alert('Producto personalizado no encontrado en la base de datos.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Hubo un problema al obtener el producto personalizado.');
        });
});

    // --- Sugerencias ---
    const suggestionButton = document.getElementById('suggestion-button');
    const suggestionModal = document.getElementById('suggestion-modal');
    const closeSuggestionModal = suggestionModal.querySelector('.close-modal');
    const suggestionForm = document.getElementById('suggestion-form');

    suggestionButton.addEventListener('click', () => {
        suggestionModal.style.display = 'block';
    });

    closeSuggestionModal.addEventListener('click', () => {
        suggestionModal.style.display = 'none';
    });

    window.addEventListener('click', (event) => {
        if (event.target === suggestionModal) {
            suggestionModal.style.display = 'none';
        }
    });

    suggestionForm.addEventListener('submit', (event) => {
        event.preventDefault();
        const suggestionText = document.getElementById('suggestion-text').value;

        fetch('submit_suggestion.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ suggestion: suggestionText }),
        })
        .then(response => response.json())
        .then(data => {
            alert('Sugerencia enviada con éxito.');
            suggestionModal.style.display = 'none';
            suggestionForm.reset();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Hubo un problema al enviar tu sugerencia.');
        });
    });

    // --- Resaltado y scroll suave de categorías ---
    document.querySelectorAll('.category-item').forEach(item => {
        item.addEventListener('click', function() {
            const category = this.dataset.category;
            const section = document.getElementById(category);
            if (!section) return;

            const sectionPosition = section.getBoundingClientRect().top + window.pageYOffset - 20;

            window.scrollTo({
                top: sectionPosition,
                behavior: 'smooth'
            });
        });
    });
});