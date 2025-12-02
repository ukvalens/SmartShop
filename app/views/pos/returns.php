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

// Handle return processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_return'])) {
    $sale_id = $_POST['sale_id'];
    $return_reason = $_POST['return_reason'];
    $refund_amount = $_POST['refund_amount'];
    
    // Insert return record
    $stmt = $conn->prepare("INSERT INTO returns (sale_id, user_id, return_reason, refund_amount) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iisd", $sale_id, $user['user_id'], $return_reason, $refund_amount);
    
    if ($stmt->execute()) {
        // Update sale status
        $conn->query("UPDATE sales SET status = 'returned' WHERE sale_id = $sale_id");
        $message = 'Return processed successfully!';
    } else {
        $message = 'Failed to process return.';
    }
}

// Pagination setup
$items_per_page = 7;
$sales_page = isset($_GET['sales_page']) ? (int)$_GET['sales_page'] : 1;
$returns_page = isset($_GET['returns_page']) ? (int)$_GET['returns_page'] : 1;
$sales_offset = ($sales_page - 1) * $items_per_page;
$returns_offset = ($returns_page - 1) * $items_per_page;

// Count total sales for pagination
$sales_count_result = $conn->query("
    SELECT COUNT(*) as total
    FROM sales s
    WHERE DATE(s.sale_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    AND s.status != 'returned'
");
$total_sales = $sales_count_result->fetch_assoc()['total'];
$total_sales_pages = ceil($total_sales / $items_per_page);

// Count total returns for pagination
$returns_count_result = $conn->query("
    SELECT COUNT(*) as total
    FROM returns r
    JOIN sales s ON r.sale_id = s.sale_id
");
$total_returns = $returns_count_result->fetch_assoc()['total'];
$total_returns_pages = ceil($total_returns / $items_per_page);

// Get recent sales for returns with pagination
$recent_sales = $conn->query("
    SELECT s.*, c.full_name as customer_name
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.customer_id
    WHERE DATE(s.sale_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    AND s.status != 'returned'
    ORDER BY s.sale_date DESC
    LIMIT $items_per_page OFFSET $sales_offset
");

// Get recent returns with pagination
$recent_returns = $conn->query("
    SELECT r.*, s.total_amount as original_amount, c.full_name as customer_name, u.full_name as processed_by
    FROM returns r
    JOIN sales s ON r.sale_id = s.sale_id
    LEFT JOIN customers c ON s.customer_id = c.customer_id
    JOIN users u ON r.user_id = u.user_id
    ORDER BY r.created_at DESC
    LIMIT $items_per_page OFFSET $returns_offset
");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo Language::getText('returns_exchanges', $lang); ?> - SmartSHOP</title>
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
            <h1>🔄 <?php echo Language::getText('returns_exchanges', $lang); ?></h1>
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
            <?php if ($message): ?>
                <div class="alert <?php echo strpos($message, 'success') ? 'alert-success' : 'alert-error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="returns-grid">
                <div class="sales-section">
                    <h2><?php echo Language::getText('recent_sales', $lang); ?></h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Sale ID</th>
                                <th><?php echo Language::getText('date', $lang); ?></th>
                                <th><?php echo Language::getText('customer', $lang); ?></th>
                                <th><?php echo Language::getText('amount', $lang); ?></th>
                                <th>Payment</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($sale = $recent_sales->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $sale['sale_id']; ?></td>
                                    <td><?php echo date('M d, H:i', strtotime($sale['sale_date'])); ?></td>
                                    <td><?php echo $sale['customer_name'] ?? 'Walk-in'; ?></td>
                                    <td><?php echo number_format($sale['total_amount']); ?> RWF</td>
                                    <td><?php echo $sale['payment_method']; ?></td>
                                    <td>
                                        <button onclick="openReturnModal(<?php echo $sale['sale_id']; ?>, <?php echo $sale['total_amount']; ?>)" class="btn-small btn-return">Return</button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    
                    <!-- Sales Pagination -->
                    <?php if ($total_sales_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($sales_page > 1): ?>
                                <a href="?sales_page=<?php echo $sales_page - 1; ?>&returns_page=<?php echo $returns_page; ?>&lang=<?php echo $lang; ?>" class="page-btn">« Prev</a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_sales_pages; $i++): ?>
                                <a href="?sales_page=<?php echo $i; ?>&returns_page=<?php echo $returns_page; ?>&lang=<?php echo $lang; ?>" 
                                   class="page-btn <?php echo $i == $sales_page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                            <?php endfor; ?>
                            
                            <?php if ($sales_page < $total_sales_pages): ?>
                                <a href="?sales_page=<?php echo $sales_page + 1; ?>&returns_page=<?php echo $returns_page; ?>&lang=<?php echo $lang; ?>" class="page-btn">Next »</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="returns-section">
                    <h2><?php echo Language::getText('recent_returns', $lang); ?></h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Return ID</th>
                                <th>Sale ID</th>
                                <th><?php echo Language::getText('customer', $lang); ?></th>
                                <th>Refund Amount</th>
                                <th>Reason</th>
                                <th>Processed By</th>
                                <th><?php echo Language::getText('date', $lang); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($return = $recent_returns->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $return['return_id']; ?></td>
                                    <td>#<?php echo $return['sale_id']; ?></td>
                                    <td><?php echo $return['customer_name'] ?? 'Walk-in'; ?></td>
                                    <td><?php echo number_format($return['refund_amount']); ?> RWF</td>
                                    <td><?php echo $return['return_reason']; ?></td>
                                    <td><?php echo $return['processed_by']; ?></td>
                                    <td><?php echo date('M d, H:i', strtotime($return['created_at'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    
                    <!-- Returns Pagination -->
                    <?php if ($total_returns_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($returns_page > 1): ?>
                                <a href="?sales_page=<?php echo $sales_page; ?>&returns_page=<?php echo $returns_page - 1; ?>&lang=<?php echo $lang; ?>" class="page-btn">« Prev</a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_returns_pages; $i++): ?>
                                <a href="?sales_page=<?php echo $sales_page; ?>&returns_page=<?php echo $i; ?>&lang=<?php echo $lang; ?>" 
                                   class="page-btn <?php echo $i == $returns_page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                            <?php endfor; ?>
                            
                            <?php if ($returns_page < $total_returns_pages): ?>
                                <a href="?sales_page=<?php echo $sales_page; ?>&returns_page=<?php echo $returns_page + 1; ?>&lang=<?php echo $lang; ?>" class="page-btn">Next »</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php include __DIR__ . '/../../includes/footer.php'; ?>
    </div>

    <!-- Return Modal -->
    <div id="return-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeReturnModal()">&times;</span>
            <h2>Process Return</h2>
            <form method="POST">
                <input type="hidden" name="sale_id" id="return-sale-id">
                
                <div class="form-group">
                    <label class="form-label">Sale ID</label>
                    <input type="text" id="display-sale-id" class="form-input" readonly>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Original Amount</label>
                    <input type="text" id="original-amount" class="form-input" readonly>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Refund Amount (RWF)</label>
                    <input type="number" name="refund_amount" class="form-input" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Return Reason</label>
                    <select name="return_reason" class="form-input" required>
                        <option value="">Select Reason</option>
                        <option value="Defective Product">Defective Product</option>
                        <option value="Wrong Item">Wrong Item</option>
                        <option value="Customer Changed Mind">Customer Changed Mind</option>
                        <option value="Expired Product">Expired Product</option>
                        <option value="Damaged in Transit">Damaged in Transit</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <button type="submit" name="process_return" class="btn">Process Return</button>
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
    .returns-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .sales-section, .returns-section {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .sales-section table, .returns-section table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
    }
    
    .sales-section th, .sales-section td,
    .returns-section th, .returns-section td {
        padding: 0.4rem 0.5rem;
        text-align: left;
        border-bottom: 1px solid #eee;
        font-size: 0.85rem;
    }
    
    .sales-section th, .returns-section th {
        background: var(--background);
        font-weight: 600;
    }
    
    .btn-small {
        padding: 0.3rem 0.6rem;
        font-size: 0.8rem;
        border: none;
        border-radius: 3px;
        cursor: pointer;
        color: white;
    }
    
    .btn-return {
        background: #dc3545;
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
    
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.5rem;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #eee;
    }
    
    .page-btn {
        padding: 0.5rem 0.75rem;
        text-decoration: none;
        color: var(--text);
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

    <script>
        function openReturnModal(saleId, amount) {
            document.getElementById('return-sale-id').value = saleId;
            document.getElementById('display-sale-id').value = '#' + saleId;
            document.getElementById('original-amount').value = amount.toLocaleString() + ' RWF';
            document.getElementById('return-modal').style.display = 'block';
        }
        
        function closeReturnModal() {
            document.getElementById('return-modal').style.display = 'none';
        }
        
        function changeLanguage(lang) {
            window.location.href = '?lang=' + lang;
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('return-modal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>