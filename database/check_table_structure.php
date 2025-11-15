<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "Checking users table structure:\n";
    echo "===============================\n";
    
    $result = $conn->query("DESCRIBE users");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "Field: {$row['Field']} | Type: {$row['Type']} | Null: {$row['Null']} | Default: {$row['Default']}\n";
        }
    }
    
    echo "\nChecking role column values:\n";
    echo "===========================\n";
    
    $result2 = $conn->query("SELECT user_id, role, LENGTH(role) as role_length, ASCII(role) as role_ascii FROM users");
    if ($result2) {
        while ($row = $result2->fetch_assoc()) {
            echo "ID: {$row['user_id']} | Role: '{$row['role']}' | Length: {$row['role_length']} | ASCII: {$row['role_ascii']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>