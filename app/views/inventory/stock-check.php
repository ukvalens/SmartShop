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

$search_term = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 8;
$offset = ($page - 1) * $limit;

// Build search query
$where_conditions = [];
$params = [];
$types = '';

if ($search_term) {
    $where_conditions[] = "(p.product_name LIKE ? OR p.barcode LIKE ?)";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
    $types .= 'ss';
}

if ($category_filter) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
    $types .= 'i';
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM products p LEFT JOIN categories c ON p.category_id = c.category_id $where_clause";
$count_stmt = $conn->prepare($count_query);
if ($params) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_products = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_products / $limit);

$query = "
    SELECT p.*, c.category_name,
           CASE WHEN p.stock_quantity <= p.reorder_level THEN 1 ELSE 0 END as low_stock
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    $where_clause
    ORDER BY p.product_name
    LIMIT $limit OFFSET $offset
";

$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result();

// Get categories for filter
$categories = $conn->query("SELECT * FROM categories ORDER BY category_name");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Check - SmartSHOP</title>
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
            <option value="en">English</option>
            <option value="rw">Kinyarwanda</option>
        </select>
    </div>

    <div class="dashboard-container">
        <?php Navigation::renderNav($user['role'], $_GET['lang'] ?? 'en'); ?>
        
        <header class="header">
            <h1>📦 Stock Check</h1>
            <div class="user-info">
                <div class="user-profile">
                    <img src="../../../uploads/profiles/<?php echo $user['user_id']; ?>.jpg?v=<?php echo time(); ?>" alt="Profile" class="profile-img" onerror="this.src='../../../uploads/profiles/default.jpg'">
                    <div class="user-details">
                        <span class="user-name"><?php echo $user['full_name']; ?></span>
                        <span class="user-role"><?php echo $user['role']; ?></span>
                    </div>
                    <div class="user-menu">
                        <a href="../profile/index.php" class="profile-link"><?php echo Language::getText('profile', $lang); ?></a>
                        <a href="../../controllers/logout.php" class="btn-logout"><?php echo Language::getText('logout', $lang); ?></a>
                    </div>
                </div>
            </div>
        </header>

        <div class="content">
            <div class="search-controls">
                <form method="GET" class="search-form">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Search by name or barcode..." class="form-input">
                    <select name="category" class="form-input">
                        <option value="">All Categories</option>
                        <?php while ($category = $categories->fetch_assoc()): ?>
                            <option value="<?php echo $category['category_id']; ?>" <?php echo $category_filter == $category['category_id'] ? 'selected' : ''; ?>>
                                <?php echo $category['category_name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" class="btn">Search</button>
                    <a href="?" class="btn btn-secondary">Clear</a>
                </form>
            </div>

            <div class="stock-results">
                <h2>Product Stock Information</h2>
                <div class="products-grid">
                    <?php while ($product = $products->fetch_assoc()): ?>
                        <div class="product-card <?php echo $product['low_stock'] ? 'low-stock' : ''; ?>">
                            <div class="product-header">
                                <h3><?php echo $product['product_name']; ?></h3>
                                <?php if ($product['barcode']): ?>
                                    <span class="barcode">📊 <?php echo $product['barcode']; ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-details">
                                <div class="detail-row">
                                    <span class="label">Category:</span>
                                    <span class="value"><?php echo $product['category_name']; ?></span>
                                </div>
                                
                                <div class="detail-row">
                                    <span class="label">Stock:</span>
                                    <span class="value stock-level <?php echo $product['low_stock'] ? 'low' : 'normal'; ?>">
                                        <?php echo $product['stock_quantity']; ?> units
                                    </span>
                                </div>
                                
                                <div class="detail-row">
                                    <span class="label">Reorder Level:</span>
                                    <span class="value"><?php echo $product['reorder_level']; ?> units</span>
                                </div>
                                
                                <div class="detail-row">
                                    <span class="label">Selling Price:</span>
                                    <span class="value price"><?php echo number_format($product['selling_price']); ?> RWF</span>
                                </div>
                                
                                <div class="detail-row">
                                    <span class="label">Status:</span>
                                    <?php if ($product['low_stock']): ?>
                                        <span class="status low-stock-status">⚠️ Low Stock</span>
                                    <?php elseif ($product['stock_quantity'] == 0): ?>
                                        <span class="status out-of-stock">❌ Out of Stock</span>
                                    <?php else: ?>
                                        <span class="status in-stock">✅ In Stock</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search_term); ?>&category=<?php echo $category_filter; ?>" class="page-btn">Previous</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search_term); ?>&category=<?php echo $category_filter; ?>" class="page-btn <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search_term); ?>&category=<?php echo $category_filter; ?>" class="page-btn">Next</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
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
    .search-controls {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }
    
    .search-form {
        display: flex;
        gap: 1rem;
        align-items: center;
        flex-wrap: wrap;
    }
    
    .search-form input,
    .search-form select {
        flex: 1;
        min-width: 200px;
    }
    
    .btn-secondary {
        background: var(--secondary);
    }
    
    .stock-results {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 0.75rem;
        margin-top: 1rem;
    }
    
    .product-card {
        background: var(--background);
        padding: 0.5rem;
        border-radius: 6px;
        border-left: 3px solid var(--accent);
    }
    
    .product-card.low-stock {
        border-left-color: #dc3545;
        background: #fff3cd;
    }
    
    .product-header {
        margin-bottom: 0.4rem;
        padding-bottom: 0.2rem;
        border-bottom: 1px solid #ddd;
    }
    
    .product-header h3 {
        margin: 0;
        color: var(--primary);
        font-size: 0.9rem;
    }
    
    .barcode {
        font-size: 0.7rem;
        color: #666;
    }
    
    .detail-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.15rem;
        font-size: 0.8rem;
    }
    
    .label {
        font-weight: 500;
        color: #666;
    }
    
    .value {
        font-weight: 600;
    }
    
    .stock-level.low {
        color: #dc3545;
    }
    
    .stock-level.normal {
        color: #28a745;
    }
    
    .price {
        color: var(--accent);
    }
    
    .status {
        padding: 0.1rem 0.3rem;
        border-radius: 3px;
        font-size: 0.7rem;
        font-weight: bold;
    }
    
    .in-stock {
        background: #d4edda;
        color: #155724;
    }
    
    .low-stock-status {
        background: #fff3cd;
        color: #856404;
    }
    
    .out-of-stock {
        background: #f8d7da;
        color: #721c24;
    }
    
    .pagination {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 2rem;
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
        function changeLanguage(lang) {
            const url = new URL(window.location);
            url.searchParams.set('lang', lang);
            window.location.href = url.toString();
        }
    </script>
</body>
</html>