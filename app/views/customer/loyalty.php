<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../helpers/Navigation.php';
require_once __DIR__ . '/../../../config/database.php';

$auth = new AuthController();
if (!$auth->isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

$user = $auth->getCurrentUser();
$lang = $_GET['lang'] ?? 'en';
$db = new Database();
$conn = $db->getConnection();

// Get customer loyalty info (only their own)
$customer_id = $user['user_id'];
$customer = $conn->query("SELECT * FROM customers WHERE customer_id = $customer_id")->fetch_assoc();
$loyalty_points = $customer['loyalty_points'] ?? 0;

// Get recent point transactions (only their own)
$point_history = $conn->query("
    SELECT 'Purchase' as type, total_amount as amount, sale_date as date, 
           FLOOR(total_amount / 1000) as points_earned
    FROM sales 
    WHERE customer_id = $customer_id
    ORDER BY sale_date DESC
    LIMIT 10
");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Loyalty Points - SmartSHOP</title>
    <link rel="stylesheet" href="../../../public/css/main.css">
    <link rel="stylesheet" href="../../../public/css/dashboard.css">
    <style>
        .language-selector {
            position: fixed !important;
            top: 20px !important;
            right: 20px !important;
            left: auto !important;
            z-index: 1000 !important;
        }
        .top-nav .nav-links {
            gap: 0.3rem !important;
        }
        .top-nav .nav-links a {
            padding: 0.4rem 0.6rem !important;
            font-size: 0.85rem !important;
            white-space: nowrap !important;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php Navigation::renderNav($user['role'], $lang); ?>
        
        <header class="header">
            <h1>🎁 Loyalty Points</h1>
            <div class="user-info">
                <span>Welcome, <?php echo $user['full_name']; ?></span>
            </div>
        </header>

        <div class="content">
            <div class="loyalty-summary">
                <div class="points-card">
                    <h2>Your Points Balance</h2>
                    <div class="points-display"><?php echo number_format($loyalty_points); ?></div>
                    <p>Points</p>
                </div>
                
                <div class="rewards-info">
                    <h3>How to Earn Points</h3>
                    <ul>
                        <li>1 point for every 1,000 RWF spent</li>
                        <li>Bonus points on special promotions</li>
                        <li>Birthday bonus points</li>
                    </ul>
                    
                    <h3>Redeem Your Points</h3>
                    <ul>
                        <li>100 points = 1,000 RWF discount</li>
                        <li>500 points = 6,000 RWF discount</li>
                        <li>1000 points = 15,000 RWF discount</li>
                    </ul>
                </div>
            </div>

            <div class="points-history">
                <h2>Recent Activity</h2>
                <table>
                    <thead>
                        <tr>
                            <th><?php echo Language::get('date', $lang); ?></th>
                            <th>Activity</th>
                            <th>Amount Spent</th>
                            <th>Points Earned</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($activity = $point_history->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($activity['date'])); ?></td>
                                <td><?php echo $activity['type']; ?></td>
                                <td><?php echo number_format($activity['amount']); ?> RWF</td>
                                <td>+<?php echo $activity['points_earned']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
        .language-selector {
            position: fixed !important;
            top: 20px !important;
            right: 20px !important;
            left: auto !important;
            z-index: 1000 !important;
        }
    .loyalty-summary {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 2rem;
        margin-bottom: 2rem;
    }
    
    .points-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        border-radius: 15px;
        text-align: center;
    }
    
    .points-display {
        font-size: 3rem;
        font-weight: bold;
        margin: 1rem 0;
    }
    
    .rewards-info {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .points-history {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .points-history table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
    }
    
    .points-history th, .points-history td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    
    .points-history th {
        background: var(--background);
        font-weight: 600;
    }
    </style>
</body>
</html>