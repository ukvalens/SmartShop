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
$lang = $_GET['lang'] ?? 'en';
$db = new Database();
$conn = $db->getConnection();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = $_POST['amount'];
    $payment_method = $_POST['payment_method'];
    
    $stmt = $conn->prepare("INSERT INTO sales (customer_id, user_id, total_amount, payment_method, sale_date) VALUES (NULL, ?, ?, ?, NOW())");
    $stmt->bind_param("ids", $user['user_id'], $amount, $payment_method);
    
    if ($stmt->execute()) {
        $message = 'Sale recorded successfully!';
    } else {
        $message = 'Failed to record sale.';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Sale - SmartSHOP</title>
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
        <header class="header">
            <h1>💰 Quick Sale Entry</h1>
            <div class="user-info">
                <span><?php echo $user['full_name']; ?> - <?php echo $user['role']; ?></span>
            </div>
        </header>

        <div class="content">
            <?php if ($message): ?>
                <div class="alert <?php echo strpos($message, 'success') ? 'alert-success' : 'alert-error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="quick-sale-form">
                <h2>Record Sale</h2>
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Amount (RWF)</label>
                        <input type="number" name="amount" class="form-input" step="100" min="100" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-input" required>
                            <option value="Cash">💵 Cash</option>
                            <option value="Mobile Money">📱 Mobile Money</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn">Record Sale</button>
                </form>
            </div>

            <div class="today-summary">
                <h2>Today's Summary</h2>
                <?php
                $today = date('Y-m-d');
                $summary = $conn->query("
                    SELECT 
                        payment_method,
                        COUNT(*) as count,
                        SUM(total_amount) as total
                    FROM sales 
                    WHERE DATE(sale_date) = '$today' AND user_id = {$user['user_id']}
                    GROUP BY payment_method
                ");
                ?>
                <table>
                    <thead>
                        <tr>
                            <th>Payment Method</th>
                            <th>Count</th>
                            <th>Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $summary->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['payment_method']; ?></td>
                                <td><?php echo $row['count']; ?></td>
                                <td><?php echo number_format($row['total']); ?> RWF</td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
    .quick-sale-form, .today-summary {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }
    
    .quick-sale-form h2, .today-summary h2 {
        margin-bottom: 1.5rem;
        color: var(--primary);
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
    }
    
    .form-input {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 1rem;
    }
    
    .btn {
        background: var(--primary);
        color: white;
        padding: 0.75rem 2rem;
        border: none;
        border-radius: 5px;
        font-size: 1rem;
        cursor: pointer;
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
    }
    
    th, td {
        padding: 0.75rem;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    
    th {
        background: var(--background);
        font-weight: 600;
    }
    </style>

    <script>
        function changeLanguage(lang) {
            window.location.href = '?lang=' + lang;
        }
    </script>
</body>
</html>