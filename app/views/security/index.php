<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../helpers/Navigation.php';
require_once __DIR__ . '/../../../config/database.php';

$auth = new AuthController();
if (!$auth->isLoggedIn() || !in_array($auth->getCurrentUser()['role'], ['Admin', 'System Admin'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user = $auth->getCurrentUser();
$lang = $_GET['lang'] ?? 'en';
$db = new Database();
$conn = $db->getConnection();

// Get login attempts (simulated data)
$login_logs = [
    ['user' => 'admin@smartshop.rw', 'ip' => '192.168.1.100', 'status' => 'Success', 'time' => date('Y-m-d H:i:s', strtotime('-1 hour'))],
    ['user' => 'owner@smartshop.rw', 'ip' => '192.168.1.101', 'status' => 'Success', 'time' => date('Y-m-d H:i:s', strtotime('-2 hours'))],
    ['user' => 'unknown@test.com', 'ip' => '192.168.1.200', 'status' => 'Failed', 'time' => date('Y-m-d H:i:s', strtotime('-3 hours'))],
];

// Get active sessions
$active_users = $conn->query("SELECT full_name, email, role FROM users WHERE user_id IN (SELECT DISTINCT user_id FROM sales WHERE DATE(sale_date) = CURDATE()) LIMIT 10");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo Language::get('security', $lang); ?> - SmartSHOP</title>
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
            <h1>🔒 <?php echo Language::get('security', $lang); ?></h1>
        </header>

        <div class="content">
            <div class="security-grid">
                <div class="security-section">
                    <h2>Login Activity</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>IP Address</th>
                                <th><?php echo Language::get('status', $lang); ?></th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($login_logs as $log): ?>
                                <tr>
                                    <td><?php echo $log['user']; ?></td>
                                    <td><?php echo $log['ip']; ?></td>
                                    <td>
                                        <span class="status-<?php echo strtolower($log['status']); ?>">
                                            <?php echo $log['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $log['time']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="security-section">
                    <h2><?php echo Language::get('active', $lang); ?> Users Today</h2>
                    <table>
                        <thead>
                            <tr>
                                <th><?php echo Language::get('name', $lang); ?></th>
                                <th><?php echo Language::get('email', $lang); ?></th>
                                <th>Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($active_user = $active_users->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $active_user['full_name']; ?></td>
                                    <td><?php echo $active_user['email']; ?></td>
                                    <td><?php echo $active_user['role']; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <div class="security-section">
                    <h2><?php echo Language::get('security', $lang); ?> Settings</h2>
                    <div class="security-controls">
                        <div class="control-item">
                            <label>Password Policy</label>
                            <p>Minimum 8 characters, mixed case required</p>
                        </div>
                        <div class="control-item">
                            <label>Session Timeout</label>
                            <p>Auto logout after 30 minutes of inactivity</p>
                        </div>
                        <div class="control-item">
                            <label>Failed Login Attempts</label>
                            <p>Account locked after 5 failed attempts</p>
                        </div>
                        <div class="control-item">
                            <label>Two-Factor Authentication</label>
                            <p>Available for Admin and System Admin roles</p>
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
    .security-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
    }
    
    .security-section {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .security-section:last-child {
        grid-column: 1 / -1;
    }
    
    .security-section table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
    }
    
    .security-section th, .security-section td {
        padding: 0.75rem;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    
    .security-section th {
        background: var(--background);
        font-weight: 600;
    }
    
    .status-success {
        color: green;
        font-weight: bold;
    }
    
    .status-failed {
        color: red;
        font-weight: bold;
    }
    
    .security-controls {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-top: 1rem;
    }
    
    .control-item {
        padding: 1rem;
        background: var(--background);
        border-radius: 5px;
    }
    
    .control-item label {
        font-weight: bold;
        color: var(--primary);
        display: block;
        margin-bottom: 0.5rem;
    }
    
    .control-item p {
        margin: 0;
        color: #666;
        font-size: 0.9rem;
    }
    </style>
</body>
</html>