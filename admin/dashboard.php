<?php
require_once '../auth/auth.php';


$auth = new Auth();

if (!$auth->isLoggedIn() || !$auth->checkRole('admin')) {
    die("Access denied: Admin only.");//terminating the session
}


require_once '../meal.php';
require_once '../auth/database.php';
require_once 'user.php';
// require_once 'meal.php';

// --- Authorization Check ---
// In a real app, ensure session check looks like: if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit; }

// --- Initialize Database and Objects ---
$database = new Database();
$db = $database->getConnection();
$userObj = new User($db);
$mealObj = new Meal($db);

$message = "";

// printing out the session variables


// --- Handle Form Submissions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Handle New Meal Creation
    if (isset($_POST['action']) && $_POST['action'] == 'add_meal') {
        $name = $_POST['name'];
        $desc = $_POST['description'];
        $price = $_POST['price'];
        $cat = $_POST['category'];
 
        
        // Basic Image Handling (In production, use proper file upload checks)
        $image = "default_food.png";
        if(isset($_FILES['image']['name']) && $_FILES['image']['name'] != ""){
            $target_dir = "../menu/";
            if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
            $image = $target_dir . basename($_FILES["image"]["name"]);
            move_uploaded_file($_FILES["image"]["tmp_name"], $image);
        }

        if ($mealObj->create($name, $desc, $price, $cat, $image)) {
            $message = "Meal added successfully!";
        } else {
            $message = "Error adding meal.";
        }
    }

    // 2. Handle User Creation (Admin adding staff)
    if (isset($_POST['action']) && $_POST['action'] == 'add_user') {
        $u_name = $_POST['username'];
        $u_email = $_POST['email'];
        $u_pass = $_POST['password'];
        $u_role = $_POST['role']; // Admin, Sales, Manager

        if ($userObj->create($u_name, $u_email, $u_pass, $u_role)) {
            $message = "User created with role: $u_role";
        } else {
            $message = "Error creating user.";
        }
    }

    // 3. Handle Meal Deletion
    if (isset($_POST['delete_meal_id'])) {
        $mealObj->delete($_POST['delete_meal_id']);
        $message = "Meal deleted.";
    }

    // 4. Handle Availability Toggle
    if (isset($_POST['toggle_id'])) {
        $mealObj->toggleAvailability($_POST['toggle_id'], $_POST['current_status']);
    }
}

// Fetch Data for View
$meals = $mealObj->readAll();
// Fetch Users (Assuming User class doesn't have readAll, doing raw query for assignment speed)
$users = $userObj->readAll();
    
