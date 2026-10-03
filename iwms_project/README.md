# Inventory & Warehouse Management System (IWMS)

A complete DBMS mini-project implementation matching the supplied project brief. It uses MySQL for persistent relational data, PHP for the backend/API, and HTML/CSS/JavaScript for the frontend. The dashboard refreshes from the database every 5 seconds to provide live stock visibility.

## Features
- Admin/staff login with role-based access.
- Product CRUD with SKU, category, supplier, price and reorder level.
- Supplier CRUD.
- Customer CRUD for sales orders.
- Warehouse CRUD with capacity/location.
- Live stock by product and warehouse.
- Manual stock-in/stock-out with transaction-safe updates and movement history.
- Purchase orders automatically increase stock.
- Sales orders automatically decrease stock and reject insufficient stock.
- Order detail view and history.
- Low-stock alerts.
- Dashboard KPIs and recent orders.
- SQL-backed reports for stock value, purchases, sales, warehouse stock and top sold products.
- Admin user creation and account enable/disable.
- Parameterized SQL, foreign keys, unique constraints and database transactions.

## Installation with XAMPP (Windows)
1. Install XAMPP with Apache + MySQL.
2. Copy the `iwms` folder into `C:\xampp\htdocs\`.
3. Start Apache and MySQL from XAMPP.
4. Open `http://localhost/iwms/setup.php` once.
5. Open `http://localhost/iwms/login.php`.
6. Demo admin: `admin / admin123`.
7. Demo staff: `staff / staff123`.
8. After setup, delete or rename `setup.php` for safety.

If your MySQL root account has a password, edit `includes/config.php` and set `DB_PASS`.

## Review/demo flow
1. Login as admin.
2. Dashboard: show live KPIs and low-stock alerts.
3. Products: add/edit/archive a product.
4. Suppliers/Customers/Warehouses: demonstrate CRUD.
5. Stock: perform Stock In and Stock Out; the quantity changes immediately.
6. Orders: create a purchase and show stock increasing; create a sale and show stock decreasing.
7. Try a sale larger than available stock: the transaction is rejected without changing stock.
8. Movements: show the audit trail.
9. Reports: show stock value, purchase/sales totals, warehouse summary and top sold products.
10. Login as staff and show restricted admin actions.

## Database design
The supplied presentation describes a normalized relational design around Supplier, Product, Warehouse, Stock, Orders and Order Items, with category/customer concepts used by the system. This implementation keeps those core relationships and adds Users, Categories, Customers and Stock Movements so every UI feature is fully persistent and auditable.
