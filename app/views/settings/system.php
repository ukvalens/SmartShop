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

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'shop_name' => $_POST['shop_name'],
        'shop_address' => $_POST['shop_address'],
        'shop_phone' => $_POST['shop_phone'],
        'shop_email' => $_POST['shop_email'],
        'currency' => $_POST['currency'],
        'tax_rate' => $_POST['tax_rate']
    ];
    
    // Save settings to file or database
    file_put_contents(__DIR__ . '/../../../config/settings.json', json_encode($settings));
    $message = 'Settings updated successfully!';
}

// Load current settings
$settings_file = __DIR__ . '/../../../config/settings.json';
$settings = file_exists($settings_file) ? json_decode(file_get_contents($settings_file), true) : [
    'shop_name' => 'SmartSHOP',
    'shop_address' => 'Kigali, Rwanda',
    'shop_phone' => '+250 XXX XXX XXX',
    'shop_email' => 'info@smartshop.rw',
    'currency' => 'RWF',
    'tax_rate' => '18'
];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo Language::getText('system_settings', $lang); ?> - <?php echo Language::getText('smartshop', $lang); ?></title>
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
            <h1>⚙️ <?php echo Language::getText('system_settings', $lang); ?></h1>
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
            <?php if ($message): ?>
                <div class="alert <?php echo strpos($message, 'success') ? 'alert-success' : 'alert-error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="settings-sections">
                <div class="settings-section">
                    <h2>🏪 <?php echo Language::getText('shop_information', $lang); ?></h2>
                    <form method="POST" class="settings-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label"><?php echo Language::getText('shop_name', $lang); ?></label>
                                <input type="text" name="shop_name" value="<?php echo htmlspecialchars($settings['shop_name']); ?>" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?php echo Language::getText('phone_number', $lang); ?></label>
                                <input type="text" name="shop_phone" value="<?php echo htmlspecialchars($settings['shop_phone']); ?>" class="form-input" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"><?php echo Language::getText('address', $lang); ?></label>
                            <textarea name="shop_address" class="form-input" rows="3" required><?php echo htmlspecialchars($settings['shop_address']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"><?php echo Language::getText('email', $lang); ?></label>
                            <input type="email" name="shop_email" value="<?php echo htmlspecialchars($settings['shop_email']); ?>" class="form-input" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label"><?php echo Language::getText('currency', $lang); ?></label>
                                <select name="currency" class="form-input" required>
                                    <option value="RWF" <?php echo $settings['currency'] === 'RWF' ? 'selected' : ''; ?>>Rwandan Franc (RWF)</option>
                                    <option value="USD" <?php echo $settings['currency'] === 'USD' ? 'selected' : ''; ?>>US Dollar (USD)</option>
                                    <option value="EUR" <?php echo $settings['currency'] === 'EUR' ? 'selected' : ''; ?>>Euro (EUR)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?php echo Language::getText('tax_rate', $lang); ?> (%)</label>
                                <input type="number" name="tax_rate" value="<?php echo $settings['tax_rate']; ?>" class="form-input" step="0.01" min="0" max="100" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn"><?php echo Language::getText('update_settings', $lang); ?></button>
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
        
        .settings-sections {
            display: grid;
            gap: 2rem;
        }
        
        .settings-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .settings-section h2 {
            margin: 0 0 1.5rem 0;
            color: var(--primary);
            border-bottom: 2px solid var(--accent);
            padding-bottom: 0.5rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
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
            .form-row {
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