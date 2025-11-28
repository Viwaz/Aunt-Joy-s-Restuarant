<?php
require_once '../auth/database.php';
require_once '../auth/auth.php';

class Menu {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function getCategories() {
        $query = "SELECT * FROM categories ORDER BY category_name";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result  = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getMealsByCategory($category_id = null) {
        $query = "SELECT m.*, c.category_name as category_name 
                  FROM menu_items m 
                  LEFT JOIN categories c ON m.category = c.id 
                  WHERE m.availability = 'in_stock'";
        
        if ($category_id) {
            $query .= " AND m.category = ?";
        }
        
        $query .= " ORDER BY m.name";
        
        $stmt = $this->db->prepare($query);
        if ($category_id) {
            $stmt->bind_param("i", $category_id);
        }
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function searchMeals($keyword) {
        $query = "SELECT m.*, c.category_name as category_name 
                  FROM menu_items m 
                  LEFT JOIN categories c ON m.category = c.id 
                  WHERE m.availability = 'in_stock' 
                  AND (m.name LIKE ? OR m.description LIKE ?) 
                  ORDER BY m.name";
        
        $stmt = $this->db->prepare($query);
        $keyword = "%$keyword%";
        $stmt->bind_param("ss", $keyword, $keyword);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

$menu = new Menu();
$categories = $menu->getCategories();

// Handle search
$meals = [];
if (isset($_GET['search']) && !empty($_GET['keyword'])) {
    $meals = $menu->searchMeals($_GET['keyword']);
} elseif (isset($_GET['category_id'])) {
    $meals = $menu->getMealsByCategory($_GET['category_id']);
} else {
    $meals = $menu->getMealsByCategory();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aunt Joy's Restaurant - Menu</title>
    <style>
        .menu-container { display: flex; }
        .categories { width: 200px; padding: 20px; }
        .meals { flex: 1; padding: 20px; }
        .meal-card { border: 1px solid #ddd; padding: 15px; margin: 10px; border-radius: 5px; }
        .add-to-cart { background: #007bff; color: white; border: none; padding: 5px 10px; cursor: pointer; }
    </style>
</head>
<body>
    <header>
        <h1>Aunt Joy's Restaurant</h1>
        <nav>
            <a href="customer_interface.php">Menu</a>
            <a href="cart.php">Cart</a>
            <a href="customer_orders.php">My Orders</a>
            <a href="../auth/logout.php">Logout</a>
        </nav>
    </header>

    <div class="menu-container">
        <div class="categories">
            <h3>Categories</h3>
            <a href="customer_interface.php">All Categories</a>
            <?php foreach ($categories as $category): ?>
                <div>
                    <a href="customer_interface.php?category_id=<?= $category['id'] ?>">
                        <?= htmlspecialchars($category['category_name']) ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="meals">
            <form method="GET" action="customer_interface.php">
                <input type="text" name="keyword" placeholder="Search meals..." 
                       value="<?= isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : '' ?>">
                <button type="submit" name="search">Search</button>
            </form>

            <div class="meals-grid">
                <?php foreach ($meals as $meal): ?>
                    <div class="meal-card">
                        <img src="<?= '../menu/'.$meal['image'] ?: 'placeholder.jpg' ?>" class="meal-img" alt="Food">
                        <h3><?= htmlspecialchars($meal['name']) ?></h3>
                        <p><?= htmlspecialchars($meal['description']) ?></p>
                        <p>MWK <?= number_format($meal['price_MWK'], 2) ?></p>
                        <!-- <p>Category: <?= htmlspecialchars($meal['category_name']) ?></p> -->
                        <form method="POST">
                            <input type="hidden" name="meal_id" value="<?= $meal['id'] ?>">
                            <input type="number" name="quantity" value="1" min="1" max="10">
                            <button type="submit" name="add_to_cart" class="add-to-cart" onclick="addToCart(<?= $meal['id'] ?>)">Add to Cart</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <script>
function addToCart(mealId) {
    const quantity = document.getElementById(`qty-${mealId}`).value || 1;

    fetch("cart.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ menu_item_id: mealId, quantity: quantity })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert("Item added to cart!");
            updateCartCount();
        } else {
            alert("Failed to add to cart: " + data.message);
        }
    })
    .catch(err => console.error(err));
}


function updateCartCount() {
    fetch("cart_count.php")
    .then(res => res.text())
    .then(count => {
        document.getElementById("cart-count").innerText = count;
    });
}
</script>
<footer class="bg-dark text-light py-4 mt-5">
  <div class="container">
    <div class="row">
      <!-- About -->
      <div class="col-md-4 mb-3">
        <h5>Aunt Joy Restaurant</h5>
        <p>Delicious meals served with love. Visit us for a tasty experience!</p>
      </div>

      <!-- Quick Links -->
      <div class="col-md-4 mb-3">
        <h5>Quick Links</h5>
        <ul class="list-unstyled">
          <li><a href="#menu-container" class="text-light text-decoration-none">Menu</a></li>
          <li><a href="#contact" class="text-light text-decoration-none">Contact</a></li>
          <li><a href="#about" class="text-light text-decoration-none">About Us</a></li>
        </ul>
      </div>

      <!-- Contact -->
      <div class="col-md-4 mb-3">
        <h5>Contact Us</h5>
        <p>Email: info@auntjoy.com</p>
        <p>Phone: +265 999 999 999</p>
        <p>Location: Mzuzu, Malawi</p>
      </div>
    </div>

    <div class="text-center mt-3">
      &copy; 2025 Aunt Joy Restaurant. All Rights Reserved.
    </div>
  </div>
</footer>+
</body>
</html>