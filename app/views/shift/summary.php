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
$user_id = $user['user_id'];

// Get shift info for the selected date
$shift_info = $conn->query("
    SELECT * FROM staff_schedules 
    WHERE user_id = $user_id AND shift_date = '$selected_date'
")->fetch_assoc();

// Get sales made by this cashier on selected date
$sales_summary = $conn->query("
    SELECT 
        COUNT(*) as total_transactions,
        SUM(total_amount) as total_sales,
        AVG(total_amount) as avg_transaction
    FROM sales 
    WHERE user_id = $user_id AND DATE(sale_date) = '$selected_date'
")->fetch_assoc();

// Get hourly breakdown
$hourly_sales = $conn->query("
    SELECT 
        HOUR(sale_date) as hour,
        COUNT(*) as transactions,
        SUM(total_amount) as amount
    FROM sales 
    WHERE user_id = $user_id AND DATE(sale_date) = '$selected_date'
    GROUP BY HOUR(sale_date)
    ORDER BY hour
");

// Get payment methods breakdown
$payment_methods = $conn->query("
    SELECT 
        payment_method,
        COUNT(*) as count,
        SUM(total_amount) as amount
    FROM sales 
    WHERE user_id = $user_id AND DATE(sale_date) = '$selected_date'
    GROUP BY payment_method
");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Shift Summary - SmartSHOP</title>
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
            <h1>📊 My Shift Summary</h1>
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
                <label><?php echo Language::getText('select_date', $lang); ?>:</label>
                <input type="date" value="<?php echo $selected_date; ?>" onchange="changeDate(this.value)" class="form-input">
            </div>

            <?php if ($shift_info): ?>
                <div class="shift-info">
                    <h2><?php echo Language::getText('shift_information', $lang); ?></h2>
                    <div class="shift-details">
                        <div class="shift-card">
                            <h3><?php echo Language::getText('shift_type', $lang); ?></h3>
                            <p><?php echo ucfirst($shift_info['shift_type']); ?></p>
                        </div>
                        <div class="shift-card">
                            <h3><?php echo Language::getText('scheduled_time', $lang); ?></h3>
                            <p><?php echo date('H:i', strtotime($shift_info['start_time'])); ?> - <?php echo date('H:i', strtotime($shift_info['end_time'])); ?></p>
                        </div>
                        <div class="shift-card">
                            <h3><?php echo Language::getText('status', $lang); ?></h3>
                            <p><?php echo ucfirst($shift_info['status']); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="summary-cards">
                <div class="summary-card">
                    <h3><?php echo Language::getText('total_transactions', $lang); ?></h3>
                    <p class="big-number"><?php echo $sales_summary['total_transactions'] ?? 0; ?></p>
                </div>
                <div class="summary-card">
                    <h3><?php echo Language::getText('total_sales', $lang); ?></h3>
                    <p class="big-number"><?php echo number_format($sales_summary['total_sales'] ?? 0); ?> RWF</p>
                </div>
                <div class="summary-card">
                    <h3><?php echo Language::getText('average_transaction', $lang); ?></h3>
                    <p class="big-number"><?php echo number_format($sales_summary['avg_transaction'] ?? 0); ?> RWF</p>
                </div>
            </div>

            <div class="reports-grid">
                <div class="report-section">
                    <h2><?php echo Language::getText('hourly_performance', $lang); ?></h2>
                    <table>
                        <thead>
                            <tr>
                                <th><?php echo Language::getText('hour', $lang); ?></th>
                                <th><?php echo Language::getText('transactions', $lang); ?></th>
                                <th><?php echo Language::getText('sales_amount', $lang); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($hour = $hourly_sales->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo sprintf('%02d:00', $hour['hour']); ?></td>
                                    <td><?php echo $hour['transactions']; ?></td>
                                    <td><?php echo number_format($hour['amount']); ?> RWF</td>
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
                                <th><?php echo Language::getText('count', $lang); ?></th>
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
    
    .shift-info {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }
    
    .shift-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-top: 1rem;
    }
    
    .shift-card {
        background: var(--background);
        padding: 1rem;
        border-radius: 8px;
        text-align: center;
    }
    
    .shift-card h3 {
        color: var(--primary);
        margin-bottom: 0.5rem;
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
    </script>
</body>
</html>