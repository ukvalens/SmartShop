<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../helpers/Language.php';
require_once __DIR__ . '/../../helpers/Navigation.php';
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

// Handle new product addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $product_name = $_POST['product_name'];
    $category_id = $_POST['category_id'];
    $barcode = $_POST['barcode'];
    $cost_price = $_POST['cost_price'];
    $selling_price = $_POST['selling_price'];
    $stock_quantity = $_POST['stock_quantity'];
    $reorder_level = $_POST['reorder_level'];
    
    $stmt = $conn->prepare("INSERT INTO products (product_name, category_id, barcode, cost_price, selling_price, stock_quantity, reorder_level) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sisddii", $product_name, $category_id, $barcode, $cost_price, $selling_price, $stock_quantity, $reorder_level);
    
    if ($stmt->execute()) {
        $message = 'Product added successfully!';
    } else {
        $message = 'Failed to add product.';
    }
}

// Handle stock adjustment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adjust_stock'])) {
    $product_id = $_POST['product_id'];
    $quantity_changed = $_POST['quantity_changed'];
    $adjustment_type = $_POST['adjustment_type'];
    $reason = $_POST['reason'];
    
    // Insert stock adjustment record
    $stmt = $conn->prepare("INSERT INTO stock_adjustments (product_id, user_id, quantity_changed, adjustment_type, reason) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiis", $product_id, $user['user_id'], $quantity_changed, $adjustment_type, $reason);
    
    if ($stmt->execute()) {
        // Update product stock
        $stmt = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE product_id = ?");
        $stmt->bind_param("ii", $quantity_changed, $product_id);
        $stmt->execute();
        
        $message = 'Stock adjusted successfully!';
    } else {
        $message = 'Failed to adjust stock.';
    }
}

