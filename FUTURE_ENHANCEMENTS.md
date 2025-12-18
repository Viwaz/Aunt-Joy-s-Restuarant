# Future Enhancements & Improvements

This document outlines feedback from code review and planned features for Aunt Joy's Restaurant PHP app.

---

## User Management Enhancements

### 1. User Activation/Deactivation (instead of deletion)
- **Current**: Users are deleted from the database
- **Improvement**: Add `is_active` boolean column to `users` table (default: `1`)
- **Behavior**: Deactivate users instead of deleting. Admin can reactivate via dashboard toggle
- **API**: `api/users_api.php?action=toggle_status` with `{"id": int, "is_active": bool}`
- **Benefits**: Preserves order history, maintains referential integrity, audit trail

### 2. Role Management via Numeric IDs
- **Current**: Roles stored as strings (`"admin"`, `"customer"`, `"manager"`, `"sales"`)
- **Improvement**: Use numeric role IDs for faster DB queries and reduced storage
  - `0` = Admin
  - `1` = Customer
  - `2` = Manager
  - `3` = Sales
- **Migration**: Update `users` table `role` column from VARCHAR to INT, backfill existing roles

### 3. Default Password Generation & Email
- **Current**: Manual password creation during user registration
- **Improvement**: 
  - Admin creates user → system generates random secure password
  - Email password to user's registered email address
  - User must change password on first login
  - Implement password reset flow via email token

---

## Activity & Logging Enhancements

### 4. User Activity Tracking
- **Current**: No activity logging
- **Improvement**: Create `user_activity_logs` table
  ```
  - id (PK)
  - user_id (FK)
  - action (login, logout, order_placed, meal_viewed, etc.)
  - timestamp
  - ip_address
  - user_agent
  ```
- **Features**:
  - Log login/logout times
  - Track order placements, cart modifications, searches
  - Admin dashboard widget showing recent activities

### 5. Historical Audit Trail
- **Current**: No historical record of order/meal changes
- **Improvement**: Create `audit_logs` table
  ```
  - id (PK)
  - entity_type (user, meal, order, etc.)
  - entity_id
  - action (create, update, delete, activate, deactivate)
  - old_values (JSON)
  - new_values (JSON)
  - changed_by (user_id)
  - timestamp
  ```
- **Benefits**: Full visibility into system changes, compliance, debugging

---

## Meal & Order Management Enhancements

### 6. New Category Management
- **Current**: Categories managed via raw SQL
- **Improvement**: Add admin UI for category CRUD
  - API: `api/categories_api.php` (list, create, update, delete, toggle_active)
  - Admin dashboard modal for category management
  - Prevent deletion if meals exist (offer deactivate instead)

### 7. Meal Activation/Deactivation
- **Current**: `available` column toggles availability
- **Improvement**: Extend to `is_active` flag (separate from `available`)
  - `is_active = 0`: Meal is hidden/archived (admin only, no customer view)
  - `available = 0`: Meal is temporarily out of stock (shows as "Currently Unavailable")
  - Allow bulk activation/deactivation in admin dashboard

### 8. Order Status & Lifecycle Management
- **Current**: Basic order creation
- **Improvement**: Implement order status pipeline
  ```
  Statuses: pending → confirmed → preparing → ready → delivered → completed
  Also: cancelled (with cancellation reason)
  ```
- **Features**:
  - Timeline view of order status changes
  - Notifications when order status updates
  - Only allow state transitions based on user role (customer can cancel pending, manager/sales can update status)

### 9. Fixed Quantity & Unit Price Management
- **Current**: Price stored in `menu_items`, quantity variable per order
- **Improvement**: 
  - Store `unit_price` in `order_items` (snapshot of meal price at order time)
  - Implement inventory/quantity management in `menu_items` table
    ```
    - quantity_in_stock (INT)
    - reorder_level (INT)
    - last_restocked (TIMESTAMP)
    ```
  - Prevent ordering if quantity insufficient
  - Alert admin when stock falls below reorder level

### 10. Recalculate Order Total (Remove from Storage)
- **Current**: `total` stored in `orders` table
- **Improvement**:
  - Remove `total` column from `orders`
  - Calculate total on-the-fly in API:
    ```php
    $total = SUM(order_items.quantity * order_items.unit_price)
    ```
  - API returns calculated total in JSON response
  - Benefits: Prevents price manipulation, single source of truth

---

## Cart & Checkout Enhancements

### 11. Session-Based Cart (No Database Storage)
- **Current**: Cart stored in database `cart` table
- **Improvement**: Move cart to session/localStorage
  - Cart data: `$_SESSION['cart'] = [{"menu_item_id": 3, "quantity": 2}, ...]`
  - Reduce DB queries during browsing
  - Cart persists for logged-in user; cleared on logout
  - Database only records completed orders (order_items), not pending carts
  - Benefits: Faster performance, simpler DB schema

---

## Authentication & Checkout Enhancements

### 12. Login-Required Checkout
- **Current**: Customers can add to cart without login
- **Improvement**:
  - Allow browsing menu without login
  - Require login **only** when attempting to place order
  - Redirect to login modal when "Place Order" clicked
  - After login, restore cart and proceed to checkout
  - Show cart in checkout with customer's saved address (see #13)

