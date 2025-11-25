<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../helpers/Language.php';
require_once __DIR__ . '/../../helpers/Navigation.php';
$auth = new AuthController();
if (!$auth->isLoggedIn() || !in_array($auth->getCurrentUser()['role'], ['Admin', 'System Admin'])) {
    header('Location: ../auth/login.php?lang=' . ($_GET['lang'] ?? 'en'));
    exit;
}

$user = $auth->getCurrentUser();
$lang = $_GET['lang'] ?? $_SESSION['language'] ?? 'en';
$_SESSION['language'] = $lang;
$message = '';

// Database connection
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'smartshop';
$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle backup creation
if (isset($_GET['create_backup'])) {
    $backup_dir = __DIR__ . '/../../../backups/';
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }
    
    $filename = 'smartshop_backup_' . date('Y-m-d_H-i-s') . '.sql';
    $filepath = $backup_dir . $filename;
    
    try {
        // Create real database backup
        $backup_content = "-- SmartSHOP Database Backup\n";
        $backup_content .= "-- Created: " . date('Y-m-d H:i:s') . "\n";
        $backup_content .= "-- Database: smartshop\n\n";
        $backup_content .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        
        // Get all tables
        $tables = $conn->query("SHOW TABLES");
        while ($table = $tables->fetch_array()) {
            $table_name = $table[0];
            
            // Get table structure
            $create_table = $conn->query("SHOW CREATE TABLE `$table_name`")->fetch_array();
            $backup_content .= "DROP TABLE IF EXISTS `$table_name`;\n";
            $backup_content .= $create_table[1] . ";\n\n";
            
            // Get table data
            $rows = $conn->query("SELECT * FROM `$table_name`");
            if ($rows->num_rows > 0) {
                while ($row = $rows->fetch_assoc()) {
                    $backup_content .= "INSERT INTO `$table_name` VALUES (";
                    $values = [];
                    foreach ($row as $value) {
                        $values[] = $value === null ? 'NULL' : "'" . $conn->real_escape_string($value) . "'";
                    }
                    $backup_content .= implode(', ', $values) . ");\n";
                }
                $backup_content .= "\n";
            }
        }
        
        $backup_content .= "SET FOREIGN_KEY_CHECKS=1;\n";
        
        if (file_put_contents($filepath, $backup_content)) {
            $message = "Database backup created successfully: $filename";
        } else {
            $message = 'Failed to create backup file.';
        }
    } catch (Exception $e) {
        $message = 'Backup failed: ' . $e->getMessage();
    }
}

// Handle backup restore
if (isset($_POST['restore_backup']) && isset($_POST['backup_file'])) {
    $backup_file = $_POST['backup_file'];
    $backup_path = __DIR__ . '/../../../backups/' . $backup_file;
    
    if (file_exists($backup_path)) {
        try {
            $sql = file_get_contents($backup_path);
            $conn->multi_query($sql);
            
            // Process all results
            do {
                if ($result = $conn->store_result()) {
                    $result->free();
                }
            } while ($conn->next_result());
            
            $message = "Database restored successfully from: $backup_file";
        } catch (Exception $e) {
            $message = 'Restore failed: ' . $e->getMessage();
        }
    } else {
        $message = 'Backup file not found.';
    }
}

