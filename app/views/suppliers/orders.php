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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_id = $_POST['supplier_id'];
    $order_date = $_POST['order_date'];
    $products = $_POST['products'];
    $quantities = $_POST['quantities'];
    $cost_prices = $_POST['cost_prices'];
    
    $total_amount = 0;
    foreach ($products as $index => $product_id) {
        $total_amount += $quantities[$index] * $cost_prices[$index];
    }
    
    // Insert purchase order
    $stmt = $conn->prepare("INSERT INTO purchase_orders (supplier_id, order_date, total_amount) VALUES (?, ?, ?)");
    $stmt->bind_param("isd", $supplier_id, $order_date, $total_amount);
    
    if ($stmt->execute()) {
        $order_id = $conn->insert_id;
        
        // Insert order details
        foreach ($products as $index => $product_id) {
            $stmt = $conn->prepare("INSERT INTO purchase_order_details (purchase_order_id, product_id, quantity_ordered, cost_price) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiid", $order_id, $product_id, $quantities[$index], $cost_prices[$index]);
            $stmt->execute();
        }
        
        $message = 'Purchase order created successfully!';
    } else {
        $message = 'Failed to create purchase order.';
    }
}

// Get suppliers
$suppliers = $conn->query("SELECT * FROM suppliers ORDER BY supplier_name");

// Get products
$products = $conn->query("SELECT * FROM products ORDER BY product_name");

// Get recent orders
$recent_orders = $conn->query("
    SELECT po.*, s.supplier_name 
    FROM purchase_orders po 
    JOIN suppliers s ON po.supplier_id = s.supplier_id 
    ORDER BY po.created_at DESC 
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Orders - SmartSHOP</title>
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
                <a href="../dashboard/index.php?lang=<?php echo $lang; ?>"><?php echo Language::getText('dashboard', $lang); ?></a>
                <a href="../pos/index.php?lang=<?php echo $lang; ?>">POS</a>
                <a href="../inventory/index.php?lang=<?php echo $lang; ?>">Inventory</a>
                <a href="../reports/index.php?lang=<?php echo $lang; ?>">Reports</a>
                <a href="../customers/index.php?lang=<?php echo $lang; ?>">Customers</a>
            </div>
        </nav>
        
        <header class="header">
            <h1>📦 Purchase Orders</h1>
            <div class="user-info">
                <div class="user-profile">
                    <img src="../../../uploads/profiles/<?php echo $user['user_id']; ?>.jpg?v=<?php echo time(); ?>" alt="Profile" class="profile-img" onerror="this.src='../../../uploads/profiles/default.jpg'">
                    <div class="user-details">
                        <span class="user-name"><?php echo $user['full_name']; ?></span>
                        <span class="user-role"><?php echo $user['role']; ?></span>
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

            <div class="order-section">
                <h2>Create New Purchase Order</h2>
                
                <form method="POST" class="order-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Supplier</label>
                            <select name="supplier_id" class="form-input" required>
                                <option value="">Select Supplier</option>
                                <?php while ($supplier = $suppliers->fetch_assoc()): ?>
                                    <option value="<?php echo $supplier['supplier_id']; ?>">
                                        <?php echo $supplier['supplier_name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Order Date</label>
                            <input type="date" name="order_date" class="form-input" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>

                    <div class="products-section">
                        <h3>Order Items</h3>
                        <div id="product-items">
                            <div class="product-item">
                                <select name="products[]" class="form-input" required>
                                    <option value="">Select Product</option>
                                    <?php 
                                    $products->data_seek(0);
                                    while ($product = $products->fetch_assoc()): ?>
                                        <option value="<?php echo $product['product_id']; ?>">
                                            <?php echo $product['product_name']; ?> (Stock: <?php echo $product['stock_quantity']; ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <input type="number" name="quantities[]" placeholder="Quantity" class="form-input" min="1" required>
                                <input type="number" name="cost_prices[]" placeholder="Cost Price" class="form-input" step="0.01" min="0" required>
                                <button type="button" onclick="removeItem(this)" class="btn-remove">Remove</button>
                            </div>
                        </div>
                        <button type="button" onclick="addItem()" class="btn-add">Add Item</button>
                    </div>

                    <button type="submit" class="btn">Create Purchase Order</button>
                </form>
            </div>

            <div class="recent-orders">
                <h2>Recent Purchase Orders</h2>
                <div class="orders-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Supplier</th>
                                <th><?php echo Language::getText('date', $lang); ?></th>
                                <th>Total Amount</th>
                                <th><?php echo Language::getText('status', $lang); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = $recent_orders->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $order['purchase_order_id']; ?></td>
                                    <td><?php echo $order['supplier_name']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                    <td><?php echo number_format($order['total_amount']); ?> RWF</td>
                                    <td><span class="status-<?php echo strtolower($order['status']); ?>"><?php echo $order['status']; ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
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
    .order-section {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }
    
    .order-form {
        max-width: 800px;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .products-section {
        margin: 2rem 0;
    }
    
    .product-item {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr auto;
        gap: 1rem;
        margin-bottom: 1rem;
        align-items: center;
    }
    
    .btn-add, .btn-remove {
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 0.9rem;
    }
    
    .btn-add {
        background: var(--accent);
        color: white;
    }
    
    .btn-remove {
        background: var(--error);
        color: white;
    }
    
    .recent-orders {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .orders-table table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .orders-table th,
    .orders-table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    
    .orders-table th {
        background: var(--background);
        font-weight: 600;
    }
    
    .status-pending { color: orange; }
    .status-delivered { color: green; }
    .status-cancelled { color: red; }
    </style>

    <script>
        function changeLanguage(lang) {
            window.location.href = '?lang=' + lang;
        }
        
        function addItem() {
            const container = document.getElementById('product-items');
            const newItem = container.firstElementChild.cloneNode(true);
            
            // Clear values
            newItem.querySelectorAll('select, input').forEach(input => {
                input.value = '';
            });
            
            container.appendChild(newItem);
        }
        
        function removeItem(button) {
            const container = document.getElementById('product-items');
            if (container.children.length > 1) {
                button.parentElement.remove();
            }
        }
    </script>
</body>
</html>