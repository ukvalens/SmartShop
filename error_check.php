<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "PHP Version: " . phpversion() . "<br>";

// Test basic includes
try {
    if (file_exists('config/database.php')) {
        require_once 'config/database.php';
        echo "✅ Database config loaded<br>";
    } else {
        echo "❌ Database config missing<br>";
    }
} catch (Exception $e) {
    echo "❌ Database config error: " . $e->getMessage() . "<br>";
}

try {
    if (file_exists('app/helpers/Language.php')) {
        require_once 'app/helpers/Language.php';
        echo "✅ Language helper loaded<br>";
    } else {
        echo "❌ Language helper missing<br>";
    }
} catch (Exception $e) {
    echo "❌ Language helper error: " . $e->getMessage() . "<br>";
}

echo "<br><a href='minimal_index.php'>Try Minimal Index</a>";
?>