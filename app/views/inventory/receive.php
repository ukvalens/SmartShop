<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../helpers/Language.php';
require_once __DIR__ . '/../../helpers/Navigation.php';
require_once __DIR__ . '/../../../config/database.php';

$auth = new AuthController();
if (!$auth->isLoggedIn() || $auth->getCurrentUser()['role'] !== 'Owner') {
    header('Location: ../auth/login.php');
    exit;
}

$user = $auth->getCurrentUser();
$lang = $_GET['lang'] ?? 'en';
$db = new Database();
$conn = $db->getConnection();
$message = '';

// Handle delivery receipt
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['receive_delivery'])) {
    $supplier_id = $_POST['supplier_id'];
    $delivery_date = $_POST['delivery_date'];
    $notes = $_POST['notes'];
    
    // Process each product in the delivery
    foreach ($_POST['products'] as $product_data) {
        if (!empty($product_data['product_name']) && $product_data['quantity'] > 0) {
            $product_name = $product_data['product_name'];
            $quantity = $product_data['quantity'];
            $cost_price = $product_data['cost_price'];
            $selling_price = $product_data['selling_price'];
            
            // Check if product exists
            $stmt = $conn->prepare("SELECT product_id FROM products WHERE product_name = ?");
            $stmt->bind_param("s", $product_name);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Update existing product
                $product = $result->fetch_assoc();
                $stmt = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity + ?, cost_price = ?, selling_price = ? WHERE product_id = ?");
                $stmt->bind_param("iddi", $quantity, $cost_price, $selling_price, $product['product_id']);
                $stmt->execute();
            } else {
                // Add new product
                $stmt = $conn->prepare("INSERT INTO products (product_name, cost_price, selling_price, stock_quantity) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("sddi", $product_name, $cost_price, $selling_price, $quantity);
                $stmt->execute();
            }
        }
    }
    
    $message = 'Delivery received and inventory updated successfully!';
}

