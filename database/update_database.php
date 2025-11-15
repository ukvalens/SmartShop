<?php
require_once __DIR__ . '/../config/database.php';

$db = new Database();
$conn = $db->getConnection();

echo "<h2>SmartSHOP Database Update</h2>";
echo "<pre>";

// Read and execute update SQL
$sqlFile = __DIR__ . '/update_tables.sql';
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
    
    echo "\nDatabase update completed!\n";
} else {
    echo "✗ Update SQL file not found!\n";
}

echo "</pre>";
echo "<p><a href='../app/views/auth/login.php'>Go to Login Page</a></p>";
?>