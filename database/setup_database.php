<?php
require_once __DIR__ . '/../config/database.php';

$db = new Database();
$conn = $db->getConnection();

echo "<h2>SmartSHOP Database Setup</h2>";
echo "<pre>";

// Read and execute SQL file
$sqlFile = __DIR__ . '/create_tables.sql';
if (file_exists($sqlFile)) {
    $sql = file_get_contents($sqlFile);
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && strpos($statement, '--') !== 0) {
            if ($conn->query($statement)) {
                echo "✓ " . substr($statement, 0, 50) . "...\n";
            } else {
                echo "✗ Error: " . $conn->error . "\n";
            }
        }
    }
    
    // Create default admin user
    $adminEmail = 'admin@smartshop.com';
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $adminQuery = "INSERT INTO users (full_name, email, phone_number, password, role) VALUES ('System Admin', '$adminEmail', '+250788000000', '$adminPassword', 'Admin') ON DUPLICATE KEY UPDATE email=email";
    
    if ($conn->query($adminQuery)) {
        echo "✓ Default admin user created (admin@smartshop.com / admin123)\n";
    } else {
        echo "✗ Error creating admin user: " . $conn->error . "\n";
    }
    
    echo "\nDatabase setup completed successfully!\n";
} else {
    echo "✗ SQL file not found!\n";
}

echo "</pre>";
echo "<p><a href='../app/views/auth/login.php'>Go to Login Page</a></p>";
?>