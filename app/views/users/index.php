<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../helpers/Navigation.php';
require_once __DIR__ . '/../../helpers/Language.php';
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
$message = '';

// Handle user creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $full_name, $email, $password, $role);
    
    if ($stmt->execute()) {
        $message = 'User created successfully!';
    } else {
        $message = 'Failed to create user.';
    }
}

// Handle role approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_role'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['new_role'];
    
    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE user_id = ?");
    $stmt->bind_param("si", $new_role, $user_id);
    
    if ($stmt->execute()) {
        $message = 'User role approved successfully!';
    } else {
        $message = 'Failed to approve user role.';
    }
}

// Handle user deletion
if (isset($_GET['delete']) && $_GET['delete'] != $user['user_id']) {
    $delete_id = $_GET['delete'];
    
    // Delete related records first to avoid foreign key constraint errors
    $conn->query("DELETE FROM returns WHERE sale_id IN (SELECT sale_id FROM sales WHERE user_id = $delete_id)");
    $conn->query("DELETE FROM sales WHERE user_id = $delete_id");
    $conn->query("DELETE FROM staff_schedules WHERE user_id = $delete_id");
    
    // Now delete the user
    if ($conn->query("DELETE FROM users WHERE user_id = $delete_id")) {
        $message = 'User deleted successfully!';
    } else {
        $message = 'Failed to delete user.';
    }
}

