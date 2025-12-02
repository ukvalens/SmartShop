<?php
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../helpers/Language.php';

$lang = $_GET['lang'] ?? 'en';
$language = new Language($lang);
$auth = new AuthController();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = $_POST['full_name'];
    $email = $_POST['email'];
    $phoneNumber = $_POST['phone_number'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    $result = $auth->register($fullName, $email, $phoneNumber, $password, 'Pending');
    
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
    <title><?php echo $language->get('register'); ?> - SmartShop POS</title>
    <link rel="stylesheet" href="../../../public/css/main.css">
    <link rel="stylesheet" href="../../../public/css/auth.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="auth-container responsive-auth">
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
                    <h2><?php echo $language->get('modern_pos_system'); ?></h2>
                    <p><?php echo $language->get('pos_description'); ?></p>
                    <div class="features">
                        <div class="feature">
                            <i class="fas fa-cash-register"></i>
                            <span><?php echo $language->get('fast_sales'); ?></span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-chart-line"></i>
                            <span><?php echo $language->get('analytics'); ?></span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-users"></i>
                            <span><?php echo $language->get('multi_user'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="auth-form-section">
                <div class="auth-card">
                    <div class="auth-header">
                        <h3><i class="fas fa-user-plus"></i> <?php echo $language->get('create_account'); ?></h3>
                        <p><?php echo $language->get('join_smartshop'); ?></p>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert <?php echo strpos($message, 'successful') ? 'alert-success' : 'alert-error'; ?>">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="auth-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-user"></i> <?php echo $language->get('full_name'); ?></label>
                                <input type="text" name="full_name" class="form-input" placeholder="<?php echo $language->get('enter_full_name'); ?>" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-envelope"></i> <?php echo $language->get('email'); ?></label>
                                <input type="email" name="email" class="form-input" placeholder="<?php echo $language->get('enter_email'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-phone"></i> <?php echo $language->get('phone_number'); ?></label>
                                <input type="tel" name="phone_number" class="form-input" placeholder="<?php echo $language->get('enter_phone'); ?>" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-lock"></i> <?php echo $language->get('password'); ?></label>
                                <input type="password" name="password" class="form-input" placeholder="<?php echo $language->get('create_password'); ?>" required>
                            </div>
                        </div>
                        
                        <div class="role-notice">
                            <i class="fas fa-info-circle"></i>
                            <p>Your account role will be assigned by an administrator after registration.</p>
                        </div>

                        <button type="submit" class="btn-primary">
                            <i class="fas fa-user-plus"></i>
                            <?php echo $language->get('register'); ?>
                        </button>
                    </form>

                    <div class="auth-footer">
                        <p><?php echo $language->get('already_have_account'); ?> <a href="login.php?lang=<?php echo $lang; ?>"><?php echo $language->get('sign_in_here'); ?></a></p>
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
    
    <style>
        .role-notice {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 5px;
            padding: 1rem;
            margin: 1rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .role-notice i {
            color: #2196f3;
            font-size: 1.2rem;
        }
        
        .role-notice p {
            margin: 0;
            color: #1976d2;
            font-size: 0.9rem;
        }
        
        /* Responsive Design */
        @media (max-width: 1366px) {
            .auth-layout {
                grid-template-columns: 1fr 1fr;
                gap: 1rem;
                padding: 0.5rem;
                min-height: calc(100vh - 80px);
            }
            
            .auth-hero {
                padding: 1rem;
            }
            
            .hero-content h2 {
                font-size: 1.5rem;
            }
            
            .hero-content p {
                font-size: 0.9rem;
            }
            
            .features {
                gap: 0.5rem;
            }
            
            .feature {
                font-size: 0.8rem;
            }
            
            .auth-card {
                padding: 1rem;
            }
        }
        
        @media (max-width: 768px) {
            .auth-layout {
                grid-template-columns: 1fr;
            }
            
            .auth-hero {
                order: 2;
                padding: 0.5rem;
            }
            
            .auth-form-section {
                order: 1;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .auth-card {
                padding: 1rem;
                margin: 0.5rem;
            }
            
            .top-header {
                padding: 0.5rem 1rem;
            }
        }
    </style>
</body>
</html>