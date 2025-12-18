# Aunt Joy's Restaurant - Web Application

A PHP-based restaurant management system with admin dashboard, customer ordering portal, and sales/manager interfaces.

---

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Quick Start](#quick-start)
- [Installation](#installation)
- [Database Setup](#database-setup)
- [Project Structure](#project-structure)
- [User Roles](#user-roles)
- [Key Features by Role](#key-features-by-role)
- [API Endpoints](#api-endpoints)
- [Configuration](#configuration)
- [File Uploads](#file-uploads)
- [Security](#security)
- [Troubleshooting](#troubleshooting)
- [Future Enhancements](#future-enhancements)
- [Support](#support)

---

## Overview

Aunt Joy's Restaurant is a full-featured web application for managing meal orders, menu items, categories, and users. It provides:

- **Admin Dashboard**: Manage meals, categories, and users
- **Customer Portal**: Browse menu, add to cart, place orders
- **Sales/Manager Dashboard**: Track and manage orders
- **Role-Based Access Control**: Different interfaces for admin, customer, sales, and manager
- **Activity Tracking**: Monitor user activities (login, logout, add to cart)
- **Audit Logging**: Track all admin changes for compliance

---

## Features

### Core Functionality
✅ User authentication and authorization  
✅ Role-based access control (Admin, Customer, Manager, Sales)  
✅ Menu management with categories and images  
✅ Shopping cart system  
✅ Order placement and tracking  
✅ User account management  
✅ Activity logging and audit trail  

### Admin Features
✅ Create/edit/delete meals and categories  
✅ Manage user accounts (create, deactivate)  
✅ View all orders and their status  
✅ Toggle meal availability  
✅ View activity logs  

### Customer Features
✅ Browse menu by category  
✅ Search for meals  
✅ Add meals to cart  
✅ Place orders with delivery address  
✅ Track order status  
✅ View order history  

### Manager/Sales Features
✅ View all orders  
✅ Update order status  
✅ Track deliveries  
✅ View sales reports  

---

## Tech Stack

### Backend
- **Language**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Session Management**: PHP Sessions
- **PDF Generation**: TCPDF (for invoices/reports)

### Frontend
- **HTML5/CSS3**
- **JavaScript (Vanilla)**
- **Bootstrap/Custom CSS** (responsive design)
- **Fetch API** (for AJAX calls)

### Infrastructure
- **Server**: Apache (XAMPP)
- **Port**: 80 (default HTTP)
- **Database Port**: 3306 (MySQL)

---

## Quick Start

### Prerequisites
- XAMPP installed (Apache + MySQL + PHP)
- Git (optional, for cloning)

### Setup (5 minutes)

1. **Clone/Place Repository**
```bash
# Copy the Aunt_Joy's_Restuarant folder to:
C:\xampp\htdocs\Aunt_Joy's_Restuarant
```

2. **Start XAMPP Services**
```bash
# Open XAMPP Control Panel
# Click "Start" for Apache and MySQL
```

3. **Import Database**
```bash
# Open browser: http://localhost/phpmyadmin
# Create new database: aunt_joydb
# Import: includes/DB and entities.sql
```

4. **Access Application**
```
http://localhost/Aunt_Joy's_Restuarant/index.php
```

5. **Login with Default Admin**
```
Email: admin@1
Password: (check DB and entities.sql for hashed password)
```

---

## Installation

### Step 1: Download/Clone Repository

```bash
# Option A: Clone from repository
git clone https://github.com/your-repo/aunt-joys-restaurant.git

# Option B: Download ZIP and extract to:
C:\xampp\htdocs\Aunt_Joy's_Restuarant
```

### Step 2: Start XAMPP

```bash
# Windows:
# 1. Open C:\xampp\xampp-control.exe
# 2. Click "Start" next to Apache
# 3. Click "Start" next to MySQL
# Note: Port should be 3307 for MySQL (if 3306 is occupied)
```

### Step 3: Verify File Permissions

```bash
# Ensure the following directories are writable:
# - menu/ (for meal images)
# - uploads/ (for file uploads)

# On Windows, this is usually automatic
# On Linux/Mac:
chmod 755 menu/
chmod 755 uploads/
```

### Step 4: Configure Database Connection (if needed)

Edit `includes/Database.php`:

```php
private $host = "127.0.0.1";      // Database host
private $port = "3307";            // MySQL port (change if needed)
private $db_name = "aunt_joydb";  // Database name
private $username = "root";        // DB user
private $password = "";            // DB password (empty by default)
```

---

## Database Setup

### Option 1: Import Complete Schema (Recommended)

```bash
# 1. Open phpMyAdmin:
http://localhost/phpmyadmin

# 2. Create database:
# - Click "New"
# - Database name: aunt_joydb
# - Collation: utf8mb4_unicode_ci
# - Click "Create"

# 3. Select aunt_joydb database

# 4. Go to "Import" tab
# - Select file: includes/DB and entities.sql
# - Click "Import"
```

### Option 2: Manual SQL Import

```bash
# Using MySQL CLI:
mysql -u root -p aunt_joydb < includes/DB\ and\ entities.sql

# If no password:
mysql -u root aunt_joydb < includes/DB\ and\ entities.sql
```

### Database Schema

**Tables Created**:
- `users` - User accounts (admin, customer, sales, manager)
- `categories` - Meal categories (Breakfast, Lunch, Dinner, Drinks)
- `menu_items` - Meals with prices and images
- `cart` - Shopping cart items
- `orders` - Placed orders
- `order_items` - Order line items
- `audit_log` - Admin action audit trail
- `user_activity_logs` - User activity tracking

**Default Admin User**:
```
Email: admin@1
Password: (hashed in database)
Role: admin
```

---

## Project Structure

```
Aunt_Joy's_Restuarant/
├── api/                          # REST API endpoints
│   ├── add_to_cart.php          # Add item to cart
│   ├── cart_count.php           # Get cart item count
│   ├── meals_api.php            # Meal management (admin)
│   ├── menu_api.php             # Menu browsing (customer)
│   ├── orders_api.php           # Order management
│   ├── users_api.php            # User management (admin)
│   ├── categories_api.php       # Category management
│   ├── activity_logs_api.php    # Activity tracking
│   └── user_profile_api.php     # User profile/address
│
├── auth/                         # Authentication
│   ├── auth.php                 # Auth class
│   ├── login.php                # Login page
│   ├── logout.php               # Logout handler
│   ├── register.php             # User registration
│   └── style.css                # Auth styles
│
├── includes/                     # Core classes and config
│   ├── Database.php             # Database connection
│   ├── AuditLog.php             # Audit logging helper
│   ├── UserActivityLog.php      # Activity logging helper
│   ├── user.php                 # User model
│   ├── meal.php                 # Meal model
│   └── DB and entities.sql      # Database schema
│
├── views/                        # Dashboard views
│   ├── admin/                   # Admin dashboard
│   │   ├── dashboard.php
│   │   ├── admin.js
│   │   ├── api-client.js
│   │   └── styles.css
│   ├── manager/                 # Manager dashboard
│   ├── sales/                   # Sales dashboard
│   └── user/                    # Customer interface
│       ├── customer_interface.php
│       ├── customer.js
│       └── styles/
│
├── menu/                         # Meal images folder
│   └── *.jpg, *.png, *.webp
│
├── uploads/                      # User uploads folder
│   └── (for future use)
│
├── vendor/                       # Third-party libraries
│   └── tecnickcom/tcpdf/       # PDF generation library
│
├── index.php                     # Home page
├── index2.php                    # Alternative entry
├── FUTURE_ENHANCEMENTS.md       # Planned features
├── README.md                     # This file
└── notes.txt                     # Dev notes
```

---

## User Roles

### 1. Admin
**Access Level**: Full system access

**Responsibilities**:
- Create/edit/delete meals
- Manage meal categories
- Create and deactivate users
- View all orders
- View activity logs and audit trails
- Toggle meal availability

**Dashboard**: `/views/admin/dashboard.php`

### 2. Customer
**Access Level**: Menu browsing and ordering

**Responsibilities**:
- Browse menu
- Search meals
- Add to cart
- Place orders
- Track orders
- View order history

**Dashboard**: `/views/user/customer_interface.php`

### 3. Manager
**Access Level**: Order management

**Responsibilities**:
- View all orders
- Update order status
- Track order fulfillment
- View sales metrics

**Dashboard**: `/views/manager/dashboard.php`

### 4. Sales
**Access Level**: Order tracking

**Responsibilities**:
- View orders
- Update order status
- Generate sales reports

**Dashboard**: `/views/sales/dashboard.php`

---

## Key Features by Role

### Admin Dashboard
| Feature | Action |
|---------|--------|
| Manage Meals | Create, edit, delete, toggle availability |
| Manage Categories | Add new categories |
| Manage Users | Create users, deactivate accounts |
| View Orders | See all orders with details |
| View Activity | Check user login/logout times, activities |
| Audit Trail | Review all admin changes |

### Customer Interface
| Feature | Action |
|---------|--------|
| Browse Menu | View all meals by category |
| Search | Find meals by keyword |
| Add to Cart | Select quantity and add |
| View Cart | See cart items and total |
| Checkout | Enter address and place order |
| Track Order | View order status |
| Order History | See past orders |

### Manager/Sales Dashboard
| Feature | Action |
|---------|--------|
| View Orders | See all pending/in-progress orders |
| Update Status | Change order status (pending → en route → delivered) |
| Customer Info | View delivery address and contact |
| Order Items | See meal items in each order |
| Reports | View order counts and sales |

---

## API Endpoints

### Authentication
```
POST /auth/login.php              # User login
POST /auth/register.php           # User registration
GET  /auth/logout.php             # User logout
```

### Menu (Customer)
```
GET  /api/menu_api.php?action=categories              # Get categories
GET  /api/menu_api.php?action=meals&category_id={id} # Get meals by category
GET  /api/menu_api.php?action=search&keyword={term}  # Search meals
GET  /api/cart_count.php                              # Get cart count
```

### Cart
```
POST /api/add_to_cart.php         # Add item to cart
GET  /api/cart_count.php          # Get item count
```

### Orders
```
GET  /api/orders_api.php?action=list                  # Get orders
POST /api/orders_api.php?action=update_status         # Update status
```

### Admin Only
```
GET  /api/meals_api.php?action=list                   # List all meals
POST /api/meals_api.php?action=create                 # Create meal
POST /api/meals_api.php?action=delete                 # Delete meal
POST /api/meals_api.php?action=toggle                 # Toggle availability
GET  /api/users_api.php?action=list                   # List users
POST /api/users_api.php?action=create                 # Create user
POST /api/users_api.php?action=delete                 # Deactivate user
```

### Categories
```
GET  /api/categories_api.php?action=list              # List categories
POST /api/categories_api.php?action=create            # Create category (admin)
POST /api/categories_api.php?action=update            # Update category (admin)
POST /api/categories_api.php?action=delete            # Delete category (admin)
```

### Activity & Profile
```
GET  /api/activity_logs_api.php?action=user_history  # Get user activity
GET  /api/activity_logs_api.php?action=login_logout  # Get login/logout times
GET  /api/user_profile_api.php?action=get            # Get user profile
POST /api/user_profile_api.php?action=update_address_json  # Update address
```

---

## Configuration

### Database Configuration
File: `includes/Database.php`

```php
private $host = "127.0.0.1";
private $port = "3307";
private $db_name = "aunt_joydb";
private $username = "root";
private $password = "";
```

**Note**: Update port and credentials if your setup differs

### Session Configuration
PHP sessions are used for authentication. Session files are stored in:
```
C:\xampp\php\tmp\
```

### Timezone (Optional)
To set timezone, add to relevant PHP files:
```php
date_default_timezone_set('Africa/Blantyre');
```

---

## File Uploads

### Meal Images
- **Location**: `menu/` folder
- **Supported Formats**: JPG, PNG, WebP
- **Max Size**: 5MB (recommended)
- **Resize**: Consider resizing images before upload for performance

### User Profile Pictures (Future)
- **Location**: `uploads/profiles/`
- **Will be implemented in Phase 2

---

## Security

### Implemented
✅ **Password Hashing**: bcrypt (PHP's `password_hash()`)  
✅ **Session-Based Auth**: PHP `$_SESSION`  
✅ **Role-Based Access Control**: Checked on each request  
✅ **Prepared Statements**: Prevent SQL injection  
✅ **Audit Logging**: Track all admin actions  
✅ **IP Capture**: Log user IP and user agent  
✅ **Soft Deletion**: Deactivate users instead of deleting  

### Best Practices
✅ Never display error messages to users (log instead)  
✅ Use HTTPS in production  
✅ Regularly backup database  
✅ Keep dependencies updated  
✅ Use strong admin password  
✅ Implement rate limiting on login endpoint  

### To Implement (Future Phases)
⏳ CSRF token protection  
⏳ Session timeout  
⏳ Email verification  
⏳ Two-factor authentication  
⏳ Password strength enforcement  

---

## Troubleshooting

### XAMPP Won't Start
```
Solution:
1. Check if ports 80 (Apache) or 3306/3307 (MySQL) are in use
2. Close conflicting programs
3. Try changing port in xampp-control.exe
4. Restart XAMPP
```

### Database Connection Error
```
Error: "Connection error: Can't connect to MySQL"

Solution:
1. Start MySQL from XAMPP Control Panel
2. Verify port: Check XAMPP MySQL port (usually 3306 or 3307)
3. Update includes/Database.php with correct port
4. Check username and password in Database.php
```

### 404 Error on Access
```
Solution:
1. Ensure folder is in C:\xampp\htdocs\
2. Use correct URL: http://localhost/Aunt_Joy's_Restuarant/
3. Check folder name matches URL
4. Restart Apache
```

### Sessions Not Working
```
Solution:
1. Check PHP session path: C:\xampp\php\tmp\
2. Ensure folder exists and is writable
3. Verify session.save_path in php.ini
4. Test with phpinfo();
```

### Images Not Showing
```
Solution:
1. Check menu/ folder exists
2. Verify file paths in database match actual files
3. Check file permissions (should be readable)
4. Verify image format is supported
```

### Login Loop (Keeps Redirecting)
```
Solution:
1. Check session variables are being set in auth.php
2. Clear browser cookies
3. Check role column in users table has correct value
4. Verify user is_active = 1
```

---

## Future Enhancements

This application has a roadmap for future features organized in phases:

**Phase 1**: User activity tracking, category management, address pre-population  
**Phase 2**: Session-based cart, order total calculation, meal activation toggle  
**Phase 3**: Numeric role IDs, backup system, order status timeline  
**Phase 4**: Default password generation, e-commerce redesign, inventory management  

See **FUTURE_ENHANCEMENTS.md** for detailed specifications.

---

## Support

### Getting Help

1. **Check FUTURE_ENHANCEMENTS.md** for planned features
2. **Review API_ENDPOINTS_PHASE1.md** for API documentation (if created)
3. **Check error logs**: 
   - PHP: `C:\xampp\apache\logs\error.log`
   - Browser Console: Press F12 → Console tab
4. **Test Endpoints**: Use Postman or similar tool

### Common Questions

**Q: How do I change the admin password?**  
A: Update the password hash in the `users` table via phpMyAdmin

**Q: How do I add a new meal category?**  
A: Use the admin dashboard or direct SQL:
```sql
INSERT INTO categories (category_name) VALUES ('New Category');
```

**Q: How do I backup the database?**  
A: In phpMyAdmin, select database → Export → Download

**Q: Can I use this in production?**  
A: Not yet - implement security enhancements from Phase 1 first (CSRF, session timeout, HTTPS)

---

## Development Team

- **Original Version**: PHP Monolith
- **Current Version**: 1.0 (Enhanced with activity logging and category management)
- **Last Updated**: December 18, 2025

---

## License

[Add your license here]

---

## Contact

For questions or issues, contact the development team or create an issue in the repository.

---

**Thank you for using Aunt Joy's Restaurant!** 🍽️

