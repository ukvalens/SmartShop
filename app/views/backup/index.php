<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../helpers/Navigation.php';

$auth = new AuthController();
if (!$auth->isLoggedIn() || !in_array($auth->getCurrentUser()['role'], ['Admin', 'System Admin'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user = $auth->getCurrentUser();
$lang = $_GET['lang'] ?? 'en';
$message = '';

// Handle backup creation
if (isset($_GET['create_backup'])) {
    $backup_dir = __DIR__ . '/../../../backups/';
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }
    
    $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    $filepath = $backup_dir . $filename;
    
    // Simple backup simulation
    $backup_content = "-- SmartSHOP Database Backup\n-- Created: " . date('Y-m-d H:i:s') . "\n\n";
    $backup_content .= "-- This is a simulated backup file\n";
    $backup_content .= "-- In production, this would contain actual database dump\n";
    
    if (file_put_contents($filepath, $backup_content)) {
        $message = "Backup created successfully: $filename";
    } else {
        $message = 'Failed to create backup.';
    }
}

// Get existing backups
$backup_dir = __DIR__ . '/../../../backups/';
$backups = [];
if (is_dir($backup_dir)) {
    $files = scandir($backup_dir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $backups[] = [
                'name' => $file,
                'size' => filesize($backup_dir . $file),
                'date' => filemtime($backup_dir . $file)
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Data Backup - SmartSHOP</title>
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
    <div class="dashboard-container">
        <?php Navigation::renderNav($user['role'], $lang); ?>
        
        <header class="header">
            <h1>💾 Data Backup</h1>
        </header>

        <div class="content">
            <?php if ($message): ?>
                <div class="alert <?php echo strpos($message, 'success') ? 'alert-success' : 'alert-error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="backup-controls">
                <a href="?create_backup=1&lang=<?php echo $lang; ?>" class="btn btn-primary" onclick="return confirm('Create database backup?')">Create New Backup</a>
                <p>Regular backups help protect your data. Create backups before major updates.</p>
            </div>

            <div class="backups-table">
                <h2>Existing Backups</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Filename</th>
                            <th>Size</th>
                            <th>Created</th>
                            <th><?php echo Language::get('actions', $lang); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($backups as $backup): ?>
                            <tr>
                                <td><?php echo $backup['name']; ?></td>
                                <td><?php echo number_format($backup['size'] / 1024, 2); ?> KB</td>
                                <td><?php echo date('M d, Y H:i', $backup['date']); ?></td>
                                <td>
                                    <a href="../../../backups/<?php echo $backup['name']; ?>" class="btn-small" download>Download</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($backups)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: #666;">No backups found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
    .backup-controls {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
        text-align: center;
    }
    
    .backups-table {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .backups-table table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
    }
    
    .backups-table th, .backups-table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    
    .backups-table th {
        background: var(--background);
        font-weight: 600;
    }
    
    .btn-small {
        background: var(--primary);
        color: white;
        padding: 0.3rem 0.6rem;
        border-radius: 3px;
        text-decoration: none;
        font-size: 0.8rem;
    }
    
    .alert-success {
        background: #d4edda;
        color: #155724;
        padding: 1rem;
        border-radius: 5px;
        margin-bottom: 1rem;
    }
    
    .alert-error {
        background: #f8d7da;
        color: #721c24;
        padding: 1rem;
        border-radius: 5px;
        margin-bottom: 1rem;
    }
    </style>
</body>
</html>