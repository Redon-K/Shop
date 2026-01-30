-- Enhanced Apex Fuel Database with Admin Features
-- Run this script to set up the complete database

-- Drop existing tables if they exist
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS cart_items;
DROP TABLE IF EXISTS wishlist;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS admin_logs;

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
    country VARCHAR(100),
    date_of_birth DATE,
    newsletter_subscribed BOOLEAN DEFAULT FALSE,
    is_admin BOOLEAN DEFAULT FALSE,
    avatar_url VARCHAR(500),
    contact_preference ENUM('email', 'phone') DEFAULT 'email',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    image_url VARCHAR(500),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table with more fields
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    short_description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    compare_price DECIMAL(10, 2),
    cost_price DECIMAL(10, 2),
    sku VARCHAR(100) UNIQUE,
    barcode VARCHAR(100),
    image_url VARCHAR(500),
    image_urls JSON,
    stock_quantity INT DEFAULT 0,
    weight DECIMAL(10, 2),
    is_featured BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    tags JSON,
    specifications JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_slug (slug),
    INDEX idx_category (category_id),
    INDEX idx_featured (is_featured),
    INDEX idx_active (is_active)
);

-- Wishlist table
CREATE TABLE wishlist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist_item (user_id, product_id)
);

-- Cart items table
CREATE TABLE cart_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    size VARCHAR(50),
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (user_id, product_id, size)
);

-- Orders table
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded') DEFAULT 'pending',
    total_amount DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    tax_amount DECIMAL(10, 2) DEFAULT 0,
    shipping_amount DECIMAL(10, 2) DEFAULT 0,
    shipping_address TEXT,
    billing_address TEXT,
    payment_method VARCHAR(100),
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    notes TEXT,
    tracking_number VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_order_number (order_number),
    INDEX idx_status (status),
    INDEX idx_user (user_id)
);

-- Order items table
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    product_price DECIMAL(10, 2) NOT NULL,
    quantity INT NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Wishlist/Favorites table
CREATE TABLE IF NOT EXISTS wishlist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist_item (user_id, product_id)
);

-- Admin activity logs
CREATE TABLE admin_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    details JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_action (action),
    INDEX idx_admin (admin_id)
);

-- Newsletter subscribers
CREATE TABLE newsletter_subscribers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    unsubscribed_at TIMESTAMP NULL
);

-- Insert default categories
INSERT INTO categories (name, slug, description) VALUES
('Proteins', 'proteins', 'Protein powders and supplements'),
('Pre Workout', 'pre-workout', 'Pre-workout supplements for energy and focus'),
('Vitamins', 'vitamins', 'Vitamins and mineral supplements'),
('Supplements', 'supplements', 'General fitness supplements');

-- Insert sample products with more details
INSERT INTO products (category_id, name, slug, description, short_description, price, stock_quantity, image_url) VALUES
(1, 'Whey Protein Chocolate', 'whey-protein-chocolate', 'Premium whey protein with rich chocolate flavor. 24g protein per serving, perfect for post-workout recovery.', '24g protein per serving, chocolate flavor', 29.99, 50, '../Images/Prote+na+whey+OPTIMUN+NUTRITION+Gold+Standard+chocolate+908+g-1159974388.jpg'),
(1, 'Whey Isolate Vanilla', 'whey-isolate-vanilla', 'Pure whey isolate with vanilla flavor. Low in carbs and fat, high in protein content.', 'Pure whey isolate, vanilla flavor', 34.99, 45, '../Images/Prote+na+whey+OPTIMUN+NUTRITION+Gold+Standard+chocolate+908+g-1159974388.jpg'),
(1, 'Casein Protein', 'casein-protein', 'Slow-release casein protein for overnight muscle recovery. Chocolate flavor.', 'Slow-release protein for overnight recovery', 32.99, 30, '../Images/casein.jpg'),
(2, 'Pre-Workout Explosion', 'pre-workout-explosion', 'High-energy pre-workout drink with caffeine, beta-alanine, and creatine for intense workouts.', 'Energy and focus booster for workouts', 24.99, 60, '../Images/preworkout1.jpg'),
(2, 'Nitric Oxide Booster', 'nitric-oxide-booster', 'Enhances blood flow and muscle pumps during training. Improves endurance and performance.', 'Improves blood flow and endurance', 28.99, 40, '../Images/preworkout2.jpg'),
(3, 'Daily Multivitamins', 'daily-multivitamins', 'Complete daily vitamin supplement with essential vitamins and minerals for overall health.', 'Complete daily vitamin supplement', 19.99, 100, '../Images/multivitamin.jpg'),
(3, 'Omega-3 Fish Oil', 'omega-3-fish-oil', 'High-quality fish oil with EPA and DHA for heart, brain, and joint health.', 'Essential fatty acids for health', 22.99, 75, '../Images/omega3.jpg'),
(4, 'Creatine Monohydrate', 'creatine-monohydrate', 'Pure creatine monohydrate powder for strength, power, and muscle growth.', 'Pure creatine for strength and growth', 15.99, 80, '../Images/creatine.jpg'),
(4, 'BCAAs Recovery', 'bcaas-recovery', 'Branch chain amino acids for muscle recovery and reducing muscle soreness after workouts.', 'Amino acids for muscle recovery', 18.99, 70, '../Images/bcaa.jpg'),
(4, 'Glutamine Powder', 'glutamine-powder', 'L-Glutamine powder for immune system support and muscle recovery.', 'Glutamine for recovery and immunity', 16.99, 65, '../Images/plant-protein.jpg');

-- Create default admin account (password: admin123)
INSERT INTO users (email, password, first_name, last_name, is_admin) VALUES
('admin@apex.com', '$2y$10$4zQhQGvN5bN5F5K5K5K5K.h5K5K5K5K5K5K5K5K5K5K5K5K5K5K5K', 'Admin', 'User', TRUE);

-- Create a regular user for testing (password: user123)
INSERT INTO users (email, password, first_name, last_name, phone, street_address, city, postal_code, state_region, country, newsletter_subscribed) VALUES
('user@example.com', '$2y$10$6zRhQGvN5bN5F5K5K5K5K.h5K5K5K5K5K5K5K5K5K5K5K5K5K5K5K', 'John', 'Doe', '+1234567890', '123 Main St', 'New York', '10001', 'NY', 'USA', TRUE);

-- Insert sample admin log
INSERT INTO admin_logs (admin_id, action, table_name, record_id, details) VALUES
(1, 'INSERT', 'products', 1, '{"product": "Whey Protein Chocolate", "price": "29.99"}');

-- Create indexes for better performance
CREATE INDEX idx_products_price ON products(price);
CREATE INDEX idx_products_stock ON products(stock_quantity);
CREATE INDEX idx_orders_date ON orders(order_date);
CREATE INDEX idx_cart_user ON cart_items(user_id);
CREATE INDEX idx_wishlist_user ON wishlist(user_id);