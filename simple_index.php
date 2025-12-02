<?php
// Simplified index.php for InfinityFree - minimal version to avoid errors
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartShop - POS System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; }
        .btn { display: inline-block; padding: 12px 24px; background: #007cba; color: white; text-decoration: none; border-radius: 5px; margin: 10px; }
        .btn:hover { background: #005a87; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛒 SmartShop POS System</h1>
            <p>Modern Point of Sale Solution</p>
        </div>

        <?php
        // Check if database is configured
        $db_configured = false;
        if (file_exists('config/database.php')) {
            try {
                require_once 'config/database.php';
                $db = new Database();
                $conn = $db->getConnection();
                $db_configured = true;
                echo '<div class="status success">✅ Database connection successful</div>';
            } catch (Exception $e) {
                echo '<div class="status error">❌ Database connection failed: ' . $e->getMessage() . '</div>';
                echo '<div class="status error">Please update your database credentials in config/database.php</div>';
            }
        } else {
            echo '<div class="status error">❌ Database configuration file missing</div>';
        }
        ?>

        <div style="text-align: center; margin-top: 30px;">
            <?php if ($db_configured): ?>
                <a href="setup_database_infinityfree.php" class="btn">🔧 Setup Database</a>
                <a href="app/views/auth/login.php" class="btn">🔑 Login</a>
            <?php else: ?>
                <p>Please configure your database first:</p>
                <ol style="text-align: left; max-width: 500px; margin: 0 auto;">
                    <li>Update database credentials in <code>config/database.php</code></li>
                    <li>Run the database setup</li>
                    <li>Start using SmartShop</li>
                </ol>
            <?php endif; ?>
            
            <br><br>
            <a href="debug.php" class="btn" style="background: #6c757d;">🔍 Debug Info</a>
        </div>

        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; text-align: center; color: #666;">
            <p><strong>Features:</strong> Sales Processing • Inventory Management • Customer Management • Reports & Analytics</p>
        </div>
    </div>
</body>
</html>