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

// Get customer loyalty info
$customer_id = $user['user_id'];
$customer = $conn->query("SELECT * FROM customers WHERE customer_id = $customer_id")->fetch_assoc();
$loyalty_points = $customer['loyalty_points'] ?? 0;

// Get loyalty history
$loyalty_history = $conn->query("
    SELECT * FROM loyalty_points 
    WHERE customer_id = $customer_id 
    ORDER BY created_at DESC 
    LIMIT 20
");
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loyalty Points - SmartSHOP</title>
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
            <h1>🎆 Loyalty Points</h1>
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
            <div class="loyalty-summary">
                <div class="points-card">
                    <div class="points-display">
                        <i class="fas fa-star"></i>
                        <div class="points-info">
                            <h2>Current Points</h2>
                            <div class="points-number"><?php echo number_format($loyalty_points); ?></div>
                            <p>Loyalty Points</p>
                        </div>
                    </div>
                </div>
                
                <div class="rewards-info">
                    <h3>How to Earn Points</h3>
                    <ul class="earn-list">
                        <li><i class="fas fa-shopping-cart"></i> 1 point for every 100 RWF spent</li>
                        <li><i class="fas fa-gift"></i> Bonus points on special occasions</li>
                        <li><i class="fas fa-calendar"></i> Birthday bonus: 50 points</li>
                    </ul>
                    
                    <h3>Redeem Rewards</h3>
                    <div class="rewards-grid">
                        <div class="reward-item">
                            <div class="reward-cost">100 pts</div>
                            <div class="reward-desc">5% discount on next purchase</div>
                        </div>
                        <div class="reward-item">
                            <div class="reward-cost">250 pts</div>
                            <div class="reward-desc">Free small item (soap, etc.)</div>
                        </div>
                        <div class="reward-item">
                            <div class="reward-cost">500 pts</div>
                            <div class="reward-desc">10% discount on next purchase</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="loyalty-history">
                <h2>Points History</h2>
                <?php if ($loyalty_history->num_rows === 0): ?>
                    <div class="no-history">
                        <i class="fas fa-star"></i>
                        <h3>No Points History</h3>
                        <p>Start shopping to earn loyalty points!</p>
                    </div>
                <?php else: ?>
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Points Earned</th>
                                <th>Points Redeemed</th>
                                <th>Transaction</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($record = $loyalty_history->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($record['created_at'])); ?></td>
                                    <td class="points-earned">
                                        <?php echo $record['points_earned'] > 0 ? '+' . $record['points_earned'] : ''; ?>
                                    </td>
                                    <td class="points-redeemed">
                                        <?php echo $record['points_redeemed'] > 0 ? '-' . $record['points_redeemed'] : ''; ?>
                                    </td>
                                    <td><?php echo $record['transaction_id'] ? '#' . $record['transaction_id'] : 'Manual'; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
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
        
        .loyalty-summary {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .points-card {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            padding: 2rem;
            border-radius: 15px;
            color: white;
            text-align: center;
        }
        
        .points-display {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }
        
        .points-display i {
            font-size: 3rem;
            color: #ffd700;
        }
        
        .points-number {
            font-size: 3rem;
            font-weight: bold;
            margin: 0.5rem 0;
        }
        
        .rewards-info {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .earn-list {
            list-style: none;
            padding: 0;
            margin: 1rem 0;
        }
        
        .earn-list li {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            color: #666;
        }
        
        .earn-list i {
            color: #4f46e5;
            width: 20px;
        }
        
        .rewards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .reward-item {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            border: 2px solid transparent;
            transition: border-color 0.2s;
        }
        
        .reward-item:hover {
            border-color: #4f46e5;
        }
        
        .reward-cost {
            font-size: 1.25rem;
            font-weight: bold;
            color: #4f46e5;
            margin-bottom: 0.5rem;
        }
        
        .reward-desc {
            color: #666;
            font-size: 0.9rem;
        }
        
        .loyalty-history {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .history-table th,
        .history-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .history-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .points-earned {
            color: #28a745;
            font-weight: bold;
        }
        
        .points-redeemed {
            color: #dc3545;
            font-weight: bold;
        }
        
        .no-history {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .no-history i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 1rem;
        }
    </style>
</body>
</html>