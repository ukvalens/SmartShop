<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../helpers/Language.php';
require_once __DIR__ . '/../../../config/database.php';

$auth = new AuthController();
if (!$auth->isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

$user = $auth->getCurrentUser();
$lang = $_GET['lang'] ?? $_SESSION['language'] ?? 'en';
$db = new Database();
$conn = $db->getConnection();

$message = '';

// Handle receiving items
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = $_POST['order_id'];
    $received_quantities = $_POST['received_quantities'];
    
    foreach ($received_quantities as $detail_id => $received_qty) {
        if ($received_qty > 0) {
            // Update purchase order details
            $stmt = $conn->prepare("UPDATE purchase_order_details SET received_quantity = ? WHERE purchase_order_detail_id = ?");
            $stmt->bind_param("ii", $received_qty, $detail_id);
            $stmt->execute();
            
            // Update product stock
            $stmt = $conn->prepare("
                UPDATE products p 
                JOIN purchase_order_details pod ON p.product_id = pod.product_id 
                SET p.stock_quantity = p.stock_quantity + ? 
                WHERE pod.purchase_order_detail_id = ?
            ");
            $stmt->bind_param("ii", $received_qty, $detail_id);
            $stmt->execute();
        }
    }
    
    // Check if order is fully received
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total, 
               SUM(CASE WHEN received_quantity >= quantity_ordered THEN 1 ELSE 0 END) as received
        FROM purchase_order_details 
        WHERE purchase_order_id = ?
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result['total'] == $result['received']) {
        $stmt = $conn->prepare("UPDATE purchase_orders SET status = 'Delivered' WHERE purchase_order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
    }
    
    $message = 'Items received and stock updated successfully!';
}

// Get pending orders
$pending_orders = $conn->query("
    SELECT po.*, s.supplier_name 
    FROM purchase_orders po 
    JOIN suppliers s ON po.supplier_id = s.supplier_id 
    WHERE po.status = '<?php echo Language::get('pending', $lang); ?>' 
    ORDER BY po.order_date DESC
");

// Get order details if order selected
$order_details = null;
$selected_order = null;
if (isset($_GET['order_id'])) {
    $order_id = $_GET['order_id'];
    
    $stmt = $conn->prepare("
        SELECT po.*, s.supplier_name 
        FROM purchase_orders po 
        JOIN suppliers s ON po.supplier_id = s.supplier_id 
        WHERE po.purchase_order_id = ?
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $selected_order = $stmt->get_result()->fetch_assoc();
    
    $stmt = $conn->prepare("
        SELECT pod.*, p.product_name, p.stock_quantity 
        FROM purchase_order_details pod 
        JOIN products p ON pod.product_id = p.product_id 
        WHERE pod.purchase_order_id = ?
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order_details = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receive Inventory - SmartSHOP</title>
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
        <nav class="top-nav">
            <div class="nav-links">
                <a href="../dashboard/index.php?lang=<?php echo $lang; ?>"><?php echo Language::get('dashboard', $lang); ?></a>
                <a href="../pos/index.php?lang=<?php echo $lang; ?>">POS</a>
                <a href="../inventory/index.php?lang=<?php echo $lang; ?>">Inventory</a>
                <a href="../reports/index.php?lang=<?php echo $lang; ?>">Reports</a>
                <a href="../customers/index.php?lang=<?php echo $lang; ?>">Customers</a>
            </div>
        </nav>
        
        <header class="header">
            <h1>📥 Receive Inventory</h1>
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
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="pending-orders">
                <h2><?php echo Language::get('pending', $lang); ?> Purchase Orders</h2>
                <div class="orders-grid">
                    <?php while ($order = $pending_orders->fetch_assoc()): ?>
                        <div class="order-card">
                            <h3>Order #<?php echo $order['purchase_order_id']; ?></h3>
                            <p><strong>Supplier:</strong> <?php echo $order['supplier_name']; ?></p>
                            <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($order['order_date'])); ?></p>
                            <p><strong>Total:</strong> <?php echo number_format($order['total_amount']); ?> RWF</p>
                            <a href="?order_id=<?php echo $order['purchase_order_id']; ?>&lang=<?php echo $lang; ?>" class="btn">Receive Items</a>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <?php if ($selected_order && $order_details): ?>
                <div class="receive-section">
                    <h2>Receive Items - Order #<?php echo $selected_order['purchase_order_id']; ?></h2>
                    <p><strong>Supplier:</strong> <?php echo $selected_order['supplier_name']; ?></p>
                    
                    <form method="POST">
                        <input type="hidden" name="order_id" value="<?php echo $selected_order['purchase_order_id']; ?>">
                        
                        <div class="items-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th><?php echo Language::get('product', $lang); ?></th>
                                        <th>Ordered</th>
                                        <th>Previously Received</th>
                                        <th>Current Stock</th>
                                        <th>Receive Now</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($item = $order_details->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $item['product_name']; ?></td>
                                            <td><?php echo $item['quantity_ordered']; ?></td>
                                            <td><?php echo $item['received_quantity']; ?></td>
                                            <td><?php echo $item['stock_quantity']; ?></td>
                                            <td>
                                                <input type="number" 
                                                       name="received_quantities[<?php echo $item['purchase_order_detail_id']; ?>]" 
                                                       min="0" 
                                                       max="<?php echo $item['quantity_ordered'] - $item['received_quantity']; ?>"
                                                       value="<?php echo $item['quantity_ordered'] - $item['received_quantity']; ?>"
                                                       class="form-input">
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn">Update Stock</button>
                            <a href="receive.php?lang=<?php echo $lang; ?>" class="btn btn-secondary">Back to Orders</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
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
    .pending-orders {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }
    
    .orders-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-top: 1rem;
    }
    
    .order-card {
        background: var(--background);
        padding: 1.5rem;
        border-radius: 8px;
        border-left: 4px solid var(--accent);
    }
    
    .order-card h3 {
        color: var(--primary);
        margin-bottom: 1rem;
    }
    
    .order-card p {
        margin-bottom: 0.5rem;
    }
    
    .order-card .btn {
        margin-top: 1rem;
        width: 100%;
    }
    
    .receive-section {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .items-table {
        margin: 2rem 0;
        overflow-x: auto;
    }
    
    .items-table table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .items-table th,
    .items-table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    
    .items-table th {
        background: var(--background);
        font-weight: 600;
    }
    
    .items-table input {
        width: 80px;
        padding: 0.5rem;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    
    .form-actions {
        display: flex;
        gap: 1rem;
        margin-top: 2rem;
    }
    
    .btn-secondary {
        background: var(--secondary);
    }
    
    .btn-secondary:hover {
        background: #34495e;
    }
    </style>

    <script>
        function changeLanguage(lang) {
            window.location.href = '?lang=' + lang;
        }
    </script>
</body>
</html>