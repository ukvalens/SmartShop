<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../helpers/Language.php';
require_once __DIR__ . '/../../helpers/Navigation.php';

$auth = new AuthController();
if (!$auth->isLoggedIn()) {
    header('Location: ../auth/login.php?lang=' . $lang);
    exit;
}

$user = $auth->getCurrentUser();
$lang = $_GET['lang'] ?? $_SESSION['language'] ?? 'en';
$_SESSION['language'] = $lang;
$role = $user['role'];
$fullName = $user['full_name'];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo Language::get('dashboard', $lang); ?> - <?php echo Language::get('smartshop', $lang); ?></title>
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
        .payment-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        .payment-method {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid var(--accent);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .payment-method h4 {
            margin: 0 0 0.5rem 0;
            color: var(--primary);
        }
        .payment-method p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }
        .navigation-wrapper {
            display: block;
            width: 100%;
        }
        .top-nav {
            display: block !important;
            visibility: visible !important;
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
        <?php Navigation::renderNav($role, $lang); ?>
        
        <header class="header">
            <h1><?php echo Language::get('smartshop', $lang); ?></h1>
            <div class="user-info">
                <div class="user-profile">
                    <img src="../../../uploads/profiles/<?php echo $user['user_id']; ?>.jpg?v=<?php echo time(); ?>" alt="Profile" class="profile-img" onerror="this.src='../../../uploads/profiles/default.jpg'" style="object-fit: cover;">
                    <div class="user-details">
                        <span class="user-name"><?php echo $fullName; ?></span>
                        <span class="user-role"><?php echo Language::get(strtolower($role), $lang); ?></span>
                    </div>
                    <div class="user-menu">
                        <a href="../profile/index.php?lang=<?php echo $lang; ?>" class="profile-link"><?php echo Language::get('profile', $lang); ?></a>
                        <a href="../../controllers/logout.php" class="btn-logout"><?php echo Language::get('logout', $lang); ?></a>
                    </div>
                </div>
            </div>
        </header>

        <div class="content">
            <?php if ($role === 'System Admin' || $role === 'Admin'): ?>
                <div class="admin-panel">
                    <h2>System Administrator</h2>
                    <p class="role-desc">Complete system control and user management</p>
                    <div class="menu-grid">
                        <a href="../users/index.php?lang=<?php echo $lang; ?>" class="menu-item admin-item">
                            <h3>👥 <?php echo Language::get('user_management', $lang); ?></h3>
                            <p>Create, edit, delete users and assign roles</p>
                        </a>
                        <a href="../settings/system.php?lang=<?php echo $lang; ?>" class="menu-item admin-item">
                            <h3>⚙️ <?php echo Language::get('system_settings', $lang); ?></h3>
                            <p>Configure system parameters and preferences</p>
                        </a>
                        <a href="../reports/system.php?lang=<?php echo $lang; ?>" class="menu-item admin-item">
                            <h3>📊 System Reports</h3>
                            <p>User activity, system logs, performance metrics</p>
                        </a>
                        <a href="../backup/index.php?lang=<?php echo $lang; ?>" class="menu-item admin-item">
                            <h3>💾 Data Backup</h3>
                            <p>Database backup and restore operations</p>
                        </a>
                        <a href="../security/index.php?lang=<?php echo $lang; ?>" class="menu-item admin-item">
                            <h3>🔒 <?php echo Language::get('security', $lang); ?></h3>
                            <p>Access logs, security settings, permissions</p>
                        </a>
                        <a href="../profile/index.php?lang=<?php echo $lang; ?>" class="menu-item admin-item">
                            <h3>👤 My Profile</h3>
                            <p>Update profile and account settings</p>
                        </a>
                    </div>
                </div>
            <?php elseif ($role === 'Admin' || $role === 'Owner'): ?>
                <div class="owner-panel">
                    <h2>Business Owner</h2>
                    <p class="role-desc">Oversees entire business operations, including inventory, supplier orders, customer credit, and reporting</p>
                    
                    <div class="workflow-section">
                        <h3>🏪 Core Business Operations</h3>
                        <div class="menu-grid">
                            <a href="../suppliers/index.php?lang=<?php echo $lang; ?>" class="menu-item main-pos">
                                <h3>🚚 Supplier Management</h3>
                                <p>Create purchase orders for sugar, rice, soap and other products</p>
                            </a>
                            <a href="../inventory/index.php?lang=<?php echo $lang; ?>" class="menu-item owner-item">
                                <h3>📦 <?php echo Language::get('inventory_management', $lang); ?></h3>
                                <p>Verify delivered goods and update stock in the system</p>
                            </a>
                            <a href="../pos/index.php?lang=<?php echo $lang; ?>" class="menu-item owner-item">
                                <h3>💰 <?php echo Language::get('point_of_sale', $lang); ?></h3>
                                <p>Supervise sales transactions and customer service</p>
                            </a>
                        </div>
                    </div>
                    
                    <div class="workflow-section">
                        <h3>📊 Business Intelligence & Decision Making</h3>
                        <div class="menu-grid">
                            <a href="../reports/daily.php?lang=<?php echo $lang; ?>" class="menu-item owner-item">
                                <h3>📈 End-of-Day Reports</h3>
                                <p>Track sales, profits, top-selling products, and frequent customers</p>
                            </a>
                            <a href="../reports/analytics.php?lang=<?php echo $lang; ?>" class="menu-item owner-item">
                                <h3>🎯 Business Analytics</h3>
                                <p>Decide what to restock and identify most profitable products</p>
                            </a>
                            <a href="../customers/credit.php?lang=<?php echo $lang; ?>" class="menu-item owner-item">
                                <h3>💳 Credit Management</h3>
                                <p>Track customer debts and monitor payment collections</p>
                            </a>
                        </div>
                    </div>
                    
                    <div class="workflow-section">
                        <h3>👥 <?php echo Language::get('customer_management', $lang); ?></h3>
                        <div class="menu-grid">
                            <a href="../customers/index.php?lang=<?php echo $lang; ?>" class="menu-item owner-item">
                                <h3>👥 Customer Service</h3>
                                <p>Handle customer relationships and loyalty programs</p>
                            </a>
                            <a href="../profile/index.php?lang=<?php echo $lang; ?>" class="menu-item owner-item">
                                <h3>👤 My Profile</h3>
                                <p>Update profile and business information</p>
                            </a>
                        </div>
                    </div>
                    
                    <div class="access-level">
                        <p><strong>Access Level:</strong> Full access to all system modules — Supplier, Inventory, POS, <?php echo Language::get('customer_management', $lang); ?>, and Reporting & Analytics</p>
                    </div>
                </div>
            <?php elseif ($role === 'Cashier'): ?>
                <div class="cashier-panel">
                    <h2>Sales Cashier</h2>
                    <p class="role-desc">Customer service and transaction processing</p>
                    <div class="menu-grid">
                        <a href="../pos/index.php?lang=<?php echo $lang; ?>" class="menu-item main-pos">
                            <h3>💰 <?php echo Language::get('point_of_sale', $lang); ?></h3>
                            <p>Process sales, accept payments, print receipts</p>
                        </a>
                        <a href="../customers/index.php?lang=<?php echo $lang; ?>" class="menu-item cashier-item">
                            <h3>👥 Customer Lookup</h3>
                            <p>Find customers, check loyalty points, basic info</p>
                        </a>
                        <a href="../inventory/stock-check.php?lang=<?php echo $lang; ?>" class="menu-item cashier-item">
                            <h3>📦 Stock Check</h3>
                            <p>Check product availability and prices</p>
                        </a>
                        <a href="../pos/returns.php?lang=<?php echo $lang; ?>" class="menu-item cashier-item">
                            <h3>🔄 <?php echo Language::get('returns_exchanges', $lang); ?></h3>
                            <p>Process returns, exchanges, refunds</p>
                        </a>
                        <a href="../shift/summary.php?lang=<?php echo $lang; ?>" class="menu-item cashier-item">
                            <h3>📊 My Shift Summary</h3>
                            <p>View daily sales, transactions, performance</p>
                        </a>
                        <a href="../profile/index.php?lang=<?php echo $lang; ?>" class="menu-item cashier-item">
                            <h3>👤 My Profile</h3>
                            <p>Update personal information and settings</p>
                        </a>
                    </div>
                </div>
            <?php elseif ($role === 'Customer'): ?>
                <div class="customer-panel">
                    <h2>Customer Portal</h2>
                    <p class="role-desc">Purchases goods from the shop and may use credit or loyalty features</p>
                    
                    <div class="workflow-section">
                        <h3>🛍️ My Shopping Experience</h3>
                        <div class="menu-grid">
                            <a href="../customer/orders.php?lang=<?php echo $lang; ?>" class="menu-item main-pos">
                                <h3>📦 My Purchase History</h3>
                                <p>View products bought (sugar, soap, etc.) and digital receipts</p>
                            </a>
                            <a href="../customer/loyalty.php?lang=<?php echo $lang; ?>" class="menu-item cashier-item">
                                <h3>🎆 Loyalty Points</h3>
                                <p>Track rewards earned from purchases and redeem benefits</p>
                            </a>
                            <a href="../customer/credit.php?lang=<?php echo $lang; ?>" class="menu-item cashier-item">
                                <h3>💳 My Credit Balance</h3>
                                <p>View credit purchases and payment reminders</p>
                            </a>
                        </div>
                    </div>
                    
                    <div class="workflow-section">
                        <h3>💰 Payment Methods Used</h3>
                        <div class="payment-info">
                            <div class="payment-method">
                                <h4>💵 Cash Payments</h4>
                                <p>Direct cash transactions at the shop</p>
                            </div>
                            <div class="payment-method">
                                <h4>📱 Mobile Money</h4>
                                <p>Digital payments through mobile money services</p>
                            </div>
                            <div class="payment-method">
                                <h4>💳 Credit Purchases</h4>
                                <p>Buy now, pay later with payment reminders</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="workflow-section">
                        <h3>👤 Account Management</h3>
                        <div class="menu-grid">
                            <a href="../profile/index.php?lang=<?php echo $lang; ?>" class="menu-item cashier-item">
                                <h3>👤 My Profile</h3>
                                <p>Update personal information and contact details</p>
                            </a>
                        </div>
                    </div>
                    
                    <div class="access-level">
                        <p><strong>Access Level:</strong> Personal account access only - all transactions are recorded by Cashiers or Shop Owners</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <?php include __DIR__ . '/../../includes/footer.php'; ?>
    </div>

    <script>
        function changeLanguage(lang) {
            window.location.href = '?lang=' + lang;
        }
    </script>
</body>
</html>