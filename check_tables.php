<?php
require_once __DIR__ . '/config/database.php';

$db = new Database();
$conn = $db->getConnection();

echo "<h2>Checking Existing Tables in SmartShop Database</h2>";

// Show all tables
$result = $conn->query("SHOW TABLES");

if ($result->num_rows > 0) {
    echo "<h3>Existing Tables:</h3>";
    echo "<ul>";
    while ($row = $result->fetch_array()) {
        $table_name = $row[0];
        echo "<li><strong>$table_name</strong>";
        
        // Show table structure
        $desc = $conn->query("DESCRIBE $table_name");
        if ($desc->num_rows > 0) {
            echo "<ul>";
            while ($col = $desc->fetch_assoc()) {
                echo "<li>{$col['Field']} - {$col['Type']}</li>";
            }
            echo "</ul>";
        }
        echo "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No tables found in the database.</p>";
}

echo "<hr>";
echo "<p><a href='app/views/inventory/index.php?lang=en'>Back to Inventory</a></p>";
?>