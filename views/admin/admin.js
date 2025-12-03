// Modal management
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

// Section switching
function showSection(sectionId) {
    document.getElementById('overview-section').style.display = 'none';
    document.getElementById('meals-section').style.display = 'none';
    document.getElementById('users-section').style.display = 'none';

    document.querySelectorAll('.sidebar a').forEach(a => a.classList.remove('active'));

    document.getElementById(sectionId).style.display = 'block';
    document.querySelector(`.sidebar a[href="#${sectionId}"]`).classList.add('active');
}

// ===== MEALS API =====
async function loadMeals() {
    try {
        
           ;  // /project/path/api/meals_api.php
        const response = await fetch('../../api/meals_api.php?action=list');
        const data = await response.json();

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
                    <button class="btn-sm btn-delete" onclick="deleteMeal(${meal.id})">Delete</button>
                    <button class="btn-sm btn-edit" onclick ="openModal('editMealModal')">Edit</button>
                </td>
            `;
            tbody.appendChild(row);
        });

        // Update meal count
        document.querySelector('#meals-section .section-header h2').textContent = 
            `Current Menu Items(${data.meals.length})`;
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
    const image = document.getElementById("image").value;
    
    try {
        const response = await fetch('../../api/meals_api.php?action=create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, description, price, category, image })
        });

        const data = await response.json();

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
    if (!confirm('Delete this meal?')) return;

    try {
        const response = await fetch('../../api/meals_api.php?action=delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: mealId })
        });

        const data = await response.json();

        if (data.success) {
            alert('Meal deleted');
            loadMeals();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error deleting meal:', error);
        alert('Error deleting meal');
    }
}

async function toggleMealAvailability(mealId, currentStatus) {
    try {
        const response = await fetch('../../api/meals_api.php?action=toggle', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: mealId, status: currentStatus })
        });

        const data = await response.json();

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

// ===== USERS API =====
async function loadUsers() {
    try {
        const response = await fetch('../../api/users_api.php?action=list');
        const data = await response.json();

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
                    <button class="btn-sm btn-delete" disabled>Delete</button>
                    <button class="btn-sm btn-edit" disabled>Edit</button>
                </td>
            `;
            tbody.appendChild(row);
        });

        // Update user count
        document.querySelector('#users-section .section-header h2').textContent = 
            `System Users(${data.users.length})`;
        document.querySelector('#overview-section .stat-card p#total-users').textContent = 
            `${data.users.length}`;

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
        const response = await fetch('../../api/users_api.php?action=create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, email, password, role })
        });

        const data = await response.json();

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

// Utility
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

// Load data on page load
document.addEventListener('DOMContentLoaded', function() {
    loadMeals();
    loadUsers();
});
