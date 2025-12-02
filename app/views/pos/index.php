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

// Handle sale completion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_sale'])) {
    $customer_id = $_POST['customer_id'] ?: null;
    $payment_method = $_POST['payment_method'];
    $cart_items = json_decode($_POST['cart_items'], true);
    $total_amount = floatval($_POST['total_amount']);
    
    // Insert sale with explicit sale_date
    $stmt = $conn->prepare("INSERT INTO sales (customer_id, user_id, total_amount, payment_method, sale_date) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("iids", $customer_id, $user['user_id'], $total_amount, $payment_method);
    
    if ($stmt->execute()) {
        $sale_id = $conn->insert_id;
        
        // Insert sale details and update stock
        foreach ($cart_items as $item) {
            $subtotal = $item['quantity'] * $item['price'];
            
            // Insert sale detail
            $stmt = $conn->prepare("INSERT INTO sale_details (sale_id, product_id, quantity_sold, selling_price, subtotal) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiidd", $sale_id, $item['product_id'], $item['quantity'], $item['price'], $subtotal);
            $stmt->execute();
            
            // Update product stock
            $stmt = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ?");
            $stmt->bind_param("ii", $item['quantity'], $item['product_id']);
            $stmt->execute();
        }
        
        // If payment method is Credit, update customer credit balance
        if ($payment_method === 'Credit' && $customer_id) {
            $stmt = $conn->prepare("UPDATE customers SET credit_balance = credit_balance + ? WHERE customer_id = ?");
            $stmt->bind_param("di", $total_amount, $customer_id);
            $stmt->execute();
        }
        
        $message = "Sale completed successfully! Sale ID: #$sale_id";
    } else {
        $message = 'Failed to complete sale.';
    }
}

// Get products
$products = $conn->query("SELECT p.*, c.category_name FROM products p LEFT JOIN categories c ON p.category_id = c.category_id WHERE p.stock_quantity > 0 ORDER BY p.product_name");

