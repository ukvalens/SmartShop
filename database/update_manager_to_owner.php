<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "Starting database update: Manager -> Owner\n";
    
    // Update users table - change Manager role to Owner
    $result = $conn->query("UPDATE users SET role = 'Owner' WHERE role = 'Manager'");
    if ($result) {
        $affected = $conn->affected_rows;
        echo "Updated $affected user(s) from Manager to Owner\n";
    }
    
    // Update any other tables that might reference Manager role
    // Check if there are any role-based permissions or logs
    
    echo "Database update completed successfully!\n";
    echo "All Manager roles have been changed to Owner.\n";
    
} catch (Exception $e) {
    echo "Error updating database: " . $e->getMessage() . "\n";
}
?>