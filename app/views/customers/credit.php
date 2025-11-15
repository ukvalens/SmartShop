<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../helpers/Language.php';
require_once __DIR__ . '/../../helpers/EmailHelper.php';
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

// Handle new credit entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_credit'])) {
    $customer_id = $_POST['customer_id'];
    $amount = $_POST['amount'];
    $due_date = $_POST['due_date'];
    $items = $_POST['items'];
    $notes = $_POST['notes'] ?? '';
    $description = "Items: " . $items . ($notes ? " | Notes: " . $notes : "");
    
    $stmt = $conn->prepare("INSERT INTO customer_credits (customer_id, amount, due_date, description, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->bind_param("idss", $customer_id, $amount, $due_date, $description);
    
    if ($stmt->execute()) {
        $stmt = $conn->prepare("UPDATE customers SET credit_balance = credit_balance + ? WHERE customer_id = ?");
        $stmt->bind_param("di", $amount, $customer_id);
        $stmt->execute();
        $message = 'Credit added successfully!';
    } else {
        $message = 'Failed to add credit.';
    }
}

// Mark credit as paid
if (isset($_GET['mark_paid'])) {
    $credit_id = $_GET['mark_paid'];
    $stmt = $conn->prepare("UPDATE customer_credits SET status = 'paid' WHERE credit_id = ?");
    $stmt->bind_param("i", $credit_id);
    if ($stmt->execute()) {
        $message = 'Credit marked as paid!';
    }
}

// Send individual reminder
if (isset($_GET['send_reminder'])) {
    $credit_id = $_GET['send_reminder'];
    $credit_data = $conn->query("SELECT cc.*, c.full_name, c.email FROM customer_credits cc JOIN customers c ON cc.customer_id = c.customer_id WHERE cc.credit_id = $credit_id")->fetch_assoc();
    
    if ($credit_data && $credit_data['email']) {
        $emailHelper = new EmailHelper();
        $subject = "Payment Reminder - SmartSHOP";
        $body = "<h2>Payment Reminder</h2><p>Dear {$credit_data['full_name']},</p><p>Your payment of <strong>{$credit_data['amount']} RWF</strong> is due on <strong>{$credit_data['due_date']}</strong>.</p><p>Please make your payment as soon as possible.</p>";
        
        if ($emailHelper->sendEmail($credit_data['email'], $subject, $body)) {
            $message = 'Reminder email sent successfully!';
        } else {
            $message = 'Failed to send reminder email.';
        }
    }
}

// Send reminder emails for due credits
if (isset($_GET['send_reminders'])) {
    $due_credits = $conn->query("
        SELECT cc.*, c.full_name, c.email, c.phone_number 
        FROM customer_credits cc
        JOIN customers c ON cc.customer_id = c.customer_id
        WHERE cc.status = 'pending' 
        AND cc.due_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)
        AND c.email IS NOT NULL AND c.email != ''
    ");
    
    $emailHelper = new EmailHelper();
    $sent_count = 0;
    
    while ($credit = $due_credits->fetch_assoc()) {
        $subject = "Payment Reminder - SmartSHOP";
        $body = "
        <h2>Payment Reminder</h2>
        <p>Dear {$credit['full_name']},</p>
        <p>This is a reminder that your payment of <strong>{$credit['amount']} RWF</strong> is due on <strong>{$credit['due_date']}</strong>.</p>
        <p>Description: {$credit['description']}</p>
        <p>Please make your payment as soon as possible.</p>
        <p>Thank you,<br>SmartSHOP Team</p>
        ";
        
        if ($emailHelper->sendEmail($credit['email'], $subject, $body)) {
            $sent_count++;
        }
    }
    
    $message = "Sent {$sent_count} reminder emails.";
}

// Get customers for dropdown
$customers = $conn->query("SELECT customer_id, full_name, email FROM customers ORDER BY full_name");

