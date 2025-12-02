<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../helpers/Language.php';
require_once __DIR__ . '/../../helpers/Navigation.php';

$auth = new AuthController();
if (!$auth->isLoggedIn() || !in_array($auth->getCurrentUser()['role'], ['Admin', 'Owner'])) {
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

// Handle supplier operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_supplier'])) {
        $supplier_name = $_POST['supplier_name'];
        $contact_person = $_POST['contact_person'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        $address = $_POST['address'];
        $notes = $_POST['notes'] ?? '';
        
        $stmt = $conn->prepare("INSERT INTO suppliers (supplier_name, contact_person, phone, email, address, notes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $supplier_name, $contact_person, $phone, $email, $address, $notes);
        
        if ($stmt->execute()) {
            $message = 'Supplier added successfully!';
        } else {
            $message = 'Failed to add supplier.';
        }
    } elseif (isset($_POST['update_supplier'])) {
        $supplier_id = $_POST['supplier_id'];
        $supplier_name = $_POST['supplier_name'];
        $contact_person = $_POST['contact_person'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        $address = $_POST['address'];
        $notes = $_POST['notes'] ?? '';
        
        $stmt = $conn->prepare("UPDATE suppliers SET supplier_name=?, contact_person=?, phone=?, email=?, address=?, notes=? WHERE supplier_id=?");
        $stmt->bind_param("ssssssi", $supplier_name, $contact_person, $phone, $email, $address, $notes, $supplier_id);
        
        if ($stmt->execute()) {
            $message = 'Supplier updated successfully!';
        } else {
            $message = 'Failed to update supplier.';
        }
    }
}

// Handle supplier deletion
if (isset($_GET['delete'])) {
    $supplier_id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM suppliers WHERE supplier_id = ?");
    $stmt->bind_param("i", $supplier_id);
    
    if ($stmt->execute()) {
        $message = 'Supplier deleted successfully!';
    } else {
        $message = 'Failed to delete supplier.';
    }
}

// Get suppliers with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$total_result = $conn->query("SELECT COUNT(*) as total FROM suppliers");
$total_records = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

$suppliers = $conn->query("
    SELECT s.*,
           0 as total_orders,
           0 as total_spent
    FROM suppliers s
    ORDER BY s.supplier_name
    LIMIT $limit OFFSET $offset
");

// Get supplier for editing
$edit_supplier = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $edit_result = $conn->query("SELECT * FROM suppliers WHERE supplier_id = $edit_id");
    $edit_supplier = $edit_result->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo Language::getText('supplier_management', $lang); ?> - <?php echo Language::getText('smartshop', $lang); ?></title>
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
            <h1>🚚 <?php echo Language::getText('supplier_management', $lang); ?></h1>
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
            <?php if ($message): ?>
                <div class="alert <?php echo strpos($message, 'success') ? 'alert-success' : 'alert-error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="supplier-actions">
                <button onclick="openAddModal()" class="btn">➕ <?php echo Language::getText('add_supplier', $lang); ?></button>
                <a href="orders.php?lang=<?php echo $lang; ?>" class="btn btn-secondary">📋 <?php echo Language::getText('purchase_orders', $lang); ?></a>
            </div>

            <div class="suppliers-section">
                <h2>📋 <?php echo Language::getText('suppliers_list', $lang); ?></h2>
                <div class="suppliers-table">
                    <table>
                        <thead>
                            <tr>
                                <th><?php echo Language::getText('supplier_name', $lang); ?></th>
                                <th><?php echo Language::getText('contact_person', $lang); ?></th>
                                <th><?php echo Language::getText('phone', $lang); ?></th>
                                <th><?php echo Language::getText('email', $lang); ?></th>
                                <th><?php echo Language::getText('total_orders', $lang); ?></th>
                                <th><?php echo Language::getText('total_spent', $lang); ?></th>
                                <th><?php echo Language::getText('actions', $lang); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($supplier = $suppliers->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($supplier['supplier_name']); ?></strong>
                                        <br><small><?php echo htmlspecialchars($supplier['address']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($supplier['contact_person'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($supplier['phone'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($supplier['email']); ?></td>
                                    <td><span class="badge"><?php echo $supplier['total_orders']; ?></span></td>
                                    <td><?php echo number_format($supplier['total_spent']); ?> RWF</td>
                                    <td>
                                        <a href="?edit=<?php echo $supplier['supplier_id']; ?>&lang=<?php echo $lang; ?>" class="btn-small btn-primary">✏️ <?php echo Language::getText('edit', $lang); ?></a>
                                        <a href="?delete=<?php echo $supplier['supplier_id']; ?>&lang=<?php echo $lang; ?>" class="btn-small btn-danger" onclick="return confirm('<?php echo Language::getText('confirm_delete', $lang); ?>')">🗑️ <?php echo Language::getText('delete', $lang); ?></a>
                                        <a href="orders.php?supplier_id=<?php echo $supplier['supplier_id']; ?>&lang=<?php echo $lang; ?>" class="btn-small">📋 <?php echo Language::getText('orders', $lang); ?></a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page-1; ?>&lang=<?php echo $lang; ?>" class="btn-small">Previous</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&lang=<?php echo $lang; ?>" class="btn-small <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page+1; ?>&lang=<?php echo $lang; ?>" class="btn-small">Next</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php include __DIR__ . '/../../includes/footer.php'; ?>
    </div>

    <!-- Add/Edit Supplier Modal -->
    <div id="supplier-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2><?php echo $edit_supplier ? Language::getText('edit_supplier', $lang) : Language::getText('add_supplier', $lang); ?></h2>
            <form method="POST" class="supplier-form">
                <?php if ($edit_supplier): ?>
                    <input type="hidden" name="supplier_id" value="<?php echo $edit_supplier['supplier_id']; ?>">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><?php echo Language::getText('supplier_name', $lang); ?></label>
                        <input type="text" name="supplier_name" class="form-input" value="<?php echo $edit_supplier ? htmlspecialchars($edit_supplier['supplier_name']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?php echo Language::getText('contact_person', $lang); ?></label>
                        <input type="text" name="contact_person" class="form-input" value="<?php echo $edit_supplier ? htmlspecialchars($edit_supplier['contact_person'] ?? '') : ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><?php echo Language::getText('phone', $lang); ?></label>
                        <input type="tel" name="phone" class="form-input" value="<?php echo $edit_supplier ? htmlspecialchars($edit_supplier['phone'] ?? '') : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?php echo Language::getText('email', $lang); ?></label>
                        <input type="email" name="email" class="form-input" value="<?php echo $edit_supplier ? htmlspecialchars($edit_supplier['email']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label"><?php echo Language::getText('address', $lang); ?></label>
                    <textarea name="address" class="form-input" rows="3" required><?php echo $edit_supplier ? htmlspecialchars($edit_supplier['address']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label"><?php echo Language::getText('notes', $lang); ?></label>
                    <textarea name="notes" class="form-input" rows="2"><?php echo $edit_supplier ? htmlspecialchars($edit_supplier['notes'] ?? '') : ''; ?></textarea>
                </div>
                
                <button type="submit" name="<?php echo $edit_supplier ? 'update_supplier' : 'add_supplier'; ?>" class="btn">
                    <?php echo $edit_supplier ? Language::getText('update_supplier', $lang) : Language::getText('add_supplier', $lang); ?>
                </button>
            </form>
        </div>
    </div>

    <style>
        .supplier-actions {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
        }
        
        .suppliers-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .suppliers-section h2 {
            margin: 0 0 1.5rem 0;
            color: var(--primary);
            border-bottom: 2px solid var(--accent);
            padding-bottom: 0.5rem;
        }
        
        .suppliers-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .suppliers-table th,
        .suppliers-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .suppliers-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        
        .suppliers-table tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            background: #007bff;
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-size: 0.8rem;
        }
        
        .btn-small {
            padding: 0.3rem 0.6rem;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.8rem;
            margin: 0 0.2rem;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
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
            margin: 2vh auto;
            padding: 2rem;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            max-height: 85vh;
            overflow-y: auto;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: black;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }
        
        .pagination .btn-small.active {
            background: var(--primary);
            color: white;
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
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .supplier-actions {
                flex-direction: column;
            }
        }
    </style>

    <script>
        function openAddModal() {
            document.getElementById('supplier-modal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('supplier-modal').style.display = 'none';
        }
        
        function changeLanguage(lang) {
            window.location.href = '?lang=' + lang;
        }
        
        // Auto-open modal if editing
        <?php if ($edit_supplier): ?>
        document.getElementById('supplier-modal').style.display = 'block';
        <?php endif; ?>
    </script>
</body>
</html>