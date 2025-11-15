<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../helpers/Navigation.php';
require_once __DIR__ . '/../../../config/database.php';

$auth = new AuthController();
if (!$auth->isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

$user = $auth->getCurrentUser();
$lang = $_GET['lang'] ?? 'en';
$product_id = $_GET['id'] ?? 0;
$db = new Database();
$conn = $db->getConnection();
$message = '';

// Handle product update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $product_name = $_POST['product_name'];
    $category_id = $_POST['category_id'];
    $barcode = $_POST['barcode'];
    $cost_price = $_POST['cost_price'];
    $selling_price = $_POST['selling_price'];
    $reorder_level = $_POST['reorder_level'];
    
    $stmt = $conn->prepare("UPDATE products SET product_name=?, category_id=?, barcode=?, cost_price=?, selling_price=?, reorder_level=? WHERE product_id=?");
    $stmt->bind_param("sisddii", $product_name, $category_id, $barcode, $cost_price, $selling_price, $reorder_level, $product_id);
    
    if ($stmt->execute()) {
        $message = 'Product updated successfully!';
    } else {
        $message = 'Failed to update product.';
    }
}

// Get product details
$product = $conn->query("SELECT * FROM products WHERE product_id = $product_id")->fetch_assoc();
if (!$product) {
    header('Location: index.php?lang=' . $lang);
    exit;
}

// Get categories
$categories = $conn->query("SELECT * FROM categories ORDER BY category_name");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Edit Product - SmartSHOP</title>
    <link rel="stylesheet" href="../../../public/css/main.css">
    <link rel="stylesheet" href="../../../public/css/dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <?php Navigation::renderNav($user['role'], $lang); ?>
        
        <header class="header">
            <h1>✏️ Edit Product</h1>
            <div class="user-info">
                <a href="index.php?lang=<?php echo $lang; ?>" class="btn">Back to Inventory</a>
            </div>
        </header>

        <div class="content">
            <?php if ($message): ?>
                <div class="alert <?php echo strpos($message, 'success') ? 'alert-success' : 'alert-error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="edit-form">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Product Name</label>
                        <input type="text" name="product_name" value="<?php echo $product['product_name']; ?>" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-input" required>
                            <?php while ($cat = $categories->fetch_assoc()): ?>
                                <option value="<?php echo $cat['category_id']; ?>" <?php echo $cat['category_id'] == $product['category_id'] ? 'selected' : ''; ?>>
                                    <?php echo $cat['category_name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Barcode</label>
                        <input type="text" name="barcode" value="<?php echo $product['barcode']; ?>" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Cost Price (RWF)</label>
                        <input type="number" name="cost_price" value="<?php echo $product['cost_price']; ?>" class="form-input" step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Selling Price (RWF)</label>
                        <input type="number" name="selling_price" value="<?php echo $product['selling_price']; ?>" class="form-input" step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Current Stock</label>
                        <input type="text" value="<?php echo $product['stock_quantity']; ?>" class="form-input" readonly>
                        <small>Use stock adjustment to change quantity</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Reorder Level</label>
                        <input type="number" name="reorder_level" value="<?php echo $product['reorder_level']; ?>" class="form-input" min="0" required>
                    </div>
                    
                    <button type="submit" name="update_product" class="btn">Update Product</button>
                </form>
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
    .edit-form {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        max-width: 600px;
        margin: 0 auto;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
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
</body>
</html>