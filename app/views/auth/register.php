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
    <title><?php echo Language::get('register', $lang); ?> - <?php echo Language::get('smartshop', $lang); ?></title>
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
                        <p><?php echo Language::get('register', $lang); ?></p>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert <?php echo strpos($message, 'successful') ? 'alert-success' : 'alert-error'; ?>">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label"><?php echo Language::get('full_name', $lang); ?></label>
                            <input type="text" name="full_name" class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label"><?php echo Language::get('email', $lang); ?></label>
                            <input type="email" name="email" class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label"><?php echo Language::get('phone_number', $lang); ?></label>
                            <input type="tel" name="phone_number" class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label"><?php echo Language::get('role', $lang); ?></label>
                            <select name="role" class="form-input" required>
                                <option value="System Admin">System Admin</option>
                                <option value="Owner">Owner</option>
                                <option value="Manager"><?php echo Language::get('manager', $lang); ?></option>
                                <option value="Customer"><?php echo Language::get('customer', $lang); ?></option>
                                <option value="Cashier"><?php echo Language::get('cashier', $lang); ?></option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label"><?php echo Language::get('password', $lang); ?></label>
                            <input type="password" name="password" class="form-input" required>
                        </div>

                        <button type="submit" class="btn"><?php echo Language::get('register', $lang); ?></button>
                    </form>

                    <div class="auth-links">
                        <a href="login.php?lang=<?php echo $lang; ?>"><?php echo Language::get('login', $lang); ?></a>
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