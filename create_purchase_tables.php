<?php
require_once __DIR__ . '/config/database.php';

$db = new Database();
$conn = $db->getConnection();

echo "<h2>Creating Purchase Order Tables</h2>";

// Create suppliers table
$sql1 = "CREATE TABLE IF NOT EXISTS suppliers (
    supplier_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255),
    phone VARCHAR(20),
    email VARCHAR(255),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// Create purchase_orders table
$sql2 = "CREATE TABLE IF NOT EXISTS purchase_orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT,
    order_date DATE,
    status ENUM('Pending', 'Received', 'Cancelled') DEFAULT 'Pending',
    total_amount DECIMAL(10,2),
    notes TEXT,
    received_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id)
)";

// Create purchase_order_items table
$sql3 = "CREATE TABLE IF NOT EXISTS purchase_order_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    product_name VARCHAR(255),
    quantity INT,
    unit_price DECIMAL(10,2),
    total_price DECIMAL(10,2),
    FOREIGN KEY (order_id) REFERENCES purchase_orders(order_id) ON DELETE CASCADE
)";

if ($conn->query($sql1)) {
    echo "<p>✓ Suppliers table created</p>";
} else {
    echo "<p>✗ Error creating suppliers table: " . $conn->error . "</p>";
}

if ($conn->query($sql2)) {
    echo "<p>✓ Purchase orders table created</p>";
} else {
    echo "<p>✗ Error creating purchase orders table: " . $conn->error . "</p>";
}

if ($conn->query($sql3)) {
    echo "<p>✓ Purchase order items table created</p>";
} else {
    echo "<p>✗ Error creating purchase order items table: " . $conn->error . "</p>";
}

// Insert sample supplier
$conn->query("INSERT IGNORE INTO suppliers (supplier_name, contact_person, phone) VALUES ('Sample Supplier', 'John Doe', '+250788123456')");

echo "<p><strong>Tables created successfully!</strong></p>";
echo "<p><a href='app/views/inventory/index.php?lang=en'>Go to Inventory</a></p>";
?>