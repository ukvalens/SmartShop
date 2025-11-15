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

// Get customer credit info (only their own)
$customer_id = $user['user_id'];
$customer = $conn->query("SELECT * FROM customers WHERE customer_id = $customer_id")->fetch_assoc();
$credit_balance = $customer['credit_balance'] ?? 0;

// Get credit history (only their own)
$credit_history = $conn->query("
    SELECT credit_id, amount, created_at, description, 'Credit' as type, status
    FROM customer_credits
    WHERE customer_id = $customer_id
    UNION ALL
    SELECT payment_id as credit_id, amount_paid as amount, created_at, 
           payment_method as description, 'Payment' as type, 'paid' as status
    FROM payments
    WHERE customer_id = $customer_id
    ORDER BY created_at DESC
    LIMIT 20
");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Credit Balance - SmartSHOP</title>
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
            <h1>💳 Credit Balance</h1>
            <div class="user-info">
                <span>Welcome, <?php echo $user['full_name']; ?></span>
            </div>
        </header>

        <div class="content">
            <div class="credit-summary">
                <div class="balance-card <?php echo $credit_balance > 0 ? 'has-balance' : 'no-balance'; ?>">
                    <h2>Current Balance</h2>
                    <div class="balance-display"><?php echo number_format($credit_balance); ?></div>
                    <p>RWF</p>
                    <?php if ($credit_balance > 0): ?>
                        <small>Please make payment as soon as possible</small>
                    <?php else: ?>
                        <small>Your account is up to date</small>
                    <?php endif; ?>
                </div>
                
                <div class="payment-info">
                    <h3><?php echo Language::get('payment_methods', $lang); ?></h3>
                    <ul>
                        <li>💰 Cash payment at store</li>
                        <li>📱 Mobile Money transfer</li>
                        <li>🏦 Bank transfer</li>
                    </ul>
                    
                    <h3>Contact Information</h3>
                    <p>For payment arrangements, please contact:</p>
                    <ul>
                        <li>📞 Phone: +250 XXX XXX XXX</li>
                        <li>📧 Email: payments@smartshop.rw</li>
                        <li>🏪 Visit our store</li>
                    </ul>
                </div>
            </div>

            <div class="credit-history">
                <h2>Credit History</h2>
                <table>
                    <thead>
                        <tr>
                            <th><?php echo Language::get('date', $lang); ?></th>
                            <th>Type</th>
                            <th><?php echo Language::get('description', $lang); ?></th>
                            <th><?php echo Language::get('amount', $lang); ?></th>
                            <th><?php echo Language::get('status', $lang); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($record = $credit_history->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($record['created_at'])); ?></td>
                                <td><?php echo $record['type']; ?></td>
                                <td><?php echo $record['description'] ?? 'N/A'; ?></td>
                                <td class="<?php echo $record['type'] === 'Payment' ? 'payment' : 'credit'; ?>">
                                    <?php echo $record['type'] === 'Payment' ? '-' : '+'; ?><?php echo number_format($record['amount']); ?> RWF
                                </td>
                                <td><?php echo ucfirst($record['status']); ?></td>
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
    .credit-summary {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 2rem;
        margin-bottom: 2rem;
    }
    
    .balance-card {
        padding: 2rem;
        border-radius: 15px;
        text-align: center;
        color: white;
    }
    
    .balance-card.has-balance {
        background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
    }
    
    .balance-card.no-balance {
        background: linear-gradient(135deg, #00b894 0%, #00a085 100%);
    }
    
    .balance-display {
        font-size: 3rem;
        font-weight: bold;
        margin: 1rem 0;
    }
    
    .payment-info {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .credit-history {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .credit-history table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
    }
    
    .credit-history th, .credit-history td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    
    .credit-history th {
        background: var(--background);
        font-weight: 600;
    }
    
    .payment {
        color: green;
        font-weight: bold;
    }
    
    .credit {
        color: red;
        font-weight: bold;
    }
    </style>
</body>
</html>