<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "Checking all user roles in database:\n";
    echo "=====================================\n";
    
    $result = $conn->query("SELECT user_id, full_name, email, role FROM users ORDER BY user_id");
    
    if ($result && $result->num_rows > 0) {
        while ($user = $result->fetch_assoc()) {
            echo "ID: {$user['user_id']} | Name: {$user['full_name']} | Email: {$user['email']} | Role: {$user['role']}\n";
        }
    } else {
        echo "No users found in database.\n";
    }
    
    echo "\nRole distribution:\n";
    $roles = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    if ($roles && $roles->num_rows > 0) {
        while ($role = $roles->fetch_assoc()) {
            echo "- {$role['role']}: {$role['count']} user(s)\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>