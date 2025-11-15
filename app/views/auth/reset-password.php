<?php
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../helpers/Language.php';

$lang = $_GET['lang'] ?? 'en';
$token = $_GET['token'] ?? '';
$auth = new AuthController();
$message = '';
$messageType = 'error';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if ($newPassword === $confirmPassword) {
        $result = $auth->resetPassword($token, $newPassword);
        if ($result['success']) {
            $message = $result['message'];
            $messageType = 'success';
        } else {
            $message = $result['message'];
        }
    } else {
        $message = "Passwords do not match!";
    }
}

if (!$token) {
    $message = "Invalid reset link";
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo Language::get('reset_password', $lang); ?> - <?php echo Language::get('smartshop', $lang); ?></title>
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
            <p><?php echo Language::get('reset_password', $lang); ?></p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            
            <div class="form-group">
                <label><?php echo Language::get('new_password', $lang); ?></label>
                <input type="password" name="new_password" required>
            </div>
            
            <div class="form-group">
                <label><?php echo Language::get('confirm_password', $lang); ?></label>
                <input type="password" name="confirm_password" required>
            </div>

            <button type="submit" class="btn"><?php echo Language::get('update_password', $lang); ?></button>
        </form>

        <div class="auth-links">
            <a href="login.php?lang=<?php echo $lang; ?>"><?php echo Language::get('back_to_login', $lang); ?></a>
        </div>
    </div>

    <script>
        function changeLanguage(lang) {
            window.location.href = '?lang=' + lang + '&token=<?php echo $token; ?>';
        }
    </script>
    
    <?php include __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>