// Get suppliers for dropdown
$suppliers = $conn->query("SELECT * FROM suppliers ORDER BY supplier_name");
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receive Delivery - SmartSHOP</title>
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
            <h1>🚚 Receive Delivery</h1>
            <div class="user-info">
                <div class="user-profile">
                    <img src="../../../uploads/profiles/<?php echo $user['user_id']; ?>.jpg" alt="Profile" class="profile-img" onerror="this.src='../../../uploads/profiles/default.jpg'">
                    <div class="user-details">
                        <span class="user-name"><?php echo $user['full_name']; ?></span>
                        <span class="user-role"><?php echo $user['role']; ?></span>
                    </div>
                </div>
            </div>
        </header>

        <div class="content">
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="receive-form">
                <form method="POST">
                    <div class="delivery-info">
                        <h3>Delivery Information</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Supplier</label>
                                <select name="supplier_id" class="form-input" onchange="loadSupplierOrders(this.value)" required>
                                    <option value="">Select Supplier</option>
                                    <?php while ($supplier = $suppliers->fetch_assoc()): ?>
                                        <option value="<?php echo $supplier['supplier_id']; ?>"><?php echo $supplier['supplier_name']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Delivery Date</label>
                                <input type="date" name="delivery_date" class="form-input" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" class="form-input" rows="2" placeholder="Delivery notes..."></textarea>
                        </div>
                    </div>

                    <div class="products-section">
                        <h3>Products Received</h3>
                        <div id="products-container">
                            <div class="product-row">
                                <input type="text" name="products[0][product_name]" placeholder="Product Name (e.g., Sugar 1kg)" class="form-input" required>
                                <input type="number" name="products[0][quantity]" placeholder="Quantity" class="form-input" min="1" required>
                                <input type="number" name="products[0][cost_price]" placeholder="Cost Price" class="form-input" step="0.01" required>
                                <input type="number" name="products[0][selling_price]" placeholder="Selling Price" class="form-input" step="0.01" required>
                                <button type="button" onclick="removeProduct(this)" class="btn-remove">Remove</button>
                            </div>
                        </div>
                        <div id="ordered-products" style="display: none;">
                            <h4>Ordered Products from Supplier:</h4>
                            <div id="ordered-products-list"></div>
                        </div>
                        <button type="button" onclick="addProduct()" class="btn btn-secondary">Add Another Product</button>
                    </div>

                    <div class="form-actions">
                        <input type="hidden" name="order_id" id="order-id-input">
                        <button type="submit" name="receive_delivery" class="btn btn-primary">Receive Delivery</button>
                        <a href="index.php?lang=<?php echo $lang; ?>" class="btn btn-secondary">Back to Inventory</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .language-selector {
            position: fixed !important;
            top: 20px !important;
            right: 20px !important;
            z-index: 1000 !important;
        }
        
        .receive-form {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .delivery-info, .products-section {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .product-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr auto;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            align-items: center;
        }
        
        .btn-remove {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.5rem;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn-use:hover {
            background: #218838;
        }
        
        .btn-remove:hover {
            background: #c82333;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        
        #ordered-products {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin: 1rem 0;
            border: 1px solid #dee2e6;
        }
        
        .ordered-product {
            background: white;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            border: 1px solid #e9ecef;
        }
        
        .order-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .btn-use {
            background: #28a745;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .order-products {
            display: grid;
            gap: 0.5rem;
        }
        
        .order-product-item {
            background: #f8f9fa;
            padding: 0.5rem;
            border-radius: 3px;
            font-size: 0.9rem;
        }
    </style>

    <script>
        function changeLanguage(lang) {
            window.location.href = '?lang=' + lang;
        }
        
        let productIndex = 1;
        
        function addProduct() {
            const container = document.getElementById('products-container');
            const productRow = document.createElement('div');
            productRow.className = 'product-row';
            productRow.innerHTML = `
                <input type="text" name="products[${productIndex}][product_name]" placeholder="Product Name" class="form-input" required>
                <input type="number" name="products[${productIndex}][quantity]" placeholder="Quantity" class="form-input" min="1" required>
                <input type="number" name="products[${productIndex}][cost_price]" placeholder="Cost Price" class="form-input" step="0.01" required>
                <input type="number" name="products[${productIndex}][selling_price]" placeholder="Selling Price" class="form-input" step="0.01" required>
                <button type="button" onclick="removeProduct(this)" class="btn-remove">Remove</button>
            `;
            container.appendChild(productRow);
            productIndex++;
        }
        
        function removeProduct(button) {
            button.parentElement.remove();
        }
        
        function loadSupplierOrders(supplierId) {
            if (!supplierId) {
                document.getElementById('ordered-products').style.display = 'none';
                return;
            }
            
            fetch(`get_supplier_orders.php?supplier_id=${supplierId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.length > 0) {
                        displayOrderedProducts(data);
                    } else {
                        document.getElementById('ordered-products').style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error loading supplier orders:', error);
                });
        }
        
        function displayOrderedProducts(orders) {
            const container = document.getElementById('ordered-products-list');
            container.innerHTML = '';
            
            orders.forEach(order => {
                const orderDiv = document.createElement('div');
                orderDiv.className = 'ordered-product';
                orderDiv.innerHTML = `
                    <div class="order-info">
                        <strong>Order #${order.purchase_order_id}</strong> - ${order.order_date}
                        <button type="button" onclick="useOrderedProducts(${order.purchase_order_id})" class="btn-use">Use These Products</button>
                    </div>
                    <div class="order-products">
                        ${order.products.map(product => `
                            <div class="order-product-item">
                                ${product.product_name} - Qty: ${product.quantity_ordered} - Cost: ${product.cost_price}
                            </div>
                        `).join('')}
                    </div>
                `;
                container.appendChild(orderDiv);
            });
            
            document.getElementById('ordered-products').style.display = 'block';
        }
        
        function useOrderedProducts(orderId) {
            fetch(`get_order_details.php?order_id=${orderId}`)
                .then(response => response.json())
                .then(products => {
                    const container = document.getElementById('products-container');
                    container.innerHTML = '';
                    
                    products.forEach((product, index) => {
                        const productRow = document.createElement('div');
                        productRow.className = 'product-row';
                        productRow.innerHTML = `
                            <input type="text" name="products[${index}][product_name]" value="${product.product_name}" class="form-input" required>
                            <input type="number" name="products[${index}][quantity]" value="${product.quantity_ordered}" class="form-input" min="1" required>
                            <input type="number" name="products[${index}][cost_price]" value="${product.cost_price}" class="form-input" step="0.01" required>
                            <input type="number" name="products[${index}][selling_price]" placeholder="Selling Price" class="form-input" step="0.01" required>
                            <button type="button" onclick="removeProduct(this)" class="btn-remove">Remove</button>
                        `;
                        container.appendChild(productRow);
                    });
                    
                    // Set the order ID to mark it as delivered when received
                    document.getElementById('order-id-input').value = orderId;
                    productIndex = products.length;
                })
                .catch(error => {
                    console.error('Error loading order details:', error);
                });
        }
    </script>
</body>
</html>