// Handle backup deletion
if (isset($_GET['delete_backup'])) {
    $backup_file = $_GET['delete_backup'];
    $backup_path = __DIR__ . '/../../../backups/' . $backup_file;
    
    if (file_exists($backup_path) && unlink($backup_path)) {
        $message = "Backup deleted: $backup_file";
    } else {
        $message = 'Failed to delete backup.';
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
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo Language::get('data_backup', $lang); ?> - <?php echo Language::get('smartshop', $lang); ?></title>
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
            <h1>💾 <?php echo Language::get('data_backup', $lang); ?></h1>
            <div class="user-info">
                <div class="user-profile">
                    <img src="../../../uploads/profiles/<?php echo $user['user_id']; ?>.jpg?v=<?php echo time(); ?>" alt="Profile" class="profile-img" onerror="this.src='../../../uploads/profiles/default.jpg'" style="object-fit: cover;">
                    <div class="user-details">
                        <span class="user-name"><?php echo $user['full_name']; ?></span>
                        <span class="user-role"><?php echo Language::get(strtolower($user['role']), $lang); ?></span>
                    </div>
                    <div class="user-menu">
                        <a href="../profile/index.php?lang=<?php echo $lang; ?>" class="profile-link"><?php echo Language::get('profile', $lang); ?></a>
                        <a href="../../controllers/logout.php" class="btn-logout"><?php echo Language::get('logout', $lang); ?></a>
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

            <div class="backup-sections">
                <div class="backup-section">
                    <h2>🔄 <?php echo Language::get('backup_operations', $lang); ?></h2>
                    <div class="backup-controls">
                        <div class="control-item">
                            <h3>📦 <?php echo Language::get('create_backup', $lang); ?></h3>
                            <p><?php echo Language::get('backup_description', $lang); ?></p>
                            <a href="?create_backup=1&lang=<?php echo $lang; ?>" class="btn" onclick="return confirm('<?php echo Language::get('confirm_backup', $lang); ?>')">
                                💾 <?php echo Language::get('create_new_backup', $lang); ?>
                            </a>
                        </div>
                        
                        <div class="control-item">
                            <h3>🔄 <?php echo Language::get('restore_backup', $lang); ?></h3>
                            <p><?php echo Language::get('restore_description', $lang); ?></p>
                            <form method="POST" class="restore-form">
                                <select name="backup_file" class="form-input" required>
                                    <option value=""><?php echo Language::get('select_backup', $lang); ?></option>
                                    <?php foreach ($backups as $backup): ?>
                                        <option value="<?php echo $backup['name']; ?>"><?php echo $backup['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="restore_backup" class="btn btn-warning" onclick="return confirm('<?php echo Language::get('confirm_restore', $lang); ?>')">
                                    🔄 <?php echo Language::get('restore_database', $lang); ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="backup-section">
                    <h2>📋 <?php echo Language::get('backup_history', $lang); ?></h2>
                    <div class="backups-table">
                        <table>
                            <thead>
                                <tr>
                                    <th><?php echo Language::get('filename', $lang); ?></th>
                                    <th><?php echo Language::get('size', $lang); ?></th>
                                    <th><?php echo Language::get('created', $lang); ?></th>
                                    <th><?php echo Language::get('actions', $lang); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($backups as $backup): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $backup['name']; ?></strong>
                                            <br><small><?php echo date('l, F j, Y', $backup['date']); ?></small>
                                        </td>
                                        <td><?php echo number_format($backup['size'] / 1024, 2); ?> KB</td>
                                        <td><?php echo date('M d, Y H:i', $backup['date']); ?></td>
                                        <td>
                                            <a href="../../../backups/<?php echo $backup['name']; ?>" class="btn-small btn-primary" download>
                                                📥 <?php echo Language::get('download', $lang); ?>
                                            </a>
                                            <a href="?delete_backup=<?php echo urlencode($backup['name']); ?>&lang=<?php echo $lang; ?>" 
                                               class="btn-small btn-danger" 
                                               onclick="return confirm('<?php echo Language::get('confirm_delete', $lang); ?>')">
                                                🗑️ <?php echo Language::get('delete', $lang); ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($backups)): ?>
                                    <tr>
                                        <td colspan="4" style="text-align: center; color: #666; padding: 2rem;">
                                            📂 <?php echo Language::get('no_backups_found', $lang); ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
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
        
        .backup-sections {
            display: grid;
            gap: 2rem;
        }
        
        .backup-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .backup-section h2 {
            margin: 0 0 1.5rem 0;
            color: var(--primary);
            border-bottom: 2px solid var(--accent);
            padding-bottom: 0.5rem;
        }
        
        .backup-controls {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .control-item {
            padding: 1.5rem;
            border: 2px solid #f0f0f0;
            border-radius: 8px;
            text-align: center;
        }
        
        .control-item h3 {
            margin: 0 0 1rem 0;
            color: var(--primary);
        }
        
        .control-item p {
            color: #666;
            margin-bottom: 1.5rem;
        }
        
        .restore-form {
            display: flex;
            gap: 1rem;
            align-items: end;
        }
        
        .restore-form select {
            flex: 1;
        }
        
        .backups-table table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .backups-table th,
        .backups-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .backups-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        
        .backups-table tr:hover {
            background: #f8f9fa;
        }
        
        .btn-small {
            padding: 0.4rem 0.8rem;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.85rem;
            margin: 0 0.2rem;
            display: inline-block;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
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
        
        @media (max-width: 768px) {
            .backup-controls {
                grid-template-columns: 1fr;
            }
            
            .restore-form {
                flex-direction: column;
            }
        }
    </style>

    <script>
        function changeLanguage(lang) {
            window.location.href = '?lang=' + lang;
        }
    </script>
</body>
</html>