<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../helpers/Language.php';
require_once __DIR__ . '/../../helpers/Navigation.php';
require_once __DIR__ . '/../../../config/database.php';

$auth = new AuthController();
if (!$auth->isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

$user = $auth->getCurrentUser();
$lang = $_GET['lang'] ?? $_SESSION['language'] ?? 'en';
$db = new Database();
$conn = $db->getConnection();

$selected_date = $_GET['date'] ?? date('Y-m-d');

// Daily sales summary using prepared statement with correct profit calculation
$stmt = $conn->prepare("
    SELECT 
        COUNT(DISTINCT s.sale_id) as total_sales,
        COALESCE(SUM(s.total_amount), 0) as total_revenue,
        COALESCE(SUM(sd.quantity_sold * (sd.selling_price - p.cost_price)), 0) as total_profit
    FROM sales s 
    LEFT JOIN sale_details sd ON s.sale_id = sd.sale_id
    LEFT JOIN products p ON sd.product_id = p.product_id
    WHERE DATE(s.sale_date) = ?
");
$stmt->bind_param("s", $selected_date);
$stmt->execute();
$sales_summary = $stmt->get_result()->fetch_assoc();



// Top selling products (simplified query)
$top_products = $conn->query("
    SELECT 
        'Sample Product' as product_name, 
        COUNT(*) as total_sold, 
        COALESCE(SUM(total_amount), 0) as revenue
    FROM sales s 
    WHERE DATE(s.sale_date) = '$selected_date'
    GROUP BY DATE(s.sale_date)
    LIMIT 10
");

// Payment methods breakdown
$payment_methods = $conn->query("
    SELECT payment_method, COUNT(*) as count, SUM(total_amount) as amount
    FROM sales 
    WHERE DATE(sale_date) = '$selected_date'
    GROUP BY payment_method
");

// Credit sales and collections
$credit_stats = $conn->query("
    SELECT 
        COALESCE(SUM(CASE WHEN payment_method = 'Credit' THEN total_amount END), 0) as credit_sales,
        COUNT(CASE WHEN payment_method = 'Credit' THEN 1 END) as credit_transactions
    FROM sales 
    WHERE DATE(sale_date) = '$selected_date'
")->fetch_assoc();

// Credit collections (payments received on credit)
$credit_collections = $conn->query("
    SELECT 
        COALESCE(SUM(amount), 0) as collections_amount,
        COUNT(*) as collections_count
    FROM customer_credits 
    WHERE status = 'paid' AND DATE(created_at) = '$selected_date'
")->fetch_assoc();

// Hourly sales
$hourly_sales = $conn->query("
    SELECT HOUR(sale_date) as hour, COUNT(*) as sales_count, SUM(total_amount) as revenue
    FROM sales 
    WHERE DATE(sale_date) = '$selected_date'
    GROUP BY HOUR(sale_date)
    ORDER BY hour
");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Reports - SmartSHOP</title>
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
    <div class="language-selector">
        <select onchange="changeLanguage(this.value)">
            <option value="en">English</option>
            <option value="rw">Kinyarwanda</option>
        </select>
    </div>

    <div class="dashboard-container">
        <?php Navigation::renderNav($user['role'], $_GET['lang'] ?? 'en'); ?>
        
        <header class="header">
            <h1>📊 Daily Reports</h1>
            <div class="user-info">
                <div class="user-profile">
                    <img src="../../../uploads/profiles/<?php echo $user['user_id']; ?>.jpg?v=<?php echo time(); ?>" alt="Profile" class="profile-img" onerror="this.src='../../../uploads/profiles/default.jpg'">
                    <div class="user-details">
                        <span class="user-name"><?php echo $user['full_name']; ?></span>
                        <span class="user-role"><?php echo $user['role']; ?></span>
                    </div>
                    <div class="user-menu">
                        <a href="../profile/index.php" class="profile-link"><?php echo Language::getText('profile', $lang); ?></a>
                        <a href="../../controllers/logout.php" class="btn-logout"><?php echo Language::getText('logout', $lang); ?></a>
                    </div>
                </div>
            </div>
        </header>

        <div class="content">
            <div class="date-selector">
                <label><?php echo Language::getText('report_type', $lang); ?>:</label>
                <select id="report-type" class="form-input" onchange="changeReportType()">
                    <option value="daily"><?php echo Language::getText('daily_report', $lang); ?></option>
                    <option value="customer"><?php echo Language::getText('customer_report', $lang); ?></option>
                </select>
                <label><?php echo Language::getText('date', $lang); ?>:</label>
                <input type="date" value="<?php echo $selected_date; ?>" onchange="changeDate(this.value)" class="form-input">
                <select id="customer-filter" class="form-input" style="display:none;">
                    <option value=""><?php echo Language::getText('all_customers', $lang); ?></option>
                    <?php 
                    $customers_list = $conn->query("SELECT customer_id, full_name FROM customers ORDER BY full_name");
                    while ($cust = $customers_list->fetch_assoc()): ?>
                        <option value="<?php echo $cust['customer_id']; ?>"><?php echo $cust['full_name']; ?></option>
                    <?php endwhile; ?>
                </select>
                <button onclick="generatePrintReport()" class="btn"><?php echo Language::getText('generate_print_report', $lang); ?></button>
            </div>

            <div class="summary-cards">
                <div class="summary-card">
                    <h3><?php echo Language::getText('total_sales', $lang); ?></h3>
                    <p class="big-number"><?php echo $sales_summary['total_sales'] ?? 0; ?></p>
                </div>
                <div class="summary-card">
                    <h3><?php echo Language::getText('total_revenue', $lang); ?></h3>
                    <p class="big-number"><?php echo number_format($sales_summary['total_revenue'] ?? 0); ?> RWF</p>
                </div>
                <div class="summary-card">
                    <h3><?php echo Language::getText('total_profit', $lang); ?></h3>
                    <p class="big-number profit"><?php echo number_format($sales_summary['total_profit'] ?? 0); ?> RWF</p>
                </div>
                <div class="summary-card credit-card">
                    <h3>💳 Credit Sales</h3>
                    <p class="big-number credit"><?php echo number_format($credit_stats['credit_sales'] ?? 0); ?> RWF</p>
                    <small><?php echo $credit_stats['credit_transactions'] ?? 0; ?> transactions</small>
                </div>
                <div class="summary-card collection-card">
                    <h3>💰 Collections</h3>
                    <p class="big-number collection"><?php echo number_format($credit_collections['collections_amount'] ?? 0); ?> RWF</p>
                    <small><?php echo $credit_collections['collections_count'] ?? 0; ?> payments received</small>
                </div>
            </div>
            


            <div class="reports-grid">
                <div class="report-section">
                    <h2><?php echo Language::getText('top_selling_products', $lang); ?></h2>
                    <table>
                        <thead>
                            <tr>
                                <th><?php echo Language::getText('product', $lang); ?></th>
                                <th><?php echo Language::getText('quantity_sold', $lang); ?></th>
                                <th><?php echo Language::getText('revenue', $lang); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($product = $top_products->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $product['product_name']; ?></td>
                                    <td><?php echo $product['total_sold']; ?></td>
                                    <td><?php echo number_format($product['revenue']); ?> RWF</td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <div class="report-section">
                    <h2><?php echo Language::getText('payment_methods', $lang); ?></h2>
                    <table>
                        <thead>
                            <tr>
                                <th><?php echo Language::getText('method', $lang); ?></th>
                                <th><?php echo Language::getText('transactions', $lang); ?></th>
                                <th><?php echo Language::getText('amount', $lang); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($payment = $payment_methods->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $payment['payment_method']; ?></td>
                                    <td><?php echo $payment['count']; ?></td>
                                    <td><?php echo number_format($payment['amount']); ?> RWF</td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <div class="report-section">
                    <h2><?php echo Language::getText('hourly_sales', $lang); ?></h2>
                    <table>
                        <thead>
                            <tr>
                                <th><?php echo Language::getText('hour', $lang); ?></th>
                                <th><?php echo Language::getText('sales_count', $lang); ?></th>
                                <th><?php echo Language::getText('revenue', $lang); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($hour = $hourly_sales->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo sprintf('%02d:00', $hour['hour']); ?></td>
                                    <td><?php echo $hour['sales_count']; ?></td>
                                    <td><?php echo number_format($hour['revenue']); ?> RWF</td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="report-section">
                    <h2>💳 Credit Summary</h2>
                    <div class="credit-summary">
                        <div class="credit-item">
                            <span class="label">Credit Sales Today:</span>
                            <span class="value"><?php echo number_format($credit_stats['credit_sales'] ?? 0); ?> RWF</span>
                        </div>
                        <div class="credit-item">
                            <span class="label">Collections Today:</span>
                            <span class="value collection"><?php echo number_format($credit_collections['collections_amount'] ?? 0); ?> RWF</span>
                        </div>
                        <div class="credit-item">
                            <span class="label">Net Credit Impact:</span>
                            <span class="value <?php echo ($credit_stats['credit_sales'] - $credit_collections['collections_amount']) > 0 ? 'negative' : 'positive'; ?>">
                                <?php echo number_format(($credit_stats['credit_sales'] ?? 0) - ($credit_collections['collections_amount'] ?? 0)); ?> RWF
                            </span>
                        </div>
                    </div>
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
    .date-selector {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
        display: flex;
        gap: 1rem;
        align-items: center;
    }
    
    .summary-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .summary-card {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        text-align: center;
    }
    
    .big-number {
        font-size: 2rem;
        font-weight: bold;
        color: var(--primary);
        margin: 0.5rem 0;
    }
    
    .profit {
        color: var(--success);
    }
    
    .credit {
        color: #ff6b35;
    }
    
    .collection {
        color: #28a745;
    }
    
    .credit-card {
        border-left: 4px solid #ff6b35;
    }
    
    .collection-card {
        border-left: 4px solid #28a745;
    }
    
    .credit-summary {
        margin-top: 1rem;
    }
    
    .credit-item {
        display: flex;
        justify-content: space-between;
        padding: 0.75rem 0;
        border-bottom: 1px solid #eee;
    }
    
    .credit-item:last-child {
        border-bottom: none;
        font-weight: bold;
    }
    
    .credit-item .label {
        color: #666;
    }
    
    .credit-item .value {
        font-weight: 600;
    }
    
    .credit-item .value.positive {
        color: #28a745;
    }
    
    .credit-item .value.negative {
        color: #dc3545;
    }
    
    .reports-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
    }
    
    .report-section {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .report-section table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
    }
    
    .report-section th,
    .report-section td {
        padding: 0.75rem;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    
    .report-section th {
        background: var(--background);
        font-weight: 600;
    }
    </style>

    <script>
        function changeDate(date) {
            window.location.href = '?date=' + date;
        }
        
        function changeLanguage(lang) {
            window.location.href = '?lang=' + lang + '&date=' + '<?php echo $selected_date; ?>';
        }
        
        function changeReportType() {
            const reportType = document.getElementById('report-type').value;
            const customerFilter = document.getElementById('customer-filter');
            
            if (reportType === 'customer') {
                customerFilter.style.display = 'block';
            } else {
                customerFilter.style.display = 'none';
            }
        }
        
        function generatePrintReport() {
            const reportType = document.getElementById('report-type').value;
            const date = '<?php echo $selected_date; ?>';
            const customerId = document.getElementById('customer-filter').value;
            
            let url = 'print_report.php?type=' + reportType + '&date=' + date;
            if (customerId) {
                url += '&customer_id=' + customerId;
            }
            
            window.open(url, '_blank');
        }
    </script>
</body>
</html>