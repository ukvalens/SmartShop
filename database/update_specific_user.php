<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "Updating specific user role:\n";
    echo "===========================\n";
    
    // Update the specific user (ID 10) to Owner role
    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE user_id = ?");
    $role = 'Owner';
    $user_id = 10;
    $stmt->bind_param("si", $role, $user_id);
    
    if ($stmt->execute()) {
        echo "Successfully updated user ID 10 to Owner role\n";
    } else {
        echo "Failed to update user role\n";
    }
    
    // Verify the update
    $result = $conn->query("SELECT user_id, full_name, email, role FROM users WHERE user_id = 10");
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "Verified - ID: {$user['user_id']} | Name: {$user['full_name']} | Role: '{$user['role']}'\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>