<?php
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../helpers/Language.php';

$lang = $_GET['lang'] ?? 'en';
$auth = new AuthController();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = $_POST['full_name'];
    $email = $_POST['email'];
    $phoneNumber = $_POST['phone_number'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    $result = $auth->register($fullName, $email, $phoneNumber, $password, $role);
    
    if ($result['success']) {
        header('Location: login.php?registered=1&lang=' . $lang);
        exit;
    } else {
        $message = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo Language::get('register', $lang); ?> - SmartShop POS</title>
    <link rel="stylesheet" href="../../../public/css/main.css">
    <link rel="stylesheet" href="../../../public/css/auth.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <header class="top-header">
            <div class="header-brand">
                <i class="fas fa-shopping-cart"></i>
                <span>SmartShop</span>
            </div>
            <div class="language-selector">
                <select onchange="changeLanguage(this.value)">
                    <option value="en" <?php echo $lang === 'en' ? 'selected' : ''; ?>>🇺🇸 English</option>
                    <option value="rw" <?php echo $lang === 'rw' ? 'selected' : ''; ?>>🇷🇼 Kinyarwanda</option>
                </select>
            </div>
        </header>

        <div class="auth-layout">
            <div class="auth-hero">
                <div class="hero-content">
                    <h2>Modern Point of Sale System</h2>
                    <p>Streamline your retail operations with our comprehensive POS solution.</p>
                    <div class="features">
                        <div class="feature">
                            <i class="fas fa-cash-register"></i>
                            <span>Fast Sales</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-chart-line"></i>
                            <span>Analytics</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-users"></i>
                            <span>Multi-user</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="auth-form-section">
                <div class="auth-card">
                    <div class="auth-header">
                        <h3><i class="fas fa-user-plus"></i> Create Account</h3>
                        <p>Join SmartShop and start managing your business efficiently</p>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert <?php echo strpos($message, 'successful') ? 'alert-success' : 'alert-error'; ?>">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="auth-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-user"></i> <?php echo Language::get('full_name', $lang); ?></label>
                                <input type="text" name="full_name" class="form-input" placeholder="Enter your full name" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-envelope"></i> <?php echo Language::get('email', $lang); ?></label>
                                <input type="email" name="email" class="form-input" placeholder="Enter your email" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-phone"></i> <?php echo Language::get('phone_number', $lang); ?></label>
                                <input type="tel" name="phone_number" class="form-input" placeholder="Enter phone number" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-user-tag"></i> <?php echo Language::get('role', $lang); ?></label>
                                <select name="role" class="form-input" required>
                                    <option value="">Select your role</option>
                                    <option value="System Admin">System Admin</option>
                                    <option value="Owner">Business Owner</option>
                                    <option value="Manager"><?php echo Language::get('manager', $lang); ?></option>
                                    <option value="Cashier"><?php echo Language::get('cashier', $lang); ?></option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-lock"></i> <?php echo Language::get('password', $lang); ?></label>
                                <input type="password" name="password" class="form-input" placeholder="Create password" required>
                            </div>
                        </div>

                        <button type="submit" class="btn-primary">
                            <i class="fas fa-user-plus"></i>
                            <?php echo Language::get('register', $lang); ?>
                        </button>
                    </form>

                    <div class="auth-footer">
                        <p>Already have an account? <a href="login.php?lang=<?php echo $lang; ?>">Sign in here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function changeLanguage(lang) {
            window.location.href = '?lang=' + lang;
        }
    </script>
</body>
</html>