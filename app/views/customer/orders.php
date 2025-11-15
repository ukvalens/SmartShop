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

// Get customer's orders (only their own)
$customer_id = $user['user_id'];
$orders = $conn->query("
    SELECT s.*, COUNT(sd.sale_detail_id) as item_count
    FROM sales s
    LEFT JOIN sale_details sd ON s.sale_id = sd.sale_id
    WHERE s.customer_id = $customer_id
    GROUP BY s.sale_id
    ORDER BY s.sale_date DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Order History - SmartSHOP</title>
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
            <h1>📋 Order History</h1>
            <div class="user-info">
                <span>Welcome, <?php echo $user['full_name']; ?></span>
            </div>
        </header>

        <div class="content">
            <div class="orders-table">
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th><?php echo Language::get('date', $lang); ?></th>
                            <th>Items</th>
                            <th>Total Amount</th>
                            <th><?php echo Language::get('payment_method', $lang); ?></th>
                            <th><?php echo Language::get('status', $lang); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = $orders->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $order['sale_id']; ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($order['sale_date'])); ?></td>
                                <td><?php echo $order['item_count']; ?> items</td>
                                <td><?php echo number_format($order['total_amount']); ?> RWF</td>
                                <td><?php echo $order['payment_method']; ?></td>
                                <td><?php echo ucfirst($order['status'] ?? 'completed'); ?></td>
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
    .orders-table {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .orders-table table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .orders-table th, .orders-table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    
    .orders-table th {
        background: var(--background);
        font-weight: 600;
    }
    </style>
</body>
</html>