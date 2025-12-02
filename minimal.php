<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>SmartShop Minimal Test</h1>";

// Test 1: Basic PHP
echo "<p>✓ PHP is working</p>";

// Test 2: Database connection
try {
    require_once __DIR__ . '/config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    echo "<p>✓ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database Error: " . $e->getMessage() . "</p>";
}

// Test 3: Check if database exists
try {
    $result = $conn->query("SHOW TABLES");
    if ($result->num_rows > 0) {
        echo "<p>✓ Database has " . $result->num_rows . " tables</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Database exists but no tables found</p>";
        echo "<p><a href='database/setup_database.php'>Setup Database Tables</a></p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database query error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='app/views/auth/login.php'>Go to Login</a></p>";
echo "<p><a href='app/views/auth/register.php'>Go to Register</a></p>";
?>