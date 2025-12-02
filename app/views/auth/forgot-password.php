<?php
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../helpers/Language.php';
require_once __DIR__ . '/../../helpers/EmailHelper.php';

$lang = $_GET['lang'] ?? 'en';
$auth = new AuthController();
$emailHelper = new EmailHelper();
$message = '';
$messageType = 'error';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $result = $auth->generateResetToken($email);
    
    if ($result['success']) {
        $resetLink = "http://localhost/SMESHOP/app/views/auth/reset-password.php?token=" . $result['token'];
        
        // Send email using EmailHelper
        $emailResult = $emailHelper->sendPasswordReset($email, $resetLink);
        
        if ($emailResult['success']) {
            $message = "Password reset email sent to your inbox.";
            $messageType = 'success';
        } else {
            $message = "Email could not be sent. Reset link: " . $resetLink;
            $messageType = 'success';
        }
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
    <title><?php echo Language::getText('forgot_password', $lang); ?> - SmartShop POS</title>
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
                    <h2>Reset Your Password</h2>
                    <p>Don't worry! It happens to the best of us. Enter your email address and we'll send you a link to reset your password and get back to managing your business.</p>
                    <div class="features">
                        <div class="feature">
                            <i class="fas fa-shield-alt"></i>
                            <span>Secure Process</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-clock"></i>
                            <span>Quick Recovery</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-envelope"></i>
                            <span>Email Verification</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="auth-form-section">
                <div class="auth-card">
                    <div class="auth-header">
                        <h3><i class="fas fa-key"></i> Password Recovery</h3>
                        <p>Enter your email to receive a password reset link</p>
                    </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

                    <form method="POST" class="auth-form">
                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-envelope"></i> <?php echo Language::getText('email', $lang); ?></label>
                            <input type="email" name="email" class="form-input" placeholder="Enter your registered email" required>
                        </div>

                        <button type="submit" class="btn-primary">
                            <i class="fas fa-paper-plane"></i>
                            Send Reset Link
                        </button>
                    </form>

                    <div class="auth-footer">
                        <p>Remember your password? <a href="login.php?lang=<?php echo $lang; ?>">Sign in here</a></p>
                        <p>Need an account? <a href="register.php?lang=<?php echo $lang; ?>">Create one here</a></p>
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
    
    <?php include __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>