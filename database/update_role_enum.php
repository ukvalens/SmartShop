<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "Updating role ENUM column:\n";
    echo "=========================\n";
    
    // First, update the ENUM to include Owner and remove Manager
    $sql = "ALTER TABLE users MODIFY COLUMN role ENUM('Admin','Owner','Cashier','Customer') NOT NULL";
    
    if ($conn->query($sql)) {
        echo "Successfully updated role ENUM column\n";
    } else {
        echo "Error updating ENUM: " . $conn->error . "\n";
    }
    
    // Now update the user with empty role to Owner
    $stmt = $conn->prepare("UPDATE users SET role = 'Owner' WHERE user_id = 10");
    if ($stmt->execute()) {
        echo "Successfully set user ID 10 role to Owner\n";
    } else {
        echo "Error updating user role: " . $conn->error . "\n";
    }
    
    // Verify the changes
    echo "\nVerifying changes:\n";
    echo "==================\n";
    
    $result = $conn->query("DESCRIBE users");
    while ($row = $result->fetch_assoc()) {
        if ($row['Field'] == 'role') {
            echo "Role column type: {$row['Type']}\n";
        }
    }
    
    $result2 = $conn->query("SELECT user_id, full_name, role FROM users ORDER BY user_id");
    while ($user = $result2->fetch_assoc()) {
        echo "ID: {$user['user_id']} | Name: {$user['full_name']} | Role: {$user['role']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>