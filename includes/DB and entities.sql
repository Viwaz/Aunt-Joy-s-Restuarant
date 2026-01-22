-- ============================================
-- 1. CREATE DATABASE
-- ============================================
CREATE DATABASE aunt_joydb;
USE aunt_joydb;

-- ============================================
-- 2. CATEGORIES TABLE
-- ============================================
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) NOT NULL
);

INSERT INTO categories(category_name) VALUES
('Breakfast'), ('Lunch'), ('Dinner'), ('Drinks');

-- ============================================
-- 3. USERS TABLE
-- ============================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'admin', 'sales', 'manager') NOT NULL DEFAULT 'customer',
    delivery_address VARCHAR(150),
    phone_num VARCHAR(15),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- inserting the admin
INSERT INTO users(username, email, password, role) VALUES
("Admin","admin@1","$2y$10$GYjwsWWFGZ1ZfZKdVUDO7O9rtxPQULNz2r/6QfxIsghboc0ek3seK","admin");
-- ============================================
-- 4. MENU ITEMS TABLE
-- ============================================
CREATE TABLE menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price_MWK DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    category INT,
    availability ENUM('in_stock','out_of_stock') DEFAULT 'in_stock',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category) REFERENCES categories(id)
);

-- Insert menu items
INSERT INTO menu_items (name, description, price_MWK, image, category) VALUES
('Beef Fried Rice', 'A tasty combination of seasoned fried rice and tender beef strips.', 6000, 'Beef fried rice.jpg', 2),
('Coca-Cola', 'A refreshing fizzy soft drink perfect to enjoy with any meal.', 800, 'Coke.webp', 4),
('Beef Curry with Rice', 'A rich and fragrant beef curry served with perfectly cooked rice.', 6500, 'fragrant-beef-curry-with-rice.webp', 2),
('Grilled Chicken', 'Deliciously marinated grilled chicken cooked to golden perfection.', 7000, 'Grilled Chicken.webp', 2),
('Rice and Beef Curry', 'Flavourful beef curry paired with soft white rice.', 6500, 'Rice and Beef Curry.webp', 2),
('Roasted Chicken with Rice', 'Crispy roasted chicken served alongside fluffy rice.', 7500, 'Roasted Chicken with rice.jpg', 2),
('Fresh Salad', 'A refreshing mix of crisp vegetables for a healthy side.', 3500, 'salad.jpg', 2),
('Vegetable Rice', 'A tasty rice dish cooked with stir-fried mixed vegetables.', 5500, 'vegr.webp', 2),
('Bottled Water', 'Clean and refreshing bottled drinking water.', 500, 'water.webp', 4);

-- ============================================
-- 5. ORDERS TABLE
-- ============================================
CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    total_amount DECIMAL(10,2),
    status ENUM('pending','en route','delivered') DEFAULT 'pending',
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    delivery_address VARCHAR(150),
    phone_num VARCHAR(15),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ============================================
-- 6. ORDER ITEMS TABLE
-- ============================================
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    menu_item_id INT,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id),
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
);

-- ============================================
-- 7. CART TABLE
-- ============================================
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    menu_item_id INT,
    quantity INT NOT NULL DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
);

-- ============================================
-- 8. AUDIT LOG TABLE
-- ============================================
CREATE TABLE audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT NOT NULL,
    performed_by INT,
    description TEXT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (performed_by) REFERENCES users(id)
);

-- ============================================
-- 9. QUICK TEST QUERIES (Optional)
-- ============================================
-- SELECT * FROM users;
-- SELECT * FROM categories;
-- SELECT * FROM menu_items;
-- SELECT * FROM orders;
-- SELECT * FROM order_items;
-- SELECT * FROM audit_log;
-- SELECT * FROM cart;
