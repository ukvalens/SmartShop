<?php
// Simple debug script to identify issues on InfinityFree
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>SmartShop Debug Information</h2>";

// Check PHP version
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";

// Check if files exist
$files_to_check = [
    'config/database.php',
    'config/config.php',
    'app/controllers/AuthController.php',
    'app/helpers/Language.php',
    'index.php'
];

echo "<h3>File Check:</h3>";
foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "<p>✅ $file exists</p>";
    } else {
        echo "<p>❌ $file missing</p>";
    }
}

// Test database connection
echo "<h3>Database Connection Test:</h3>";
try {
    if (file_exists('config/database.php')) {
        require_once 'config/database.php';
        $db = new Database();
        $conn = $db->getConnection();
        echo "<p>✅ Database connection successful</p>";
    } else {
        echo "<p>❌ Database config file missing</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

// Check directory permissions
echo "<h3>Directory Permissions:</h3>";
$dirs_to_check = ['uploads', 'backups', 'config'];
foreach ($dirs_to_check as $dir) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo "<p>✅ $dir is writable</p>";
        } else {
            echo "<p>⚠️ $dir is not writable</p>";
        }
    } else {
        echo "<p>❌ $dir directory missing</p>";
    }
}

echo "<p><a href='index.php'>Go to Main Page</a></p>";
?>