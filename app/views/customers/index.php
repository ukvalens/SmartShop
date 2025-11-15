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

// Handle new customer registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_customer'])) {
    $full_name = $_POST['full_name'];
    $phone_number = $_POST['phone_number'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    
    $stmt = $conn->prepare("INSERT INTO customers (full_name, phone_number, email, address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $full_name, $phone_number, $email, $address);
    
    if ($stmt->execute()) {
        $message = 'Customer added successfully!';
    } else {
        $message = 'Failed to add customer.';
    }
}

// Handle credit payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_credit'])) {
    $customer_id = $_POST['customer_id'];
    $payment_amount = $_POST['payment_amount'];
    
    // Insert payment record
    $stmt = $conn->prepare("INSERT INTO payments (customer_id, amount_paid, payment_method) VALUES (?, ?, 'Cash')");
    $stmt->bind_param("id", $customer_id, $payment_amount);
    
    if ($stmt->execute()) {
        // Update customer credit balance
        $stmt = $conn->prepare("UPDATE customers SET credit_balance = credit_balance - ? WHERE customer_id = ?");
        $stmt->bind_param("di", $payment_amount, $customer_id);
        $stmt->execute();
        
        $message = 'Payment recorded successfully!';
    } else {
        $message = 'Failed to record payment.';
    }
}

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total customers count
$total_result = $conn->query("SELECT COUNT(*) as total FROM customers");
$total_customers = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_customers / $limit);