// Get customers
$customers = $conn->query("SELECT * FROM customers ORDER BY full_name");
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo Language::getText('point_of_sale', $lang); ?> - SmartSHOP</title>
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
            <h1>🏪 <?php echo Language::getText('point_of_sale', $lang); ?></h1>
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

        <div class="pos-content">
            <?php if ($message): ?>
                <div class="alert <?php echo strpos($message, 'success') ? 'alert-success' : 'alert-error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="pos-layout">
                <div class="products-section">
                    <h2><?php echo Language::getText('products', $lang); ?></h2>
                    <div class="product-search">
                        <input type="text" id="search" placeholder="<?php echo Language::getText('search_products', $lang); ?>" class="form-input">
                    </div>
                    <div class="products-grid" id="products-grid">
                        <?php while ($product = $products->fetch_assoc()): ?>
                            <div class="product-card" data-name="<?php echo strtolower($product['product_name']); ?>">
                                <h4><?php echo $product['product_name']; ?></h4>
                                <p class="category"><?php echo $product['category_name']; ?></p>
                                <p class="price"><?php echo number_format($product['selling_price']); ?> RWF</p>
                                <p class="stock">Stock: <?php echo $product['stock_quantity']; ?></p>
                                <button onclick="addToCart(<?php echo $product['product_id']; ?>, '<?php echo $product['product_name']; ?>', <?php echo $product['selling_price']; ?>, <?php echo $product['stock_quantity']; ?>)" class="btn-add-cart"><?php echo Language::getText('add_to_cart', $lang); ?></button>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <div class="cart-section">
                    <h2><?php echo Language::getText('shopping_cart', $lang); ?></h2>
                    <div class="cart-items" id="cart-items">
                        <p class="empty-cart"><?php echo Language::getText('cart_empty', $lang); ?></p>
                    </div>
                    
                    <div class="cart-total">
                        <h3><?php echo Language::getText('total', $lang); ?>: <span id="cart-total">0</span> RWF</h3>
                    </div>

                    <div class="checkout-form">
                        <form method="POST" id="checkout-form">
                            <div class="form-group">
                                <label class="form-label"><?php echo Language::getText('customer_optional', $lang); ?></label>
                                <select name="customer_id" class="form-input">
                                    <option value=""><?php echo Language::getText('walk_in_customer', $lang); ?></option>
                                    <?php while ($customer = $customers->fetch_assoc()): ?>
                                        <option value="<?php echo $customer['customer_id']; ?>">
                                            <?php echo $customer['full_name']; ?> - <?php echo $customer['phone_number']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label"><?php echo Language::getText('payment_method', $lang); ?></label>
                                <select name="payment_method" class="form-input" required>
                                    <option value="Cash"><?php echo Language::getText('cash', $lang); ?></option>
                                    <option value="Mobile Money"><?php echo Language::getText('mobile_money', $lang); ?></option>
                                    <option value="Credit">Credit</option>
                                </select>
                            </div>

                            <input type="hidden" name="cart_items" id="cart-items-input">
                            <input type="hidden" name="total_amount" id="total-amount-input">
                            <input type="hidden" name="complete_sale" value="1">

                            <button type="submit" class="btn btn-complete" id="complete-sale-btn" disabled><?php echo Language::getText('complete_sale', $lang); ?></button>
                            <button type="button" onclick="clearCart()" class="btn btn-clear"><?php echo Language::getText('clear_cart', $lang); ?></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <?php include __DIR__ . '/../../includes/footer.php'; ?>
    </div>

    <style>
    .pos-content {
        padding: 2rem;
        max-width: 1400px;
        margin: 0 auto;
    }
    
    .pos-layout {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 2rem;
    }
    
    .products-section, .cart-section {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .product-search {
        margin-bottom: 1rem;
    }
    
    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1rem;
        max-height: 600px;
        overflow-y: auto;
    }
    
    .product-card {
        background: var(--background);
        padding: 1rem;
        border-radius: 8px;
        text-align: center;
        border: 1px solid #eee;
    }
    
    .product-card h4 {
        color: var(--primary);
        margin-bottom: 0.5rem;
    }
    
    .product-card .category {
        color: #666;
        font-size: 0.8rem;
        margin-bottom: 0.5rem;
    }
    
    .product-card .price {
        font-weight: bold;
        color: var(--accent);
        margin-bottom: 0.5rem;
    }
    
    .product-card .stock {
        font-size: 0.8rem;
        color: #666;
        margin-bottom: 1rem;
    }
    
    .btn-add-cart {
        background: var(--accent);
        color: white;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 5px;
        cursor: pointer;
        width: 100%;
    }
    
    .cart-items {
        min-height: 300px;
        max-height: 400px;
        overflow-y: auto;
        margin-bottom: 1rem;
    }
    
    .cart-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem;
        border-bottom: 1px solid #eee;
    }
    
    .cart-item-info {
        flex: 1;
    }
    
    .cart-item-controls {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .qty-btn {
        background: var(--secondary);
        color: white;
        border: none;
        width: 25px;
        height: 25px;
        border-radius: 3px;
        cursor: pointer;
    }
    
    .cart-total {
        background: var(--primary);
        color: white;
        padding: 1rem;
        border-radius: 5px;
        text-align: center;
        margin-bottom: 1rem;
    }
    
    .btn-complete {
        background: var(--success);
        width: 100%;
        margin-bottom: 0.5rem;
    }
    
    .btn-clear {
        background: var(--error);
        width: 100%;
    }
    
    .empty-cart {
        text-align: center;
        color: #666;
        font-style: italic;
        padding: 2rem;
    }
    </style>

    <script>
        let cart = [];
        
        function addToCart(productId, productName, price, stock) {
            const existingItem = cart.find(item => item.product_id === productId);
            
            if (existingItem) {
                if (existingItem.quantity < stock) {
                    existingItem.quantity++;
                } else {
                    alert('Not enough stock available');
                    return;
                }
            } else {
                cart.push({
                    product_id: productId,
                    name: productName,
                    price: parseFloat(price),
                    quantity: 1,
                    stock: stock
                });
            }
            
            updateCartDisplay();
        }
        
        function updateQuantity(productId, change) {
            const item = cart.find(item => item.product_id === productId);
            if (item) {
                item.quantity += change;
                if (item.quantity <= 0) {
                    removeFromCart(productId);
                } else if (item.quantity > item.stock) {
                    item.quantity = item.stock;
                    alert('Not enough stock available');
                }
                updateCartDisplay();
            }
        }
        
        function removeFromCart(productId) {
            cart = cart.filter(item => item.product_id !== productId);
            updateCartDisplay();
        }
        
        function updateCartDisplay() {
            const cartItems = document.getElementById('cart-items');
            const cartTotal = document.getElementById('cart-total');
            const completeBtn = document.getElementById('complete-sale-btn');
            
            if (cart.length === 0) {
                cartItems.innerHTML = '<p class="empty-cart">Cart is empty</p>';
                cartTotal.textContent = '0';
                completeBtn.disabled = true;
                document.getElementById('total-amount-input').value = 0;
            } else {
                let html = '';
                let total = 0;
                
                cart.forEach(item => {
                    const subtotal = parseFloat(item.quantity) * parseFloat(item.price);
                    total += subtotal;
                    
                    html += `
                        <div class="cart-item">
                            <div class="cart-item-info">
                                <strong>${item.name}</strong><br>
                                ${item.price} RWF x ${item.quantity} = ${subtotal.toFixed(2)} RWF
                            </div>
                            <div class="cart-item-controls">
                                <button class="qty-btn" onclick="updateQuantity(${item.product_id}, -1)">-</button>
                                <span>${item.quantity}</span>
                                <button class="qty-btn" onclick="updateQuantity(${item.product_id}, 1)">+</button>
                                <button class="qty-btn" onclick="removeFromCart(${item.product_id})" style="background: var(--error);">×</button>
                            </div>
                        </div>
                    `;
                });
                
                cartItems.innerHTML = html;
                cartTotal.textContent = total.toLocaleString();
                completeBtn.disabled = false;
                document.getElementById('total-amount-input').value = total.toFixed(2);
            }
            
            document.getElementById('cart-items-input').value = JSON.stringify(cart);
            console.log('Cart total:', total); // Debug
        }
        
        function clearCart() {
            cart = [];
            updateCartDisplay();
        }
        
        // Search functionality
        document.getElementById('search').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const products = document.querySelectorAll('.product-card');
            
            products.forEach(product => {
                const productName = product.dataset.name;
                if (productName.includes(searchTerm)) {
                    product.style.display = 'block';
                } else {
                    product.style.display = 'none';
                }
            });
        });
        
        function changeLanguage(lang) {
            window.location.href = '?lang=' + lang;
        }
    </script>
</body>
</html>