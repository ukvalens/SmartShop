<?php
require_once __DIR__ . '/../config/database.php';

$db = new Database();
$conn = $db->getConnection();

echo "<h2>Creating All SmartSHOP Tables</h2><pre>";

// Disable foreign key checks and drop tables
$conn->query("SET FOREIGN_KEY_CHECKS = 0");
$dropTables = [
    'notifications', 'loyalty_points', 'audit_trail', 'reports', 'stock_adjustments',
    'payments', 'sale_details', 'sales', 'purchase_order_details', 
    'purchase_orders', 'products', 'categories', 'suppliers', 'customers', 
    'system_settings', 'users'
];

foreach ($dropTables as $table) {
    $conn->query("DROP TABLE IF EXISTS $table");
}
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

// Create tables
$tables = [
    "CREATE TABLE users (
        user_id INT PRIMARY KEY AUTO_INCREMENT,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        phone_number VARCHAR(20),
        password VARCHAR(255) NOT NULL,
        role ENUM('Admin', 'Owner', 'Cashier', 'Customer') NOT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        language_preference VARCHAR(5) DEFAULT 'en',
        reset_token VARCHAR(255) NULL,
        reset_token_expires TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE categories (
        category_id INT PRIMARY KEY AUTO_INCREMENT,
        category_name VARCHAR(100) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE products (
        product_id INT PRIMARY KEY AUTO_INCREMENT,
        product_name VARCHAR(150) NOT NULL,
        category_id INT,
        cost_price DECIMAL(10,2) NOT NULL,
        selling_price DECIMAL(10,2) NOT NULL,
        stock_quantity INT DEFAULT 0,
        reorder_level INT DEFAULT 10,
        expiry_date DATE NULL,
        barcode VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL
    )",
    
    "CREATE TABLE suppliers (
        supplier_id INT PRIMARY KEY AUTO_INCREMENT,
        supplier_name VARCHAR(150) NOT NULL,
        contact_name VARCHAR(100),
        phone_number VARCHAR(20),
        email VARCHAR(100),
        address TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE customers (
        customer_id INT PRIMARY KEY AUTO_INCREMENT,
        full_name VARCHAR(100) NOT NULL,
        phone_number VARCHAR(20),
        email VARCHAR(100),
        address TEXT,
        loyalty_points INT DEFAULT 0,
        credit_balance DECIMAL(10,2) DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE sales (
        sale_id INT PRIMARY KEY AUTO_INCREMENT,
        customer_id INT NULL,
        user_id INT NOT NULL,
        sale_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        total_amount DECIMAL(12,2) NOT NULL,
        payment_method ENUM('Cash', 'Mobile Money', 'Credit') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE SET NULL,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )",
    
    "CREATE TABLE purchase_orders (
        purchase_order_id INT PRIMARY KEY AUTO_INCREMENT,
        supplier_id INT NOT NULL,
        order_date DATE NOT NULL,
        total_amount DECIMAL(12,2) NOT NULL,
        status ENUM('Pending', 'Delivered', 'Cancelled') DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id) ON DELETE CASCADE
    )",
    
    "CREATE TABLE purchase_order_details (
        purchase_order_detail_id INT PRIMARY KEY AUTO_INCREMENT,
        purchase_order_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity_ordered INT NOT NULL,
        cost_price DECIMAL(10,2) NOT NULL,
        received_quantity INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(purchase_order_id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
    )",
    
    "CREATE TABLE sale_details (
        sale_detail_id INT PRIMARY KEY AUTO_INCREMENT,
        sale_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity_sold INT NOT NULL,
        selling_price DECIMAL(10,2) NOT NULL,
        discount DECIMAL(5,2) DEFAULT 0.00,
        subtotal DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (sale_id) REFERENCES sales(sale_id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
    )",
    
    "CREATE TABLE payments (
        payment_id INT PRIMARY KEY AUTO_INCREMENT,
        sale_id INT NULL,
        customer_id INT NOT NULL,
        payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        amount_paid DECIMAL(10,2) NOT NULL,
        payment_method ENUM('Cash', 'Mobile Money') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (sale_id) REFERENCES sales(sale_id) ON DELETE SET NULL,
        FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE
    )",
    
    "CREATE TABLE stock_adjustments (
        adjustment_id INT PRIMARY KEY AUTO_INCREMENT,
        product_id INT NOT NULL,
        user_id INT NOT NULL,
        quantity_changed INT NOT NULL,
        adjustment_type ENUM('Damaged', 'Expired', 'Manual Correction') NOT NULL,
        reason TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )",
    
    "CREATE TABLE reports (
        report_id INT PRIMARY KEY AUTO_INCREMENT,
        report_type ENUM('Daily', 'Weekly', 'Monthly') NOT NULL,
        total_sales DECIMAL(12,2) NOT NULL,
        total_profit DECIMAL(12,2) NOT NULL,
        top_selling_product_id INT NULL,
        frequent_customer_id INT NULL,
        generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (top_selling_product_id) REFERENCES products(product_id) ON DELETE SET NULL,
        FOREIGN KEY (frequent_customer_id) REFERENCES customers(customer_id) ON DELETE SET NULL
    )",
    
    "CREATE TABLE audit_trail (
        log_id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        action_type VARCHAR(50) NOT NULL,
        description TEXT,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )",
    
    "CREATE TABLE loyalty_points (
        loyalty_id INT PRIMARY KEY AUTO_INCREMENT,
        customer_id INT NOT NULL,
        points_earned INT DEFAULT 0,
        points_redeemed INT DEFAULT 0,
        transaction_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE,
        FOREIGN KEY (transaction_id) REFERENCES sales(sale_id) ON DELETE SET NULL
    )",
    
    "CREATE TABLE notifications (
        notification_id INT PRIMARY KEY AUTO_INCREMENT,
        type ENUM('Low Stock', 'Credit Reminder', 'Expiry Alert', 'Promotion') NOT NULL,
        recipient_id INT NOT NULL,
        recipient_type ENUM('User', 'Customer') NOT NULL,
        message_content TEXT NOT NULL,
        status ENUM('Sent', 'Pending', 'Failed') DEFAULT 'Pending',
        sent_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE system_settings (
        setting_id INT PRIMARY KEY AUTO_INCREMENT,
        setting_key VARCHAR(50) NOT NULL UNIQUE,
        setting_value TEXT NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )"
];