// Get inventory with low stock alerts
$inventory = $conn->query("
    SELECT p.*, c.category_name,
           CASE WHEN p.stock_quantity <= p.reorder_level THEN 1 ELSE 0 END as low_stock
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    ORDER BY low_stock DESC, p.product_name
");

// Get categories for filtering
$categories = $conn->query("SELECT * FROM categories ORDER BY category_name");

// Get recent stock adjustments
$recent_adjustments = $conn->query("
    SELECT sa.*, p.product_name, u.full_name 
    FROM stock_adjustments sa
    JOIN products p ON sa.product_id = p.product_id
    JOIN users u ON sa.user_id = u.user_id
    ORDER BY sa.created_at DESC
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo Language::get('inventory_management', $lang); ?> - SmartSHOP</title>
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
            <h1>📦 <?php echo Language::get('inventory_management', $lang); ?></h1>
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

            <div class="inventory-controls">
                <div class="search-filter">
                    <button onclick="openAddProductModal()" class="btn btn-primary">Add New Product</button>
                    <input type="text" id="search" placeholder="<?php echo Language::get('search_products', $lang); ?>" class="form-input">
                    <select id="category-filter" class="form-input">
                        <option value="">All Categories</option>
                        <?php while ($category = $categories->fetch_assoc()): ?>
                            <option value="<?php echo $category['category_id']; ?>"><?php echo $category['category_name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                    <button onclick="showLowStock()" class="btn btn-warning">Show Low Stock</button>
                    <button onclick="showAllProducts()" class="btn btn-secondary">Show All</button>
                </div>
            </div>

            <div class="inventory-table">
                <table>
                    <thead>
                        <tr>
                            <th><?php echo Language::get('product', $lang); ?></th>
                            <th>Category</th>
                            <th>Stock</th>
                            <th>Reorder Level</th>
                            <th>Cost Price</th>
                            <th>Selling Price</th>
                            <th><?php echo Language::get('status', $lang); ?></th>
                            <th><?php echo Language::get('actions', $lang); ?></th>
                        </tr>
                    </thead>
                    <tbody id="inventory-tbody">
                        <?php while ($product = $inventory->fetch_assoc()): ?>
                            <tr data-category="<?php echo $product['category_id']; ?>" data-name="<?php echo strtolower($product['product_name']); ?>" class="<?php echo $product['low_stock'] ? 'low-stock-row' : ''; ?>">
                                <td>
                                    <strong><?php echo $product['product_name']; ?></strong>
                                    <?php if ($product['barcode']): ?>
                                        <br><small>Barcode: <?php echo $product['barcode']; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $product['category_name']; ?></td>
                                <td>
                                    <span class="stock-quantity <?php echo $product['low_stock'] ? 'low-stock' : ''; ?>">
                                        <?php echo $product['stock_quantity']; ?>
                                    </span>
                                </td>
                                <td><?php echo $product['reorder_level']; ?></td>
                                <td><?php echo number_format($product['cost_price']); ?> RWF</td>
                                <td><?php echo number_format($product['selling_price']); ?> RWF</td>
                                <td>
                                    <?php if ($product['low_stock']): ?>
                                        <span class="status-low">Low Stock</span>
                                    <?php else: ?>
                                        <span class="status-ok">In Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button onclick="openAdjustModal(<?php echo $product['product_id']; ?>, '<?php echo addslashes($product['product_name']); ?>')" class="btn-small btn-adjust">Adjust</button>
                                    <button onclick="openEditModal(<?php echo $product['product_id']; ?>)" class="btn-small btn-edit"><?php echo Language::get('edit', $lang); ?></button>
                                    <button onclick="viewHistory(<?php echo $product['product_id']; ?>)" class="btn-small btn-history">History</button>
                                    <?php if ($product['low_stock']): ?>
                                        <button onclick="reorderProduct(<?php echo $product['product_id']; ?>)" class="btn-small btn-reorder">Reorder</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="recent-adjustments">
                <h2>Recent Stock Adjustments</h2>
                <div class="adjustments-table">
                    <table>
                        <thead>
                            <tr>
                                <th><?php echo Language::get('product', $lang); ?></th>
                                <th>Quantity</th>
                                <th>Type</th>
                                <th>Reason</th>
                                <th>User</th>
                                <th><?php echo Language::get('date', $lang); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($adjustment = $recent_adjustments->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $adjustment['product_name']; ?></td>
                                    <td class="<?php echo $adjustment['quantity_changed'] > 0 ? 'positive' : 'negative'; ?>">
                                        <?php echo $adjustment['quantity_changed'] > 0 ? '+' : ''; ?><?php echo $adjustment['quantity_changed']; ?>
                                    </td>
                                    <td>
                                        <span class="adjustment-type"><?php echo ucfirst($adjustment['adjustment_type']); ?></span>
                                    </td>
                                    <td><?php echo $adjustment['reason']; ?></td>
                                    <td><?php echo $adjustment['full_name']; ?></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($adjustment['created_at'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <?php include __DIR__ . '/../../includes/footer.php'; ?>
    </div>

    <!-- Stock Adjustment Modal -->
    <div id="adjust-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAdjustModal()">&times;</span>
            <h2>Adjust Stock</h2>
            <form method="POST">
                <input type="hidden" name="product_id" id="adjust-product-id">
                <p>Product: <strong id="adjust-product-name"></strong></p>
                
                <div class="form-group">
                    <label class="form-label">Quantity Change</label>
                    <input type="number" name="quantity_changed" class="form-input" required>
                    <small>Use negative numbers to reduce stock</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Adjustment Type</label>
                    <select name="adjustment_type" class="form-input" required>
                        <option value="Manual Correction">Manual Correction</option>
                        <option value="Damaged">Damaged</option>
                        <option value="Expired">Expired</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Reason</label>
                    <textarea name="reason" class="form-input" rows="3" required></textarea>
                </div>
                
                <button type="submit" name="adjust_stock" class="btn">Adjust Stock</button>
            </form>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div id="add-product-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddProductModal()">&times;</span>
            <h2>Add New Product</h2>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Product Name</label>
                    <input type="text" name="product_name" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-input" required>
                        <option value="">Select Category</option>
                        <?php 
                        $categories_for_modal = $conn->query("SELECT * FROM categories ORDER BY category_name");
                        while ($cat = $categories_for_modal->fetch_assoc()): ?>
                            <option value="<?php echo $cat['category_id']; ?>"><?php echo $cat['category_name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Barcode (Optional)</label>
                    <input type="text" name="barcode" class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Cost Price (RWF)</label>
                    <input type="number" name="cost_price" class="form-input" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Selling Price (RWF)</label>
                    <input type="number" name="selling_price" class="form-input" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Initial Stock Quantity</label>
                    <input type="number" name="stock_quantity" class="form-input" min="0" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Reorder Level</label>
                    <input type="number" name="reorder_level" class="form-input" min="0" required>
                </div>
                
                <button type="submit" name="add_product" class="btn">Add Product</button>
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
    .inventory-controls {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }
    
    .search-filter {
        display: flex;
        gap: 1rem;
        align-items: center;
    }
    
    .search-filter input,
    .search-filter select {
        flex: 1;
    }
    
    .btn-warning {
        background: orange;
        color: white;
    }
    
    .btn-primary {
        background: var(--primary);
        color: white;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: 500;
    }
    
    .btn-secondary {
        background: var(--secondary);
        color: white;
        padding: 0.75rem 1rem;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }
    
    .inventory-table,
    .recent-adjustments {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }
    
    .inventory-table table,
    .adjustments-table table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .inventory-table th,
    .inventory-table td,
    .adjustments-table th,
    .adjustments-table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    
    .inventory-table th,
    .adjustments-table th {
        background: var(--background);
        font-weight: 600;
    }
    
    .low-stock-row {
        background: #fff3cd;
    }
    
    .stock-quantity.low-stock {
        color: red;
        font-weight: bold;
    }
    
    .status-low {
        color: red;
        font-weight: bold;
    }
    
    .status-ok {
        color: green;
    }
    
    .btn-small {
        color: white;
        border: none;
        padding: 0.3rem 0.6rem;
        border-radius: 3px;
        cursor: pointer;
        font-size: 0.75rem;
        margin: 0 2px;
    }
    
    .btn-adjust { background: var(--accent); }
    .btn-edit { background: #28a745; }
    .btn-history { background: #6c757d; }
    .btn-reorder { background: #fd7e14; }
    
    .btn-small:hover {
        opacity: 0.8;
        transform: translateY(-1px);
    }
    
    .positive {
        color: green;
        font-weight: bold;
    }
    
    .negative {
        color: red;
        font-weight: bold;
    }
    
    .adjustment-type {
        background: #e9ecef;
        padding: 0.2rem 0.5rem;
        border-radius: 3px;
        font-size: 0.8rem;
        font-weight: 500;
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
        max-height: 90vh;
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
    
    /* Laptop screen adjustments */
    @media (max-width: 1366px) and (max-height: 768px) {
        .modal-content {
            margin: 2vh auto;
            padding: 1.5rem;
            max-height: 85vh;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-input {
            padding: 0.6rem;
        }
    }
    </style>

    <script>
        function changeLanguage(lang) {
            window.location.href = '?lang=' + lang;
        }
        
        function openAdjustModal(productId, productName) {
            document.getElementById('adjust-product-id').value = productId;
            document.getElementById('adjust-product-name').textContent = productName;
            document.getElementById('adjust-modal').style.display = 'block';
        }
        
        function closeAdjustModal() {
            document.getElementById('adjust-modal').style.display = 'none';
        }
        
        function openEditModal(productId) {
            window.location.href = 'edit_product.php?id=' + productId + '&lang=<?php echo $lang; ?>';
        }
        
        function viewHistory(productId) {
            window.open('product_history.php?id=' + productId + '&lang=<?php echo $lang; ?>', '_blank', 'width=800,height=600');
        }
        
        function reorderProduct(productId) {
            if (confirm('Generate reorder alert for this product?')) {
                fetch('reorder_product.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'product_id=' + productId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Reorder alert generated successfully!');
                    } else {
                        alert('Failed to generate reorder alert.');
                    }
                })
                .catch(() => alert('Error generating reorder alert.'));
            }
        }
        
        window.onclick = function(event) {
            const adjustModal = document.getElementById('adjust-modal');
            const addModal = document.getElementById('add-product-modal');
            if (event.target == adjustModal) {
                adjustModal.style.display = 'none';
            }
            if (event.target == addModal) {
                addModal.style.display = 'none';
            }
        }
        
        function showLowStock() {
            const rows = document.querySelectorAll('#inventory-tbody tr');
            rows.forEach(row => {
                if (row.classList.contains('low-stock-row')) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        function showAllProducts() {
            const rows = document.querySelectorAll('#inventory-tbody tr');
            rows.forEach(row => {
                row.style.display = '';
            });
        }
        
        function openAddProductModal() {
            document.getElementById('add-product-modal').style.display = 'block';
        }
        
        function closeAddProductModal() {
            document.getElementById('add-product-modal').style.display = 'none';
        }
        
        // Search functionality
        document.getElementById('search').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#inventory-tbody tr');
            
            rows.forEach(row => {
                const productName = row.dataset.name;
                if (productName.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        // Category filter
        document.getElementById('category-filter').addEventListener('change', function(e) {
            const categoryId = e.target.value;
            const rows = document.querySelectorAll('#inventory-tbody tr');
            
            rows.forEach(row => {
                const rowCategory = row.dataset.category;
                if (!categoryId || rowCategory === categoryId) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>