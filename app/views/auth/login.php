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
    <title><?php echo Language::get('login', $lang); ?> - <?php echo Language::get('smartshop', $lang); ?></title>
    <link rel="stylesheet" href="../../../public/css/main.css">
</head>
<body>
    <div class="language-selector">
        <select onchange="changeLanguage(this.value)">
            <option value="en" <?php echo $lang === 'en' ? 'selected' : ''; ?>>English</option>
            <option value="rw" <?php echo $lang === 'rw' ? 'selected' : ''; ?>>Kinyarwanda</option>
        </select>
    </div>

    <div class="page-container">
        <div class="content-wrapper">
            <div class="auth-page">
                <div class="auth-card">
                    <div class="auth-header">
                        <h1><?php echo Language::get('smartshop', $lang); ?></h1>
                        <p><?php echo Language::get('welcome', $lang); ?></p>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert <?php echo isset($_GET['registered']) ? 'alert-success' : 'alert-error'; ?>">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label"><?php echo Language::get('email', $lang); ?></label>
                            <input type="email" name="email" class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label"><?php echo Language::get('password', $lang); ?></label>
                            <input type="password" name="password" class="form-input" required>
                        </div>

                        <button type="submit" class="btn"><?php echo Language::get('login', $lang); ?></button>
                    </form>

                    <div class="auth-links">
                        <a href="register.php?lang=<?php echo $lang; ?>"><?php echo Language::get('register', $lang); ?></a> |
                        <a href="forgot-password.php?lang=<?php echo $lang; ?>"><?php echo Language::get('forgot_password', $lang); ?></a>
                    </div>
                </div>
            </div>
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