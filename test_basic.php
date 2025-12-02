<?php
// Most basic test - just check if PHP is working
echo "PHP is working!<br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server: " . $_SERVER['HTTP_HOST'] . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";

// Test if we can include files
if (file_exists('config/database.php')) {
    echo "Database config file exists<br>";
} else {
    echo "Database config file missing<br>";
}

echo "<br><a href='simple_index.php'>Go to Simple Index</a>";
echo "<br><a href='debug.php'>Go to Debug Page</a>";
?>