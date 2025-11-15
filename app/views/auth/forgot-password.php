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
    <title><?php echo Language::get('forgot_password', $lang); ?> - <?php echo Language::get('smartshop', $lang); ?></title>
    <link rel="stylesheet" href="../../../public/css/auth.css">
</head>
<body>
    <div class="language-selector">
        <select onchange="changeLanguage(this.value)">
            <option value="en" <?php echo $lang === 'en' ? 'selected' : ''; ?>>English</option>
            <option value="rw" <?php echo $lang === 'rw' ? 'selected' : ''; ?>>Kinyarwanda</option>
        </select>
    </div>

    <div class="auth-container">
        <div class="logo">
            <h1><?php echo Language::get('smartshop', $lang); ?></h1>
            <p><?php echo Language::get('forgot_password', $lang); ?></p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label><?php echo Language::get('email', $lang); ?></label>
                <input type="email" name="email" required>
            </div>

            <button type="submit" class="btn"><?php echo Language::get('send_reset_link', $lang); ?></button>
        </form>

        <div class="auth-links">
            <a href="login.php?lang=<?php echo $lang; ?>"><?php echo Language::get('back_to_login', $lang); ?></a>
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