// Get customers with pagination
$customers = $conn->query("
    SELECT c.*,
           COALESCE(SUM(s.total_amount), 0) as total_purchases,
           COUNT(s.sale_id) as total_orders
    FROM customers c
    LEFT JOIN sales s ON c.customer_id = s.customer_id
    GROUP BY c.customer_id
    ORDER BY c.full_name
    LIMIT $limit OFFSET $offset
");

// Get customers with credit balances
$credit_customers = $conn->query("
    SELECT * FROM customers 
    WHERE credit_balance > 0 
    ORDER BY credit_balance DESC
");
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo Language::get('customer_management', $lang); ?> - SmartSHOP</title>
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
            <h1>👥 <?php echo Language::get('customer_management', $lang); ?></h1>
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

            <div class="customer-actions">
                <button onclick="openAddModal()" class="btn"><?php echo Language::get('add_new_customer', $lang); ?></button>
                <input type="text" id="search" placeholder="<?php echo Language::get('search_customers', $lang); ?>" class="form-input">
            </div>

            <div class="customers-table">
                <h2><?php echo Language::get('all_customers', $lang); ?></h2>
                <table>
                    <thead>
                        <tr>
                            <th><?php echo Language::get('name', $lang); ?></th>
                            <th><?php echo Language::get('phone', $lang); ?></th>
                            <th><?php echo Language::get('email', $lang); ?></th>
                            <th>Loyalty Points</th>
                            <th>Credit Balance</th>
                            <th>Total Purchases</th>
                            <th>Orders</th>
                            <th><?php echo Language::get('actions', $lang); ?></th>
                        </tr>
                    </thead>
                    <tbody id="customers-tbody">
                        <?php while ($customer = $customers->fetch_assoc()): ?>
                            <tr data-name="<?php echo strtolower($customer['full_name']); ?>">
                                <td>
                                    <strong><?php echo $customer['full_name']; ?></strong>
                                </td>
                                <td><?php echo $customer['phone_number']; ?></td>
                                <td><?php echo $customer['email']; ?></td>
                                <td>
                                    <span class="loyalty-points"><?php echo $customer['loyalty_points']; ?> pts</span>
                                </td>
                                <td>
                                    <?php if ($customer['credit_balance'] > 0): ?>
                                        <span class="credit-balance"><?php echo number_format($customer['credit_balance']); ?> RWF</span>
                                    <?php else: ?>
                                        <span class="no-credit">0 RWF</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo number_format($customer['total_purchases']); ?> RWF</td>
                                <td><?php echo $customer['total_orders']; ?></td>
                                <td>
                                    <?php if ($customer['credit_balance'] > 0): ?>
                                        <button onclick="openPaymentModal(<?php echo $customer['customer_id']; ?>, '<?php echo $customer['full_name']; ?>', <?php echo $customer['credit_balance']; ?>)" class="btn-small btn-payment">Pay Credit</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&lang=<?php echo $lang; ?>" class="page-btn"><?php echo Language::get('previous', $lang); ?></a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&lang=<?php echo $lang; ?>" class="page-btn <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&lang=<?php echo $lang; ?>" class="page-btn"><?php echo Language::get('next', $lang); ?></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="credit-summary">
                <h2><?php echo Language::get('credit_management', $lang); ?></h2>
                <div class="credit-cards">
                    <?php while ($credit_customer = $credit_customers->fetch_assoc()): ?>
                        <div class="credit-card">
                            <h4><?php echo $credit_customer['full_name']; ?></h4>
                            <p class="credit-amount"><?php echo number_format($credit_customer['credit_balance']); ?> RWF</p>
                            <p class="phone"><?php echo $credit_customer['phone_number']; ?></p>
                            <button onclick="openPaymentModal(<?php echo $credit_customer['customer_id']; ?>, '<?php echo $credit_customer['full_name']; ?>', <?php echo $credit_customer['credit_balance']; ?>)" class="btn-small">Collect Payment</button>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
        
        <?php include __DIR__ . '/../../includes/footer.php'; ?>
    </div>

    <!-- Add Customer Modal -->
    <div id="add-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddModal()">&times;</span>
            <h2><?php echo Language::get('add_new_customer', $lang); ?></h2>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" name="phone_number" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo Language::get('email', $lang); ?></label>
                    <input type="email" name="email" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo Language::get('address', $lang); ?></label>
                    <textarea name="address" class="form-input" rows="3"></textarea>
                </div>
                <button type="submit" name="add_customer" class="btn">Add Customer</button>
            </form>
        </div>
    </div>

    <!-- Payment Modal -->
    <div id="payment-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closePaymentModal()">&times;</span>
            <h2>Record Credit Payment</h2>
            <form method="POST">
                <input type="hidden" name="customer_id" id="payment-customer-id">
                <p>Customer: <strong id="payment-customer-name"></strong></p>
                <p>Credit Balance: <strong id="payment-credit-balance"></strong> RWF</p>
                
                <div class="form-group">
                    <label class="form-label">Payment Amount</label>
                    <input type="number" name="payment_amount" class="form-input" step="0.01" min="0" required>
                </div>
                
                <button type="submit" name="pay_credit" class="btn">Record Payment</button>
            </form>
        </div>
    </div>

    <style>
    .customer-actions {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
        display: flex;
        gap: 1rem;
        align-items: center;
    }
    
    .customers-table, .credit-summary {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }
    
    .customers-table table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .customers-table th,
    .customers-table td {
        padding: 0.5rem;
        text-align: left;
        border-bottom: 1px solid #eee;
        font-size: 0.9rem;
    }
    
    .customers-table th {
        background: var(--background);
        font-weight: 600;
    }
    
    .loyalty-points {
        color: var(--accent);
        font-weight: bold;
    }
    
    .credit-balance {
        color: red;
        font-weight: bold;
    }
    
    .no-credit {
        color: green;
    }
    
    .credit-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
    }
    
    .credit-card {
        background: #fff3cd;
        padding: 1.5rem;
        border-radius: 8px;
        border-left: 4px solid orange;
    }
    
    .credit-card h4 {
        color: var(--primary);
        margin-bottom: 0.5rem;
    }
    
    .credit-amount {
        font-size: 1.2rem;
        font-weight: bold;
        color: red;
        margin-bottom: 0.5rem;
    }
    
    .phone {
        color: #666;
        margin-bottom: 1rem;
    }
    
    .btn-payment {
        background: var(--success);
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
    }
    
    .page-btn:hover {
        background: var(--accent);
        color: var(--white);
    }
    
    .page-btn.active {
        background: var(--primary);
        color: var(--white);
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
        
        function openAddModal() {
            document.getElementById('add-modal').style.display = 'block';
        }
        
        function closeAddModal() {
            document.getElementById('add-modal').style.display = 'none';
        }
        
        function openPaymentModal(customerId, customerName, creditBalance) {
            document.getElementById('payment-customer-id').value = customerId;
            document.getElementById('payment-customer-name').textContent = customerName;
            document.getElementById('payment-credit-balance').textContent = creditBalance.toLocaleString();
            document.getElementById('payment-modal').style.display = 'block';
        }
        
        function closePaymentModal() {
            document.getElementById('payment-modal').style.display = 'none';
        }
        
        // Search functionality
        document.getElementById('search').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#customers-tbody tr');
            
            rows.forEach(row => {
                const customerName = row.dataset.name;
                if (customerName.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>