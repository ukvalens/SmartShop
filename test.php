<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>SmartShop Test Page</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Current Directory: " . __DIR__ . "</p>";

// Test database connection
try {
    require_once __DIR__ . '/config/database.php';
    echo "<p>✓ Database class loaded successfully</p>";
    
    $db = new Database();
    echo "<p>✓ Database object created</p>";
    
    $conn = $db->getConnection();
    echo "<p>✓ Database connection successful</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='index.php'>Back to Main Page</a></p>";
?>