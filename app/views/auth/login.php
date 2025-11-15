<?php
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../helpers/Language.php';

$lang = $_GET['lang'] ?? 'en';
$auth = new AuthController();

if ($auth->isLoggedIn()) {
    header('Location: ../dashboard/index.php?lang=' . $lang);
    exit;
}

$message = '';
if (isset($_GET['registered'])) {
    $message = 'Registration successful! Please login.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $result = $auth->login($email, $password);
    
    if ($result['success']) {
        header('Location: ../dashboard/index.php?lang=' . $lang);
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
    <title><?php echo Language::get('login', $lang); ?> - SmartShop POS</title>
    <link rel="stylesheet" href="../../../public/css/main.css">
    <link rel="stylesheet" href="../../../public/css/auth.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="language-selector">
            <select onchange="changeLanguage(this.value)">
                <option value="en" <?php echo $lang === 'en' ? 'selected' : ''; ?>>🇺🇸 English</option>
                <option value="rw" <?php echo $lang === 'rw' ? 'selected' : ''; ?>>🇷🇼 Kinyarwanda</option>
            </select>
        </div>

        <div class="auth-layout">
            <div class="auth-hero">
                <div class="hero-content">
                    <div class="brand">
                        <i class="fas fa-shopping-cart"></i>
                        <h1>SmartShop</h1>
                    </div>
                    <h2>Welcome Back!</h2>
                    <p>Access your SmartShop dashboard to manage sales, inventory, customers, and business analytics. Your complete retail management solution awaits.</p>
                    <div class="features">
                        <div class="feature">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard Analytics</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-box"></i>
                            <span>Inventory Management</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-receipt"></i>
                            <span>Sales Reports</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="auth-form-section">
                <div class="auth-card">
                    <div class="auth-header">
                        <h3><i class="fas fa-sign-in-alt"></i> Sign In</h3>
                        <p>Enter your credentials to access your account</p>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert <?php echo isset($_GET['registered']) ? 'alert-success' : 'alert-error'; ?>">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="auth-form">
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-envelope"></i> <?php echo Language::get('email', $lang); ?></label>
                            <input type="email" name="email" class="form-input" placeholder="Enter your email" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-lock"></i> <?php echo Language::get('password', $lang); ?></label>
                            <input type="password" name="password" class="form-input" placeholder="Enter your password" required>
                        </div>

                        <button type="submit" class="btn-primary">
                            <i class="fas fa-sign-in-alt"></i>
                            <?php echo Language::get('login', $lang); ?>
                        </button>
                    </form>

                    <div class="auth-footer">
                        <p>Don't have an account? <a href="register.php?lang=<?php echo $lang; ?>">Create one here</a></p>
                        <p><a href="forgot-password.php?lang=<?php echo $lang; ?>">Forgot your password?</a></p>
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