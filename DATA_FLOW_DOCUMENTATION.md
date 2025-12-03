# Aunt Joy's Restaurant - Data Flow Documentation

## System Overview

Aunt Joy's Restaurant is a PHP-based restaurant ordering system built on a **Path A architecture** with clear separation of concerns:
- **API Layer** (`api/`): JSON endpoints handling all business logic
- **View Layer** (`views/`): Minimal PHP templates with HTML/Bootstrap
- **JavaScript Layer** (`*.js`): Client-side UI orchestration and API calls
- **Database Layer**: Centralized MySQLi connection via `includes/Database.php`
- **Authentication**: Session-based role-based access control (`auth/`)

---

## 1. Authentication & Session Flow

### 1.1 Login Process

```
User (customer/admin/sales/manager)
    ↓ [POST] username, password
    ↓
auth/login.php (view)
    ↓ [Form submission]
    ↓
auth/auth.php (Auth class)
    ├─ SQL: SELECT * FROM users WHERE email = ?
    ├─ password_verify() check
    └─ IF valid:
        ├─ $_SESSION['id'] = user.id
        ├─ $_SESSION['role'] = user.role
        ├─ Merge pending_cart if exists
        └─ Redirect based on role
```

**Key Files:**
- `auth/login.php` - Login form view
- `auth/auth.php` - Authentication logic (Auth class)

**Database Table:** `users` (id, username, email, password, role, created_at)

**Session Variables Created:**
- `$_SESSION['id']` - User ID
- `$_SESSION['role']` - User role (customer, admin, sales, manager)
- `$_SESSION['pending_cart']` - Temp cart for guests (optional)
- `$_SESSION['redirect_after_login']` - Post-login redirect URL (optional)

### 1.2 Cart Merging for Guest Users

```
Guest User adds items
    ↓ [No session]
    ↓
Customer clicks Checkout
    ↓ [Not logged in?]
    ↓
views/user/checkout.php
    ├─ Set $_SESSION['pending_cart'] = cart items
    ├─ Set $_SESSION['redirect_after_login'] = 'user/checkout.php'
    └─ Redirect to login
    ↓
User logs in (auth/login.php)
    ↓
auth/auth.php (Auth::login)
    ├─ Check for $_SESSION['pending_cart']
    ├─ Merge into DB cart table
    ├─ Clear $_SESSION['pending_cart']
    ├─ Check $_SESSION['redirect_after_login']
    └─ Redirect to original URL
```

---

## 2. Menu Browsing Flow (Customer)

### 2.1 Load Categories

```
Customer visits views/user/customer_interface.php
    ↓
Page loads (DOMContentLoaded)
    ↓
customer.js calls loadCategories()
    ↓ [GET] ../../api/menu_api.php?action=categories
    ↓
api/menu_api.php
    ├─ SQL: SELECT * FROM categories ORDER BY category_name
    ├─ No auth required
    └─ Response: { success: true, categories: [...] }
    ↓
JavaScript renders category links in #category-list
```

**Database Table:** `categories` (id, category_name)

### 2.2 Load Meals (by Category or All)

```
User clicks category link OR page loads
    ↓
customer.js calls loadMeals(categoryId?)
    ↓ [GET] ../../api/menu_api.php?action=meals&category_id=X (or no param for all)
    ↓
api/menu_api.php
    ├─ SQL: SELECT m.*, c.category_name FROM menu_items m 
    │         LEFT JOIN categories c ON m.category = c.id
    │         WHERE m.availability = 'in_stock'
    │         [+ AND m.category = ? if category_id provided]
    ├─ No auth required
    └─ Response: { success: true, meals: [...] }
    ↓
JavaScript renders meal cards in #meals-grid
    ├─ Displays: image (from /menu/ folder), name, description, price, category
    └─ Quantity input + "Add to Cart" button
```

**Database Tables:**
- `menu_items` (id, name, description, price_MWK, category, image, availability)
- `categories` (id, category_name)

### 2.3 Search Meals