foreach ($tables as $sql) {
    if ($conn->query($sql)) {
        $tableName = preg_match('/CREATE TABLE (\w+)/', $sql, $matches) ? $matches[1] : 'table';
        echo "✓ Created table: $tableName\n";
    } else {
        echo "✗ Error: " . $conn->error . "\n";
    }
}

// Insert default data
$defaultData = [
    "INSERT INTO system_settings (setting_key, setting_value) VALUES
    ('language', 'English'),
    ('theme', 'Light'),
    ('VAT_rate', '18'),
    ('currency', 'RWF'),
    ('low_stock_threshold', '10'),
    ('sms_enabled', '1'),
    ('backup_frequency', 'daily')",
    
    "INSERT INTO categories (category_name, description) VALUES
    ('Food & Beverages', 'Food items and drinks'),
    ('Personal Care', 'Soap, shampoo, toothpaste'),
    ('Household Items', 'Cleaning supplies, kitchen items'),
    ('Electronics', 'Phones, accessories, batteries'),
    ('Stationery', 'Pens, books, paper')",
    
    "INSERT INTO users (full_name, email, phone_number, password, role) VALUES
    ('System Admin', 'admin@smartshop.com', '+250788000000', '" . password_hash('admin123', PASSWORD_DEFAULT) . "', 'Admin'),
    ('Shop Owner', 'owner@smartshop.com', '+250788111111', '" . password_hash('owner123', PASSWORD_DEFAULT) . "', 'Owner'),
    ('Cashier One', 'cashier@smartshop.com', '+250788222222', '" . password_hash('cashier123', PASSWORD_DEFAULT) . "', 'Cashier'),
    ('Customer User', 'customer@smartshop.com', '+250788999999', '" . password_hash('customer123', PASSWORD_DEFAULT) . "', 'Customer')",
    
    "INSERT INTO suppliers (supplier_name, contact_name, phone_number, email, address) VALUES
    ('Inyama Ltd', 'Jean Baptiste', '+250788333333', 'jean@inyama.rw', 'Kigali, Rwanda'),
    ('Ubwiyunge Co', 'Marie Claire', '+250788444444', 'marie@ubwiyunge.rw', 'Musanze, Rwanda'),
    ('Amazi Fresh', 'Patrick Nkusi', '+250788555555', 'patrick@amazi.rw', 'Huye, Rwanda')",
    
    "INSERT INTO products (product_name, category_id, cost_price, selling_price, stock_quantity, reorder_level, barcode) VALUES
    ('Sugar 1kg', 1, 800, 1000, 50, 10, 'SG001'),
    ('Rice 1kg', 1, 1200, 1500, 30, 5, 'RC001'),
    ('Soap Bar', 2, 300, 400, 100, 20, 'SP001'),
    ('Cooking Oil 1L', 1, 2000, 2500, 25, 5, 'OL001'),
    ('Toothpaste', 2, 800, 1200, 40, 10, 'TP001')",
    
    "INSERT INTO customers (full_name, phone_number, email, address, loyalty_points, credit_balance) VALUES
    ('Alice Mukamana', '+250788666666', 'alice@email.com', 'Kigali', 150, 0),
    ('Bob Niyonzima', '+250788777777', 'bob@email.com', 'Musanze', 75, 5000),
    ('Claire Uwimana', '+250788888888', 'claire@email.com', 'Huye', 200, 0)"
];

foreach ($defaultData as $sql) {
    if ($conn->query($sql)) {
        echo "✓ Inserted default data\n";
    } else {
        echo "✗ Error inserting data: " . $conn->error . "\n";
    }
}

echo "\n✓ All tables created successfully!\n";
echo "Default admin: admin@smartshop.com / admin123\n";
echo "</pre>";
echo "<p><a href='../app/views/auth/login.php'>Go to Login</a></p>";
?>