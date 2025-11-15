<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "Fixing empty roles in database:\n";
    echo "===============================\n";
    
    // Update empty roles to Owner (assuming they should be business owners)
    $result = $conn->query("UPDATE users SET role = 'Owner' WHERE role = '' OR role IS NULL");
    
    if ($result) {
        $affected = $conn->affected_rows;
        echo "Updated $affected user(s) with empty roles to Owner\n";
    }
    
    // Also update any 'Business Owner' roles to 'Owner' for consistency
    $result2 = $conn->query("UPDATE users SET role = 'Owner' WHERE role = 'Business Owner'");
    if ($result2) {
        $affected2 = $conn->affected_rows;
        echo "Updated $affected2 user(s) from Business Owner to Owner\n";
    }
    
    echo "\nUpdated user roles:\n";
    $users = $conn->query("SELECT user_id, full_name, email, role FROM users ORDER BY user_id");
    while ($user = $users->fetch_assoc()) {
        echo "ID: {$user['user_id']} | Name: {$user['full_name']} | Role: {$user['role']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>