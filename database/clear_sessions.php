<?php
// Clear all sessions to force re-login with updated role
session_start();
session_destroy();

echo "Session cleared successfully!\n";
echo "Please log in again to see the updated role.\n";
echo "Go to: http://localhost/SMESHOP/app/views/auth/login.php\n";
?>