// Get credit records with due date alerts
$credits = $conn->query("
    SELECT cc.*, c.full_name, c.email, c.phone_number,
           DATEDIFF(cc.due_date, CURDATE()) as days_until_due
    FROM customer_credits cc
    JOIN customers c ON cc.customer_id = c.customer_id
    WHERE cc.status = 'pending'
    ORDER BY cc.due_date ASC
");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credit Management - SmartSHOP</title>
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
            <h1>💳 Credit Management</h1>
            <div class="user-info">
                <div class="user-profile">
                    <img src="../../../uploads/profiles/<?php echo $user['user_id']; ?>.jpg?v=<?php echo time(); ?>" alt="Profile" class="profile-img" onerror="this.src='../../../uploads/profiles/default.jpg'">
                    <div class="user-details">
                        <span class="user-name"><?php echo $user['full_name']; ?></span>
                        <span class="user-role"><?php echo $user['role']; ?></span>
                    </div>
                    <div class="user-menu">
                        <a href="../profile/index.php" class="profile-link"><?php echo Language::get('profile', $lang); ?></a>
                        <a href="../../controllers/logout.php" class="btn-logout"><?php echo Language::get('logout', $lang); ?></a>
                    </div>
                </div>
            </div>
        </header>

        <div class="content">
            <?php if ($message): ?>
                <div class="alert <?php echo strpos($message, 'success') || strpos($message, 'Sent') ? 'alert-success' : 'alert-error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="credit-actions">
                <button onclick="openAddModal()" class="btn"><?php echo Language::get('add_credit', $lang); ?></button>
                <a href="?send_reminders=1" class="btn btn-warning" onclick="return confirm('Send reminder emails to customers with due payments?')"><?php echo Language::get('send_reminders', $lang); ?></a>
            </div>

            <div class="credits-table">
                <h2><?php echo Language::get('credit_records', $lang); ?></h2>
                <table>
                    <thead>
                        <tr>
                            <th><?php echo Language::get('customer', $lang); ?></th>
                            <th><?php echo Language::get('amount', $lang); ?></th>
                            <th><?php echo Language::get('due_date', $lang); ?></th>
                            <th><?php echo Language::get('days_left', $lang); ?></th>
                            <th><?php echo Language::get('description', $lang); ?></th>
                            <th><?php echo Language::get('status', $lang); ?></th>
                            <th><?php echo Language::get('actions', $lang); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($credit = $credits->fetch_assoc()): ?>
                            <tr class="<?php echo $credit['days_until_due'] <= 3 ? 'due-soon' : ''; ?>">
                                <td>
                                    <strong><?php echo $credit['full_name']; ?></strong>
                                    <br><small><?php echo $credit['email']; ?></small>
                                </td>
                                <td><?php echo number_format($credit['amount']); ?> RWF</td>
                                <td><?php echo date('M d, Y', strtotime($credit['due_date'])); ?></td>
                                <td>
                                    <span class="days-left <?php echo $credit['days_until_due'] <= 0 ? 'overdue' : ($credit['days_until_due'] <= 3 ? 'due-soon' : ''); ?>">
                                        <?php 
                                        if ($credit['days_until_due'] < 0) {
                                            echo abs($credit['days_until_due']) . ' days overdue';
                                        } elseif ($credit['days_until_due'] == 0) {
                                            echo 'Due today';
                                        } else {
                                            echo $credit['days_until_due'] . ' days';
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo $credit['description']; ?></td>
                                <td>
                                    <span class="status-<?php echo $credit['status']; ?>"><?php echo ucfirst($credit['status']); ?></span>
                                </td>
                                <td>
                                    <?php if ($credit['email']): ?>
                                        <button onclick="sendReminder(<?php echo $credit['credit_id']; ?>)" class="btn-small"><?php echo Language::get('remind', $lang); ?></button>
                                    <?php endif; ?>
                                    <button onclick="markPaid(<?php echo $credit['credit_id']; ?>)" class="btn-small btn-success"><?php echo Language::get('mark_paid', $lang); ?></button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <?php include __DIR__ . '/../../includes/footer.php'; ?>
    </div>

    <!-- Add Credit Modal -->
    <div id="add-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2><?php echo Language::get('add_credit', $lang); ?></h2>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label"><?php echo Language::get('customer', $lang); ?></label>
                    <select name="customer_id" id="customer-select" class="form-input" onchange="loadCustomerItems()" required>
                        <option value=""><?php echo Language::get('select_customer', $lang); ?></option>
                        <?php while ($customer = $customers->fetch_assoc()): ?>
                            <option value="<?php echo $customer['customer_id']; ?>"><?php echo $customer['full_name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo Language::get('items_taken', $lang); ?></label>
                    <textarea name="items" id="items-field" class="form-input" rows="3" placeholder="Select customer to load recent purchases" required></textarea>
                    <small>Recent purchases will be loaded automatically</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Amount (RWF)</label>
                    <input type="number" name="amount" class="form-input" step="0.01" required>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo Language::get('due_date', $lang); ?></label>
                    <input type="date" name="due_date" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo Language::get('additional_notes', $lang); ?></label>
                    <textarea name="notes" class="form-input" rows="2" placeholder="Optional additional information"></textarea>
                </div>
                <button type="submit" name="add_credit" class="btn"><?php echo Language::get('add_credit', $lang); ?></button>
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
    .credit-actions {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
        display: flex;
        gap: 1rem;
    }
    
    .credits-table {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .credits-table table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .credits-table th,
    .credits-table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    
    .due-soon {
        background: #fff3cd;
    }
    
    .overdue {
        color: red;
        font-weight: bold;
    }
    
    .days-left.due-soon {
        color: orange;
        font-weight: bold;
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
        margin: 2vh auto;
        padding: 1.5rem;
        border-radius: 10px;
        width: 90%;
        max-width: 500px;
        max-height: 85vh;
        overflow-y: auto;
    }
    
    @media (max-width: 1366px) and (max-height: 768px) {
        .modal-content {
            margin: 1vh auto;
            padding: 1rem;
            max-height: 80vh;
        }
        
        .form-group {
            margin-bottom: 0.8rem;
        }
    }
    
    .btn-small {
        padding: 0.3rem 0.6rem;
        font-size: 0.8rem;
        margin: 0 2px;
        border: none;
        border-radius: 3px;
        cursor: pointer;
        background: var(--accent);
        color: white;
    }
    
    .btn-success {
        background: #28a745;
    }
    </style>

    <script>
        function openAddModal() {
            document.getElementById('add-modal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('add-modal').style.display = 'none';
        }
        
        function sendReminder(creditId) {
            if (confirm('Send payment reminder email?')) {
                window.location.href = '?send_reminder=' + creditId;
            }
        }
        
        function markPaid(creditId) {
            if (confirm('Mark this credit as paid?')) {
                window.location.href = '?mark_paid=' + creditId;
            }
        }
        
        function changeLanguage(lang) {
            window.location.href = '?lang=' + lang;
        }
        
        function loadCustomerItems() {
            const customerId = document.getElementById('customer-select').value;
            const itemsField = document.getElementById('items-field');
            
            if (!customerId) {
                itemsField.value = '';
                return;
            }
            
            fetch('get_customer_items.php?customer_id=' + customerId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        itemsField.value = data.items;
                    } else {
                        itemsField.value = 'No recent purchases found';
                    }
                })
                .catch(error => {
                    itemsField.value = 'Error loading items';
                });
        }
    </script>
</body>
</html>