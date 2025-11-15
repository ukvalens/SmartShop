<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../helpers/Navigation.php';
require_once __DIR__ . '/../../../config/database.php';

$auth = new AuthController();
if (!$auth->isLoggedIn() || !in_array($auth->getCurrentUser()['role'], ['Owner', 'Manager'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user = $auth->getCurrentUser();
$lang = $_GET['lang'] ?? 'en';
$db = new Database();
$conn = $db->getConnection();
$message = '';

// Handle purchase order creation (simulated)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_order'])) {
    $supplier_name = $_POST['supplier_name'];
    $product_items = $_POST['product_items'];
    $total_amount = $_POST['total_amount'];
    $delivery_date = $_POST['delivery_date'];
    
    // Simulate successful order creation
    $message = 'Purchase order created successfully! Order for ' . $supplier_name . ' has been submitted.';
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get recent purchase orders (simulated data)
$all_orders = [
    ['order_id' => 1, 'supplier_name' => 'ABC Suppliers', 'product_items' => 'Sugar 50kg x 10', 'total_amount' => 150000, 'delivery_date' => date('Y-m-d', strtotime('+7 days')), 'status' => 'pending', 'created_by_name' => $user['full_name'], 'created_at' => date('Y-m-d H:i:s')],
    ['order_id' => 2, 'supplier_name' => 'XYZ Trading', 'product_items' => 'Soap x 200, Oil 5L x 10', 'total_amount' => 85000, 'delivery_date' => date('Y-m-d', strtotime('+5 days')), 'status' => 'delivered', 'created_by_name' => $user['full_name'], 'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))],
    ['order_id' => 3, 'supplier_name' => 'DEF Wholesale', 'product_items' => 'Rice 25kg x 15', 'total_amount' => 120000, 'delivery_date' => date('Y-m-d', strtotime('+3 days')), 'status' => 'pending', 'created_by_name' => $user['full_name'], 'created_at' => date('Y-m-d H:i:s')],
    ['order_id' => 4, 'supplier_name' => 'GHI Supplies', 'product_items' => 'Flour 10kg x 20', 'total_amount' => 95000, 'delivery_date' => date('Y-m-d', strtotime('+4 days')), 'status' => 'pending', 'created_by_name' => $user['full_name'], 'created_at' => date('Y-m-d H:i:s')],
    ['order_id' => 5, 'supplier_name' => 'JKL Trading', 'product_items' => 'Tea 500g x 50', 'total_amount' => 75000, 'delivery_date' => date('Y-m-d', strtotime('+6 days')), 'status' => 'delivered', 'created_by_name' => $user['full_name'], 'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))],
    ['order_id' => 6, 'supplier_name' => 'MNO Distributors', 'product_items' => 'Salt 1kg x 100', 'total_amount' => 45000, 'delivery_date' => date('Y-m-d', strtotime('+8 days')), 'status' => 'pending', 'created_by_name' => $user['full_name'], 'created_at' => date('Y-m-d H:i:s')],
    ['order_id' => 7, 'supplier_name' => 'PQR Wholesale', 'product_items' => 'Beans 5kg x 30', 'total_amount' => 110000, 'delivery_date' => date('Y-m-d', strtotime('+2 days')), 'status' => 'delivered', 'created_by_name' => $user['full_name'], 'created_at' => date('Y-m-d H:i:s', strtotime('-3 days'))],
    ['order_id' => 8, 'supplier_name' => 'STU Suppliers', 'product_items' => 'Maize 10kg x 25', 'total_amount' => 130000, 'delivery_date' => date('Y-m-d', strtotime('+9 days')), 'status' => 'pending', 'created_by_name' => $user['full_name'], 'created_at' => date('Y-m-d H:i:s')],
    ['order_id' => 9, 'supplier_name' => 'VWX Trading', 'product_items' => 'Milk powder 1kg x 40', 'total_amount' => 160000, 'delivery_date' => date('Y-m-d', strtotime('+5 days')), 'status' => 'pending', 'created_by_name' => $user['full_name'], 'created_at' => date('Y-m-d H:i:s')],
    ['order_id' => 10, 'supplier_name' => 'YZ Distributors', 'product_items' => 'Cooking fat 2kg x 20', 'total_amount' => 80000, 'delivery_date' => date('Y-m-d', strtotime('+7 days')), 'status' => 'delivered', 'created_by_name' => $user['full_name'], 'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))],
    ['order_id' => 11, 'supplier_name' => 'Alpha Supplies', 'product_items' => 'Pasta 500g x 60', 'total_amount' => 90000, 'delivery_date' => date('Y-m-d', strtotime('+10 days')), 'status' => 'pending', 'created_by_name' => $user['full_name'], 'created_at' => date('Y-m-d H:i:s')],
    ['order_id' => 12, 'supplier_name' => 'Beta Trading', 'product_items' => 'Tomato sauce 400g x 80', 'total_amount' => 70000, 'delivery_date' => date('Y-m-d', strtotime('+4 days')), 'status' => 'pending', 'created_by_name' => $user['full_name'], 'created_at' => date('Y-m-d H:i:s')]
];

$total_orders = count($all_orders);
$total_pages = ceil($total_orders / $limit);
$orders_data = array_slice($all_orders, $offset, $limit);
$orders = (object) ['data' => $orders_data, 'index' => 0];
$orders->fetch_assoc = function() use (&$orders) {
    return isset($orders->data[$orders->index]) ? $orders->data[$orders->index++] : null;
};
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Management - SmartSHOP</title>
    <link rel="stylesheet" href="../../../public/css/main.css">
    <link rel="stylesheet" href="../../../public/css/dashboard.css">
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
            <h1>🚚 <?php echo Language::get('supplier_management', $lang); ?></h1>
            <div class="user-info">
                <div class="user-profile">
                    <img src="../../../uploads/profiles/<?php echo $user['user_id']; ?>.jpg?v=<?php echo time(); ?>" alt="Profile" class="profile-img" onerror="this.src='../../../uploads/profiles/default.jpg'">
                    <div class="user-details">
                        <span class="user-name"><?php echo $user['full_name']; ?></span>
                        <span class="user-role"><?php echo $user['role']; ?></span>
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

            <div class="supplier-controls">
                <button onclick="openOrderModal()" class="btn btn-primary">Create Purchase Order</button>
                <p>Create purchase orders for sugar, rice, soap and other products</p>
            </div>

            <div class="orders-table">
                <h2>Purchase Orders</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Supplier</th>
                            <th>Items</th>
                            <th><?php echo Language::get('amount', $lang); ?></th>
                            <th>Delivery Date</th>
                            <th><?php echo Language::get('status', $lang); ?></th>
                            <th>Created By</th>
                            <th><?php echo Language::get('date', $lang); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders_data as $order): ?>
                            <tr>
                                <td>#<?php echo $order['order_id']; ?></td>
                                <td><?php echo $order['supplier_name']; ?></td>
                                <td><?php echo $order['product_items']; ?></td>
                                <td><?php echo number_format($order['total_amount']); ?> RWF</td>
                                <td><?php echo date('M d, Y', strtotime($order['delivery_date'])); ?></td>
                                <td><?php echo ucfirst($order['status']); ?></td>
                                <td><?php echo $order['created_by_name']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&lang=<?php echo $lang; ?>" class="page-btn">Previous</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&lang=<?php echo $lang; ?>" class="page-btn <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&lang=<?php echo $lang; ?>" class="page-btn">Next</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Create Order Modal -->
    <div id="order-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Create Purchase Order</h2>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label"><?php echo Language::get('supplier_name', $lang); ?></label>
                    <input type="text" name="supplier_name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Product Items</label>
                    <textarea name="product_items" class="form-input" rows="4" placeholder="e.g., Sugar 50kg x 10 bags, Rice 25kg x 20 bags, Soap bars x 100 pieces" required></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Total Amount (RWF)</label>
                    <input type="number" name="total_amount" class="form-input" step="0.01" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Expected Delivery Date</label>
                    <input type="date" name="delivery_date" class="form-input" required>
                </div>
                <button type="submit" name="create_order" class="btn">Create Order</button>
            </form>
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
    .supplier-controls {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
        text-align: center;
    }
    
    .orders-table {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .orders-table table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
    }
    
    .orders-table th, .orders-table td {
        padding: 0.4rem;
        text-align: left;
        border-bottom: 1px solid #eee;
        font-size: 0.85rem;
    }
    
    .orders-table th {
        background: var(--background);
        font-weight: 600;
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
        padding: 1.5rem;
        border-radius: 10px;
        width: 90%;
        max-width: 500px;
        max-height: 90vh;
        overflow-y: auto;
    }
    
    /* Laptop screen adjustments */
    @media (max-width: 1366px) and (max-height: 768px) {
        .modal-content {
            margin: 1vh auto;
            padding: 1rem;
            max-height: 95vh;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-input {
            padding: 0.6rem;
        }
        
        .form-label {
            margin-bottom: 0.3rem;
        }
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
    
    /* Compact navigation for more links */
    .top-nav .nav-links {
        gap: 0.5rem;
    }
    
    .top-nav .nav-links a {
        padding: 0.5rem 0.8rem;
        font-size: 0.9rem;
        white-space: nowrap;
    }
    
    .pagination {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 1.5rem;
        padding-top: 1rem;
        border-top: 1px solid #eee;
    }
    
    .page-btn {
        padding: 0.5rem 1rem;
        background: var(--white);
        color: var(--primary);
        text-decoration: none;
        border: 1px solid var(--border);
        border-radius: 5px;
        transition: all 0.3s ease;
        font-size: 0.9rem;
    }
    
    .page-btn:hover {
        background: var(--accent);
        color: var(--white);
    }
    
    .page-btn.active {
        background: var(--primary);
        color: var(--white);
    }
    </style>

    <script>
        function openOrderModal() {
            document.getElementById('order-modal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('order-modal').style.display = 'none';
        }
        
        function changeLanguage(lang) {
            window.location.href = '?lang=' + lang;
        }
    </script>
    
    <?php include __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>