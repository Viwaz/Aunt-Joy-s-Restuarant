<!-- Copilot / AI agent instructions for Aunt Joy's Restaurant PHP app -->
# Repository Overview

This is a small PHP monolith (no framework) intended to run under XAMPP/Apache + MySQL. Key top-level folders: `admin/`, `auth/`, `manager/`, `menu/`, `sales/`, `user/`, and `uploads/`. Entrypoints include `index.php`, `index2.php`, `login.php`, `register.php`, and pages under `user/` for the customer interfaces.

**Quick run notes:** place the repo in XAMPP `htdocs`, start Apache+MySQL, import `DB and entities.sql` and use the database name `aunt_joydb`. Default DB user is `root` (no password) in the local config files.

# Architecture & Patterns

- **Separation of Concerns** (Path A refactor): 
  - API endpoints in `api/` handle all logic (database queries, CRUD)
  - View files (dashboard.php, customer_interface.php) are HTML + minimal PHP for auth
  - JavaScript files (admin.js, customer.js) handle UI interactions and fetch calls
- **Database access**: Centralized `Database` class in `includes/Database.php`. All code imports with `require_once dirname(__DIR__) . '/includes/Database.php'` (adjust path depth).
- **Auth & sessions**: Session-based auth. Files call `session_start()` and check `$_SESSION['user_id']` and `$_SESSION['role']` to gate actions.
- **JSON APIs**: Endpoints in `api/` return JSON with `header("Content-Type: application/json")` at top. Use `$_GET` for queries, JSON body for POST data.
- **File-based assets**: Meal images in `menu/` folder; uploads in `uploads/`.
- **Styling**: Admin and customer dashboards use consistent purple gradient (`#667eea` → `#764ba2`) with sidebar layout.

# Important Files to Reference

- `DB and entities.sql` — canonical schema (tables: `users`, `menu_items`, `categories`, `cart`, `orders`, `order_items`). Note `price_MWK` column name and `menu_items` table rename during SQL.
- `includes/Database.php` — centralized DB connection class. All code imports from here.
- `api/meals_api.php` — meal CRUD (list, create, delete, toggle availability). Auth required for create/delete/toggle.
- `api/menu_api.php` — customer menu API (categories, meals by category, search). No auth required.
- `api/users_api.php` — user creation. Admin auth required.
- `admin/dashboard.php` — admin view (HTML + minimal PHP). Calls `api/meals_api.php` and `api/users_api.php`.
- `admin/admin.js` — admin UI: modals, form submissions, table rendering via fetch.
- `user/customer_interface.php` — customer view (HTML + minimal PHP). Calls `api/menu_api.php`.
- `user/customer.js` — customer UI: search, category filter, add to cart via fetch.

- # Conventions & Gotchas (for agents)

- **Session dependency**: Many pages assume `session_start()` runs and `$_SESSION['id']` and `$_SESSION['role']` exist. Do not remove or bypass session checks when editing endpoints.
- **Session Security & Role Management**: The system enforces strict session/role validation:
  - When a user logs in with a **different role** (e.g., admin in one tab, customer in another), the old session is destroyed and a new one starts (prevents role hijacking).
  - Login page shows a **warning** if user attempts to switch roles across tabs.
  - **All sensitive operations require role validation**: `add_to_cart.php`, `cart_count.php` only allow `role='customer'`. `orders_api.php` enforces role checks for different actions.
  - **Rules**: Customer can place orders, view own orders. Sales/Manager can view all orders and update status. Admin can CRUD meals and users.
  - If a request comes in with mismatched role (e.g., `role='admin'` calling `add_to_cart.php`), returns HTTP 403 "Unauthorized".