```
User enters search term and submits form
    ↓
customer.js calls searchMeals(keyword)
    ↓ [GET] ../../api/menu_api.php?action=search&keyword=...
    ↓
api/menu_api.php
    ├─ If keyword is empty:
    │   └─ Return { success: true, meals: [] }
    ├─ Else:
    │   ├─ SQL: SELECT m.*, c.category_name FROM menu_items m
    │   │         LEFT JOIN categories c ON m.category = c.id
    │   │         WHERE m.availability = 'in_stock'
    │   │         AND (m.name LIKE '%keyword%' OR m.description LIKE '%keyword%')
    │   └─ Response: { success: true, meals: [...] }
    ↓
JavaScript renders filtered meals or "No results" message
```

---

## 3. Cart Management Flow

### 3.1 Add Item to Cart

```
Customer selects quantity and clicks "Add to Cart"
    ↓
customer.js calls addToCart(mealId)
    ├─ Gets qty from #qty-{mealId} input
    └─ Read qty value
    ↓ [POST JSON] ../../api/add_to_cart.php
    │  Body: { menu_item_id: X, quantity: Y }
    ↓
api/add_to_cart.php
    ├─ Check $_SESSION['id'] (require logged-in user)
    ├─ IF NOT logged in:
    │   └─ Response: { success: false, message: 'User not logged in' }
    ├─ ELSE:
    │   ├─ SQL: SELECT quantity FROM cart WHERE user_id = ? AND menu_item_id = ?
    │   ├─ IF item exists in cart:
    │   │   └─ UPDATE cart SET quantity = quantity + ? WHERE user_id = ? AND menu_item_id = ?
    │   ├─ ELSE:
    │   │   └─ INSERT INTO cart (user_id, menu_item_id, quantity) VALUES (?, ?, ?)
    │   └─ Response: { success: true }
    ↓
JavaScript shows "Item added to cart!" alert
    ↓
customer.js calls updateCartCount()
    ├─ [GET] ../../api/cart_count.php
    └─ Updates #cart-count badge
```

**Database Table:** `cart` (id, user_id, menu_item_id, quantity, created_at)

### 3.2 View Cart

```
Customer clicks "Cart" in sidebar
    ↓
Navigates to views/user/cart.php
    ↓
PHP backend:
    ├─ Check $_SESSION['id'] (require login)
    ├─ SQL: SELECT c.*, m.* FROM cart c JOIN menu_items m WHERE c.user_id = ?
    ├─ Calculate subtotals and total
    └─ Render cart items with quantity inputs and remove buttons
    ↓
HTML renders cart with:
    ├─ Item cards: image, name, category, description, unit price
    ├─ Quantity field (update via POST)
    └─ Remove button (POST)
```

**Form Submissions in cart.php:**
- **Update Quantity**: POST `cart_id` + `quantity` → PHP redirects to self
- **Remove Item**: POST `remove_id` → DELETE FROM cart → redirect to self

### 3.3 Cart Count Badge

```
(Anywhere in app)
    ↓
customer.js calls updateCartCount()
    ↓ [GET] ../../api/cart_count.php
    ↓
api/cart_count.php
    ├─ Check $_SESSION['id']
    ├─ SQL: SELECT SUM(quantity) FROM cart WHERE user_id = ?
    └─ Response: { success: true, count: N }
    ↓
JavaScript updates #cart-count text content
```

---

## 4. Checkout & Order Creation Flow

### 4.1 Checkout Page

```
Customer clicks "Proceed to Checkout" button
    ↓
Navigates to views/user/checkout.php
    ↓
PHP backend:
    ├─ Check $_SESSION['id']
    ├─ IF NOT logged in:
    │   ├─ Set $_SESSION['pending_cart'] = $_SESSION['cart'] (if exists)
    │   ├─ Set $_SESSION['redirect_after_login'] = 'user/checkout.php'
    │   └─ Redirect to ../../auth/login.php
    ├─ ELSE:
    │   ├─ SQL: SELECT c.*, m.* FROM cart c JOIN menu_items m WHERE c.user_id = ?
    │   ├─ Calculate total
    │   └─ Render checkout form (delivery address, phone)
    ↓
User fills delivery address + phone
    ↓ [POST] method=POST
    ↓
PHP processes:
    ├─ Validate address (required)
    ├─ Validate phone (required)
    ├─ Check cart not empty
    └─ IF validation passes:
        ├─ DB->begin_transaction()
        ├─ SQL: INSERT INTO orders (user_id, total_amount, delivery_address, phone_num, status, order_date)
        │        VALUES (?, ?, ?, ?, 'pending', NOW())
        ├─ $order_id = $db->insert_id
        ├─ SQL: INSERT INTO order_items (order_id, menu_item_id, quantity, price, sub_total)
        │        VALUES (?, ?, ?, ?, ?) [for each cart item]
        ├─ SQL: DELETE FROM cart WHERE user_id = ?
        ├─ DB->commit()
        └─ Redirect to customer_orders.php?order_created=1&order_id=X
```

