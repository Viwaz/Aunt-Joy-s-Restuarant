// Utility function to escape HTML
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// Load categories on page load
async function loadCategories() {
    try {
        const response = await fetch('../../../api/menu_api.php?action=categories');
        const data = await response.json();

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

// Load meals by category or all
async function loadMeals(categoryId = null) {
    try {
        let url = '../../../api/menu_api.php?action=meals';
        if (categoryId) {
            url += '&category_id=' + categoryId;
        }

        const response = await fetch(url);
        const data = await response.json();

        if (!data.success) {
            alert('Failed to load meals');
            return;
        }

        const mealsContainer = document.getElementById('meals-grid');
        mealsContainer.innerHTML = '';

        if (data.meals.length === 0) {
            mealsContainer.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: #999;">No meals available</p>';
            return;
        }

        data.meals.forEach(meal => {
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
            mealsContainer.appendChild(card);
        });

    } catch (error) {
        console.error('Error loading meals:', error);
        alert('Error loading meals');
    }
}

// Search meals
async function searchMeals(event) {
    event.preventDefault();

    const keyword = document.getElementById('search-keyword').value.trim();

    if (!keyword) {
        alert('Please enter a search term');
        return;
    }

    try {
        const response = await fetch(`../../../api/menu_api.php?action=search&keyword=${encodeURIComponent(keyword)}`);
        const data = await response.json();

        if (!data.success) {
            alert('Search failed');
            return;
        }

        const mealsContainer = document.getElementById('meals-grid');
        mealsContainer.innerHTML = '';

        if (data.meals.length === 0) {
            mealsContainer.innerHTML = `<p style="grid-column: 1/-1; text-align: center; color: #999;">No meals found matching "${escapeHtml(keyword)}"</p>`;
            return;
        }

        data.meals.forEach(meal => {
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
            mealsContainer.appendChild(card);
        });

    } catch (error) {
        console.error('Error searching meals:', error);
        alert('Error searching meals');
    }
}

// Add meal to cart
async function addToCart(mealId) {
    const quantity = parseInt(document.getElementById(`qty-${mealId}`).value) || 1;

    try {
        const response = await fetch('../../../api/add_to_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ menu_item_id: mealId, quantity })
        });

        const data = await response.json();

        if (data.success) {
            alert('Item added to cart!');
            updateCartCount();
        } else {
            alert('Error: ' + (data.message || 'Failed to add to cart'));
        }
    } catch (error) {
        console.error('Error adding to cart:', error);
        alert('Error adding to cart');
    }
}

// Update cart count in header
async function updateCartCount() {
    try {
        const response = await fetch('../../../api/cart_count.php');
        const data = await response.json();
        document.getElementById('cart-count').textContent = data.count || '0';
    } catch (error) {
        console.error('Error updating cart count:', error);
    }
}

// Format number with thousands separator
function number_format(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

// Load on page load
document.addEventListener('DOMContentLoaded', function() {
    loadCategories();
    loadMeals();
    updateCartCount();
});
