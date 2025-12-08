// ===== UTILITY =====
function escapeHtml(text) {
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.replace(/[&<>"']/g, m => map[m]);
}

function number_format(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

// ===== CATEGORY MANAGEMENT =====
async function loadCategories() {
    try {
        const data = await ApiClient.getCategories();

        if (!data.success) {
            console.error('Failed to load categories');
            return;
        }

        const categoryList = document.getElementById('category-list');
        categoryList.innerHTML = '<a href="#" class="category-link active" onclick="loadMeals(); return false;">All Categories</a>';

        data.categories.forEach(cat => {
            const link = document.createElement('a');
            link.href = '#';
            link.className = 'category-link';
            link.textContent = cat.category_name;
            link.onclick = function(e) {
                e.preventDefault();
                loadMeals(cat.id);
                updateActiveCategory(this);
            };
            categoryList.appendChild(link);
        });

    } catch (error) {
        console.error('Error loading categories:', error);
    }
}

function updateActiveCategory(element) {
    document.querySelectorAll('.category-link').forEach(el => el.classList.remove('active'));
    element.classList.add('active');
}

// ===== MEAL LOADING & RENDERING =====
async function loadMeals(categoryId = null) {
    try {
        const data = await ApiClient.getMeals(categoryId);

        if (!data.success) {
            showNotification('Failed to load meals', 'error');
            return;
        }

        const mealsContainer = document.getElementById('meals-grid');
        mealsContainer.innerHTML = '';

        if (data.meals.length === 0) {
            mealsContainer.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: #999;">No meals available</p>';
            return;
        }

        data.meals.forEach(meal => renderMealCard(meal, mealsContainer));

    } catch (error) {
        console.error('Error loading meals:', error);
        showNotification('Error loading meals', 'error');
    }
}

function renderMealCard(meal, container) {
    const card = document.createElement('div');
    card.className = 'meal-card';
    card.innerHTML = `
        <img src="../../../menu/${escapeHtml(meal.image || 'placeholder.jpg')}" alt="${escapeHtml(meal.name)}" class="meal-img">
        <h3>${escapeHtml(meal.name)}</h3>
        <p class="meal-desc">${escapeHtml(meal.description)}</p>
        <p class="meal-category"><small>${escapeHtml(meal.category_name)}</small></p>
        <p class="meal-price">MWK ${number_format(meal.price_MWK)}</p>
        <div class="meal-actions">
            <input type="number" class="qty-input" value="1" min="1" max="10" id="qty-${meal.id}">
            <button class="btn-add-cart" onclick="addToCart(${meal.id})">Add to Cart</button>
        </div>
    `;
    container.appendChild(card);
}

// ===== SEARCH =====
async function searchMeals(event) {
    event.preventDefault();

    const keyword = document.getElementById('search-keyword').value.trim();

    if (!keyword) {
        showNotification('Please enter a search term', 'warning');
        return;
    }

    try {
        const data = await ApiClient.searchMeals(keyword);

        if (!data.success) {
            showNotification('Search failed', 'error');
            return;
        }

        const mealsContainer = document.getElementById('meals-grid');
        mealsContainer.innerHTML = '';

        if (data.meals.length === 0) {
            mealsContainer.innerHTML = `<p style="grid-column: 1/-1; text-align: center; color: #999;">No meals found matching "${escapeHtml(keyword)}"</p>`;
            return;
        }

        data.meals.forEach(meal => renderMealCard(meal, mealsContainer));

    } catch (error) {
        console.error('Error searching meals:', error);
        showNotification('Error searching meals', 'error');
    }
}

// ===== CART MANAGEMENT =====
async function addToCart(mealId) {
    const quantity = parseInt(document.getElementById(`qty-${mealId}`).value) || 1;

    try {
        const data = await ApiClient.addToCart(mealId, quantity);

        if (data.success) {
            showNotification('Item added to cart!', 'success');
            updateCartCount();
        } else {
            // Display specific error message from API
            showNotification(data.message || 'Failed to add to cart', 'error');
        }
    } catch (error) {
        console.error('Error adding to cart:', error);
        showNotification('Error adding to cart', 'error');
    }
}

async function updateCartCount() {
    try {
        const data = await ApiClient.getCartCount();
        document.getElementById('cart-count').textContent = data.count || '0';
    } catch (error) {
        console.error('Error updating cart count:', error);
    }
}

const popup = document.getElementById('notification-popup');
function showNotification(message, type = 'info', duration = 3000) {
    if (!popup) {
        console.error('Notification popup element not found');
        return;
    }
    popup.textContent = message;
    popup.className = 'notification-popup ' + type;
    popup.style.display = 'block';
    setTimeout(() => {
        popup.style.display = 'none';
    }, duration);
}

// ===== INITIALIZATION =====
document.addEventListener('DOMContentLoaded', function() {
    loadCategories();
    loadMeals();
    updateCartCount();
});