**Database Tables Created/Modified:**
- `orders` INSERT: (order_id, user_id, total_amount, delivery_address, phone_num, status, order_date)
- `order_items` INSERT: (order_item_id, order_id, menu_item_id, quantity, price, sub_total)
- `cart` DELETE: remove all items for user

---

## 5. Order Management Flow

### 5.1 Customer Views Own Orders

```
Customer clicks "My Orders"
    ↓
Navigates to views/user/customer_orders.php
    ↓
Page loads JavaScript (orders.js)
    ↓
orders.js calls loadOrders()
    ↓ [GET] ../../api/orders_api.php?action=list
    ↓
api/orders_api.php
    ├─ Check $_SESSION['id'] (require login)
    ├─ IF role = 'customer':
    │   └─ SQL: SELECT o.*, u.email, u.username FROM orders o 
    │            JOIN users u ON o.user_id = u.id
    │            WHERE o.user_id = ? ORDER BY o.order_date DESC
    ├─ Response: { success: true, orders: [...with items] }
    ↓
JavaScript renders:
    ├─ Current Orders section (status: pending, en route)
    ├─ Order History section (status: delivered)
    ├─ Each order card shows: Order ID, date, status badge, total, delivery address
    └─ "View Details" button → modal
```

**Database Query:**
```sql
SELECT o.*, u.email, u.username FROM orders o 
JOIN users u ON o.user_id = u.id 
WHERE o.user_id = ? 
ORDER BY o.order_date DESC
```

Then for each order, fetch:
```sql
SELECT order_id, menu_item_id, quantity, price, (quantity * price) as subtotal 
FROM order_items WHERE order_id = ?
```

### 5.2 Customer Views Order Details

```
User clicks "View Details" on order card
    ↓
orders.js calls showOrderDetails(orderId)
    ↓ [GET] ../../api/orders_api.php?action=list (re-fetch all)
    ↓
JavaScript finds order by orderId in cached data
    ↓
Renders modal with:
    ├─ Order ID, status, date
    ├─ Delivery address
    ├─ Contact phone
    ├─ Table of items: Item ID, Qty, Price, Subtotal
    └─ Order total
```

---

## 6. Sales/Manager Dashboard & Order Status Updates

### 6.1 Sales Views All Orders

```
Sales staff views views/sales/dashboard.php
    ↓
Check auth: role = 'sales'
    ↓
Page loads JavaScript (sales.js)
    ↓
sales.js calls loadOrders()
    ↓ [GET] ../../api/orders_api.php?action=list
    ↓
api/orders_api.php
    ├─ Check $_SESSION['id']
    ├─ IF role ≠ 'customer':
    │   └─ SQL: SELECT o.*, u.email, u.username FROM orders o 
    │            JOIN users u ON o.user_id = u.id
    │            ORDER BY o.order_date DESC [NO user_id filter]
    ├─ Response: { success: true, orders: [...all orders...] }
    ↓
JavaScript renders:
    ├─ Grid with columns: Order ID, Customer (name+email), Total, Status, Date, Action
    ├─ Status filter dropdown
    ├─ "View" button per order
    └─ Color-coded status badges
```

### 6.2 Sales Updates Order Status

