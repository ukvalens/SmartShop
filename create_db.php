<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Creating SmartShop Database</h1>";

// Create database connection without selecting a database
$host = 'localhost';
$username = 'root';
$password = '';

try {
    $conn = new mysqli($host, $username, $password);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "<p>✓ Connected to MySQL server</p>";
    
    // Create database
    $sql = "CREATE DATABASE IF NOT EXISTS smartshop";
    if ($conn->query($sql) === TRUE) {
        echo "<p>✓ Database 'smartshop' created successfully</p>";
    } else {
        echo "<p style='color: red;'>✗ Error creating database: " . $conn->error . "</p>";
    }
    
    $conn->close();
    
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ol>";
    echo "<li><a href='database/setup_database.php'>Setup Database Tables</a></li>";
    echo "<li><a href='test.php'>Test Connection</a></li>";
    echo "<li><a href='index.php'>Go to Main Page</a></li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}
?>