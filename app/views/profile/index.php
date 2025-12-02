<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../helpers/Language.php';
require_once __DIR__ . '/../../helpers/Navigation.php';

$auth = new AuthController();
if (!$auth->isLoggedIn()) {
    header('Location: ../auth/login.php?lang=' . ($_GET['lang'] ?? 'en'));
    exit;
}

$user = $auth->getCurrentUser();
$lang = $_GET['lang'] ?? $_SESSION['language'] ?? 'en';
$_SESSION['language'] = $lang;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
    $uploadDir = __DIR__ . '/../../../uploads/profiles/';
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $fileName = $user['user_id'] . '.jpg';
    $uploadFile = $uploadDir . $fileName;
    
    // Validate image
    if (isset($_FILES['profile_image']['tmp_name']) && $_FILES['profile_image']['tmp_name']) {
        $imageInfo = getimagesize($_FILES['profile_image']['tmp_name']);
        if ($imageInfo && in_array($imageInfo['mime'], ['image/jpeg', 'image/png', 'image/gif'])) {
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadFile)) {
                chmod($uploadFile, 0644);
                $message = 'Profile image updated successfully!';
            } else {
                $message = 'Failed to upload image.';
            }
        } else {
            $message = 'Please upload a valid image file (JPG, PNG, GIF).';
        }
    } else {
        $message = 'No image file selected.';
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo Language::getText('profile', $lang); ?> - <?php echo Language::getText('smartshop', $lang); ?></title>
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
            <h1>👤 <?php echo Language::getText('profile', $lang); ?></h1>
            <div class="user-info">
                <div class="user-profile">
                    <img src="../../../uploads/profiles/<?php echo $user['user_id']; ?>.jpg?v=<?php echo time(); ?>" alt="Profile" class="profile-img" onerror="this.src='../../../uploads/profiles/default.jpg'" style="object-fit: cover;">
                    <div class="user-details">
                        <span class="user-name"><?php echo $user['full_name']; ?></span>
                        <span class="user-role"><?php echo Language::getText(strtolower($user['role']), $lang); ?></span>
                    </div>
                    <div class="user-menu">
                        <a href="../dashboard/index.php?lang=<?php echo $lang; ?>" class="profile-link"><?php echo Language::getText('dashboard', $lang); ?></a>
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
            
            <div class="profile-section">
                <h2>👤 <?php echo Language::getText('profile_information', $lang); ?></h2>
                
                <div class="profile-form">
                    <div class="profile-image-section">
                        <img src="../../../uploads/profiles/<?php echo $user['user_id']; ?>.jpg?v=<?php echo time(); ?>" 
                             alt="Profile" class="profile-img-large" 
                             onerror="this.src='../../../uploads/profiles/default.jpg'">
                        
                        <form method="POST" enctype="multipart/form-data" class="image-upload-form">
                            <input type="file" name="profile_image" accept="image/*" class="form-input" required>
                            <button type="submit" class="btn">📷 <?php echo Language::getText('update_image', $lang); ?></button>
                        </form>
                    </div>
                    
                    <div class="profile-details">
                        <div class="detail-item">
                            <label><?php echo Language::getText('full_name', $lang); ?>:</label>
                            <span><?php echo htmlspecialchars($user['full_name']); ?></span>
                        </div>
                        <div class="detail-item">
                            <label><?php echo Language::getText('email', $lang); ?>:</label>
                            <span><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <div class="detail-item">
                            <label><?php echo Language::getText('role', $lang); ?>:</label>
                            <span class="role-badge"><?php echo $user['role']; ?></span>
                        </div>
                        <?php if ($user['role'] === 'Owner'): ?>
                        <div class="detail-item">
                            <label><?php echo Language::getText('access_level', $lang); ?>:</label>
                            <span>Business Management - Full Access</span>
                        </div>
                        <div class="detail-item">
                            <label><?php echo Language::getText('permissions', $lang); ?>:</label>
                            <span>Inventory, POS, Customers, Reports, Suppliers</span>
                        </div>
                        <?php elseif ($user['role'] === 'Admin'): ?>
                        <div class="detail-item">
                            <label><?php echo Language::getText('access_level', $lang); ?>:</label>
                            <span>Business Administration - Extended Access</span>
                        </div>
                        <div class="detail-item">
                            <label><?php echo Language::getText('permissions', $lang); ?>:</label>
                            <span>POS, Inventory, Customers, Reports, User Management</span>
                        </div>
                        <?php elseif ($user['role'] === 'System Admin'): ?>
                        <div class="detail-item">
                            <label><?php echo Language::getText('access_level', $lang); ?>:</label>
                            <span>System Administration - Full Control</span>
                        </div>
                        <div class="detail-item">
                            <label><?php echo Language::getText('permissions', $lang); ?>:</label>
                            <span>All System Functions, User Management, Security, Backup</span>
                        </div>
                        <?php elseif ($user['role'] === 'Cashier'): ?>
                        <div class="detail-item">
                            <label><?php echo Language::getText('access_level', $lang); ?>:</label>
                            <span>Sales Operations - Limited Access</span>
                        </div>
                        <div class="detail-item">
                            <label><?php echo Language::getText('permissions', $lang); ?>:</label>
                            <span>POS, Customer Lookup, Stock Check, Returns</span>
                        </div>
                        <?php elseif ($user['role'] === 'Customer'): ?>
                        <div class="detail-item">
                            <label><?php echo Language::getText('access_level', $lang); ?>:</label>
                            <span>Personal Account - View Only</span>
                        </div>
                        <div class="detail-item">
                            <label><?php echo Language::getText('permissions', $lang); ?>:</label>
                            <span>Order History, Loyalty Points, Credit Balance</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
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
    .profile-section {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .profile-form {
        display: grid;
        grid-template-columns: auto 1fr;
        gap: 2rem;
        align-items: start;
    }
    
    .profile-image-section {
        text-align: center;
    }
    
    .profile-img-large {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid var(--primary);
        margin-bottom: 1rem;
    }
    
    .image-upload-form {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .image-upload-form .form-input {
        margin-bottom: 1rem;
    }
    
    .profile-details {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    .detail-item {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
        border-bottom: 1px solid #eee;
    }
    
    .detail-item label {
        font-weight: bold;
        color: var(--primary);
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
    
    .role-badge {
        background: var(--accent);
        color: white;
        padding: 0.2rem 0.5rem;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: 500;
    }
    </style>

    <script>
        function changeLanguage(lang) {
            window.location.href = '?lang=' + lang;
        }
    </script>
</body>
</html>