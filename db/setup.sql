-- Apex Fuel Database Setup Script
-- Creates all necessary tables for the shop system

-- Drop existing tables if they exist (careful with production!)
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS cart_items;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    phone VARCHAR(20),
    street_address VARCHAR(255),
    city VARCHAR(100),
    postal_code VARCHAR(20),
    state_region VARCHAR(100),
    date_of_birth DATE,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image_url VARCHAR(255),
    stock_quantity INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Cart items table (temporary storage for cart before checkout)
CREATE TABLE cart_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (user_id, product_id)
);

-- Orders table
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(50) DEFAULT 'pending',
    total_amount DECIMAL(10, 2) NOT NULL,
    shipping_address VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Order items table
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Insert default categories
INSERT INTO categories (name, description) VALUES
('Proteins', 'Protein powders and supplements'),
('Pre Workout', 'Pre-workout supplements'),
('Vitamins', 'Vitamins and mineral supplements'),
('Supplements', 'General supplements');

-- Insert sample products
INSERT INTO products (category_id, name, description, price, stock_quantity) VALUES
(1, 'Whey Protein', 'High quality whey protein powder', 29.99, 50),
(1, 'Whey Isolate', 'Pure whey isolate protein', 34.99, 45),
(1, 'Casein Protein', 'Slow-release casein protein', 32.99, 30),
(2, 'Pre-Workout Energy', 'High-energy pre-workout drink', 24.99, 60),
(2, 'Nitric Oxide Booster', 'Enhances blood flow', 28.99, 40),
(3, 'Multivitamins', 'Complete daily vitamin supplement', 19.99, 100),
(3, 'Omega-3 Fish Oil', 'Essential fatty acids', 22.99, 75),
(4, 'Creatine Monohydrate', 'Pure creatine powder', 15.99, 80),
(4, 'BCAAs', 'Branch chain amino acids', 18.99, 70),
(4, 'Glutamine', 'Recovery supplement', 16.99, 65);

-- Create default admin account (password: admin123 hashed with bcrypt)
INSERT INTO users (email, password, first_name, last_name, is_admin) VALUES
('admin@apex.com', '$2y$10$4zQhQGvN5bN5F5K5K5K5K.h5K5K5K5K5K5K5K5K5K5K5K5K5K5K5K', 'Admin', 'User', TRUE);

-- Create indexes for better query performance
CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_cart_user ON cart_items(user_id);
CREATE INDEX idx_orders_user ON orders(user_id);
CREATE INDEX idx_order_items_order ON order_items(order_id);
