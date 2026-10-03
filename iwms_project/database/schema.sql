CREATE DATABASE IF NOT EXISTS iwms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE iwms;

SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS stock_movements, order_items, orders, stock, products, customers, warehouses, suppliers, categories, users;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','staff') NOT NULL DEFAULT 'staff',
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  description VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE suppliers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  phone VARCHAR(30) DEFAULT NULL,
  email VARCHAR(120) DEFAULT NULL,
  address VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_supplier_name_phone (name, phone)
) ENGINE=InnoDB;

CREATE TABLE customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  phone VARCHAR(30) DEFAULT NULL,
  email VARCHAR(120) DEFAULT NULL,
  address VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE warehouses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  location VARCHAR(180) NOT NULL,
  capacity INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sku VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(150) NOT NULL,
  category_id INT NOT NULL,
  supplier_id INT NOT NULL,
  price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  reorder_level INT UNSIGNED NOT NULL DEFAULT 10,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_product_category FOREIGN KEY (category_id) REFERENCES categories(id),
  CONSTRAINT fk_product_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
) ENGINE=InnoDB;

CREATE TABLE stock (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  warehouse_id INT NOT NULL,
  quantity INT NOT NULL DEFAULT 0,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_product_warehouse (product_id, warehouse_id),
  CONSTRAINT fk_stock_product FOREIGN KEY (product_id) REFERENCES products(id),
  CONSTRAINT fk_stock_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(id)
) ENGINE=InnoDB;

CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_no VARCHAR(40) NOT NULL UNIQUE,
  type ENUM('purchase','sale') NOT NULL,
  supplier_id INT DEFAULT NULL,
  customer_id INT DEFAULT NULL,
  warehouse_id INT NOT NULL,
  status ENUM('completed','cancelled') NOT NULL DEFAULT 'completed',
  total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  created_by INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_order_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
  CONSTRAINT fk_order_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
  CONSTRAINT fk_order_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
  CONSTRAINT fk_order_user FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT UNSIGNED NOT NULL,
  unit_price DECIMAL(12,2) NOT NULL,
  subtotal DECIMAL(12,2) AS (quantity * unit_price) STORED,
  CONSTRAINT fk_item_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_item_product FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB;

CREATE TABLE stock_movements (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  warehouse_id INT NOT NULL,
  movement_type ENUM('IN','OUT','ADJUSTMENT') NOT NULL,
  quantity INT NOT NULL,
  reference_type VARCHAR(30) DEFAULT NULL,
  reference_id INT DEFAULT NULL,
  notes VARCHAR(255) DEFAULT NULL,
  created_by INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_move_product FOREIGN KEY (product_id) REFERENCES products(id),
  CONSTRAINT fk_move_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
  CONSTRAINT fk_move_user FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

INSERT INTO users (full_name, username, password_hash, role) VALUES
('System Administrator','admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4nYjv9o8QKx9K9K9K9K9K9K9K9K9K9K9K9K9K9', 'admin'),
('Warehouse Staff','staff', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4nYjv9o8QKx9K9K9K9K9K9K9K9K9K9K9K9K9K9', 'staff');

-- Replace seeded hashes at first run if your PHP version rejects them; setup.php creates known hashes.
INSERT INTO categories (name, description) VALUES
('Electronics','Electronic devices and accessories'),
('Office Supplies','Stationery and office essentials'),
('Hardware','Tools and hardware items');

INSERT INTO suppliers (name, phone, email, address) VALUES
('TechSource Pvt Ltd','9876543210','sales@techsource.example','Hyderabad'),
('Prime Office Supplies','9123456780','orders@primeoffice.example','Secunderabad'),
('BuildRight Distributors','9988776655','sales@buildright.example','Kukatpally');

INSERT INTO customers (name, phone, email, address) VALUES
('KLH Campus Store','9000000001','store@example.com','Bachupally'),
('Metro Retail','9000000002','metro@example.com','Hyderabad');

INSERT INTO warehouses (name, location, capacity) VALUES
('Main Warehouse','Bachupally',5000),
('Secondary Warehouse','Kukatpally',2500);

INSERT INTO products (sku, name, category_id, supplier_id, price, reorder_level) VALUES
('LAP-001','Laptop',1,1,65000,10),
('MOU-001','Wireless Mouse',1,1,850,20),
('PEN-001','Gel Pen Pack',2,2,120,30),
('TOOL-001','Tool Kit',3,3,1800,10),
('KEY-001','Mechanical Keyboard',1,1,3200,15);

INSERT INTO stock (product_id, warehouse_id, quantity) VALUES
(1,1,24),(2,1,65),(3,1,120),(4,1,8),(5,1,18),
(1,2,5),(2,2,20),(3,2,40),(4,2,12),(5,2,4);

INSERT INTO stock_movements (product_id, warehouse_id, movement_type, quantity, reference_type, notes, created_by) VALUES
(1,1,'IN',24,'SEED','Initial stock',1),(2,1,'IN',65,'SEED','Initial stock',1),(3,1,'IN',120,'SEED','Initial stock',1),(4,1,'IN',8,'SEED','Initial stock',1),(5,1,'IN',18,'SEED','Initial stock',1),
(1,2,'IN',5,'SEED','Initial stock',1),(2,2,'IN',20,'SEED','Initial stock',1),(3,2,'IN',40,'SEED','Initial stock',1),(4,2,'IN',12,'SEED','Initial stock',1),(5,2,'IN',4,'SEED','Initial stock',1);