```
Sales clicks "View" on an order
    ↓
sales.js calls viewOrderDetails(orderId)
    ↓
Modal opens with:
    ├─ Order details (customer, items, address, phone)
    ├─ Current status
    ├─ Status dropdown (pending, en route, delivered)
    └─ "Update Status" button
    ↓
User selects new status and clicks button
    ↓
sales.js calls updateOrderStatus()
    ↓ [POST JSON] ../../api/orders_api.php?action=update_status
    │  Body: { order_id: X, status: 'new_status' }
    ↓
api/orders_api.php
    ├─ Check $_SESSION['id']
    ├─ Check role = 'sales' (ENFORCE)
    ├─ Validate order_id
    ├─ Validate status in ['pending', 'en route', 'delivered']
    ├─ SQL: UPDATE orders SET status = ? WHERE order_id = ?
    └─ Response: { success: true, status: 'new_status' }
    ↓
JavaScript alerts success
    ↓
sales.js calls loadOrders() to refresh grid
```

---

## 7. Admin Dashboard & Meal Management

### 7.1 Admin Views Meals

```
Admin visits views/admin/dashboard.php
    ↓
Check auth: role = 'admin'
    ↓
Page loads JavaScript (admin.js)
    ↓
admin.js calls loadMeals()
    ↓ [GET] ../../api/meals_api.php?action=list
    ↓
api/meals_api.php
    ├─ Check $_SESSION['id'] and role = 'admin'
    ├─ SQL: SELECT m.*, c.category_name FROM menu_items m 
    │         LEFT JOIN categories c ON m.category = c.id
    │         ORDER BY m.name
    └─ Response: { success: true, meals: [...] }
    ↓
JavaScript renders table with:
    ├─ Image, Name, Category, Price, Availability Status
    └─ Edit/Delete action buttons
```

### 7.2 Admin Creates Meal

```
Admin clicks "+ Add New Meal"
    ↓
Modal opens with form:
    ├─ Meal name
    ├─ Description
    ├─ Price (MK)
    ├─ Category dropdown
    └─ Image file upload
    ↓
Admin fills form and submits
    ↓
admin.js calls addMeal(event)
    ↓ [POST FormData] ../../api/meals_api.php?action=create
    │  - name, description, price, category, image file
    ↓
api/meals_api.php
    ├─ Check $_SESSION['id'] and role = 'admin'
    ├─ Validate inputs
    ├─ IF image provided:
    │   ├─ Move uploaded file to ../menu/ folder
    │   └─ Get filename
    ├─ SQL: INSERT INTO menu_items (name, description, price_MWK, category, image, availability)
    │        VALUES (?, ?, ?, ?, ?, 'in_stock')
    └─ Response: { success: true, message: 'Meal created' }
    ↓
JavaScript shows success alert
    ↓
admin.js calls loadMeals() to refresh table
```

**Database Table:** `menu_items` (id, name, description, price_MWK, category, image, availability)

### 7.3 Admin Deletes Meal

```
Admin clicks delete icon on meal row
    ↓
admin.js calls deleteMeal(mealId)
    ↓ [POST JSON] ../../api/meals_api.php?action=delete
    │  Body: { id: X }
    ↓
api/meals_api.php
    ├─ Check admin role
    ├─ SQL: DELETE FROM menu_items WHERE id = ?
    └─ Response: { success: true, message: 'Meal deleted' }
    ↓
JavaScript refreshes table
```

### 7.4 Admin Toggles Meal Availability

```
Admin clicks availability toggle
    ↓
admin.js calls toggleMealStatus(mealId, currentStatus)
    ↓ [POST JSON] ../../api/meals_api.php?action=toggle
    │  Body: { id: X, status: 'in_stock' | 'out_of_stock' }
    ↓
api/meals_api.php
    ├─ Check admin role
    ├─ Determine new status (toggle)
    ├─ SQL: UPDATE menu_items SET availability = ? WHERE id = ?
    └─ Response: { success: true, status: 'new_status' }
    ↓
JavaScript updates UI
```

---

## 8. User Management (Admin)

### 8.1 Admin Views Users

```
Admin clicks "Users" tab
    ↓
admin.js calls loadUsers()
    ↓ [GET] ../../api/users_api.php?action=list
    ↓
api/users_api.php
    ├─ Check admin role
    ├─ SQL: SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC
    └─ Response: { success: true, users: [...] }
    ↓
JavaScript renders table: Username, Email, Role, Joined Date, Actions
```

### 8.2 Admin Creates User

