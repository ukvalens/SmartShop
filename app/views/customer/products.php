<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../helpers/Language.php';
require_once __DIR__ . '/../../helpers/Navigation.php';
require_once __DIR__ . '/../../../config/database.php';

$auth = new AuthController();
if (!$auth->isLoggedIn() || $auth->getCurrentUser()['role'] !== 'Customer') {
    header('Location: ../auth/login.php');
    exit;
}

$user = $auth->getCurrentUser();
$lang = $_GET['lang'] ?? 'en';
$db = new Database();
$conn = $db->getConnection();

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

// Get categories for filter
$categories = $conn->query("SELECT * FROM categories ORDER BY category_name");

// Build base query for counting
$count_query = "
    SELECT COUNT(*) as total
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    WHERE p.stock_quantity > 0
";

// Build products query
$query = "
    SELECT p.*, c.category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    WHERE p.stock_quantity > 0
";

$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND p.product_name LIKE ?";
    $count_query .= " AND p.product_name LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

if (!empty($category_filter)) {
    $query .= " AND p.category_id = ?";
    $count_query .= " AND p.category_id = ?";
    $params[] = $category_filter;
    $types .= "i";
}

// Get total count
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_products = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_products / $items_per_page);

// Get products with pagination
$query .= " ORDER BY p.product_name LIMIT ? OFFSET ?";
$params[] = $items_per_page;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$products = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Products - SmartSHOP</title>
    <link rel="stylesheet" href="../../../public/css/main.css">
    <link rel="stylesheet" href="../../../public/css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="language-selector">
        <select onchange="changeLanguage(this.value)">
            <option value="en" <?php echo $lang === 'en' ? 'selected' : ''; ?>>🇺🇸 English</option>
            <option value="rw" <?php echo $lang === 'rw' ? 'selected' : ''; ?>>🇷🇼 Kinyarwanda</option>
        </select>
    </div>

    <div class="dashboard-container">
        <?php Navigation::renderNav($user['role'], $lang); ?>
        
        <header class="header">
            <h1>🛍️ Available Products</h1>
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
            <div class="products-section">
                <div class="filters-section">
                    <form method="GET" class="filters-form">
                        <div class="search-group">
                            <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>" class="search-input">
                            <select name="category" class="category-filter">
                                <option value="">All Categories</option>
                                <?php while ($category = $categories->fetch_assoc()): ?>
                                    <option value="<?php echo $category['category_id']; ?>" <?php echo $category_filter == $category['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo $category['category_name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <button type="submit" class="btn-search">
                                <i class="fas fa-search"></i> Search
                            </button>
                            <input type="hidden" name="lang" value="<?php echo $lang; ?>">
                            <input type="hidden" name="page" value="1">
                        </div>
                    </form>
                </div>

                <div class="products-grid">
                    <?php if ($products->num_rows === 0): ?>
                        <div class="no-products">
                            <i class="fas fa-box-open"></i>
                            <h3>No Products Found</h3>
                            <p>No products match your search criteria or all products are out of stock.</p>
                        </div>
                    <?php else: ?>
                        <?php while ($product = $products->fetch_assoc()): ?>
                            <div class="product-card">
                                <div class="product-image">
                                    <i class="fas fa-box"></i>
                                </div>
                                <div class="product-info">
                                    <h3 class="product-name"><?php echo $product['product_name']; ?></h3>
                                    <p class="product-category"><?php echo $product['category_name'] ?? 'Uncategorized'; ?></p>
                                    <div class="product-details">
                                        <div class="price">
                                            <span class="current-price"><?php echo number_format($product['selling_price']); ?> RWF</span>
                                        </div>
                                        <div class="stock-info">
                                            <span class="stock-level <?php echo $product['stock_quantity'] <= 10 ? 'low-stock' : 'in-stock'; ?>">
                                                <?php echo $product['stock_quantity']; ?> in stock
                                            </span>
                                        </div>
                                    </div>
                                    <div class="product-actions">
                                        <button class="btn-add-to-wishlist" onclick="addToWishlist(<?php echo $product['product_id']; ?>)">
                                            <i class="fas fa-heart"></i> Add to Wishlist
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_filter; ?>&lang=<?php echo $lang; ?>" class="page-btn">« Prev</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_filter; ?>&lang=<?php echo $lang; ?>" 
                               class="page-btn <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_filter; ?>&lang=<?php echo $lang; ?>" class="page-btn">Next »</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="shopping-note">
                    <div class="note-card">
                        <i class="fas fa-info-circle"></i>
                        <div class="note-content">
                            <h4>How to Purchase</h4>
                            <p>To buy these products, please visit our physical store. Our cashiers will help you with your purchase and process payments through cash, mobile money, or credit.</p>
                            <div class="store-info">
                                <p><strong>Store Hours:</strong> Monday - Sunday, 8:00 AM - 8:00 PM</p>
                                <p><strong>Location:</strong> SmartShop Store, Kigali</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php include __DIR__ . '/../../includes/footer.php'; ?>
    </div>

    <script>
        function changeLanguage(lang) {
            window.location.href = '?lang=' + lang;
        }
        
        function addToWishlist(productId) {
            // Simple wishlist functionality - could be expanded
            alert('Product added to your wishlist! Visit the store to purchase.');
        }
    </script>

    <style>
        .language-selector {
            position: fixed !important;
            top: 20px !important;
            right: 20px !important;
            z-index: 1000 !important;
        }
        
        .language-selector select {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: white;
            font-size: 0.9rem;
        }
        
        .products-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .filters-section {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .filters-form {
            display: flex;
            justify-content: center;
        }
        
        .search-group {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .search-input, .category-filter {
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .search-input {
            width: 300px;
        }
        
        .btn-search {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        
        .product-card {
            border: 1px solid #eee;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .product-image {
            background: #f8f9fa;
            height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #ddd;
        }
        
        .product-info {
            padding: 1.5rem;
        }
        
        .product-name {
            margin: 0 0 0.5rem 0;
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
        }
        
        .product-category {
            margin: 0 0 1rem 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        .product-details {
            margin-bottom: 1rem;
        }
        
        .current-price {
            font-size: 1.25rem;
            font-weight: bold;
            color: #28a745;
        }
        
        .stock-level {
            font-size: 0.9rem;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            margin-top: 0.5rem;
            display: inline-block;
        }
        
        .in-stock {
            background: #d4edda;
            color: #155724;
        }
        
        .low-stock {
            background: #fff3cd;
            color: #856404;
        }
        
        .btn-add-to-wishlist {
            background: #e9ecef;
            color: #495057;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            width: 100%;
        }
        
        .btn-add-to-wishlist:hover {
            background: #dee2e6;
        }
        
        .no-products {
            grid-column: 1 / -1;
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .no-products i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 1rem;
        }
        
        .shopping-note {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #eee;
        }
        
        .note-card {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid var(--primary);
            display: flex;
            gap: 1rem;
        }
        
        .note-card i {
            font-size: 1.5rem;
            color: var(--primary);
            margin-top: 0.25rem;
        }
        
        .note-content h4 {
            margin: 0 0 0.5rem 0;
            color: #333;
        }
        
        .store-info {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #dee2e6;
        }
        
        .store-info p {
            margin: 0.25rem 0;
            font-size: 0.9rem;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin: 2rem 0;
            padding: 1rem 0;
            border-top: 1px solid #eee;
        }
        
        .page-btn {
            padding: 0.5rem 0.75rem;
            text-decoration: none;
            color: #333;
            border: 1px solid #ddd;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .page-btn:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .page-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
    </style>
</body>
</html>