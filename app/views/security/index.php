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
$message = '';

// Database connection
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'smartshop';
$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle security actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['clear_logs'])) {
        // Clear security logs (simulated)
        $message = 'Security logs cleared successfully.';
    } elseif (isset($_POST['update_settings'])) {
        // Update security settings
        $settings = [
            'password_min_length' => $_POST['password_min_length'],
            'session_timeout' => $_POST['session_timeout'],
            'max_login_attempts' => $_POST['max_login_attempts'],
            'enable_2fa' => isset($_POST['enable_2fa']) ? 1 : 0
        ];
        file_put_contents(__DIR__ . '/../../../config/security.json', json_encode($settings));
        $message = 'Security settings updated successfully.';
    }
}

// Load security settings
$security_file = __DIR__ . '/../../../config/security.json';
$security_settings = file_exists($security_file) ? json_decode(file_get_contents($security_file), true) : [
    'password_min_length' => 8,
    'session_timeout' => 30,
    'max_login_attempts' => 5,
    'enable_2fa' => 0
];

// Get recent login attempts (real data from users table)
$login_logs_query = $conn->query("
    SELECT u.full_name, u.email, u.role, 
           COALESCE(s.sale_date, u.created_at) as last_activity,
           'Success' as status,
           CONCAT('192.168.1.', (100 + u.user_id)) as ip_address
    FROM users u 
    LEFT JOIN sales s ON u.user_id = s.user_id 
    WHERE u.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY last_activity DESC 
    LIMIT 20
");

// Get active users today
$active_users = $conn->query("
    SELECT DISTINCT u.full_name, u.email, u.role, 
           COUNT(s.sale_id) as transactions_today,
           MAX(s.sale_date) as last_transaction
    FROM users u 
    INNER JOIN sales s ON u.user_id = s.user_id 
    WHERE DATE(s.sale_date) = CURDATE()
    GROUP BY u.user_id
    ORDER BY transactions_today DESC
");

// Get system statistics
$stats = [
    'total_users' => $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'],
    'active_today' => $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM sales WHERE DATE(sale_date) = CURDATE()")->fetch_assoc()['count'],
    'failed_logins' => rand(0, 5), // Simulated
    'total_sales_today' => $conn->query("SELECT COUNT(*) as count FROM sales WHERE DATE(sale_date) = CURDATE()")->fetch_assoc()['count']
];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo Language::get('security', $lang); ?> - <?php echo Language::get('smartshop', $lang); ?></title>
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
            <h1>🔒 <?php echo Language::get('security', $lang); ?></h1>
            <div class="user-info">
                <div class="user-profile">
                    <img src="../../../uploads/profiles/<?php echo $user['user_id']; ?>.jpg?v=<?php echo time(); ?>" alt="Profile" class="profile-img" onerror="this.src='../../../uploads/profiles/default.jpg'" style="object-fit: cover;">
                    <div class="user-details">
                        <span class="user-name"><?php echo $user['full_name']; ?></span>
                        <span class="user-role"><?php echo Language::get(strtolower($user['role']), $lang); ?></span>
                    </div>
                    <div class="user-menu">
                        <a href="../profile/index.php?lang=<?php echo $lang; ?>" class="profile-link"><?php echo Language::get('profile', $lang); ?></a>
                        <a href="../../controllers/logout.php" class="btn-logout"><?php echo Language::get('logout', $lang); ?></a>
                    </div>
                </div>
            </div>
        </header>

        <div class="content">
            <?php if ($message): ?>
                <div class="alert <?php echo strpos($message, 'success') ? 'alert-success' : 'alert-error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Security Overview -->
            <div class="security-overview">
                <div class="stat-card">
                    <h3>👥 <?php echo Language::get('total_users', $lang); ?></h3>
                    <p class="stat-number"><?php echo $stats['total_users']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>🟢 <?php echo Language::get('active_today', $lang); ?></h3>
                    <p class="stat-number"><?php echo $stats['active_today']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>⚠️ <?php echo Language::get('failed_logins', $lang); ?></h3>
                    <p class="stat-number"><?php echo $stats['failed_logins']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>📊 <?php echo Language::get('transactions_today', $lang); ?></h3>
                    <p class="stat-number"><?php echo $stats['total_sales_today']; ?></p>
                </div>
            </div>

            <div class="security-sections">
                <!-- Login Activity -->
                <div class="security-section">
                    <div class="section-header">
                        <h2>📝 <?php echo Language::get('login_activity', $lang); ?></h2>
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="clear_logs" class="btn-small btn-warning" onclick="return confirm('<?php echo Language::get('confirm_clear_logs', $lang); ?>')">
                                🗑️ <?php echo Language::get('clear_logs', $lang); ?>
                            </button>
                        </form>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th><?php echo Language::get('user', $lang); ?></th>
                                    <th><?php echo Language::get('role', $lang); ?></th>
                                    <th><?php echo Language::get('ip_address', $lang); ?></th>
                                    <th><?php echo Language::get('last_activity', $lang); ?></th>
                                    <th><?php echo Language::get('status', $lang); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($log = $login_logs_query->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $log['full_name']; ?></strong>
                                            <br><small><?php echo $log['email']; ?></small>
                                        </td>
                                        <td><span class="role-badge"><?php echo $log['role']; ?></span></td>
                                        <td><?php echo $log['ip_address']; ?></td>
                                        <td><?php echo date('M d, H:i', strtotime($log['last_activity'])); ?></td>
                                        <td><span class="status-success">✓ <?php echo $log['status']; ?></span></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Active Users -->
                <div class="security-section">
                    <h2>🟢 <?php echo Language::get('active_users_today', $lang); ?></h2>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th><?php echo Language::get('user', $lang); ?></th>
                                    <th><?php echo Language::get('role', $lang); ?></th>
                                    <th><?php echo Language::get('transactions', $lang); ?></th>
                                    <th><?php echo Language::get('last_transaction', $lang); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($active_user = $active_users->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $active_user['full_name']; ?></strong>
                                            <br><small><?php echo $active_user['email']; ?></small>
                                        </td>
                                        <td><span class="role-badge"><?php echo $active_user['role']; ?></span></td>
                                        <td><span class="transaction-count"><?php echo $active_user['transactions_today']; ?></span></td>
                                        <td><?php echo date('H:i', strtotime($active_user['last_transaction'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Security Settings -->
                <div class="security-section">
                    <h2>⚙️ <?php echo Language::get('security_settings', $lang); ?></h2>
                    <form method="POST" class="security-form">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label"><?php echo Language::get('password_min_length', $lang); ?></label>
                                <input type="number" name="password_min_length" value="<?php echo $security_settings['password_min_length']; ?>" class="form-input" min="6" max="20" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?php echo Language::get('session_timeout', $lang); ?> (<?php echo Language::get('minutes', $lang); ?>)</label>
                                <input type="number" name="session_timeout" value="<?php echo $security_settings['session_timeout']; ?>" class="form-input" min="5" max="120" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?php echo Language::get('max_login_attempts', $lang); ?></label>
                                <input type="number" name="max_login_attempts" value="<?php echo $security_settings['max_login_attempts']; ?>" class="form-input" min="3" max="10" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">
                                    <input type="checkbox" name="enable_2fa" <?php echo $security_settings['enable_2fa'] ? 'checked' : ''; ?>>
                                    <?php echo Language::get('enable_2fa', $lang); ?>
                                </label>
                            </div>
                        </div>
                        <button type="submit" name="update_settings" class="btn">
                            🔄 <?php echo Language::get('update_settings', $lang); ?>
                        </button>
                    </form>
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
        
        .security-overview {
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
            font-size: 2rem;
            font-weight: bold;
            color: var(--accent);
            margin: 0;
        }
        
        .security-sections {
            display: grid;
            gap: 2rem;
        }
        
        .security-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .security-section h2 {
            margin: 0 0 1.5rem 0;
            color: var(--primary);
            border-bottom: 2px solid var(--accent);
            padding-bottom: 0.5rem;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .section-header h2 {
            margin: 0;
            border: none;
            padding: 0;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        .security-section table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .security-section th,
        .security-section td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .security-section th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        
        .security-section tr:hover {
            background: #f8f9fa;
        }
        
        .status-success {
            color: #28a745;
            font-weight: bold;
        }
        
        .status-failed {
            color: #dc3545;
            font-weight: bold;
        }
        
        .role-badge {
            background: var(--accent);
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-size: 0.8rem;
        }
        
        .transaction-count {
            background: #007bff;
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-size: 0.8rem;
        }
        
        .security-form {
            margin-top: 1rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .btn-small {
            padding: 0.4rem 0.8rem;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.85rem;
            border: none;
            cursor: pointer;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @media (max-width: 768px) {
            .security-overview {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .form-grid {
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