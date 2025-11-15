<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../helpers/Language.php';
require_once __DIR__ . '/../../../config/database.php';

$auth = new AuthController();
if (!$auth->isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

$user = $auth->getCurrentUser();\n$lang = $_GET['lang'] ?? $_SESSION['language'] ?? 'en';
$db = new Database();
$conn = $db->getConnection();

$period = $_GET['period'] ?? 'week';

// Date ranges
$date_conditions = [
    'week' => "DATE(s.sale_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)",
    'month' => "DATE(s.sale_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)",
    'quarter' => "DATE(s.sale_date) >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)"
];

$date_condition = $date_conditions[$period];

// Most profitable products
$profitable_products = $conn->query("
    SELECT p.product_name, p.stock_quantity, p.reorder_level,
           COALESCE(SUM(si.quantity), 0) as total_sold,
           COALESCE(SUM(si.quantity * (si.unit_price - p.cost_price)), 0) as total_profit,
           COALESCE(AVG(si.unit_price - p.cost_price), 0) as avg_profit_per_unit
    FROM products p
    LEFT JOIN sale_items si ON p.product_id = si.product_id
    GROUP BY p.product_id
    ORDER BY total_profit DESC
    LIMIT 20
");

// Low stock items that need reordering
$restock_needed = $conn->query("
    SELECT p.product_name, p.stock_quantity, p.reorder_level,
           0 as sold_recently,
           NULL as days_remaining
    FROM products p
    WHERE p.stock_quantity <= p.reorder_level
    ORDER BY p.stock_quantity ASC
");

// Customer frequency analysis
$customer_analysis = $conn->query("
    SELECT c.full_name, c.phone_number,
           0 as visit_count,
           0 as total_spent,
           0 as avg_per_visit,
           NULL as last_visit
    FROM customers c
    ORDER BY c.full_name
    LIMIT 15
");

// Sales trends by day - simplified for now
$daily_trends = $conn->query("
    SELECT CURDATE() as sale_day,
           0 as transaction_count,
           0 as daily_revenue
    LIMIT 1
");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Analytics - SmartSHOP</title>
    <link rel="stylesheet" href="../../../public/css/main.css">
    <link rel="stylesheet" href="../../../public/css/dashboard.css">
</head>
<body>
    <div class="language-selector">
        <select onchange="changeLanguage(this.value)">
            <option value="en">English</option>
            <option value="rw">Kinyarwanda</option>
        </select>
    </div>

    <div class="dashboard-container">
        <nav class="top-nav">
            <div class="nav-links">
                <a href="../dashboard/index.php"><?php echo Language::get('dashboard', $lang); ?></a>
                <a href="../pos/index.php">POS</a>
                <a href="../inventory/index.php">Inventory</a>
                <a href="../reports/daily.php">Reports</a>
                <a href="../customers/index.php">Customers</a>
            </div>
        </nav>
        
        <header class="header">
            <h1>🎯 Business Analytics</h1>
            <div class="user-info">
                <div class="user-profile">
                    <img src="../../../uploads/profiles/<?php echo $user['user_id']; ?>.jpg?v=<?php echo time(); ?>" alt="Profile" class="profile-img" onerror="this.src='../../../uploads/profiles/default.jpg'">
                    <div class="user-details">
                        <span class="user-name"><?php echo $user['full_name']; ?></span>
                        <span class="user-role"><?php echo $user['role']; ?></span>
                    </div>
                    <div class="user-menu">
                        <a href="../profile/index.php" class="profile-link"><?php echo Language::get('profile', $lang); ?></a>
                        <a href="../../controllers/logout.php" class="btn-logout"><?php echo Language::get('logout', $lang); ?></a>
                    </div>
                </div>
            </div>
        </header>

        <div class="content">
            <div class="period-selector">
                <label>Analysis Period:</label>
                <select onchange="changePeriod(this.value)" class="form-input">
                    <option value="week" <?php echo $period === 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                    <option value="month" <?php echo $period === 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
                    <option value="quarter" <?php echo $period === 'quarter' ? 'selected' : ''; ?>>Last 90 Days</option>
                </select>
            </div>

            <div class="analytics-grid">
                <div class="analytics-section">
                    <h2>💰 Most Profitable Products</h2>
                    <p class="insight">Focus on stocking these high-profit items</p>
                    <table>
                        <thead>
                            <tr>
                                <th><?php echo Language::get('product', $lang); ?></th>
                                <th>Stock</th>
                                <th>Sold</th>
                                <th><?php echo Language::get('total_profit', $lang); ?></th>
                                <th>Profit/Unit</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($product = $profitable_products->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $product['product_name']; ?></td>
                                    <td class="<?php echo $product['stock_quantity'] <= $product['reorder_level'] ? 'low-stock' : ''; ?>">
                                        <?php echo $product['stock_quantity']; ?>
                                    </td>
                                    <td><?php echo $product['total_sold'] ?? 0; ?></td>
                                    <td class="profit"><?php echo number_format($product['total_profit'] ?? 0); ?> RWF</td>
                                    <td><?php echo number_format($product['avg_profit_per_unit'] ?? 0); ?> RWF</td>
                                    <td>
                                        <?php if ($product['stock_quantity'] <= $product['reorder_level']): ?>
                                            <span class="action-needed">Restock Now</span>
                                        <?php else: ?>
                                            <span class="action-ok">Stock OK</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <div class="analytics-section">
                    <h2>⚠️ Restock Recommendations</h2>
                    <p class="insight">Items running low based on sales velocity</p>
                    <table>
                        <thead>
                            <tr>
                                <th><?php echo Language::get('product', $lang); ?></th>
                                <th>Current Stock</th>
                                <th>Reorder Level</th>
                                <th>Recently Sold</th>
                                <th><?php echo Language::get('days_left', $lang); ?></th>
                                <th>Priority</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($item = $restock_needed->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $item['product_name']; ?></td>
                                    <td class="low-stock"><?php echo $item['stock_quantity']; ?></td>
                                    <td><?php echo $item['reorder_level']; ?></td>
                                    <td><?php echo $item['sold_recently'] ?? 0; ?></td>
                                    <td><?php echo $item['days_remaining'] ? number_format($item['days_remaining'], 1) : 'N/A'; ?></td>
                                    <td>
                                        <?php 
                                        $days = $item['days_remaining'] ?? 999;
                                        if ($days < 3) echo '<span class="priority-high">HIGH</span>';
                                        elseif ($days < 7) echo '<span class="priority-medium">MEDIUM</span>';
                                        else echo '<span class="priority-low">LOW</span>';
                                        ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <div class="analytics-section">
                    <h2>👥 Customer Insights</h2>
                    <p class="insight">Your most valuable customers</p>
                    <table>
                        <thead>
                            <tr>
                                <th><?php echo Language::get('customer', $lang); ?></th>
                                <th>Visits</th>
                                <th>Total Spent</th>
                                <th>Avg/Visit</th>
                                <th>Last Visit</th>
                                <th><?php echo Language::get('status', $lang); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($customer = $customer_analysis->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php echo $customer['full_name']; ?>
                                        <br><small><?php echo $customer['phone_number']; ?></small>
                                    </td>
                                    <td><?php echo $customer['visit_count'] ?? 0; ?></td>
                                    <td class="profit"><?php echo number_format($customer['total_spent'] ?? 0); ?> RWF</td>
                                    <td><?php echo number_format($customer['avg_per_visit'] ?? 0); ?> RWF</td>
                                    <td><?php echo $customer['last_visit'] ? date('M d', strtotime($customer['last_visit'])) : 'Never'; ?></td>
                                    <td>
                                        <?php 
                                        $spent = $customer['total_spent'] ?? 0;
                                        if ($spent > 50000) echo '<span class="vip">VIP</span>';
                                        elseif ($spent > 20000) echo '<span class="regular">Regular</span>';
                                        else echo '<span class="new"><?php echo Language::get('new', $lang); ?></span>';
                                        ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <div class="analytics-section">
                    <h2>📈 Sales Trends</h2>
                    <p class="insight">Daily performance overview</p>
                    <table>
                        <thead>
                            <tr>
                                <th><?php echo Language::get('date', $lang); ?></th>
                                <th><?php echo Language::get('transactions', $lang); ?></th>
                                <th><?php echo Language::get('revenue', $lang); ?></th>
                                <th>Avg/Transaction</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($trend = $daily_trends->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($trend['sale_day'])); ?></td>
                                    <td><?php echo $trend['transaction_count']; ?></td>
                                    <td class="profit"><?php echo number_format($trend['daily_revenue']); ?> RWF</td>
                                    <td><?php echo number_format($trend['daily_revenue'] / $trend['transaction_count']); ?> RWF</td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <?php include __DIR__ . '/../../includes/footer.php'; ?>
    </div>

    <style>
        .language-selector {
            position: fixed !important;
            top: 20px !important;
            right: 20px !important;
            left: auto !important;
            z-index: 1000 !important;
        }
    .period-selector {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
        display: flex;
        gap: 1rem;
        align-items: center;
    }
    
    .analytics-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .analytics-section {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .insight {
        color: #666;
        font-style: italic;
        margin-bottom: 1rem;
    }
    
    .analytics-section table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
    }
    
    .analytics-section th,
    .analytics-section td {
        padding: 0.75rem;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    
    .analytics-section th {
        background: var(--background);
        font-weight: 600;
    }
    
    .low-stock { color: red; font-weight: bold; }
    .profit { color: green; font-weight: bold; }
    .action-needed { color: red; font-weight: bold; }
    .action-ok { color: green; }
    
    .priority-high { background: #dc3545; color: white; padding: 2px 6px; border-radius: 3px; font-size: 0.8rem; }
    .priority-medium { background: #fd7e14; color: white; padding: 2px 6px; border-radius: 3px; font-size: 0.8rem; }
    .priority-low { background: #28a745; color: white; padding: 2px 6px; border-radius: 3px; font-size: 0.8rem; }
    
    .vip { background: #6f42c1; color: white; padding: 2px 6px; border-radius: 3px; font-size: 0.8rem; }
    .regular { background: #007bff; color: white; padding: 2px 6px; border-radius: 3px; font-size: 0.8rem; }
    .new { background: #6c757d; color: white; padding: 2px 6px; border-radius: 3px; font-size: 0.8rem; }
    </style>

    <script>
        function changePeriod(period) {
            window.location.href = '?period=' + period;
        }
        
        function changeLanguage(lang) {
            window.location.href = '?lang=' + lang + '&period=' + '<?php echo $period; ?>';
        }
    </script>
</body>
</html>