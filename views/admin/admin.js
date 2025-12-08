// ===== MODAL & UI MANAGEMENT =====
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'flex';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = "none";
    }
}

function showSection(sectionId) {
    document.getElementById('overview-section').style.display = 'none';
    document.getElementById('meals-section').style.display = 'none';
    document.getElementById('users-section').style.display = 'none';

    document.querySelectorAll('.sidebar a').forEach(a => a.classList.remove('active'));

    document.getElementById(sectionId).style.display = 'block';
    document.querySelector(`.sidebar a[href="#${sectionId}"]`).classList.add('active');
}

// ===== MEAL MANAGEMENT =====
async function loadMeals() {
    try {
        const data = await ApiClient.getMeals();

        if (!data.success) {
            alert('Failed to load meals');
            return;
        }

        const tbody = document.querySelector('#meals-table tbody');
        tbody.innerHTML = '';

        data.meals.forEach(meal => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><img src="../../menu/${meal.image || 'placeholder.jpg'}" class="meal-img" alt="Food" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px;"></td>
                <td>
                    <strong>${escapeHtml(meal.name)}</strong><br>
                    <small style="color:#888;">${escapeHtml(meal.description).substring(0, 30)}...</small>
                </td>
                <td>${escapeHtml(meal.category_name)}</td>
                <td>MWK${escapeHtml(meal.price_MWK)}</td>
                <td>
                    <button class="status-btn" onclick="toggleMealAvailability(${meal.id}, '${meal.availability}')">
                        <span class="badge ${meal.availability === 'in_stock' ? 'badge-success' : 'badge-danger'}">
                            ${meal.availability === 'in_stock' ? 'In Stock' : 'Out of Stock'}
                        </span>
                    </button>
                </td>
                <td>
                    <button class="btn-sm btn-delete" onclick="deleteMeal(${meal.id})">Deactivate</button>
                    <button class="btn-sm btn-edit" onclick="editMeal(${meal.id})">Edit</button>
                </td>
            `;
            tbody.appendChild(row);
        });

        document.querySelector('#meals-section .section-header h2').textContent = `Current Menu Items(${data.meals.length})`;
        document.querySelector('#overview-section .stat-card p#total-meals').textContent = `${data.meals.length}`;
    } catch (error) {
        console.error('Error loading meals:', error);
        alert('Error loading meals');
    }
}

async function addMeal(event) {
    event.preventDefault();

    const name = document.getElementById("name").value;
    const description = document.getElementById("description").value;
    const price = document.getElementById("price").value;
    const category = document.getElementById("category").value;
    const imageInput = document.getElementById('image');

    try {
        const mealData = { name, description, price, category };
        if (imageInput?.files?.length > 0) {
            mealData.image = imageInput.files[0];
        }

        const data = await ApiClient.createMeal(mealData);

        if (data.success) {
            alert('Meal added successfully!');
            closeModal('mealModal');
            document.getElementById('mealForm').reset();
            loadMeals();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error adding meal:', error);
        alert('Error adding meal');
    }
}

async function deleteMeal(mealId) {
    if (!confirm('Deactivate this meal?')) return;

    try {
        const data = await ApiClient.deleteMeal(mealId);

        if (data.success) {
            alert('Meal deactivated');
            loadMeals();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error deactivating meal:', error);
        alert('Error deactivating meal');
    }
}

async function toggleMealAvailability(mealId, currentStatus) {
    try {
        const data = await ApiClient.toggleMealAvailability(mealId, currentStatus);

        if (data.success) {
            loadMeals();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error toggling availability:', error);
        alert('Error updating status');
    }
}

async function editMeal(mealId) {
    try {
        const rows = document.querySelectorAll('#meals-table tbody tr');
        let mealData = null;

        for (let row of rows) {
            const cells = row.querySelectorAll('td');
            if (cells.length >= 6) {
                const actionCell = cells[5];
                const delBtn = actionCell.querySelector('.btn-delete');
                if (delBtn && delBtn.onclick.toString().includes(mealId)) {
                    const nameFull = cells[1].innerText.split('\n')[0];
                    const categoryName = cells[2].innerText;
                    const priceText = cells[3].innerText.replace('MWK', '').trim();
                    const description = cells[1].innerText.split('\n').slice(1).join('\n') || '';

                    mealData = { id: mealId, name: nameFull, description, price: priceText, category: categoryName };
                    break;
                }
            }
        }

        if (!mealData) {
            alert('Could not load meal details');
            return;
        }

        document.getElementById('edit-meal-id').value = mealData.id;
        document.getElementById('edit-name').value = mealData.name;
        document.getElementById('edit-description').value = mealData.description;
        document.getElementById('edit-price').value = mealData.price;
        document.getElementById('edit-category').value = mealData.category;
        document.getElementById('edit-image').value = '';

        openModal('editMealModal');
    } catch (error) {
        console.error('Error loading meal for edit:', error);
        alert('Error loading meal details');
    }
}

async function saveMealEdit(event) {
    event.preventDefault();

    const mealId = document.getElementById('edit-meal-id').value;
    const name = document.getElementById('edit-name').value;
    const description = document.getElementById('edit-description').value;
    const price = document.getElementById('edit-price').value;
    const category = document.getElementById('edit-category').value;
    const imageInput = document.getElementById('edit-image');

    try {
        const mealData = { id: mealId, name, description, price, category };
        if (imageInput?.files?.length > 0) {
            mealData.image = imageInput.files[0];
        }

        const data = await ApiClient.updateMeal(mealData);

        if (data.success) {
            alert('Meal updated successfully!');
            closeModal('editMealModal');
            document.getElementById('editMealForm').reset();
            loadMeals();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error updating meal:', error);
        alert('Error updating meal');
    }
}

// ===== USER MANAGEMENT =====
async function loadUsers() {
    try {
        const data = await ApiClient.getUsers();

        if (!data.success) {
            alert('Failed to load users');
            return;
        }

        const tbody = document.querySelector('#users-table tbody');
        tbody.innerHTML = '';

        data.users.forEach(user => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${escapeHtml(user.username)}</td>
                <td>${escapeHtml(user.email)}</td>
                <td><span style="text-transform: capitalize; font-weight:bold; color: #555;">${escapeHtml(user.role)}</span></td>
                <td>${user.created_at}</td>
                <td>
                    <button class="btn-sm btn-delete" onclick="deleteUser(${user.id})">Deactivate</button>
                </td>
            `;
            tbody.appendChild(row);
        });

        document.querySelector('#users-section .section-header h2').textContent = `System Users(${data.users.length})`;
        document.querySelector('#overview-section .stat-card p#total-users').textContent = `${data.users.length}`;
    } catch (error) {
        console.error('Error loading users:', error);
        alert('Error loading users');
    }
}

async function addUser(event) {
    event.preventDefault();

    const username = document.getElementById('username').value;
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const role = document.getElementById('role').value;

    try {
        const data = await ApiClient.createUser({ username, email, password, role });

        if (data.success) {
            alert('User created successfully!');
            closeModal('userModal');
            document.getElementById('userForm').reset();
            loadUsers();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error creating user:', error);
        alert('Error creating user');
    }
}

async function deleteUser(userId) {
    if (!confirm('Deactivate this user? They will no longer be able to login.')) return;

    try {
        const data = await ApiClient.deleteUser(userId);

        if (data.success) {
            alert('User deactivated');
            loadUsers();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error deactivating user:', error);
        alert('Error deactivating user');
    }
}

// ===== UTILITY =====
function escapeHtml(text) {
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// ===== INITIALIZATION =====
document.addEventListener('DOMContentLoaded', function() {
    loadMeals();
    loadUsers();
});