- **menu_api query params**: The customer menu endpoint accepts `?action=meals&category_id=<int>` (also accepts `?id=<int>` for backward compatibility). Use `category_id` in new code and docs.
- **search behavior**: `api/menu_api.php?action=search&keyword=...` will return an empty `"meals"` array when `keyword` is empty (the UI can choose to call the `meals` action to load all items instead).
- **Centralized Database class**: The `Database` class is now in `includes/Database.php`. All code imports with `require_once dirname(__DIR__) . '/includes/Database.php'` (adjust path depth for subdirectories).
- **SQL naming**: Some column names use `price_MWK` and `category` is an `int` foreign-key pointing to `categories.id`. Use the schema in `DB and entities.sql` as ground truth.
- **AJAX vs form posts**: APIs use fetch/JSON (e.g. `api/meals_api.php`). Views call these APIs from JavaScript. Never mix form POSTs with JSON APIs in the same endpoint.
- **View files (minimal PHP)**: `admin/dashboard.php` and `user/customer_interface.php` only contain auth checks and HTML. All logic moved to `api/` folder.
- **JavaScript orchestration**: `admin.js` and `customer.js` handle all modal/fetch/DOM operations. Views link to these files via `<script src="...js"></script>`.

# JSON Endpoints Checklist

| Endpoint | HTTP Method | Request Body | Response | Session Required |
|----------|------------|--------------|----------|------------------|
| `api/meals_api.php?action=list` | GET | None | `{"success": bool, "meals": [...]}` | Yes (admin) |
| `api/meals_api.php?action=create` | POST | Form data (multipart) | `{"success": bool, "message": string}` | Yes (admin) |
| `api/meals_api.php?action=delete` | POST | `{"id": int}` | `{"success": bool, "message": string}` | Yes (admin) |
| `api/meals_api.php?action=toggle` | POST | `{"id": int, "status": string}` | `{"success": bool, "status": string}` | Yes (admin) |
| `api/menu_api.php?action=categories` | GET | None | `{"success": bool, "categories": [...]}` | No |
| `api/menu_api.php?action=meals[&category_id=int]` | GET | None | `{"success": bool, "meals": [...]}` | No |
| `api/menu_api.php?action=search&keyword=str` | GET | None | `{"success": bool, "meals": [...]}` (returns empty `meals` array if `keyword` is empty) | No |
| `api/users_api.php?action=list` | GET | None | `{"success": bool, "users": [...]}` | Yes (admin) |
| `api/users_api.php?action=create` | POST | `{"username": str, "email": str, "password": str, "role": str}` | `{"success": bool, "message": string}` | Yes (admin) |
| `api/add_to_cart.php` | POST | `{"menu_item_id": int, "quantity": int}` | `{"success": bool, "message": string}` | Yes (customer role) |
| `api/cart_count.php` | GET | None | `{"success": bool, "count": int}` | Yes (customer role) |

# Examples (copy-paste safe snippets)

- Add to cart (client -> `api/add_to_cart.php`): send JSON body `{"menu_item_id": 3, "quantity": 2}`; endpoint returns JSON `{ "success": true }`.
- Load menu in JavaScript: `fetch('../api/menu_api.php?action=meals').then(r => r.json()).then(data => { /* render meals */ })`.
- Create meal (admin): POST to `api/meals_api.php?action=create` with FormData including file upload.
- Delete meal (admin): POST JSON `{"id": meal_id}` to `api/meals_api.php?action=delete`.

# Recommended Agent Behavior

- When modifying DB code, run the local SQL in `DB and entities.sql` to validate column/table names.
- Preserve session usage; if you add endpoints that should be private, require `$_SESSION['user_id']` and return JSON 401-like messages when missing.
- Prefer prepared statements as used in existing code. Follow existing `bind_param` style (`"iii"`, `"ss"`, etc.).
- When adding new Database includes, use: `require_once dirname(__DIR__) . '/includes/Database.php'` (adjust path depth based on subdirectory level).
- Always set `header("Content-Type: application/json");` at the top of JSON endpoint files for proper MIME type.

# Local dev steps (concise)

1. Put repo in XAMPP `htdocs`.
2. Start Apache & MySQL via XAMPP control panel.
3. Import `DB and entities.sql` in phpMyAdmin or via `mysql` CLI to create `aunt_joydb`.
4. Visit `http://localhost/Aunt_Joy's_Restuarant2/index.php` (or project folder name) to exercise pages.

# If you need more

If anything here is unclear or you'd like me to centralize the `Database` class, add API docs for all JSON endpoints, or generate simple integration tests for key endpoints, tell me which area to expand.