// getting username from session
$query = "SELECT username FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$username = $stmt->get_result()->fetch_object()->username;
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Aunt Joy's</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="dashboard-container">
    <!-- Sidebar -->
    <div class="sidebar">
        <a href="dashboard.php" class = "logo"><h2>Aunt Joy's Restuarant</h2></a>
        <ul>
            <li><a href="#overview" class="active" onclick="showSection('overview-section')">📊 Overview</a></li>
            <li><a href="#meals-section" onclick="showSection('meals-section')">🍔 Manage Meals</a></li>
            <li><a href="#users-section" onclick="showSection('users-section')">👥 Manage Users</a></li>
            <li><a href="../auth/logout.php" class="logout">Logout</a></li>
        </ul>
    </div>


    <!-- Main Content -->
    <div class="main-content">
        <header>
            <div>
                <h1>Admin Dashboard</h1>
                <p style="color: #777;">Welcome, <?= htmlspecialchars($username); ?></p>
            </div>
            <?php if($message): ?>
                <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px;">
                    <?= $message; ?>
                </div>
            <?php endif; ?>
        </header>

        <!-- SECTIONS -->
        <div id="overview-section" style="display:block;">
        <!-- 1. Overview Section -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Meals</h3>
                    <p><?= $meals->num_rows; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Users</h3>
                    <p><?= $users->num_rows; ?></p>
                </div>
            </div>
        </div>

        <div id="meals-section" style="display:block;">
        <!-- 2. Meals Section -->
            
            <div class="section-header" >
                <h2>Current Menu Items(<?= $meals->num_rows; ?>)</h2>
                <button class="btn-primary" onclick="openModal('mealModal')">+ Add New Meal</button>
            </div>
            
            <div class="table-responsive">
                <table>
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
                        <?php  while($row = $meals->fetch_assoc()){
                        ?>
                        <tr>
                            <td><img src="<?= '../menu/'.$row['image'] ?: 'placeholder.jpg' ?>" class="meal-img" alt="Food"></td>
                            <td>
                                <strong><?= htmlspecialchars($row['name']); ?></strong><br>
                                <small style="color:#888;"><?= substr(htmlspecialchars($row['description']), 0, 30); ?>...</small>
                            </td>
                            <td><?= htmlspecialchars($row['category']); ?></td>
                            <td>MWK<?= htmlspecialchars($row['price_MWK']); ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="toggle_id" value="<?= $row['id']; ?>">
                                    <input type="hidden" name="current_status" value="<?= $row['availability']; ?>">
                                    <button type="submit" style="border:none; background:none; cursor:pointer;">
                                        <?php if($row['availability'] == 'in_stock'): ?>
                                            <span class="badge badge-success">In Stock</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Out of Stock</span>
                                        <?php endif; ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Delete this meal?');" style="display:inline;">
                                    <input type="hidden" name="delete_meal_id" value="<?= $row['id']; ?>">
                                    <button class="btn-sm btn-delete">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php }?>
                    </tbody>
                </table>
                
            </div>

            
        </div>

        <div id="users-section" style="display:block;" >
        <!-- 3. Users Section  -->

            <div class="section-header">
                <h2>System Users(<?= $users->num_rows; ?>)</h2>
                <button class="btn-primary" onclick="openModal('userModal')">+ Add New Staff</button>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Joined Date</th>
                        </tr>
                    </thead>
                    
                    <tbody>
                        <?php while($u = $users -> FETCH_ASSOC()): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['username']); ?></td>
                            <td><?= htmlspecialchars($u['email']); ?></td>
                            <td>
                                <span style="text-transform: capitalize; font-weight:bold; color: #555;">
                                    <?= htmlspecialchars($u['role']); ?>
                                </span>
                            </td>
                            <td><?= $u['created_at']; ?></td>
                        </tr>
                        <?php endwhile; ?>
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
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_meal">
            <div class="form-group">
                <label>Meal Name</label>
                <input type="text" name="name" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" required></textarea>
            </div>
            <div class="form-group">
                <label>Price (MK)</label>
                <input type="number" step="0.01" name="price" required>
            </div>
            <div class="form-group">
                <label>Category</label>
                <select name="category">
                    <option value="Breakfast">Breakfast</option>
                    <option value="Lunch">Lunch</option>
                    <option value="Dinner">Dinner</option>
                    <option value="Drinks">Drinks</option>
                </select>
            </div>
            <div class="form-group">
                <label>Image</label>
                <input type="file" name="image">
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
        <form method="POST">
            <input type="hidden" name="action" value="add_user">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label>Role</label>
                <select name="role">
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
<script>
// Simple JS for tab switching
function showSection(sectionId) {
    // Hide all sections
    document.getElementById('overview-section').style.display = 'none';
    document.getElementById('meals-section').style.display = 'none';
    document.getElementById('users-section').style.display = 'none';

    // Remove active class from links
    document.querySelectorAll('.sidebar a').forEach(a => a.classList.remove('active'));

    // Show target and activate link
    document.getElementById(sectionId).style.display = 'block';
    document.querySelector(`.sidebar a[href="#${sectionId}"]`).classList.add('active');
}

// Modal Logic
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'flex';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Close modal if clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = "none";
    }
}
</script>
</body>
</html>