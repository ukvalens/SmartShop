<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../helpers/Navigation.php';

$auth = new AuthController();
if (!$auth->isLoggedIn() || !in_array($auth->getCurrentUser()['role'], ['Admin', 'System Admin'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user = $auth->getCurrentUser();
$lang = $_GET['lang'] ?? 'en';
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
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo Language::get('system_settings', $lang); ?> - SmartSHOP</title>
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
            <h1>⚙️ <?php echo Language::get('system_settings', $lang); ?></h1>
        </header>

        <div class="content">
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="settings-form">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Shop Name</label>
                        <input type="text" name="shop_name" value="<?php echo $settings['shop_name']; ?>" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Shop Address</label>
                        <textarea name="shop_address" class="form-input" rows="3" required><?php echo $settings['shop_address']; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="shop_phone" value="<?php echo $settings['shop_phone']; ?>" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="shop_email" value="<?php echo $settings['shop_email']; ?>" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Currency</label>
                        <select name="currency" class="form-input" required>
                            <option value="RWF" <?php echo $settings['currency'] === 'RWF' ? 'selected' : ''; ?>>Rwandan Franc (RWF)</option>
                            <option value="USD" <?php echo $settings['currency'] === 'USD' ? 'selected' : ''; ?>>US Dollar (USD)</option>
                            <option value="EUR" <?php echo $settings['currency'] === 'EUR' ? 'selected' : ''; ?>>Euro (EUR)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Tax Rate (%)</label>
                        <input type="number" name="tax_rate" value="<?php echo $settings['tax_rate']; ?>" class="form-input" step="0.01" min="0" max="100" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Update Settings</button>
                </form>
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
    .settings-form {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        max-width: 600px;
        margin: 0 auto;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .alert-success {
        background: #d4edda;
        color: #155724;
        padding: 1rem;
        border-radius: 5px;
        margin-bottom: 1rem;
    }
    </style>
</body>
</html>