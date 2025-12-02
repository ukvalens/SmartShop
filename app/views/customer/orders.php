<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../helpers/Language.php';
require_once __DIR__ . '/../../helpers/Navigation.php';
require_once __DIR__ . '/../../../config/database.php';

$auth = new AuthController();
if (!$auth->isLoggedIn() || $auth->getCurrentUser()['role'] !== 'Customer') {
    header('Location: ../auth/login.php');
    exit;
}

$user = $auth->getCurrentUser();
$lang = $_GET['lang'] ?? 'en';
$db = new Database();
$conn = $db->getConnection();

// Get customer's own transactions only
$customer_id = $user['user_id'];

// Get customer's purchase history
$query = "
    SELECT s.*, sd.product_id, sd.quantity_sold, sd.selling_price, sd.subtotal, p.product_name
    FROM sales s
    JOIN sale_details sd ON s.sale_id = sd.sale_id
    JOIN products p ON sd.product_id = p.product_id
    WHERE s.customer_id = ?
    ORDER BY s.sale_date DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$transactions = $stmt->get_result();

// Group transactions by sale_id
$grouped_transactions = [];
while ($row = $transactions->fetch_assoc()) {
    $sale_id = $row['sale_id'];
    if (!isset($grouped_transactions[$sale_id])) {
        $grouped_transactions[$sale_id] = [
            'sale_info' => [
                'sale_id' => $row['sale_id'],
                'sale_date' => $row['sale_date'],
                'total_amount' => $row['total_amount'],
                'payment_method' => $row['payment_method']
            ],
            'items' => []
        ];
    }
    $grouped_transactions[$sale_id]['items'][] = [
        'product_name' => $row['product_name'],
        'quantity_sold' => $row['quantity_sold'],
        'selling_price' => $row['selling_price'],
        'subtotal' => $row['subtotal']
    ];
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Purchase History - SmartSHOP</title>
    <link rel="stylesheet" href="../../../public/css/main.css">
    <link rel="stylesheet" href="../../../public/css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="language-selector">
        <select onchange="changeLanguage(this.value)">
            <option value="en" <?php echo $lang === 'en' ? 'selected' : ''; ?>>🇺🇸 English</option>
            <option value="rw" <?php echo $lang === 'rw' ? 'selected' : ''; ?>>🇷🇼 Kinyarwanda</option>
        </select>
    </div>

    <div class="dashboard-container">
        <?php Navigation::renderNav($user['role'], $lang); ?>
        
        <header class="header">
            <h1>📦 My Purchase History</h1>
            <div class="user-info">
                <div class="user-profile">
                    <img src="../../../uploads/profiles/<?php echo $user['user_id']; ?>.jpg?v=<?php echo time(); ?>" alt="Profile" class="profile-img" onerror="this.src='../../../uploads/profiles/default.jpg'">
                    <div class="user-details">
                        <span class="user-name"><?php echo $user['full_name']; ?></span>
                        <span class="user-role"><?php echo $user['role']; ?></span>
                    </div>
                    <div class="user-menu">
                        <a href="../profile/index.php?lang=<?php echo $lang; ?>" class="profile-link"><?php echo Language::getText('profile', $lang); ?></a>
                        <a href="../../controllers/logout.php" class="btn-logout"><?php echo Language::getText('logout', $lang); ?></a>
                    </div>
                </div>
            </div>
        </header>

        <div class="content">
            <div class="transactions-section">
                <?php if (empty($grouped_transactions)): ?>
                    <div class="no-transactions">
                        <i class="fas fa-shopping-cart"></i>
                        <h3>No Purchase History</h3>
                        <p>You haven't made any purchases yet. Visit our store to start shopping!</p>
                        <a href="../customer/products.php?lang=<?php echo $lang; ?>" class="btn btn-primary">Browse Products</a>
                    </div>
                <?php else: ?>
                    <div class="transactions-list">
                        <?php foreach ($grouped_transactions as $transaction): ?>
                            <div class="transaction-card">
                                <div class="transaction-header">
                                    <div class="transaction-info">
                                        <h3>Receipt #<?php echo $transaction['sale_info']['sale_id']; ?></h3>
                                        <p class="transaction-date"><?php echo date('M d, Y - H:i', strtotime($transaction['sale_info']['sale_date'])); ?></p>
                                    </div>
                                    <div class="transaction-total">
                                        <span class="amount"><?php echo number_format($transaction['sale_info']['total_amount']); ?> RWF</span>
                                        <span class="payment-method"><?php echo $transaction['sale_info']['payment_method']; ?></span>
                                    </div>
                                </div>
                                
                                <div class="transaction-items">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Qty</th>
                                                <th>Price</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($transaction['items'] as $item): ?>
                                                <tr>
                                                    <td><?php echo $item['product_name']; ?></td>
                                                    <td><?php echo $item['quantity_sold']; ?></td>
                                                    <td><?php echo number_format($item['selling_price']); ?> RWF</td>
                                                    <td><?php echo number_format($item['subtotal']); ?> RWF</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php include __DIR__ . '/../../includes/footer.php'; ?>
    </div>

    <script>
        function changeLanguage(lang) {
            window.location.href = '?lang=' + lang;
        }
    </script>

    <style>
        .language-selector {
            position: fixed !important;
            top: 20px !important;
            right: 20px !important;
            z-index: 1000 !important;
        }
        
        .language-selector select {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: white;
            font-size: 0.9rem;
        }
        
        .transactions-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .no-transactions {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .no-transactions i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 1rem;
        }
        
        .transaction-card {
            border: 1px solid #eee;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        
        .transaction-header {
            background: #f8f9fa;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
        }
        
        .transaction-info h3 {
            margin: 0;
            color: #333;
        }
        
        .transaction-date {
            margin: 0.25rem 0 0 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        .transaction-total {
            text-align: right;
        }
        
        .amount {
            display: block;
            font-size: 1.25rem;
            font-weight: bold;
            color: #28a745;
        }
        
        .payment-method {
            font-size: 0.9rem;
            color: #666;
            background: #e9ecef;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
        }
        
        .transaction-items table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .transaction-items th,
        .transaction-items td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .transaction-items th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .transaction-items tbody tr:hover {
            background: #f8f9fa;
        }
    </style>
</body>
</html>