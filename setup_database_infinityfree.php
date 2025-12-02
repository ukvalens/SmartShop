<?php
// Simple database setup script for InfinityFree
// Run this once after uploading files to create the database structure

require_once 'config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "<h2>SmartShop Database Setup for InfinityFree</h2>";
    
    // Create users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        phone_number VARCHAR(20),
        password VARCHAR(255) NOT NULL,
        role ENUM('Admin', 'Manager', 'Cashier', 'System Admin') DEFAULT 'Cashier',
        status ENUM('active', 'inactive') DEFAULT 'active',
        reset_token VARCHAR(255) NULL,
        reset_token_expires DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql)) {
        echo "<p>✅ Users table created successfully</p>";
    } else {
        echo "<p>❌ Error creating users table: " . $conn->error . "</p>";
    }
    
    // Create categories table
    $sql = "CREATE TABLE IF NOT EXISTS categories (
        category_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql)) {
        echo "<p>✅ Categories table created successfully</p>";
    } else {
        echo "<p>❌ Error creating categories table: " . $conn->error . "</p>";
    }
    
    // Create products table
    $sql = "CREATE TABLE IF NOT EXISTS products (
        product_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(200) NOT NULL,
        description TEXT,
        category_id INT,
        price DECIMAL(10,2) NOT NULL,
        cost_price DECIMAL(10,2),
        stock_quantity INT DEFAULT 0,
        min_stock_level INT DEFAULT 5,
        barcode VARCHAR(100) UNIQUE,
        image_url VARCHAR(255),
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(category_id)
    )";
    
    if ($conn->query($sql)) {
        echo "<p>✅ Products table created successfully</p>";
    } else {
        echo "<p>❌ Error creating products table: " . $conn->error . "</p>";
    }
    
    // Create sales table
    $sql = "CREATE TABLE IF NOT EXISTS sales (
        sale_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        customer_name VARCHAR(100),
        customer_phone VARCHAR(20),
        total_amount DECIMAL(10,2) NOT NULL,
        payment_method ENUM('cash', 'card', 'mobile') DEFAULT 'cash',
        status ENUM('completed', 'pending', 'cancelled') DEFAULT 'completed',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id)
    )";
    
    if ($conn->query($sql)) {
        echo "<p>✅ Sales table created successfully</p>";
    } else {
        echo "<p>❌ Error creating sales table: " . $conn->error . "</p>";
    }
    
    // Create sale_items table
    $sql = "CREATE TABLE IF NOT EXISTS sale_items (
        item_id INT AUTO_INCREMENT PRIMARY KEY,
        sale_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        unit_price DECIMAL(10,2) NOT NULL,
        total_price DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (sale_id) REFERENCES sales(sale_id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(product_id)
    )";
    
    if ($conn->query($sql)) {
        echo "<p>✅ Sale items table created successfully</p>";
    } else {
        echo "<p>❌ Error creating sale_items table: " . $conn->error . "</p>";
    }
    
    // Insert default admin user
    $admin_email = 'admin@smartshop.com';
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    
    $check_admin = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $check_admin->bind_param("s", $admin_email);
    $check_admin->execute();
    
    if ($check_admin->get_result()->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)");
        $full_name = "System Administrator";
        $role = "System Admin";
        $stmt->bind_param("ssss", $full_name, $admin_email, $admin_password, $role);
        
        if ($stmt->execute()) {
            echo "<p>✅ Default admin user created successfully</p>";
            echo "<p><strong>Login credentials:</strong><br>";
            echo "Email: admin@smartshop.com<br>";
            echo "Password: admin123</p>";
        } else {
            echo "<p>❌ Error creating admin user: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>ℹ️ Admin user already exists</p>";
    }
    
    // Insert sample category
    $check_category = $conn->query("SELECT category_id FROM categories LIMIT 1");
    if ($check_category->num_rows == 0) {
        $conn->query("INSERT INTO categories (name, description) VALUES ('General', 'General products category')");
        echo "<p>✅ Sample category created</p>";
    }
    
    echo "<h3>🎉 Database setup completed!</h3>";
    echo "<p><a href='app/views/auth/login.php'>Go to Login Page</a></p>";
    echo "<p><strong>Important:</strong> Delete this file (setup_database_infinityfree.php) after setup for security!</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Database setup failed: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration in config/database.php</p>";
}
?>