### 13. Pre-Filled Delivery Address
- **Current**: Address entered manually each time
- **Improvement**:
  - Store default address in `users` table:
    ```
    - default_address (TEXT)
    - default_city (VARCHAR)
    - default_phone (VARCHAR)
    ```
  - On checkout, pre-fill saved address
  - Allow customer to edit address if needed (only update on checkout, not in profile)
  - Show address change warning: "Changing address will affect delivery"

---

## Payment & Pricing Enhancements

### 14. Payment Method Management
- **Current**: No payment processing
- **Improvement**: Add `payments` table
  ```
  - id (PK)
  - order_id (FK)
  - payment_method (credit_card, mobile_money, cash_on_delivery, etc.)
  - amount
  - status (pending, completed, failed, refunded)
  - transaction_id (external payment processor)
  - timestamp
  ```
- **Features**:
  - Support multiple payment methods
  - Update payment status (e.g., from "pending" to "completed")
  - Show payment status in order details

### 15. Dynamic Pricing & Promotions
- **Improvement**: Add future support for
  - Discount codes / coupon system
  - Volume discounts
  - Seasonal pricing
  - Category-level pricing adjustments

---

## UI/UX Enhancements

### 16. E-commerce Design Overhaul
- **Current**: Simple admin/customer interface
- **Improvement**: Redesign customer portal to match market leaders
  - **Inspiration**: Alibaba/AliExpress, Amazon, eBay
  - **Features**:
    - Large product images with carousel/zoom
    - Category filters & faceted search (price range, ratings, availability)
    - Product cards: image, name, price, stars, quick-add-to-cart button
    - Persistent cart badge (top-right) with item count
    - Wishlist / save for later
    - Customer reviews & ratings per meal
    - Product comparison view
    - Checkout progress indicator
    - Order tracking page with status timeline

### 17. Unified Admin & Customer Interface
- **Current**: Separate dashboards
- **Improvement**:
  - Consistent purple gradient theme across all pages
  - Unified navigation (hamburger menu on mobile)
  - Responsive design (mobile-first approach)
  - Dark mode support (optional)
  - Loading spinners and skeleton screens

---

## Data Management & Compliance

### 18. System Backup Management
- **Current**: Manual backups
- **Improvement**: Add admin backup control panel
  - Set automatic backup frequency: 24 hours or 48 hours
  - Manual backup trigger button
  - Create `backups` table:
    ```
    - id (PK)
    - backup_file (path/filename)
    - backup_type (automatic, manual)
    - created_at (TIMESTAMP)
    - size_mb (DECIMAL)
    ```
  - Store backups in `uploads/backups/`
  - Admin can view backup history and download/restore (restore to staging first)
  - Automated cleanup: delete backups older than 30 days

### 19. Data Export & Reporting
- **Improvement**: Add reporting features
  - Export orders as CSV/Excel
  - Sales dashboard: daily/weekly/monthly revenue
  - Top-selling meals report
  - Customer acquisition funnel
  - Meal popularity trends

---

## Security Enhancements

### 20. Enhanced Session Management
- **Current**: Basic session-based auth
- **Improvement**:
  - Add session timeout (e.g., 30 minutes of inactivity)
  - Implement CSRF token protection on all forms
  - Add rate limiting on login endpoint (prevent brute force)
  - Log failed login attempts
  - Send login notification emails (unusual location/device)

### 21. Password & Email Security
- **Current**: Plain text password handling
- **Improvement**:
  - Enforce strong password policy (min 8 chars, upper/lower/number/symbol)
  - Add email verification on registration
  - Implement password reset via secure token (expires in 1 hour)
  - Add two-factor authentication (2FA) option for admin users

---

## Database Optimizations

### 22. Performance & Indexing
- Add indexes on frequently queried columns:
  - `orders.user_id`
  - `orders.created_at`
  - `menu_items.category_id`
  - `menu_items.is_active`
  - `user_activity_logs.user_id`
  - `user_activity_logs.timestamp`

---

## Implementation Priority (Recommended Order)

### **Phase 1** (High Priority - Core Functionality)
1. User activation/deactivation (not delete)
2. Role management via numeric IDs (migration required)
3. Login-required checkout
4. Pre-filled delivery address
5. Remove total from storage, calculate on-the-fly

### **Phase 2** (Medium Priority - Logging & Management)
1. User activity tracking
2. Audit logs
3. System backup management
4. Order status lifecycle

### **Phase 3** (Medium Priority - UI/UX)
1. Category management UI
2. E-commerce design overhaul
3. Meal activation/deactivation UI

### **Phase 4** (Lower Priority - Advanced Features)
1. Payment method management
2. Default password generation & email
3. Inventory management
4. Data export & reporting
5. Enhanced session management

---

## Notes for Development

- Test all changes locally in XAMPP before deployment
- Update `DB and entities.sql` with new schema changes
- Preserve backward compatibility where possible
- Document all new API endpoints in this file and main `copilot-instructions.md`
- Review security implications before implementing each feature

