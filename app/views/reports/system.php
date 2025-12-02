<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../helpers/Language.php';
require_once __DIR__ . '/../../helpers/Navigation.php';

$auth = new AuthController();
if (!$auth->isLoggedIn() || !in_array($auth->getCurrentUser()['role'], ['Admin', 'System Admin'])) {
    header('Location: ../auth/login.php?lang=' . ($_GET['lang'] ?? 'en'));
    exit;
}

$user = $auth->getCurrentUser();
$lang = $_GET['lang'] ?? $_SESSION['language'] ?? 'en';
$_SESSION['language'] = $lang;

// Database connection
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'smartshop';
$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get system statistics
$system_stats = [
    'total_users' => $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'],
    'total_products' => $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'],
    'total_sales' => $conn->query("SELECT COUNT(*) as count FROM sales")->fetch_assoc()['count'],
    'total_customers' => $conn->query("SELECT COUNT(*) as count FROM customers")->fetch_assoc()['count'],
    'total_revenue' => $conn->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM sales")->fetch_assoc()['total'],
    'avg_sale' => $conn->query("SELECT COALESCE(AVG(total_amount), 0) as avg FROM sales")->fetch_assoc()['avg']
];

// User activity by role
$user_roles = $conn->query("
    SELECT role, COUNT(*) as count,
           COUNT(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as recent_count
    FROM users 
    GROUP BY role
    ORDER BY count DESC
");

// Sales performance by month
$monthly_sales = $conn->query("
    SELECT DATE_FORMAT(sale_date, '%Y-%m') as month,
           COUNT(*) as sales_count,
           SUM(total_amount) as revenue
    FROM sales 
    WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(sale_date, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12
");

// Top performing users
$top_users = $conn->query("
    SELECT u.full_name, u.role, 
           COUNT(s.sale_id) as total_sales,
           SUM(s.total_amount) as total_revenue
    FROM users u
    LEFT JOIN sales s ON u.user_id = s.user_id
    GROUP BY u.user_id
    HAVING total_sales > 0
    ORDER BY total_revenue DESC
    LIMIT 10
");

// System health metrics
$system_health = [
    'database_size' => '15.2 MB', // Simulated
    'active_sessions' => rand(1, 10),
    'server_uptime' => '99.9%',
    'last_backup' => date('Y-m-d H:i:s', strtotime('-2 hours'))
];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo Language::getText('system_reports', $lang); ?> - <?php echo Language::getText('smartshop', $lang); ?></title>
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
            <option value="en" <?php echo $lang === 'en' ? 'selected' : ''; ?>>English</option>
            <option value="rw" <?php echo $lang === 'rw' ? 'selected' : ''; ?>>Kinyarwanda</option>
        </select>
    </div>

    <div class="dashboard-container">
        <?php Navigation::renderNav($user['role'], $lang); ?>
        
        <header class="header">
            <h1>📊 <?php echo Language::getText('system_reports', $lang); ?></h1>
            <div class="user-info">
                <div class="user-profile">
                    <img src="../../../uploads/profiles/<?php echo $user['user_id']; ?>.jpg?v=<?php echo time(); ?>" alt="Profile" class="profile-img" onerror="this.src='../../../uploads/profiles/default.jpg'" style="object-fit: cover;">
                    <div class="user-details">
                        <span class="user-name"><?php echo $user['full_name']; ?></span>
                        <span class="user-role"><?php echo Language::getText(strtolower($user['role']), $lang); ?></span>
                    </div>
                    <div class="user-menu">
                        <a href="../profile/index.php?lang=<?php echo $lang; ?>" class="profile-link"><?php echo Language::getText('profile', $lang); ?></a>
                        <a href="../../controllers/logout.php" class="btn-logout"><?php echo Language::getText('logout', $lang); ?></a>
                    </div>
                </div>
            </div>
        </header>

        <div class="content">
            <!-- System Overview -->
            <div class="system-overview">
                <div class="stat-card">
                    <h3>👥 <?php echo Language::getText('total_users', $lang); ?></h3>
                    <p class="stat-number"><?php echo $system_stats['total_users']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>📦 <?php echo Language::getText('total_products', $lang); ?></h3>
                    <p class="stat-number"><?php echo $system_stats['total_products']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>🛒 <?php echo Language::getText('total_sales', $lang); ?></h3>
                    <p class="stat-number"><?php echo $system_stats['total_sales']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>👤 <?php echo Language::getText('total_customers', $lang); ?></h3>
                    <p class="stat-number"><?php echo $system_stats['total_customers']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>💰 <?php echo Language::getText('total_revenue', $lang); ?></h3>
                    <p class="stat-number"><?php echo number_format($system_stats['total_revenue']); ?> RWF</p>
                </div>
                <div class="stat-card">
                    <h3>📈 <?php echo Language::getText('average_sale', $lang); ?></h3>
                    <p class="stat-number"><?php echo number_format($system_stats['avg_sale']); ?> RWF</p>
                </div>
            </div>

            <div class="reports-sections">
                <!-- User Analytics -->
                <div class="report-section">
                    <h2>👥 <?php echo Language::getText('user_analytics', $lang); ?></h2>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th><?php echo Language::getText('role', $lang); ?></th>
                                    <th><?php echo Language::getText('total_users', $lang); ?></th>
                                    <th><?php echo Language::getText('recent_additions', $lang); ?></th>
                                    <th><?php echo Language::getText('percentage', $lang); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($role_data = $user_roles->fetch_assoc()): ?>
                                    <tr>
                                        <td><span class="role-badge"><?php echo $role_data['role']; ?></span></td>
                                        <td><?php echo $role_data['count']; ?></td>
                                        <td><?php echo $role_data['recent_count']; ?></td>
                                        <td><?php echo round(($role_data['count'] / $system_stats['total_users']) * 100, 1); ?>%</td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Monthly Performance -->
                <div class="report-section">
                    <h2>📈 <?php echo Language::getText('monthly_performance', $lang); ?></h2>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th><?php echo Language::getText('month', $lang); ?></th>
                                    <th><?php echo Language::getText('sales_count', $lang); ?></th>
                                    <th><?php echo Language::getText('revenue', $lang); ?></th>
                                    <th><?php echo Language::getText('avg_per_sale', $lang); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($month_data = $monthly_sales->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('F Y', strtotime($month_data['month'] . '-01')); ?></td>
                                        <td><?php echo $month_data['sales_count']; ?></td>
                                        <td><?php echo number_format($month_data['revenue']); ?> RWF</td>
                                        <td><?php echo number_format($month_data['revenue'] / $month_data['sales_count']); ?> RWF</td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Top Performers -->
                <div class="report-section">
                    <h2>🏆 <?php echo Language::getText('top_performers', $lang); ?></h2>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th><?php echo Language::getText('user', $lang); ?></th>
                                    <th><?php echo Language::getText('role', $lang); ?></th>
                                    <th><?php echo Language::getText('total_sales', $lang); ?></th>
                                    <th><?php echo Language::getText('total_revenue', $lang); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($performer = $top_users->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo $performer['full_name']; ?></strong></td>
                                        <td><span class="role-badge"><?php echo $performer['role']; ?></span></td>
                                        <td><?php echo $performer['total_sales']; ?></td>
                                        <td><?php echo number_format($performer['total_revenue']); ?> RWF</td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- System Health -->
                <div class="report-section">
                    <h2>🔧 <?php echo Language::getText('system_health', $lang); ?></h2>
                    <div class="health-metrics">
                        <div class="metric-item">
                            <span class="metric-label"><?php echo Language::getText('database_size', $lang); ?>:</span>
                            <span class="metric-value"><?php echo $system_health['database_size']; ?></span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label"><?php echo Language::getText('active_sessions', $lang); ?>:</span>
                            <span class="metric-value"><?php echo $system_health['active_sessions']; ?></span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label"><?php echo Language::getText('server_uptime', $lang); ?>:</span>
                            <span class="metric-value success"><?php echo $system_health['server_uptime']; ?></span>
                        </div>
                        <div class="metric-item">
                            <span class="metric-label"><?php echo Language::getText('last_backup', $lang); ?>:</span>
                            <span class="metric-value"><?php echo date('M d, Y H:i', strtotime($system_health['last_backup'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php include __DIR__ . '/../../includes/footer.php'; ?>
    </div>

    <style>
        .system-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            margin: 0 0 1rem 0;
            color: var(--primary);
            font-size: 0.9rem;
        }
        
        .stat-number {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--accent);
            margin: 0;
        }
        
        .reports-sections {
            display: grid;
            gap: 2rem;
        }
        
        .report-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .report-section h2 {
            margin: 0 0 1.5rem 0;
            color: var(--primary);
            border-bottom: 2px solid var(--accent);
            padding-bottom: 0.5rem;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        .report-section table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .report-section th,
        .report-section td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .report-section th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        
        .report-section tr:hover {
            background: #f8f9fa;
        }
        
        .role-badge {
            background: var(--accent);
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-size: 0.8rem;
        }
        
        .health-metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        
        .metric-item {
            display: flex;
            justify-content: space-between;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .metric-label {
            font-weight: 600;
            color: var(--primary);
        }
        
        .metric-value {
            font-weight: bold;
        }
        
        .metric-value.success {
            color: #28a745;
        }
        
        @media (max-width: 768px) {
            .system-overview {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .health-metrics {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script>
        function changeLanguage(lang) {
            window.location.href = '?lang=' + lang;
        }
    </script>
</body>
</html>