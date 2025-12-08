<?php
    require_once __DIR__.'/../../auth/auth.php';
require_once  __DIR__.'/../../includes/Database.php';

$auth = new Auth();

if (!$auth->isLoggedIn() || !$auth->checkRole('admin')) {
    header("Location: ../../auth/login.php");
    exit;
}

// Get username for greeting
$db = (new Database())->getConnection();
$query = "SELECT username FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$result = $stmt->get_result()->fetch_object();
$username = $result ? $result->username : 'Admin';
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    html { font-size:25px; }
  </style>
    <title>Admin Dashboard - Aunt Joy's</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="dashboard-container">
    <!-- Sidebar -->
    <div class="sidebar">
        <a href="dashboard.php" class = "logo"><h2>Aunt Joy's Restuarant</h2></a>
        <ul>
            <li><a href="#overview" class="active" onclick="showSection('overview-section')"> Overview</a></li>
            <li><a href="#meals-section" onclick="showSection('meals-section')">🍔 Meals</a></li>
            <li><a href="#users-section" onclick="showSection('users-section')">👥 Users</a></li>
            <!-- <li><a href="../auth/logout.php" class="logout" position = 'bottom'>Logout</a></li> -->
        </ul>
    </div>


    <!-- Main Content -->
    <div class="main-content">
        <header>
            <div class = "header">
                <h1>Admin Dashboard</h1>
                <a href="../../auth/logout.php" class="logout" position="right"><i class="fas fa-sign-out-alt"></i>Logout</a>
                <p style="color: #777;">Welcome, <?= htmlspecialchars($username); ?></p>
            </div>
        </header>

        <!-- SECTIONS -->
        <div id="overview-section" style="display:block;">
        <!-- 1. Overview Section -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Meals</h3>
                    <p id="total-meals">0</p>
                </div>
                <div class="stat-card">
                    <h3>Total Users</h3>
                    <p id="total-users">0</p>
                </div>
            </div>
        </div>

        <div id="meals-section" style="display:block;">
        <!-- 2. Meals Section -->
            
            <div class="section-header" >
                <h2>Current Menu Items(0)</h2>
                <button class="btn-primary" onclick="openModal('mealModal')">+ Add New Meal</button>
            </div>
            
            <div class="table-responsive">
                <table id="meals-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Populated by admin.js -->
                    </tbody>
                </table>
            </div>
        </div>

        <div id="users-section" style="display:block;" >
        <!-- 3. Users Section  -->

            <div class="section-header">
                <h2>System Users(0)</h2>
                <button class="btn-primary" onclick="openModal('userModal')">+ Add New Staff</button>
            </div>
            <div class="table-responsive">
                <table id="users-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Joined Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    
                    <tbody>
                        <!-- Populated by admin.js -->
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- MODALS -->

<!-- Add Meal Modal -->
<div id="mealModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('mealModal')">&times;</span>
        <h2>Add New Meal</h2>
        <form id="mealForm" onsubmit="addMeal(event)" enctype="multipart/form-data">
            <div class="form-group">
                <label>Meal Name</label>
                <input type="text" id= "name" name="name" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea id = "description" name="description" required></textarea>
            </div>
            <div class="form-group">
                <label>Price (MK)</label>
                <input type="number" id="price"  name="price" required>
            </div>
            <div class="form-group">
                <label>Category</label>
                <select id ="category" name="category">
                    <option value="Breakfast">Breakfast</option>
                    <option value="Lunch">Lunch</option>
                    <option value="Dinner">Dinner</option>
                    <option value="Drinks">Drinks</option>
                </select>
            </div>
            <div class="form-group">
                <label>Image</label>
                <input type="file" id ="image" name="image" required>
            </div>
            <button type="submit" class="btn-primary" style="width:100%;">Save Meal</button>
        </form>
    </div>
</div>

<!-- Add User Modal -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('userModal')">&times;</span>
        <h2>Add New Staff/User</h2>
        <form id="userForm" onsubmit="addUser(event)">
            <div class="form-group">
                <label>Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label>Role</label>
                <select id="role" name="role">
                    <option value="admin">Administrator</option>
                    <option value="manager">Manager</option>
                    <option value="sales">Sales Staff</option>
                    <option value="customer">Customer</option>
                </select>
            </div>
            <button type="submit" class="btn-primary" style="width:100%;">Create User</button>
        </form>
    </div>
</div>

<!-- Edit Meal Modal -->
<div id="editMealModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('editMealModal')">&times;</span>
        <h2>Edit Meal</h2>
        <form id="editMealForm" onsubmit="saveMealEdit(event)" enctype="multipart/form-data">
            <input type="hidden" id="edit-meal-id" name="meal_id">
            <div class="form-group">
                <label>Meal Name</label>
                <input type="text" id="edit-name" name="name" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea id="edit-description" name="description" required></textarea>
            </div>
            <div class="form-group">
                <label>Price (MK)</label>
                <input type="number" id="edit-price" name="price" required>
            </div>
            <div class="form-group">
                <label>Category</label>
                <select id="edit-category" name="category" required>
                    <option value="Breakfast">Breakfast</option>
                    <option value="Lunch">Lunch</option>
                    <option value="Dinner">Dinner</option>
                    <option value="Drinks">Drinks</option>
                </select>
            </div>
            <div class="form-group">
                <label>Image</label>
                <input type="file" id="edit-image" name="image">
                <small style="color:#888;">Leave blank to keep current image</small>
            </div>
            <button type="submit" class="btn-primary" style="width:100%;">Save Changes</button>
        </form>
    </div>
</div>
<script src="admin.js"></script>
</body>
</html>