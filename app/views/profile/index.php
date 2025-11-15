<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../helpers/Language.php';
require_once __DIR__ . '/../../helpers/Navigation.php';

$auth = new AuthController();
if (!$auth->isLoggedIn()) {
    header('Location: ../auth/login.php?lang=' . $lang);
    exit;
}

$user = $auth->getCurrentUser();
$lang = $_GET['lang'] ?? $_SESSION['language'] ?? 'en';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
    $uploadDir = __DIR__ . '/../../../uploads/profiles/';
    $fileName = $user['user_id'] . '.jpg';
    $uploadFile = $uploadDir . $fileName;
    
    // Validate image
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
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - SmartSHOP</title>
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

    <div class="dashboard">
        <?php Navigation::renderNav($user['role'], $lang); ?>
        
        <header class="header">
            <h1>User Profile</h1>
            <div class="user-info">
                <a href="../../controllers/logout.php" class="btn-logout"><?php echo Language::get('logout', $lang); ?></a>
            </div>
        </header>

        <div class="content">
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <div class="profile-section">
                <h2>Profile Information</h2>
                
                <div class="profile-form">
                    <div class="profile-image-section">
                        <img src="../../../uploads/profiles/<?php echo $user['user_id']; ?>.jpg?v=<?php echo time(); ?>" 
                             alt="Profile" class="profile-img-large" 
                             onerror="this.src='../../../uploads/profiles/default.jpg'">
                        
                        <form method="POST" enctype="multipart/form-data" class="image-upload-form">
                            <input type="file" name="profile_image" accept="image/*" class="form-input" required>
                            <button type="submit" class="btn">Update Profile Image</button>
                        </form>
                    </div>
                    
                    <div class="profile-details">
                        <div class="detail-item">
                            <label>Full Name:</label>
                            <span><?php echo $user['full_name']; ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Email:</label>
                            <span><?php echo $user['email']; ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Role:</label>
                            <span><?php echo $user['role']; ?></span>
                        </div>
                    </div>
                </div>
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
    
    .alert-success {
        background: #d4edda;
        color: #155724;
        padding: 1rem;
        border-radius: 5px;
        margin-bottom: 1rem;
    }
    </style>

    <script>
        function changeLanguage(lang) {
            window.location.href = '?lang=' + lang;
        }
    </script>
    
    <?php include __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>