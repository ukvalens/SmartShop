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

$product_id = $_GET['id'] ?? 0;
$db = new Database();
$conn = $db->getConnection();

// Get product details
$product = $conn->query("SELECT * FROM products WHERE product_id = $product_id")->fetch_assoc();
if (!$product) {
    echo "Product not found";
    exit;
}

// Get stock adjustments history
$history = $conn->query("
    SELECT sa.*, u.full_name 
    FROM stock_adjustments sa
    JOIN users u ON sa.user_id = u.user_id
    WHERE sa.product_id = $product_id
    ORDER BY sa.created_at DESC
");

// Get sales history
$sales_history = $conn->query("
    SELECT sd.*, s.sale_date, u.full_name as cashier_name
    FROM sale_details sd
    JOIN sales s ON sd.sale_id = s.sale_id
    JOIN users u ON s.user_id = u.user_id
    WHERE sd.product_id = $product_id
    ORDER BY s.sale_date DESC
    LIMIT 20
");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Product History - <?php echo $product['product_name']; ?></title>
    <link rel="stylesheet" href="../../../public/css/main.css">
    <style>
        .language-selector {
            position: fixed !important;
            top: 20px !important;
            right: 20px !important;
            left: auto !important;
            z-index: 1000 !important;
        }
    body { font-family: Arial, sans-serif; padding: 1rem; }
    .header { text-align: center; margin-bottom: 2rem; }
    .section { margin-bottom: 2rem; }
    table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
    th, td { padding: 0.5rem; text-align: left; border-bottom: 1px solid #ddd; }
    th { background: #f8f9fa; font-weight: bold; }
    .positive { color: green; font-weight: bold; }
    .negative { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📦 Product History</h1>
        <h2><?php echo $product['product_name']; ?></h2>
        <p>Current Stock: <strong><?php echo $product['stock_quantity']; ?></strong></p>
    </div>

    <div class="section">
        <h3>Stock Adjustments</h3>
        <table>
            <thead>
                <tr>
                    <th><?php echo Language::get('date', $lang); ?></th>
                    <th>Quantity Change</th>
                    <th>Type</th>
                    <th>Reason</th>
                    <th>User</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($adj = $history->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('M d, Y H:i', strtotime($adj['created_at'])); ?></td>
                        <td class="<?php echo $adj['quantity_changed'] > 0 ? 'positive' : 'negative'; ?>">
                            <?php echo $adj['quantity_changed'] > 0 ? '+' : ''; ?><?php echo $adj['quantity_changed']; ?>
                        </td>
                        <td><?php echo $adj['adjustment_type']; ?></td>
                        <td><?php echo $adj['reason']; ?></td>
                        <td><?php echo $adj['full_name']; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h3>Recent Sales (Last 20)</h3>
        <table>
            <thead>
                <tr>
                    <th><?php echo Language::get('date', $lang); ?></th>
                    <th><?php echo Language::get('quantity_sold', $lang); ?></th>
                    <th>Unit Price</th>
                    <th>Subtotal</th>
                    <th>Cashier</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($sale = $sales_history->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('M d, Y H:i', strtotime($sale['sale_date'])); ?></td>
                        <td><?php echo $sale['quantity_sold']; ?></td>
                        <td><?php echo number_format($sale['selling_price']); ?> RWF</td>
                        <td><?php echo number_format($sale['subtotal']); ?> RWF</td>
                        <td><?php echo $sale['cashier_name']; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div style="text-align: center; margin-top: 2rem;">
        <button onclick="window.close()" style="padding: 0.5rem 1rem; background: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer;">Close</button>
    </div>
</body>
</html>