```
Admin clicks "+ Add New Staff"
    ↓
Modal opens with form:
    ├─ Username
    ├─ Email
    ├─ Password
    └─ Role dropdown (admin, manager, sales, customer)
    ↓
Admin submits
    ↓
admin.js calls addUser(event)
    ↓ [POST JSON] ../../api/users_api.php?action=create
    │  Body: { username, email, password, role }
    ↓
api/users_api.php
    ├─ Check admin role
    ├─ Validate all fields
    ├─ Check email uniqueness (SQL: SELECT id FROM users WHERE email = ?)
    ├─ Hash password: password_hash($password, PASSWORD_DEFAULT)
    ├─ SQL: INSERT INTO users (username, email, password, role)
    │        VALUES (?, ?, ?, ?)
    └─ Response: { success: true, message: 'User created' }
    ↓
JavaScript refreshes users table
```

**Database Table:** `users` (id, username, email, password, role, created_at)

---

## 9. Manager Dashboard & Reporting

### 9.1 Manager Generates Sales Report

```
Manager visits views/manager/dashboard.php
    ↓
Check auth: role = 'manager'
    ↓
Manager selects month/year and clicks "Generate Report"
    ↓ [POST] form data
    ↓
PHP backend:
    ├─ Call ReportManager->getSalesReport($month, $year)
    ├─ SQL: SELECT COUNT(*), SUM(total_amount), AVG(total_amount) 
    │        FROM orders WHERE DATE(order_date) BETWEEN ? AND ? 
    │        AND status = 'Delivered'
    ├─ SQL: SELECT m.name, SUM(oi.quantity), SUM(oi.quantity * oi.price)
    │        FROM order_items oi JOIN menu_items m JOIN orders o
    │        WHERE DATE(o.order_date) BETWEEN ? AND ?
    │        GROUP BY m.id ORDER BY total_quantity DESC LIMIT 10
    ├─ SQL: SELECT DATE(order_date), COUNT(*), SUM(total_amount)
    │        FROM orders WHERE DATE(order_date) BETWEEN ? AND ?
    │        GROUP BY DATE(order_date)
    └─ Render report with:
        ├─ Summary cards: total orders, revenue, average order value
        ├─ Best sellers table
        └─ Daily trends table
```

### 9.2 Manager Exports Report

```
Manager clicks "PDF" or "Excel" button
    ↓
Same report generation as above
    ↓
IF PDF:
    ├─ Set header('Content-Type: application/pdf')
    └─ Output text-based PDF content
├─ IF Excel:
    ├─ Set header('Content-Type: text/csv')
    ├─ Use fputcsv() to write CSV
    └─ Browser downloads file
```

---

## 10. Database Schema Overview

```
users
  ├─ id (INT PRIMARY KEY AUTO_INCREMENT)
  ├─ username (VARCHAR)
  ├─ email (VARCHAR UNIQUE)
  ├─ password (VARCHAR - hashed)
  ├─ role (ENUM: customer, admin, sales, manager)
  └─ created_at (TIMESTAMP)

categories
  ├─ id (INT PRIMARY KEY AUTO_INCREMENT)
  └─ category_name (VARCHAR)

menu_items
  ├─ id (INT PRIMARY KEY AUTO_INCREMENT)
  ├─ name (VARCHAR)
  ├─ description (TEXT)
  ├─ price_MWK (DECIMAL)
  ├─ category (INT FOREIGN KEY → categories.id)
  ├─ image (VARCHAR - filename)
  ├─ availability (ENUM: in_stock, out_of_stock)
  └─ created_at (TIMESTAMP)

cart
  ├─ id (INT PRIMARY KEY AUTO_INCREMENT)
  ├─ user_id (INT FOREIGN KEY → users.id)
  ├─ menu_item_id (INT FOREIGN KEY → menu_items.id)
  ├─ quantity (INT)
  └─ created_at (TIMESTAMP)

orders
  ├─ order_id (INT PRIMARY KEY AUTO_INCREMENT)
  ├─ user_id (INT FOREIGN KEY → users.id)
  ├─ total_amount (DECIMAL)
  ├─ delivery_address (TEXT)
  ├─ phone_num (VARCHAR)
  ├─ status (ENUM: pending, en route, delivered)
  ├─ order_date (TIMESTAMP)
  └─ created_at (TIMESTAMP)

order_items
  ├─ order_item_id (INT PRIMARY KEY AUTO_INCREMENT)
  ├─ order_id (INT FOREIGN KEY → orders.order_id)
  ├─ menu_item_id (INT FOREIGN KEY → menu_items.id)
  ├─ quantity (INT)
  ├─ price (DECIMAL - snapshot of price at time of order)
  └─ sub_total (DECIMAL)
```