// Get all users
$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo Language::getText('user_management', $lang); ?> - SmartSHOP</title>
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
        
        <div class="language-selector">
            <select onchange="changeLanguage(this.value)">
                <option value="en" <?php echo $lang === 'en' ? 'selected' : ''; ?>>English</option>
                <option value="rw" <?php echo $lang === 'rw' ? 'selected' : ''; ?>>Kinyarwanda</option>
            </select>
        </div>
        
        <header class="header">
            <h1><?php echo Language::getText('smartshop', $lang); ?></h1>
            <div class="user-info">
                <div class="user-profile">
                    <img src="../../../uploads/profiles/<?php echo $user['user_id']; ?>.jpg?v=<?php echo time(); ?>" alt="Profile" class="profile-img" onerror="this.src='../../../uploads/profiles/default.jpg'" style="object-fit: cover;">
                    <div class="user-details">
                        <span class="user-name"><?php echo $user['full_name']; ?></span>
                        <span class="user-role"><?php echo Language::getText(strtolower($user['role']), $lang); ?></span>
                    </div>
                    <div class="user-menu">
                        <a href="../profile/index.php?lang=<?php echo $lang; ?>" class="profile-link"><?php echo Language::getText('profile', $lang); ?></a>
                        <a href="../../controllers/logout.php" class="btn-logout"><?php echo Language::getText('logout', $lang); ?></a>
                    </div>
                </div>
            </div>
        </header>

        <div class="content">
            <div class="page-header">
                <h2>👥 <?php echo Language::getText('user_management', $lang); ?></h2>
                <p>Manage system users and their roles</p>
            </div>
            
            <?php if ($message): ?>
                <div class="alert <?php echo strpos($message, 'success') ? 'alert-success' : 'alert-error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="user-controls">
                <button onclick="openCreateModal()" class="btn btn-primary">Create New User</button>
            </div>

            <div class="users-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th><?php echo Language::getText('name', $lang); ?></th>
                            <th><?php echo Language::getText('email', $lang); ?></th>
                            <th>Role</th>
                            <th>Created</th>
                            <th><?php echo Language::getText('actions', $lang); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($u = $users->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $u['user_id']; ?></td>
                                <td><?php echo $u['full_name']; ?></td>
                                <td><?php echo $u['email']; ?></td>
                                <td>
                                    <span class="role-badge role-<?php echo strtolower($u['role']); ?>"><?php echo $u['role']; ?></span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                                <td>
                                    <?php if ($u['role'] === 'Pending'): ?>
                                        <button onclick="openApproveModal(<?php echo $u['user_id']; ?>, '<?php echo addslashes($u['full_name']); ?>')" class="btn-small btn-approve">Approve</button>
                                    <?php endif; ?>
                                    <?php if ($u['user_id'] != $user['user_id']): ?>
                                        <button onclick="deleteUser(<?php echo $u['user_id']; ?>)" class="btn-small btn-danger"><?php echo Language::getText('delete', $lang); ?></button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Create User Modal -->
    <div id="create-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Create New User</h2>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo Language::getText('email', $lang); ?></label>
                    <input type="email" name="email" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-input" required>
                        <option value="Admin">Admin</option>
                        <option value="Owner">Owner</option>
                        <option value="Cashier">Cashier</option>
                        <option value="Customer"><?php echo Language::getText('customer', $lang); ?></option>
                    </select>
                </div>
                <button type="submit" name="create_user" class="btn">Create User</button>
            </form>
        </div>
    </div>

    <!-- Approve Role Modal -->
    <div id="approve-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeApproveModal()">&times;</span>
            <h2>Approve User Role</h2>
            <form method="POST">
                <input type="hidden" name="user_id" id="approve-user-id">
                <p>Assign role for: <strong id="approve-user-name"></strong></p>
                <div class="form-group">
                    <label class="form-label">Select Role</label>
                    <select name="new_role" class="form-input" required>
                        <option value="Admin">Admin</option>
                        <option value="Owner">Owner</option>
                        <option value="Cashier">Cashier</option>
                        <option value="Customer">Customer</option>
                    </select>
                </div>
                <button type="submit" name="approve_role" class="btn btn-success">Approve Role</button>
            </form>
        </div>
        
        <?php include __DIR__ . '/../../includes/footer.php'; ?>
    </div>

    <script>
        function changeLanguage(lang) {
            window.location.href = '?lang=' + lang;
        }
    </script>

    <style>
        .language-selector {
            position: fixed !important;
            top: 20px !important;
            right: 20px !important;
            left: auto !important;
            z-index: 1000 !important;
        }
        
        .page-header {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .page-header h2 {
            margin: 0 0 0.5rem 0;
            color: var(--primary);
            font-size: 1.8rem;
        }
        
        .page-header p {
            margin: 0;
            color: #666;
        }
        
    .user-controls {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }
    
    .users-table {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .users-table table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .users-table th, .users-table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    
    .users-table th {
        background: var(--background);
        font-weight: 600;
    }
    
    .btn-danger {
        background: #dc3545;
        color: white;
    }
    
    .btn-approve {
        background: #28a745;
        color: white;
    }
    
    .btn-success {
        background: #28a745;
        color: white;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }
    
    .role-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 15px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .role-pending {
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffeaa7;
    }
    
    .role-admin {
        background: #d1ecf1;
        color: #0c5460;
    }
    
    .role-owner {
        background: #d4edda;
        color: #155724;
    }
    
    .role-cashier {
        background: #e2e3e5;
        color: #383d41;
    }
    
    .role-customer {
        background: #f8d7da;
        color: #721c24;
    }
    
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }
    
    .modal-content {
        background-color: white;
        margin: 5vh auto;
        padding: 2rem;
        border-radius: 10px;
        width: 90%;
        max-width: 500px;
    }
    
    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
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

    <script>
        function openCreateModal() {
            document.getElementById('create-modal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('create-modal').style.display = 'none';
        }
        
        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user?')) {
                window.location.href = '?delete=' + userId + '&lang=<?php echo $lang; ?>';
            }
        }
        
        function openApproveModal(userId, userName) {
            document.getElementById('approve-user-id').value = userId;
            document.getElementById('approve-user-name').textContent = userName;
            document.getElementById('approve-modal').style.display = 'block';
        }
        
        function closeApproveModal() {
            document.getElementById('approve-modal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const createModal = document.getElementById('create-modal');
            const approveModal = document.getElementById('approve-modal');
            if (event.target == createModal) {
                createModal.style.display = 'none';
            }
            if (event.target == approveModal) {
                approveModal.style.display = 'none';
            }
        }
    </script>
</body>
</html>