---

## 11. Key Security & Validation Points

| Flow | Security Measure | Location |
|------|-----------------|----------|
| **Login** | password_hash() / password_verify() | auth/auth.php |
| **API Access** | Session check `$_SESSION['id']` | api/*_api.php |
| **Role-Based Access** | Role validation in API endpoints | api/meals_api.php, api/orders_api.php |
| **SQL Injection** | Prepared statements + bind_param() | All SQL queries |
| **XSS Prevention** | htmlspecialchars() in PHP, escapeHtml() in JS | Views & JS files |
| **File Upload** | Image move to dedicated folder | api/meals_api.php |

---

## 12. Request/Response Format

### Standard JSON API Response (Success)
```json
{
  "success": true,
  "data": { /* specific response data */ },
  "message": "Optional message"
}
```

### Standard JSON API Response (Error)
```json
{
  "success": false,
  "message": "Error description"
}
```

### HTTP Status Codes Used
- **200 OK** - Successful operation
- **400 Bad Request** - Invalid input/action
- **401 Unauthorized** - Not logged in
- **403 Forbidden** - Insufficient permissions (wrong role)
- **404 Not Found** - Resource not found
- **500 Internal Server Error** - Server-side exception

---

## 13. Development Architecture Highlights

### Path A Design Pattern
This system follows **Path A (Separation of Concerns)**:

```
┌─────────────────────────────────────────────────────┐
│                  User Browser                       │
└────────────────────┬────────────────────────────────┘
                     │
        ┌────────────┴────────────┐
        ↓                         ↓
    ┌─────────────────┐  ┌──────────────────┐
    │  Views/         │  │  JavaScript      │
    │  (HTML+PHP)     │  │  (*.js files)    │
    │                 │  │  - AJAX calls    │
    │  - Minimal PHP  │  │  - DOM rendering │
    │  - Auth checks  │  │  - Event handlers│
    │  - No logic     │  └──────────────────┘
    └────────┬────────┘         │
             │                  │
             └──────────┬───────┘
                        ↓
              ┌──────────────────────┐
              │   JSON APIs          │
              │   (api/*.php)        │
              │                      │
              │  - Business logic    │
              │  - DB queries        │
              │  - Auth enforcement  │
              │  - Validation        │
              └─────────┬────────────┘
                        ↓
              ┌──────────────────────┐
              │   Database           │
              │   (MySQL)            │
              │                      │
              │  - includes/         │
              │    Database.php      │
              │                      │
              │  Tables:             │
              │  - users             │
              │  - menu_items        │
              │  - categories        │
              │  - cart              │
              │  - orders            │
              │  - order_items       │
              └──────────────────────┘
```

### Benefits
1. **Testability**: APIs can be tested independently
2. **Reusability**: Multiple frontends can use same APIs
3. **Maintainability**: Clear separation of concerns
4. **Scalability**: Easy to add new features without modifying views
5. **Security**: Centralized validation in API layer

---

## 14. File Location Reference

```
project/
├── auth/
│   ├── auth.php           (Auth class)
│   ├── login.php          (Login view)
│   ├── register.php       (Registration)
│   ├── logout.php         (Logout handler)
│   └── database.php       (Deprecated - use includes/Database.php)
│
├── api/
│   ├── orders_api.php     (Order CRUD + status updates)
│   ├── menu_api.php       (Menu browsing - no auth required)
│   ├── meals_api.php      (Meal CRUD - admin only)
│   ├── users_api.php      (User management - admin only)
│   ├── add_to_cart.php    (Add items to cart)
│   └── cart_count.php     (Get cart item count)
│
├── includes/
│   └── Database.php       (MySQLi connection class)
│
├── views/
│   ├── admin/
│   │   ├── dashboard.php  (Admin interface)
│   │   ├── admin.js       (Admin JS logic)
│   │   └── styles.css     (Admin styling)
│   │
│   ├── manager/
│   │   └── dashboard.php  (Manager reporting)
│   │
│   ├── sales/
│   │   ├── dashboard.php  (Sales order management)
│   │   ├── sales.js       (Sales JS logic)
│   │   └── sales.css      (Sales styling)
│   │
│   └── user/
│       ├── customer_interface.php    (Menu browsing)
│       ├── customer.js               (Menu browsing JS)
│       ├── customer.css              (Menu styling)
│       ├── cart.php                  (View/edit cart)
│       ├── cart.css                  (Cart styling)
│       ├── checkout.php              (Order creation)
│       ├── customer_orders.php       (View orders)
│       ├── orders.js                 (Orders JS logic)
│       └── orders.css                (Orders styling)
│
├── menu/                  (Meal images stored here)
│   └── *.jpg/png
│
└── uploads/              (Other uploaded files)
```

---

## 15. Common Data Flows Diagram

```
CUSTOMER JOURNEY
════════════════════════════════════════════════════════════════════════
  Browse Menu              Add to Cart           View Cart            Checkout
      │                        │                    │                   │
      ├→ menu_api.php      ├→ add_to_cart.php   ├→ cart.php        ├→ checkout.php
      │  (categories)      │  (JSON POST)       │  (Form view)      │  (Form POST)
      │                    │                    │                    │
      └→ JS renders        └→ cart_count.php    └→ Update/Remove    └→ orders_api.php
         categories           (Get count)          quantities           (Create order)
         & meals

SALES WORKFLOW
════════════════════════════════════════════════════════════════════════
  View All Orders          View Order Details        Update Status
      │                          │                        │
      ├→ orders_api.php      ├→ Cached data           ├→ orders_api.php
      │  (GET list)          │  from first call       │  (POST update_status)
      │  (role=sales)        │                        │  (role=sales)
      │                      └→ JS renders            │
      └→ JS renders             modal with items      └→ Reload orders
         table of all orders                            (refresh list)

ADMIN MEAL MANAGEMENT
════════════════════════════════════════════════════════════════════════
  View Meals                 Create Meal              Delete Meal
      │                          │                        │
      ├→ meals_api.php       ├→ meals_api.php        ├→ meals_api.php
      │  (GET list)          │  (POST create)        │  (POST delete)
      │  (role=admin)        │  (FormData+file)      │  (role=admin)
      │                      │  (role=admin)         │
      └→ JS renders          └→ Move file → /menu/   └→ Reload meals
         meals table            Insert menu_item        (refresh list)
```

---

## 16. Error Handling Flow

```
User Action
    ↓
[Validation in View Layer]
  ├─ Empty fields?
  ├─ Invalid format?
  └─ Client-side checks

    ↓ [API Request]
    ↓
[Validation in API Layer]
  ├─ Check $_SESSION['id']
  ├─ Check role permissions
  ├─ Validate input parameters
  ├─ Prepared statement execution

    ↓ [Response]
    ├─ Success: { success: true, data: ... }
    ├─ Validation error: { success: false, message: '...' } (HTTP 400)
    ├─ Auth error: { success: false, message: '...' } (HTTP 401)
    ├─ Permission error: { success: false, message: '...' } (HTTP 403)
    └─ Server error: { success: false, message: '...' } (HTTP 500)

    ↓ [JavaScript Handler]
    ├─ IF success:
    │   └─ Update UI / Show success message
    └─ IF error:
        └─ Show alert with error message
```

---

## Summary

This restaurant ordering system implements a **modern, secure, and maintainable architecture** with:

✅ **Clear separation of concerns** (Views, APIs, Database)  
✅ **Role-based access control** (Customer, Sales, Admin, Manager)  
✅ **Secure authentication** (Session-based, password hashing)  
✅ **Comprehensive error handling** (HTTP codes, JSON responses)  
✅ **Database integrity** (Prepared statements, transactions)  
✅ **Responsive UI** (Bootstrap, vanilla JavaScript with fetch)  
✅ **Scalable design** (Easy to add new features/APIs)  

The system supports complete restaurant operations from menu management, customer ordering, cart management, checkout, order tracking, to sales reporting—all through well-defined data flows and secure APIs.
